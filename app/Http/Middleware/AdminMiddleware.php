<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Shop panel: owners/admins and staff with a role. What staff may do is checked by CheckPermission. */
class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check()) {
            return redirect()->route('admin.login');
        }

        $user = auth()->user();

        if (! $user->canUseAdminPanel() || $user->is_banned) {
            // Log out instead of redirecting to "/", which would send them straight back here.
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('admin.login')->withErrors([
                'email' => $user->is_banned ? 'This account has been disabled.' : 'This account cannot use the admin panel.',
            ]);
        }

        return $next($request);
    }
}
