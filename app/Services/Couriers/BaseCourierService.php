<?php

namespace App\Services\Couriers;

use App\Models\CourierSetting;
use App\Models\CourierShipment;
use App\Models\Order;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

/**
 * One courier's API, used with one of the shop's courier accounts.
 * Every courier speaks its own status words; normalize() turns them into ours
 * (see CourierShipment::STATUSES).
 */
abstract class BaseCourierService
{
    public function __construct(protected CourierSetting $account)
    {
    }

    /**
     * Book a parcel.
     * @return array{consignment_id: string, tracking_code: string, tracking_link?: ?string, courier_charge?: ?float}
     */
    abstract public function createShipment(Order $order): array;

    /**
     * Ask the courier where a parcel is.
     * @return array{courier_status: string, message?: ?string, collected?: ?float, charge?: ?float}|null
     */
    abstract public function track(CourierShipment $shipment): ?array;

    /** Checks the keys work. Returns a short success message, throws with the reason otherwise. */
    abstract public function testConnection(): string;

    /** The courier's status word → ours. */
    abstract public static function normalize(string $courierStatus): string;

    /**
     * A webhook body → what happened, or null when it is not a parcel update.
     * @return array{consignment_id?: ?string, tracking_code?: ?string, invoice?: ?string, courier_status: string, message?: ?string, collected?: ?float, charge?: ?float, at?: ?string}|null
     */
    abstract public static function parseWebhook(array $payload): ?array;

    public function cancelShipment(CourierShipment $shipment): string
    {
        throw new \RuntimeException($this->account->label() . ' does not allow cancelling from outside its own panel. Cancel it there.');
    }

    /** Tracking page for customers, when the courier has one. */
    public static function trackingLink(CourierShipment $s): ?string
    {
        return $s->tracking_link;
    }

    // ── Helpers ─────────────────────────────────────────────────

    protected function http(): PendingRequest
    {
        return Http::timeout(25)->acceptJson()->asJson();
    }

    /** Cash the courier must collect: what the customer still owes. */
    protected function cod(Order $order): float
    {
        return round(max(0, (float) $order->total - (float) $order->paid_amount), 2);
    }

    protected function address(Order $order): string
    {
        return collect([$order->shipping_address, $order->shipping_area, $order->shipping_district])
            ->map(fn ($v) => trim((string) $v))->filter()->unique()->implode(', ');
    }

    protected function itemsSummary(Order $order, int $max = 200): string
    {
        $order->loadMissing('items');

        return mb_substr($order->items->map(fn ($i) => trim(($i->product_name ?? 'Item') . ' ' . ($i->variant_info ?? '')) . ' x' . $i->qty)->implode(', '), 0, $max);
    }

    protected function itemCount(Order $order): int
    {
        $order->loadMissing('items');

        return max(1, (int) $order->items->sum('qty'));
    }

    /** kg; the shop can set a default weight per account. */
    protected function weightKg(Order $order): float
    {
        return (float) $this->account->extra('weight_kg', 0.5) ?: 0.5;
    }

    protected function fail(string $what, $response): never
    {
        $body = is_object($response) && method_exists($response, 'json') ? ($response->json() ?? $response->body()) : $response;
        $msg = is_array($body) ? ($body['message'] ?? $body['error'] ?? $body['errors'] ?? $body) : $body;
        if (is_array($msg)) {
            $msg = collect($msg)->flatten()->implode(' ');
        }

        throw new \RuntimeException($this->account->label() . " {$what} failed: " . mb_substr((string) $msg, 0, 300));
    }

    protected static function num($v): ?float
    {
        return $v === null || $v === '' ? null : (float) preg_replace('/[^\d.\-]/', '', (string) $v);
    }
}
