@extends('layouts.admin')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')


@section('content')

{{-- ═══ Date Range Filter ═══ --}}
<div class="mb-6 flex flex-wrap items-center justify-end gap-3">
    {{-- Single Period Dropdown --}}
    <div x-data="periodDropdown()" class="relative" @click.outside="open = false">
        <button @click="open = !open"
                class="px-4 py-2 bg-primary hover:bg-primary/90 text-white text-sm font-semibold rounded-lg flex items-center gap-2 transition-colors shadow-sm min-w-[130px] justify-between"
                :class="loading ? 'opacity-75 cursor-wait' : ''">
            <span x-text="label"></span>
            <svg x-show="!loading" class="w-4 h-4 flex-shrink-0 transition-transform duration-200" :class="open ? 'rotate-180' : ''"
                 fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
            </svg>
            <svg x-show="loading" class="w-4 h-4 flex-shrink-0 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
            </svg>
        </button>

        <div x-show="open" x-cloak
             x-transition:enter="transition ease-out duration-100"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-75"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="absolute right-0 top-full mt-1.5 w-52 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-xl z-50 py-1 overflow-hidden">

            @foreach([
                'all'        => 'All Time',
                'today'      => 'Today',
                'yesterday'  => 'Yesterday',
                'last_7'     => 'Last 7 Days',
                'last_30'    => 'Last 30 Days',
                'this_month' => 'This Month',
                'last_month' => 'Last Month',
                'this_year'  => 'This Year',
                'last_year'  => 'Last Year',
            ] as $rKey => $rLabel)
            <button @click="goRange('{{ $rKey }}', '{{ $rLabel }}')"
                    class="w-full flex items-center justify-between px-4 py-2 text-xs transition-colors"
                    :class="range === '{{ $rKey }}' ? 'text-primary font-semibold bg-primary/5 dark:bg-primary/10' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'">
                <span>{{ $rLabel }}</span>
                <svg x-show="range === '{{ $rKey }}'" class="w-3.5 h-3.5 text-primary flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
            </button>
            @endforeach

            {{-- Custom Range --}}
            <div class="border-t border-gray-100 dark:border-gray-700 mt-1 pt-1">
                <button @click="showCustom = !showCustom"
                        class="w-full flex items-center justify-between px-4 py-2 text-xs transition-colors"
                        :class="range === 'custom' ? 'text-primary font-semibold bg-primary/5 dark:bg-primary/10' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'">
                    <div class="flex items-center gap-2">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <span>Custom Range</span>
                    </div>
                    <svg x-show="range === 'custom'" class="w-3.5 h-3.5 text-primary flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                </button>

                <div x-show="showCustom" x-cloak class="px-3 pb-3 pt-1 space-y-1.5">
                    <input type="date" x-model="customFrom"
                           class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-1 focus:ring-primary">
                    <input type="date" x-model="customTo"
                           class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-1 focus:ring-primary">
                    <button @click="applyCustom()"
                            class="w-full py-1.5 text-xs font-semibold bg-primary hover:bg-primary/90 text-white rounded-lg transition-colors">
                        Apply
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ═══ SECTION 1: 5 Summary Cards ═══ --}}
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-4 mb-6">

    {{-- Total Orders --}}
    <div class="rounded-2xl p-5 text-white" style="background: #1e3a5f;">
        <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 rounded-xl bg-white/15 flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007z"/>
                </svg>
            </div>
            <span class="text-xs font-medium bg-white/15 px-2 py-0.5 rounded-full">Orders</span>
        </div>
        <p id="summary-total-orders" class="text-3xl font-bold">{{ number_format($totalOrders) }}</p>
        <p class="text-xs text-white/70 mt-1">Total for period</p>
    </div>

    {{-- Total Revenue --}}
    <div class="rounded-2xl p-5 text-white" style="background: #0f5132;">
        <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 rounded-xl bg-white/15 flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <span class="text-xs font-medium bg-white/15 px-2 py-0.5 rounded-full">Revenue</span>
        </div>
        <p id="summary-revenue" class="text-3xl font-bold">৳{{ number_format($totalRevenue, 0) }}</p>
        <p class="text-xs text-white/70 mt-1">Delivered & completed</p>
    </div>

    {{-- Pending Orders --}}
    <div class="rounded-2xl p-5 text-white" style="background: #78350f;">
        <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 rounded-xl bg-white/15 flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <span class="text-xs font-medium bg-white/15 px-2 py-0.5 rounded-full">Pending</span>
        </div>
        <p id="summary-pending" class="text-3xl font-bold">{{ number_format($pendingOrders) }}</p>
        <p class="text-xs text-white/70 mt-1">Awaiting action</p>
    </div>

    {{-- In Courier --}}
    <div class="rounded-2xl p-5 text-white" style="background: #7c3aed;">
        <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 rounded-xl bg-white/15 flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12"/>
                </svg>
            </div>
            <span class="text-xs font-medium bg-white/15 px-2 py-0.5 rounded-full">Shipped</span>
        </div>
        <p id="summary-in-courier" class="text-3xl font-bold">{{ number_format($inCourier) }}</p>
        <p class="text-xs text-white/70 mt-1">In transit</p>
    </div>

    {{-- Conversion Rate --}}
    <div class="rounded-2xl p-5 text-white" style="background: #1f2937;">
        <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 rounded-xl bg-white/15 flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/>
                </svg>
            </div>
            <span class="text-xs font-medium bg-white/15 px-2 py-0.5 rounded-full">Rate</span>
        </div>
        <p id="summary-conversion" class="text-3xl font-bold">{{ $conversionRate }}%</p>
        <p class="text-xs text-white/70 mt-1">Completion rate</p>
    </div>

