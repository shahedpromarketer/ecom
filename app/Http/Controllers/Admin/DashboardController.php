<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    private const STATUSES = [
        'pending', 'confirmed', 'processing', 'shipped',
        'delivered', 'completed', 'cancelled', 'returned', 'hold',
    ];

    public function index(Request $request)
    {
        [$from, $to, $rangeLabel] = $this->parseDateRange(
            $request->input('range', 'today'),
            $request
        );

        $start = Carbon::parse($from)->startOfDay();
        $end   = Carbon::parse($to)->endOfDay();

        // ── Summary cards ─────────────────────────────────────────────
        $totalOrders    = Order::whereBetween('created_at', [$start, $end])->count();
        $totalRevenue   = Order::whereBetween('created_at', [$start, $end])
                            ->whereIn('status', ['delivered', 'completed'])->sum('total');
        $pendingOrders  = Order::whereBetween('created_at', [$start, $end])
                            ->where('status', 'pending')->count();
        $inCourier      = Order::whereBetween('created_at', [$start, $end])
                            ->where('status', 'shipped')->count();
        $completedOrders = Order::whereBetween('created_at', [$start, $end])
                            ->where('status', 'completed')->count();
        $conversionRate = $totalOrders > 0
                            ? round(($completedOrders / $totalOrders) * 100, 1)
                            : 0;

        // ── Order status breakdown ─────────────────────────────────────
        $ordersByStatus = [];
        foreach (self::STATUSES as $status) {
            $ordersByStatus[$status] = Order::whereBetween('created_at', [$start, $end])
                ->where('status', $status)->count();
        }

        // ── Order sources (by payment method) ─────────────────────────
        $orderSources = Order::whereBetween('created_at', [$start, $end])
            ->select('payment_method', DB::raw('count(*) as total'))
            ->groupBy('payment_method')
            ->pluck('total', 'payment_method')
            ->toArray();

        // ── Top products by revenue ────────────────────────────────────
        $topProducts = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereBetween('orders.created_at', [$start, $end])
            ->whereIn('orders.status', ['delivered', 'completed'])
            ->select(
                'order_items.product_id',
                'order_items.product_name',
                DB::raw('SUM(order_items.qty) as total_qty'),
                DB::raw('SUM(order_items.total) as total_revenue')
            )
            ->groupBy('order_items.product_id', 'order_items.product_name')
            ->orderByDesc('total_revenue')
            ->limit(8)
            ->get();

        // ── Recent orders ──────────────────────────────────────────────
        $recentOrders = Order::with('user')
            ->whereBetween('created_at', [$start, $end])
            ->latest()
            ->take(10)
            ->get();

        // ── Quick stats ────────────────────────────────────────────────
        $totalCustomers  = User::where('role', 'customer')->count();
        $totalProducts   = Product::where('status', 'active')->count();
        $lowStockCount   = ProductVariant::where('stock', '<=', 5)->where('is_active', true)->count();
        $avgOrderValue   = $totalOrders > 0 ? round($totalRevenue / max($completedOrders, 1), 0) : 0;
        $totalUnits      = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereBetween('orders.created_at', [$start, $end])
            ->sum('order_items.qty');
        $returnsCount    = $ordersByStatus['returned'] ?? 0;

        return view('admin.dashboard', compact(
            'rangeLabel', 'from', 'to',
            'totalOrders', 'totalRevenue', 'pendingOrders', 'inCourier', 'conversionRate',
            'ordersByStatus', 'orderSources',
            'topProducts', 'recentOrders',
            'totalCustomers', 'totalProducts', 'lowStockCount',
            'avgOrderValue', 'totalUnits', 'returnsCount'
        ));
    }

    private function parseDateRange(string $range, Request $request): array
    {
        return match ($range) {
            'yesterday'  => [
                today()->subDay()->toDateString(),
                today()->subDay()->toDateString(),
                'Yesterday',
            ],
            'this_week'  => [
                today()->startOfWeek()->toDateString(),
                today()->toDateString(),
                'This Week',
            ],
            'this_month' => [
                today()->startOfMonth()->toDateString(),
                today()->toDateString(),
                'This Month',
            ],
            'last_month' => [
                today()->subMonth()->startOfMonth()->toDateString(),
                today()->subMonth()->endOfMonth()->toDateString(),
                'Last Month',
            ],
            'custom'     => [
                $request->input('from', today()->toDateString()),
                $request->input('to',   today()->toDateString()),
                'Custom Range',
            ],
            default      => [
                today()->toDateString(),
                today()->toDateString(),
                'Today',
            ],
        };
    }
}
