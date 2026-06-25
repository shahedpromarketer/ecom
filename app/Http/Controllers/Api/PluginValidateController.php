<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\WordPressSite;
use App\Services\OrderRestrictionService;
use Illuminate\Http\Request;

class PluginValidateController extends Controller
{
    public function __construct(private OrderRestrictionService $restriction) {}

    /**
     * Plugin calls this at WooCommerce checkout to decide allow/block.
     *
     * Request (JSON):
     *   api_key      - site api key (header X-API-KEY or body)
     *   phone        - customer phone
     *   ip           - customer IP
     *   total        - order total
     *   product_ids  - array of product IDs (optional)
     *   address      - shipping address (optional)
     *   items        - array of {qty} (optional)
     */
    public function validate(Request $request)
    {
        $apiKey = $request->header('X-API-KEY')
            ?? $request->header('x-api-key')
            ?? $request->input('api_key');

        if (!$apiKey) {
            return response()->json(['allowed' => true, 'message' => '']); // fail-open: don't block if no key
        }

        $site = WordPressSite::where('api_key', $apiKey)->where('is_active', true)->first();

        if (!$site || !$site->plugin_connected) {
            return response()->json(['allowed' => true, 'message' => '']);
        }

        $tenantId   = $site->tenant_id;
        $phone      = preg_replace('/[^0-9]/', '', $request->input('phone', ''));
        $ip         = $request->input('ip') ?: $request->ip();
        $total      = (float) $request->input('total', 0);
        $productIds = $request->input('product_ids', []);
        $address    = $request->input('address', '');
        $items      = $request->input('items', []);

        // Per-feature flags sent by plugin (default true if not sent)
        $checkIp        = $request->boolean('check_ip',        true);
        $check24h       = $request->boolean('check_24h',       true);
        $checkDuplicate = $request->boolean('check_duplicate', true);

        // ── IP Block ──────────────────────────────────────────────────────────
        if ($checkIp) {
            $ipCheck = $this->restriction->checkIpBlock($ip, $tenantId);
            if (!$ipCheck['allowed']) {
                return response()->json(['allowed' => false, 'reason' => 'ip_blocked', 'message' => $ipCheck['message']]);
            }
        }

        // ── Phone checks ─────────────────────────────────────────────────────
        if ($phone) {
            $formatCheck = $this->restriction->checkPhoneFormat($phone);
            if (!$formatCheck['allowed']) {
                return response()->json(['allowed' => false, 'reason' => 'invalid_phone', 'message' => $formatCheck['message']]);
            }

            // ── Phone blacklist (always on) ───────────────────────────────────
            $blacklistCheck = $this->restriction->checkBlacklist($phone);
            if (!$blacklistCheck['allowed']) {
                return response()->json(['allowed' => false, 'reason' => 'phone_blacklisted', 'message' => $blacklistCheck['message']]);
            }

            // ── 24-hour one-order limit ───────────────────────────────────────
            if ($check24h) {
                $limitCheck = $this->restriction->check24hLimit($phone, $tenantId);
                if (!$limitCheck['allowed']) {
                    return response()->json(['allowed' => false, 'reason' => '24h_limit', 'message' => $limitCheck['message']]);
                }
            }

            // ── Duplicate order block (same phone, recent order) ──────────────
            if ($checkDuplicate) {
                $dupCheck = $this->restriction->checkDuplicateOrder($phone);
                if (!$dupCheck['allowed']) {
                    return response()->json(['allowed' => false, 'reason' => 'duplicate_order', 'message' => $dupCheck['message']]);
                }
            }
        }

        return response()->json(['allowed' => true, 'message' => '']);
    }
}
