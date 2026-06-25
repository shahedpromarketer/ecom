@extends('super-admin.layout')
@section('page-title', 'Tenant: ' . $tenant->name)

@section('content')
<div class="flex items-center gap-4 mb-6">
    <a href="{{ route('super.tenants.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← Back to Tenants</a>
    <h2 class="text-xl font-bold text-gray-900">{{ $tenant->name }}</h2>
    <span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full
        {{ $tenant->status === 'active' ? 'bg-green-100 text-green-700' : ($tenant->status === 'suspended' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700') }}">
        {{ ucfirst($tenant->status) }}
    </span>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- Left Column --}}
    <div class="lg:col-span-2 space-y-6">

        {{-- Info Card --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="font-semibold text-gray-800 mb-4">Tenant Information</h3>
            <dl class="grid grid-cols-2 gap-4 text-sm">
                <div><dt class="text-gray-500">Shop Name</dt><dd class="font-medium text-gray-900 mt-0.5">{{ $tenant->name }}</dd></div>
                <div><dt class="text-gray-500">Email</dt><dd class="font-medium text-gray-900 mt-0.5">{{ $tenant->email }}</dd></div>
                <div><dt class="text-gray-500">Phone</dt><dd class="font-medium text-gray-900 mt-0.5">{{ $tenant->phone ?? '—' }}</dd></div>
                <div><dt class="text-gray-500">Owner</dt><dd class="font-medium text-gray-900 mt-0.5">{{ $tenant->owner?->name ?? '—' }}</dd></div>
                <div><dt class="text-gray-500">Plan</dt>
                    <dd class="mt-0.5">
                        <span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full
                            {{ $tenant->plan === 'enterprise' ? 'bg-purple-100 text-purple-700' : ($tenant->plan === 'pro' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-700') }}">
                            {{ ucfirst($tenant->plan) }}
                        </span>
                    </dd>
                </div>
                <div><dt class="text-gray-500">Plan Expires</dt><dd class="font-medium text-gray-900 mt-0.5">{{ $tenant->plan_expires_at?->format('d M Y') ?? 'N/A' }}</dd></div>
                <div><dt class="text-gray-500">Created</dt><dd class="font-medium text-gray-900 mt-0.5">{{ $tenant->created_at->format('d M Y') }}</dd></div>
                <div><dt class="text-gray-500">Days Remaining</dt>
                    <dd class="font-medium mt-0.5 {{ $tenant->plan_expires_at && $tenant->plan_expires_at->isPast() ? 'text-red-600' : 'text-green-600' }}">
                        {{ $tenant->plan_expires_at ? ($tenant->plan_expires_at->isPast() ? 'Expired' : $tenant->plan_expires_at->diffForHumans()) : '—' }}
                    </dd>
                </div>
            </dl>
        </div>

        {{-- Stats --}}
        <div class="grid grid-cols-3 gap-4">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 text-center">
                <p class="text-3xl font-bold text-gray-900">{{ $tenant->orders_count }}</p>
                <p class="text-xs text-gray-500 mt-1 uppercase tracking-wide">Total Orders</p>
                <p class="text-xs text-gray-400 mt-0.5">Max: {{ $tenant->max_orders }}</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 text-center">
                <p class="text-3xl font-bold text-gray-900">{{ $usersCount }}</p>
                <p class="text-xs text-gray-500 mt-1 uppercase tracking-wide">Team Members</p>
                <p class="text-xs text-gray-400 mt-0.5">Max: {{ $tenant->max_users }}</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 text-center">
                @php $pct = $tenant->max_orders > 0 ? round(($tenant->orders_count / $tenant->max_orders) * 100) : 0; @endphp
                <p class="text-3xl font-bold {{ $pct >= 90 ? 'text-red-600' : ($pct >= 70 ? 'text-yellow-600' : 'text-green-600') }}">{{ $pct }}%</p>
                <p class="text-xs text-gray-500 mt-1 uppercase tracking-wide">Order Usage</p>
                <div class="mt-2 w-full bg-gray-100 rounded-full h-1.5">
                    <div class="h-1.5 rounded-full {{ $pct >= 90 ? 'bg-red-500' : ($pct >= 70 ? 'bg-yellow-500' : 'bg-green-500') }}" style="width: {{ min($pct, 100) }}%"></div>
                </div>
            </div>
        </div>

        {{-- Recent Orders --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="font-semibold text-gray-800">Recent Orders</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Order ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($recentOrders as $order)
                        <tr>
                            <td class="px-6 py-3 font-mono text-xs text-gray-500">#{{ $order->id }}</td>
                            <td class="px-6 py-3">
                                <span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full bg-gray-100 text-gray-700">
                                    {{ ucfirst($order->status ?? '—') }}
                                </span>
                            </td>
                            <td class="px-6 py-3 text-gray-700">{{ number_format($order->total ?? 0) }} ৳</td>
                            <td class="px-6 py-3 text-gray-500 text-xs">{{ $order->created_at->format('d M Y') }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="px-6 py-6 text-center text-gray-400">No orders yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Users --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="font-semibold text-gray-800">Team Members</h3>
            </div>
            <div class="divide-y divide-gray-100">
                @forelse($tenant->users as $user)
                <div class="px-6 py-3 flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-700 font-bold text-xs">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-900">{{ $user->name }}</p>
                        <p class="text-xs text-gray-500">{{ $user->email }} · {{ ucfirst($user->role ?? 'user') }}</p>
                    </div>
                </div>
                @empty
                <div class="px-6 py-4 text-center text-gray-400 text-sm">No users found.</div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Right Column: Actions --}}
    <div class="space-y-4">

        {{-- Impersonate --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <h3 class="font-semibold text-gray-800 mb-3">Login As Tenant</h3>
            <p class="text-xs text-gray-500 mb-3">Enter this tenant's admin panel as their user.</p>
            <form method="POST" action="{{ route('super.impersonate', $tenant->id) }}">
                @csrf
                <button type="submit" onclick="return confirm('Login as this tenant\'s admin?')"
                        class="w-full py-2 px-4 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg transition">
                    Login As Admin
                </button>
            </form>
        </div>

        {{-- Plan --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <h3 class="font-semibold text-gray-800 mb-3">Change Plan</h3>
            <form method="POST" action="{{ route('super.tenants.plan', $tenant->id) }}" class="space-y-3">
                @csrf
                <select name="plan" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                    <option value="basic"      {{ $tenant->plan === 'basic'      ? 'selected' : '' }}>Basic</option>
                    <option value="pro"        {{ $tenant->plan === 'pro'        ? 'selected' : '' }}>Pro</option>
                    <option value="enterprise" {{ $tenant->plan === 'enterprise' ? 'selected' : '' }}>Enterprise</option>
                </select>
                <div class="flex gap-2 items-center">
                    <input type="number" name="plan_days" value="30" min="1"
                           class="flex-1 border border-gray-200 rounded-lg px-3 py-2 text-sm" placeholder="Days">
                    <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg transition">Set</button>
                </div>
            </form>
        </div>

        {{-- Feature 9: Custom Limits --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <h3 class="font-semibold text-gray-800 mb-1">Custom Limits</h3>
            <p class="text-xs text-gray-500 mb-3">Override default plan limits for this tenant.</p>
            <form method="POST" action="{{ route('super.tenants.limits', $tenant->id) }}" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Max Orders</label>
                    <input type="number" name="max_orders" value="{{ $tenant->max_orders }}" min="1"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Max Users</label>
                    <input type="number" name="max_users" value="{{ $tenant->max_users }}" min="1"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                </div>
                <button type="submit" class="w-full py-2 bg-gray-800 hover:bg-gray-900 text-white text-sm font-semibold rounded-lg transition">
                    Save Limits
                </button>
            </form>
        </div>

        {{-- Sync Orders --}}
        @if($wpSites->count() > 0)
        <div class="bg-white rounded-xl shadow-sm border border-blue-100 p-5">
            <h3 class="font-semibold text-gray-800 mb-1">Sync Orders</h3>
            <p class="text-xs text-gray-500 mb-3">Pull orders from WooCommerce for this tenant.</p>

            @if($wpSites->count() > 1)
            <div class="mb-3">
                <label class="block text-xs font-medium text-gray-500 mb-1">Select Site</label>
                <select id="sync-site-id" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                    @foreach($wpSites as $wpSite)
                    <option value="{{ $wpSite->id }}">{{ $wpSite->name }}</option>
                    @endforeach
                </select>
            </div>
            @else
            <input type="hidden" id="sync-site-id" value="{{ $wpSites->first()->id }}">
            <p class="text-xs text-blue-600 font-medium mb-3">{{ $wpSites->first()->name }}</p>
            @endif

            <p class="text-xs text-gray-500 mb-2">Select time range:</p>
            <div class="grid grid-cols-3 gap-2 mb-3">
                <button onclick="selectDays(1, this)" class="day-btn py-2 bg-gray-50 hover:bg-blue-50 text-gray-600 hover:text-blue-700 text-xs font-semibold rounded-lg border border-gray-200 hover:border-blue-300 transition">
                    Last 1 Day
                </button>
                <button onclick="selectDays(3, this)" class="day-btn py-2 bg-gray-50 hover:bg-blue-50 text-gray-600 hover:text-blue-700 text-xs font-semibold rounded-lg border border-gray-200 hover:border-blue-300 transition">
                    Last 3 Days
                </button>
                <button onclick="selectDays(7, this)" class="day-btn py-2 bg-gray-50 hover:bg-blue-50 text-gray-600 hover:text-blue-700 text-xs font-semibold rounded-lg border border-gray-200 hover:border-blue-300 transition">
                    Last 7 Days
                </button>
            </div>

            <button id="sync-submit-btn" onclick="triggerSync()" disabled
                class="w-full py-2 bg-blue-600 text-white text-sm font-semibold rounded-lg transition opacity-40 cursor-not-allowed">
                Sync Now
            </button>

            <div id="sync-result" class="hidden text-xs rounded-lg px-3 py-2 mt-3"></div>
        </div>

        <script>
        var selectedDays = null;

        function selectDays(days, el) {
            selectedDays = days;
            document.querySelectorAll('.day-btn').forEach(b => {
                b.className = 'day-btn py-2 bg-gray-50 hover:bg-blue-50 text-gray-600 hover:text-blue-700 text-xs font-semibold rounded-lg border border-gray-200 hover:border-blue-300 transition';
            });
            el.className = 'day-btn py-2 bg-blue-600 text-white text-xs font-semibold rounded-lg border border-blue-600 transition';

            const btn = document.getElementById('sync-submit-btn');
            btn.disabled = false;
            btn.className = 'w-full py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg transition cursor-pointer';
        }

        function triggerSync() {
            if (!selectedDays) return;

            const siteId = document.getElementById('sync-site-id').value;
            const result = document.getElementById('sync-result');
            const submitBtn = document.getElementById('sync-submit-btn');
            const dayBtns = document.querySelectorAll('.day-btn');

            submitBtn.disabled = true;
            submitBtn.textContent = 'Syncing...';
            submitBtn.className = 'w-full py-2 bg-blue-400 text-white text-sm font-semibold rounded-lg transition cursor-not-allowed';
            dayBtns.forEach(b => b.disabled = true);

            result.className = 'text-xs rounded-lg px-3 py-2 mt-3 bg-gray-50 text-gray-500';
            result.classList.remove('hidden');
            result.textContent = 'Connecting to WooCommerce...';

            fetch('{{ route("super.tenants.sync-orders", $tenant->id) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ days: selectedDays, site_id: siteId })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    result.className = 'text-xs rounded-lg px-3 py-2 mt-3 bg-green-50 text-green-700 border border-green-200';
                    result.textContent = '✓ ' + data.message;
                } else {
                    result.className = 'text-xs rounded-lg px-3 py-2 mt-3 bg-red-50 text-red-700 border border-red-200';
                    result.textContent = '✗ ' + data.message;
                }
            })
            .catch(() => {
                result.className = 'text-xs rounded-lg px-3 py-2 mt-3 bg-red-50 text-red-700 border border-red-200';
                result.textContent = '✗ Network error. Please try again.';
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Sync Now';
                submitBtn.className = 'w-full py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg transition cursor-pointer';
                dayBtns.forEach(b => b.disabled = false);
            });
        }
        </script>
        @endif

        {{-- Suspend / Activate --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <h3 class="font-semibold text-gray-800 mb-3">Status Control</h3>
            @if ($tenant->status === 'active')
                <form method="POST" action="{{ route('super.tenants.suspend', $tenant->id) }}">
                    @csrf
                    <button type="submit" class="w-full py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-lg transition">
                        Suspend Tenant
                    </button>
                </form>
            @else
                <form method="POST" action="{{ route('super.tenants.activate', $tenant->id) }}">
                    @csrf
                    <button type="submit" class="w-full py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold rounded-lg transition">
                        Activate Tenant
                    </button>
                </form>
            @endif
        </div>

        {{-- Delete --}}
        <div class="bg-white rounded-xl shadow-sm border border-red-100 p-5">
            <h3 class="font-semibold text-red-700 mb-1">Danger Zone</h3>
            <p class="text-xs text-gray-500 mb-3">This will permanently delete the tenant and all their users.</p>
            <form method="POST" action="{{ route('super.tenants.delete', $tenant->id) }}"
                  onsubmit="return confirm('PERMANENTLY delete {{ $tenant->name }} and all data?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="w-full py-2 bg-red-100 hover:bg-red-600 hover:text-white text-red-700 text-sm font-semibold rounded-lg transition">
                    Delete Tenant
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
