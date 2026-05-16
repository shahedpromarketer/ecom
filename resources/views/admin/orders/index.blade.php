@extends('layouts.admin')

@section('title', request('status') ? ucwords(str_replace('_', ' ', request('status'))) . ' Orders' : (request('cod_changed') ? 'COD Changed Orders' : 'All Orders'))

@section('page-title')
@if(request('status')){{ ucwords(str_replace('_', ' ', request('status'))) }} Orders
@elseif(request('cod_changed'))COD Changed Orders
@else Orders
@endif
@endsection

@section('breadcrumb')
    <span class="text-textsecondary">/</span>
    <span class="text-textprimary font-medium">
        @if(request('status')){{ ucwords(str_replace('_', ' ', request('status'))) }} Orders
        @elseif(request('cod_changed'))COD Changed Orders
        @else All Orders
        @endif
    </span>
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

@php
    $hasFilters = collect(['status','search','payment_status','payment_method','source','created_range',
        'courier_range','status_range','note_range','courier','courier_status','district','category',
        'order_tag','utm_source','print_status','amount_min','amount_max','cod_changed','duplicates'])
        ->filter(fn($k) => request()->filled($k))->isNotEmpty();
@endphp

{{-- ── Top Action Bar ────────────────────────────────────────────────────── --}}
<div class="flex flex-wrap items-center gap-2 mb-4">
    <a href="{{ route('admin.orders.create') }}"
       class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold bg-primary text-white rounded-xl hover:bg-primary/90 transition-colors">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
        </svg>
        New Order
    </a>
    <a href="{{ route('admin.orders.incomplete') }}"
       class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold bg-amber-500 text-white rounded-xl hover:bg-amber-600 transition-colors">
        Incomplete
    </a>
    <a href="{{ route('admin.invoices.csv') }}"
       class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold border border-border dark:border-gray-600 bg-white dark:bg-gray-800 text-textprimary dark:text-gray-200 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
        </svg>
        Export CSV
    </a>
    <span class="ml-auto text-xs text-textsecondary dark:text-gray-500">
        {{ number_format($orders->total()) }} result{{ $orders->total() !== 1 ? 's' : '' }}
    </span>
</div>

