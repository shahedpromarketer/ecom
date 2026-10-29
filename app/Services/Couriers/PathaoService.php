<?php

namespace App\Services\Couriers;

use App\Models\CourierShipment;
use App\Models\Order;
use Illuminate\Support\Facades\Cache;

/**
 * Pathao (merchant.pathao.com → Developer API). City / zone are optional:
 * Pathao reads them from the address itself.
 */
class PathaoService extends BaseCourierService
{
    private function base(): string
    {
        return $this->account->sandbox ? 'https://courier-api-sandbox.pathao.com' : 'https://api-hermes.pathao.com';
    }

    private function token(): string
    {
        $key = 'pathao_token_' . $this->account->id . '_' . md5((string) $this->account->api_key . $this->account->extra('username'));
        if ($t = Cache::get($key)) {
            return $t;
        }

        $res = $this->http()->post($this->base() . '/aladdin/api/v1/issue-token', [
            'client_id'     => (string) $this->account->api_key,
            'client_secret' => (string) $this->account->api_secret,
            'username'      => (string) $this->account->extra('username'),
            'password'      => (string) $this->account->extra('password'),
            'grant_type'    => 'password',
        ]);
        if (! $res->successful() || ! $res->json('access_token')) {
            $this->fail('login', $res);
        }
        Cache::put($key, $res->json('access_token'), max(60, (int) ($res->json('expires_in') ?: 3600) - 300));

        return $res->json('access_token');
    }

    private function api()
    {
        return $this->http()->withToken($this->token());
    }

    public function createShipment(Order $order): array
    {
        $address = $this->address($order);
        if (mb_strlen($address) < 10) {
            throw new \RuntimeException('Pathao needs a full address (at least 10 letters).');
        }

        $res = $this->api()->post($this->base() . '/aladdin/api/v1/orders', array_filter([
            'store_id'                  => (int) $this->account->extra('store_id'),
            'merchant_order_id'         => $order->courierReference(),
            'recipient_name'            => $order->shipping_name,
            'recipient_phone'           => $order->shipping_phone,
            'recipient_secondary_phone' => $order->alt_phone ?: null,
            'recipient_address'         => mb_substr($address, 0, 220),
            'delivery_type'             => 48,   // normal delivery
            'item_type'                 => 2,    // parcel
            'special_instruction'       => mb_substr((string) $order->courier_note, 0, 200) ?: null,
            'item_quantity'             => $this->itemCount($order),
            'item_weight'               => $this->weightKg($order),
            'item_description'          => $this->itemsSummary($order) ?: null,
            'amount_to_collect'         => (int) round($this->cod($order)),
        ], fn ($v) => $v !== null));

        $d = $res->json('data');
        if (! $res->successful() || empty($d['consignment_id'])) {
            $this->fail('booking', $res);
        }

        return [
            'consignment_id' => (string) $d['consignment_id'],
            'tracking_code'  => (string) $d['consignment_id'],
            'tracking_link'  => 'https://merchant.pathao.com/tracking?consignment_id=' . $d['consignment_id'] . '&phone=' . $order->shipping_phone,
            'courier_charge' => self::num($d['delivery_fee'] ?? null),
        ];
    }

    public function track(CourierShipment $s): ?array
    {
        $res = $this->api()->get($this->base() . '/aladdin/api/v1/orders/' . urlencode((string) $s->consignment_id) . '/info');
        $d = $res->json('data');
        $status = $d['order_status_slug'] ?? $d['order_status'] ?? null;

        return $res->successful() && $status ? ['courier_status' => (string) $status] : null;
    }

    public function testConnection(): string
    {
        $res = $this->api()->get($this->base() . '/aladdin/api/v1/stores');
        if (! $res->successful()) {
            $this->fail('login', $res);
        }
        $stores = collect($res->json('data.data') ?? []);
        $mine = $stores->firstWhere('store_id', (int) $this->account->extra('store_id'));

        return $mine
            ? 'Connected. Store: ' . $mine['store_name']
            : 'Connected, but Store ID "' . $this->account->extra('store_id') . '" was not found. Your stores: '
                . $stores->map(fn ($s) => $s['store_id'] . ' (' . $s['store_name'] . ')')->implode(', ');
    }

    public static function normalize(string $s): string
    {
        $s = str_replace(['order.', '-', ' '], ['', '_', '_'], strtolower(trim($s)));

        return match ($s) {
            'pending', 'created', 'updated', 'pickup_requested', 'assigned_for_pickup' => 'submitted',
            'picked'                                                                   => 'picked',
            'at_the_sorting_hub', 'in_transit', 'received_at_last_mile_hub'            => 'in_transit',
            'assigned_for_delivery'                                                    => 'out_for_delivery',
            'delivered', 'paid', 'payment_invoice', 'exchange', 'exchanged'            => 'delivered',
            'partial_delivery', 'partial_delivered'                                    => 'partial',
            'delivery_failed', 'on_hold', 'pickup_failed'                              => 'hold',
            'return', 'returned', 'paid_return'                                        => 'returning',
            'pickup_cancelled', 'pickup_cancel'                                        => 'cancelled',
            default                                                                    => 'unknown',
        };
    }

    public static function parseWebhook(array $p): ?array
    {
        if (($p['event'] ?? '') === 'webhook_integration' || empty($p['consignment_id'])) {
            return null;
        }

        return [
            'consignment_id' => (string) $p['consignment_id'],
            'invoice'        => $p['merchant_order_id'] ?? null,
            'courier_status' => (string) ($p['order_status_slug'] ?? $p['order_status'] ?? $p['event'] ?? 'unknown'),
            'message'        => $p['reason'] ?? null,
            'collected'      => self::num($p['collected_amount'] ?? null),
            'charge'         => self::num($p['delivery_fee'] ?? null),
            'at'             => $p['updated_at'] ?? $p['timestamp'] ?? null,
        ];
    }
}