</div>

{{-- ═══ SECTION 1b: Order Status Summary Cards ═══ --}}
@php
$dashStatusCards = [
    ['key' => 'pending',        'label' => 'Pending',     'color' => '#F59E0B'],
    ['key' => 'confirmed',      'label' => 'Confirmed',   'color' => '#3B82F6'],
    ['key' => 'processing',     'label' => 'Processing',  'color' => '#6366F1'],
    ['key' => 'hold',           'label' => 'Hold',        'color' => '#F97316'],
    ['key' => 'in_courier',     'label' => 'In Courier',  'color' => '#06B6D4', 'status' => 'shipped'],
    ['key' => 'delivered',      'label' => 'Delivered',   'color' => '#10B981'],
    ['key' => 'completed',      'label' => 'Completed',   'color' => '#059669'],
    ['key' => 'cancelled',      'label' => 'Canceled',    'color' => '#EF4444'],
    ['key' => 'pending_return', 'label' => 'Pend. Return','color' => '#EC4899'],
    ['key' => 'returned',       'label' => 'Returned',    'color' => '#8B5CF6'],
];
@endphp

<div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-2.5 mb-6">
    {{-- Total card --}}
    <a href="{{ route('admin.orders.index') }}"
       class="bg-white dark:bg-gray-800 rounded-xl border border-border dark:border-gray-700 shadow-sm p-3.5 hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between mb-1.5">
            <span class="text-xs text-textsecondary dark:text-gray-400 font-medium">Total Orders</span>
            <span class="w-2 h-2 rounded-full bg-gray-400 flex-shrink-0"></span>
        </div>
        <p id="total-orders-count" class="text-2xl font-bold text-textprimary dark:text-gray-100">{{ number_format($periodTotal) }}</p>
        <p class="text-xs text-textsecondary dark:text-gray-500 mt-0.5"><span id="summary-range-label">{{ $rangeLabel }}</span> · <span id="return-ratio">{{ $returnRatio }}</span>% return</p>
    </a>

    @foreach($dashStatusCards as $card)
    @php $statusKey = $card['status'] ?? $card['key']; $stat = $ordersByStatus->get($statusKey); @endphp
    <a href="{{ route('admin.orders.index', ['status' => $statusKey]) }}"
       class="bg-white dark:bg-gray-800 rounded-xl border border-border dark:border-gray-700 shadow-sm p-3 hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between mb-1">
            <span class="text-[11px] text-textsecondary dark:text-gray-400 font-medium truncate leading-tight">{{ $card['label'] }}</span>
            <span class="w-2 h-2 rounded-full flex-shrink-0 ml-1" style="background:{{ $card['color'] }}"></span>
        </div>
        <p id="status-count-{{ $card['key'] }}" class="text-xl font-bold text-textprimary dark:text-gray-100">{{ number_format($stat?->count ?? 0) }}</p>
        <p id="status-revenue-{{ $card['key'] }}" class="text-[10px] text-textsecondary dark:text-gray-500 mt-0.5 truncate">৳{{ number_format($stat?->revenue ?? 0, 0) }}</p>
    </a>
    @endforeach
</div>

