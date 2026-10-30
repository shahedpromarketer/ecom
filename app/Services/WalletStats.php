<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\WalletTransaction;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Numbers for the wallet screens (shop and super admin): balance health, what orders
 * cost today / this week / this month, and a day-by-day summary.
 */
class WalletStats
{
    /** Below this many orders left the balance shows red, below the second yellow. */
    public const RED_BELOW = 20;
    public const YELLOW_BELOW = 100;

    public static function summary(Tenant $tenant): array
    {
        $fee = (float) $tenant->order_charge;
        $balance = (float) $tenant->wallet_balance;
        $left = $tenant->ordersLeft();

        $level = match (true) {
            $fee <= 0                      => 'free',
            $balance <= 0                  => 'red',
            $left < self::RED_BELOW        => 'red',
            $left < self::YELLOW_BELOW     => 'yellow',
            default                        => 'green',
        };

        $fees = fn (Carbon $from) => self::q($tenant)->where('type', 'order')->where('created_at', '>=', $from);

        return [
            'balance'    => $balance,
            'fee'        => $fee,
            'charge_on'  => $tenant->charge_on ?? 'confirmed',
            'left'       => $left,
            'level'      => $level,
            'today'      => ['orders' => $fees(today())->count(), 'cost' => -1 * (float) $fees(today())->sum('amount')],
            'week'       => ['orders' => $fees(now()->subDays(6)->startOfDay())->count(), 'cost' => -1 * (float) $fees(now()->subDays(6)->startOfDay())->sum('amount')],
            'month'      => ['orders' => $fees(now()->startOfMonth())->count(), 'cost' => -1 * (float) $fees(now()->startOfMonth())->sum('amount')],
            'all'        => ['orders' => self::q($tenant)->where('type', 'order')->count(), 'cost' => -1 * (float) self::q($tenant)->where('type', 'order')->sum('amount')],
            'recharged'  => (float) self::q($tenant)->where('type', 'recharge')->sum('amount'),
            'last_recharge' => self::q($tenant)->where('type', 'recharge')->latest('id')->first(),
        ];
    }

    /**
     * One row per day that had wallet activity, newest first:
     * orders charged, their cost, money added and the balance at the end of the day.
     */
    public static function daily(Tenant $tenant, ?Carbon $from = null, ?Carbon $to = null): Collection
    {
        $rows = self::q($tenant)
            ->when($from, fn ($q) => $q->where('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->where('created_at', '<=', $to))
            ->selectRaw("DATE(created_at) as day,
                SUM(CASE WHEN type = 'order' THEN 1 ELSE 0 END) as orders,
                SUM(CASE WHEN type = 'order' THEN -amount ELSE 0 END) as cost,
                SUM(CASE WHEN type <> 'order' AND amount > 0 THEN amount ELSE 0 END) as added,
                SUM(CASE WHEN type <> 'order' AND amount < 0 THEN -amount ELSE 0 END) as taken,
                MAX(id) as last_id")
            ->groupByRaw('DATE(created_at)')
            ->orderByDesc('day')
            ->get();

        $closing = WalletTransaction::withoutGlobalScopes()->whereIn('id', $rows->pluck('last_id'))->pluck('balance_after', 'id');

        return $rows->map(fn ($r) => (object) [
            'day'     => Carbon::parse($r->day),
            'orders'  => (int) $r->orders,
            'cost'    => round((float) $r->cost, 2),
            'added'   => round((float) $r->added, 2),
            'taken'   => round((float) $r->taken, 2),
            'balance' => (float) ($closing[$r->last_id] ?? 0),
        ]);
    }

    /** Orders and cost for each of the last $days days (oldest first, empty days included) — for the chart. */
    public static function chart(Tenant $tenant, int $days = 30): array
    {
        $byDay = self::daily($tenant, now()->subDays($days - 1)->startOfDay())->keyBy(fn ($r) => $r->day->toDateString());
        $out = ['labels' => [], 'orders' => [], 'cost' => []];
        for ($i = $days - 1; $i >= 0; $i--) {
            $d = now()->subDays($i);
            $row = $byDay[$d->toDateString()] ?? null;
            $out['labels'][] = $d->format('d M');
            $out['orders'][] = $row->orders ?? 0;
            $out['cost'][] = $row->cost ?? 0;
        }

        return $out;
    }

    private static function q(Tenant $tenant)
    {
        return WalletTransaction::withoutGlobalScopes()->where('tenant_id', $tenant->id);
    }
}
