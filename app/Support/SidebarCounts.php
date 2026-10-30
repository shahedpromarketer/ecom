<?php

namespace App\Support;

use App\Models\Followup;
use App\Models\IncompleteOrder;
use App\Models\Lead;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * The numbers in the side menu, shown on every page: one grouped query, cached briefly.
 * Staff who only see their own orders get their own numbers (the order list hides the rest).
 */
class SidebarCounts
{
    public const STATUSES = ['pending', 'confirmed', 'processing', 'hold', 'shipped', 'delivered', 'completed', 'cancelled', 'pending_return', 'returned'];

    public static function key(User $user): string
    {
        $base = 'sidebar_order_counts_' . ($user->is_super_admin ? 'sa' : $user->tenant_id);

        return $user->can('view_all_orders') || $user->is_super_admin ? $base : $base . '_u' . $user->id;
    }

    /** @return array<string,int> status => count, plus all, incomplete, return_waiting */
    public static function orders(User $user): array
    {
        return Cache::remember(self::key($user), 120, function () {
            $rows = Order::query()
                ->select('status', 'payment_status', DB::raw("CASE WHEN source = 'manual' THEN 1 ELSE 0 END as manual"), DB::raw('COUNT(*) as cnt'))
                ->groupBy('status', 'payment_status', DB::raw("CASE WHEN source = 'manual' THEN 1 ELSE 0 END"))
                ->get();

            $out = ['all' => (int) $rows->sum('cnt')];
            foreach (self::STATUSES as $s) {
                $out[$s] = (int) $rows->where('status', $s)->sum('cnt');
            }
            $out['incomplete'] = (int) $rows->where('status', 'pending')->where('payment_status', 'unpaid')->where('manual', 0)->sum('cnt');
            $out['return_waiting'] = (int) $rows->whereIn('status', ['pending_return', 'return_collection'])->sum('cnt');

            return $out;
        });
    }

    /** Failed orders to call, new leads, follow-ups due: per user, for 30 seconds. */
    public static function badges(User $user): array
    {
        return Cache::remember('sidebar_badges_' . ($user->tenant_id ?? 'sa') . '_' . $user->id, 30, fn () => [
            'failed'    => IncompleteOrder::where('status', 'pending')->count(),
            'leads'     => Lead::query()->visibleTo($user)->where('status', 'pending')->whereNull('read_at')->count(),
            'followups' => Followup::query()->visibleTo($user)->open()->whereDate('scheduled_for', '<=', today())->count(),
        ]);
    }

    /** After order changes: everyone in the shop sees fresh numbers on their next page. */
    public static function forget(?int $tenantId): void
    {
        Cache::forget('sidebar_order_counts_' . ($tenantId ?? 'sa'));
        Cache::forget('sidebar_order_counts_sa');
    }
}
