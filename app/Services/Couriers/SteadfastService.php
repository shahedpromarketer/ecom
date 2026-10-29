<?php

namespace App\Services\Couriers;

use App\Models\CourierShipment;
use App\Models\Order;

/** Steadfast (portal.packzy.com). Keys: Api-Key + Secret-Key from the Steadfast panel → API. */
class SteadfastService extends BaseCourierService
{
    private const BASE = 'https://portal.packzy.com/api/v1';

    private function api()
    {
        return $this->http()->withHeaders([
            'Api-Key'    => (string) $this->account->api_key,
            'Secret-Key' => (string) $this->account->api_secret,
        ]);
    }

    public function createShipment(Order $order): array
    {
        $res = $this->api()->post(self::BASE . '/create_order', array_filter([
            'invoice'           => $order->courierReference(),
            'recipient_name'    => $order->shipping_name,
            'recipient_phone'   => $order->shipping_phone,
            'alternative_phone' => $order->alt_phone ?: null,
            'recipient_address' => mb_substr($this->address($order), 0, 250),
            'cod_amount'        => $this->cod($order),
            'note'              => mb_substr((string) $order->courier_note, 0, 200) ?: null,
            'item_description'  => $this->itemsSummary($order) ?: null,
        ], fn ($v) => $v !== null));

        $c = $res->json('consignment');
        if (! $res->successful() || empty($c['consignment_id'])) {
            $this->fail('booking', $res);
        }

        return [
            'consignment_id' => (string) $c['consignment_id'],
            'tracking_code'  => (string) ($c['tracking_code'] ?? $c['consignment_id']),
            'tracking_link'  => ! empty($c['tracking_code']) ? 'https://steadfast.com.bd/t/' . $c['tracking_code'] : null,
        ];
    }

    public function track(CourierShipment $s): ?array
    {
        $res = $this->api()->get(self::BASE . '/status_by_cid/' . urlencode((string) $s->consignment_id));
        $status = $res->json('delivery_status');

        return $res->successful() && $status ? ['courier_status' => $status] : null;
    }

    public function testConnection(): string
    {
        $res = $this->api()->get(self::BASE . '/get_balance');
        if (! $res->successful() || $res->json('current_balance') === null) {
            $this->fail('login', $res);
        }

        return 'Connected. Balance: ৳' . number_format((float) $res->json('current_balance'));
    }

    public static function normalize(string $s): string
    {
        return match (strtolower(trim($s))) {
            'delivered', 'delivered_approval_pending'                 => 'delivered',
            'partial_delivered', 'partial_delivered_approval_pending' => 'partial',
            'cancelled', 'cancelled_approval_pending'                 => 'returning',   // comes back to the shop
            'hold'                                                    => 'hold',
            'in_review', 'pending'                                    => 'in_transit',
            default                                                   => 'unknown',
        };
    }

    public static function parseWebhook(array $p): ?array
    {
        if (empty($p['consignment_id']) && empty($p['invoice'])) {
            return null;
        }
        $status = $p['status'] ?? $p['delivery_status'] ?? null;
        if (! $status && ($p['notification_type'] ?? '') === 'tracking_update') {
            $status = 'pending';   // a tracking note only
        }

        return [
            'consignment_id' => isset($p['consignment_id']) ? (string) $p['consignment_id'] : null,
            'invoice'        => $p['invoice'] ?? null,
            'courier_status' => (string) ($status ?? 'unknown'),
            'message'        => $p['tracking_message'] ?? null,
            'collected'      => self::num($p['cod_amount'] ?? null),
            'charge'         => self::num($p['delivery_charge'] ?? null),
            'at'             => $p['updated_at'] ?? null,
        ];
    }
}
