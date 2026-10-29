<?php

namespace App\Services\Couriers;

use App\Models\CourierShipment;
use App\Models\Order;

/**
 * Paperfly. Basic auth with the merchant panel username / password, plus the
 * "paperflykey" header from the panel's API Docs page.
 */
class PaperFlyService extends BaseCourierService
{
    private function base(): string
    {
        return $this->account->sandbox ? 'https://sandbox.paperfly-bd.com' : 'https://api.paperfly.com.bd';
    }

    private function api()
    {
        return $this->http()
            ->withBasicAuth((string) $this->account->extra('username'), (string) $this->account->extra('password'))
            ->withHeaders(['paperflykey' => (string) $this->account->api_key]);
    }

    public function createShipment(Order $order): array
    {
        $res = $this->api()->post($this->base() . '/merchant/api/service/new_order_v2.php', [
            'merchantOrderReference' => $order->courierReference(),
            'storeName'              => (string) ($this->account->extra('store_name') ?: $this->account->name),
            'productBrief'           => $this->itemsSummary($order, 150) ?: 'Parcel',
            'packagePrice'           => (string) $this->cod($order),
            'max_weight'             => (string) $this->weightKg($order),
            'customerName'           => $order->shipping_name,
            'customerAddress'        => mb_substr($this->address($order), 0, 250),
            'customerPhone'          => $order->shipping_phone,
        ]);

        $tn = $res->json('success.tracking_number');
        if (! $res->successful() || ! $tn) {
            $this->fail('booking', $res);
        }

        return [
            'consignment_id' => (string) $order->courierReference(),   // Paperfly works with our reference
            'tracking_code'  => (string) $tn,
            'tracking_link'  => 'https://go.paperfly.com.bd/track/order/' . $tn,
        ];
    }

    public function track(CourierShipment $s): ?array
    {
        $res = $this->api()->post($this->base() . '/API-Order-Tracking', ['ReferenceNumber' => (string) $s->consignment_id]);
        $t = $res->json('success.trackingStatus.0') ?? $res->json('success') ?? [];
        if (! $res->successful() || ! is_array($t)) {
            return null;
        }
        // Paperfly gives one time per stage; the latest stage that has a time wins.
        $stages = ['close' => 'cancelled', 'Returned' => 'returned', 'Partial' => 'partial', 'Delivered' => 'delivered',
            'PickedForDelivery' => 'out_for_delivery', 'ReceivedAtPoint' => 'in_transit', 'inTransit' => 'in_transit', 'Pick' => 'picked'];
        foreach ($stages as $field => $status) {
            if (! empty($t[$field . 'Time']) || (! empty($t[$field]) && ! in_array(strtolower((string) $t[$field]), ['no', '0', 'false'], true))) {
                return ['courier_status' => $status, 'collected' => self::num($t['receivedAmount'] ?? null)];
            }
        }

        return ['courier_status' => 'submitted'];
    }

    public function cancelShipment(CourierShipment $s): string
    {
        $res = $this->api()->post($this->base() . '/api/v1/cancel-order', ['order_id' => (string) $s->consignment_id]);
        if (! $res->successful()) {
            $this->fail('cancel', $res);
        }

        return 'Cancelled at Paperfly.';
    }

    public function testConnection(): string
    {
        $res = $this->api()->post($this->base() . '/API-Order-Tracking', ['ReferenceNumber' => 'EP-CONNECTION-TEST']);
        // Wrong keys: 400 {"error": {"message": "invalid Credentials/API KEY"}}. An unknown order is fine.
        $error = (string) $res->json('error.message');
        if (in_array($res->status(), [401, 403], true) || stripos($error, 'credential') !== false || stripos($error, 'api key') !== false) {
            $this->fail('login', $error ?: $res);
        }
        if ($res->serverError()) {
            $this->fail('connection', $res);
        }

        return 'Connected to Paperfly.';
    }

    public static function normalize(string $s): string
    {
        $s = str_replace(['parcel.', '-', ' '], ['', '_', '_'], strtolower(trim($s)));

        return match ($s) {
            'created', 'invoiced', 'submitted'                  => 'submitted',
            'picked_up', 'picked'                               => 'picked',
            'in_transit', 'received_at_point'                   => 'in_transit',
            'assigned_for_delivery', 'out_for_delivery'         => 'out_for_delivery',
            'delivered', 'exchange'                             => 'delivered',
            'partial'                                           => 'partial',
            'on_hold'                                           => 'hold',
            'return', 'return_transit'                          => 'returning',
            'return_to_merchant', 'returned'                    => 'returned',
            'cancelled', 'close'                                => 'cancelled',
            default                                             => 'unknown',
        };
    }

    public static function parseWebhook(array $p): ?array
    {
        $d = (array) ($p['data'] ?? []);
        if (empty($p['event']) || (empty($d['merchant_order_reference']) && empty($d['order_number']))) {
            return null;
        }

        return [
            'consignment_id' => $d['merchant_order_reference'] ?? null,
            'tracking_code'  => $d['order_number'] ?? null,
            'invoice'        => $d['merchant_order_reference'] ?? null,
            'courier_status' => (string) $p['event'],
            'message'        => $d['special_instruction'] ?? null,
            'at'             => $p['timestamp'] ?? null,
        ];
    }
}
