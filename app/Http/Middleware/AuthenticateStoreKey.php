<?php

namespace App\Http\Middleware;

use App\Models\WordPressSite;
use App\Support\Tenancy;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Public REST API for custom websites:  Authorization: Bearer {siteId}.{secret}
 * (the store's connection key from Integrations). HTTPS only in production.
 */
class AuthenticateStoreKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = (string) $request->bearerToken();

        if (! preg_match('/^(\d+)\.([A-Za-z0-9]{20,})$/', $key, $m)) {
            return $this->deny('missing_key', 'Send your connection key as "Authorization: Bearer <key>".');
        }

        $site = WordPressSite::withoutGlobalScopes()->whereKey((int) $m[1])->first();

        if (! $site || ! hash_equals((string) $site->api_key, $m[2])) {
            return $this->deny('invalid_key', 'This connection key is not valid.');
        }
        if (! $site->is_active) {
            return response()->json(['ok' => false, 'error' => 'store_inactive', 'message' => 'This store is switched off in Ecom Pilot.'], 403);
        }

        Tenancy::set($site->tenant_id);
        $site->forceFill(['last_seen_at' => now()])->saveQuietly();
        $request->attributes->set('store', $site);

        return $next($request);
    }

    private function deny(string $code, string $message): Response
    {
        return response()->json(['ok' => false, 'error' => $code, 'message' => $message], 401);
    }
}
