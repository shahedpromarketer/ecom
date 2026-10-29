<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Hands new, unassigned orders to staff automatically.
 *
 * Settings (per shop):
 *   distribution_mode     off | round_robin | least_load
 *   distribution_max_open max open orders one person may hold (0 = no limit)
 *   distribution_sticky   1 = a returning customer goes back to the person who handled them
 *
 * Staff take part when users.receives_orders is on. If everyone is at their
 * limit the order stays unassigned (the queue) and `orders:distribute`
 * retries every minute.
 */
class OrderDistributor
{
    /** Orders that still need someone's attention. */
    public const OPEN_STATUSES = ['pending', 'confirmed', 'processing', 'hold', 'hold_followup'];

    public const MODES = [
        'off'         => 'Off — orders stay unassigned',
        'round_robin' => 'Round robin — one after another',
        'least_load'  => 'Least busy — whoever has the fewest open orders',
    ];

    public static function settings(int $tenantId): array
    {
        return [
            'mode'     => Setting::getValue('distribution_mode', 'off', $tenantId),
            'max_open' => (int) Setting::getValue('distribution_max_open', 0, $tenantId),
            'sticky'   => (bool) Setting::getValue('distribution_sticky', 1, $tenantId),
        ];
    }

    /** Try to assign one order. Returns the user it went to, or null (left in the queue). */
    public static function assign(Order $order): ?User
    {
        if ($order->assigned_to || ! $order->tenant_id || ! in_array($order->status, self::OPEN_STATUSES, true)) {
            return null;
        }

        $cfg = self::settings($order->tenant_id);
        if ($cfg['mode'] === 'off') {
            return null;
        }

        // Lock per shop so two orders arriving together don't both pick the same "next" person.
        return Cache::lock('distribute-orders-' . $order->tenant_id, 10)->block(5, function () use ($order, $cfg) {
            $candidates = self::candidates($order->tenant_id, $cfg['max_open']);
            if ($candidates->isEmpty()) {
                return null;
            }

            $user = ($cfg['sticky'] ? self::previousHandler($order, $candidates) : null)
                ?? ($cfg['mode'] === 'least_load'
                    ? $candidates->sortBy(fn ($u) => [$u->open_orders, $u->id])->first()
                    : self::nextInTurn($order->tenant_id, $candidates));

            $order->forceFill(['assigned_to' => $user->id, 'assigned_at' => now()])->saveQuietly();
            Setting::setValue('distribution_last_user', $user->id, 'distribution', $order->tenant_id);

            return $user;
        });
    }

    /** Assign as many queued orders as limits allow (oldest first). Returns how many were assigned. */
    public static function drainQueue(int $tenantId): int
    {
        if (self::settings($tenantId)['mode'] === 'off') {
            return 0;
        }

        $assigned = 0;
        Order::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->whereNull('assigned_to')
            ->whereIn('status', self::OPEN_STATUSES)
            ->oldest()
            ->limit(500)
            ->get()
            ->each(function (Order $order) use (&$assigned) {
                if (! self::assign($order)) {
                    return false;   // everyone is full; stop for now
                }
                $assigned++;
            });

        return $assigned;
    }

    /** Staff who take orders and are below their limit, with their current open-order count. */
    public static function candidates(int $tenantId, int $maxOpen): Collection
    {
        return User::where('tenant_id', $tenantId)
            ->where('receives_orders', true)
            ->where('is_banned', false)
            ->whereIn('role', ['admin', 'staff'])
            ->withCount(['assignedOrders as open_orders' => fn ($q) => $q->withoutGlobalScopes()->whereIn('status', self::OPEN_STATUSES)])
            ->orderBy('id')
            ->get()
            ->filter(fn ($u) => $maxOpen <= 0 || $u->open_orders < $maxOpen)
            ->values();
    }

    private static function previousHandler(Order $order, Collection $candidates): ?User
    {
        $previousId = $order->customer?->assigned_to
            ?? Order::withoutGlobalScopes()
                ->where('tenant_id', $order->tenant_id)
                ->where('customer_id', $order->customer_id)
                ->where('id', '!=', $order->id)
                ->whereNotNull('assigned_to')
                ->latest()
                ->value('assigned_to');

        return $previousId ? $candidates->firstWhere('id', (int) $previousId) : null;
    }

    private static function nextInTurn(int $tenantId, Collection $candidates): User
    {
        $last = (int) Setting::getValue('distribution_last_user', 0, $tenantId);

        return $candidates->first(fn ($u) => $u->id > $last) ?? $candidates->first();
    }
}
