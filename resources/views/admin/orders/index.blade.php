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
    <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">

        {{-- Row 1: Title + ID-only search --}}
        <div class="flex items-center justify-between gap-4 mb-2">
            <h3 class="text-sm font-bold text-gray-900 dark:text-white">Order List</h3>
            <form method="GET" action="{{ route('admin.orders.index') }}" class="flex items-center gap-1 flex-shrink-0">
                @foreach(request()->except(['id_search','search','page']) as $key => $val)
                    <input type="hidden" name="{{ $key }}" value="{{ $val }}">
                @endforeach
                <span class="inline-flex items-center px-2 py-1.5 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-200 text-xs font-bold rounded-l-lg border border-gray-300 dark:border-gray-500 border-r-0 leading-none">ID</span>
                <input type="text" name="id_search" value="{{ request('id_search') }}"
                    placeholder="Search by Only ID"
                    class="w-40 px-3 py-1.5 text-xs rounded-r-lg border border-gray-300 dark:border-gray-500
                           bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400
                           focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary">
            </form>
        </div>

        {{-- Row 2: Show entries (left) + Search (right) --}}
        <div class="flex items-center justify-between gap-4">
            <div class="flex items-center gap-2 flex-shrink-0">
                <span class="text-xs text-gray-500 dark:text-gray-400">Show</span>
                <div class="relative">
                    <select onchange="window.location.href='?per_page='+this.value+'&{{ http_build_query(request()->except('per_page', 'page')) }}'"
                        class="appearance-none pl-2 pr-7 py-1.5 text-xs rounded-lg border border-gray-300 dark:border-gray-500
                               bg-white dark:bg-gray-700 text-gray-900 dark:text-white cursor-pointer">
                        @foreach([10,25,50,100,150,200,250,300,500,1000,1500,2000] as $n)
                        <option value="{{ $n }}" {{ request('per_page', 10) == $n ? 'selected' : '' }}>{{ $n }}</option>
                        @endforeach
                    </select>
                    <svg class="absolute right-2 top-1/2 -translate-y-1/2 w-3 h-3 text-gray-400 pointer-events-none"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                    </svg>
                </div>
                <span class="text-xs text-gray-500 dark:text-gray-400">entries</span>
            </div>
            <form method="GET" action="{{ route('admin.orders.index') }}" class="flex items-center gap-2">
                @foreach(request()->except(['search','id_search','page']) as $key => $val)
                    <input type="hidden" name="{{ $key }}" value="{{ $val }}">
                @endforeach
                <label class="text-xs text-gray-500 dark:text-gray-400 flex-shrink-0">Search:</label>
                <input type="text" name="search" value="{{ request('search') }}"
                    placeholder="Order# or phone..."
                    class="w-44 px-3 py-1.5 text-xs rounded-lg border border-gray-300 dark:border-gray-500
                           bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400
                           focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary">
            </form>
        </div>

        {{-- Row 3: Showing X to Y of Z --}}
        <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
            Showing {{ $orders->firstItem() ?? 0 }} to {{ $orders->lastItem() ?? 0 }} of {{ number_format($orders->total()) }} entries
        </div>

    </div>

    {{-- Table --}}
    <div class="overflow-x-auto w-full">
        <table class="w-full text-sm min-w-[1400px] border-collapse">
        <thead>
        <tr class="bg-gray-50 dark:bg-gray-800 text-xs text-gray-500 dark:text-gray-400 border-b-2 border-gray-200 dark:border-gray-700">
            <th class="px-3 py-3 text-left border-r border-gray-200 dark:border-gray-700 w-[100px]">Status</th>
            <th class="px-3 py-3 border-r border-gray-200 dark:border-gray-700 w-[40px]"><input type="checkbox" id="selectAll" @click="toggleAll()" class="w-4 h-4 rounded border-gray-300 text-primary cursor-pointer"></th>
            <th class="px-3 py-3 text-left border-r border-gray-200 dark:border-gray-700 w-[60px]">Notes</th>
            <th class="px-3 py-3 text-left border-r border-gray-200 dark:border-gray-700 w-[200px]">ID & Products ↕</th>
            <th class="px-3 py-3 text-left border-r border-gray-200 dark:border-gray-700 w-[200px]">Name & Number</th>
            <th class="px-3 py-3 text-left border-r border-gray-200 dark:border-gray-700 w-[160px]">Date</th>
            <th class="px-3 py-3 text-left border-r border-gray-200 dark:border-gray-700 w-[180px]">Address</th>
            <th class="px-3 py-3 border-r border-gray-200 dark:border-gray-700 w-[40px]"></th>
            <th class="px-3 py-3 text-left border-r border-gray-200 dark:border-gray-700 w-[150px]">Courier</th>
            <th class="px-3 py-3 text-left border-r border-gray-200 dark:border-gray-700 w-[140px]">Summary</th>
            <th class="px-3 py-3 text-left w-[80px]">Employee</th>
        </tr>
        </thead>
        <tbody>
        @php
            $phoneCounts = $orders->countBy(fn($o) => $o->shipping_phone);
            $seenPhones  = [];
        @endphp
        @forelse($orders as $sl => $order)
        @php
            $phone = $order->shipping_phone;
            $isDuplicate = ($phoneCounts[$phone] ?? 1) > 1 && in_array($phone, $seenPhones);
            $seenPhones[] = $phone;
        @endphp
        <tr class="border-b dark:border-gray-800 transition {{ $isDuplicate ? 'bg-yellow-100 dark:bg-yellow-900/25 hover:bg-yellow-200 dark:hover:bg-yellow-900/35' : 'hover:bg-gray-50 dark:hover:bg-gray-800/50' }}"
            data-order-id="{{ $order->id }}"
            data-total="{{ $order->total ?? 0 }}"
            data-paid="{{ $order->paid_amount ?? 0 }}"
            data-due="{{ ($order->total ?? 0) - ($order->paid_amount ?? 0) }}">

            {{-- Status --}}
            <td class="px-3 py-3 align-top w-[100px] border-r border-gray-100 dark:border-gray-800">
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
            <td class="px-3 py-3 align-top text-center w-[40px] border-r border-gray-100 dark:border-gray-800">
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
            <td class="px-3 py-3 align-top text-center w-[60px] border-r border-gray-100 dark:border-gray-800">
                {{-- Add note button --}}
                <button type="button" onclick="quickAddNote({{ $order->id }})"
                    class="w-5 h-5 flex items-center justify-center bg-blue-100 dark:bg-blue-900/30 text-blue-500 rounded-md hover:bg-blue-200 mx-auto"
                    title="Add note">
                    <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                </button>
                {{-- Note view tooltip icon (only if notes exist) --}}
                @if($order->orderNotes && $order->orderNotes->count() > 0)
                @php $lastNote = $order->orderNotes->sortByDesc('created_at')->first(); @endphp
                <button type="button"
                    class="note-tip-btn mt-0.5 w-5 h-5 flex items-center justify-center bg-amber-100 dark:bg-amber-900/30 text-amber-600 rounded-md hover:bg-amber-200 mx-auto"
                    data-count="{{ $order->orderNotes->count() }}"
                    data-text="{{ e(Str::limit($lastNote->note_text ?? '', 220)) }}"
                    data-date="{{ $lastNote->created_at->format('d/m/Y h:ia') }}">
                    <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </button>
                <span class="note-count-span text-[10px] text-amber-500 font-semibold leading-none">{{ $order->orderNotes->count() }}</span>
                @endif
                @if($isDuplicate)
                <span class="inline-block mt-1 px-1 py-0.5 bg-yellow-400 text-yellow-900 text-[9px] font-bold rounded leading-none">DUP</span>
                @endif
            </td>

            {{-- ID & Products --}}
            <td class="px-3 py-3 align-top w-[200px] border-r border-gray-100 dark:border-gray-800">
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
                        <div class="text-xs text-gray-700 dark:text-gray-300 leading-tight max-w-[180px] truncate">{{ $item->qty }}x {{ $item->product_name ?? $item->product->name ?? 'Unknown Product' }}</div>
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
            <td class="px-3 py-3 align-top w-[200px] border-r border-gray-100 dark:border-gray-800">
                <div class="flex items-center gap-1 mb-1">
                    <a href="{{ route('admin.orders.show', $order->id) }}" class="font-semibold text-gray-800 dark:text-white text-sm hover:text-[#1B2A4A] dark:hover:text-[#F59E0B] hover:underline">{{ $order->shipping_name ?? 'N/A' }}</a>
                </div>
                <div class="flex items-center gap-1">
                    <span class="bg-gray-900 dark:bg-gray-700 text-white text-xs px-2 py-0.5 rounded-lg font-mono">{{ $order->shipping_phone ?? 'N/A' }}</span>
                    <a href="https://wa.me/880{{ ltrim($order->shipping_phone ?? '', '0') }}" target="_blank" class="w-6 h-6 flex items-center justify-center bg-green-500 text-white rounded-md hover:bg-green-600">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                    </a>
                    <a href="tel:{{ $order->shipping_phone ?? '' }}" class="w-6 h-6 flex items-center justify-center bg-blue-500 text-white rounded-md hover:bg-blue-600">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.948V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 7V5z"/></svg>
                    </a>
                </div>
                <div class="text-xs text-gray-400 mt-1">Source: {{ $order->source ?? 'Direct' }}</div>
            </td>

            {{-- Date --}}
            <td class="px-3 py-3 align-top text-xs text-gray-500 dark:text-gray-400 w-[160px] border-r border-gray-100 dark:border-gray-800">
                <div>C:{{ $order->created_at->format('d/m/Y - h:ia') }}</div>
                <div>U:{{ $order->updated_at->format('d/m/Y - h:ia') }}</div>
                @if($order->assignedTo)
                <div class="mt-1">By: <span class="font-medium text-gray-700 dark:text-gray-300">{{ $order->assignedTo->name }}</span></div>
                @endif
            </td>

            {{-- Address --}}
            <td class="px-3 py-3 align-top w-[180px] border-r border-gray-100 dark:border-gray-800">
                <div class="flex items-start gap-1">
                    <span class="text-xs text-gray-700 dark:text-gray-300 break-words">{{ $order->shipping_address ?? 'N/A' }}{{ $order->shipping_district ? ', '.$order->shipping_district : '' }}</span>
                </div>
            </td>

            {{-- Copy --}}
            <td class="px-2 py-3 align-top text-center w-[40px] border-r border-gray-100 dark:border-gray-800">
                <button type="button" onclick="copyOrderDetails(
                    '{{ addslashes($order->shipping_name ?? '') }}',
                    '{{ $order->shipping_phone ?? '' }}',
                    '{{ addslashes(($order->shipping_address ?? '').($order->shipping_district ? ', '.$order->shipping_district : '')) }}',
                    '{{ $order->id }}',
                    '{{ number_format($order->total ?? 0, 0) }}',
                    '{{ $order->payment_method ?? 'COD' }}'
                )" class="w-6 h-6 flex items-center justify-center bg-gray-100 dark:bg-gray-700 text-gray-500 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-md" title="Copy details">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                </button>
            </td>

            {{-- Courier --}}
            <td class="px-3 py-3 align-top text-xs w-[150px] border-r border-gray-100 dark:border-gray-800">
                @php
                    $courierName = $order->shipment->courier_name ?? 'New';
                    $customerTotal = \App\Models\Order::where('shipping_phone', $order->shipping_phone)->count();
                    $customerSuccess = \App\Models\Order::where('shipping_phone', $order->shipping_phone)->whereIn('status',['delivered','completed'])->count();
                    $customerFailed = \App\Models\Order::where('shipping_phone', $order->shipping_phone)->whereIn('status',['cancelled','failed','returned'])->count();
                    $percent = $customerTotal > 0 ? round(($customerSuccess/$customerTotal)*100) : 0;
                    $shipmentStatusColors = [
                        'submitted' => 'bg-blue-500',
                        'assigned'  => 'bg-yellow-500',
                        'pending'   => 'bg-orange-400',
                        'delivered' => 'bg-green-500',
                        'returned'  => 'bg-red-500',
                        'cancelled' => 'bg-gray-500',
                    ];
                    $shipmentStatusColor = $shipmentStatusColors[$order->shipment?->status ?? ''] ?? 'bg-gray-400';
                @endphp
                <div class="text-gray-600 dark:text-gray-400 mb-1">
                    To: {{ $customerTotal }} Co: {{ $customerSuccess }}
                    <span class="px-1 py-0.5 rounded text-white text-xs {{ $order->shipment ? 'bg-[#1B2A4A]' : 'bg-gray-400' }}">{{ ucfirst($courierName) }}</span>
                </div>
                <div class="w-24 h-1.5 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden mb-1">
                    <div class="h-full rounded-full {{ $percent >= 70 ? 'bg-green-500' : ($percent >= 40 ? 'bg-yellow-500' : 'bg-red-500') }}"
                        style="width: {{ $percent }}%"></div>
                </div>
                <div class="text-gray-500 dark:text-gray-400 mb-1">
                    Su: {{ $customerSuccess }} Fa: {{ $customerFailed }}
                </div>
                @if($order->shipment)
                <div class="border-t border-gray-100 dark:border-gray-700 pt-1 mt-1 space-y-0.5">
                    @if($order->shipment->tracking_code)
                    <div class="text-gray-700 dark:text-gray-300 font-medium break-all flex items-start gap-1">
                        <span>{{ ucfirst($order->shipment->courier_name) }} Inv: {{ $order->shipment->tracking_code }}@if($order->shipment->consignment_id) - {{ $order->shipment->consignment_id }}@endif</span>
                        @if($order->shipment->tracking_link)
                        <a href="{{ $order->shipment->tracking_link }}" target="_blank"
                           class="flex-shrink-0 w-5 h-5 flex items-center justify-center bg-blue-100 dark:bg-blue-900/30 text-blue-600 rounded hover:bg-blue-200 mt-0.5" title="Track">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        </a>
                        @endif
                    </div>
                    @endif
                    <div class="flex items-center gap-1">
                        <span class="px-1 py-0.5 rounded text-white text-[10px] {{ $shipmentStatusColor }}">{{ ucfirst($order->shipment->status) }}</span>
                        @if($order->shipment->delivery_fee > 0)
                        <span class="text-gray-500 dark:text-gray-400">৳{{ number_format($order->shipment->delivery_fee, 0) }}</span>
                        @endif
                    </div>
                    <div class="text-gray-400 dark:text-gray-500 text-[10px]">
                        {{ $order->shipment->created_at->format('d/m/Y g:ia') }}
                    </div>
                </div>
                @endif
            </td>

            {{-- Summary --}}
            <td class="px-3 py-3 align-top text-xs w-[140px] border-r border-gray-100 dark:border-gray-800">
                <div class="text-gray-700 dark:text-gray-300">Total: <span class="font-bold">৳{{ number_format($order->total ?? 0, 0) }}</span></div>
                <div class="text-green-600">Less: <span class="font-bold">৳{{ number_format($order->discount ?? 0, 0) }}</span></div>
                <div class="text-blue-600">Paid: <span class="font-bold">৳{{ number_format($order->paid_amount ?? 0, 0) }}</span></div>
                <div class="text-red-500">Due: <span class="font-bold">৳{{ number_format(($order->total ?? 0) - ($order->paid_amount ?? 0), 0) }}</span></div>
            </td>

            {{-- Employee --}}
            <td class="px-3 py-3 align-top text-xs text-gray-600 dark:text-gray-400 w-[80px]">
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
    <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700 flex justify-center">
        {{ $orders->links('pagination::tailwind') }}
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

      <!-- Courier Modal Trigger (mobile) -->
      <button onclick="openBulkCourierModal()"
        class="px-2.5 py-1.5 rounded-lg bg-purple-600 text-white
               text-xs font-semibold whitespace-nowrap flex-shrink-0">
        Courier
      </button>

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
      <button onclick="openBulkCourierModal()"
        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl
               bg-purple-600 text-white text-xs font-semibold hover:bg-purple-700 transition">
        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
        </svg>
        Submit Courier
      </button>

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

