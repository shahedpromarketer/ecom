<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\SyncLog;
use App\Models\WordPressSite;
use App\Services\OrderRestrictionService;
use App\Services\WooOrderImporter;
use App\Support\Phone;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * REST API for custom websites (see Integrations → API docs).
 * Auth: Authorization: Bearer {connection key}. The store's shop is pinned by AuthenticateStoreKey.
 */
class PublicStoreApiController extends Controller
{
    private function store(Request $request): WordPressSite
    {
        return $request->attributes->get('store');
    }

    /** POST /api/v1/orders — create (or update, same external_id) an order. */
    public function storeOrder(Request $request)
    {
        $data = $request->validate([
            'external_id'        => 'required|string|max:100',
            'order_number'       => 'nullable|string|max:100',
            'customer'           => 'required|array',
            'customer.name'      => 'required|string|max:255',
            'customer.phone'     => ['required', 'string', 'max:20', function ($a, $v, $fail) {
                if (! Phone::isValidBdMobile($v)) {
                    $fail('customer.phone must be a Bangladeshi mobile number (01XXXXXXXXX).');
                }
            }],
            'customer.alt_phone' => 'nullable|string|max:20',
            'customer.email'     => 'nullable|email|max:255',
            'customer.address'   => 'required|string|max:1000',
            'customer.area'      => 'nullable|string|max:255',
            'customer.district'  => 'nullable|string|max:100',
            'items'              => 'required|array|min:1',
            'items.*.name'       => 'required|string|max:255',
            'items.*.sku'        => 'nullable|string|max:100',
            'items.*.variant'    => 'nullable|string|max:255',
            'items.*.qty'        => 'required|integer|min:1|max:1000',
            'items.*.price'      => 'required|numeric|min:0',
            'shipping_charge'    => 'nullable|numeric|min:0',
            'discount'           => 'nullable|numeric|min:0',
            'total'              => 'nullable|numeric|min:0',
            'payment_method'     => 'nullable|string|max:30',
            'paid'               => 'nullable|boolean',
            'status'             => ['nullable', Rule::in(['new', 'cancelled'])],
            'note'               => 'nullable|string|max:2000',
            'ip'                 => 'nullable|ip',
            'utm_source'         => 'nullable|string|max:100',
            'utm_medium'         => 'nullable|string|max:100',
            'utm_campaign'       => 'nullable|string|max:150',
            'created_at'         => 'nullable|date',
        ]);

        $store = $this->store($request);
        [$order, $result] = WooOrderImporter::import($store, self::toImporterPayload($data));

        if ($result === 'created') {
            $store->increment('total_synced');
        }
        $store->forceFill(['last_sync_at' => now(), 'last_order_sync_at' => now()])->saveQuietly();
        SyncLog::create(['site_id' => $store->id, 'order_id' => $order?->id, 'source_order_id' => $data['external_id'],
            'status' => $result === 'skipped' ? 'skipped' : 'success', 'message' => 'API: ' . $result]);

        return response()->json(['ok' => true, 'result' => $result] + self::orderView($order), $result === 'created' ? 201 : 200);
    }

    /** GET /api/v1/orders/{external_id} — current status in the CRM. */
    public function showOrder(Request $request, string $externalId)
    {
        $order = Order::withoutGlobalScope('assignee')
            ->where('wordpress_site_id', $this->store($request)->id)
            ->where('source_order_id', $externalId)
            ->with('shipment')
            ->first();

        if (! $order) {
            return response()->json(['ok' => false, 'error' => 'not_found', 'message' => 'No order with this external_id.'], 404);
        }

        return response()->json(['ok' => true] + self::orderView($order));
    }

