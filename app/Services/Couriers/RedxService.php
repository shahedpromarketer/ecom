<?php

namespace App\Services\Couriers;

use App\Models\CourierShipment;
use App\Models\Order;
use Illuminate\Support\Facades\Cache;

/**
 * RedX Open API. The token goes in the API-ACCESS-TOKEN header ("Bearer …").
 * RedX needs a delivery area id: we look it up from the order's district / area.
 */
class RedxService extends BaseCourierService
{
    private function base(): string
    {
        return $this->account->sandbox ? 'https://sandbox.redx.com.bd/v1.0.0-beta' : 'https://openapi.redx.com.bd/v1.0.0-beta';
    }

    private function api()
    {
        $token = trim((string) $this->account->api_key);

        return $this->http()->withHeaders(['API-ACCESS-TOKEN' => str_starts_with($token, 'Bearer ') ? $token : 'Bearer ' . $token]);
    }

    /** RedX areas of a district (cached a day). */
    private function areas(string $district): array
    {
        return Cache::remember('redx_areas_' . $this->account->id . '_' . md5(mb_strtolower($district)), 86400, function () use ($district) {
            $res = $this->api()->get($this->base() . '/areas', ['district_name' => $district]);

            return $res->successful() ? ($res->json('areas') ?? []) : [];
        });
    }

    /** @return array{id: int, name: string} */
    public function findArea(Order $order): array
    {
        $district = trim((string) $order->shipping_district);
        if ($district === '') {
            throw new \RuntimeException('RedX needs the district of the address. Add it to the order first.');
        }
        $areas = $this->areas($district);
        if (! $areas) {
            throw new \RuntimeException("RedX has no delivery areas for district \"{$district}\". Check the spelling.");
        }

        $hay = mb_strtolower($order->shipping_area . ' ' . $order->shipping_address);
        $best = null;
        foreach ($areas as $a) {
            $name = mb_strtolower(trim(preg_replace('/\(.*?\)/', '', $a['name'] ?? '')));
            if ($name !== '' && str_contains($hay, $name) && (! $best || mb_strlen($name) > mb_strlen($best['_n']))) {
                $best = $a + ['_n' => $name];
            }
        }
        // Nothing in the address matched: the district's own / sadar area.
        $best ??= collect($areas)->first(fn ($a) => str_contains(mb_strtolower($a['name']), mb_strtolower($district)) || str_contains(mb_strtolower($a['name']), 'sadar'));
        if (! $best) {
            throw new \RuntimeException('RedX could not tell the delivery area from the address. Write the area (thana) in the address.');
        }

        return ['id' => (int) $best['id'], 'name' => $best['name']];
    }

    public function createShipment(Order $order): array
    {
        $area = $this->findArea($order);
        $res = $this->api()->post($this->base() . '/parcel', array_filter([
            'customer_name'          => $order->shipping_name,
            'customer_phone'         => $order->shipping_phone,
            'delivery_area'          => $area['name'],
            'delivery_area_id'       => $area['id'],
            'customer_address'       => mb_substr($this->address($order), 0, 250),
            'merchant_invoice_id'    => $order->courierReference(),
            'cash_collection_amount' => (string) $this->cod($order),
            'parcel_weight'          => (string) (int) round($this->weightKg($order) * 1000),   // grams
            'instruction'            => mb_substr((string) $order->courier_note, 0, 200) ?: null,
            'value'                  => (string) (float) $order->total,
            'pickup_store_id'        => $this->account->extra('pickup_store_id') ?: null,
        ], fn ($v) => $v !== null && $v !== ''));

        $id = $res->json('tracking_id');
        if (! $res->successful() || ! $id) {
            $this->fail('booking', $res);
        }

        return [
            'consignment_id' => (string) $id,
            'tracking_code'  => (string) $id,
            'tracking_link'  => 'https://redx.com.bd/track-parcel/?trackingId=' . $id,
        ];
    }

    public function track(CourierShipment $s): ?array
    {
        $res = $this->api()->get($this->base() . '/parcel/info/' . urlencode((string) $s->tracking_code));
        $p = $res->json('parcel');

        return $res->successful() && ! empty($p['status'])
            ? ['courier_status' => (string) $p['status'], 'charge' => self::num($p['charge'] ?? null)]
            : null;
    }

    public function cancelShipment(CourierShipment $s): string
    {
        $res = $this->api()->patch($this->base() . '/parcels', [
            'entity_type'    => 'parcel-tracking-id',
            'entity_id'      => (string) $s->tracking_code,
            'update_details' => ['property_name' => 'status', 'new_value' => 'cancelled', 'reason' => 'Cancelled by merchant'],
        ]);
        if (! $res->successful()) {
            $this->fail('cancel', $res);
        }

        return 'Cancelled at RedX.';
    }

    public function testConnection(): string
    {
        $res = $this->api()->get($this->base() . '/pickup/stores');
        if (! $res->successful()) {
            $this->fail('login', $res);
        }
        $stores = collect($res->json('pickup_stores') ?? []);

        return 'Connected. Pickup stores: ' . ($stores->map(fn ($s) => ($s['id'] ?? '') . ' (' . ($s['name'] ?? '') . ')')->implode(', ') ?: 'none');
    }

    public static function normalize(string $s): string
    {
        return match (strtolower(trim($s))) {
            'pickup-pending', 'pickup-in-progress'     => 'submitted',
            'ready-for-delivery', 'agent-area-change'  => 'in_transit',
            'delivery-in-progress'                     => 'out_for_delivery',
            'delivered'                                => 'delivered',
            'partial-delivered', 'partially-delivered' => 'partial',
            'agent-hold', 'hold'                       => 'hold',
            'agent-returning', 'returning'             => 'returning',
            'returned'                                 => 'returned',
            'cancelled', 'pickup-cancelled'            => 'cancelled',
            default                                    => 'unknown',
        };
    }

    public static function parseWebhook(array $p): ?array
    {
        if (empty($p['tracking_number']) || empty($p['status'])) {
            return null;
        }

        return [
            'tracking_code'  => (string) $p['tracking_number'],
            'invoice'        => $p['invoice_number'] ?? null,
            'courier_status' => (string) $p['status'],
            'message'        => $p['message_en'] ?? $p['message_bn'] ?? null,
            'at'             => $p['timestamp'] ?? null,
        ];
    }
}
