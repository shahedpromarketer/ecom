<?php

namespace App\Services\Couriers;

use App\Models\CourierShipment;
use App\Models\Order;

/**
 * CarryBee Developers API v2. Headers: Client-ID, Client-Secret, Client-Context.
 * CarryBee needs city / zone ids: we ask its address-details endpoint to read them from the address.
 */
class CarryBeeService extends BaseCourierService
{
    private function base(): string
    {
        return $this->account->sandbox ? 'https://sandbox.carrybee.com' : 'https://developers.carrybee.com';
    }

    private function api()
    {
        return $this->http()->withHeaders([
            'Client-ID'      => (string) $this->account->api_key,
            'Client-Secret'  => (string) $this->account->api_secret,
            'Client-Context' => (string) $this->account->extra('client_context'),
        ]);
    }

    /** CarryBee wraps answers in {error, message, data}. */
    private function data($res, string $what): array
    {
        if (! $res->successful() || $res->json('error') === true) {
            $this->fail($what, $res);
        }

        return (array) ($res->json('data') ?? []);
    }

    /** @return array{city_id: int, zone_id: int, area_id: ?int} */
    public function findLocation(Order $order): array
    {
        $d = $this->data($this->api()->post($this->base() . '/api/v2/address-details', ['query' => $this->address($order)]), 'address lookup');
        $city = $d['city_id'] ?? $d['city']['id'] ?? null;
        $zone = $d['zone_id'] ?? $d['zone']['id'] ?? null;
        if (! $city || ! $zone) {
            throw new \RuntimeException('CarryBee could not find the city / zone from this address. Write the thana and district in the address.');
        }

        return ['city_id' => (int) $city, 'zone_id' => (int) $zone, 'area_id' => ($d['area_id'] ?? $d['area']['id'] ?? null) ?: null];
    }

    public function createShipment(Order $order): array
    {
        $loc = $this->findLocation($order);
        $d = $this->data($this->api()->post($this->base() . '/api/v2/orders', array_filter([
            'store_id'                  => (string) $this->account->extra('store_id'),
            'merchant_order_id'         => $order->courierReference(),
            'delivery_type'             => 1,   // normal
            'product_type'              => 1,   // parcel
            'recipient_phone'           => $order->shipping_phone,
            'recipient_secondary_phone' => $order->alt_phone ?: null,
            'recipient_name'            => $order->shipping_name,
            'recipient_address'         => mb_substr($this->address($order), 0, 250),
            'city_id'                   => $loc['city_id'],
            'zone_id'                   => $loc['zone_id'],
            'area_id'                   => $loc['area_id'],
            'special_instruction'       => mb_substr((string) $order->courier_note, 0, 200) ?: null,
            'product_description'       => $this->itemsSummary($order) ?: null,
            'item_weight'               => (int) round($this->weightKg($order) * 1000),   // grams
            'item_quantity'             => $this->itemCount($order),
            'collectable_amount'        => (int) round($this->cod($order)),
        ], fn ($v) => $v !== null)), 'booking');

        $o = $d['order'] ?? $d;
        $id = $o['consignment_id'] ?? $o['id'] ?? null;
        if (! $id) {
            $this->fail('booking', json_encode($d));
        }

        return [
            'consignment_id' => (string) $id,
            'tracking_code'  => (string) $id,
            'courier_charge' => self::num($o['delivery_fee'] ?? null),
        ];
    }

    public function track(CourierShipment $s): ?array
    {
        $res = $this->api()->get($this->base() . '/api/v2/orders/' . urlencode((string) $s->consignment_id) . '/details');
        if (! $res->successful() || $res->json('error') === true) {
            return null;
        }
        $d = (array) ($res->json('data.order') ?? $res->json('data') ?? []);
        $status = $d['transfer_status'] ?? $d['order_status'] ?? $d['status'] ?? null;

        return $status ? ['courier_status' => (string) $status, 'collected' => self::num($d['collected_amount'] ?? null)] : null;
    }

    public function testConnection(): string
    {
        $d = $this->data($this->api()->get($this->base() . '/api/v2/stores'), 'login');
        $stores = collect($d['stores'] ?? $d);

        return 'Connected. Stores: ' . ($stores->map(fn ($s) => ($s['id'] ?? '') . ' (' . ($s['name'] ?? '') . ')')->implode(', ') ?: 'none');
    }

    public static function normalize(string $s): string
    {
        $s = str_replace(['order.', '-', ' '], ['', '_', '_'], strtolower(trim($s)));

        return match (true) {
            str_contains($s, 'partial')                                   => 'partial',
            str_contains($s, 'delivered') && ! str_contains($s, 'un')    => 'delivered',
            str_contains($s, 'returned') || str_contains($s, 'return_to_merchant') => 'returned',
            str_contains($s, 'return')                                    => 'returning',
            str_contains($s, 'cancel')                                    => 'cancelled',
            str_contains($s, 'hold') || str_contains($s, 'failed')        => 'hold',
            str_contains($s, 'out_for_delivery') || str_contains($s, 'assigned_for_delivery') => 'out_for_delivery',
            str_contains($s, 'transit') || str_contains($s, 'hub') || str_contains($s, 'sorting') => 'in_transit',
            str_contains($s, 'picked')                                    => 'picked',
            str_contains($s, 'pending') || str_contains($s, 'created') || str_contains($s, 'pickup') => 'submitted',
            default                                                       => 'unknown',
        };
    }

    public static function parseWebhook(array $p): ?array
    {
        $d = (array) ($p['data'] ?? $p);
        $id = $d['consignment_id'] ?? null;
        $status = $d['transfer_status'] ?? $d['order_status'] ?? $d['status'] ?? $p['event'] ?? null;
        if (! $id || ! $status) {
            return null;
        }

        return [
            'consignment_id' => (string) $id,
            'invoice'        => $d['merchant_order_id'] ?? null,
            'courier_status' => (string) $status,
            'message'        => $d['reason'] ?? $d['message'] ?? null,
            'collected'      => self::num($d['collected_amount'] ?? null),
            'charge'         => self::num($d['delivery_fee'] ?? null),
            'at'             => $d['updated_at'] ?? $p['timestamp'] ?? null,
        ];
    }
}
