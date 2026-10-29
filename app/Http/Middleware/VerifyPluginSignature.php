<?php

namespace App\Http\Middleware;

use App\Models\WordPressSite;
use App\Support\PluginSignature;
use App\Support\Tenancy;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Authenticates the Ecom Pilot plugin: finds the store and checks the HMAC signature. */
class VerifyPluginSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $site = WordPressSite::withoutGlobalScopes()
            ->whereKey((int) $request->header('X-EP-Site'))
            ->first();

        if (! $site || ! PluginSignature::verify(
            (string) $site->api_key,
            $request->getContent(),
            $request->header('X-EP-Timestamp'),
            $request->header('X-EP-Signature'),
        )) {
            return response()->json(['ok' => false, 'error' => 'invalid_signature',
                'message' => 'Connection key is wrong, or the server clock is off by more than 5 minutes.'], 401);
        }

        if (! $site->is_active) {
            return response()->json(['ok' => false, 'error' => 'store_inactive',
                'message' => 'This store is switched off in Ecom Pilot.'], 403);
        }

        Tenancy::set($site->tenant_id);
        $site->forceFill(['last_seen_at' => now()])->saveQuietly();
        $request->attributes->set('store', $site);

        return $next($request);
    }
}
