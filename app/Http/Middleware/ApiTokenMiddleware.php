<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use App\Support\Tenancy;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiTokenMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Header only — tokens in the query string end up in server and proxy logs.
        $plain = $request->bearerToken() ?? $request->header('X-API-TOKEN');

        if (! $plain) {
            return response()->json(['error' => 'Missing API token'], 401);
        }

        $apiToken = ApiToken::findActiveByPlainToken($plain);

        if (! $apiToken) {
            return response()->json(['error' => 'Invalid API token'], 401);
        }

        if ($apiToken->expires_at && $apiToken->expires_at->isPast()) {
            return response()->json(['error' => 'Token expired'], 401);
        }

        if (! $apiToken->tenant_id) {
            return response()->json(['error' => 'Token is not linked to a shop'], 401);
        }

        $apiToken->forceFill(['last_used_at' => now()])->saveQuietly();

        // Everything this request reads or writes stays inside the token's shop.
        Tenancy::set($apiToken->tenant_id);
        $request->attributes->set('api_token', $apiToken);

        return $next($request);
    }
}
