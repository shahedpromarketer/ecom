<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CourierSetting;
use App\Services\Couriers\CourierFactory;
use App\Services\Couriers\CourierTracker;
use App\Support\Tenancy;
use Illuminate\Http\Request;

/**
 * POST /api/courier-webhook/{token} — parcel status updates from couriers.
 * The secret token in the address says which shop's courier account this is.
 */
class CourierWebhookController extends Controller
{
    public function __invoke(Request $request, string $token)
    {
        $account = strlen($token) >= 30
            ? CourierSetting::withoutGlobalScopes()->where('webhook_token', $token)->first()
            : null;
        if (! $account) {
            return response()->json(['ok' => false, 'error' => 'unknown_webhook'], 404);
        }

        $payload = $request->json()->all() ?: $request->all();

        // Pathao checks the address first and expects its secret header echoed back.
        if ($account->courier === 'pathao' && ($payload['event'] ?? '') === 'webhook_integration') {
            // Echo Pathao's own challenge value when it sends one, else our secret.
            $challenge = $request->header('X-Pathao-Merchant-Webhook-Integration-Secret')
                ?? $payload['integration_secret'] ?? $payload['secret'] ?? $payload['token'] ?? $account->webhook_token;
            \Illuminate\Support\Facades\Log::warning('pathao.webhook_integration', [
                'account' => $account->id,
                'headers' => collect($request->headers->all())->except(['cookie', 'authorization'])->map(fn ($v) => $v[0] ?? null)->all(),
                'payload' => $payload,
            ]);

            return response()->json(['status' => 202, 'message' => 'Webhook integration successful'], 202)
                ->header('X-Pathao-Merchant-Webhook-Integration-Secret', (string) $challenge);
        }

        // Couriers that can send a secret must send ours (the address already holds it, this is a second lock).
        $sent = $request->bearerToken()
            ?? $request->header('X-Pathao-Signature')
            ?? $request->header('X-Paperfly-Secret')
            ?? $request->header('X-Webhook-Secret');
        if ($sent !== null && $sent !== '' && ! hash_equals($account->webhook_token, $sent)) {
            return response()->json(['ok' => false, 'error' => 'bad_secret'], 401);
        }

        $class = CourierFactory::class($account->courier);
        $update = $class::parseWebhook($payload);

        $result = Tenancy::as($account->tenant_id, function () use ($account, $update, $payload) {
            $account->forceFill(['last_webhook_at' => now()])->saveQuietly();
            if (! $update) {
                return 'ignored';
            }
            $shipment = CourierTracker::findShipment($account->courier, $update, $account->id);
            if (! $shipment) {
                return 'not_found';
            }

            return CourierTracker::apply($shipment, $update, 'webhook', $payload);
        });

        $response = response()->json(['ok' => true, 'status' => 'success', 'result' => $result], $account->courier === 'pathao' ? 202 : 200);

        return $account->courier === 'pathao'
            ? $response->header('X-Pathao-Merchant-Webhook-Integration-Secret', $account->webhook_token)
            : $response;
    }
}
