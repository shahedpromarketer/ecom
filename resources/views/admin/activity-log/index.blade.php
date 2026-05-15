@extends('layouts.admin')

@section('page-title', 'Activity Log')

@section('breadcrumb')
    <span class="text-textsecondary">/</span>
    <span class="text-textprimary font-medium">Activity Log</span>
@endsection

@section('content')

{{-- ── Page Header ───────────────────────────────────────────────────────── --}}
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-xl font-bold text-textprimary dark:text-gray-100">Activity Log</h1>
        <p class="text-sm text-textsecondary dark:text-gray-400 mt-0.5">Track all admin actions across the system</p>
    </div>
</div>

{{-- ── Filters ───────────────────────────────────────────────────────────── --}}
<form method="GET" action="{{ route('admin.activity-log.index') }}"
      class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-4 shadow-sm mb-6">

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-3">

        {{-- Search --}}
        <div class="xl:col-span-2">
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Search description or action…"
                   class="w-full px-3 py-2 text-sm rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 text-textprimary dark:text-gray-100 placeholder-textsecondary dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all">
        </div>

        {{-- Action type --}}
        <select name="action_type"
                class="px-3 py-2 text-sm rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all">
            <option value="">All Actions</option>
            <option value="order."   {{ request('action_type') === 'order.'    ? 'selected' : '' }}>Orders</option>
            <option value="product." {{ request('action_type') === 'product.'  ? 'selected' : '' }}>Products</option>
            <option value="return."  {{ request('action_type') === 'return.'   ? 'selected' : '' }}>Returns</option>
            <option value="user."    {{ request('action_type') === 'user.'     ? 'selected' : '' }}>Users</option>
            <option value="settings."{{ request('action_type') === 'settings.' ? 'selected' : '' }}>Settings</option>
        </select>

        {{-- User --}}
        <select name="user_id"
                class="px-3 py-2 text-sm rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all">
            <option value="">All Users</option>
            @foreach($admins as $admin)
                <option value="{{ $admin->id }}" {{ request('user_id') == $admin->id ? 'selected' : '' }}>
                    {{ $admin->name }}
                </option>
            @endforeach
        </select>

        {{-- Date from --}}
        <input type="date" name="date_from" value="{{ request('date_from') }}"
               class="px-3 py-2 text-sm rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all">

        {{-- Date to --}}
        <input type="date" name="date_to" value="{{ request('date_to') }}"
               class="px-3 py-2 text-sm rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all">
    </div>

    <div class="flex items-center gap-2 mt-3">
        <button type="submit"
                class="px-5 py-2 text-sm font-semibold bg-primary hover:bg-primary/90 text-white rounded-xl transition-colors">
            Apply Filters
        </button>
        <a href="{{ route('admin.activity-log.index') }}"
           class="px-4 py-2 text-sm font-medium border border-gray-200 dark:border-gray-600 text-textsecondary dark:text-gray-400 hover:text-textprimary dark:hover:text-gray-200 rounded-xl transition-colors">
            Clear
        </a>
    </div>

</form>

