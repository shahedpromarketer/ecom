<?php

namespace App\Services;

use App\Models\IncompleteOrder;
use App\Models\Order;
use App\Models\WordPressSite;
use App\Support\Phone;

/**
 * Records unfinished checkouts and marks them recovered when the same number orders.
 * Runs with the store's shop pinned.
 */
class IncompleteCheckoutRecorder
{
    /** A real order this soon after the abandoned checkout counts as recovering it. */
    public const RECOVERY_WINDOW_DAYS = 7;

    public static function record(WordPressSite $site, array $d): ?IncompleteOrder
    {
        $phone = Phone::normalize((string) ($d['phone'] ?? ''));
        if (! Phone::isValidBdMobile($phone)) {
            return null;   // nothing we could call
        }

        $session = substr((string) ($d['session_id'] ?? ''), 0, 100) ?: 'phone:' . $phone;

        $row = IncompleteOrder::firstOrNew([
            'wordpress_site_id' => $site->id,
            'session_key'       => $session,
        ]);

        // Already finished or dismissed: leave it alone.
        if ($row->exists && $row->status !== 'pending') {
            return $row;
        }

        $items = collect($d['items'] ?? [])->take(30)->map(fn ($i) => [
            'name'    => mb_substr((string) ($i['name'] ?? ''), 0, 255),
            'qty'     => max(1, (int) ($i['qty'] ?? 1)),
            'price'   => (float) ($i['price'] ?? 0),
            'variant' => isset($i['variant']) ? mb_substr((string) $i['variant'], 0, 100) : null,
        ])->filter(fn ($i) => $i['name'] !== '')->values()->all();

        $row->fill(array_filter([
            'phone'      => $phone,
            'name'       => self::clean($d['name'] ?? null, 255),
            'email'      => filter_var($d['email'] ?? null, FILTER_VALIDATE_EMAIL) ?: null,
            'address'    => self::clean($d['address'] ?? null, 1000),
            'district'   => self::clean($d['district'] ?? null, 100),
            'items'      => $items ?: null,
            'total'      => isset($d['total']) ? (float) $d['total'] : null,
            'page_url'   => self::clean($d['page_url'] ?? null, 500),
            'ip'         => filter_var($d['ip'] ?? null, FILTER_VALIDATE_IP) ?: null,
            'user_agent' => self::clean($d['user_agent'] ?? null, 500),
        ], fn ($v) => $v !== null && $v !== ''));

        $row->status = 'pending';
        $row->last_activity_at = now();

        // They may already have ordered (the order arrived first).
        $order = self::recentOrderFor($phone);
        if ($order) {
            $row->status = 'recovered';
            $row->order_id = $order->id;
        } elseif (! $row->exists) {
            $row->assigned_to = \App\Models\Customer::where('phone', $phone)->value('assigned_to');
        }

        $row->save();

        return $row;
    }

    /** Called when a real order is created: its abandoned checkouts are recovered. */
    public static function markRecovered(Order $order): void
    {
        $phone = Phone::normalize($order->shipping_phone);
        if ($phone === '' || ! $order->tenant_id) {
            return;
        }

        IncompleteOrder::withoutGlobalScopes()
            ->where('tenant_id', $order->tenant_id)
            ->where('phone', $phone)
            ->where('status', 'pending')
            ->where('last_activity_at', '>=', now()->subDays(self::RECOVERY_WINDOW_DAYS))
            ->update(['status' => 'recovered', 'order_id' => $order->id, 'updated_at' => now()]);
    }

    private static function recentOrderFor(string $phone): ?Order
    {
        return Order::withoutGlobalScope('assignee')
            ->where('shipping_phone', $phone)
            ->where('created_at', '>=', now()->subHours(2))
            ->latest()
            ->first();
    }

    private static function clean($v, int $max): ?string
    {
        $v = trim(strip_tags((string) $v));

        return $v === '' ? null : mb_substr($v, 0, $max);
    }
}
