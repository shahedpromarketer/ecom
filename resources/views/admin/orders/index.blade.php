@extends('layouts.admin')

@section('title', 'All Orders')
@section('page-title', 'Orders')

@section('breadcrumb')
    <span class="text-textsecondary">/</span>
    <span class="text-textprimary font-medium">All Orders</span>
@endsection

@section('content')

@if(session('success'))
    <div class="mb-5 flex items-center gap-3 p-3.5 rounded-xl bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800">
        <svg class="w-4 h-4 text-green-600 dark:text-green-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/></svg>
        <p class="text-sm text-green-700 dark:text-green-400">{{ session('success') }}</p>
    </div>
@endif
@if(session('error'))
    <div class="mb-5 flex items-center gap-3 p-3.5 rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800">
        <svg class="w-4 h-4 text-red-600 dark:text-red-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd"/></svg>
        <p class="text-sm text-red-700 dark:text-red-400">{{ session('error') }}</p>
    </div>
@endif

{{-- ── Status Summary Cards ─────────────────────────────────────────────── --}}
@php
    $statusCards = [
        ['key' => 'pending',                    'label' => 'Pending',               'color' => '#F59E0B'],
        ['key' => 'confirmed',                  'label' => 'Confirmed',             'color' => '#3B82F6'],
        ['key' => 'failed',                     'label' => 'Failed',                'color' => '#DC2626'],
        ['key' => 'processing',                 'label' => 'Processing',            'color' => '#6366F1'],
        ['key' => 'hold',                       'label' => 'Hold',                  'color' => '#F97316'],
        ['key' => 'hold_followup',              'label' => 'Hold Followup',         'color' => '#FB923C'],
        ['key' => 'in_courier',                 'label' => 'In Courier',            'color' => '#06B6D4'],
        ['key' => 'delivered',                  'label' => 'Delivered',             'color' => '#10B981'],
        ['key' => 'completed',                  'label' => 'Completed',             'color' => '#059669'],
        ['key' => 'cancelled',                  'label' => 'Canceled',              'color' => '#EF4444'],
        ['key' => 'pending_return',             'label' => 'Pend. Return',          'color' => '#EC4899'],
        ['key' => 'returned',                   'label' => 'Returned',              'color' => '#8B5CF6'],
        ['key' => 'damage',                     'label' => 'Damage',                'color' => '#7F1D1D'],
        ['key' => 'hand_delivery',              'label' => 'Hand Delivery',         'color' => '#0D9488'],
        ['key' => 'hand_delivery_completed',    'label' => 'HD Completed',          'color' => '#047857'],
        ['key' => 'return_collection',          'label' => 'Return Collection',     'color' => '#BE185D'],
        ['key' => 'courier_payment_validate',   'label' => 'Courier Pmt.',          'color' => '#1D4ED8'],
        ['key' => 'others',                     'label' => 'Others',                'color' => '#6B7280'],
    ];
@endphp