{{-- Quick Add Note Modal --}}
<div id="quickNoteModal"
     class="fixed inset-0 bg-black/50 z-[9999] hidden items-center justify-center p-4"
     onclick="if(event.target===this) closeQuickNoteModal()">
    <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-xl w-full max-w-md p-6">
        <div class="flex items-center justify-between mb-4">
            <h4 class="font-semibold text-[#1B2A4A] dark:text-white">Add Note — Order #<span id="quickNoteOrderId"></span></h4>
            <button type="button" onclick="closeQuickNoteModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 text-2xl leading-none">&times;</button>
        </div>
        <textarea id="quickNoteTextarea" rows="4" placeholder="Write note here..."
            class="w-full rounded-xl border border-gray-200 dark:border-gray-700 px-4 py-2 text-sm bg-white dark:bg-gray-800 dark:text-white resize-none focus:outline-none focus:ring-2 focus:ring-[#1B2A4A]"></textarea>
        <div class="flex gap-3 mt-4">
            <button type="button" onclick="submitQuickNote()"
                class="flex-1 bg-[#1B2A4A] text-white py-2 rounded-xl text-sm font-medium hover:bg-[#243a63] transition">
                Save Note
            </button>
            <button type="button" onclick="closeQuickNoteModal()"
                class="px-4 py-2 border border-gray-200 dark:border-gray-600 rounded-xl text-sm text-gray-700 dark:text-white hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                Cancel
            </button>
        </div>
    </div>
