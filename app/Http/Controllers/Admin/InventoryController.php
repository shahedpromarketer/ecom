<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductVariant;
use App\Models\StockLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $base = ProductVariant::query()->where('product_variants.is_active', true);
        if (!auth()->user()->is_super_admin) {
            $base->whereHas('product', fn($p) => $p->where('tenant_id', auth()->user()->tenant_id));
        }

        // Totals for every variant, worked out by the database (not by loading them all).
        $agg = (clone $base)
            ->join('products', 'products.id', '=', 'product_variants.product_id')
            ->selectRaw('COUNT(*) AS skus, COALESCE(SUM(product_variants.stock), 0) AS stock,
                SUM(CASE WHEN product_variants.stock = 0 THEN 1 ELSE 0 END) AS out_count,
                SUM(CASE WHEN product_variants.stock > 0 AND product_variants.stock <= 10 THEN 1 ELSE 0 END) AS low_count,
                COALESCE(SUM((COALESCE(products.sale_price, products.price, 0) + COALESCE(product_variants.extra_price, 0)) * product_variants.stock), 0) AS value')
            ->first();
        $totalSkus       = (int) $agg->skus;
        $totalStock      = (int) $agg->stock;
        $outOfStock      = (int) $agg->out_count;
        $lowStock        = (int) $agg->low_count;
        $grandTotalValue = (float) $agg->value;

        // The list: filtered and searched on the server, 25 rows a page (each row carries its own adjust form).
        $filter = in_array($request->filter, ['in', 'low', 'out'], true) ? $request->filter : 'all';
        $search = trim((string) $request->input('q', ''));
        $variants = (clone $base)->with(['product.images'])
            ->when($filter === 'out', fn ($q) => $q->where('product_variants.stock', 0))
            ->when($filter === 'low', fn ($q) => $q->whereBetween('product_variants.stock', [1, 10]))
            ->when($filter === 'in', fn ($q) => $q->where('product_variants.stock', '>', 10))
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w->where('product_variants.sku', 'like', "%{$search}%")
                ->orWhereHas('product', fn ($p) => $p->where('name', 'like', "%{$search}%"))))
            ->orderBy('product_variants.stock')->orderBy('product_variants.id')
            ->paginate(25)->withQueryString();

        return view('admin.inventory.index', compact('variants', 'totalSkus', 'outOfStock', 'lowStock', 'totalStock', 'grandTotalValue', 'filter', 'search'));
    }

    public function adjust(Request $request, ProductVariant $variant)
    {
        $request->validate([
            'type'  => 'required|in:add,remove,set,return',
            'qty'   => 'required|integer|min:1',
            'note'  => 'nullable|string|max:200',
        ]);

        DB::transaction(function () use ($request, $variant) {
            $before = $variant->stock;
            $qty    = (int) $request->qty;

            $after = match ($request->type) {
                'add', 'return' => $before + $qty,
                'remove'        => max(0, $before - $qty),
                'set'           => $qty,
            };

            $change = $after - $before;

            $variant->update(['stock' => $after]);

            StockLog::create([
                'variant_id' => $variant->id,
                'user_id'    => auth()->id(),
                'order_id'   => null,
                'qty_before' => $before,
                'change'     => $change,
                'qty_after'  => $after,
                'type'       => $request->type,
                'note'       => $request->note,
            ]);
        });

        $variant->refresh();

        return response()->json([
            'success' => true,
            'stock'   => $variant->stock,
        ]);
    }

    public function logs(Request $request, ProductVariant $variant)
    {
        $logs = StockLog::with(['user', 'order'])
            ->where('variant_id', $variant->id)
            ->latest()
            ->take(20)
            ->get()
            ->map(fn($log) => [
                'id'         => $log->id,
                'type'       => $log->type,
                'change'     => $log->change,
                'qty_before' => $log->qty_before,
                'qty_after'  => $log->qty_after,
                'note'       => $log->note,
                'user'       => $log->user?->name ?? 'System',
                'order'      => $log->order?->order_number,
                'date'       => $log->created_at->format('d M Y, h:i A'),
            ]);

        return response()->json($logs);
    }

    public function exportCsv(Request $request)
    {
        $query = ProductVariant::with(['product.images'])->where('is_active', true);
        if (!auth()->user()->is_super_admin) {
            $query->whereHas('product', fn($p) => $p->where('tenant_id', auth()->user()->tenant_id));
        }
        $variants = $query->orderBy('stock')->get();

        $filename = 'inventory_' . now()->format('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($variants) {
            $out = fopen('php://output', 'w');
            // UTF-8 BOM for Excel
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, ['Product', 'Size', 'Color', 'SKU', 'Stock', 'Status', 'Unit Price (৳)', 'Stock Value (৳)']);

            foreach ($variants as $v) {
                $price  = (float) ($v->product?->sale_price ?? $v->product?->price ?? 0)
                        + (float) ($v->extra_price ?? 0);
                $status = $v->stock === 0 ? 'Out of Stock'
                        : ($v->stock <= 10 ? 'Low Stock' : 'In Stock');

                fputcsv($out, [
                    $v->product?->name ?? '',
                    $v->size  ?? '',
                    $v->color ?? '',
                    $v->sku   ?? '',
                    $v->stock,
                    $status,
                    number_format($price, 2),
                    number_format($price * $v->stock, 2),
                ]);
            }

            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }

    // Legacy: direct stock set (kept for bulk update compatibility)
    public function updateStock(Request $request, ProductVariant $variant)
    {
        $request->validate(['stock' => 'required|integer|min:0']);

        $before = $variant->stock;
        $after  = (int) $request->stock;
        $variant->update(['stock' => $after]);

        StockLog::create([
            'variant_id' => $variant->id,
            'user_id'    => auth()->id(),
            'qty_before' => $before,
            'change'     => $after - $before,
            'qty_after'  => $after,
            'type'       => 'set',
            'note'       => 'Manual set via inventory table',
        ]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'stock' => $variant->stock]);
        }

        return back()->with('success', 'Stock updated.');
    }

    public function bulkUpdate(Request $request)
    {
        $request->validate([
            'variants'       => 'required|array',
            'variants.*.id'  => 'required|exists:product_variants,id',
            'variants.*.stock' => 'required|integer|min:0',
        ]);

        DB::transaction(function () use ($request) {
            foreach ($request->variants as $item) {
                $variant = ProductVariant::find($item['id']);
                if (!$variant) continue;
                $before = $variant->stock;
                $after  = (int) $item['stock'];
                $variant->update(['stock' => $after]);
                StockLog::create([
                    'variant_id' => $variant->id,
                    'user_id'    => auth()->id(),
                    'qty_before' => $before,
                    'change'     => $after - $before,
                    'qty_after'  => $after,
                    'type'       => 'set',
                    'note'       => 'Bulk update',
                ]);
            }
        });

        return back()->with('success', 'Stock updated in bulk.');
    }
}