{{-- ═══ Period Comparison ═══ --}}
@if($comparison)
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">

    {{-- Orders comparison --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm p-5">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs text-textsecondary dark:text-gray-400 font-medium uppercase tracking-wide mb-1">Orders · {{ $rangeLabel }}</p>
                <p class="text-2xl font-bold text-textprimary dark:text-gray-100">{{ number_format($totalOrders) }}</p>
            </div>
            <div class="text-right">
                <p class="text-xs text-textsecondary dark:text-gray-400 mb-1">vs {{ $comparison['label'] }}: <span class="font-semibold text-textprimary dark:text-gray-300">{{ number_format($comparison['orders']) }}</span></p>
                @if($comparison['orders_diff'] !== null)
                @php $od = $comparison['orders_diff']; @endphp
                <span class="inline-flex items-center gap-0.5 text-sm font-bold {{ $od >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-500 dark:text-red-400' }}">
                    @if($od >= 0)
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7"/></svg>
                    @else
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                    @endif
                    {{ abs($od) }}%
                </span>
                @else
                <span class="text-xs text-textsecondary dark:text-gray-500">No prev. data</span>
                @endif
            </div>
        </div>
        @php $oMax = max($totalOrders, $comparison['orders'], 1); $oPct = round($totalOrders / $oMax * 100); $oPrevPct = round($comparison['orders'] / $oMax * 100); @endphp
        <div class="mt-3 space-y-1">
            <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-1.5">
                <div class="h-1.5 rounded-full bg-blue-500 transition-all" style="width:{{ $oPct }}%"></div>
            </div>
            <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-1.5">
                <div class="h-1.5 rounded-full bg-blue-200 dark:bg-blue-800 transition-all" style="width:{{ $oPrevPct }}%"></div>
            </div>
        </div>
    </div>

    {{-- Revenue comparison --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm p-5">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs text-textsecondary dark:text-gray-400 font-medium uppercase tracking-wide mb-1">Revenue · {{ $rangeLabel }}</p>
                <p class="text-2xl font-bold text-textprimary dark:text-gray-100">৳{{ number_format($totalRevenue, 0) }}</p>
            </div>
            <div class="text-right">
                <p class="text-xs text-textsecondary dark:text-gray-400 mb-1">vs {{ $comparison['label'] }}: <span class="font-semibold text-textprimary dark:text-gray-300">৳{{ number_format($comparison['revenue'], 0) }}</span></p>
                @if($comparison['revenue_diff'] !== null)
                @php $rd = $comparison['revenue_diff']; @endphp
                <span class="inline-flex items-center gap-0.5 text-sm font-bold {{ $rd >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-500 dark:text-red-400' }}">
                    @if($rd >= 0)
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7"/></svg>
                    @else
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                    @endif
                    {{ abs($rd) }}%
                </span>
                @else
                <span class="text-xs text-textsecondary dark:text-gray-500">No prev. data</span>
                @endif
            </div>
        </div>
        @php $rMax = max($totalRevenue, $comparison['revenue'], 1); $rPct = round($totalRevenue / $rMax * 100); $rPrevPct = round($comparison['revenue'] / $rMax * 100); @endphp
        <div class="mt-3 space-y-1">
            <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-1.5">
                <div class="h-1.5 rounded-full bg-green-500 transition-all" style="width:{{ $rPct }}%"></div>
            </div>
            <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-1.5">
                <div class="h-1.5 rounded-full bg-green-200 dark:bg-green-800 transition-all" style="width:{{ $rPrevPct }}%"></div>
            </div>
        </div>
    </div>

</div>
@endif

{{-- ═══ SECTION 2: Charts Row ═══ --}}
@php
$cardRanges = [
    'all'        => 'All Time',
    'today'      => 'Today',
    'yesterday'  => 'Yesterday',
    'last_7'     => 'Last 7 Days',
    'last_30'    => 'Last 30 Days',
    'this_month' => 'This Month',
    'last_month' => 'Last Month',
    'this_year'  => 'This Year',
    'last_year'  => 'Last Year',
    'custom'     => 'Custom Range',
];
$statusMeta = [
    'pending'        => ['label' => 'Pending',     'color' => '#f59e0b'],
    'confirmed'      => ['label' => 'Confirmed',   'color' => '#3b82f6'],
    'processing'     => ['label' => 'Processing',  'color' => '#8b5cf6'],
    'hold'           => ['label' => 'Hold',        'color' => '#6b7280'],
    'shipped'        => ['label' => 'In Courier',  'color' => '#06b6d4'],
    'delivered'      => ['label' => 'Delivered',   'color' => '#10b981'],
    'completed'      => ['label' => 'Completed',   'color' => '#059669'],
    'cancelled'      => ['label' => 'Cancelled',   'color' => '#ef4444'],
    'pending_return' => ['label' => 'Pend. Return','color' => '#ec4899'],
    'returned'       => ['label' => 'Returned',    'color' => '#f97316'],
];
@endphp
<div class="grid grid-cols-1 xl:grid-cols-5 gap-6 mb-6">

    {{-- Order Status Donut (3/5) --}}
    <div x-data="statusCardData()" x-init="init()"
         class="xl:col-span-3 bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm p-6">
        <div class="flex items-center justify-between mb-5">
            <h3 class="font-heading font-semibold text-textprimary dark:text-gray-100">Order Status</h3>

            <div class="relative" @click.outside="open = false">
                <button @click="open = !open"
                        class="px-3 py-1.5 bg-purple-600 hover:bg-purple-700 text-white text-xs font-semibold rounded-lg flex items-center gap-1 transition-colors">
                    <span x-text="rangeLabel"></span>
                    <svg class="w-3 h-3 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="open" x-cloak
                     x-transition:enter="transition ease-out duration-100"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     class="absolute right-0 top-full mt-1 w-48 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-lg z-50 py-1 overflow-hidden">
                    @foreach($cardRanges as $rKey => $rLabel)
                    <button @click="selectRange('{{ $rKey }}', '{{ $rLabel }}')"
                            class="w-full flex items-center justify-between px-3 py-2 text-xs hover:bg-purple-50 dark:hover:bg-purple-900/20 transition-colors"
                            :class="range === '{{ $rKey }}' ? 'text-purple-600 font-semibold' : 'text-gray-700 dark:text-gray-300'">
                        <span>{{ $rLabel }}</span>
                        <svg x-show="range === '{{ $rKey }}'" class="w-3.5 h-3.5 text-purple-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                    </button>
                    @endforeach
                    <div x-show="showCustom" x-cloak class="border-t border-gray-100 dark:border-gray-700 px-3 py-2 space-y-1.5">
                        <input type="date" x-model="customFrom"
                               class="w-full px-2 py-1 text-xs rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-1 focus:ring-purple-500">
                        <input type="date" x-model="customTo"
                               class="w-full px-2 py-1 text-xs rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-1 focus:ring-purple-500">
                        <button @click="applyCustom()"
                                class="w-full py-1 text-xs font-semibold bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition-colors">Apply</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex flex-col md:flex-row items-center gap-6">
            <div class="relative flex-shrink-0 w-44 h-44">
                <canvas id="statusChart"></canvas>
                <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                    <span x-text="total.toLocaleString()" class="text-2xl font-bold text-textprimary dark:text-gray-100">{{ number_format($periodTotal) }}</span>
                    <span class="text-xs text-textsecondary dark:text-gray-400">Total</span>
                </div>
            </div>
            <div class="flex-1 grid grid-cols-1 sm:grid-cols-2 gap-2 w-full">
                @foreach($statusMeta as $key => $meta)
                @php $count = $ordersByStatus->get($key)?->count ?? 0; @endphp
                <div class="flex items-center justify-between px-3 py-2 rounded-xl bg-gray-50 dark:bg-gray-700/50"
                     data-status-key="{{ $key }}">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full flex-shrink-0" style="background: {{ $meta['color'] }}"></span>
                        <span class="text-xs text-textsecondary dark:text-gray-400">{{ $meta['label'] }}</span>
                    </div>
                    <span class="text-xs font-semibold text-textprimary dark:text-gray-100 status-val">{{ $count }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Order Source Card (2/5) --}}
    @php
    $sourceColors = [
        'no_source'   => '#9CA3AF',
        'website'     => '#3B82F6',
        'wordpress'   => '#8B5CF6',
        'cartflow'    => '#EC4899',
        'manual'      => '#F59E0B',
        'mobile_call' => '#10B981',
        'whatsapp'    => '#22C55E',
        'facebook'    => '#1877F2',
    ];
    $sourceLabels = [
        'no_source'   => 'No Source',
        'website'     => 'Website',
        'wordpress'   => 'WordPress',
        'cartflow'    => 'CartFlow',
        'manual'      => 'Manual',
        'mobile_call' => 'Mobile Call',
        'whatsapp'    => 'WhatsApp',
        'facebook'    => 'Facebook',
    ];
    @endphp
    <div x-data="sourceCardData()" x-init="init()"
         class="xl:col-span-2 bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">

        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
            <div>
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
                    Order Source <span class="text-gray-400 cursor-help" title="Orders grouped by source">ⓘ</span>
                </h3>
                <p class="text-xs text-gray-400" x-text="subtitle"></p>
            </div>

            {{-- Range dropdown --}}
            <div class="relative" @click.outside="open = false">
                <button @click="open = !open"
                        class="px-3 py-1.5 bg-purple-600 hover:bg-purple-700 text-white text-xs font-semibold rounded-lg flex items-center gap-1 transition-colors">
                    <span x-text="rangeLabel"></span>
                    <svg class="w-3 h-3 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="open" x-cloak
                     x-transition:enter="transition ease-out duration-100"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     class="absolute right-0 top-full mt-1 w-48 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-lg z-50 py-1 overflow-hidden">
                    @foreach($cardRanges as $rKey => $rLabel)
                    <button @click="selectRange('{{ $rKey }}', '{{ $rLabel }}')"
                            class="w-full flex items-center justify-between px-3 py-2 text-xs hover:bg-purple-50 dark:hover:bg-purple-900/20 transition-colors"
                            :class="range === '{{ $rKey }}' ? 'text-purple-600 font-semibold' : 'text-gray-700 dark:text-gray-300'">
                        <span>{{ $rLabel }}</span>
                        <svg x-show="range === '{{ $rKey }}'" class="w-3.5 h-3.5 text-purple-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                    </button>
                    @endforeach
                    <div x-show="showCustom" x-cloak class="border-t border-gray-100 dark:border-gray-700 px-3 py-2 space-y-1.5">
                        <input type="date" x-model="customFrom"
                               class="w-full px-2 py-1 text-xs rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-1 focus:ring-purple-500">
                        <input type="date" x-model="customTo"
                               class="w-full px-2 py-1 text-xs rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-1 focus:ring-purple-500">
                        <button @click="applyCustom()"
                                class="w-full py-1 text-xs font-semibold bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition-colors">Apply</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="p-6">
            <div class="flex justify-center mb-6">
                <div class="relative w-48 h-48">
                    <canvas id="orderSourceChart"></canvas>
                    <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                        <span x-text="total" class="text-3xl font-bold text-gray-900 dark:text-white"></span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-2" id="source-breakdown">
                @foreach($orderSources as $src)
                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-3 border-l-4"
                     style="border-color: {{ $sourceColors[$src->source] ?? '#9CA3AF' }}">
                    <p class="text-xs font-medium text-gray-600 dark:text-gray-400">
                        {{ $sourceLabels[$src->source] ?? ucfirst(str_replace('_', ' ', $src->source)) }}
                    </p>
                    <p class="text-sm font-bold text-gray-900 dark:text-white mt-1">
                        {{ number_format($src->count) }}
                        <span class="text-xs font-normal text-gray-400">({{ number_format($src->revenue) }})</span>
                    </p>
                </div>
                @endforeach
                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-3 border-l-4 border-gray-900 dark:border-white">
                    <p class="text-xs font-medium text-gray-600 dark:text-gray-400">Total</p>
                    <p class="text-sm font-bold text-gray-900 dark:text-white mt-1">
                        {{ number_format($orderSources->sum('count')) }}
                        <span class="text-xs font-normal text-gray-400">({{ number_format($orderSources->sum('revenue')) }})</span>
                    </p>
                </div>
            </div>
        </div>

    </div>

</div>

{{-- ═══ SECTION 3: Bottom Row ═══ --}}
<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

    {{-- Top Products --}}
    <div class="xl:col-span-1 bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="font-heading font-semibold text-textprimary dark:text-gray-100">Top Products</h3>
            <a href="{{ route('admin.products.index') }}" class="text-xs text-accent hover:underline font-medium">View all</a>
        </div>
        @if($topProducts->isEmpty())
        <div class="px-6 py-10 text-center text-xs text-textsecondary dark:text-gray-400">No sales data for period</div>
        @else
        <div class="px-4 pt-4" style="height:200px;position:relative;">
            <canvas id="topProductsChart"></canvas>
        </div>
        <div class="divide-y divide-gray-100 dark:divide-gray-700 mt-2">
            @foreach($topProducts->take(5) as $i => $product)
            <div class="flex items-center gap-3 px-5 py-2.5">
                <span class="w-5 h-5 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-xs font-bold text-textsecondary dark:text-gray-400 flex-shrink-0">{{ $i + 1 }}</span>
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-medium text-textprimary dark:text-gray-100 truncate">{{ $product->product_name }}</p>
                    <p class="text-[10px] text-textsecondary dark:text-gray-400">{{ number_format($product->total_qty) }} units</p>
                </div>
                <span class="text-xs font-semibold text-textprimary dark:text-gray-100 flex-shrink-0">৳{{ number_format($product->total_revenue, 0) }}</span>
            </div>
            @endforeach
        </div>
        @endif
    </div>

    {{-- Recent Orders --}}
    <div class="xl:col-span-1 bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="font-heading font-semibold text-textprimary dark:text-gray-100">Recent Orders</h3>
            <a href="{{ route('admin.orders.index') }}" class="text-xs text-accent hover:underline font-medium">View all</a>
        </div>
        @if($recentOrders->isEmpty())
        <div class="px-6 py-10 text-center text-xs text-textsecondary dark:text-gray-400">No orders for period</div>
        @else
        <div class="divide-y divide-gray-100 dark:divide-gray-700">
            @foreach($recentOrders as $order)
            @php
            $sc = match($order->status) {
                'delivered','completed' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                'cancelled','returned'  => 'bg-red-100 text-danger dark:bg-red-900/30 dark:text-red-400',
                'processing','shipped'  => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                'confirmed'             => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400',
                default                 => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
            };
            @endphp
            <div class="flex items-center gap-3 px-5 py-3">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2">
                        <a href="{{ route('admin.orders.show', $order) }}" class="text-sm font-mono font-semibold text-textprimary dark:text-gray-100 hover:text-primary transition-colors truncate">{{ $order->order_number }}</a>
                    </div>
                    <p class="text-xs text-textsecondary dark:text-gray-400 truncate">{{ $order->user?->name ?? 'Guest' }}</p>
                </div>
                <div class="flex flex-col items-end gap-1 flex-shrink-0">
                    <span class="text-xs font-semibold text-textprimary dark:text-gray-100">৳{{ number_format($order->total, 0) }}</span>
                    <span class="px-2 py-0.5 text-xs font-medium rounded-full {{ $sc }}">{{ ucfirst($order->status) }}</span>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>

    {{-- Quick Stats --}}
    <div class="xl:col-span-1 bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm p-6">
        <h3 class="font-heading font-semibold text-textprimary dark:text-gray-100 mb-5">Quick Stats</h3>
        <div class="space-y-4">
            <div class="flex items-center justify-between py-3 border-b border-gray-100 dark:border-gray-700">
                <span class="text-sm text-textsecondary dark:text-gray-400">Total Customers</span>
                <span class="text-sm font-semibold text-textprimary dark:text-gray-100">{{ number_format($totalCustomers) }}</span>
            </div>
            <div class="flex items-center justify-between py-3 border-b border-gray-100 dark:border-gray-700">
                <span class="text-sm text-textsecondary dark:text-gray-400">Active Products</span>
                <span class="text-sm font-semibold text-textprimary dark:text-gray-100">{{ number_format($totalProducts) }}</span>
            </div>
            <div class="flex items-center justify-between py-3 border-b border-gray-100 dark:border-gray-700">
                <span class="text-sm text-textsecondary dark:text-gray-400">Low Stock Items</span>
                <span class="text-sm font-semibold {{ $lowStockCount > 0 ? 'text-danger' : 'text-textprimary dark:text-gray-100' }}">{{ $lowStockCount }}</span>
            </div>
            <div class="flex items-center justify-between py-3 border-b border-gray-100 dark:border-gray-700">
                <span class="text-sm text-textsecondary dark:text-gray-400">Avg Order Value</span>
                <span class="text-sm font-semibold text-textprimary dark:text-gray-100">৳{{ number_format($avgOrderValue, 0) }}</span>
            </div>
            <div class="flex items-center justify-between py-3 border-b border-gray-100 dark:border-gray-700">
                <span class="text-sm text-textsecondary dark:text-gray-400">Units Sold</span>
                <span class="text-sm font-semibold text-textprimary dark:text-gray-100">{{ number_format($totalUnits) }}</span>
            </div>
            <div class="flex items-center justify-between py-3 border-b border-gray-100 dark:border-gray-700">
                <span class="text-sm text-textsecondary dark:text-gray-400">Returns</span>
                <span class="text-sm font-semibold {{ $returnsCount > 0 ? 'text-danger' : 'text-textprimary dark:text-gray-100' }}">{{ $returnsCount }}</span>
            </div>
            <div class="flex items-center justify-between py-3">
                <div>
                    <span class="text-sm text-textsecondary dark:text-gray-400">Repeat Customers</span>
                    <p class="text-[10px] text-textsecondary dark:text-gray-500">Ordered more than once</p>
                </div>
                <span class="text-sm font-semibold {{ $repeatRate > 30 ? 'text-green-600 dark:text-green-400' : ($repeatRate > 0 ? 'text-blue-600 dark:text-blue-400' : 'text-textprimary dark:text-gray-100') }}">{{ $repeatRate }}%</span>
            </div>
        </div>
    </div>

</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// ── Period dropdown (top-level filter — AJAX, no page reload) ────────────────
function periodDropdown() {
    return {
        open:       false,
        showCustom: {{ request('range') === 'custom' ? 'true' : 'false' }},
        range:      '{{ request('range', 'today') }}',
        label:      '{{ $rangeLabel }}',
        customFrom: '{{ request('from', '') }}',
        customTo:   '{{ request('to', '') }}',
        loading:    false,

        goRange(r, lbl) {
            this.open      = false;
            this.range     = r;
            this.label     = lbl;
            this.fetch(r);
        },

        applyCustom() {
            if (!this.customFrom || !this.customTo) return;
            this.open  = false;
            this.range = 'custom';
            this.label = 'Custom Range';
            this.fetch('custom', this.customFrom, this.customTo);
        },

        fetch(range, from, to) {
            this.loading = true;
            let url = '{{ route('admin.dashboard.summaryData') }}?range=' + range;
            if (from && to) url += '&from=' + encodeURIComponent(from) + '&to=' + encodeURIComponent(to);
            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => r.json())
                .then(d => {
                    this.label   = d.rangeLabel;
                    this.loading = false;
                    this.updateDom(d);
                });
        },

        updateDom(d) {
            const set = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
            set('summary-total-orders', d.totalOrders.toLocaleString());
            set('summary-revenue',      '৳' + Math.round(d.totalRevenue).toLocaleString());
            set('summary-pending',      d.pendingOrders.toLocaleString());
            set('summary-in-courier',   d.inCourier.toLocaleString());
            set('summary-conversion',   d.conversionRate + '%');
            set('total-orders-count',   d.periodTotal.toLocaleString());
            set('summary-range-label',  d.rangeLabel);
            set('return-ratio',         d.returnRatio);
            Object.entries(d.cards).forEach(([status, info]) => {
                const cEl = document.getElementById('status-count-' + status);
                const rEl = document.getElementById('status-revenue-' + status);
                if (cEl) cEl.textContent = info.count.toLocaleString();
                if (rEl) rEl.textContent = '৳' + Math.round(info.revenue).toLocaleString();
            });
        },
    };
}

// ── Shared constants ──────────────────────────────────────────────────────────
const SOURCE_COLORS = @json($sourceColors);
const SOURCE_LABELS = @json($sourceLabels);
const STATUS_COLOR_MAP = {
    pending: '#f59e0b', confirmed: '#3b82f6', processing: '#8b5cf6',
    shipped: '#06b6d4', delivered: '#10b981', completed: '#059669',
    cancelled: '#ef4444', returned: '#f97316', hold: '#6b7280',
    pending_return: '#ec4899',
};
const STATUS_LABEL_MAP = {
    pending: 'Pending', confirmed: 'Confirmed', processing: 'Processing',
    shipped: 'In Courier', delivered: 'Delivered', completed: 'Completed',
    cancelled: 'Cancelled', returned: 'Returned', hold: 'Hold',
    pending_return: 'Pend. Return',
};

// Chart instances (set once in DOMContentLoaded, updated by Alpine components)
let statusChart = null;
let sourceChart = null;

function buildSubtitle(from, to) {
    return (!from && !to) ? 'All time' : (from === to ? from : `${from} to ${to}`);
}
function cardFetch(url, from, to) {
    if (from && to) url += `&from=${encodeURIComponent(from)}&to=${encodeURIComponent(to)}`;
    return fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } }).then(r => r.json());
}
function applyStatusChart(statuses) {
    const active = statuses.filter(s => s.count > 0);
    const empty  = active.length === 0;
    if (!statusChart) return;
    statusChart.data.labels                      = empty ? [] : active.map(s => STATUS_LABEL_MAP[s.status] ?? s.status);
    statusChart.data.datasets[0].data            = empty ? [1] : active.map(s => s.count);
    statusChart.data.datasets[0].backgroundColor = empty ? ['#e5e7eb'] : active.map(s => STATUS_COLOR_MAP[s.status] ?? '#9CA3AF');
    statusChart.options.plugins.tooltip.enabled  = !empty;
    statusChart.update();
}

