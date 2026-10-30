@extends('super-admin.layout')
@section('page-title', 'Dashboard')

@section('content')
{{-- Stats Grid --}}
<div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
    @foreach([
        ['label' => 'Total Tenants',  'value' => $stats['total'],     'color' => 'blue'],
        ['label' => 'Active',          'value' => $stats['active'],    'color' => 'green'],
        ['label' => 'Suspended',       'value' => $stats['suspended'], 'color' => 'red'],
        ['label' => 'Trial',           'value' => $stats['trial'],     'color' => 'yellow'],
        ['label' => 'Expired',         'value' => $stats['expired'],   'color' => 'gray'],
    ] as $stat)
    <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100">
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $stat['label'] }}</p>
        <p class="text-3xl font-bold text-gray-900 mt-1">{{ $stat['value'] }}</p>
    </div>
    @endforeach
</div>

{{-- Recent Tenants Table --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100">
    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
        <h3 class="font-semibold text-gray-800">Recent Tenants</h3>
        <a href="{{ route('super.tenants.index') }}" class="text-sm text-blue-600 hover:underline">View All</a>
    </div>
    <div class="overflow-x-auto">
        <table class="ep-stack min-w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Shop</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Plan</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Expires</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($tenants as $tenant)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 font-medium text-gray-900">{{ $tenant->name }}</td>
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
                    <td class="px-6 py-4 text-gray-500">
                        {{ $tenant->plan_expires_at ? $tenant->plan_expires_at->format('d M Y') : 'N/A' }}
                    </td>
                    <td class="px-6 py-4">
                        @if ($tenant->status === 'active')
                            <form method="POST" action="{{ route('super.tenants.suspend', $tenant->id) }}" class="inline">
                                @csrf
                                <button type="submit" class="text-xs text-red-600 hover:underline">Suspend</button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('super.tenants.activate', $tenant->id) }}" class="inline">
                                @csrf
                                <button type="submit" class="text-xs text-green-600 hover:underline">Activate</button>
                            </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-6 py-8 text-center text-gray-400">No tenants yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
