<?php
namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Announcement;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\SyncLog;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WordPressSite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class SuperAdminController extends Controller {

    // ─── Dashboard ────────────────────────────────────────────────────────────

    public function dashboard() {
        $stats = [
            'total'     => Tenant::count(),
            'active'    => Tenant::where('status', 'active')->count(),
            'suspended' => Tenant::where('status', 'suspended')->count(),
            'expired'   => Tenant::where('status', 'expired')->count(),
            'trial'     => Tenant::where('plan', 'basic')->where('plan_expires_at', '>', now())->count(),
        ];
        $tenants = Tenant::with('owner')->latest()->take(10)->get();
        return view('super-admin.dashboard', compact('stats', 'tenants'));
    }

    // ─── Tenants (Feature 5: Search/Filter) ──────────────────────────────────

    public function tenants(Request $request) {
        $query = Tenant::with('owner')->withCount('orders');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%");
            });
        }
        if ($request->filled('plan'))   $query->where('plan', $request->plan);
        if ($request->filled('status')) $query->where('status', $request->status);

        $tenants = $query->latest()->paginate(20)->withQueryString();
        return view('super-admin.tenants', compact('tenants'));
    }

    public function createTenant() {
        return view('super-admin.create-tenant');
    }

    public function storeTenant(Request $request) {
        $request->validate([
            'shop_name' => 'required',
            'email'     => 'required|email|unique:users,email',
            'password'  => 'required|min:6',
            'plan'      => 'required|in:basic,pro,enterprise',
            'plan_days' => 'required|integer|min:1',
        ]);

        $maxOrders = match($request->plan) { 'basic' => 500, 'pro' => 5000, default => 999999 };
        $maxUsers  = match($request->plan) { 'basic' => 2,   'pro' => 10,   default => 999 };

        $tenant = Tenant::create([
            'name'            => $request->shop_name,
            'email'           => $request->email,
            'phone'           => $request->phone ?? null,
            'status'          => 'active',
            'plan'            => $request->plan,
            'plan_expires_at' => now()->addDays((int) $request->plan_days),
            'max_orders'      => $maxOrders,
            'max_users'       => $maxUsers,
        ]);

        $user = User::create([
            'name'              => $request->owner_name ?? $request->shop_name,
            'email'             => $request->email,
            'password'          => bcrypt($request->password),
            'tenant_id'         => $tenant->id,
            'is_super_admin'    => false,
            'email_verified_at' => now(),
            'role'              => 'admin',
        ]);

        $tenant->update(['owner_id' => $user->id]);

        $this->logAction('tenant.create', $tenant, "Created tenant: {$tenant->name}");

        return redirect()->route('super.tenants.index')
            ->with('success', 'Tenant created. Login: ' . $request->email . ' / ' . $request->password);
    }

    // ─── Feature 1: Tenant Detail ─────────────────────────────────────────────

    public function showTenant($id) {
        $tenant       = Tenant::with('owner', 'users')->withCount('orders')->findOrFail($id);
        $recentOrders = Order::where('tenant_id', $id)->latest()->take(10)->get();
        $usersCount   = User::where('tenant_id', $id)->count();
        $wpSites      = WordPressSite::where('tenant_id', $id)
                            ->whereNotNull('consumer_key')
                            ->whereNotNull('consumer_secret')
                            ->get();
        // Wallet: balance health, costs, recharges and the latest fees with their orders.
        $w          = \App\Services\WalletStats::summary($tenant);
        $walletDays = \App\Services\WalletStats::daily($tenant, now()->subDays(13)->startOfDay());
        $walletTx   = \App\Models\WalletTransaction::withoutGlobalScopes()->with('order')->where('tenant_id', $tenant->id)->latest('id')->take(30)->get();
        $recharges  = \App\Models\WalletRechargeRequest::withoutGlobalScopes()->where('tenant_id', $tenant->id)->latest('id')->take(8)->get();

        return view('super-admin.tenant-show', compact('tenant', 'recentOrders', 'usersCount', 'wpSites', 'w', 'walletDays', 'walletTx', 'recharges'));
    }

    public function syncTenantOrders(Request $request, $id)
    {
        $request->validate(['days' => 'required|in:1,3,7', 'site_id' => 'required|integer']);

        $site = WordPressSite::where('id', $request->site_id)
            ->where('tenant_id', $id)
            ->firstOrFail();

        // Work as that shop for the rest of this request (duplicate checks, SKU matching, new orders).
        \App\Support\Tenancy::set((int) $id);

        $after       = now()->subDays((int) $request->days)->toIso8601String();
        $credentials = base64_encode($site->consumer_key . ':' . $site->consumer_secret);

        try {
            $response = Http::timeout(30)
                ->withOptions(['verify' => (bool) config('services.woocommerce.verify_ssl', true)])
                ->withHeaders(['Authorization' => 'Basic ' . $credentials])
                ->get(rtrim($site->site_url, '/') . '/wp-json/wc/v3/orders', [
                    'per_page' => 100,
                    'status'   => 'any',
                    'orderby'  => 'date',
                    'order'    => 'desc',
                    'after'    => $after,
                ]);

            if ($response->failed()) {
                return response()->json(['success' => false, 'message' => 'WooCommerce API error: ' . $response->status()]);
            }

            $wcOrders = $response->json();
            if (!is_array($wcOrders)) {
                return response()->json(['success' => false, 'message' => 'Invalid API response.']);
            }

            $synced  = 0;
            $skipped = 0;
            $failed  = 0;

            foreach ($wcOrders as $wcOrder) {
                if (empty($wcOrder['id'])) continue;

                try {
                    $existing = Order::where('source_order_id', (string) $wcOrder['id'])
                        ->where('source', 'woocommerce')
                        ->where('tenant_id', $site->tenant_id)
                        ->first();

                    if ($existing) { $skipped++; continue; }

                    $billing = $wcOrder['billing'] ?? [];
                    $phone   = preg_replace('/[^0-9]/', '', $billing['phone'] ?? '');
                    if (strlen($phone) > 11) $phone = substr($phone, -11);
                    if (empty($phone)) $phone = '01000000000';

                    $order = new Order([
                        'order_number'      => 'EPL' . \Carbon\Carbon::parse($wcOrder['date_created'] ?? now())->format('Ymd') . '-WC' . $wcOrder['id'],
                        'status'            => 'pending',
                        'shipping_name'     => trim(($billing['first_name'] ?? '') . ' ' . ($billing['last_name'] ?? '')),
                        'shipping_phone'    => $phone,
                        'shipping_division' => $billing['state'] ?? 'Dhaka',
                        'shipping_district' => $billing['city'] ?? 'Dhaka',
                        'shipping_area'     => $billing['city'] ?? '',
                        'shipping_address'  => $billing['address_1'] ?? '',
                        'subtotal'          => collect($wcOrder['line_items'] ?? [])->sum('subtotal'),
                        'discount'          => $wcOrder['discount_total'] ?? 0,
                        'shipping_charge'   => $wcOrder['shipping_total'] ?? 0,
                        'total'             => $wcOrder['total'],
                        'payment_method'    => 'cod',
                        'payment_status'    => ($wcOrder['payment_method'] ?? '') === 'cod' ? 'unpaid' : 'paid',
                        'source'            => 'woocommerce',
                        'source_order_id'   => (string) $wcOrder['id'],
                        'source_url'        => $site->site_url,
                        'print_status'      => 'unprinted',
                        'tenant_id'         => $site->tenant_id,
                    ]);
                    $order->created_at = \Carbon\Carbon::parse($wcOrder['date_created'] ?? now());
                    $order->save();

                    foreach ($wcOrder['line_items'] ?? [] as $item) {
                        OrderItem::create([
                            'order_id'     => $order->id,
                            'product_name' => $item['name'] ?? 'Unknown',
                            'qty'          => $item['quantity'] ?? 1,
                            'unit_price'   => $item['price'] ?? 0,
                            'total'        => $item['total'] ?? 0,
                        ]);
                    }

                    SyncLog::create([
                        'site_id'         => $site->id,
                        'order_id'        => $order->id,
                        'source_order_id' => (string) $wcOrder['id'],
                        'status'          => 'success',
                        'message'         => 'Synced by super admin (last ' . $request->days . ' day(s))',
                    ]);

                    $synced++;
                } catch (\Exception $e) {
                    $failed++;
                }
            }

            if ($synced > 0) {
                $site->update(['last_order_sync_at' => now(), 'total_synced' => $site->total_synced + $synced]);
            }

            $this->logAction('tenant.sync_orders', Tenant::find($id), "Super admin synced {$synced} orders for tenant ID {$id} (last {$request->days} day(s))");

            return response()->json([
                'success' => true,
                'synced'  => $synced,
                'skipped' => $skipped,
                'failed'  => $failed,
                'message' => "{$synced} new orders synced, {$skipped} already existed" . ($failed ? ", {$failed} failed" : ''),
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Connection error: ' . $e->getMessage()]);
        }
    }

    // ─── Feature 9: Custom Limits ─────────────────────────────────────────────

    public function updateLimits(Request $request, $id) {
        $request->validate([
            'max_orders' => 'required|integer|min:1',
            'max_users'  => 'required|integer|min:1',
        ]);

        $tenant = Tenant::findOrFail($id);
        $tenant->update([
            'max_orders' => $request->max_orders,
            'max_users'  => $request->max_users,
        ]);

        $this->logAction('tenant.limits', $tenant, "Updated limits for {$tenant->name}: max_orders={$request->max_orders}, max_users={$request->max_users}");

        return back()->with('success', 'Limits updated.');
    }

    // ─── Suspend / Activate / Delete ─────────────────────────────────────────

    public function suspendTenant($id) {
        $tenant = Tenant::findOrFail($id);
        $tenant->update(['status' => 'suspended']);
        $this->logAction('tenant.suspend', $tenant, "Suspended tenant: {$tenant->name}");
        return back()->with('success', 'Tenant suspended.');
    }

    public function activateTenant($id) {
        $tenant = Tenant::findOrFail($id);
        $tenant->update(['status' => 'active']);
        $this->logAction('tenant.activate', $tenant, "Activated tenant: {$tenant->name}");
        return back()->with('success', 'Tenant activated.');
    }

    public function deleteTenant($id) {
        $tenant = Tenant::findOrFail($id);
        $name   = $tenant->name;
        User::where('tenant_id', $id)->delete();
        $tenant->delete();
        $this->logAction('tenant.delete', (object)['id' => $id], "Deleted tenant: {$name}");
        return back()->with('success', 'Tenant deleted.');
    }

    public function assignPlan(Request $request, $id) {
        $request->validate(['plan' => 'required|in:basic,pro,enterprise', 'plan_days' => 'required|integer|min:1']);
        $tenant = Tenant::findOrFail($id);
        $tenant->update([
            'plan'            => $request->plan,
            'plan_expires_at' => now()->addDays((int) $request->plan_days),
            'status'          => 'active',
        ]);
        $this->logAction('tenant.plan', $tenant, "Changed plan for {$tenant->name} to {$request->plan} (+{$request->plan_days} days)");
        return back()->with('success', 'Plan updated.');
    }

    // ─── Feature 6: Bulk Actions ──────────────────────────────────────────────

    public function bulkAction(Request $request) {
        $request->validate(['bulk_action' => 'required', 'tenant_ids' => 'required|array']);

        $ids    = $request->tenant_ids;
        $action = $request->bulk_action;

        switch ($action) {
            case 'suspend':
                Tenant::whereIn('id', $ids)->update(['status' => 'suspended']);
                $msg = count($ids) . ' tenants suspended.';
                break;

            case 'activate':
                Tenant::whereIn('id', $ids)->update(['status' => 'active']);
                $msg = count($ids) . ' tenants activated.';
                break;

            case 'extend_30':
                foreach (Tenant::whereIn('id', $ids)->get() as $t) {
                    $base = $t->plan_expires_at && $t->plan_expires_at->isFuture() ? $t->plan_expires_at : now();
                    $t->update(['plan_expires_at' => $base->addDays(30), 'status' => 'active']);
                }
                $msg = count($ids) . ' tenants extended by 30 days.';
                break;

            case 'extend_365':
                foreach (Tenant::whereIn('id', $ids)->get() as $t) {
                    $base = $t->plan_expires_at && $t->plan_expires_at->isFuture() ? $t->plan_expires_at : now();
                    $t->update(['plan_expires_at' => $base->addDays(365), 'status' => 'active']);
                }
                $msg = count($ids) . ' tenants extended by 1 year.';
                break;

            default:
                return back()->with('error', 'Invalid action.');
        }

        ActivityLog::create([
            'user_id'          => auth()->id(),
            'action'           => 'bulk.' . $action,
            'action_performed' => 'bulk.' . $action,
            'subject_type'     => Tenant::class,
            'subject_id'       => 0,
            'description'      => $msg . ' IDs: ' . implode(',', $ids),
            'ip_address'       => request()->ip(),
            'user_agent'       => request()->userAgent(),
            'tenant_id'        => null,
        ]);

        return back()->with('success', $msg);
    }

    /** No email here, so a forgotten password is reset by the super admin, who tells the client. */
    public function setUserPassword(Request $request, $id, $userId)
    {
        $data = $request->validate(['password' => 'required|string|min:8|max:100']);
        $user = User::where('tenant_id', $id)->findOrFail($userId);   // only a user of this shop
        $user->forceFill(['password' => bcrypt($data['password']), 'remember_token' => \Illuminate\Support\Str::random(60)])->save();
        // Log out the old sessions (database sessions).
        rescue(fn () => \Illuminate\Support\Facades\DB::table('sessions')->where('user_id', $user->id)->delete(), null, false);

        ActivityLog::create([
            'user_id'          => auth()->id(),
            'action'           => 'user.password_reset',
            'action_performed' => 'user.password_reset',
            'subject_type'     => User::class,
            'subject_id'       => $user->id,
            'description'      => 'Super admin set a new password for ' . $user->email,
            'ip_address'       => $request->ip(),
            'user_agent'       => $request->userAgent(),
            'tenant_id'        => null,
        ]);

        return back()->with('success', 'New password set for ' . $user->email . '. Send it to the client; they can change it later from their profile.');
    }

    // ─── Feature 2: Impersonation ─────────────────────────────────────────────

    public function impersonate($id) {
        $tenant = Tenant::findOrFail($id);
        $owner  = User::where('tenant_id', $id)->where('role', 'admin')->first();

        if (!$owner) return back()->with('error', 'No admin user found for this tenant.');

        session(['impersonating_super_admin_id' => auth()->id()]);
        auth()->login($owner);

        $this->logAction('tenant.impersonate', $tenant, "Impersonated tenant: {$tenant->name} (as {$owner->email})");

        return redirect('/admin/dashboard');
    }

    public function stopImpersonating() {
        $superAdminId = session('impersonating_super_admin_id');
        if (!$superAdminId) return redirect()->route('super.dashboard');

        session()->forget('impersonating_super_admin_id');
        auth()->loginUsingId($superAdminId);

        return redirect()->route('super.dashboard')->with('success', 'Impersonation ended.');
    }

    // ─── Feature 7: Announcements ─────────────────────────────────────────────

    public function announcements() {
        $announcements = Announcement::with('sender')->latest()->paginate(20);
        return view('super-admin.announcements', compact('announcements'));
    }

    public function sendAnnouncement(Request $request) {
        $request->validate([
            'title'   => 'required|string|max:200',
            'message' => 'required|string',
            'target'  => 'required|in:all,active,plan_basic,plan_pro,plan_enterprise',
        ]);

        Announcement::create([
            'title'   => $request->title,
            'message' => $request->message,
            'target'  => $request->target,
            'sent_by' => auth()->id(),
        ]);

        $this->logAction('announcement.send', auth()->user(), "Sent announcement: {$request->title} → target: {$request->target}");

        return back()->with('success', 'Announcement saved.');
    }

    public function deleteAnnouncement($id) {
        Announcement::findOrFail($id)->delete();
        return back()->with('success', 'Announcement deleted.');
    }

    // ─── Feature 8: System Health ─────────────────────────────────────────────

    public function systemHealth() {
        $data = [
            'failed_jobs'    => DB::table('failed_jobs')->count(),
            'pending_jobs'   => DB::table('jobs')->count(),
            'total_tenants'  => Tenant::count(),
            'active_tenants' => Tenant::where('status', 'active')->count(),
            'total_orders'   => Order::count(),
            'total_users'    => User::where('is_super_admin', false)->count(),
            'disk_free'      => disk_free_space('/'),
            'disk_total'     => disk_total_space('/'),
            'memory_usage'   => memory_get_usage(true),
            'memory_peak'    => memory_get_peak_usage(true),
            'php_version'    => PHP_VERSION,
            'laravel_version'=> app()->version(),
            'uptime'         => @file_get_contents('/proc/uptime') ?: null,
        ];

        $recentLogs = [];
        $logFile = storage_path('logs/laravel.log');
        if (file_exists($logFile)) {
            $lines = array_filter(explode("\n", shell_exec("tail -n 200 " . escapeshellarg($logFile)) ?: ''));
            $recentLogs = array_values(array_slice(array_reverse(array_values($lines)), 0, 50));
        }

        $recentFailed = DB::table('failed_jobs')->latest('failed_at')->take(10)->get();

        return view('super-admin.system-health', compact('data', 'recentLogs', 'recentFailed'));
    }

    public function clearFailedJobs() {
        DB::table('failed_jobs')->truncate();
        $this->logAction('system.clear_failed_jobs', auth()->user(), 'Cleared all failed jobs');
        return back()->with('success', 'Failed jobs cleared.');
    }

    // ─── Feature 10: Super Admin Activity Log ────────────────────────────────

    public function activityLog(Request $request) {
        $query = ActivityLog::with('user')
            ->whereNull('tenant_id')
            ->latest();

        if ($request->filled('search')) {
            $query->where('description', 'like', '%' . $request->search . '%');
        }
        if ($request->filled('action')) {
            $query->where('action', 'like', $request->action . '%');
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->paginate(50)->withQueryString();
        return view('super-admin.activity-log', compact('logs'));
    }

    // ─── Private Helper ───────────────────────────────────────────────────────

    private function logAction(string $action, $subject, string $description): void {
        try {
            ActivityLog::create([
                'user_id'          => auth()->id(),
                'action'           => $action,
                'action_performed' => $action,
                'subject_type'     => is_object($subject) ? get_class($subject) : 'Unknown',
                'subject_id'       => is_object($subject) ? ($subject->id ?? 0) : 0,
                'description'      => $description,
                'ip_address'       => request()->ip(),
                'user_agent'       => request()->userAgent(),
                'tenant_id'        => null,
            ]);
        } catch (\Exception $e) {
            \Log::error('SuperAdmin log failed: ' . $e->getMessage());
        }
    }
}