// ── Order Status card component ───────────────────────────────────────────────
function statusCardData() {
    return {
        open:       false,
        range:      'this_month',
        rangeLabel: 'This Month',
        showCustom: false,
        customFrom: '',
        customTo:   '',
        total:      {{ $periodTotal }},
        loading:    false,

        init() {
            this.load('this_month');
        },

        selectRange(r, label) {
            this.range      = r;
            this.rangeLabel = label;
            this.showCustom = (r === 'custom');
            if (r !== 'custom') { this.open = false; this.load(r); }
        },

        applyCustom() { this.open = false; this.load('custom', this.customFrom, this.customTo); },

        load(range, from, to) {
            this.loading = true;
            let url = '{{ route('admin.dashboard.orderStatus') }}?range=' + range;
            cardFetch(url, from, to).then(data => {
                this.total   = data.total;
                this.loading = false;
                applyStatusChart(data.statuses);
                this.updateLegend(data.statuses);
                this.updateCards(data.cards, data.total, data.return_ratio);
            });
        },

        updateLegend(statuses) {
            document.querySelectorAll('[data-status-key]').forEach(el => {
                const item = statuses.find(x => x.status === el.dataset.statusKey);
                const val  = el.querySelector('.status-val');
                if (val) val.textContent = (item ? item.count : 0).toLocaleString();
            });
        },

        updateCards(cards, total, returnRatio) {
            const totalEl = document.getElementById('total-orders-count');
            if (totalEl) totalEl.textContent = total.toLocaleString();
            const ratioEl = document.getElementById('return-ratio');
            if (ratioEl) ratioEl.textContent = returnRatio;
            Object.entries(cards).forEach(([status, info]) => {
                const cEl = document.getElementById('status-count-' + status);
                const rEl = document.getElementById('status-revenue-' + status);
                if (cEl) cEl.textContent = info.count.toLocaleString();
                if (rEl) rEl.textContent = '৳' + Math.round(info.revenue).toLocaleString();
            });
        },
    };
}