    /** POST /api/v1/checkout/check — may this phone / IP order right now? */
    public function check(Request $request, OrderRestrictionService $rules)
    {
        $request->validate(['phone' => 'required|string|max:20', 'ip' => 'nullable|ip']);
        $phone = Phone::normalize($request->phone);

        $checks = [
            'invalid_phone'   => $rules->checkPhoneFormat($phone),
            'phone_blocked'   => $rules->checkBlacklist($phone),
            'ip_blocked'      => $request->ip ? $rules->checkIpBlock($request->ip, $this->store($request)->tenant_id) : ['allowed' => true],
            'duplicate_order' => $rules->checkDuplicateOrder($phone),
            'order_limit'     => $rules->checkOrderLimit($phone),
        ];
        foreach ($checks as $reason => $c) {
            if (! ($c['allowed'] ?? true)) {
                return response()->json(['ok' => true, 'allowed' => false, 'reason' => $reason, 'message' => $c['message'] ?? 'Not allowed.']);
            }
        }

        return response()->json(['ok' => true, 'allowed' => true]);
    }

    /** POST /api/v1/incomplete — what a visitor has typed at checkout so far. */
    public function incomplete(Request $request)
    {
        $request->validate([
            'session_id' => 'nullable|string|max:100',
            'phone'      => 'required|string|max:20',
            'name'       => 'nullable|string|max:255',
            'address'    => 'nullable|string|max:1000',
            'items'      => 'nullable|array|max:30',
            'total'      => 'nullable|numeric|min:0',
            'page_url'   => 'nullable|string|max:500',
        ]);

        $row = \App\Services\IncompleteCheckoutRecorder::record($this->store($request), $request->all());

        return response()->json(['ok' => true, 'recorded' => (bool) $row, 'status' => $row?->status]);
    }

    /** The simple public format → the importer's (WooCommerce-shaped) format. */
    public static function toImporterPayload(array $d): array
    {
        $c = $d['customer'];
        $parts = preg_split('/\s+/', trim($c['name']), 2);
        $items = array_map(fn ($i) => [
            'name'       => $i['name'],
            'sku'        => $i['sku'] ?? null,
            'qty'        => (int) $i['qty'],
            'price'      => (float) $i['price'],
            'subtotal'   => (float) $i['price'] * (int) $i['qty'],
            'total'      => (float) $i['price'] * (int) $i['qty'],
            'attributes' => ! empty($i['variant']) ? [$i['variant']] : [],
        ], $d['items']);
        $subtotal = array_sum(array_column($items, 'total'));
        $shipping = (float) ($d['shipping_charge'] ?? 0);
        $discount = (float) ($d['discount'] ?? 0);

        return [
            'id'             => $d['external_id'],
            'number'         => $d['order_number'] ?? $d['external_id'],
            'status'         => ($d['status'] ?? 'new') === 'cancelled' ? 'cancelled' : 'processing',
            'created_at'     => $d['created_at'] ?? null,
            'subtotal'       => $subtotal,
            'discount_total' => $discount,
            'shipping_total' => $shipping,
            'total'          => isset($d['total']) ? (float) $d['total'] : max(0, $subtotal - $discount + $shipping),
            'payment_method' => $d['payment_method'] ?? 'cod',
            'paid'           => (bool) ($d['paid'] ?? false),
            'customer_note'  => $d['note'] ?? '',
            'customer_ip'    => $d['ip'] ?? null,
            'billing'        => [
                'first_name' => $parts[0] ?? '',
                'last_name'  => $parts[1] ?? '',
                'phone'      => $c['phone'],
                'email'      => $c['email'] ?? '',
                'address_1'  => $c['address'],
                'city'       => $c['area'] ?? '',
                'state_name' => $c['district'] ?? '',
            ],
            'items'          => $items,
            'attribution'    => [
                'utm_source'   => $d['utm_source'] ?? null,
                'utm_medium'   => $d['utm_medium'] ?? null,
                'utm_campaign' => $d['utm_campaign'] ?? null,
            ],
        ];
    }

    public static function orderView(?Order $order): array
    {
        if (! $order) {
            return [];
        }

        return [
            'order' => [
                'external_id'   => $order->source_order_id,
                'order_number'  => $order->order_number,
                'status'        => $order->status,
                'total'         => (float) $order->total,
                'payment'       => $order->payment_status,
                'tracking_code' => $order->shipment?->tracking_code,
                'courier'       => $order->shipment?->courier_name,
                'updated_at'    => $order->updated_at?->toIso8601String(),
            ],
        ];
    }
}
