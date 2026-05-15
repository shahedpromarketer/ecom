<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $range = $request->get('range', '30');
        $from  = match ($range) {
            'today'  => now()->startOfDay(),
            '7'      => now()->subDays(7),
            '90'     => now()->subDays(90),
            'custom' => $request->filled('from') ? \Carbon\Carbon::parse($request->from) : now()->subDays(30),
            default  => now()->subDays(30),
        };
        $to = $range === 'custom' && $request->filled('to')
            ? \Carbon\Carbon::parse($request->to)->endOfDay()
            : now()->endOfDay();

        $totalRevenue   = Order::where('status', 'delivered')->whereBetween('created_at', [$from, $to])->sum('total');
        $totalOrders    = Order::whereBetween('created_at', [$from, $to])->count();
        $avgOrderValue  = $totalOrders > 0 ? round($totalRevenue / max($totalOrders, 1), 2) : 0;
        $totalCustomers = User::where('role', 'customer')->whereBetween('created_at', [$from, $to])->count();
        $repeatCustomers = Order::whereBetween('created_at', [$from, $to])
            ->select('user_id', DB::raw('count(*) as cnt'))
            ->groupBy('user_id')
            ->having('cnt', '>', 1)
            ->count();

        $revenueByDate = Order::where('status', 'delivered')
            ->whereBetween('created_at', [$from, $to])
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(total) as revenue'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $ordersByDate = Order::whereBetween('created_at', [$from, $to])
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as orders'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $topProducts = OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->whereBetween('orders.created_at', [$from, $to])
            ->select(
                'products.id',
                'products.name',
                DB::raw('SUM(order_items.qty) as total_qty'),
                DB::raw('SUM(order_items.total) as revenue')
            )
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_qty')
            ->limit(10)
            ->get();

        $topCustomers = Order::with('user')
            ->whereBetween('created_at', [$from, $to])
            ->whereNotNull('user_id')
            ->select('user_id', DB::raw('COUNT(*) as orders_count'), DB::raw('SUM(total) as total_spent'))
            ->groupBy('user_id')
            ->orderByDesc('total_spent')
            ->limit(10)
            ->get();

        $ordersByStatus = Order::whereBetween('created_at', [$from, $to])
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get();

        $revenueByPayment = Order::where('status', 'delivered')
            ->whereBetween('created_at', [$from, $to])
            ->select('payment_method', DB::raw('SUM(total) as revenue'))
            ->groupBy('payment_method')
            ->get();

        return view('admin.analytics.index', compact(
            'totalRevenue', 'totalOrders', 'avgOrderValue', 'totalCustomers', 'repeatCustomers',
            'revenueByDate', 'ordersByDate', 'topProducts', 'topCustomers',
            'ordersByStatus', 'revenueByPayment', 'range'
        ));
    }
}