</div>

{{-- AJAX note toast --}}
<div id="note-toast"
     class="fixed bottom-5 right-5 z-50 flex items-center gap-2 px-4 py-2.5 rounded-lg shadow-lg text-sm font-medium text-white transition-all duration-300 opacity-0 translate-y-2 pointer-events-none"
     style="background:#22c55e">
    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
    <span id="note-toast-msg">Note added successfully</span>
</div>

{{-- Bulk Courier Modal --}}
<div id="bulkCourierModal" class="hidden fixed inset-0 z-[10000] flex items-center justify-center p-4"
     onclick="if(event.target===this) closeBulkCourierModal()">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>
    <div class="relative w-full max-w-md bg-white dark:bg-gray-900 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        {{-- Header --}}
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-gray-800">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-xl bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
                    <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Bulk Courier Submit</h3>
                    <p class="text-xs text-gray-400"><span id="bulkCourierCount">0</span> টি order selected</p>
                </div>
            </div>
            <button onclick="closeBulkCourierModal()"
                    class="w-8 h-8 flex items-center justify-center rounded-full text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        {{-- Body --}}
        <div class="px-5 py-4 space-y-4">
            {{-- Courier Selection --}}
            <div>
                <label class="text-xs font-semibold text-gray-600 dark:text-gray-400 mb-2 block">Courier নির্বাচন করুন <span class="text-red-500">*</span></label>
                <div class="grid grid-cols-3 gap-2" id="bulkCourierBtnGroup">
                    @foreach(['pathao'=>'Pathao','steadfast'=>'Steadfast','carrybe'=>'CarryBe','redx'=>'RedX','paperfly'=>'Paperfly','manual'=>'Manual'] as $key => $label)
                    <button type="button"
                        onclick="selectBulkCourier('{{ $key }}')"
                        data-bulk-courier="{{ $key }}"
                        class="bulk-courier-btn flex flex-col items-center justify-center gap-1 px-2 py-3 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-xs font-semibold text-gray-700 dark:text-gray-300 hover:border-purple-400 hover:text-purple-600 transition-all">
                        @if($key === 'manual')
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        @else
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>
                        @endif
                        {{ $label }}
                    </button>
                    @endforeach
                </div>
                <input type="hidden" id="bulkSelectedCourier" value="">
            </div>

            {{-- Warning --}}
            <div class="flex items-start gap-2 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl px-3 py-2.5">
                <svg class="w-4 h-4 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                <p class="text-xs text-amber-700 dark:text-amber-400"><span id="bulkCourierWarning">0</span> টি order এ courier assign হবে। প্রতিটি order এর নিজস্ব shipping charge auto-use হবে।</p>
            </div>
        </div>
        {{-- Footer --}}
        <div class="flex items-center justify-end gap-2 px-5 py-3 border-t border-gray-100 dark:border-gray-800">
            <button onclick="closeBulkCourierModal()"
                    class="px-4 py-2 rounded-lg text-sm font-medium text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">
                Cancel
            </button>
            <button onclick="submitBulkCourier()"
                    id="bulkCourierSubmitBtn"
                    class="px-5 py-2 rounded-lg text-sm font-semibold text-white bg-purple-600 hover:bg-purple-700 transition-colors flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                Submit All
            </button>
        </div>
    </div>
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

    let _quickNoteOrderId = null;

    function quickAddNote(orderId) {
        _quickNoteOrderId = orderId;
        document.getElementById('quickNoteOrderId').textContent = orderId;
        document.getElementById('quickNoteTextarea').value = '';
        const m = document.getElementById('quickNoteModal');
        m.classList.remove('hidden');
        m.classList.add('flex');
        setTimeout(() => document.getElementById('quickNoteTextarea').focus(), 50);
    }

    function closeQuickNoteModal() {
        const m = document.getElementById('quickNoteModal');
        m.classList.add('hidden');
        m.classList.remove('flex');
        _quickNoteOrderId = null;
    }

    function submitQuickNote() {
        const note = document.getElementById('quickNoteTextarea').value.trim();
        if (!note) { showToast('Note cannot be empty', 'error'); return; }
        const orderId = _quickNoteOrderId;
        fetch('/admin/orders/' + orderId + '/notes', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify({note_text: note, type: 'order'})
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                closeQuickNoteModal();
                showToast('Note added!', 'success');

                const addBtn = document.querySelector('[onclick="quickAddNote(' + orderId + ')"]');
                if (!addBtn) return;
                const td = addBtn.closest('td');
                let tipBtn    = td.querySelector('.note-tip-btn');
                let countSpan = td.querySelector('.note-count-span');
                const dateStr = nowDateStr();
                const noteSnip = note.substring(0, 220);

                if (!tipBtn) {
                    // First note — create amber icon + count span
                    tipBtn = document.createElement('button');
                    tipBtn.type = 'button';
                    tipBtn.className = 'note-tip-btn mt-0.5 w-5 h-5 flex items-center justify-center bg-amber-100 text-amber-600 rounded-md hover:bg-amber-200 mx-auto';
                    tipBtn.dataset.count = '1';
                    tipBtn.dataset.text  = noteSnip;
                    tipBtn.dataset.date  = dateStr;
                    tipBtn.innerHTML = `<svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>`;

                    countSpan = document.createElement('span');
                    countSpan.className = 'note-count-span text-[10px] text-amber-500 font-semibold leading-none';
                    countSpan.textContent = '1';

                    addBtn.insertAdjacentElement('afterend', countSpan);
                    addBtn.insertAdjacentElement('afterend', tipBtn);
                    _noteTip.attach(tipBtn);
                } else {
                    // Subsequent notes — update count + tooltip data
                    const newCount = parseInt(tipBtn.dataset.count || 0) + 1;
                    tipBtn.dataset.count = newCount;
                    tipBtn.dataset.text  = noteSnip;
                    tipBtn.dataset.date  = dateStr;
                    if (countSpan) countSpan.textContent = newCount;
                }
            } else {
                showToast('Failed to add note', 'error');
            }
        })
        .catch(() => showToast('Error adding note', 'error'));
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeQuickNoteModal();
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

    function openBulkCourierModal() {
        const selected = window.getSelectedOrders ? window.getSelectedOrders() : getSelectedIds();
        if (!selected.length) { showToast('কোনো order select করা হয়নি', 'error'); return; }
        document.getElementById('bulkCourierCount').textContent = selected.length;
        document.getElementById('bulkCourierWarning').textContent = selected.length;
        document.getElementById('bulkSelectedCourier').value = '';
        document.querySelectorAll('.bulk-courier-btn').forEach(btn => {
            btn.classList.remove('border-purple-500','bg-purple-50','text-purple-700','dark:bg-purple-900/30','dark:text-purple-300');
            btn.classList.add('border-gray-200','dark:border-gray-700','bg-white','dark:bg-gray-800','text-gray-700','dark:text-gray-300');
        });
        document.getElementById('bulkCourierModal').classList.remove('hidden');
    }

    function closeBulkCourierModal() {
        document.getElementById('bulkCourierModal').classList.add('hidden');
    }

    function selectBulkCourier(name) {
        document.getElementById('bulkSelectedCourier').value = name;
        document.querySelectorAll('.bulk-courier-btn').forEach(btn => {
            const active = btn.dataset.bulkCourier === name;
            btn.classList.toggle('border-purple-500', active);
            btn.classList.toggle('bg-purple-50', active);
            btn.classList.toggle('text-purple-700', active);
            btn.classList.toggle('dark:bg-purple-900/30', active);
            btn.classList.toggle('dark:text-purple-300', active);
            btn.classList.toggle('border-gray-200', !active);
            btn.classList.toggle('dark:border-gray-700', !active);
            btn.classList.toggle('bg-white', !active);
            btn.classList.toggle('dark:bg-gray-800', !active);
            btn.classList.toggle('text-gray-700', !active);
            btn.classList.toggle('dark:text-gray-300', !active);
        });
    }

    async function submitBulkCourier() {
        const courier = document.getElementById('bulkSelectedCourier').value;
        if (!courier) { showToast('Courier নির্বাচন করুন', 'error'); return; }
        const selected = window.getSelectedOrders ? window.getSelectedOrders() : getSelectedIds();
        if (!selected.length) { showToast('কোনো order select করা হয়নি', 'error'); return; }
        const btn = document.getElementById('bulkCourierSubmitBtn');
        btn.disabled = true;
        btn.innerHTML = '<svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg> Submitting...';
        try {
            const response = await fetch('/admin/orders/bulk-courier', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                body: JSON.stringify({ ids: selected, courier: courier })
            });
            const data = await response.json();
            if (data.success) {
                if (data.errors && data.errors.length) {
                    showToast(`${selected.length - data.errors.length} টি submit হয়েছে। ${data.errors.length} টি fail: ${data.errors[0]}`, 'error');
                } else {
                    showToast(`${selected.length} টি order ${courier} এ submit হয়েছে`, 'success');
                }
                closeBulkCourierModal();
                setTimeout(() => window.location.reload(), 1800);
            } else {
                showToast(data.message || 'Submit failed', 'error');
                btn.disabled = false;
                btn.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg> Submit All';
            }
        } catch(e) {
            showToast('Network error', 'error');
            btn.disabled = false;
            btn.innerHTML = 'Submit All';
        }
    }

    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeBulkCourierModal(); });

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

    // ── Note Hover Tooltip ────────────────────────────────────────────────────
    const _noteTip = (function () {
        const tip = document.createElement('div');
        tip.id = 'noteTip';
        Object.assign(tip.style, {
            position: 'fixed', zIndex: '99999', maxWidth: '280px', minWidth: '180px',
            background: '#1e293b', color: '#f1f5f9', borderRadius: '10px',
            padding: '10px 13px', fontSize: '12px', lineHeight: '1.55',
            boxShadow: '0 8px 24px rgba(0,0,0,0.35)', pointerEvents: 'none',
            display: 'none', wordBreak: 'break-word',
        });
        document.body.appendChild(tip);

        function posTip(e) {
            const mg = 14, tw = tip.offsetWidth, th = tip.offsetHeight;
            let x = e.clientX + mg, y = e.clientY - th - mg;
            if (x + tw > window.innerWidth) x = e.clientX - tw - mg;
            if (y < 0) y = e.clientY + mg;
            tip.style.left = x + 'px'; tip.style.top = y + 'px';
        }

        function attach(btn) {
            btn.addEventListener('mouseenter', function (e) {
                const c = this.dataset.count, d = this.dataset.date, t = this.dataset.text;
                tip.innerHTML =
                    `<div style="color:#94a3b8;font-size:10px;margin-bottom:5px;border-bottom:1px solid #334155;padding-bottom:4px;">` +
                    `📝 ${c} note${c > 1 ? 's' : ''} &nbsp;·&nbsp; ${d}</div><div>${t}</div>`;
                tip.style.display = 'block';
                posTip(e);
            });
            btn.addEventListener('mousemove', posTip);
            btn.addEventListener('mouseleave', () => { tip.style.display = 'none'; });
        }

        document.querySelectorAll('.note-tip-btn').forEach(attach);
        return { attach, hide: () => { tip.style.display = 'none'; } };
    })();

    function nowDateStr() {
        const n = new Date();
        const pad = v => String(v).padStart(2,'0');
        const h = n.getHours(), ampm = h >= 12 ? 'pm' : 'am';
        return `${pad(n.getDate())}/${pad(n.getMonth()+1)}/${n.getFullYear()} ${pad(h%12||12)}:${pad(n.getMinutes())}${ampm}`;
    }
</script>
@endpush

@endsection
