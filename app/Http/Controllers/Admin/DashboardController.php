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
        $range = $request->input('range', 'today');
        [$from, $to, $rangeLabel] = $this->parseDateRange($range, $request);

        $start = $from ? Carbon::parse($from)->startOfDay() : null;
        $end   = $to   ? Carbon::parse($to)->endOfDay()     : null;

        $isSA     = auth()->user()->is_super_admin;
        $tenantId = auth()->user()->tenant_id;
        $orderBase = fn() => $isSA ? Order::query() : Order::where('tenant_id', $tenantId);
        $dateFilter = fn ($q) => $start ? $q->whereBetween('created_at', [$start, $end]) : $q;

        // ── Single GROUP BY query — all status counts + revenue in one hit ────
        $ordersByStatus = $dateFilter($orderBase())
            ->selectRaw('status, count(*) as count, sum(total) as revenue')
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        // ── Summary cards derived from collection (no extra DB queries) ────────
        $totalOrders     = (int) $ordersByStatus->sum('count');
        $totalRevenue    = (float) $ordersByStatus
            ->filter(fn($r) => in_array($r->status, ['delivered', 'completed']))
            ->sum('revenue');
        $pendingOrders   = (int) ($ordersByStatus->get('pending')?->count   ?? 0);
        $inCourier       = (int) ($ordersByStatus->get('shipped')?->count   ?? 0);
        $completedOrders = (int) ($ordersByStatus->get('completed')?->count ?? 0);
        $conversionRate  = $totalOrders > 0 ? round(($completedOrders / $totalOrders) * 100, 1) : 0;

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
        $orderSources = $dateFilter($orderBase())
            ->selectRaw('COALESCE(source, "no_source") as source, count(*) as count, sum(total) as revenue')
            ->groupBy('source')->get();

        // ── Top products by revenue ────────────────────────────────────────────
        $topQuery = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereIn('orders.status', ['delivered', 'completed']);
        if ($start && $end) {
            $topQuery->whereBetween('orders.created_at', [$start, $end]);
        }
        if (!$isSA && $tenantId) {
            $topQuery->where('orders.tenant_id', $tenantId);
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
        $recentOrders = $dateFilter($orderBase()->with('user'))->latest()->take(10)->get();

        // ── Quick stats ────────────────────────────────────────────────────────
        $custCountQ = User::inCurrentTenant()->where('role', 'customer');
        if (!$isSA) { $custCountQ->where('tenant_id', $tenantId); }
        $totalCustomers = $custCountQ->count();
        $prodCountQ = Product::where('status', 'active');
        if (!$isSA) { $prodCountQ->where('tenant_id', $tenantId); }
        $totalProducts  = $prodCountQ->count();
        $lowStockQ = ProductVariant::where('stock', '<=', 5)->where('is_active', true);
        if (!$isSA) { $lowStockQ->whereHas('product', fn($p) => $p->where('tenant_id', $tenantId)); }
        $lowStockCount  = $lowStockQ->count();
        $avgOrderValue  = $totalOrders > 0 ? round($totalRevenue / max($completedOrders, 1), 0) : 0;

        $unitsQuery = DB::table('order_items')->join('orders', 'order_items.order_id', '=', 'orders.id');
        if ($start && $end) {
            $unitsQuery->whereBetween('orders.created_at', [$start, $end]);
        }
        if (!$isSA && $tenantId) {
            $unitsQuery->where('orders.tenant_id', $tenantId);
        }
        $totalUnits   = $unitsQuery->sum('order_items.qty');
        $returnsCount = $statusCounts['returned'] ?? 0;

        // ── Previous period comparison ─────────────────────────────────────────
        [$prevStart, $prevEnd] = $this->getPreviousPeriod($range, $start, $end);
        $comparison = null;
        if ($prevStart && $prevEnd) {
            $prevQ = $isSA ? Order::query() : Order::where('tenant_id', $tenantId);
            $prevData = $prevQ->whereBetween('created_at', [$prevStart, $prevEnd])
                ->selectRaw('count(*) as total_orders, sum(case when status in ("delivered","completed") then total else 0 end) as total_revenue')
                ->first();
            $prevOrders  = (int)   ($prevData->total_orders  ?? 0);
            $prevRevenue = (float) ($prevData->total_revenue ?? 0);

            $comparison = [
                'label'        => $this->getPreviousLabel($range),
                'orders'       => $prevOrders,
                'revenue'      => $prevRevenue,
                'orders_diff'  => $prevOrders  > 0 ? round((($totalOrders  - $prevOrders)  / $prevOrders)  * 100, 1) : null,
                'revenue_diff' => $prevRevenue > 0 ? round((($totalRevenue - $prevRevenue) / $prevRevenue) * 100, 1) : null,
            ];
        }

        // ── Repeat customer rate ───────────────────────────────────────────────
        $repeatQ = $isSA ? Order::query() : Order::where('tenant_id', $tenantId);
        $repeatData = $repeatQ->select('shipping_phone', DB::raw('count(*) as order_count'))
            ->whereNotNull('shipping_phone')
            ->groupBy('shipping_phone')
            ->havingRaw('count(*) > 1')
            ->get();
        $repeatCustomers = $repeatData->count();
        $allPhones       = ($isSA ? Order::query() : Order::where('tenant_id', $tenantId))
            ->distinct('shipping_phone')->whereNotNull('shipping_phone')->count('shipping_phone');
        $repeatRate = $allPhones > 0 ? round(($repeatCustomers / $allPhones) * 100, 1) : 0;

        return view('admin.dashboard', compact(
            'range', 'rangeLabel', 'from', 'to',
            'totalOrders', 'totalRevenue', 'pendingOrders', 'inCourier', 'conversionRate',
            'ordersByStatus', 'statusCounts', 'periodTotal', 'returnRatio',
            'orderSources',
            'topProducts', 'recentOrders',
            'totalCustomers', 'totalProducts', 'lowStockCount',
            'avgOrderValue', 'totalUnits', 'returnsCount',
            'comparison', 'repeatRate',
        ));
    }

    public function orderStatus(Request $request): \Illuminate\Http\JsonResponse
    {
        $isSA = auth()->user()->is_super_admin;
        $tenantId = auth()->user()->tenant_id;
        $baseQ = $isSA ? Order::query() : Order::where('tenant_id', $tenantId);
        $query = $this->applyDateRange($baseQ, $request->input('range', 'all'), $request);
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
        $isSA2 = auth()->user()->is_super_admin;
        $tenantId2 = auth()->user()->tenant_id;
        $baseQ2 = $isSA2 ? Order::query() : Order::where('tenant_id', $tenantId2);
        $query   = $this->applyDateRange($baseQ2, $range, $request);
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

    public function summaryData(Request $request): \Illuminate\Http\JsonResponse
    {
        $range = $request->input('range', 'today');
        [$from, $to, $rangeLabel] = $this->parseDateRange($range, $request);

        $start = $from ? Carbon::parse($from)->startOfDay() : null;
        $end   = $to   ? Carbon::parse($to)->endOfDay()     : null;
        $isSA3    = auth()->user()->is_super_admin;
        $tenId3   = auth()->user()->tenant_id;
        $ob3      = fn() => $isSA3 ? Order::query() : Order::where('tenant_id', $tenId3);
        $df    = fn ($q) => $start ? $q->whereBetween('created_at', [$start, $end]) : $q;

        $byStatus = $df($ob3())
            ->selectRaw('status, count(*) as count, sum(total) as revenue')
            ->groupBy('status')->get()->keyBy('status');

        $totalOrders     = (int) $byStatus->sum('count');
        $totalRevenue    = (float) $byStatus
            ->filter(fn($r) => in_array($r->status, ['delivered', 'completed']))->sum('revenue');
        $pendingOrders   = (int) ($byStatus->get('pending')?->count   ?? 0);
        $inCourier       = (int) ($byStatus->get('shipped')?->count   ?? 0);
        $completedOrders = (int) ($byStatus->get('completed')?->count ?? 0);
        $conversionRate  = $totalOrders > 0 ? round(($completedOrders / $totalOrders) * 100, 1) : 0;

        $periodTotal = (int) $byStatus->sum('count');
        $returnRatio = $periodTotal > 0
            ? round((($byStatus->get('returned')?->count ?? 0) + ($byStatus->get('pending_return')?->count ?? 0)) / $periodTotal * 100, 2)
            : 0;

        $cardKeys = ['pending','confirmed','processing','hold','delivered','completed','cancelled','pending_return','returned'];
        $cards = [];
        foreach ($cardKeys as $s) {
            $cards[$s] = ['count' => (int)($byStatus->get($s)?->count ?? 0), 'revenue' => (float)($byStatus->get($s)?->revenue ?? 0)];
        }
        $cards['in_courier'] = ['count' => (int)($byStatus->get('shipped')?->count ?? 0), 'revenue' => (float)($byStatus->get('shipped')?->revenue ?? 0)];

        return response()->json([
            'rangeLabel'     => $rangeLabel,
            'totalOrders'    => $totalOrders,
            'totalRevenue'   => (float) $totalRevenue,
            'pendingOrders'  => $pendingOrders,
            'inCourier'      => $inCourier,
            'conversionRate' => $conversionRate,
            'periodTotal'    => $periodTotal,
            'returnRatio'    => $returnRatio,
            'cards'          => $cards,
        ]);
    }

    public function sourceData(Request $request): \Illuminate\Http\JsonResponse
    {
        [$from, $to] = $this->parseDateRange($request->input('range', 'this_month'), $request);
        $start = $from ? Carbon::parse($from)->startOfDay() : null;
        $end   = $to   ? Carbon::parse($to)->endOfDay()     : null;

        $isSA4 = auth()->user()->is_super_admin;
        $tenId4 = auth()->user()->tenant_id;
        $q = $isSA4 ? Order::selectRaw('COALESCE(source, "no_source") as source, count(*) as count, sum(total) as revenue') : Order::where('tenant_id', $tenId4)->selectRaw('COALESCE(source, "no_source") as source, count(*) as count, sum(total) as revenue');
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

        $isSA5  = auth()->user()->is_super_admin;
        $tenId5 = auth()->user()->tenant_id;
        $q5     = $isSA5 ? Order::query() : Order::where('tenant_id', $tenId5);
        if ($start && $end) {
            $q5->whereBetween('created_at', [$start, $end]);
        }
        $raw5 = $q5->selectRaw('status, count(*) as count')
            ->groupBy('status')->get()->keyBy('status');

        $statuses = [];
        foreach (self::STATUSES as $status) {
            $statuses[$status] = (int) ($raw5->get($status)?->count ?? 0);
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
            'last_30'    => [
                today()->subDays(29)->toDateString(),
                today()->toDateString(),
                'Last 30 Days',
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
            'last_year'  => [
                today()->subYear()->startOfYear()->toDateString(),
                today()->subYear()->endOfYear()->toDateString(),
                'Last Year',
            ],
            'custom'     => [
                $request->input('from', today()->toDateString()),
                $request->input('to',   today()->toDateString()),
                'Custom Range',
            ],
            default      => [null, null, 'All Time'],
        };
    }

    private function getPreviousPeriod(string $range, ?Carbon $start, ?Carbon $end): array
    {
        return match ($range) {
            'today'      => [today()->subDay()->startOfDay(),   today()->subDay()->endOfDay()],
            'yesterday'  => [today()->subDays(2)->startOfDay(), today()->subDays(2)->endOfDay()],
            'last_7'     => [today()->subDays(13)->startOfDay(), today()->subDays(7)->endOfDay()],
            'last_30'    => [today()->subDays(59)->startOfDay(), today()->subDays(30)->endOfDay()],
            'this_month' => [today()->subMonth()->startOfMonth(), today()->subMonth()->endOfMonth()],
            'last_month' => [today()->subMonths(2)->startOfMonth(), today()->subMonths(2)->endOfMonth()],
            'this_year'  => [today()->subYear()->startOfYear(), today()->subYear()->endOfYear()],
            'last_year'  => [today()->subYears(2)->startOfYear(), today()->subYears(2)->endOfYear()],
            default      => [null, null],
        };
    }

    private function getPreviousLabel(string $range): string
    {
        return match ($range) {
            'today'      => 'Yesterday',
            'yesterday'  => 'Day Before',
            'last_7'     => 'Prev 7 Days',
            'last_30'    => 'Prev 30 Days',
            'this_month' => 'Last Month',
            'last_month' => '2 Months Ago',
            'this_year'  => 'Last Year',
            'last_year'  => '2 Years Ago',
            default      => 'Previous',
        };
    }
}
