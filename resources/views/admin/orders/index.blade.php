@extends('layouts.admin')

@section('title', request('status') ? ucwords(str_replace('_', ' ', request('status'))) . ' Orders' : (request('cod_changed') ? 'COD Changed Orders' : 'All Orders'))

@section('page-title')
@if(request('status')){{ ucwords(str_replace('_', ' ', request('status'))) }} Orders
@elseif(request('cod_changed'))COD Changed Orders
@else Orders
@endif
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
    $hasFilters = collect(['search','payment_status','payment_method','source','created_range',
        'courier_range','courier','courier_status','district','category',
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
</div>

{{-- ── Filter Panel ─────────────────────────────────────────────────────────── --}}
<div x-data="{ filterOpen: false, loading: false }" class="mb-4">

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
                <svg x-show="loading" class="w-4 h-4 animate-spin text-primary" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
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

            <form method="GET" action="{{ route('admin.orders.index') }}" class="p-6 space-y-6"
                  x-on:submit="loading = true">

                {{-- ROW 1: Date Range Dropdowns --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Order Created At</label>
                        <select name="created_range" onchange="this.form.submit()"
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
                        <select name="courier_range" onchange="this.form.submit()"
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
                        <select name="status_range" onchange="this.form.submit()"
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
                        <select name="note_range" onchange="this.form.submit()"
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
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</label>
                        <select name="status" onchange="this.form.submit()"
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
                                <option value="{{ $val }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Order Source</label>
                        <select name="source" onchange="this.form.submit()"
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
                               x-data="{ timer: null }"
                               x-on:input="clearTimeout(timer); timer = setTimeout(() => $el.form.submit(), 800)"
                               class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-600
                                      bg-white dark:bg-gray-700 text-sm text-gray-900 dark:text-white
                                      placeholder-gray-400 dark:placeholder-gray-500
                                      focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary">
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Courier</label>
                        <select name="courier" onchange="this.form.submit()"
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
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Courier Status</label>
                        <select name="courier_status" onchange="this.form.submit()"
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
                        <select name="payment_status" onchange="this.form.submit()"
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
                        <select name="print_status" onchange="this.form.submit()"
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
                               x-data="{ timer: null }"
                               x-on:input="clearTimeout(timer); timer = setTimeout(() => $el.form.submit(), 800)"
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
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Product Category</label>
                        <select name="category" onchange="this.form.submit()"
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
                        <select name="utm_source" onchange="this.form.submit()"
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
                               x-data="{ timer: null }"
                               x-on:input="clearTimeout(timer); timer = setTimeout(() => $el.form.submit(), 1000)"
                               class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-600
                                      bg-white dark:bg-gray-700 text-sm text-gray-900 dark:text-white
                                      placeholder-gray-400 dark:placeholder-gray-500
                                      focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary">
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Amount Max (৳)</label>
                        <input type="number" name="amount_max" value="{{ request('amount_max') }}"
                               placeholder="99999" min="0"
                               x-data="{ timer: null }"
                               x-on:input="clearTimeout(timer); timer = setTimeout(() => $el.form.submit(), 1000)"
                               class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-600
                                      bg-white dark:bg-gray-700 text-sm text-gray-900 dark:text-white
                                      placeholder-gray-400 dark:placeholder-gray-500
                                      focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary">
                    </div>
                </div>

                {{-- ROW 5: Checkboxes --}}
                <div class="flex flex-wrap items-center gap-4">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="cod_changed" value="1"
                               {{ request('cod_changed') ? 'checked' : '' }}
                               onchange="this.form.submit()"
                               class="w-4 h-4 rounded border-gray-300 text-primary focus:ring-primary/30">
                        <span class="text-sm text-gray-600 dark:text-gray-400">COD Changed</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="duplicates" value="1"
                               {{ request('duplicates') ? 'checked' : '' }}
                               onchange="this.form.submit()"
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
    const checkboxes = document.querySelectorAll('.order-checkbox');
    checkboxes.forEach(cb => cb.checked = this.selectAll);
    if (this.selectAll) {
      this.selectedOrders = Array.from(checkboxes).map(el => el.value);
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
}" :class="selectedCount > 0 ? 'pb-20 md:pb-6' : 'pb-0'">
<div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">

    {{-- Table Header Bar --}}
    <div class="flex flex-col gap-2 px-4 py-3 border-b border-gray-100 dark:border-gray-700">
        {{-- Row 1: Title + results --}}
        <div class="flex items-center gap-2">
            <h3 class="text-sm font-bold text-gray-900 dark:text-white">Order List</h3>
            <span class="px-2 py-0.5 bg-primary/10 text-primary text-xs font-semibold rounded-full">
                {{ $orders->total() }} results
            </span>
        </div>
        {{-- Row 2: Search + Show entries --}}
        <div class="flex items-center gap-2">
            <form method="GET" action="{{ route('admin.orders.index') }}" class="flex items-center gap-2 flex-1">
                @foreach(request()->except(['search','page']) as $key => $val)
                    <input type="hidden" name="{{ $key }}" value="{{ $val }}">
                @endforeach
                <input type="text" name="search" value="{{ request('search') }}"
                    placeholder="Search order# or phone..."
                    class="flex-1 min-w-0 px-3 py-2 text-sm rounded-xl border border-gray-200
                           dark:border-gray-600 bg-white dark:bg-gray-700
                           text-gray-900 dark:text-white placeholder-gray-400
                           focus:outline-none focus:ring-2 focus:ring-primary/30">
                <button type="submit"
                    class="px-3 py-2 bg-primary text-white text-xs font-semibold rounded-xl flex-shrink-0">
                    Search
                </button>
            </form>
            <div class="relative flex-shrink-0">
                <select onchange="window.location.href='?per_page='+this.value+'&{{ http_build_query(request()->except('per_page', 'page')) }}'"
                    class="appearance-none pl-2 pr-6 py-2 text-xs rounded-xl border border-gray-200
                           dark:border-gray-600 bg-white dark:bg-gray-700
                           text-gray-900 dark:text-white cursor-pointer min-w-[52px]">
                    @foreach([10,25,50,100,150,200,250,300,500,1000,1500,2000] as $n)
                    <option value="{{ $n }}" {{ request('per_page', 10) == $n ? 'selected' : '' }}>{{ $n }}</option>
                    @endforeach
                </select>
                <svg class="absolute right-1.5 top-1/2 -translate-y-1/2 w-3 h-3 text-gray-400 pointer-events-none"
                     fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                </svg>
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
        <thead>
        <tr class="bg-gray-50 dark:bg-gray-800 text-xs text-gray-500 dark:text-gray-400">
            <th class="px-3 py-3 text-left">Status</th>
            <th class="px-3 py-3"><input type="checkbox" id="selectAll" @click="toggleAll()" class="w-4 h-4 rounded border-gray-300 text-primary cursor-pointer"></th>
            <th class="px-3 py-3 text-left">Notes</th>
            <th class="px-3 py-3 text-left">ID & Products ↕</th>
            <th class="px-3 py-3 text-left">Name & Number</th>
            <th class="px-3 py-3 text-left">Date</th>
            <th class="px-3 py-3 text-left">Address</th>
            <th class="px-2 py-3 w-8"></th>
            <th class="px-3 py-3 text-left">Courier</th>
            <th class="px-3 py-3 text-left">Summary</th>
            <th class="px-3 py-3 text-left">Employee</th>
        </tr>
        </thead>
        <tbody>
        @forelse($orders as $sl => $order)
        <tr class="border-b dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition"
            data-order-id="{{ $order->id }}"
            data-total="{{ $order->total ?? 0 }}"
            data-paid="{{ $order->paid_amount ?? 0 }}"
            data-due="{{ ($order->total ?? 0) - ($order->paid_amount ?? 0) }}">

            {{-- Status --}}
            <td class="px-3 py-3 align-top">
                <a href="{{ route('admin.orders.show', $order->id) }}"
                    class="inline-block px-2 py-1 rounded-lg text-xs font-semibold
                    @if($order->status == 'pending') bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400
                    @elseif($order->status == 'confirmed') bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400
                    @elseif($order->status == 'delivered' || $order->status == 'completed') bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400
                    @elseif($order->status == 'cancelled' || $order->status == 'failed') bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400
                    @elseif($order->status == 'processing') bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400
                    @elseif($order->status == 'hold') bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400
                    @else bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300
                    @endif">
                    {{ ucfirst($order->status) }}
                </a>
                <div class="text-xs text-gray-400 mt-1">sl: {{ $sl + 1 }}</div>
            </td>

            {{-- Checkbox --}}
            <td class="px-3 py-3 align-top text-center">
                <input type="checkbox" name="selected_orders[]" value="{{ $order->id }}"
                    class="order-checkbox rounded"
                    x-on:change="
                        const id = '{{ $order->id }}';
                        if ($event.target.checked) {
                            if (!selectedOrders.includes(id)) selectedOrders.push(id);
                        } else {
                            selectedOrders = selectedOrders.filter(i => i !== id);
                        }
                        updateBulkSummary(selectedOrders);
                    ">
            </td>

            {{-- Notes --}}
            <td class="px-3 py-3 align-top text-center">
                <button type="button" onclick="quickAddNote({{ $order->id }})"
                    class="w-6 h-6 flex items-center justify-center bg-blue-100 dark:bg-blue-900/30 text-blue-500 rounded-lg hover:bg-blue-200 mx-auto text-xs">+</button>
                @if($order->orderNotes && $order->orderNotes->count() > 0)
                <span class="text-xs text-gray-400 mt-0.5 block">{{ $order->orderNotes->count() }}</span>
                @endif
            </td>

            {{-- ID & Products --}}
            <td class="px-3 py-3 align-top">
                <a href="{{ route('admin.orders.show', $order->id) }}" class="text-blue-600 dark:text-blue-400 font-bold text-sm hover:underline">
                    #{{ $order->id }}
                </a>
                @foreach($order->items->take(2) as $item)
                <div class="flex items-center gap-2 mt-1">
                    @if($item->product && $item->product->images && $item->product->images->first())
                    <img src="{{ asset('storage/'.$item->product->images->first()->image_path) }}" class="w-8 h-8 object-cover rounded-lg border dark:border-gray-700" alt="">
                    @else
                    <div class="w-8 h-8 bg-gray-100 dark:bg-gray-700 rounded-lg flex items-center justify-center text-gray-300 text-xs">?</div>
                    @endif
                    <div>
                        <div class="text-xs text-gray-700 dark:text-gray-300 leading-tight max-w-[160px] truncate">{{ $item->qty }}x {{ $item->product_name ?? $item->product->name ?? 'Unknown Product' }}</div>
                        <div class="text-xs font-bold text-[#1B2A4A] dark:text-white">৳{{ number_format($item->unit_price, 0) }}Tk</div>
                    </div>
                </div>
                @endforeach
                @if($order->items->count() > 2)
                <div class="text-xs text-gray-400 mt-1">+{{ $order->items->count() - 2 }} more</div>
                @endif
                <a href="{{ route('admin.orders.show', $order->id) }}" class="text-xs text-[#F59E0B] hover:underline mt-1 block">View Stock</a>
            </td>

            {{-- Name & Number --}}
            <td class="px-3 py-3 align-top">
                <div class="flex items-center gap-1 mb-1">
                    <span class="font-semibold text-gray-800 dark:text-white text-sm">{{ $order->shipping_name ?? 'N/A' }}</span>
                </div>
                <div class="flex items-center gap-1">
                    <span class="bg-gray-900 dark:bg-gray-700 text-white text-xs px-2 py-0.5 rounded-lg font-mono">{{ $order->shipping_phone ?? 'N/A' }}</span>
                    <a href="https://wa.me/880{{ ltrim($order->shipping_phone ?? '', '0') }}" target="_blank" class="w-6 h-6 flex items-center justify-center bg-green-500 text-white rounded-lg text-xs hover:bg-green-600">💬</a>
                    <a href="tel:{{ $order->shipping_phone ?? '' }}" class="w-6 h-6 flex items-center justify-center bg-blue-500 text-white rounded-lg text-xs hover:bg-blue-600">📞</a>
                </div>
                <div class="text-xs text-gray-400 mt-1">Source: {{ $order->source ?? 'Direct' }}</div>
            </td>

            {{-- Date --}}
            <td class="px-3 py-3 align-top text-xs text-gray-500 dark:text-gray-400">
                <div>C:{{ $order->created_at->format('d/m/Y - h:ia') }}</div>
                <div>U:{{ $order->updated_at->format('d/m/Y - h:ia') }}</div>
                @if($order->assignedTo)
                <div class="mt-1">By: <span class="font-medium text-gray-700 dark:text-gray-300">{{ $order->assignedTo->name }}</span></div>
                @endif
            </td>

            {{-- Address --}}
            <td class="px-3 py-3 align-top">
                <div class="flex items-start gap-1">
                    <span class="text-xs text-gray-700 dark:text-gray-300 max-w-[150px]">{{ $order->shipping_address ?? 'N/A' }}{{ $order->shipping_district ? ', '.$order->shipping_district : '' }}</span>
                </div>
            </td>

            {{-- Copy --}}
            <td class="px-2 py-3 align-top text-center w-8">
                <button type="button" onclick="copyOrderDetails(
                    '{{ addslashes($order->shipping_name ?? '') }}',
                    '{{ $order->shipping_phone ?? '' }}',
                    '{{ addslashes(($order->shipping_address ?? '').($order->shipping_district ? ', '.$order->shipping_district : '')) }}',
                    '{{ $order->id }}',
                    '{{ number_format($order->total ?? 0, 0) }}',
                    '{{ $order->payment_method ?? 'COD' }}'
                )" class="w-7 h-7 flex items-center justify-center bg-gray-100 dark:bg-gray-700 text-gray-500 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg text-xs" title="Copy details">📋</button>
            </td>

            {{-- Courier --}}
            <td class="px-3 py-3 align-top text-xs">
                @php
                    $courierName = $order->shipment->courier_name ?? 'New';
                    $customerTotal = \App\Models\Order::where('shipping_phone', $order->shipping_phone)->count();
                    $customerSuccess = \App\Models\Order::where('shipping_phone', $order->shipping_phone)->whereIn('status',['delivered','completed'])->count();
                    $customerFailed = \App\Models\Order::where('shipping_phone', $order->shipping_phone)->whereIn('status',['cancelled','failed','returned'])->count();
                    $percent = $customerTotal > 0 ? round(($customerSuccess/$customerTotal)*100) : 0;
                @endphp
                <div class="text-gray-600 dark:text-gray-400 mb-1">
                    To: {{ $customerTotal }} Co: {{ $customerSuccess }}
                    <span class="px-1 py-0.5 rounded text-white text-xs {{ $order->shipment ? 'bg-[#1B2A4A]' : 'bg-gray-400' }}">{{ $courierName }}</span>
                </div>
                <div class="w-24 h-1.5 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden mb-1">
                    <div class="h-full rounded-full {{ $percent >= 70 ? 'bg-green-500' : ($percent >= 40 ? 'bg-yellow-500' : 'bg-red-500') }}"
                        style="width: {{ $percent }}%"></div>
                </div>
                <div class="text-gray-500 dark:text-gray-400">
                    Su: {{ $customerSuccess }} Fa: {{ $customerFailed }}
                </div>
            </td>

            {{-- Summary --}}
            <td class="px-3 py-3 align-top text-xs">
                <div class="text-gray-700 dark:text-gray-300">Total: <span class="font-bold">৳{{ number_format($order->total ?? 0, 0) }}</span></div>
                <div class="text-green-600">Less: <span class="font-bold">৳{{ number_format($order->discount ?? 0, 0) }}</span></div>
                <div class="text-blue-600">Paid: <span class="font-bold">৳{{ number_format($order->paid_amount ?? 0, 0) }}</span></div>
                <div class="text-red-500">Due: <span class="font-bold">৳{{ number_format(($order->total ?? 0) - ($order->paid_amount ?? 0), 0) }}</span></div>
            </td>

            {{-- Employee --}}
            <td class="px-3 py-3 align-top text-xs text-gray-600 dark:text-gray-400">
                {{ $order->assignedTo->name ?? 'N/A' }}
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="11" class="px-6 py-16 text-center">
                <div class="flex flex-col items-center gap-3">
                    <div class="w-14 h-14 rounded-2xl bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                        <svg class="w-7 h-7 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
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

    <p class="text-xs text-gray-400 text-center py-2 sm:hidden">← Scroll to see more →</p>

    {{-- Pagination --}}
    <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700 flex flex-col sm:flex-row items-center justify-between gap-3">
        <p class="text-xs text-gray-500 dark:text-gray-400 order-2 sm:order-1">
            Showing {{ $orders->firstItem() ?? 0 }} to {{ $orders->lastItem() ?? 0 }}
            of {{ $orders->total() }} orders
        </p>
        <div class="flex items-center gap-1 order-1 sm:order-2">
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
            rounded-none md:rounded-2xl shadow-2xl
            border-t md:border border-gray-200 dark:border-gray-700
            px-3 py-2 md:px-6 md:py-4
            md:min-w-[700px] md:max-w-4xl overflow-visible">

  <!-- Mobile Layout -->
  <div class="flex md:hidden items-center justify-between gap-2 w-full">

    <!-- Left: count -->
    <div class="flex items-center gap-2 flex-shrink-0">
      <div class="w-6 h-6 rounded-lg bg-primary flex items-center justify-center">
        <span class="text-white text-xs font-bold" x-text="selectedCount"></span>
      </div>
    </div>

    <!-- Right: action buttons -->
    <div class="flex items-center gap-1 overflow-visible flex-1 justify-end">

      <!-- Status dropdown -->
      <div class="relative overflow-visible flex-shrink-0" x-data="{ o: false }">
        <button @click="o = !o; $event.stopPropagation()"
          class="px-2.5 py-1.5 rounded-lg bg-primary text-white text-xs font-semibold whitespace-nowrap">
          Status ▾
        </button>
        <div x-show="o"
             x-on:click.away="o = false"
             x-transition
             style="position: absolute; bottom: 100%; margin-bottom: 8px; left: 0; z-index: 9999;"
             class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700
                    shadow-2xl w-44 max-h-56 overflow-y-auto">
          @foreach(['pending','confirmed','processing','hold','shipped','delivered','completed','cancelled','pending_return','returned'] as $s)
          <button @click="bulkUpdateStatus('{{ $s }}'); o = false"
            class="w-full px-3 py-2.5 text-left text-xs font-medium
                   text-gray-700 dark:text-gray-300
                   hover:bg-gray-50 dark:hover:bg-gray-700 transition">
            {{ ucfirst(str_replace('_', ' ', $s)) }}
          </button>
          @endforeach
        </div>
      </div>

      <!-- Print -->
      <button @click="bulkPrint()"
        class="px-2.5 py-1.5 rounded-xl bg-teal-600 text-white
               text-xs font-semibold flex-shrink-0">
        Print
      </button>

      <!-- Export CSV -->
      <button @click="bulkExport()"
        class="px-2.5 py-1.5 rounded-xl bg-green-600 text-white
               text-xs font-semibold flex-shrink-0">
        CSV
      </button>

      <!-- Courier dropdown -->
      <div class="relative overflow-visible flex-shrink-0" x-data="{ o: false }">
        <button @click="o = !o; $event.stopPropagation()"
          class="px-2.5 py-1.5 rounded-lg bg-purple-600 text-white
                 text-xs font-semibold whitespace-nowrap">
          Courier ▾
        </button>
        <div x-show="o"
             x-on:click.away="o = false"
             x-transition
             style="position: absolute; bottom: 100%; margin-bottom: 8px; right: 0; z-index: 9999;"
             class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700
                    shadow-2xl w-36">
          @foreach(['pathao','steadfast','carrybe','redx','paperfly','manual'] as $c)
          <button @click="bulkAssignCourier('{{ $c }}'); o = false"
            class="w-full px-3 py-2.5 text-left text-xs font-medium
                   text-gray-700 dark:text-gray-300
                   hover:bg-gray-50 dark:hover:bg-gray-700 transition">
            {{ ucfirst($c) }}
          </button>
          @endforeach
        </div>
      </div>

      <!-- Clear -->
      <button @click="selectedOrders = []; selectAll = false; updateBulkSummary([])"
        class="w-8 h-8 flex items-center justify-center rounded-xl
               bg-gray-200 dark:bg-gray-700 text-gray-600
               dark:text-gray-300 flex-shrink-0 text-sm font-bold">
        ✕
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
                      shadow-xl z-[999] overflow-hidden w-44">
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
                    shadow-xl z-[999] overflow-hidden w-36">
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

    function copyText(text) {
        copyToClipboard(text);
    }

    function copyOrderDetails(name, phone, address, orderId, total, payment) {
        const text = `নাম: ${name}\nফোন: ${phone}\nঠিকানা: ${address}\nঅর্ডার: #${orderId}\nমোট: ৳${total}\nপেমেন্ট: ${payment}`;
        navigator.clipboard.writeText(text).then(() => showToast('Order details copied!', 'success'));
    }

    function quickAddNote(orderId) {
        const note = prompt('Add note for order #' + orderId + ':');
        if (!note || !note.trim()) return;
        fetch('/admin/orders/' + orderId + '/notes', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify({note_text: note.trim(), type: 'order'})
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                showToast('Note added!', 'success');
                const btn = document.querySelector('[onclick="quickAddNote(' + orderId + ')"]');
                if (btn) {
                    const countEl = btn.nextElementSibling;
                    if (countEl) {
                        countEl.textContent = parseInt(countEl.textContent || 0) + 1;
                    } else {
                        const span = document.createElement('span');
                        span.className = 'text-xs text-gray-400 mt-0.5 block';
                        span.textContent = '1';
                        btn.parentNode.appendChild(span);
                    }
                }
            } else {
                showToast('Failed to add note', 'error');
            }
        })
        .catch(() => showToast('Error adding note', 'error'));
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
        return Array.from(document.querySelectorAll('.order-checkbox:checked')).map(el => el.value);
    }

    window.getSelectedOrders = function() {
        return Array.from(document.querySelectorAll('.order-checkbox:checked')).map(el => el.value);
    };

    async function bulkUpdateStatus(status) {
        const selected = window.getSelectedOrders();
        if (!selected.length) {
            showToast('No orders selected', 'error');
            return;
        }
        if (!confirm(`Change ${selected.length} order(s) to "${status}"?`)) return;
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
        } else {
            showToast('Update failed', 'error');
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
        const selected = window.getSelectedOrders();
        if (!selected.length) {
            showToast('No orders selected', 'error');
            return;
        }
        if (!confirm(`Assign ${selected.length} order(s) to ${courier}?`)) return;
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
        } else {
            showToast('Assignment failed', 'error');
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