{{-- ── Log Table ─────────────────────────────────────────────────────────── --}}
<div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">

    {{-- Showing entries --}}
    @if($logs->total() > 0)
    <div class="px-4 py-2.5 text-xs text-textsecondary dark:text-gray-400 border-b border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-700/20">
        Showing {{ $logs->firstItem() }} to {{ $logs->lastItem() }} of {{ number_format($logs->total()) }} entries
        @if(request()->hasAny(['search', 'action_type', 'user_id', 'date_from', 'date_to']))
            <span class="text-primary dark:text-blue-400">(filtered)</span>
        @endif
    </div>
    @endif

    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider" style="min-width:160px">Time</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider" style="min-width:180px">Action</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider" style="min-width:220px">Description</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider" style="min-width:150px">User</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider" style="min-width:140px">Subject</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider" style="min-width:120px">Properties</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse($logs as $log)
                @php
                    $prefix = explode('.', $log->action)[0] ?? '';
                    $badgeCls = match($prefix) {
                        'order'    => 'text-blue-700 bg-blue-50 dark:text-blue-300 dark:bg-blue-900/30',
                        'product'  => 'text-purple-700 bg-purple-50 dark:text-purple-300 dark:bg-purple-900/30',
                        'return'   => 'text-orange-700 bg-orange-50 dark:text-orange-300 dark:bg-orange-900/30',
                        'user'     => 'text-teal-700 bg-teal-50 dark:text-teal-300 dark:bg-teal-900/30',
                        'settings' => 'text-indigo-700 bg-indigo-50 dark:text-indigo-300 dark:bg-indigo-900/30',
                        default    => 'text-gray-600 bg-gray-100 dark:text-gray-400 dark:bg-gray-700',
                    };
                    $actionLabel = ucwords(str_replace('.', ' › ', $log->action));
                    $subjectShort = $log->subject_type ? class_basename($log->subject_type) : null;
                    $hasProperties = !empty($log->properties);
                @endphp
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">

                    {{-- Time --}}
                    <td class="px-4 py-3 align-top">
                        <p class="text-xs font-medium text-textprimary dark:text-gray-200 whitespace-nowrap">
                            {{ $log->created_at->format('M d, Y H:i') }}
                        </p>
                        <p class="text-[11px] text-textsecondary dark:text-gray-500 mt-0.5 whitespace-nowrap">
                            {{ $log->created_at->diffForHumans() }}
                        </p>
                    </td>

                    {{-- Action badge --}}
                    <td class="px-4 py-3 align-top">
                        <span class="inline-block px-2 py-1 rounded-lg text-[11px] font-semibold whitespace-nowrap {{ $badgeCls }}">
                            {{ $actionLabel }}
                        </span>
                    </td>

                    {{-- Description --}}
                    <td class="px-4 py-3 align-top">
                        <p class="text-xs text-textprimary dark:text-gray-300 leading-relaxed">
                            {{ $log->description ?: '—' }}
                        </p>
                    </td>

                    {{-- User --}}
                    <td class="px-4 py-3 align-top">
                        <p class="text-xs font-medium text-textprimary dark:text-gray-200">
                            {{ $log->user?->name ?? 'System' }}
                        </p>
                        @if($log->ip_address)
                        <p class="text-[11px] text-textsecondary dark:text-gray-500 mt-0.5 font-mono">
                            {{ $log->ip_address }}
                        </p>
                        @endif
                    </td>

                    {{-- Subject --}}
                    <td class="px-4 py-3 align-top">
                        @if($subjectShort && $log->subject_id)
                            <p class="text-xs text-textprimary dark:text-gray-300">
                                {{ $subjectShort }} <span class="text-textsecondary dark:text-gray-500">#{{ $log->subject_id }}</span>
                            </p>
                        @else
                            <span class="text-textsecondary dark:text-gray-600">—</span>
                        @endif
                    </td>

                    {{-- Properties --}}
                    <td class="px-4 py-3 align-top">
                        @if($hasProperties)
                            <details class="group">
                                <summary class="text-xs text-primary dark:text-blue-400 hover:text-primary/80 dark:hover:text-blue-300 cursor-pointer select-none list-none flex items-center gap-1 font-medium transition-colors">
                                    <svg class="w-3 h-3 transition-transform group-open:rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                                    </svg>
                                    View
                                </summary>
                                <pre class="mt-1.5 text-xs bg-gray-100 dark:bg-gray-900 rounded p-2 max-h-32 overflow-auto text-gray-700 dark:text-gray-300 leading-relaxed">{{ json_encode($log->properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                            </details>
                        @else
                            <span class="text-textsecondary dark:text-gray-600">—</span>
                        @endif
                    </td>

                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-16 text-center">
                        <svg class="w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z"/>
                        </svg>
                        <p class="text-sm font-medium text-textsecondary dark:text-gray-400">No activity found</p>
                        <p class="text-xs text-textsecondary dark:text-gray-500 mt-1">Try adjusting your filters to see more results.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination footer --}}
    <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700 flex flex-wrap items-center justify-between gap-3">
        <p class="text-xs text-textsecondary dark:text-gray-400">
            @if($logs->total() > 0)
                Showing {{ $logs->firstItem() }} to {{ $logs->lastItem() }} of {{ number_format($logs->total()) }} entries
            @else
                No entries found
            @endif
        </p>
        @if($logs->hasPages())
            {{ $logs->links() }}
        @endif
    </div>

</div>

@endsection
