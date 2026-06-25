<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;

class TenantMiddleware {
    public function handle(Request $request, Closure $next) {
        if (!auth()->check()) return redirect('/admin/login');

        $user = auth()->user();

        if ($user->is_super_admin) return $next($request);

        if (!$user->tenant_id) {
            auth()->logout();
            return redirect('/admin/login')->with('error', 'No tenant assigned.');
        }

        $tenant = $user->tenant;

        if (!$tenant) {
            auth()->logout();
            return redirect('/admin/login')->with('error', 'Tenant not found.');
        }

        if ($tenant->status === 'suspended') {
            auth()->logout();
            return redirect('/admin/login')->with('error', 'Your account has been suspended.');
        }

        if ($tenant->plan_expires_at && $tenant->plan_expires_at->isPast()) {
            auth()->logout();
            return redirect('/admin/login')->with('error', 'Your plan has expired. Please renew.');
        }

        app()->instance('tenant', $tenant);

        return $next($request);
    }
}