// ── Order Source card component ───────────────────────────────────────────────
function sourceCardData() {
    return {
        open:        false,
        range:       'this_month',
        rangeLabel:  'This Month',
        showCustom:  false,
        customFrom:  '',
        customTo:    '',
        subtitle:    '',
        total:       0,
        loading:     false,

        init() { this.load('this_month'); },

        selectRange(r, label) {
            this.range      = r;
            this.rangeLabel = label;
            this.showCustom = (r === 'custom');
            if (r !== 'custom') { this.open = false; this.load(r); }
        },

        applyCustom() { this.open = false; this.load('custom', this.customFrom, this.customTo); },

        load(range, from, to) {
            this.loading = true;
            let url = '{{ route('admin.dashboard.orderSource') }}?range=' + range;
            cardFetch(url, from, to).then(data => {
                this.subtitle = buildSubtitle(data.from, data.to);
                this.total    = data.total;
                this.loading  = false;
                this.updateChart(data.sources);
                this.updateBreakdown(data.sources);
            });
        },

        updateChart(sources) {
            const labels = sources.map(s => SOURCE_LABELS[s.source] ?? s.source.replace(/_/g, ' '));
            const counts = sources.map(s => s.count);
            const colors = sources.map(s => SOURCE_COLORS[s.source] ?? '#9CA3AF');
            const empty  = counts.every(v => v === 0);
            const apply  = () => {
                if (!sourceChart) return;
                sourceChart.data.labels                       = labels;
                sourceChart.data.datasets[0].data             = empty ? [1] : counts;
                sourceChart.data.datasets[0].backgroundColor = empty ? ['#e5e7eb'] : colors;
                sourceChart.options.plugins.tooltip.enabled  = !empty;
                sourceChart.update();
            };
            sourceChart ? apply() : setTimeout(apply, 100);
        },

        updateBreakdown(sources) {
            const grid = document.getElementById('source-breakdown');
            if (!grid) return;
            let html = '', totalRev = 0, totalCnt = 0;
            sources.forEach(s => {
                const color = SOURCE_COLORS[s.source] ?? '#9CA3AF';
                const label = SOURCE_LABELS[s.source] ?? s.source.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
                const rev   = parseFloat(s.revenue) || 0;
                totalCnt += s.count; totalRev += rev;
                html += `<div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-3 border-l-4" style="border-color:${color}">
                    <p class="text-xs font-medium text-gray-600 dark:text-gray-400">${label}</p>
                    <p class="text-sm font-bold text-gray-900 dark:text-white mt-1">${s.count.toLocaleString()}
                        <span class="text-xs font-normal text-gray-400">(${Math.round(rev).toLocaleString()})</span>
                    </p></div>`;
            });
            html += `<div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-3 border-l-4 border-gray-900 dark:border-white">
                <p class="text-xs font-medium text-gray-600 dark:text-gray-400">Total</p>
                <p class="text-sm font-bold text-gray-900 dark:text-white mt-1">${totalCnt.toLocaleString()}
                    <span class="text-xs font-normal text-gray-400">(${Math.round(totalRev).toLocaleString()})</span>
                </p></div>`;
            grid.innerHTML = html;
        },
    };
}

