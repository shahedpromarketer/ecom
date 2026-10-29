<?php

namespace App\Services\Couriers;

use App\Models\ActivityLog;
use App\Models\CourierEvent;
use App\Models\CourierSetting;
use App\Models\CourierShipment;
use App\Models\Order;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Books parcels and applies courier status updates (webhooks and syncs) to
 * shipments and their orders.
 */
class CourierTracker
{
    /** How far along a parcel is. A lower step never overwrites a higher one. */
    private const STEP = [
        'unknown' => 0, 'submitted' => 1, 'assigned' => 1, 'picked' => 2, 'in_transit' => 3,
        'hold' => 4, 'out_for_delivery' => 4, 'delivered' => 6, 'partial' => 6,
        'returning' => 7, 'returned' => 8, 'cancelled' => 8,
    ];

    /** Courier status → order status, and the order statuses it may replace. */
    private const ORDER_MOVES = [
        'delivered' => ['to' => 'delivered',         'from' => ['shipped', 'hold', 'pending_return']],
        'partial'   => ['to' => 'delivered',         'from' => ['shipped', 'hold', 'pending_return']],
        'returning' => ['to' => 'pending_return',    'from' => ['shipped', 'hold']],
        'returned'  => ['to' => 'return_collection', 'from' => ['shipped', 'hold', 'pending_return']],
    ];

    /**
     * Book an order with a courier account. Saves the shipment and marks the order shipped.
     * Throws with a readable reason when the courier refuses.
     */
    public static function book(Order $order, CourierSetting $account, ?float $deliveryFee = null, ?string $notes = null): CourierShipment
    {
        $existing = $order->shipment;
        if ($existing && $existing->consignment_id && ! in_array($existing->status, ['cancelled'], true)) {
            throw new \RuntimeException('Order #' . $order->order_number . ' is already booked with ' . ucfirst($existing->courier_name) . ' (' . $existing->tracking_code . ').');
        }

        $result = CourierFactory::for($account)->createShipment($order);

        $shipment = $order->shipment()->updateOrCreate(['order_id' => $order->id], [
            'courier_account_id' => $account->id,
            'courier_name'       => $account->courier,
            'consignment_id'     => $result['consignment_id'],
            'tracking_code'      => $result['tracking_code'],
            'tracking_link'      => $result['tracking_link'] ?? null,
            'courier_charge'     => $result['courier_charge'] ?? null,
            'cod_amount'         => max(0, (float) $order->total - (float) $order->paid_amount),
            'delivery_fee'       => $deliveryFee ?? (float) $order->shipping_charge,
            'status'             => 'submitted',
            'courier_status'     => 'booked',
            'courier_message'    => null,
            'status_updated_at'  => now(),
            'next_sync_at'       => now()->addHours(3),
            'notes'              => $notes,
            'return_received_at' => null,
            'paid_at'            => null,
        ]);
        $shipment->events()->create(['status' => 'submitted', 'courier_status' => 'booked', 'source' => 'manual',
            'message' => 'Booked with ' . $account->label() . ' — ' . $result['tracking_code'], 'happened_at' => now()]);

        if (! in_array($order->status, ['shipped', 'hand_delivery'], true)) {
            $order->update(['status' => 'shipped']);
        }
        ActivityLog::log('order.courier_submitted', $order, $account->label() . ' booked. Tracking: ' . $result['tracking_code'], $result);

        return $shipment;
    }

