<?php

namespace App\Services;

use App\Models\Order;
use App\Models\SyncLog;
use App\Models\WordPressSite;
use App\Support\PluginSignature;
use Illuminate\Support\Facades\Http;

/**
 * Sends CRM status changes back to the WooCommerce store an order came from,
 * through the Ecom Pilot plugin (signed with the store key).
 */
class StoreStatusPusher
{
    /** CRM status => WooCommerce status. Statuses not listed are not sent. */
    public const DEFAULT_MAP = [
        'confirmed'               => 'processing',
        'processing'              => 'processing',
        'hold'                    => 'on-hold',
        'hold_followup'           => 'on-hold',
        'shipped'                 => 'ep-in-courier',   // registered by the plugin
        'hand_delivery'           => 'ep-in-courier',
        'delivered'               => 'completed',
        'completed'               => 'completed',
        'hand_delivery_completed' => 'completed',
        'cancelled'               => 'cancelled',
        'returned'                => 'ep-returned',     // registered by the plugin
    ];

    public static function wooStatusFor(WordPressSite $site, string $crmStatus): ?string
    {
        $map = array_merge(self::DEFAULT_MAP, (array) ($site->status_map ?? []));
        $woo = $map[$crmStatus] ?? null;

        return $woo === '' || $woo === 'none' ? null : $woo;
    }

    /** Queue the push to run after the response, so staff never wait on the store. */
    public static function queue(Order $order): void
    {
        if (! $order->wordpress_site_id || ! $order->source_order_id) {
            return;
        }

        $orderId = $order->id;
        \Illuminate\Support\defer(fn () => self::push(Order::withoutGlobalScopes()->find($orderId)));
    }

    public static function push(?Order $order): bool
    {
        if (! $order) {
            return false;
        }

        $site = WordPressSite::withoutGlobalScopes()->find($order->wordpress_site_id);
        $wooStatus = $site ? self::wooStatusFor($site, $order->status) : null;
        if (! $site || ! $site->is_active || ! $site->plugin_connected || ! $wooStatus) {
            return false;
        }

        $base = rtrim($site->rest_url ?: rtrim($site->site_url, '/') . '/wp-json', '/');
        $body = json_encode([
            'order_id'   => (int) $order->source_order_id,
            'status'     => $wooStatus,
            'crm_status' => $order->status,
            'note'       => 'Ecom Pilot: ' . ucwords(str_replace('_', ' ', $order->status)),
            'tracking'   => $order->shipment?->tracking_code,
        ]);
        $sig = PluginSignature::sign((string) $site->api_key, $body);

        try {
            $response = Http::timeout(8)
                ->withOptions(['verify' => (bool) config('services.woocommerce.verify_ssl', true)])
                ->withHeaders([
                    'Content-Type'    => 'application/json',
                    'X-EP-Site'       => (string) $site->id,
                    'X-EP-Timestamp'  => (string) $sig['timestamp'],
                    'X-EP-Signature'  => $sig['signature'],
                ])
                ->withBody($body, 'application/json')
                ->post($base . '/ecom-pilot/v1/order-status');

            $ok = $response->successful();
            $message = $ok ? "Status sent: {$order->status} → {$wooStatus}" : 'Status push failed: HTTP ' . $response->status() . ' ' . mb_substr($response->body(), 0, 200);
        } catch (\Throwable $e) {
            $ok = false;
            $message = 'Status push failed: ' . mb_substr($e->getMessage(), 0, 200);
        }

        SyncLog::create([
            'site_id'         => $site->id,
            'order_id'        => $order->id,
            'source_order_id' => $order->source_order_id,
            'status'          => $ok ? 'success' : 'failed',
            'message'         => $message,
        ]);

        return $ok;
    }
}