// ── Initial chart render (SSR data — guaranteed same as status cards) ─────────
document.addEventListener('DOMContentLoaded', function () {
    // Use $ordersByStatus->values() so chart segments always sum to $periodTotal
    const initStatuses = @json($ordersByStatus->values()->map(fn($s) => ['status' => $s->status, 'count' => (int)$s->count, 'revenue' => (float)$s->revenue]));
    const active       = initStatuses.filter(s => s.count > 0);
    const statusEmpty  = active.length === 0;
    statusChart = new Chart(document.getElementById('statusChart').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: statusEmpty ? [] : active.map(s => STATUS_LABEL_MAP[s.status] ?? s.status),
            datasets: [{
                data:            statusEmpty ? [1] : active.map(s => s.count),
                backgroundColor: statusEmpty ? ['#e5e7eb'] : active.map(s => STATUS_COLOR_MAP[s.status] ?? '#9CA3AF'),
                borderWidth: 0, hoverOffset: 4,
            }],
        },
        options: { cutout: '72%', plugins: { legend: { display: false }, tooltip: { enabled: !statusEmpty } } },
    });

    // Top Products horizontal bar chart
    const topProdCtx = document.getElementById('topProductsChart');
    if (topProdCtx) {
        const topProdData = @json($topProducts->map(fn($p) => ['name' => \Illuminate\Support\Str::limit($p->product_name, 22), 'revenue' => (float)$p->total_revenue]));
        const barColors = ['#3B82F6','#8B5CF6','#10B981','#F59E0B','#EF4444','#06B6D4','#EC4899','#F97316'];
        new Chart(topProdCtx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: topProdData.map(p => p.name),
                datasets: [{
                    data: topProdData.map(p => p.revenue),
                    backgroundColor: topProdData.map((_, i) => barColors[i % barColors.length]),
                    borderRadius: 4,
                }],
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: ctx => '৳' + Math.round(ctx.raw).toLocaleString() } },
                },
                scales: {
                    x: {
                        grid: { color: 'rgba(0,0,0,0.05)' },
                        ticks: { font: { size: 9 }, callback: v => v >= 1000 ? (v/1000).toFixed(0)+'k' : v },
                    },
                    y: { ticks: { font: { size: 9 } } },
                },
            },
        });
    }

    const srcCtx = document.getElementById('orderSourceChart');
    if (!srcCtx) return;
    const initLabels = {!! json_encode($orderSources->map(fn($s) => $sourceLabels[$s->source] ?? ucfirst(str_replace('_', ' ', $s->source)))) !!};
    const initData   = {!! json_encode($orderSources->pluck('count')) !!};
    const initColors = {!! json_encode($orderSources->map(fn($s) => $sourceColors[$s->source] ?? '#9CA3AF')) !!};
    const srcEmpty   = initData.every(v => v === 0);
    sourceChart = new Chart(srcCtx.getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: initLabels,
            datasets: [{ data: srcEmpty ? [1] : initData, backgroundColor: srcEmpty ? ['#e5e7eb'] : initColors, borderWidth: 0, hoverOffset: 4 }],
        },
        options: { cutout: '72%', plugins: { legend: { display: false }, tooltip: { enabled: !srcEmpty } } },
    });
});
</script>
@endpush
