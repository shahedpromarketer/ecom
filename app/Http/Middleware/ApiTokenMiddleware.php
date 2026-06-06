<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiTokenMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $plain = $request->bearerToken()
            ?? $request->header('X-API-TOKEN')
            ?? $request->query('api_token');

        if (!$plain) {
            return response()->json(['error' => 'Missing API token'], 401);
        }

        $hashed = hash('sha256', $plain);

        $apiToken = ApiToken::where('token', $hashed)
            ->where('is_active', true)
            ->first();

        if (!$apiToken) {
            return response()->json(['error' => 'Invalid API token'], 401);
        }

        if ($apiToken->expires_at && $apiToken->expires_at->isPast()) {
            return response()->json(['error' => 'Token expired'], 401);
        }

        $apiToken->update(['last_used_at' => now()]);

        $request->merge(['_api_token' => $apiToken]);

        return $next($request);
    }
}
