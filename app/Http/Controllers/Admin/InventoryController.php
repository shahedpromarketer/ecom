<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $query = ProductVariant::with('product')->where('is_active', true);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('sku', 'like', "%{$search}%")
                  ->orWhereHas('product', fn($p) => $p->where('name', 'like', "%{$search}%"));
            });
        }

        $filter = $request->get('filter', 'all');
        match ($filter) {
            'out'  => $query->where('stock', 0),
            'low'  => $query->where('stock', '>', 0)->where('stock', '<=', 10),
            'in'   => $query->where('stock', '>', 10),
            default => null,
        };

        $variants = $query->orderBy('stock')->paginate(30)->withQueryString();

        $totalSkus    = ProductVariant::where('is_active', true)->count();
        $outOfStock   = ProductVariant::where('is_active', true)->where('stock', 0)->count();
        $lowStock     = ProductVariant::where('is_active', true)->where('stock', '>', 0)->where('stock', '<=', 10)->count();

        return view('admin.inventory.index', compact('variants', 'totalSkus', 'outOfStock', 'lowStock', 'filter'));
    }

    public function updateStock(Request $request, ProductVariant $variant)
    {
        $request->validate(['stock' => 'required|integer|min:0']);

        $variant->update(['stock' => $request->stock]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'stock' => $variant->stock]);
        }

        return back()->with('success', 'Stock updated.');
    }

    public function bulkUpdate(Request $request)
    {
        $request->validate(['variants' => 'required|array', 'variants.*.id' => 'required|exists:product_variants,id', 'variants.*.stock' => 'required|integer|min:0']);

        DB::transaction(function () use ($request) {
            foreach ($request->variants as $item) {
                ProductVariant::where('id', $item['id'])->update(['stock' => $item['stock']]);
            }
        });

        return back()->with('success', 'Stock updated in bulk.');
    }
}
