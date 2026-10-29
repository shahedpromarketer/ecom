<?php

namespace App\Http\Middleware;

use App\Support\Permissions;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Blocks staff from admin pages and actions their role does not allow. */
class CheckPermission
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->hasFullAccess()) {
            return $next($request);
        }

        $required = Permissions::requiredFor($request);

        if ($required === null || ($required !== false && $user->hasPermission($required))) {
            return $next($request);
        }

        $message = 'You do not have permission to do this. Ask your shop admin.';

        if ($request->expectsJson()) {
            return response()->json(['success' => false, 'message' => $message], 403);
        }

        // 'permission_error' is shown by layouts/admin.blade.php on every page.
        return $request->isMethodSafe()
            ? redirect()->route('admin.dashboard')->with('permission_error', $message)
            : back()->with('permission_error', $message);
    }
}