<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 xl:grid-cols-6 gap-2.5 mb-5">
    {{-- Total card --}}
    <a href="{{ route('admin.orders.index') }}"
       class="bg-white dark:bg-gray-800 rounded-xl border border-border dark:border-gray-700 shadow-sm p-3.5 hover:shadow-md transition-shadow group">
        <div class="flex items-center justify-between mb-1.5">
            <span class="text-xs text-textsecondary dark:text-gray-400 font-medium">Total Orders</span>
            <span class="w-2 h-2 rounded-full bg-gray-400 flex-shrink-0"></span>
        </div>
        <p class="text-2xl font-bold text-textprimary dark:text-gray-100">{{ number_format($totalOrders) }}</p>
        <p class="text-xs text-textsecondary dark:text-gray-500 mt-0.5">{{ number_format($totalUnits) }} units · {{ $returnRatio }}% return</p>
    </a>

    @foreach($statusCards as $card)
    @php $stat = $statusSummary->get($card['key']); @endphp
    <a href="{{ route('admin.orders.index', ['status' => $card['key']]) }}"
       class="bg-white dark:bg-gray-800 rounded-xl border border-border dark:border-gray-700 shadow-sm p-3 hover:shadow-md transition-shadow {{ request('status') === $card['key'] ? 'ring-2 ring-offset-1 ring-primary/40' : '' }}">
        <div class="flex items-center justify-between mb-1">
            <span class="text-[11px] text-textsecondary dark:text-gray-400 font-medium truncate leading-tight">{{ $card['label'] }}</span>
            <span class="w-2 h-2 rounded-full flex-shrink-0 ml-1" style="background:{{ $card['color'] }}"></span>
        </div>
        <p class="text-xl font-bold text-textprimary dark:text-gray-100">{{ number_format($stat?->count ?? 0) }}</p>
        <p class="text-[10px] text-textsecondary dark:text-gray-500 mt-0.5 truncate">৳{{ number_format($stat?->revenue ?? 0, 0) }}</p>
    </a>
    @endforeach
</div>

{{-- ── Advanced Filter Panel ────────────────────────────────────────────── --}}
@php
    $hasFilters = collect(['search','status','payment_status','payment_method','source','created_range',
        'created_from','created_to','courier_from','courier_to','category','product','order_tag',
        'utm_source','district','print_status','cod_changed','duplicate','amount_min','amount_max'])
        ->filter(fn($k) => request()->filled($k))->isNotEmpty();
@endphp

