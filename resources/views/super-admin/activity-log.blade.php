@extends('super-admin.layout')
@section('page-title', 'Activity Log')

@section('content')
{{-- Filters --}}
<form method="GET" action="{{ route('super.activity-log') }}" class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-4 flex flex-wrap gap-3 items-end">
    <div class="flex-1 min-w-[200px]">
        <label class="block text-xs font-medium text-gray-500 mb-1">Search</label>
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search description..."
               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
    </div>
    <div class="w-44">
        <label class="block text-xs font-medium text-gray-500 mb-1">Action Type</label>
        <select name="action" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
            <option value="">All Actions</option>
            <option value="tenant."      {{ request('action') === 'tenant.'      ? 'selected' : '' }}>Tenant Actions</option>
            <option value="bulk."        {{ request('action') === 'bulk.'        ? 'selected' : '' }}>Bulk Actions</option>
            <option value="announcement" {{ request('action') === 'announcement' ? 'selected' : '' }}>Announcements</option>
            <option value="system."      {{ request('action') === 'system.'      ? 'selected' : '' }}>System Actions</option>
        </select>
    </div>
    <div class="w-36">
        <label class="block text-xs font-medium text-gray-500 mb-1">From Date</label>
        <input type="date" name="date_from" value="{{ request('date_from') }}"
               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
    </div>
    <div class="w-36">
        <label class="block text-xs font-medium text-gray-500 mb-1">To Date</label>
        <input type="date" name="date_to" value="{{ request('date_to') }}"
               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
    </div>
    <div class="flex gap-2">
        <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg transition">Filter</button>
        @if(request()->hasAny(['search','action','date_from','date_to']))
            <a href="{{ route('super.activity-log') }}" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold rounded-lg transition">Clear</a>
        @endif
    </div>
</form>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
        <h3 class="font-semibold text-gray-800">Super Admin Actions</h3>
        <span class="text-xs text-gray-400">{{ $logs->total() }} entries</span>
    </div>
    <div class="overflow-x-auto">
        <table class="ep-stack min-w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Time</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Admin</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">IP</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($logs as $log)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-3 text-xs text-gray-500 whitespace-nowrap">
                        {{ $log->created_at->format('d M Y') }}<br>
                        <span class="text-gray-400">{{ $log->created_at->format('H:i:s') }}</span>
                    </td>
                    <td class="px-6 py-3 text-sm text-gray-700">
                        {{ $log->user?->name ?? '—' }}
                    </td>
                    <td class="px-6 py-3">
                        @php
                            $color = match(true) {
                                str_contains($log->action, 'delete')      => 'bg-red-100 text-red-700',
                                str_contains($log->action, 'suspend')     => 'bg-orange-100 text-orange-700',
                                str_contains($log->action, 'impersonat')  => 'bg-indigo-100 text-indigo-700',
                                str_contains($log->action, 'create')      => 'bg-green-100 text-green-700',
                                str_contains($log->action, 'bulk')        => 'bg-yellow-100 text-yellow-700',
                                default                                   => 'bg-gray-100 text-gray-700',
                            };
                        @endphp
                        <span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full {{ $color }}">
                            {{ $log->action }}
                        </span>
                    </td>
                    <td class="px-6 py-3 text-sm text-gray-600 max-w-xs truncate" title="{{ $log->description }}">
                        {{ $log->description }}
                    </td>
                    <td class="px-6 py-3 text-xs text-gray-400 font-mono">
                        {{ $log->ip_address ?? '—' }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-10 text-center text-gray-400">No activity logs found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($logs->hasPages())
    <div class="px-6 py-4 border-t border-gray-100">
        {{ $logs->links() }}
    </div>
    @endif
</div>
@endsection