    /**
     * Apply what the courier told us. $u: courier_status, message?, collected?, charge?, at?
     * @return string what changed, for logs
     */
    public static function apply(CourierShipment $s, array $u, string $source = 'webhook', ?array $payload = null): string
    {
        $courierStatus = (string) ($u['courier_status'] ?? 'unknown');
        $status = CourierFactory::class($s->courier_name)::normalize($courierStatus);
        $at = self::time($u['at'] ?? null);

        return DB::transaction(function () use ($s, $u, $source, $payload, $courierStatus, $status, $at) {
            $s = CourierShipment::whereKey($s->id)->lockForUpdate()->first();

            // Same news again (couriers resend): only refresh amounts.
            $repeat = $s->courier_status === $courierStatus && $s->status === $status;
            if (! $repeat) {
                $s->events()->create([
                    'status' => $status, 'courier_status' => mb_substr($courierStatus, 0, 60),
                    'message' => isset($u['message']) ? mb_substr((string) $u['message'], 0, 500) : null,
                    'source' => $source, 'payload' => $payload, 'happened_at' => $at,
                ]);
            }

            $fill = array_filter([
                'collected_amount' => $u['collected'] ?? null,
                'courier_charge'   => $u['charge'] ?? null,
            ], fn ($v) => $v !== null);

            $moves = $status !== 'unknown' && (self::STEP[$status] ?? 0) >= (self::STEP[$s->status] ?? 0)
                // a parcel that is out again after "hold" / back on its way may step sideways
                && ! ($s->status === 'returned' && $status !== 'returned');

            if ($moves) {
                $fill += ['status' => $status, 'courier_status' => mb_substr($courierStatus, 0, 60), 'status_updated_at' => $at];
                if (! empty($u['message'])) {
                    $fill['courier_message'] = mb_substr((string) $u['message'], 0, 500);
                }
            } elseif (! $repeat) {
                $fill['courier_status'] = mb_substr($courierStatus, 0, 60);
            }
            $fill['next_sync_at'] = in_array($fill['status'] ?? $s->status, CourierShipment::FINAL, true) ? null : now()->addHours(3);
            $s->fill($fill)->save();

            if (! $moves) {
                return $repeat ? 'repeat' : 'noted';
            }

            self::moveOrder($s, $status);

            return 'moved:' . $status;
        });
    }

    private static function moveOrder(CourierShipment $s, string $status): void
    {
        $order = Order::withoutGlobalScope('assignee')->find($s->order_id);
        if (! $order) {
            return;
        }

        if ($status === 'cancelled' && in_array($order->status, ['shipped', 'hold'], true)) {
            ActivityLog::log('order.courier_cancelled', $order, ucfirst($s->courier_name) . ' cancelled the booking (' . $s->tracking_code . '). Book again or cancel the order.');
            return;
        }

        $move = self::ORDER_MOVES[$status] ?? null;
        if ($move && in_array($order->status, $move['from'], true) && $order->status !== $move['to']) {
            $from = $order->status;
            $order->update(['status' => $move['to']]);
            ActivityLog::log('order.courier_status', $order, ucfirst($s->courier_name) . ': ' . CourierShipment::STATUSES[$status] . " — order {$from} → {$move['to']}");
        }
    }

    /** Find the parcel a webhook / payment line is about. */
    public static function findShipment(string $courier, array $u, ?int $accountId = null): ?CourierShipment
    {
        $q = fn () => CourierShipment::where('courier_name', $courier)
            ->when($accountId, fn ($q) => $q->where(fn ($w) => $w->where('courier_account_id', $accountId)->orWhereNull('courier_account_id')));

        foreach (['consignment_id' => 'consignment_id', 'tracking_code' => 'tracking_code'] as $key => $col) {
            if (! empty($u[$key]) && ($s = $q()->where($col, (string) $u[$key])->latest('id')->first())) {
                return $s;
            }
        }
        // Our own order number (couriers call it invoice / merchant order id); may carry a "-R…" suffix.
        if (! empty($u['invoice'])) {
            $number = preg_replace('/-R\d+$/', '', (string) $u['invoice']);
            $orderId = Order::withoutGlobalScope('assignee')->where('order_number', $number)->value('id');
            if ($orderId) {
                return $q()->where('order_id', $orderId)->latest('id')->first();
            }
        }

        return null;
    }

    private static function time($v): Carbon
    {
        try {
            return $v ? Carbon::parse($v) : now();
        } catch (\Throwable $e) {
            return now();
        }
    }
}
