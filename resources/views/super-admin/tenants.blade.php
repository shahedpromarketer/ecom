@extends('super-admin.layout')
@section('page-title', 'All Tenants')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h2 class="text-xl font-bold text-gray-900">Tenants</h2>
    <a href="{{ route('super.tenants.create') }}"
       class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg transition">
        + Add Tenant
    </a>
</div>

{{-- Feature 5: Search & Filter --}}
<form method="GET" action="{{ route('super.tenants.index') }}" class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-4 flex flex-wrap gap-3 items-end">
    <div class="flex-1 min-w-[200px]">
        <label class="block text-xs font-medium text-gray-500 mb-1">Search</label>
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Shop name or email..."
               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
    </div>
    <div class="w-36">
        <label class="block text-xs font-medium text-gray-500 mb-1">Plan</label>
        <select name="plan" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">All Plans</option>
            <option value="basic"      {{ request('plan') === 'basic'      ? 'selected' : '' }}>Basic</option>
            <option value="pro"        {{ request('plan') === 'pro'        ? 'selected' : '' }}>Pro</option>
            <option value="enterprise" {{ request('plan') === 'enterprise' ? 'selected' : '' }}>Enterprise</option>
        </select>
    </div>
    <div class="w-36">
        <label class="block text-xs font-medium text-gray-500 mb-1">Status</label>
        <select name="status" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">All Status</option>
            <option value="active"    {{ request('status') === 'active'    ? 'selected' : '' }}>Active</option>
            <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Suspended</option>
            <option value="expired"   {{ request('status') === 'expired'   ? 'selected' : '' }}>Expired</option>
        </select>
    </div>
    <div class="flex gap-2">
        <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg transition">Filter</button>
        @if(request()->hasAny(['search','plan','status']))
            <a href="{{ route('super.tenants.index') }}" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold rounded-lg transition">Clear</a>
        @endif
    </div>
</form>

{{-- Feature 6: Bulk Actions --}}
<form method="POST" action="{{ route('super.tenants.bulk') }}" id="bulkForm">
    @csrf
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="flex items-center gap-3 px-4 py-3 border-b border-gray-100 bg-gray-50">
            <input type="checkbox" id="selectAll" class="rounded border-gray-300 text-blue-600" onclick="toggleAll(this)">
            <label for="selectAll" class="text-xs font-medium text-gray-500">Select All</label>
            <select name="bulk_action" class="ml-4 border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">Bulk Action</option>
                <option value="activate">Activate</option>
                <option value="suspend">Suspend</option>
                <option value="extend_30">Extend 30 Days</option>
                <option value="extend_365">Extend 1 Year</option>
            </select>
            <button type="submit" onclick="return confirmBulk()"
                    class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-lg transition">
                Apply
            </button>
            <span class="ml-auto text-xs text-gray-400">{{ $tenants->total() }} total tenants</span>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="px-4 py-3 w-8"></th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Shop Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Plan</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Expires</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Orders</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($tenants as $tenant)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-4">
                            <input type="checkbox" name="tenant_ids[]" value="{{ $tenant->id }}" class="tenant-cb rounded border-gray-300 text-blue-600">
                        </td>
                        <td class="px-6 py-4 text-gray-400 font-mono text-xs">{{ $tenant->id }}</td>
                        <td class="px-6 py-4 font-medium text-gray-900">
                            <a href="{{ route('super.tenants.show', $tenant->id) }}" class="hover:text-blue-600 hover:underline">
                                {{ $tenant->name }}
                            </a>
                        </td>
                        <td class="px-6 py-4 text-gray-500">{{ $tenant->email }}</td>
                        <td class="px-6 py-4">
                            <span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full
                                {{ $tenant->plan === 'enterprise' ? 'bg-purple-100 text-purple-700' : ($tenant->plan === 'pro' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-700') }}">
                                {{ ucfirst($tenant->plan) }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full
                                {{ $tenant->status === 'active' ? 'bg-green-100 text-green-700' : ($tenant->status === 'suspended' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700') }}">
                                {{ ucfirst($tenant->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-gray-500 text-xs">
                            {{ $tenant->plan_expires_at ? $tenant->plan_expires_at->format('d M Y') : 'N/A' }}
                        </td>
                        <td class="px-6 py-4 text-gray-700">{{ $tenant->orders_count ?? 0 }}</td>
                        <td class="px-6 py-4">
                            <div class="flex flex-wrap gap-2 items-center">
                                <a href="{{ route('super.tenants.show', $tenant->id) }}"
                                   class="text-xs px-2 py-1 bg-gray-100 text-gray-700 rounded hover:bg-gray-200 transition">Detail</a>

                                <form method="POST" action="{{ route('super.impersonate', $tenant->id) }}">
                                    @csrf
                                    <button type="submit" class="text-xs px-2 py-1 bg-indigo-100 text-indigo-700 rounded hover:bg-indigo-200 transition"
                                            onclick="return confirm('Login as this tenant\'s admin?')">Login As</button>
                                </form>

                                @if ($tenant->status === 'active')
                                    <form method="POST" action="{{ route('super.tenants.suspend', $tenant->id) }}">
                                        @csrf
                                        <button type="submit" class="text-xs px-2 py-1 bg-red-100 text-red-700 rounded hover:bg-red-200 transition">Suspend</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('super.tenants.activate', $tenant->id) }}">
                                        @csrf
                                        <button type="submit" class="text-xs px-2 py-1 bg-green-100 text-green-700 rounded hover:bg-green-200 transition">Activate</button>
                                    </form>
                                @endif

                                <form method="POST" action="{{ route('super.tenants.plan', $tenant->id) }}" class="flex gap-1 items-center">
                                    @csrf
                                    <select name="plan" class="text-xs border rounded px-1 py-1">
                                        <option value="basic"      {{ $tenant->plan === 'basic'      ? 'selected' : '' }}>Basic</option>
                                        <option value="pro"        {{ $tenant->plan === 'pro'        ? 'selected' : '' }}>Pro</option>
                                        <option value="enterprise" {{ $tenant->plan === 'enterprise' ? 'selected' : '' }}>Enterprise</option>
                                    </select>
                                    <input type="number" name="plan_days" value="30" min="1"
                                        class="text-xs border rounded px-1 py-1 w-14" placeholder="days">
                                    <button type="submit" class="text-xs px-2 py-1 bg-blue-100 text-blue-700 rounded hover:bg-blue-200 transition">Set</button>
                                </form>

                                <form method="POST" action="{{ route('super.tenants.delete', $tenant->id) }}"
                                    onsubmit="return confirm('Delete this tenant and all users?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs px-2 py-1 bg-gray-100 text-gray-600 rounded hover:bg-red-100 hover:text-red-700 transition">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="9" class="px-6 py-8 text-center text-gray-400">No tenants found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($tenants->hasPages())
        <div class="px-6 py-4 border-t border-gray-100">
            {{ $tenants->links() }}
        </div>
        @endif
    </div>
</form>

<script>
function toggleAll(source) {
    document.querySelectorAll('.tenant-cb').forEach(cb => cb.checked = source.checked);
}
function confirmBulk() {
    const checked = document.querySelectorAll('.tenant-cb:checked');
    const action  = document.querySelector('[name=bulk_action]').value;
    if (!action)         { alert('Please select a bulk action.'); return false; }
    if (!checked.length) { alert('Please select at least one tenant.'); return false; }
    return confirm(`Apply "${action}" to ${checked.length} tenant(s)?`);
}
</script>
@endsection
