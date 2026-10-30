<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;

class TenantMiddleware {
    /** Order work that waits for a recharge when a paying shop's wallet is empty. */
    private const WALLET_LOCKED = ['admin.orders.*', 'admin.dispatch.*', 'admin.failed-orders.*', 'admin.invoices.*', 'admin.payments.*', 'admin.returns.*'];

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

        // Shops paying per order are governed by their wallet, not by a plan end date.
        if ((float) $tenant->order_charge <= 0 && $tenant->plan_expires_at && $tenant->plan_expires_at->isPast()) {
            auth()->logout();
            return redirect('/admin/login')->with('error', 'Your plan has expired. Please renew.');
        }

        app()->instance('tenant', $tenant);

        // Empty wallet: orders keep arriving, but working them waits for a recharge.
        if ($tenant->walletLocked() && $request->routeIs(...self::WALLET_LOCKED)) {
            $message = 'Your wallet balance is empty. New orders are still saved — recharge to work on them.';
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message, 'wallet_locked' => true], 402);
            }

            return redirect()->route('admin.wallet.index')->with('error', $message);
        }

        return $next($request);
    }
}
