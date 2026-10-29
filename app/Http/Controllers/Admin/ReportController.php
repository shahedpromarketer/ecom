<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index()
    {
        return view('admin.reports.index');
    }

    public function productReport(Request $request)
    {
        $from = $request->get('from', now()->subDays(30)->toDateString());
        $to   = $request->get('to', now()->toDateString());

        $data = OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->leftJoin('category_product', 'products.id', '=', 'category_product.product_id')
            ->leftJoin('categories', 'category_product.category_id', '=', 'categories.id')
            ->whereBetween('orders.created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->when(!auth()->user()->is_super_admin, fn($q) => $q->where('orders.tenant_id', auth()->user()->tenant_id))
            ->select(
                'products.name as product',
                'categories.name as category',
                DB::raw('SUM(order_items.qty) as units_sold'),
                DB::raw('SUM(order_items.total) as revenue')
            )
            ->groupBy('products.id', 'products.name', 'categories.name')
            ->orderByDesc('revenue')
            ->get();

        if ($request->get('export') === 'csv') {
            return $this->csvResponse($data, "report-products-{$from}-to-{$to}.csv");
        }

        return response()->json($data);
    }

    public function userReport(Request $request)
    {
        $userReportQ = User::inCurrentTenant()->where('role', 'customer');
        if (!auth()->user()->is_super_admin) { $userReportQ->where('tenant_id', auth()->user()->tenant_id); }
        $data = $userReportQ->withCount('orders')
            ->withSum('orders', 'total')
            ->orderByDesc('orders_sum_total')
            ->get()
            ->map(fn($u) => [
                'name'         => $u->name,
                'phone'        => $u->phone ?? '',
                'email'        => $u->email,
                'orders_count' => $u->orders_count,
                'total_spent'  => $u->orders_sum_total ?? 0,
                'last_order'   => $u->orders()->latest()->value('created_at'),
                'status'       => $u->is_active ? 'Active' : 'Inactive',
            ]);

        if ($request->get('export') === 'csv') {
            return $this->csvResponse($data, 'report-users-' . now()->toDateString() . '.csv');
        }

        return response()->json($data);
    }

    public function stockReport(Request $request)
    {
        $stockQ = ProductVariant::with('product')->where('is_active', true);
        if (!auth()->user()->is_super_admin) {
            $stockQ->whereHas('product', fn($p) => $p->where('tenant_id', auth()->user()->tenant_id));
        }
        $data = $stockQ->orderBy('stock')
            ->get()
            ->map(fn($v) => [
                'product' => $v->product?->name,
                'size'    => $v->size,
                'color'   => $v->color,
                'sku'     => $v->sku,
                'stock'   => $v->stock,
                'status'  => $v->stock === 0 ? 'Out of Stock' : ($v->stock <= 10 ? 'Low Stock' : 'In Stock'),
            ]);

        if ($request->get('export') === 'csv') {
            return $this->csvResponse($data, 'report-stock-' . now()->toDateString() . '.csv');
        }

        return response()->json($data);
    }

    public function paymentReport(Request $request)
    {
        $from = $request->get('from', now()->subDays(30)->toDateString());
        $to   = $request->get('to', now()->toDateString());

        $payQ = Order::with('user')->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59']);
        if (!auth()->user()->is_super_admin) { $payQ->where('tenant_id', auth()->user()->tenant_id); }
        $data = $payQ
            ->get()
            ->map(fn($o) => [
                'order'          => $o->order_number,
                'customer'       => $o->shipping_name,
                'amount'         => $o->total,
                'payment_method' => strtoupper($o->payment_method),
                'payment_status' => ucfirst($o->payment_status),
                'date'           => $o->created_at->format('d M Y'),
            ]);

        if ($request->get('export') === 'csv') {
            return $this->csvResponse($data, "report-payment-{$from}-to-{$to}.csv");
        }

        return response()->json($data);
    }

    private function csvResponse($data, string $filename)
    {
        $rows   = $data instanceof \Illuminate\Support\Collection ? $data->toArray() : $data;
        $handle = fopen('php://temp', 'r+');
        fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM

        if (! empty($rows)) {
            fputcsv($handle, array_keys((array) $rows[0]));
            foreach ($rows as $row) {
                fputcsv($handle, (array) $row);
            }
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
