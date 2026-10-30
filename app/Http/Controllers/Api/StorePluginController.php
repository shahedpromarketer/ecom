<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SyncLog;
use App\Models\WordPressSite;
use App\Services\OrderRestrictionService;
use App\Services\WooOrderImporter;
use App\Support\Phone;
use Illuminate\Http\Request;

/**
 * Endpoints for the Ecom Pilot WordPress plugin (v2). Every request is signed;
 * VerifyPluginSignature has already identified the store and pinned its shop.
 */
class StorePluginController extends Controller
{
    private function store(Request $request): WordPressSite
    {
        return $request->attributes->get('store');
    }

    /** Plugin saved its settings / clicked "Test connection". */
    public function connect(Request $request)
    {
        $store = $this->store($request);
        $restUrl = (string) $request->input('rest_url');
        $store->update([
            'plugin_connected'    => true,
            'plugin_connected_at' => $store->plugin_connected_at ?? now(),
            'plugin_version'      => substr((string) $request->input('plugin_version'), 0, 20) ?: null,
            // Where to send status updates (works for headless sites where the shop front is elsewhere).
            'rest_url'            => filter_var($restUrl, FILTER_VALIDATE_URL) && str_starts_with($restUrl, 'https://') ? rtrim($restUrl, '/') : $store->rest_url,
        ]);

        return response()->json([
            'ok'         => true,
            'store'      => $store->name,
            'shop'       => $store->tenant?->name,
            'checkout'   => [
                'validate' => true,    // the plugin asks /validate before accepting an order
            ],
        ]);
    }

    /** One WooCommerce order, sent when it is created or changed. */
    public function order(Request $request)
    {
        $store = $this->store($request);
        $payload = $request->json()->all();

        try {
            [$order, $result] = WooOrderImporter::import($store, $payload);
        } catch (\Throwable $e) {
            report($e);
            SyncLog::create([
                'site_id' => $store->id, 'source_order_id' => (string) ($payload['id'] ?? ''),
                'status' => 'failed', 'message' => mb_substr($e->getMessage(), 0, 500), 'payload' => $payload,
            ]);

            return response()->json(['ok' => false, 'error' => 'import_failed'], 500);
        }

        if ($result === 'created') {
            $store->increment('total_synced');
        }
        $store->forceFill(['last_sync_at' => now(), 'last_order_sync_at' => now()])->saveQuietly();

        SyncLog::create([
            'site_id' => $store->id, 'order_id' => $order?->id, 'source_order_id' => (string) ($payload['id'] ?? ''),
            'status' => $result === 'skipped' ? 'skipped' : 'success', 'message' => ucfirst($result),
        ]);

        return response()->json([
            'ok'           => true,
            'result'       => $result,
            'order_id'     => $order?->id,
            'order_number' => $order?->order_number,
        ], $result === 'created' ? 201 : 200);
    }

    /** One product (with variations), sent when it is saved in WooCommerce. */
    public function product(Request $request)
    {
        $payload = $request->json()->all();
        if (empty($payload['id'])) {
            return response()->json(['ok' => false, 'error' => 'missing_id'], 422);
        }

        [$product, $result] = \App\Services\WooProductImporter::upsert($this->store($request), $payload);

        return response()->json(['ok' => true, 'result' => $result, 'product_id' => $product->id]);
    }

    /** The visitor typed their phone at checkout but has not ordered (yet). */
    public function incomplete(Request $request)
    {
        $row = \App\Services\IncompleteCheckoutRecorder::record($this->store($request), $request->json()->all());

        return response()->json(['ok' => true, 'recorded' => (bool) $row]);
    }

    /**
     * Stock changed in WooCommerce. Stock is counted in the CRM only (it goes down when a parcel
     * goes to the courier), so the store's number is not used. Kept so older plugins get an answer.
     */
    public function stock(Request $request)
    {
        return response()->json(['ok' => true, 'updated' => false]);
    }

    /**
     * Checkout check before an order is accepted (blacklist, blocked IP, repeat orders).
     * The plugin fails open: if the CRM cannot be reached, the order goes through.
     */
    public function validateCheckout(Request $request, OrderRestrictionService $rules)
    {
        $phone = Phone::normalize((string) $request->input('phone'));
        $ip    = (string) $request->input('ip', '');

        $checks = [
            'invalid_phone'   => $rules->checkPhoneFormat($phone),
            'phone_blocked'   => $rules->checkBlacklist($phone),
            'ip_blocked'      => $ip !== '' ? $rules->checkIpBlock($ip, $this->store($request)->tenant_id) : ['allowed' => true],
            'duplicate_order' => $rules->checkDuplicateOrder($phone),
            'order_limit'     => $rules->checkOrderLimit($phone),
        ];

        foreach ($checks as $reason => $check) {
            if (! ($check['allowed'] ?? true)) {
                return response()->json(['ok' => true, 'allowed' => false, 'reason' => $reason, 'message' => $check['message'] ?? 'Order not allowed.']);
            }
        }

        return response()->json(['ok' => true, 'allowed' => true]);
    }
}