<div x-data="{ filtersOpen: {{ $hasFilters ? 'true' : 'false' }} }" class="mb-4">

    {{-- Toolbar --}}
    <div class="flex flex-wrap items-center gap-2 mb-3">
        <button type="button" @click="filtersOpen = !filtersOpen"
                class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold rounded-xl border transition-colors"
                :class="filtersOpen ? 'bg-primary text-white border-primary' : 'bg-white dark:bg-gray-800 text-textprimary dark:text-gray-200 border-border dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700'">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75"/>
            </svg>
            Filters
            @if($hasFilters)<span class="w-2 h-2 rounded-full bg-accent ml-0.5"></span>@endif
        </button>

        <a href="{{ route('admin.orders.create') }}"
           class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold bg-primary text-white rounded-xl hover:bg-primary/90 transition-colors">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            New Order
        </a>

        <a href="{{ route('admin.orders.incomplete') }}"
           class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold bg-amber-500 text-white rounded-xl hover:bg-amber-600 transition-colors">
            Incomplete
        </a>

        <a href="{{ route('admin.invoices.csv') }}"
           class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold border border-border dark:border-gray-600 bg-white dark:bg-gray-800 text-textprimary dark:text-gray-200 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
            Export CSV
        </a>

        @if($hasFilters)
        <a href="{{ route('admin.orders.index') }}"
           class="inline-flex items-center gap-1 px-3 py-2 text-xs font-medium text-danger border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 rounded-xl hover:bg-red-100 dark:hover:bg-red-900/30 transition-colors">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            Clear filters
        </a>
        @endif

        <span class="ml-auto text-xs text-textsecondary dark:text-gray-500">
            {{ number_format($orders->total()) }} result{{ $orders->total() !== 1 ? 's' : '' }}
        </span>
    </div>

    {{-- Filter Panel --}}
    <div x-show="filtersOpen" x-cloak
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0 -translate-y-1">

        <form method="GET" action="{{ route('admin.orders.index') }}"
              class="bg-white dark:bg-gray-800 rounded-2xl border border-border dark:border-gray-700 p-4 shadow-sm space-y-3">

            {{-- Row 1: Search + Status + Payment --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Order#, phone, name…"
                       class="px-3 py-2 text-sm rounded-xl border border-border dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 text-textprimary dark:text-gray-100 placeholder-textsecondary dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all">

                <select name="status"
                        class="px-3 py-2 text-sm rounded-xl border border-border dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all">
                    <option value="">All Statuses</option>
                    @foreach([
                        'pending'                  => 'Pending',
                        'confirmed'                => 'Confirmed',
                        'failed'                   => 'Failed',
                        'processing'               => 'Processing',
                        'hold'                     => 'Hold',
                        'hold_followup'            => 'Hold Followup',
                        'in_courier'               => 'In Courier',
                        'delivered'                => 'Delivered',
                        'completed'                => 'Completed',
                        'cancelled'                => 'Canceled',
                        'pending_return'           => 'Pending Return',
                        'returned'                 => 'Returned',
                        'damage'                   => 'Damage',
                        'hand_delivery'            => 'Hand Delivery',
                        'hand_delivery_completed'  => 'Hand Delivery Completed',
                        'others'                   => 'Others',
                        'return_collection'        => 'Return Collection',
                        'courier_payment_validate' => 'Courier Payment Validate',
                    ] as $val => $label)
                        <option value="{{ $val }}" {{ request('status') === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>

                <select name="payment_status"
                        class="px-3 py-2 text-sm rounded-xl border border-border dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all">
                    <option value="">All Payment Statuses</option>
                    @foreach(['unpaid','paid','failed','refunded'] as $ps)
                        <option value="{{ $ps }}" {{ request('payment_status') === $ps ? 'selected' : '' }}>{{ ucfirst($ps) }}</option>
                    @endforeach
                </select>

                <select name="payment_method"
                        class="px-3 py-2 text-sm rounded-xl border border-border dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all">
                    <option value="">All Payment Methods</option>
                    @foreach(['cod','bkash','nagad','card'] as $pm)
                        <option value="{{ $pm }}" {{ request('payment_method') === $pm ? 'selected' : '' }}>{{ strtoupper($pm) }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Row 2: Source + District + Print + Tag --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <select name="source"
                        class="px-3 py-2 text-sm rounded-xl border border-border dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all">
                    <option value="">All Sources</option>
                    @foreach($sources as $src)
                        <option value="{{ $src }}" {{ request('source') === $src ? 'selected' : '' }}>{{ ucfirst($src) }}</option>
                    @endforeach
                </select>

                <select name="district"
                        class="px-3 py-2 text-sm rounded-xl border border-border dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all">
                    <option value="">All Districts</option>
                    @foreach($districts as $d)
                        <option value="{{ $d }}" {{ request('district') === $d ? 'selected' : '' }}>{{ $d }}</option>
                    @endforeach
                </select>

                <select name="print_status"
                        class="px-3 py-2 text-sm rounded-xl border border-border dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all">
                    <option value="">All Print Statuses</option>
                    <option value="unprinted" {{ request('print_status') === 'unprinted' ? 'selected' : '' }}>Unprinted</option>
                    <option value="printed"   {{ request('print_status') === 'printed'   ? 'selected' : '' }}>Printed</option>
                </select>

                <input type="text" name="order_tag" value="{{ request('order_tag') }}"
                       placeholder="Order tag…"
                       class="px-3 py-2 text-sm rounded-xl border border-border dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 text-textprimary dark:text-gray-100 placeholder-textsecondary dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all">
            </div>

            {{-- Row 3: Date Range --}}
            <div x-data="{ customDates: '{{ request('created_range') === 'custom' ? 'true' : 'false' }}' }">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-xs font-medium text-textsecondary dark:text-gray-400 mr-1">Order Date:</span>
                    @foreach(['today' => 'Today', 'yesterday' => 'Yesterday', 'last_7' => 'Last 7 days', 'last_30' => 'Last 30 days', 'this_month' => 'This month', 'custom' => 'Custom'] as $val => $lbl)
                    <button type="button"
                            @click="document.querySelector('[name=created_range]').value = '{{ $val }}'; customDates = {{ $val === 'custom' ? "'true'" : "'false'" }}"
                            class="px-2.5 py-1 text-xs rounded-lg border transition-colors
                                   {{ request('created_range') === $val
                                      ? 'bg-primary text-white border-primary'
                                      : 'border-border dark:border-gray-600 text-textsecondary dark:text-gray-400 hover:border-primary hover:text-primary dark:hover:text-primary' }}">
                        {{ $lbl }}
                    </button>
                    @endforeach
                    <input type="hidden" name="created_range" value="{{ request('created_range') }}">
                </div>
                <div x-show="customDates === 'true'" class="flex items-center gap-2 mt-2 flex-wrap">
                    <input type="date" name="created_from" value="{{ request('created_from') }}"
                           class="px-3 py-2 text-sm rounded-xl border border-border dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all">
                    <span class="text-xs text-textsecondary dark:text-gray-400">to</span>
                    <input type="date" name="created_to" value="{{ request('created_to') }}"
                           class="px-3 py-2 text-sm rounded-xl border border-border dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all">
                </div>
            </div>

            {{-- Row 4: Category + Product + Amount --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <select name="category"
                        class="px-3 py-2 text-sm rounded-xl border border-border dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all">
                    <option value="">All Categories</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ request('category') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>

                <input type="text" name="utm_source" value="{{ request('utm_source') }}"
                       placeholder="UTM source…"
                       class="px-3 py-2 text-sm rounded-xl border border-border dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 text-textprimary dark:text-gray-100 placeholder-textsecondary dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all">

                <input type="number" name="amount_min" value="{{ request('amount_min') }}" min="0"
                       placeholder="Min amount"
                       class="px-3 py-2 text-sm rounded-xl border border-border dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 text-textprimary dark:text-gray-100 placeholder-textsecondary dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all">

                <input type="number" name="amount_max" value="{{ request('amount_max') }}" min="0"
                       placeholder="Max amount"
                       class="px-3 py-2 text-sm rounded-xl border border-border dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 text-textprimary dark:text-gray-100 placeholder-textsecondary dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all">
            </div>

            {{-- Row 5: Flags + Submit --}}
            <div class="flex flex-wrap items-center justify-between gap-3 pt-1">
                <div class="flex flex-wrap items-center gap-4">
                    <label class="flex items-center gap-2 text-sm text-textprimary dark:text-gray-200 cursor-pointer">
                        <input type="checkbox" name="cod_changed" value="1" {{ request('cod_changed') ? 'checked' : '' }}
                               class="w-4 h-4 rounded border-border text-primary focus:ring-primary/30">
                        COD Changed
                    </label>
                    <label class="flex items-center gap-2 text-sm text-textprimary dark:text-gray-200 cursor-pointer">
                        <input type="checkbox" name="duplicate" value="1" {{ request('duplicate') ? 'checked' : '' }}
                               class="w-4 h-4 rounded border-border text-primary focus:ring-primary/30">
                        Duplicates Only
                    </label>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('admin.orders.index') }}"
                       class="px-4 py-2 text-sm font-medium border border-border dark:border-gray-600 text-textsecondary dark:text-gray-400 hover:text-textprimary dark:hover:text-gray-200 rounded-xl transition-colors">
                        Reset
                    </a>
                    <button type="submit"
                            class="px-5 py-2 text-sm font-semibold bg-primary hover:bg-primary/90 text-white rounded-xl transition-colors">
                        Apply Filters
                    </button>
                </div>
            </div>

        </form>
    </div>
</div>

{{-- ── Orders Table ─────────────────────────────────────────────────────── --}}
<div class="bg-white dark:bg-gray-800 rounded-2xl border border-border dark:border-gray-700 shadow-sm overflow-hidden">

    {{-- Table top bar --}}
    <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-3 border-b border-border dark:border-gray-700">
        <h3 class="font-heading font-semibold text-sm text-textprimary dark:text-gray-100">Order List</h3>
        <form method="GET" action="{{ route('admin.orders.index') }}" class="flex items-center gap-2 flex-wrap">
            {{-- Preserve all active filters except search/per_page/page --}}
            @foreach(request()->except(['search','per_page','page']) as $k => $v)
                <input type="hidden" name="{{ $k }}" value="{{ $v }}">
            @endforeach
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="By Order ID or Mobile"
                   class="px-3 py-1.5 text-xs rounded-lg border border-border dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 text-textprimary dark:text-gray-100 placeholder-textsecondary focus:outline-none focus:ring-2 focus:ring-primary/30 w-44">
            <div class="flex items-center gap-1.5">
                <span class="text-xs text-textsecondary dark:text-gray-400">Show</span>
                <select name="per_page" onchange="this.form.submit()"
                        class="px-2 py-1.5 text-xs rounded-lg border border-border dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30">
                    @foreach([10, 25, 50, 100] as $pp)
                        <option value="{{ $pp }}" {{ $perPage == $pp ? 'selected' : '' }}>{{ $pp }}</option>
                    @endforeach
                </select>
                <span class="text-xs text-textsecondary dark:text-gray-400">entries</span>
            </div>
            <button type="submit" class="px-3 py-1.5 text-xs font-semibold bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors">Search</button>
        </form>
    </div>

    {{-- Showing X to Y of Z --}}
    @if($orders->total() > 0)
    <div class="px-4 py-2 text-xs text-textsecondary dark:text-gray-400 border-b border-border dark:border-gray-700 bg-gray-50/50 dark:bg-gray-700/20">
        Showing {{ $orders->firstItem() }} to {{ $orders->lastItem() }} of {{ number_format($orders->total()) }} entries
        @if(request()->hasAny(['search','status','payment_status','payment_method','source']))
            <span class="text-primary dark:text-blue-400">(filtered)</span>
        @endif
    </div>
    @endif

    {{-- Table --}}
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 dark:bg-gray-700/50 border-b border-border dark:border-gray-700">
                <tr>
                    <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider" style="min-width:130px">Status</th>
                    <th class="px-2 py-2.5 text-center text-[10px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider w-8">
                        <input type="checkbox" id="selectAll" class="w-3.5 h-3.5 rounded border-gray-300 dark:border-gray-600 text-primary focus:ring-primary/30 cursor-pointer">
                    </th>
                    <th class="px-2 py-2.5 text-center text-[10px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider w-10">Note</th>
                    <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider" style="min-width:260px">ID & Products</th>
                    <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider" style="min-width:200px">Name & Number</th>
                    <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider" style="min-width:160px">Date</th>
                    <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider" style="min-width:180px">Address</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border dark:divide-gray-700">
                @forelse($orders as $order)
                @php
                    $serial = $orders->firstItem() + $loop->index;
                    $statusCls = match($order->status) {
                        'pending'                => 'text-yellow-700 bg-yellow-50 dark:text-yellow-400 dark:bg-yellow-900/20',
                        'confirmed'              => 'text-blue-700 bg-blue-50 dark:text-blue-400 dark:bg-blue-900/20',
                        'processing'             => 'text-teal-700 bg-teal-50 dark:text-teal-400 dark:bg-teal-900/20',
                        'hold','hold_followup'   => 'text-orange-700 bg-orange-50 dark:text-orange-400 dark:bg-orange-900/20',
                        'in_courier','shipped'   => 'text-purple-700 bg-purple-50 dark:text-purple-400 dark:bg-purple-900/20',
                        'delivered'              => 'text-green-700 bg-green-50 dark:text-green-400 dark:bg-green-900/20',
                        'completed'              => 'text-green-800 bg-green-100 dark:text-green-300 dark:bg-green-900/30',
                        'cancelled','failed'     => 'text-red-700 bg-red-50 dark:text-red-400 dark:bg-red-900/20',
                        'pending_return'         => 'text-orange-800 bg-orange-100 dark:text-orange-300 dark:bg-orange-900/30',
                        'returned'               => 'text-gray-700 bg-gray-100 dark:text-gray-400 dark:bg-gray-700',
                        default                  => 'text-gray-700 bg-gray-50 dark:text-gray-400 dark:bg-gray-700/50',
                    };
                    $phone = $order->shipping_phone;
                    $waPhone = '880' . ltrim($phone, '0');
                    $fullAddr = collect([$order->shipping_address, $order->shipping_area, $order->shipping_district])
                        ->filter()->implode(', ');
                @endphp
                <tr x-data="{
                    noteOpen: false,
                    noteText: '{{ addslashes($order->notes ?? '') }}',
                    async submitNote(event, orderId) {
                        if (!this.noteText.trim()) return;
                        const response = await fetch(`/admin/orders/${orderId}/notes`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                            },
                            body: JSON.stringify({ note: this.noteText, is_customer_visible: false })
                        });
                        const data = await response.json();
                        if (data.success) {
                            this.noteText = '';
                            this.noteOpen = false;
                            window.showToast('Note added successfully', 'success');
                        }
                    }
                }"
                    class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors {{ $order->is_duplicate ? 'border-l-4 border-amber-400' : '' }}">

                    {{-- 1. STATUS --}}
                    <td class="px-2 py-3 align-top">
                        <div class="flex items-start gap-1.5">
                            {{-- Quick action buttons --}}
                            <div class="flex flex-col gap-1 flex-shrink-0 pt-0.5">
                                <button @click="noteOpen = !noteOpen" title="Add note"
                                        class="w-5 h-5 rounded-full bg-green-500 hover:bg-green-600 text-white flex items-center justify-center transition-colors flex-shrink-0">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                                </button>
                                <a href="{{ route('admin.orders.show', $order) }}" target="_blank" title="View / Print"
                                   class="w-5 h-5 rounded text-gray-400 dark:text-gray-500 hover:text-primary dark:hover:text-blue-400 flex items-center justify-center transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z"/></svg>
                                </a>
                            </div>
                            {{-- Badge + serial + flags --}}
                            <div class="flex flex-col gap-0.5 min-w-0">
                                <span class="inline-block px-1.5 py-0.5 rounded text-[10px] font-semibold whitespace-nowrap {{ $statusCls }}">{{ ucwords(str_replace('_', ' ', $order->status)) }}</span>
                                <span class="text-[10px] text-gray-400 dark:text-gray-500">sl: {{ $serial }}</span>
                                @if($order->cod_changed)<span class="text-[10px] text-orange-600 dark:text-orange-400 font-medium">COD Changed</span>@endif
                                @if($order->is_duplicate)<span class="text-[10px] text-amber-600 dark:text-amber-400 font-medium">Duplicate</span>@endif
                                {{-- Inline note form --}}
                                <div x-show="noteOpen" x-cloak class="mt-1.5">
                                    <form x-on:submit.prevent="submitNote($event, {{ $order->id }})" class="space-y-1">
                                        <textarea x-model="noteText" rows="2" placeholder="Write note…"
                                                  class="w-full text-[10px] px-1.5 py-1 rounded border border-border dark:border-gray-600 bg-white dark:bg-gray-700 text-textprimary dark:text-gray-100 resize-none focus:outline-none focus:ring-1 focus:ring-primary/30" style="min-width:110px"></textarea>
                                        <button type="submit" class="px-2 py-0.5 text-[10px] font-semibold bg-primary text-white rounded hover:bg-primary/90 transition-colors">Save</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </td>

                    {{-- 2. CHECKBOX --}}
                    <td class="px-2 py-3 text-center align-top">
                        <input type="checkbox" value="{{ $order->id }}" name="order_ids[]"
                               class="order-checkbox w-3.5 h-3.5 rounded border-gray-300 dark:border-gray-600 text-primary focus:ring-primary/30 cursor-pointer mt-1">
                    </td>

                    {{-- 3. NOTES --}}
                    <td class="px-2 py-3 text-center align-top">
                        @if($order->notes)
                            <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 text-[10px] font-bold cursor-pointer mt-0.5" title="{{ $order->notes }}">N</span>
                        @else
                            <button @click="noteOpen = !noteOpen" title="Add note"
                                    class="inline-flex items-center justify-center w-5 h-5 rounded-full border border-dashed border-gray-300 dark:border-gray-600 text-gray-400 dark:text-gray-500 hover:border-green-400 hover:text-green-500 transition-colors mt-0.5">
                                <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                            </button>
                        @endif
                    </td>

                    {{-- 4. ID & PRODUCTS --}}
                    <td class="px-3 py-3 align-top">
                        <a href="{{ route('admin.orders.show', $order) }}"
                           class="text-xs font-bold text-primary dark:text-blue-400 hover:underline">#{{ $order->order_number }}</a>
                        <div class="mt-1.5 space-y-1.5">
                            @foreach($order->items as $item)
                            @php $thumb = $item->product?->images->first(); @endphp
                            <div class="flex items-center gap-1.5">
                                @if($thumb)
                                    <img src="{{ asset('storage/' . $thumb->path) }}" alt="{{ $item->product_name }}"
                                         class="w-8 h-8 rounded object-cover flex-shrink-0 border border-border dark:border-gray-600">
                                @else
                                    <div class="w-8 h-8 rounded bg-gray-100 dark:bg-gray-700 flex items-center justify-center flex-shrink-0 border border-border dark:border-gray-600">
                                        <svg class="w-4 h-4 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 21h16.5M4.5 3h15A1.5 1.5 0 0121 4.5v15A1.5 1.5 0 0119.5 21h-15A1.5 1.5 0 013 19.5v-15A1.5 1.5 0 014.5 3z"/></svg>
                                    </div>
                                @endif
                                <div class="min-w-0">
                                    <p class="text-[11px] text-gray-700 dark:text-gray-300 leading-tight" style="max-width:170px">{{ $item->qty }}× {{ Str::limit($item->product_name, 30) }}</p>
                                    <p class="text-[10px] text-gray-400 dark:text-gray-500">৳{{ number_format($item->unit_price, 0) }}</p>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        <p class="mt-1.5 text-[10px] font-bold text-textprimary dark:text-gray-200">Total: ৳{{ number_format($order->total, 0) }}</p>
                    </td>

                    {{-- 5. NAME & NUMBER --}}
                    <td class="px-3 py-3 align-top">
                        <div class="flex items-center gap-1 mb-1">
                            <a href="{{ route('admin.orders.show', $order) }}"
                               class="text-xs font-semibold text-teal-600 dark:text-teal-400 hover:underline truncate" style="max-width:130px">{{ $order->shipping_name }}</a>
                            <button onclick="navigator.clipboard.writeText('{{ addslashes($order->shipping_name) }}')"
                                    class="text-gray-300 dark:text-gray-600 hover:text-gray-500 dark:hover:text-gray-400 flex-shrink-0 transition-colors" title="Copy name">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
                            </button>
                        </div>
                        {{-- Phone badge + action icons --}}
                        <div class="flex items-center gap-1 flex-wrap mb-1">
                            <span class="px-2 py-0.5 text-[10px] font-medium rounded-full bg-gray-800 text-white dark:bg-gray-600 whitespace-nowrap">{{ $phone }}</span>
                            <a href="https://wa.me/{{ $waPhone }}" target="_blank"
                               class="text-green-500 hover:text-green-600 transition-colors flex-shrink-0" title="WhatsApp">
                                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                            </a>
                            <button onclick="navigator.clipboard.writeText('{{ $phone }}')" title="Copy phone"
                                    class="text-gray-400 hover:text-primary dark:hover:text-blue-400 flex-shrink-0 transition-colors">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
                            </button>
                        </div>
                        <p class="text-[10px] text-gray-400 dark:text-gray-500">Source: <span class="text-gray-500 dark:text-gray-400">{{ $order->source ?? 'manual' }}</span></p>
                    </td>

                    {{-- 6. DATE --}}
                    <td class="px-3 py-3 align-top">
                        <div class="space-y-1 text-[10px]">
                            <div class="text-gray-600 dark:text-gray-300">
                                <span class="font-semibold">C:</span>
                                <span class="text-gray-500 dark:text-gray-400"> {{ $order->created_at->format('d M Y') }} · {{ $order->created_at->format('H:i') }}</span>
                            </div>
                            <div class="text-gray-600 dark:text-gray-300">
                                <span class="font-semibold">U:</span>
                                <span class="text-gray-500 dark:text-gray-400"> {{ $order->updated_at->format('d M Y') }} · {{ $order->updated_at->format('H:i') }}</span>
                            </div>
                            @if($order->assignedEmployee)
                            <div class="text-gray-600 dark:text-gray-300">
                                <span class="font-semibold">By:</span>
                                <span class="text-gray-500 dark:text-gray-400"> {{ $order->assignedEmployee->name }}</span>
                            </div>
                            @endif
                        </div>
                    </td>

                    {{-- 7. ADDRESS --}}
                    <td class="px-3 py-3 align-top">
                        <div class="flex items-start gap-1">
                            <p class="text-[10px] text-gray-600 dark:text-gray-400 leading-relaxed flex-1">{{ $fullAddr ?: '—' }}</p>
                            @if($fullAddr)
                            <button onclick="navigator.clipboard.writeText('{{ addslashes($fullAddr) }}')" title="Copy address"
                                    class="flex-shrink-0 text-gray-300 dark:text-gray-600 hover:text-gray-500 dark:hover:text-gray-400 transition-colors mt-0.5">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
                            </button>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-16 text-center">
                        <svg class="w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007z"/>
                        </svg>
                        <p class="text-sm font-medium text-textsecondary dark:text-gray-400">No orders found</p>
                        <p class="text-xs text-textsecondary dark:text-gray-500 mt-1">Try adjusting your filters or create a new order.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Footer: pagination --}}
    <div class="px-4 py-3 border-t border-border dark:border-gray-700 flex flex-wrap items-center justify-between gap-3">
        <p class="text-xs text-textsecondary dark:text-gray-400">
            @if($orders->total() > 0)
                Showing {{ $orders->firstItem() }} to {{ $orders->lastItem() }} of {{ number_format($orders->total()) }} entries
            @else
                No entries
            @endif
        </p>
        @if($orders->hasPages())
            {{ $orders->links() }}
        @endif
    </div>

</div>

{{-- AJAX note toast --}}
<div id="note-toast"
     class="fixed bottom-5 right-5 z-50 flex items-center gap-2 px-4 py-2.5 rounded-lg shadow-lg text-sm font-medium text-white transition-all duration-300 opacity-0 translate-y-2 pointer-events-none"
     style="background:#22c55e">
    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
    <span id="note-toast-msg">Note added successfully</span>
</div>

@push('scripts')
<script>
    // Select-all checkbox
    document.getElementById('selectAll')?.addEventListener('change', function () {
        document.querySelectorAll('.order-checkbox').forEach(cb => cb.checked = this.checked);
    });

    window.showToast = function (message, type) {
        const toast = document.getElementById('note-toast');
        const msg   = document.getElementById('note-toast-msg');
        if (!toast) return;
        toast.style.background = type === 'success' ? '#22c55e' : '#ef4444';
        msg.textContent = message;
        toast.classList.remove('opacity-0', 'translate-y-2');
        toast.classList.add('opacity-100', 'translate-y-0');
        setTimeout(() => {
            toast.classList.add('opacity-0', 'translate-y-2');
            toast.classList.remove('opacity-100', 'translate-y-0');
        }, 2500);
    };
</script>
@endpush

@endsection