{{-- ── Filter Panel ─────────────────────────────────────────────────────────── --}}
<div x-data="{ filterOpen: {{ $hasFilters ? 'true' : 'false' }} }" class="mb-4">

    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl overflow-hidden">

        {{-- Filtering Toggle Bar --}}
        <button type="button" @click="filterOpen = !filterOpen"
                class="w-full flex items-center justify-between px-6 py-4
                       hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75"/>
                </svg>
                <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">Filtering</span>
                @if($hasFilters)
                    <span class="w-2 h-2 rounded-full bg-accent"></span>
                @endif
            </div>
            <svg class="w-4 h-4 text-gray-400 transition-transform duration-200"
                 :class="filterOpen ? 'rotate-90' : ''"
                 fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
            </svg>
        </button>

        {{-- Filter Body --}}
        <div x-show="filterOpen"
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 -translate-y-1"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-100"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-1"
             class="border-t border-gray-100 dark:border-gray-700">

            <form method="GET" action="{{ route('admin.orders.index') }}" class="p-6 space-y-6">

                {{-- ROW 1: Date Range Dropdowns --}}
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Order Created At</label>
                        <select name="created_range"
                                class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-600
                                       bg-primary text-white text-sm
                                       focus:outline-none focus:ring-2 focus:ring-primary/30">
                            <option value="">All Time</option>
                            <option value="today"      {{ request('created_range')==='today'?'selected':'' }}>Today</option>
                            <option value="yesterday"  {{ request('created_range')==='yesterday'?'selected':'' }}>Yesterday</option>
                            <option value="last_7"     {{ request('created_range')==='last_7'?'selected':'' }}>Last 7 Days</option>
                            <option value="last_30"    {{ request('created_range')==='last_30'?'selected':'' }}>Last 30 Days</option>
                            <option value="this_month" {{ request('created_range')==='this_month'?'selected':'' }}>This Month</option>
                            <option value="last_month" {{ request('created_range')==='last_month'?'selected':'' }}>Last Month</option>
                            <option value="this_year"  {{ request('created_range')==='this_year'?'selected':'' }}>This Year</option>
                            <option value="custom"     {{ request('created_range')==='custom'?'selected':'' }}>Custom Range</option>
                        </select>
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Courier Submitted At</label>
                        <select name="courier_range"
                                class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-600
                                       bg-primary text-white text-sm
                                       focus:outline-none focus:ring-2 focus:ring-primary/30">
                            <option value="">All Time</option>
                            <option value="today"      {{ request('courier_range')==='today'?'selected':'' }}>Today</option>
                            <option value="yesterday"  {{ request('courier_range')==='yesterday'?'selected':'' }}>Yesterday</option>
                            <option value="last_7"     {{ request('courier_range')==='last_7'?'selected':'' }}>Last 7 Days</option>
                            <option value="this_month" {{ request('courier_range')==='this_month'?'selected':'' }}>This Month</option>
                            <option value="custom"     {{ request('courier_range')==='custom'?'selected':'' }}>Custom Range</option>
                        </select>
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">
                            Status Added At
                            <span class="text-gray-400 font-normal normal-case ml-1">(Max 90 days)</span>
                        </label>
                        <select name="status_range"
                                class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-600
                                       bg-primary text-white text-sm
                                       focus:outline-none focus:ring-2 focus:ring-primary/30">
                            <option value="">All Time</option>
                            <option value="today"      {{ request('status_range')==='today'?'selected':'' }}>Today</option>
                            <option value="last_7"     {{ request('status_range')==='last_7'?'selected':'' }}>Last 7 Days</option>
                            <option value="last_30"    {{ request('status_range')==='last_30'?'selected':'' }}>Last 30 Days</option>
                            <option value="this_month" {{ request('status_range')==='this_month'?'selected':'' }}>This Month</option>
                        </select>
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Note Added At</label>
                        <select name="note_range"
                                class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-600
                                       bg-primary text-white text-sm
                                       focus:outline-none focus:ring-2 focus:ring-primary/30">
                            <option value="">All Time</option>
                            <option value="today"      {{ request('note_range')==='today'?'selected':'' }}>Today</option>
                            <option value="last_7"     {{ request('note_range')==='last_7'?'selected':'' }}>Last 7 Days</option>
                            <option value="this_month" {{ request('note_range')==='this_month'?'selected':'' }}>This Month</option>
                        </select>
                    </div>
                </div>

                {{-- ROW 2: Status, Source, Order Tag, Courier --}}
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</label>
                        <select name="status"
                                class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-600
                                       bg-white dark:bg-gray-700 text-sm text-gray-900 dark:text-white
                                       focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary">
                            <option value="">All Statuses</option>
                            @foreach([
                                'pending'                  => 'Pending',
                                'confirmed'                => 'Confirmed',
                                'processing'               => 'Processing',
                                'hold'                     => 'Hold',
                                'hold_followup'            => 'Hold Followup',
                                'shipped'                  => 'In Courier',
                                'delivered'                => 'Delivered',
                                'completed'                => 'Completed',
                                'cancelled'                => 'Cancelled',
                                'pending_return'           => 'Pending Return',
                                'returned'                 => 'Returned',
                                'failed'                   => 'Failed',
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
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Order Source</label>
                        <select name="source"
                                class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-600
                                       bg-white dark:bg-gray-700 text-sm text-gray-900 dark:text-white
                                       focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary">
                            <option value="">All Sources</option>
                            @foreach($sources as $src)
                                <option value="{{ $src }}" {{ request('source') === $src ? 'selected' : '' }}>{{ ucfirst($src) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Order Tag</label>
                        <input type="text" name="order_tag" value="{{ request('order_tag') }}"
                               placeholder="Search tag..."
                               class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-600
                                      bg-white dark:bg-gray-700 text-sm text-gray-900 dark:text-white
                                      placeholder-gray-400 dark:placeholder-gray-500
                                      focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary">
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Courier</label>
                        <select name="courier"
                                class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-600
                                       bg-white dark:bg-gray-700 text-sm text-gray-900 dark:text-white
                                       focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary">
                            <option value="">All</option>
                            <option value="pathao"     {{ request('courier')==='pathao'?'selected':'' }}>Pathao</option>
                            <option value="steadfast"  {{ request('courier')==='steadfast'?'selected':'' }}>Steadfast</option>
                            <option value="carrybe"    {{ request('courier')==='carrybe'?'selected':'' }}>CarryBe</option>
                            <option value="redx"       {{ request('courier')==='redx'?'selected':'' }}>REDx</option>
                            <option value="paperfly"   {{ request('courier')==='paperfly'?'selected':'' }}>Paperfly</option>
                            <option value="manual"     {{ request('courier')==='manual'?'selected':'' }}>Manual</option>
                        </select>
                    </div>
                </div>

                {{-- ROW 3: Courier Status, Payment Status, Print Status, District --}}
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Courier Status</label>
                        <select name="courier_status"
                                class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-600
                                       bg-white dark:bg-gray-700 text-sm text-gray-900 dark:text-white
                                       focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary">
                            <option value="">All</option>
                            <option value="submitted" {{ request('courier_status')==='submitted'?'selected':'' }}>Submitted</option>
                            <option value="pending"   {{ request('courier_status')==='pending'?'selected':'' }}>Pending</option>
                            <option value="delivered" {{ request('courier_status')==='delivered'?'selected':'' }}>Delivered</option>
                            <option value="returned"  {{ request('courier_status')==='returned'?'selected':'' }}>Returned</option>
                        </select>
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Payment Status</label>
                        <select name="payment_status"
                                class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-600
                                       bg-white dark:bg-gray-700 text-sm text-gray-900 dark:text-white
                                       focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary">
                            <option value="">All</option>
                            <option value="unpaid"   {{ request('payment_status')==='unpaid'?'selected':'' }}>Unpaid</option>
                            <option value="paid"     {{ request('payment_status')==='paid'?'selected':'' }}>Paid</option>
                            <option value="failed"   {{ request('payment_status')==='failed'?'selected':'' }}>Failed</option>
                            <option value="refunded" {{ request('payment_status')==='refunded'?'selected':'' }}>Refunded</option>
                        </select>
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Print Status</label>
                        <select name="print_status"
                                class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-600
                                       bg-white dark:bg-gray-700 text-sm text-gray-900 dark:text-white
                                       focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary">
                            <option value="">All</option>
                            <option value="unprinted" {{ request('print_status')==='unprinted'?'selected':'' }}>Unprinted</option>
                            <option value="printed"   {{ request('print_status')==='printed'?'selected':'' }}>Printed</option>
                        </select>
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">District</label>
                        <input type="text" name="district" value="{{ request('district') }}"
                               placeholder="Search district..."
                               list="districts-list"
                               class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-600
                                      bg-white dark:bg-gray-700 text-sm text-gray-900 dark:text-white
                                      placeholder-gray-400 dark:placeholder-gray-500
                                      focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary">
                        <datalist id="districts-list">
                            @foreach($districts as $d)
                                <option value="{{ $d }}">
                            @endforeach
                        </datalist>
                    </div>
                </div>

                {{-- ROW 4: Category, UTM Source, Amount Min, Amount Max --}}
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Product Category</label>
                        <select name="category"
                                class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-600
                                       bg-white dark:bg-gray-700 text-sm text-gray-900 dark:text-white
                                       focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary">
                            <option value="">All Categories</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ request('category') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">UTM Source</label>
                        <select name="utm_source"
                                class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-600
                                       bg-white dark:bg-gray-700 text-sm text-gray-900 dark:text-white
                                       focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary">
                            <option value="">Any</option>
                            <option value="facebook"  {{ request('utm_source')==='facebook'?'selected':'' }}>Facebook</option>
                            <option value="google"    {{ request('utm_source')==='google'?'selected':'' }}>Google</option>
                            <option value="tiktok"    {{ request('utm_source')==='tiktok'?'selected':'' }}>TikTok</option>
                            <option value="instagram" {{ request('utm_source')==='instagram'?'selected':'' }}>Instagram</option>
                            <option value="direct"    {{ request('utm_source')==='direct'?'selected':'' }}>Direct</option>
                        </select>
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Amount Min (৳)</label>
                        <input type="number" name="amount_min" value="{{ request('amount_min') }}"
                               placeholder="0" min="0"
                               class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-600
                                      bg-white dark:bg-gray-700 text-sm text-gray-900 dark:text-white
                                      placeholder-gray-400 dark:placeholder-gray-500
                                      focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary">
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Amount Max (৳)</label>
                        <input type="number" name="amount_max" value="{{ request('amount_max') }}"
                               placeholder="99999" min="0"
                               class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-600
                                      bg-white dark:bg-gray-700 text-sm text-gray-900 dark:text-white
                                      placeholder-gray-400 dark:placeholder-gray-500
                                      focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary">
                    </div>
                </div>

                {{-- ROW 5: Checkboxes --}}
                <div class="flex items-center gap-6">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="cod_changed" value="1"
                               {{ request('cod_changed') ? 'checked' : '' }}
                               class="w-4 h-4 rounded border-gray-300 text-primary focus:ring-primary/30">
                        <span class="text-sm text-gray-600 dark:text-gray-400">COD Changed</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="duplicates" value="1"
                               {{ request('duplicates') ? 'checked' : '' }}
                               class="w-4 h-4 rounded border-gray-300 text-primary focus:ring-primary/30">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Duplicates Only</span>
                    </label>
                </div>

                {{-- Action Buttons --}}
                <div class="flex items-center flex-wrap gap-2 pt-4 border-t border-gray-100 dark:border-gray-700">
                    <a href="{{ route('admin.orders.index') }}"
                       class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl
                              bg-gray-800 text-white text-xs font-semibold
                              hover:bg-gray-700 transition-colors">
                        ✕ Clear Filter
                    </a>
                    <button type="submit"
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl
                                   bg-primary text-white text-xs font-semibold
                                   hover:bg-primary/90 transition-colors">
                        Apply Filters
                    </button>
                    <a href="{{ route('admin.orders.index', array_merge(request()->query(), ['view'=>'items'])) }}"
                       class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl
                              bg-teal-600 text-white text-xs font-semibold hover:bg-teal-700 transition-colors">
                        ⊞ Order Items
                    </a>
                    <a href="{{ route('admin.orders.index', ['view'=>'sources']) }}"
                       class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl
                              bg-purple-600 text-white text-xs font-semibold hover:bg-purple-700 transition-colors">
                        ≡ Order Sources
                    </a>
                    <button type="button" onclick="showDuplicates()"
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl
                                   bg-orange-500 text-white text-xs font-semibold hover:bg-orange-600 transition-colors">
                        ⧉ Duplicate Orders
                    </button>
                    <a href="{{ route('admin.orders.index', array_merge(request()->query(), ['view'=>'employee'])) }}"
                       class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl
                              bg-blue-600 text-white text-xs font-semibold hover:bg-blue-700 transition-colors">
                        👤 Orders Employee
                    </a>
                    <a href="{{ route('admin.orders.index', array_merge(request()->query(), ['view'=>'previous_status'])) }}"
                       class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl
                              bg-indigo-600 text-white text-xs font-semibold hover:bg-indigo-700 transition-colors">
                        ↺ Order Previous Status
                    </a>
                    <a href="{{ route('admin.orders.index', array_merge(request()->query(), ['view'=>'locations'])) }}"
                       class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl
                              bg-amber-500 text-white text-xs font-semibold hover:bg-amber-600 transition-colors">
                        📍 Orders by Locations
                    </a>
                    <a href="{{ route('admin.orders.index', array_merge(request()->query(), ['view'=>'courier_statuses'])) }}"
                       class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl
                              bg-gray-600 text-white text-xs font-semibold hover:bg-gray-700 transition-colors">
                        ≡ Courier Statuses
                    </a>
                </div>

            </form>
        </div>
    </div>
</div>

{{-- ── Orders Table ─────────────────────────────────────────────────────── --}}
<div x-data="{
  selectedOrders: [],
  selectAll: false,
  toggleAll() {
    this.selectAll = !this.selectAll;
    if (this.selectAll) {
      this.selectedOrders = Array.from(document.querySelectorAll('input[name=selected]')).map(el => el.value);
    } else {
      this.selectedOrders = [];
    }
    updateBulkSummary(this.selectedOrders);
  },
  toggleOrder(id) {
    const idx = this.selectedOrders.indexOf(id);
    if (idx === -1) { this.selectedOrders.push(id); } else { this.selectedOrders.splice(idx, 1); }
    updateBulkSummary(this.selectedOrders);
  },
  get selectedCount() { return this.selectedOrders.length; }
}">
<div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">

    {{-- Table Header Bar --}}
    <div class="px-6 py-4 flex items-center justify-between border-b border-gray-100 dark:border-gray-700">
        <div class="flex items-center gap-3">
            <h3 class="text-sm font-bold text-gray-900 dark:text-white">Order List</h3>
            <span class="px-2 py-0.5 bg-primary/10 text-primary text-xs font-semibold rounded-full">
                {{ $orders->total() }} results
            </span>
        </div>
        <div class="flex items-center gap-3">
            {{-- Search --}}
            <form method="GET" action="{{ route('admin.orders.index') }}" class="flex items-center gap-2">
                @foreach(request()->except(['search','page']) as $key => $val)
                    <input type="hidden" name="{{ $key }}" value="{{ $val }}">
                @endforeach
                <input type="text" name="search" value="{{ request('search') }}"
                    placeholder="Search order# or phone..."
                    class="w-56 px-4 py-2 text-sm rounded-xl border border-gray-200
                           dark:border-gray-600 bg-white dark:bg-gray-700
                           text-gray-900 dark:text-white placeholder-gray-400
                           focus:outline-none focus:ring-2 focus:ring-primary/30">
                <button type="submit" class="px-3 py-2 text-xs font-semibold bg-primary text-white rounded-xl hover:bg-primary/90 transition-colors">Search</button>
            </form>
            {{-- Per page --}}
            <select onchange="window.location.href=this.value"
                class="px-3 py-2 text-sm rounded-xl border border-gray-200
                       dark:border-gray-600 bg-white dark:bg-gray-700
                       text-gray-900 dark:text-white">
                @foreach([10, 25, 50, 100] as $n)
                <option value="{{ request()->fullUrlWithQuery(['per_page' => $n, 'page' => 1]) }}"
                    {{ request('per_page', 10) == $n ? 'selected' : '' }}>
                    Show {{ $n }}
                </option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto">
        <table class="w-full min-w-[800px]">

            {{-- Head --}}
            <thead>
                <tr class="bg-gray-50 dark:bg-gray-700/50 border-b border-gray-100 dark:border-gray-700">
                    <th class="px-4 py-3 text-left">
                        <input type="checkbox" id="selectAll"
                            :checked="selectAll"
                            @click="toggleAll()"
                            class="w-4 h-4 rounded border-gray-300 text-primary cursor-pointer">
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide w-32">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide">ID & Products</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Customer</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide w-40">Date</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Address</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide w-32">Payment</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide w-8"></th>
                </tr>
            </thead>

            {{-- Body --}}
            <tbody class="divide-y divide-gray-50 dark:divide-gray-700/50">
                @forelse($orders as $order)
                @php
                    $serial = $orders->firstItem() + $loop->index;
                    $statusColors = [
                        'pending'        => 'bg-yellow-50 text-yellow-700 border-yellow-200 dark:bg-yellow-900/20 dark:text-yellow-400',
                        'confirmed'      => 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-900/20 dark:text-blue-400',
                        'processing'     => 'bg-cyan-50 text-cyan-700 border-cyan-200',
                        'hold'           => 'bg-orange-50 text-orange-700 border-orange-200',
                        'hold_followup'  => 'bg-orange-50 text-orange-600 border-orange-200',
                        'shipped'        => 'bg-purple-50 text-purple-700 border-purple-200 dark:bg-purple-900/20 dark:text-purple-400',
                        'delivered'      => 'bg-green-50 text-green-700 border-green-200',
                        'completed'      => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                        'cancelled'      => 'bg-red-50 text-red-600 border-red-200 dark:bg-red-900/20 dark:text-red-400',
                        'pending_return' => 'bg-pink-50 text-pink-700 border-pink-200',
                        'returned'       => 'bg-gray-100 text-gray-600 border-gray-200',
                        'failed'         => 'bg-red-100 text-red-700 border-red-300',
                    ];
                    $statusLabels = [
                        'pending'        => 'Pending',
                        'confirmed'      => 'Confirmed',
                        'processing'     => 'Processing',
                        'hold'           => 'Hold',
                        'hold_followup'  => 'Hold F/U',
                        'shipped'        => 'In Courier',
                        'delivered'      => 'Delivered',
                        'completed'      => 'Completed',
                        'cancelled'      => 'Cancelled',
                        'pending_return' => 'Pend. Return',
                        'returned'       => 'Returned',
                        'failed'         => 'Failed',
                    ];
                    $phone = $order->shipping_phone;
                    $waPhone = '880' . ltrim($phone, '0');
                    $fullAddr = collect([$order->shipping_address, $order->shipping_area, $order->shipping_district])
                        ->filter()->implode(', ');
                @endphp
                <tr class="hover:bg-gray-50/80 dark:hover:bg-gray-700/20 transition-colors duration-100 group {{ $order->is_duplicate ? 'border-l-4 border-amber-400' : '' }}"
                    data-order-id="{{ $order->id }}"
                    data-total="{{ $order->total }}"
                    data-paid="{{ $order->payment_status === 'paid' ? $order->total : 0 }}"
                    data-due="{{ $order->payment_status !== 'paid' ? $order->total : 0 }}">

                    {{-- Checkbox --}}
                    <td class="px-4 py-4">
                        <input type="checkbox" name="selected" value="{{ $order->id }}"
                            :checked="selectedOrders.includes('{{ $order->id }}')"
                            @change="toggleOrder('{{ $order->id }}')"
                            class="order-checkbox w-4 h-4 rounded border-gray-300 text-primary cursor-pointer">
                    </td>

                    {{-- STATUS + ACTIONS --}}
                    <td class="px-4 py-4">
                        <div class="space-y-2">
                            <span class="inline-flex px-2.5 py-1 rounded-lg text-xs font-semibold border {{ $statusColors[$order->status] ?? 'bg-gray-100 text-gray-600 border-gray-200' }}">
                                {{ $statusLabels[$order->status] ?? ucfirst(str_replace('_', ' ', $order->status)) }}
                            </span>
                            <div class="flex items-center gap-1.5">
                                <span class="text-xs text-gray-400">sl: {{ $serial }}</span>
                                @if($order->cod_changed)
                                    <span class="text-[10px] text-orange-600 dark:text-orange-400 font-medium">COD</span>
                                @endif
                                @if($order->is_duplicate)
                                    <span class="text-[10px] text-amber-600 dark:text-amber-400 font-medium">Dup</span>
                                @endif
                                <a href="{{ route('admin.invoices.generatePrint', $order) }}"
                                   target="_blank"
                                   class="w-5 h-5 flex items-center justify-center text-gray-400 hover:text-primary transition rounded">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M17 17H7a2 2 0 01-2-2V9a2 2 0 012-2h10a2 2 0 012 2v6a2 2 0 01-2 2zm0 0v2a2 2 0 01-2 2H9a2 2 0 01-2-2v-2"/>
                                    </svg>
                                </a>
                            </div>
                        </div>
                    </td>

                    {{-- ID & PRODUCTS --}}
                    <td class="px-4 py-4 max-w-xs">
                        <a href="{{ route('admin.orders.show', $order) }}"
                           class="text-sm font-bold text-primary hover:underline">
                            #{{ $order->order_number }}
                        </a>
                        <div class="mt-1.5 space-y-1">
                            @foreach($order->items->take(2) as $item)
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-lg overflow-hidden bg-gray-100 flex-shrink-0 border border-gray-200">
                                    @if($item->product?->primaryImage)
                                        <img src="{{ asset('storage/' . $item->product->primaryImage->image_path) }}"
                                             class="w-full h-full object-cover"
                                             alt="{{ $item->product_name }}">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center text-gray-300">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                      d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                        </div>
                                    @endif
                                </div>
                                <div class="min-w-0">
                                    <p class="text-xs text-gray-700 dark:text-gray-300 truncate max-w-[160px]">
                                        {{ $item->qty }}× {{ $item->product_name }}
                                    </p>
                                    <p class="text-xs text-gray-400">৳{{ number_format($item->unit_price) }}</p>
                                </div>
                            </div>
                            @endforeach
                            @if($order->items->count() > 2)
                                <p class="text-xs text-gray-400">+{{ $order->items->count() - 2 }} more items</p>
                            @endif
                        </div>
                        <div class="mt-2 pt-2 border-t border-gray-100 dark:border-gray-700">
                            <span class="text-xs font-bold text-gray-900 dark:text-white">
                                Total: ৳{{ number_format($order->total) }}
                            </span>
                            @if($order->discount > 0)
                                <span class="text-xs text-green-600 ml-1">(-৳{{ number_format($order->discount) }})</span>
                            @endif
                        </div>
                    </td>

                    {{-- CUSTOMER --}}
                    <td class="px-4 py-4">
                        <div class="space-y-1.5">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">
                                {{ $order->shipping_name }}
                            </p>
                            <div class="flex items-center gap-1.5">
                                <span class="px-2.5 py-1 bg-gray-900 dark:bg-gray-600 text-white text-xs font-mono rounded-lg">
                                    {{ $phone }}
                                </span>
                                <a href="https://wa.me/{{ $waPhone }}"
                                   target="_blank"
                                   class="w-6 h-6 flex items-center justify-center bg-green-500 text-white rounded-lg hover:bg-green-600 transition">
                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                                    </svg>
                                </a>
                                <button onclick="copyToClipboard('{{ $phone }}')"
                                    class="w-6 h-6 flex items-center justify-center text-gray-400 hover:text-primary bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 transition">
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                    </svg>
                                </button>
                            </div>
                            @if($order->source)
                            <span class="text-xs text-gray-400">
                                Source: {{ ucfirst(str_replace('_', ' ', $order->source)) }}
                            </span>
                            @endif
                        </div>
                    </td>

                    {{-- DATE --}}
                    <td class="px-4 py-4">
                        <div class="space-y-1 text-xs text-gray-500 dark:text-gray-400">
                            <div>
                                <span class="font-semibold text-gray-400">C:</span>
                                {{ $order->created_at->format('d M Y · H:i') }}
                            </div>
                            <div>
                                <span class="font-semibold text-gray-400">U:</span>
                                {{ $order->updated_at->format('d M Y · H:i') }}
                            </div>
                            <div class="text-gray-400">{{ $order->created_at->diffForHumans() }}</div>
                            @if($order->assignedEmployee)
                            <div>
                                <span class="font-semibold text-gray-400">By:</span>
                                {{ $order->assignedEmployee->name }}
                            </div>
                            @endif
                        </div>
                    </td>

                    {{-- ADDRESS --}}
                    <td class="px-4 py-4">
                        <div class="flex items-start gap-1.5">
                            <p class="text-xs text-gray-600 dark:text-gray-400 leading-relaxed">
                                {{ $order->shipping_district }}
                                @if($order->shipping_area), {{ $order->shipping_area }}@endif
                            </p>
                            @if($fullAddr)
                            <button onclick="copyToClipboard('{{ addslashes($fullAddr) }}')"
                                class="w-5 h-5 flex-shrink-0 flex items-center justify-center text-gray-300 hover:text-primary transition rounded">
                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                </svg>
                            </button>
                            @endif
                        </div>
                    </td>

                    {{-- PAYMENT --}}
                    <td class="px-4 py-4">
                        <div class="space-y-1">
                            <span class="inline-flex px-2 py-0.5 rounded-md text-xs font-semibold
                                @if($order->payment_method === 'bkash') bg-pink-50 text-pink-700 border border-pink-200
                                @elseif($order->payment_method === 'nagad') bg-orange-50 text-orange-700 border border-orange-200
                                @else bg-gray-100 text-gray-600 border border-gray-200 @endif">
                                {{ strtoupper($order->payment_method) }}
                            </span>
                            <div>
                                <span class="text-xs
                                    @if($order->payment_status === 'paid') text-green-600 font-semibold
                                    @elseif($order->payment_status === 'failed') text-red-500
                                    @else text-gray-400 @endif">
                                    {{ ucfirst($order->payment_status) }}
                                </span>
                            </div>
                            @if($order->payment_status !== 'paid')
                            <p class="text-xs text-red-500 font-semibold">Due: ৳{{ number_format($order->total) }}</p>
                            @else
                            <p class="text-xs text-green-600 font-semibold">Paid: ৳{{ number_format($order->total) }}</p>
                            @endif
                        </div>
                    </td>

                    {{-- ACTIONS --}}
                    <td class="px-4 py-4">
                        <a href="{{ route('admin.orders.show', $order) }}"
                           class="w-8 h-8 flex items-center justify-center rounded-xl
                                  bg-primary/10 text-primary hover:bg-primary hover:text-white
                                  transition-all duration-150">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                    </td>

                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-6 py-16 text-center">
                        <div class="flex flex-col items-center gap-3">
                            <div class="w-14 h-14 rounded-2xl bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                                <svg class="w-7 h-7 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                          d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                </svg>
                            </div>
                            <p class="text-sm font-semibold text-gray-500 dark:text-gray-400">No orders found</p>
                            <p class="text-xs text-gray-400">Try adjusting your filters</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between">
        <p class="text-xs text-gray-500 dark:text-gray-400">
            Showing {{ $orders->firstItem() ?? 0 }} to {{ $orders->lastItem() ?? 0 }}
            of {{ $orders->total() }} orders
        </p>
        <div class="flex items-center gap-1">
            {{ $orders->links('pagination::tailwind') }}
        </div>
    </div>

</div>

{{-- Sticky Bulk Action Bar --}}
<div x-show="selectedCount > 0"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0 translate-y-4"
     x-transition:enter-end="opacity-100 translate-y-0"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100 translate-y-0"
     x-transition:leave-end="opacity-0 translate-y-4"
     class="fixed bottom-0 md:bottom-6 left-0 md:left-1/2
            w-full md:w-auto md:-translate-x-1/2 z-50
            bg-white dark:bg-gray-800
            md:rounded-2xl shadow-2xl
            border-t md:border border-gray-200 dark:border-gray-700
            px-4 md:px-6 py-3 md:py-4
            md:min-w-[700px] md:max-w-4xl">

  <!-- Mobile Layout -->
  <div class="flex md:hidden items-center justify-between gap-3">

    <!-- Selected count -->
    <div class="flex items-center gap-2">
      <div class="w-7 h-7 rounded-xl bg-primary flex items-center justify-center">
        <span class="text-white text-xs font-bold" x-text="selectedCount"></span>
      </div>
      <span class="text-sm font-semibold text-gray-900 dark:text-white"
            x-text="selectedCount + ' selected'"></span>
    </div>

    <!-- Mobile action buttons -->
    <div class="flex items-center gap-2">

      <!-- Change Status dropdown (mobile) -->
      <div class="relative" x-data="{ open: false }">
        <button @click="open = !open"
          class="px-3 py-2 rounded-xl bg-primary text-white text-xs font-semibold">
          Status &#x25BE;
        </button>
        <div x-show="open" @click.away="open = false"
             class="absolute bottom-full mb-2 right-0 bg-white dark:bg-gray-800
                    rounded-2xl border border-gray-200 dark:border-gray-700
                    shadow-xl z-50 overflow-hidden w-40">
          @foreach(['pending','confirmed','processing','hold','shipped','delivered','completed','cancelled','returned'] as $s)
          <button @click="bulkUpdateStatus('{{ $s }}'); open = false"
            class="w-full px-4 py-2.5 text-left text-xs font-medium
                   text-gray-700 dark:text-gray-300
                   hover:bg-gray-50 dark:hover:bg-gray-700 transition">
            {{ ucfirst(str_replace('_',' ',$s)) }}
          </button>
          @endforeach
        </div>
      </div>

      <!-- Export -->
      <button @click="bulkExport()"
        class="px-3 py-2 rounded-xl bg-green-600 text-white text-xs font-semibold">
        CSV
      </button>

      <!-- Clear -->
      <button @click="selectedOrders = []; selectAll = false; updateBulkSummary([])"
        class="px-3 py-2 rounded-xl bg-gray-200 dark:bg-gray-700
               text-gray-700 dark:text-gray-300 text-xs font-semibold">
        &#x2715;
      </button>

    </div>
  </div>

  <!-- Desktop Layout (existing, hidden on mobile) -->
  <div class="hidden md:flex items-center justify-between gap-6">

    <!-- Left: Selected count -->
    <div class="flex items-center gap-3 flex-shrink-0">
      <div class="w-8 h-8 rounded-xl bg-primary flex items-center justify-center">
        <span class="text-white text-xs font-bold" x-text="selectedCount"></span>
      </div>
      <div>
        <p class="text-sm font-semibold text-gray-900 dark:text-white"
           x-text="selectedCount + ' orders selected'"></p>
        <button @click="selectedOrders = []; selectAll = false; updateBulkSummary([])"
          class="text-xs text-gray-400 hover:text-red-500 transition">
          Clear selection
        </button>
      </div>
    </div>

    <div class="h-8 w-px bg-gray-200 dark:bg-gray-600"></div>

    <!-- Middle: Actions -->
    <div class="flex items-center flex-wrap gap-2">

      <!-- Change Status -->
      <div x-data="{ statusOpen: false }">
        <div class="relative">
          <button @click="statusOpen = !statusOpen"
            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl
                   bg-primary text-white text-xs font-semibold hover:bg-primary/90 transition">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            Change Status
            <svg class="w-3 h-3" :class="statusOpen ? 'rotate-180' : ''"
                 fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
          </button>
          <div x-show="statusOpen" @click.away="statusOpen = false"
               class="absolute bottom-full mb-2 left-0 bg-white dark:bg-gray-800
                      rounded-2xl border border-gray-200 dark:border-gray-700
                      shadow-xl z-50 overflow-hidden w-44">
            @foreach(['pending','confirmed','processing','hold','hold_followup','shipped','delivered','completed','cancelled','pending_return','returned'] as $s)
            <button @click="bulkUpdateStatus('{{ $s }}'); statusOpen = false"
              class="w-full px-4 py-2.5 text-left text-xs font-medium
                     text-gray-700 dark:text-gray-300
                     hover:bg-gray-50 dark:hover:bg-gray-700 transition">
              {{ ucfirst(str_replace('_',' ',$s)) }}
            </button>
            @endforeach
          </div>
        </div>
      </div>

      <!-- Print -->
      <button @click="bulkPrint()"
        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl
               bg-teal-600 text-white text-xs font-semibold hover:bg-teal-700 transition">
        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M17 17H7a2 2 0 01-2-2V9a2 2 0 012-2h10a2 2 0 012 2v6a2 2 0 01-2 2zm0 0v2a2 2 0 01-2 2H9a2 2 0 01-2-2v-2"/>
        </svg>
        Print
      </button>

      <!-- Export CSV -->
      <button @click="bulkExport()"
        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl
               bg-green-600 text-white text-xs font-semibold hover:bg-green-700 transition">
        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
        </svg>
        Export CSV
      </button>

      <!-- Assign Courier -->
      <div class="relative" x-data="{ courierOpen: false }">
        <button @click="courierOpen = !courierOpen"
          class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl
                 bg-purple-600 text-white text-xs font-semibold hover:bg-purple-700 transition">
          <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
          </svg>
          Courier
          <svg class="w-3 h-3" :class="courierOpen ? 'rotate-180' : ''"
               fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
          </svg>
        </button>
        <div x-show="courierOpen" @click.away="courierOpen = false"
             class="absolute bottom-full mb-2 left-0 bg-white dark:bg-gray-800
                    rounded-2xl border border-gray-200 dark:border-gray-700
                    shadow-xl z-50 overflow-hidden w-36">
          @foreach(['pathao','steadfast','carrybe','redx','paperfly','manual'] as $c)
          <button @click="bulkAssignCourier('{{ $c }}'); courierOpen = false"
            class="w-full px-4 py-2.5 text-left text-xs font-medium
                   text-gray-700 dark:text-gray-300
                   hover:bg-gray-50 dark:hover:bg-gray-700 transition">
            {{ ucfirst($c) }}
          </button>
          @endforeach
        </div>
      </div>

      <!-- Tag -->
      <div class="flex items-center gap-1.5">
        <input type="text" id="bulkTag" placeholder="Add tag..."
          class="w-28 px-3 py-2 text-xs rounded-xl border border-gray-200
                 dark:border-gray-600 bg-white dark:bg-gray-700
                 text-gray-900 dark:text-white placeholder-gray-400
                 focus:outline-none focus:ring-2 focus:ring-primary/30">
        <button @click="bulkAssignTag()"
          class="px-3 py-2 rounded-xl bg-amber-500 text-white
                 text-xs font-semibold hover:bg-amber-600 transition">
          Tag
        </button>
      </div>

    </div>

    <div class="h-8 w-px bg-gray-200 dark:bg-gray-600"></div>

    <!-- Right: Summary -->
    <div class="flex-shrink-0 text-right space-y-0.5">
      <p class="text-xs text-gray-500 dark:text-gray-400">
        Total: <span class="font-bold text-gray-900 dark:text-white" id="bulk-total">৳0</span>
      </p>
      <p class="text-xs text-green-600">
        Paid: <span class="font-semibold" id="bulk-paid">৳0</span>
      </p>
      <p class="text-xs text-red-500">
        Due: <span class="font-semibold" id="bulk-due">৳0</span>
      </p>
    </div>

  </div>
</div>

</div>{{-- end x-data wrapper --}}

{{-- AJAX note toast --}}
<div id="note-toast"
     class="fixed bottom-5 right-5 z-50 flex items-center gap-2 px-4 py-2.5 rounded-lg shadow-lg text-sm font-medium text-white transition-all duration-300 opacity-0 translate-y-2 pointer-events-none"
     style="background:#22c55e">
    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
    <span id="note-toast-msg">Note added successfully</span>
</div>

@push('scripts')
<script>
    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(() => {
            showToast('Copied!', 'success');
        });
    }

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

    function showDuplicates() {
        window.location.href = '{{ route('admin.orders.index', ['duplicates' => '1']) }}';
    }

    function getSelectedIds() {
        return Array.from(document.querySelectorAll('input[name=selected]:checked')).map(el => el.value);
    }

    async function bulkUpdateStatus(status) {
        const selected = getSelectedIds();
        if (!selected.length) return;
        if (!confirm(`Change ${selected.length} orders to "${status}"?`)) return;
        const response = await fetch('/admin/orders/bulk-status', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
            },
            body: JSON.stringify({ ids: selected, status: status })
        });
        const data = await response.json();
        if (data.success) {
            showToast(`${selected.length} orders updated to ${status}`, 'success');
            setTimeout(() => window.location.reload(), 1000);
        }
    }

    function bulkExport() {
        const selected = getSelectedIds();
        if (!selected.length) return;
        const params = new URLSearchParams();
        selected.forEach(id => params.append('ids[]', id));
        window.open('/admin/orders/export-csv?' + params.toString());
    }

    function bulkPrint() {
        const selected = getSelectedIds();
        if (!selected.length) return;
        const params = new URLSearchParams();
        selected.forEach(id => params.append('ids[]', id));
        window.open('/admin/orders/bulk-print?' + params.toString());
    }

    async function bulkAssignCourier(courier) {
        const selected = getSelectedIds();
        if (!selected.length) return;
        if (!confirm(`Assign ${selected.length} orders to ${courier}?`)) return;
        const response = await fetch('/admin/orders/bulk-courier', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
            },
            body: JSON.stringify({ ids: selected, courier: courier })
        });
        const data = await response.json();
        if (data.success) {
            showToast(`${selected.length} orders assigned to ${courier}`, 'success');
            setTimeout(() => window.location.reload(), 1000);
        }
    }

    async function bulkAssignTag() {
        const selected = getSelectedIds();
        const tag = document.getElementById('bulkTag').value.trim();
        if (!selected.length || !tag) return;
        const response = await fetch('/admin/orders/bulk-tag', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
            },
            body: JSON.stringify({ ids: selected, tag: tag })
        });
        const data = await response.json();
        if (data.success) {
            showToast(`Tag "${tag}" assigned to ${selected.length} orders`, 'success');
            document.getElementById('bulkTag').value = '';
        }
    }

    function updateBulkSummary(selectedOrders) {
        let total = 0, paid = 0, due = 0;
        selectedOrders.forEach(id => {
            const row = document.querySelector(`tr[data-order-id="${id}"]`);
            if (row) {
                total += parseFloat(row.dataset.total || 0);
                paid  += parseFloat(row.dataset.paid  || 0);
                due   += parseFloat(row.dataset.due   || 0);
            }
        });
        const fmt = n => '৳' + n.toLocaleString();
        document.getElementById('bulk-total').textContent = fmt(total);
        document.getElementById('bulk-paid').textContent  = fmt(paid);
        document.getElementById('bulk-due').textContent   = fmt(due);
    }
</script>
@endpush

@endsection
