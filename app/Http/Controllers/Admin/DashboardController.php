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
        $range = $request->input('range', 'all');
        [$from, $to, $rangeLabel] = $this->parseDateRange($range, $request);

        $start = $from ? Carbon::parse($from)->startOfDay() : null;
        $end   = $to   ? Carbon::parse($to)->endOfDay()     : null;

        $dateFilter = fn ($q) => $start ? $q->whereBetween('created_at', [$start, $end]) : $q;

        // ── Summary cards ──────────────────────────────────────────────────────
        $totalOrders     = $dateFilter(Order::query())->count();
        $totalRevenue    = $dateFilter(Order::query())->whereIn('status', ['delivered', 'completed'])->sum('total');
        $pendingOrders   = $dateFilter(Order::query())->where('status', 'pending')->count();
        $inCourier       = $dateFilter(Order::query())->where('status', 'shipped')->count();
        $completedOrders = $dateFilter(Order::query())->where('status', 'completed')->count();
        $conversionRate  = $totalOrders > 0 ? round(($completedOrders / $totalOrders) * 100, 1) : 0;

        // ── Unified status data — same source for BOTH cards and donut chart ───
        $ordersByStatus = $dateFilter(Order::query())
            ->selectRaw('status, count(*) as count, sum(total) as revenue')
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        // Ordered counts array for chart (order must match STATUSES / colour array)
        $statusCounts = [];
        foreach (self::STATUSES as $status) {
            $statusCounts[$status] = $ordersByStatus->get($status)?->count ?? 0;
        }

        $periodTotal = $ordersByStatus->sum('count');
        $returnRatio = $periodTotal > 0
            ? round(
                (($ordersByStatus->get('returned')?->count ?? 0)
                + ($ordersByStatus->get('pending_return')?->count ?? 0))
                / $periodTotal * 100, 2)
            : 0;

        // ── Order sources ──────────────────────────────────────────────────────
        $orderSources = $dateFilter(Order::query())
            ->selectRaw('COALESCE(source, "no_source") as source, count(*) as count, sum(total) as revenue')
            ->groupBy('source')->get();

        // ── Top products by revenue ────────────────────────────────────────────
        $topQuery = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereIn('orders.status', ['delivered', 'completed']);
        if ($start && $end) {
            $topQuery->whereBetween('orders.created_at', [$start, $end]);
        }
        $topProducts = $topQuery
            ->select(
                'order_items.product_id',
                'order_items.product_name',
                DB::raw('SUM(order_items.qty) as total_qty'),
                DB::raw('SUM(order_items.total) as total_revenue')
            )
            ->groupBy('order_items.product_id', 'order_items.product_name')
            ->orderByDesc('total_revenue')
            ->limit(8)->get();

        // ── Recent orders ──────────────────────────────────────────────────────
        $recentOrders = $dateFilter(Order::with('user'))->latest()->take(10)->get();

        // ── Quick stats ────────────────────────────────────────────────────────
        $totalCustomers = User::where('role', 'customer')->count();
        $totalProducts  = Product::where('status', 'active')->count();
        $lowStockCount  = ProductVariant::where('stock', '<=', 5)->where('is_active', true)->count();
        $avgOrderValue  = $totalOrders > 0 ? round($totalRevenue / max($completedOrders, 1), 0) : 0;

        $unitsQuery = DB::table('order_items')->join('orders', 'order_items.order_id', '=', 'orders.id');
        if ($start && $end) {
            $unitsQuery->whereBetween('orders.created_at', [$start, $end]);
        }
        $totalUnits   = $unitsQuery->sum('order_items.qty');
        $returnsCount = $statusCounts['returned'] ?? 0;

        return view('admin.dashboard', compact(
            'range', 'rangeLabel', 'from', 'to',
            'totalOrders', 'totalRevenue', 'pendingOrders', 'inCourier', 'conversionRate',
            'ordersByStatus', 'statusCounts', 'periodTotal', 'returnRatio',
            'orderSources',
            'topProducts', 'recentOrders',
            'totalCustomers', 'totalProducts', 'lowStockCount',
            'avgOrderValue', 'totalUnits', 'returnsCount',
        ));
    }

    public function orderStatus(Request $request): \Illuminate\Http\JsonResponse
    {
        $query = $this->applyDateRange(Order::query(), $request->input('range', 'all'), $request);
        $raw   = $query->selectRaw('status, count(*) as count, sum(total) as revenue')
            ->groupBy('status')->get()->keyBy('status');

        // All actual statuses for the chart (no hard-coded list — no missing slices)
        $statuses = $raw->values()->map(fn ($s) => [
            'status'  => $s->status,
            'count'   => (int) $s->count,
            'revenue' => (float) $s->revenue,
        ]);

        $total = (int) $raw->sum('count');

        // Section 1b card statuses (the 12 mini-cards)
        $cardKeys = [
            'pending', 'confirmed', 'processing', 'hold',
            'delivered', 'completed', 'cancelled', 'pending_return', 'returned',
        ];
        $cards = [];
        foreach ($cardKeys as $s) {
            $cards[$s] = [
                'count'   => (int) ($raw->get($s)?->count ?? 0),
                'revenue' => (float) ($raw->get($s)?->revenue ?? 0),
            ];
        }
        $cards['in_courier'] = [
            'count'   => (int) ($raw->get('shipped')?->count ?? 0),
            'revenue' => (float) ($raw->get('shipped')?->revenue ?? 0),
        ];

        $returnRatio = $total > 0
            ? round((($cards['returned']['count'] + $cards['pending_return']['count']) / $total) * 100, 2)
            : 0;

        return response()->json([
            'statuses'     => $statuses,
            'cards'        => $cards,
            'total'        => $total,
            'return_ratio' => $returnRatio,
        ]);
    }

    public function orderSource(Request $request): \Illuminate\Http\JsonResponse
    {
        $range   = $request->input('range', 'this_month');
        $query   = $this->applyDateRange(Order::query(), $range, $request);
        $sources = $query->selectRaw('COALESCE(source, "no_source") as source, count(*) as count, sum(total) as revenue')
            ->groupBy('source')->get();

        [$from, $to] = $this->parseDateRange($range, $request);

        return response()->json(['sources' => $sources, 'total' => $sources->sum('count'), 'from' => $from ?? '', 'to' => $to ?? '']);
    }

    private function applyDateRange($query, string $range, Request $request)
    {
        return match ($range) {
            'today'      => $query->whereDate('created_at', today()),
            'yesterday'  => $query->whereDate('created_at', today()->subDay()),
            'last_7'     => $query->where('created_at', '>=', today()->subDays(6)->startOfDay()),
            'last_30'    => $query->where('created_at', '>=', today()->subDays(29)->startOfDay()),
            'this_month' => $query->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year),
            'last_month' => $query->whereMonth('created_at', now()->subMonth()->month)
                                  ->whereYear('created_at', now()->subMonth()->year),
            'this_year'  => $query->whereYear('created_at', now()->year),
            'last_year'  => $query->whereYear('created_at', now()->subYear()->year),
            'custom'     => $query->whereBetween('created_at', [
                                Carbon::parse($request->input('from', today()->toDateString()))->startOfDay(),
                                Carbon::parse($request->input('to',   today()->toDateString()))->endOfDay(),
                            ]),
            default      => $query, // 'all' — no filter
        };
    }

    public function sourceData(Request $request): \Illuminate\Http\JsonResponse
    {
        [$from, $to] = $this->parseDateRange($request->input('range', 'this_month'), $request);
        $start = $from ? Carbon::parse($from)->startOfDay() : null;
        $end   = $to   ? Carbon::parse($to)->endOfDay()     : null;

        $q = Order::selectRaw('COALESCE(source, "no_source") as source, count(*) as count, sum(total) as revenue');
        if ($start && $end) {
            $q->whereBetween('created_at', [$start, $end]);
        }
        $sources = $q->groupBy('source')->get();

        return response()->json(['sources' => $sources, 'from' => $from ?? '', 'to' => $to ?? '']);
    }

    public function statusData(Request $request): \Illuminate\Http\JsonResponse
    {
        [$from, $to] = $this->parseDateRange($request->input('range', 'today'), $request);
        $start = $from ? Carbon::parse($from)->startOfDay() : null;
        $end   = $to   ? Carbon::parse($to)->endOfDay()     : null;

        $statuses = [];
        foreach (self::STATUSES as $status) {
            $q = Order::where('status', $status);
            if ($start && $end) {
                $q->whereBetween('created_at', [$start, $end]);
            }
            $statuses[$status] = $q->count();
        }
        $total = array_sum($statuses);

        return response()->json(['statuses' => $statuses, 'total' => $total, 'from' => $from ?? '', 'to' => $to ?? '']);
    }

    private function parseDateRange(string $range, Request $request): array
    {
        return match ($range) {
            'today'      => [
                today()->toDateString(),
                today()->toDateString(),
                'Today',
            ],
            'yesterday'  => [
                today()->subDay()->toDateString(),
                today()->subDay()->toDateString(),
                'Yesterday',
            ],
            'last_7'     => [
                today()->subDays(6)->toDateString(),
                today()->toDateString(),
                'Last 7 Days',
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
            'this_year'  => [
                today()->startOfYear()->toDateString(),
                today()->toDateString(),
                'This Year',
            ],
            'custom'     => [
                $request->input('from', today()->toDateString()),
                $request->input('to',   today()->toDateString()),
                'Custom Range',
            ],
            default      => [null, null, 'All Time'],
        };
    }
}
