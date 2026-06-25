@extends('layouts.admin')

@section('title', 'Ecom Pilot')

@section('page-title')@endsection


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

        {{-- Single Row: Title + Show entries (left) | Both searches (right) --}}
        <div class="flex items-center justify-between gap-4">

            {{-- Left: Title + Show entries --}}
            <div class="flex items-center gap-4 min-w-0">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white flex-shrink-0">Order List</h3>
                <div class="flex items-center gap-2 flex-shrink-0">
                    <span class="text-xs text-gray-500 dark:text-gray-400">Show</span>
                    <select onchange="window.location.href='?per_page='+this.value+'&{{ http_build_query(request()->except('per_page', 'page')) }}'"
                        class="py-1.5 px-2 pr-6 text-xs rounded-lg border border-gray-300 dark:border-gray-500
                               bg-white dark:bg-gray-700 text-gray-900 dark:text-white cursor-pointer focus:outline-none min-w-[60px]">
                        @foreach([10,25,50,100,150,200,250,300,500,1000,1500,2000] as $n)
                        <option value="{{ $n }}" {{ request('per_page', 10) == $n ? 'selected' : '' }}>{{ $n }}</option>
                        @endforeach
                    </select>
                    <span class="text-xs text-gray-500 dark:text-gray-400">entries</span>
                </div>
            </div>

            {{-- Right: ID search + General search --}}
            <div class="flex items-center gap-3 flex-shrink-0">

                {{-- ID-only search --}}
                {{-- ID search --}}
                <div class="flex items-center">
                    <span class="inline-flex items-center h-[30px] px-2 bg-gray-100 dark:bg-gray-600 text-gray-600 dark:text-gray-200 text-xs font-bold rounded-l-lg border border-gray-300 dark:border-gray-500 border-r-0">ID</span>
                    <input type="text" id="id-search-input" value="{{ request('id_search') }}"
                        placeholder="Search by ID"
                        class="h-[30px] w-32 px-3 text-xs rounded-r-lg border border-gray-300 dark:border-gray-500
                               bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400
                               focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary">
                </div>

                {{-- General search --}}
                <div class="flex items-center gap-1.5">
                    <label class="text-xs text-gray-500 dark:text-gray-400 flex-shrink-0">Search:</label>
                    <input type="text" id="search-input" value="{{ request('search') }}"
                        placeholder="Order# or phone..."
                        class="h-[30px] w-44 px-3 text-xs rounded-lg border border-gray-300 dark:border-gray-500
                               bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400
                               focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary">
                </div>

            </div>
        </div>

        {{-- Row 3: Showing X to Y of Z --}}
        <div class="mt-2 text-xs text-gray-500 dark:text-gray-400" id="orders-count-text">
            Showing {{ $orders->firstItem() ?? 0 }} to {{ $orders->lastItem() ?? 0 }} of {{ number_format($orders->total()) }} entries
        </div>

    </div>

    {{-- Table + Pagination (replaced on real-time search) --}}
    <div id="orders-table-container">

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
            <th class="px-3 py-3 text-left border-r border-gray-200 dark:border-gray-700 w-[150px]">Courier</th>
            <th class="px-3 py-3 text-left border-r border-gray-200 dark:border-gray-700 w-[140px]">Summary</th>
            <th class="px-3 py-3 text-left border-r border-gray-200 dark:border-gray-700 w-[80px]">Employee</th>
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
        <tr class="border-b dark:border-gray-700 transition {{ $isDuplicate ? 'bg-yellow-100 dark:bg-yellow-900/25 hover:bg-yellow-200 dark:hover:bg-yellow-900/35' : 'hover:bg-gray-50 dark:hover:bg-gray-800/50' }}"
            data-order-id="{{ $order->id }}"
            data-total="{{ $order->total ?? 0 }}"
            data-paid="{{ $order->paid_amount ?? 0 }}"
            data-due="{{ ($order->total ?? 0) - ($order->paid_amount ?? 0) }}">

            {{-- Status --}}
            <td class="px-3 py-3 align-top w-[100px] border-r border-gray-100 dark:border-gray-700">
                <button type="button" onclick="openQuickView({{ $order->id }})"
                    class="inline-block px-2 py-1 rounded-lg text-xs font-semibold cursor-pointer hover:opacity-80 transition-opacity
                    @if($order->status == 'pending') bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400
                    @elseif($order->status == 'confirmed') bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400
                    @elseif($order->status == 'delivered' || $order->status == 'completed') bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400
                    @elseif($order->status == 'cancelled' || $order->status == 'failed') bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400
                    @elseif($order->status == 'processing') bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400
                    @elseif($order->status == 'hold') bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400
                    @else bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300
                    @endif">
                    {{ ucfirst($order->status) }}
                </button>
                <div class="text-xs text-gray-400 mt-1">sl: {{ $sl + 1 }}</div>
            </td>

            {{-- Checkbox --}}
            <td class="px-3 py-3 align-top text-center w-[40px] border-r border-gray-100 dark:border-gray-700">
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
                @php
                $copyDataArr = [
                    'id'       => $order->id,
                    'name'     => $order->shipping_name ?? '',
                    'phone'    => $order->shipping_phone ?? '',
                    'address'  => ($order->shipping_address ?? '').($order->shipping_district ? ', '.$order->shipping_district : ''),
                    'items'    => $order->items->map(fn($i) => [
                        'qty'   => $i->qty,
                        'name'  => $i->product_name ?? ($i->product->name ?? 'Product'),
                        'price' => floatval($i->total ?? 0),
                    ])->values()->toArray(),
                    'shipping' => floatval($order->shipping_charge ?? 0),
                    'total'    => floatval($order->total ?? 0),
                    'paid'     => floatval($order->paid_amount ?? 0),
                    'due'      => floatval(($order->total ?? 0) - ($order->paid_amount ?? 0)),
                ];
                @endphp
                <button type="button"
                    data-order="{{ json_encode($copyDataArr, JSON_UNESCAPED_UNICODE) }}"
                    onclick="copyOrderDetails(this)"
                    class="mt-1 w-6 h-6 flex items-center justify-center bg-gray-100 dark:bg-gray-700 text-gray-500 hover:bg-blue-100 hover:text-blue-600 dark:hover:bg-blue-900/30 dark:hover:text-blue-400 rounded-md mx-auto transition" title="Copy order details">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                </button>
            </td>

            {{-- Notes --}}
            <td class="px-3 py-3 align-top text-center w-[60px] border-r border-gray-100 dark:border-gray-700">
                {{-- Add note button --}}
                <button type="button" onclick="quickAddNote({{ $order->id }})"
                    class="w-5 h-5 flex items-center justify-center bg-blue-100 dark:bg-blue-900/30 text-blue-500 rounded-md hover:bg-blue-200 mx-auto"
                    title="Add note">
                    <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                </button>
                {{-- Note view tooltip icon (only if notes exist) --}}
                @if($order->order_notes_count > 0)
                <button type="button"
                    class="note-tip-btn mt-0.5 w-5 h-5 flex items-center justify-center bg-amber-100 dark:bg-amber-900/30 text-amber-600 rounded-md hover:bg-amber-200 mx-auto"
                    data-count="{{ $order->order_notes_count }}"
                    data-text="{{ e(Str::limit($order->latestOrderNote?->note_text ?? '', 220)) }}"
                    data-date="{{ $order->latestOrderNote?->created_at?->format('d/m/Y h:ia') ?? '' }}">
                    <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </button>
                <span class="note-count-span text-[10px] text-amber-500 font-semibold leading-none">{{ $order->order_notes_count }}</span>
                @endif
                @if($isDuplicate)
                <span class="inline-block mt-1 px-1 py-0.5 bg-yellow-400 text-yellow-900 text-[9px] font-bold rounded leading-none">DUP</span>
                @endif
            </td>

            {{-- ID & Products --}}
            <td class="px-3 py-3 align-top w-[200px] border-r border-gray-100 dark:border-gray-700">
                <button type="button" onclick="openQuickView({{ $order->id }})" class="text-blue-600 dark:text-blue-400 font-bold text-sm hover:underline">
                    #{{ $order->id }}
                </button>
                @foreach($order->items->take(2) as $item)
                <div class="flex items-center gap-2 mt-1">
                    @if($item->product && $item->product->images && $item->product->images->first())
                    @php $imgPath = $item->product->images->first()->image_path; @endphp
                    <img src="{{ str_starts_with($imgPath, 'http') ? $imgPath : asset('storage/'.$imgPath) }}"
                         class="w-8 h-8 object-cover rounded-lg border dark:border-gray-700" alt=""
                         onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                    <div class="w-8 h-8 bg-gray-100 dark:bg-gray-700 rounded-lg flex items-center justify-center text-gray-300 text-xs" style="display:none">?</div>
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
            <td class="px-3 py-3 align-top w-[200px] border-r border-gray-100 dark:border-gray-700">
                <div class="flex items-center gap-1 mb-1">
                    <button type="button" onclick="openQuickView({{ $order->id }})" class="font-semibold text-gray-800 dark:text-white text-sm hover:text-[#1B2A4A] dark:hover:text-[#F59E0B] hover:underline">{{ $order->shipping_name ?? 'N/A' }}</button>
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
                @if($order->platform)
                @php
                    $plMap = [
                        'facebook'  => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
                        'google'    => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
                        'tiktok'    => 'bg-gray-800 text-white dark:bg-gray-700',
                        'instagram' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300',
                        'youtube'   => 'bg-red-200 text-red-800 dark:bg-red-900/30 dark:text-red-300',
                        'direct'    => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
                        'organic'   => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
                    ];
                    $plLabel = ucfirst($order->platform);
                    $plClass = $plMap[strtolower($order->platform)] ?? 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300';
                @endphp
                <span class="inline-block mt-1 px-1.5 py-0.5 rounded text-[10px] font-semibold {{ $plClass }}">{{ $plLabel }}</span>
                @endif
            </td>

            {{-- Date --}}
            <td class="px-3 py-3 align-top text-xs text-gray-500 dark:text-gray-400 w-[160px] border-r border-gray-100 dark:border-gray-700">
                <div>C:{{ $order->created_at->format('d/m/Y - h:ia') }}</div>
                <div>U:{{ $order->updated_at->format('d/m/Y - h:ia') }}</div>
                @if($order->assignedTo)
                <div class="mt-1">By: <span class="font-medium text-gray-700 dark:text-gray-300">{{ $order->assignedTo->name }}</span></div>
                @endif
            </td>

            {{-- Address --}}
            <td class="px-3 py-3 align-top w-[180px] border-r border-gray-100 dark:border-gray-700">
                <div class="flex items-start gap-1">
                    <span class="text-xs text-gray-700 dark:text-gray-300 break-words">{{ $order->shipping_address ?? 'N/A' }}{{ $order->shipping_district ? ', '.$order->shipping_district : '' }}</span>
                </div>
            </td>

            {{-- Courier --}}
            <td class="px-3 py-3 align-top text-xs w-[150px] border-r border-gray-100 dark:border-gray-700">
                @php
                    $courierName     = $order->shipment->courier_name ?? 'New';
                    $customerTotal   = $phoneTotals[$order->shipping_phone]  ?? 0;
                    $customerSuccess = $phoneSuccess[$order->shipping_phone]  ?? 0;
                    $customerFailed  = $phoneFailed[$order->shipping_phone]   ?? 0;
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
            <td class="px-3 py-3 align-top text-xs w-[140px] border-r border-gray-100 dark:border-gray-700">
                <div class="text-gray-700 dark:text-gray-300">Total: <span class="font-bold">৳{{ number_format($order->total ?? 0, 0) }}</span></div>
                <div class="text-green-600">Less: <span class="font-bold">৳{{ number_format($order->discount ?? 0, 0) }}</span></div>
                <div class="text-blue-600">Paid: <span class="font-bold">৳{{ number_format($order->paid_amount ?? 0, 0) }}</span></div>
                <div class="text-red-500">Due: <span class="font-bold">৳{{ number_format(($order->total ?? 0) - ($order->paid_amount ?? 0), 0) }}</span></div>
            </td>

            {{-- Employee --}}
            <td class="px-3 py-3 align-top text-xs text-gray-600 dark:text-gray-400 w-[80px] border-r border-gray-100 dark:border-gray-700">
                {{ $order->assignedTo->name ?? 'N/A' }}
            </td>

        </tr>
        @empty
        <tr>
            <td colspan="9" class="px-6 py-16 text-center">
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

    </div>{{-- end #orders-table-container --}}

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

{{-- ══════════════════════════════════════════════════════════════ --}}
{{-- Quick View Drawer                                           --}}
{{-- ══════════════════════════════════════════════════════════════ --}}
<style>
/* Mobile: bottom sheet */
@media (max-width: 1023px) {
    #quickViewModal  { align-items: flex-end !important; padding: 0 !important; }
    #quickViewPanel  { border-radius: 16px 16px 0 0 !important; max-height: 92dvh !important; max-width: 100% !important; }
    #qv-content      { overflow-y: auto !important; flex-direction: column !important; }
    #qv-content > div { flex: none !important; width: 100% !important; overflow-y: visible !important; max-height: none !important; }
    #qv-content > div:last-child { border-left: none !important; border-top: 1px solid #e5e7eb; }
    .dark #qv-content > div:last-child { border-top-color: #374151; }
}
/* Tablet: center modal, slightly smaller */
@media (min-width: 640px) and (max-width: 1023px) {
    #quickViewModal { align-items: center !important; padding: 12px !important; }
    #quickViewPanel { border-radius: 16px !important; max-height: 94dvh !important; }
}
</style>

<div id="quickViewModal" style="display:none;position:fixed;inset:0;z-index:9100;align-items:center;justify-content:center;padding:16px;" role="dialog" aria-modal="true">
    {{-- Backdrop --}}
    <div style="position:absolute;inset:0;background:rgba(0,0,0,0.55);" onclick="closeQuickView()"></div>

    {{-- Dialog box --}}
    <div id="quickViewPanel"
         style="position:relative;width:100%;max-width:1100px;max-height:92vh;background:#fff;border-radius:16px;box-shadow:0 20px 60px rgba(0,0,0,0.25);display:flex;flex-direction:column;opacity:0;transform:scale(0.95) translateY(-10px);transition:opacity 0.2s ease,transform 0.2s ease;"
         class="dark:bg-gray-900">

        {{-- Mobile drag handle --}}
        <div id="qv-drag-handle" class="lg:hidden flex justify-center pt-2.5 pb-1 flex-shrink-0 cursor-grab active:cursor-grabbing touch-none">
            <div class="w-10 h-1 rounded-full bg-gray-300 dark:bg-gray-600"></div>
        </div>

        {{-- Header --}}
        <div class="flex items-center gap-2 px-3 py-2.5 border-b border-gray-200 dark:border-gray-700 flex-shrink-0 bg-white dark:bg-gray-900 min-w-0">
            {{-- Order number --}}
            <span class="text-sm font-bold text-[#1B2A4A] dark:text-white flex-shrink-0" id="qv-order-num">#—</span>

            {{-- Status select --}}
            <select id="qv-status" onchange="qvSaveField('status', this.value)"
                    class="min-w-0 flex-shrink text-xs font-semibold rounded-lg px-2 py-1 border border-gray-200 dark:border-gray-600
                           bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/30 cursor-pointer">
            </select>

            {{-- Saving indicators --}}
            <span id="qv-saving-indicator" class="text-xs text-gray-400 hidden flex-shrink-0">Saving…</span>
            <span id="qv-saved-indicator"  class="text-xs text-green-500 hidden flex-shrink-0">✓ Saved</span>

            {{-- Spacer --}}
            <div class="flex-1"></div>

            {{-- Full Page: icon-only on mobile, icon+text on sm+ --}}
            <a id="qv-full-link" href="#"
               class="inline-flex items-center gap-1 px-2 sm:px-2.5 py-1 rounded-lg text-xs font-medium text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20 hover:bg-blue-100 dark:hover:bg-blue-900/40 transition-colors flex-shrink-0 whitespace-nowrap">
                <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                <span class="hidden sm:inline">Full Page</span>
            </a>

            {{-- Close button --}}
            <button onclick="closeQuickView()"
                    class="w-8 h-8 flex-shrink-0 flex items-center justify-center rounded-full text-gray-400
                           hover:text-gray-600 dark:hover:text-gray-200
                           hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Loading --}}
        <div id="qv-loading" style="display:none;flex:1;align-items:center;justify-content:center;" class="flex items-center justify-center">
            <svg class="w-8 h-8 animate-spin text-primary" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
        </div>

        {{-- Content --}}
        {{-- ── CONTENT (2-column on desktop) ── --}}
        <div id="qv-content" style="display:none;flex:1;overflow:hidden;" class="flex flex-col lg:flex-row min-h-0">

            {{-- LEFT COLUMN --}}
            <div class="flex-1 lg:flex-none lg:w-[55%] overflow-y-auto divide-y divide-gray-100 dark:divide-gray-800 min-w-0">

                {{-- Customer Info --}}
                <div class="p-2 space-y-1.5">
                    <h4 class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Customer Info</h4>
                    {{-- Row 1: Phone + Alt Phone --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-1.5">
                        <div>
                            <label class="text-[10px] text-gray-500 dark:text-gray-400 mb-0.5 block">Phone *</label>
                            <div class="flex items-center gap-1">
                                <input type="text" id="qv-phone" placeholder="01XXXXXXXXX" maxlength="11"
                                       oninput="qvDebounceSave('shipping_phone', this.value)"
                                       class="flex-1 min-w-0 rounded-lg border border-gray-200 dark:border-gray-700 px-2 py-1 text-xs bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/30">
                                <a id="qv-wa-link" href="#" target="_blank" title="WhatsApp"
                                   class="w-7 h-7 flex items-center justify-center bg-green-500 text-white rounded-lg hover:bg-green-600 flex-shrink-0">
                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                </a>
                                <a id="qv-call-link" href="#" title="Call"
                                   class="w-7 h-7 flex items-center justify-center bg-blue-500 text-white rounded-lg hover:bg-blue-600 flex-shrink-0">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.948V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 7V5z"/></svg>
                                </a>
                            </div>
                        </div>
                        <div>
                            <label class="text-[10px] text-gray-500 dark:text-gray-400 mb-0.5 block">Alt Phone</label>
                            <input type="text" id="qv-alt-phone" placeholder="Alternative number" maxlength="11"
                                   oninput="qvDebounceSave('alt_phone', this.value)"
                                   class="w-full rounded-lg border border-gray-200 dark:border-gray-700 px-2 py-1 text-xs bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/30">
                        </div>
                    </div>
                    {{-- Row 2: Name + Email --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-1.5">
                        <div>
                            <label class="text-[10px] text-gray-500 dark:text-gray-400 mb-0.5 block">Full Name *</label>
                            <input type="text" id="qv-name" placeholder="Customer name"
                                   oninput="qvDebounceSave('shipping_name', this.value)"
                                   class="w-full rounded-lg border border-gray-200 dark:border-gray-700 px-2 py-1 text-xs bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/30">
                        </div>
                        <div>
                            <label class="text-[10px] text-gray-500 dark:text-gray-400 mb-0.5 block">Email</label>
                            <input type="email" id="qv-email" placeholder="email@example.com"
                                   oninput="qvDebounceSave('email', this.value)"
                                   class="w-full rounded-lg border border-gray-200 dark:border-gray-700 px-2 py-1 text-xs bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/30">
                        </div>
                    </div>
                    {{-- Row 3: Address + District --}}
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-1.5">
                        <div class="sm:col-span-2">
                            <label class="text-[10px] text-gray-500 dark:text-gray-400 mb-0.5 block">Address</label>
                            <input type="text" id="qv-address" placeholder="Delivery address"
                                   oninput="qvDebounceSave('shipping_address', this.value)"
                                   class="w-full rounded-lg border border-gray-200 dark:border-gray-700 px-2 py-1 text-xs bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/30">
                        </div>
                        <div>
                            <label class="text-[10px] text-gray-500 dark:text-gray-400 mb-0.5 block">District</label>
                            <select id="qv-district" onchange="qvSaveField('shipping_district', this.value)"
                                    class="w-full rounded-lg border border-gray-200 dark:border-gray-700 px-2 py-1 text-xs bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/30">
                                <option value="">Select</option>
                            </select>
                        </div>
                    </div>
                    {{-- Customer insights row --}}
                    <div class="flex flex-wrap items-center gap-2 pt-1 border-t border-gray-100 dark:border-gray-800">
                        <div class="flex items-center gap-1 text-[10px]">
                            <span class="text-gray-400">Total Orders:</span>
                            <span id="qv-total-orders" class="font-bold text-[#1B2A4A] dark:text-white">—</span>
                            <a id="qv-orders-link" href="#" target="_blank" class="text-accent hover:underline">View ↗</a>
                        </div>
                        <div class="flex items-center gap-1 text-[10px]">
                            <span class="text-gray-400">Completed:</span>
                            <span id="qv-completed" class="font-bold text-green-600">—</span>
                        </div>
                        <div class="flex items-center gap-1 text-[10px]">
                            <span class="text-gray-400">Source:</span>
                            <span id="qv-source" class="text-gray-700 dark:text-gray-300 font-medium">—</span>
                        </div>
                    </div>
                    {{-- Tags row --}}
                    <div class="grid grid-cols-2 gap-1.5">
                        <div>
                            <label class="text-[10px] text-gray-500 dark:text-gray-400 mb-0.5 block">Order Tag</label>
                            <select id="qv-order-tag" onchange="qvSaveField('order_tag', this.value)"
                                    class="w-full rounded-lg border border-gray-200 dark:border-gray-700 px-2 py-1 text-xs bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/30">
                                <option value="">— None —</option>
                                <option value="vip">VIP</option>
                                <option value="regular">Regular</option>
                                <option value="problematic">Problematic</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-[10px] text-gray-500 dark:text-gray-400 mb-0.5 block">Customer Tag</label>
                            <select id="qv-customer-tag" onchange="qvSaveField('customer_tag', this.value)"
                                    class="w-full rounded-lg border border-gray-200 dark:border-gray-700 px-2 py-1 text-xs bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/30">
                                <option value="">— None —</option>
                                <option value="loyal">Loyal</option>
                                <option value="new">New</option>
                                <option value="fraud">Fraud Risk</option>
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Order Products --}}
                <div class="p-3">
                    <div class="flex items-center justify-between mb-2">
                        <h4 class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Products</h4>
                        <button type="button" onclick="qvToggleProductSearch()"
                                class="inline-flex items-center gap-1 px-2 py-1 rounded-md bg-[#1B2A4A] text-white text-[10px] font-semibold hover:bg-[#243a63] transition flex-shrink-0">
                            <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                            Add Product
                        </button>
                    </div>
                    <div class="overflow-x-auto -mx-1">
                        <table class="w-full text-xs min-w-[480px]">
                            <thead>
                                <tr class="bg-gray-50 dark:bg-gray-800 text-gray-500 dark:text-gray-400">
                                    <th class="text-left px-2 py-1.5 rounded-l-lg">Product</th>
                                    <th class="text-center px-2 py-1.5 w-20">Price</th>
                                    <th class="text-center px-2 py-1.5 w-16">Qty</th>
                                    <th class="text-center px-2 py-1.5 w-20">Disc.</th>
                                    <th class="text-center px-2 py-1.5 w-20">Sub</th>
                                    <th class="text-center px-2 py-1.5 w-8 rounded-r-lg"></th>
                                </tr>
                            </thead>
                            <tbody id="qv-items-tbody"></tbody>
                        </table>
                    </div>
                    {{-- Add product search (hidden by default, toggled by button) --}}
                    <div id="qv-add-product-wrap" style="display:none;position:relative;" class="mt-2">
                        <input type="text" id="qv-product-search"
                               placeholder="Product name লিখুন…"
                               oninput="qvSearchProduct(this.value)"
                               autocomplete="off"
                               class="w-full rounded-lg border border-gray-300 dark:border-gray-600 px-2 py-1.5 text-xs bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/30">
                        <div id="qv-product-results"
                             style="display:none;position:absolute;z-index:200;top:100%;left:0;right:0;margin-top:4px;"
                             class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-2xl max-h-56 overflow-y-auto">
                        </div>
                    </div>
                </div>

                {{-- Courier Note + Packing Note (inline) --}}
                <div class="p-3">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <div>
                            <label class="text-xs text-gray-500 dark:text-gray-400 mb-0.5 block">Courier Note</label>
                            <textarea id="qv-courier-note" rows="2" placeholder="Note for courier…"
                                      oninput="qvDebounceSave('courier_note', this.value)"
                                      class="w-full rounded-lg border border-gray-200 dark:border-gray-700 px-2 py-1.5 text-sm bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/30 resize-none"></textarea>
                        </div>
                        <div>
                            <label class="text-xs text-gray-500 dark:text-gray-400 mb-0.5 block">Packing Note</label>
                            <textarea id="qv-packing-note" rows="2" placeholder="Packing instructions…"
                                      oninput="qvDebounceSave('packing_note', this.value)"
                                      class="w-full rounded-lg border border-gray-200 dark:border-gray-700 px-2 py-1.5 text-sm bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/30 resize-none"></textarea>
                        </div>
                    </div>
                </div>

                {{-- Notes (tabbed) --}}
                <div class="p-3">
                    <div class="flex items-center gap-2 mb-2">
                        <button type="button" id="qv-tab-order" onclick="qvSwitchTab('order')"
                                class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300">
                            Order Notes <span id="qv-order-note-count" class="ml-1 opacity-70"></span>
                        </button>
                        <button type="button" id="qv-tab-customer" onclick="qvSwitchTab('customer')"
                                class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800">
                            Customer Notes <span id="qv-customer-note-count" class="ml-1 opacity-70"></span>
                        </button>
                    </div>
                    <div id="qv-notes-order" class="space-y-1.5 max-h-36 overflow-y-auto mb-2"></div>
                    <div id="qv-notes-customer" class="space-y-1.5 max-h-36 overflow-y-auto mb-2" style="display:none"></div>
                    <div class="flex gap-2">
                        <input type="text" id="qv-note-input" placeholder="Add a note and press Enter…"
                               class="flex-1 rounded-lg border border-gray-200 dark:border-gray-700 px-2 py-1.5 text-sm bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/30"
                               onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();qvAddNote();}">
                        <button type="button" onclick="qvAddNote()"
                                class="px-3 py-1.5 bg-primary text-white text-xs font-semibold rounded-lg hover:bg-primary/90 transition flex-shrink-0">Add</button>
                    </div>
                </div>

            </div>{{-- end left --}}

            {{-- RIGHT COLUMN --}}
            <div class="lg:flex-none lg:w-[45%] flex-shrink-0 overflow-y-auto divide-y divide-gray-100 dark:divide-gray-800 border-t lg:border-t-0 lg:border-l border-gray-100 dark:border-gray-800">

                {{-- Payment Summary --}}
                <div class="p-3">
                    <h4 class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Payment</h4>

                    {{-- Subtotal --}}
                    <div class="flex justify-between items-center py-1.5 border-b border-gray-100 dark:border-gray-800">
                        <span class="text-xs text-gray-500 dark:text-gray-400">Subtotal</span>
                        <span id="qv-subtotal" class="text-xs font-semibold text-gray-800 dark:text-gray-200">৳0</span>
                    </div>

                    {{-- Discount --}}
                    <div class="flex justify-between items-center py-1.5 border-b border-gray-100 dark:border-gray-800">
                        <label class="text-xs text-gray-500 dark:text-gray-400 flex-shrink-0">Discount</label>
                        <div class="flex items-center gap-1">
                            <span class="text-[10px] text-gray-400">৳</span>
                            <input type="number" id="qv-discount" min="0" step="1"
                                   oninput="qvCalcPayment(); qvDebounceSave('discount', this.value)"
                                   class="w-20 text-right rounded border border-gray-200 dark:border-gray-700 px-1.5 py-0.5 text-xs bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-1 focus:ring-primary/40">
                        </div>
                    </div>

                    {{-- Shipping --}}
                    <div class="flex justify-between items-center py-1.5 border-b border-gray-100 dark:border-gray-800">
                        <label class="text-xs text-gray-500 dark:text-gray-400 flex-shrink-0">Shipping</label>
                        <div class="flex items-center gap-1">
                            <span class="text-[10px] text-gray-400">৳</span>
                            <input type="number" id="qv-shipping" min="0" step="1"
                                   oninput="qvCalcPayment(); qvDebounceSave('shipping_charge', this.value)"
                                   class="w-20 text-right rounded border border-gray-200 dark:border-gray-700 px-1.5 py-0.5 text-xs bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-1 focus:ring-primary/40">
                        </div>
                    </div>

                    {{-- Total (highlighted) --}}
                    <div class="flex justify-between items-center px-2 py-2 my-1.5 rounded-lg bg-[#1B2A4A]/5 dark:bg-white/5">
                        <span class="text-sm font-bold text-[#1B2A4A] dark:text-white">Total</span>
                        <span id="qv-total" class="text-sm font-bold text-[#1B2A4A] dark:text-white">৳0</span>
                    </div>

                    {{-- Paid --}}
                    <div class="flex justify-between items-center py-1.5 border-b border-gray-100 dark:border-gray-800">
                        <label class="text-xs text-gray-500 dark:text-gray-400 flex-shrink-0">Paid</label>
                        <div class="flex items-center gap-1">
                            <span class="text-[10px] text-gray-400">৳</span>
                            <input type="number" id="qv-paid" min="0" step="1"
                                   oninput="qvCalcPayment(); qvDebounceSave('paid_amount', this.value)"
                                   class="w-20 text-right rounded border border-gray-200 dark:border-gray-700 px-1.5 py-0.5 text-xs bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-1 focus:ring-primary/40">
                        </div>
                    </div>

                    {{-- Due --}}
                    <div class="flex justify-between items-center py-1.5">
                        <span class="text-xs font-bold text-red-500">Due</span>
                        <span id="qv-due" class="text-xs font-bold text-red-500">৳0</span>
                    </div>

                    {{-- Payment Method --}}
                    <div class="pt-2 mt-0.5 border-t border-gray-100 dark:border-gray-800">
                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1 block">Payment Method</label>
                        <select id="qv-payment-method" onchange="qvSaveField('payment_method', this.value)"
                                class="w-full rounded-lg border border-gray-200 dark:border-gray-700 px-2 py-1.5 text-xs bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/30">
                            <option value="cod">Cash on Delivery</option>
                            <option value="bkash">bKash</option>
                            <option value="nagad">Nagad</option>
                            <option value="rocket">Rocket</option>
                            <option value="card">Bank Transfer</option>
                        </select>
                    </div>
                </div>

                {{-- Order Info --}}
                <div class="p-3 space-y-2">
                    <h4 class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Order Info</h4>
                    <div>
                        <label class="text-xs text-gray-500 dark:text-gray-400 mb-0.5 block">Assigned To</label>
                        <select id="qv-assigned" onchange="qvSaveField('assigned_to', this.value)"
                                class="w-full rounded-lg border border-gray-200 dark:border-gray-700 px-2 py-1.5 text-sm bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/30">
                            <option value="">Unassigned</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs text-gray-500 dark:text-gray-400 mb-0.5 block">Reference No</label>
                        <input type="text" id="qv-reference" placeholder="Reference number"
                               oninput="qvDebounceSave('reference_no', this.value)"
                               class="w-full rounded-lg border border-gray-200 dark:border-gray-700 px-2 py-1.5 text-sm bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/30">
                    </div>
                </div>

                {{-- Courier & Dates --}}
                <div class="p-3 space-y-1.5">
                    <h4 class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1.5">Courier & Dates</h4>
                    <div id="qv-courier-info" class="text-sm text-gray-400 space-y-1.5">—</div>
                    <div class="pt-1.5 space-y-1 border-t border-gray-100 dark:border-gray-800">
                        <div class="flex justify-between text-xs text-gray-400">
                            <span>Created</span><span id="qv-created-at" class="text-gray-600 dark:text-gray-300">—</span>
                        </div>
                        <div class="flex justify-between text-xs text-gray-400">
                            <span>Updated</span><span id="qv-updated-at" class="text-gray-600 dark:text-gray-300">—</span>
                        </div>
                    </div>
                </div>

            </div>{{-- end right --}}

        </div>{{-- end #qv-content --}}
    </div>{{-- end panel --}}
</div>{{-- end modal --}}

{{-- Custom Confirm Dialog --}}
<div id="qv-confirm-modal" style="display:none;position:fixed;inset:0;z-index:99999;align-items:center;justify-content:center;padding:16px;" class="flex">
    <div style="position:absolute;inset:0;background:rgba(0,0,0,0.5);" onclick="qvConfirmCancel()"></div>
    <div style="position:relative;width:100%;max-width:360px;" class="bg-white dark:bg-gray-900 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="px-5 pt-5 pb-2 flex items-start gap-3">
            <div class="w-9 h-9 rounded-xl bg-red-100 dark:bg-red-900/30 flex items-center justify-center flex-shrink-0 mt-0.5">
                <svg class="w-4.5 h-4.5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                </svg>
            </div>
            <div>
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-1">Confirm Action</h3>
                <p id="qv-confirm-message" class="text-xs text-gray-500 dark:text-gray-400 leading-relaxed"></p>
            </div>
        </div>
        <div class="flex items-center justify-end gap-2 px-5 py-4">
            <button type="button" onclick="qvConfirmCancel()"
                    class="px-4 py-1.5 rounded-lg text-xs font-semibold text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">
                Cancel
            </button>
            <button type="button" id="qv-confirm-ok" onclick="qvConfirmOk()"
                    class="px-4 py-1.5 rounded-lg text-xs font-semibold text-white bg-red-500 hover:bg-red-600 transition-colors">
                Delete
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
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-gray-700">
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
        <div class="flex items-center justify-end gap-2 px-5 py-3 border-t border-gray-100 dark:border-gray-700">
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

    function copyOrderDetails(btn) {
        let d;
        try { d = JSON.parse(btn.getAttribute('data-order')); }
        catch(e) { showToast('Copy failed!', 'error'); return; }
        let text = `#${d.id}\n`;
        text += `Name: ${d.name}\n`;
        text += `Address: ${d.address}\n`;
        text += `Phone: ${d.phone}\n`;
        text += `------------Products------------\n`;
        (d.items || []).forEach(item => {
            text += `${item.qty} x ${item.name} - ${parseFloat(item.price).toFixed(2)} Tk\n`;
        });
        text += `----------------------\n`;
        text += `Shipping: ${parseFloat(d.shipping).toFixed(2)} Tk\n`;
        text += `Total: ${parseFloat(d.total).toFixed(2)} Tk\n`;
        text += `Paid: ${parseFloat(d.paid).toFixed(2)} Tk\n`;
        text += `Due: ${parseFloat(d.due).toFixed(2)} Tk`;
        navigator.clipboard.writeText(text).then(() => showToast('Order details copied!', 'success'))
            .catch(() => showToast('Copy failed — clipboard blocked', 'error'));
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
        if (!await qvConfirm(`${selected.length} টি order-এর status "${status}" করতে চান?`, 'Confirm')) return;
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
            refreshSidebarCounts();
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

    // ── Real-time search ──────────────────────────────────────────────────────
    (function () {
        let searchTimer = null;

        function buildSearchUrl(search, idSearch) {
            const params = new URLSearchParams(window.location.search);
            params.delete('page');
            if (search !== null) {
                search ? params.set('search', search) : params.delete('search');
                params.delete('id_search');
            }
            if (idSearch !== null) {
                idSearch ? params.set('id_search', idSearch) : params.delete('id_search');
                params.delete('search');
            }
            return window.location.pathname + '?' + params.toString();
        }

        async function doSearch(url) {
            const container  = document.getElementById('orders-table-container');
            const countEl    = document.getElementById('orders-count-text');
            if (!container) return;

            container.style.opacity = '0.5';

            try {
                const res  = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                const html = await res.text();
                const parser  = new DOMParser();
                const doc     = parser.parseFromString(html, 'text/html');

                const newContainer = doc.getElementById('orders-table-container');
                const newCount     = doc.getElementById('orders-count-text');

                if (newContainer) container.innerHTML = newContainer.innerHTML;
                if (newCount && countEl) countEl.innerHTML = newCount.innerHTML;

                // Update URL without reload
                history.replaceState(null, '', url);
            } catch (e) {
                console.error('Search failed', e);
            } finally {
                container.style.opacity = '1';
            }
        }

        function onInput(search, idSearch) {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => {
                doSearch(buildSearchUrl(search, idSearch));
            }, 400);
        }

        document.addEventListener('DOMContentLoaded', () => {
            const searchInput   = document.getElementById('search-input');
            const idSearchInput = document.getElementById('id-search-input');

            if (searchInput) {
                searchInput.addEventListener('input', () => onInput(searchInput.value.trim(), null));
            }
            if (idSearchInput) {
                idSearchInput.addEventListener('input', () => onInput(null, idSearchInput.value.trim()));
            }
        });
    })();
</script>
<script>
// ── Quick View Dialog ─────────────────────────────────────────────────────────
const QV_DISTRICTS = ['Bagerhat','Bandarban','Barguna','Barishal','Bhola','Bogura','Brahmanbaria','Chandpur','Chapai Nawabganj','Chattogram','Chuadanga',"Cox's Bazar",'Cumilla','Dhaka','Dinajpur','Faridpur','Feni','Gaibandha','Gazipur','Gopalganj','Habiganj','Jamalpur','Jashore','Jhalokati','Jhenaidah','Joypurhat','Khagrachhari','Khulna','Kishoreganj','Kurigram','Kushtia','Lakshmipur','Lalmonirhat','Madaripur','Magura','Manikganj','Meherpur','Moulvibazar','Munshiganj','Mymensingh','Naogaon','Narail','Narayanganj','Narsingdi','Natore','Netrokona','Nilphamari','Noakhali','Pabna','Panchagarh','Patuakhali','Pirojpur','Rajbari','Rajshahi','Rangamati','Rangpur','Satkhira','Shariatpur','Sherpur','Sirajganj','Sunamganj','Sylhet','Tangail','Thakurgaon'];
const QV_STATUSES = [['pending','Pending'],['confirmed','Confirmed'],['processing','Processing'],['hold','Hold'],['hold_followup','Hold Followup'],['shipped','In Courier'],['delivered','Delivered'],['completed','Completed'],['cancelled','Cancelled'],['pending_return','Pending Return'],['returned','Returned'],['damage','Damage'],['hand_delivery','Hand Delivery'],['hand_delivery_completed','Hand Delivery Completed'],['others','Others'],['return_collection','Return Collection'],['courier_payment_validate','Courier Payment Validate']];

let qvOrderId = null;
let qvActiveNoteTab = 'order';
let qvTimers = {};
const CSRF = () => document.querySelector('meta[name=csrf-token]').content;
const fmt = v => '৳' + Number(v||0).toLocaleString();

function openQuickView(id) {
    qvOrderId = id;
    const modal = document.getElementById('quickViewModal');
    const panel = document.getElementById('quickViewPanel');
    const loading = document.getElementById('qv-loading');
    const content = document.getElementById('qv-content');

    const isMobile = window.innerWidth < 640;
    modal.style.display = 'flex';
    loading.style.display = 'flex';
    content.style.display = 'none';
    panel.style.opacity = '0';
    panel.style.transform = isMobile ? 'translateY(100%)' : 'scale(0.95) translateY(-10px)';
    requestAnimationFrame(() => requestAnimationFrame(() => {
        panel.style.opacity = '1';
        panel.style.transform = isMobile ? 'translateY(0)' : 'scale(1) translateY(0)';
    }));

    fetch(`/admin/orders/${id}/quick-view`, { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF() } })
        .then(r => { if (!r.ok) throw r.status; return r.json(); })
        .then(order => { qvPopulate(order); loading.style.display = 'none'; content.style.display = 'flex'; })
        .catch(() => { loading.style.display = 'none'; });
}

function closeQuickView() {
    const panel = document.getElementById('quickViewPanel');
    const isMobile = window.innerWidth < 640;
    panel.style.opacity = isMobile ? '1' : '0';
    panel.style.transform = isMobile ? 'translateY(110%)' : 'scale(0.95) translateY(-10px)';
    setTimeout(() => { document.getElementById('quickViewModal').style.display = 'none'; panel.style.transform = ''; }, 250);
}

// ── Swipe-to-close (mobile bottom sheet) ─────────────────────
(function () {
    let startY = 0, currentY = 0, dragging = false;
    const handle = () => document.getElementById('qv-drag-handle');
    const panel  = () => document.getElementById('quickViewPanel');

    function onStart(e) {
        if (window.innerWidth >= 640) return;
        startY   = e.touches ? e.touches[0].clientY : e.clientY;
        currentY = startY;
        dragging = true;
        panel().style.transition = 'none';
    }
    function onMove(e) {
        if (!dragging) return;
        currentY = e.touches ? e.touches[0].clientY : e.clientY;
        const dy = Math.max(0, currentY - startY);
        panel().style.transform = `translateY(${dy}px)`;
    }
    function onEnd() {
        if (!dragging) return;
        dragging = false;
        panel().style.transition = 'opacity 0.2s ease, transform 0.25s ease';
        const dy = Math.max(0, currentY - startY);
        if (dy > 100) {
            closeQuickView();
        } else {
            panel().style.transform = 'translateY(0)';
        }
    }

    document.addEventListener('touchstart', e => {
        if (handle() && handle().contains(e.target)) onStart(e);
    }, { passive: true });
    document.addEventListener('touchmove', e => { if (dragging) onMove(e); }, { passive: true });
    document.addEventListener('touchend', onEnd);
})();

function qvPopulate(order) {
    // Header
    document.getElementById('qv-order-num').textContent = '#' + (order.order_number || order.id);
    document.getElementById('qv-full-link').href = `/admin/orders/${order.id}`;

    // Status
    const statusSel = document.getElementById('qv-status');
    statusSel.innerHTML = QV_STATUSES.map(([v,l]) => `<option value="${v}" ${order.status===v?'selected':''}>${l}</option>`).join('');

    // Customer
    const phone = order.shipping_phone || '';
    document.getElementById('qv-phone').value     = phone;
    document.getElementById('qv-alt-phone').value = order.alt_phone || '';
    document.getElementById('qv-name').value      = order.shipping_name || '';
    document.getElementById('qv-email').value     = order.email || '';
    document.getElementById('qv-address').value   = order.shipping_address || '';
    document.getElementById('qv-wa-link').href    = `https://wa.me/880${phone.replace(/^0/,'')}`;
    document.getElementById('qv-call-link').href  = `tel:${phone}`;

    // District
    const distSel = document.getElementById('qv-district');
    distSel.innerHTML = '<option value="">Select</option>' + QV_DISTRICTS.map(d => `<option value="${d}" ${order.shipping_district===d?'selected':''}>${d}</option>`).join('');

    // Customer insights
    document.getElementById('qv-total-orders').textContent = order.customer_total || 0;
    document.getElementById('qv-completed').textContent    = order.customer_completed || 0;
    document.getElementById('qv-source').textContent       = order.source || 'Direct';
    document.getElementById('qv-orders-link').href         = `/admin/orders?search=${encodeURIComponent(phone)}`;

    // Tags
    document.getElementById('qv-order-tag').value    = order.order_tag || '';
    document.getElementById('qv-customer-tag').value = order.customer_tag || '';

    // Payment
    document.getElementById('qv-subtotal').textContent = fmt(order.subtotal);
    document.getElementById('qv-discount').value        = order.discount || 0;
    document.getElementById('qv-shipping').value        = order.shipping_charge || 0;
    document.getElementById('qv-total').textContent     = fmt(order.total);
    document.getElementById('qv-paid').value            = order.paid_amount || 0;
    document.getElementById('qv-due').textContent       = fmt(order.due);
    const pmSel = document.getElementById('qv-payment-method');
    [...pmSel.options].forEach(o => o.selected = o.value === order.payment_method);

    // Order info
    document.getElementById('qv-courier-note').value  = order.courier_note || '';
    document.getElementById('qv-packing-note').value  = order.packing_note || '';
    document.getElementById('qv-reference').value     = order.reference_no || order.order_number || '';
    document.getElementById('qv-created-at').textContent = order.created_at || '—';
    document.getElementById('qv-updated-at').textContent = order.updated_at || '—';

    // Assigned To
    const asSel = document.getElementById('qv-assigned');
    asSel.innerHTML = '<option value="">Unassigned</option>' + (order.admins||[]).map(a => `<option value="${a.id}" ${order.assigned_to==a.id?'selected':''}>${a.name}</option>`).join('');

    // Products table
    const tbody = document.getElementById('qv-items-tbody');
    if (order.items && order.items.length) {
        tbody.innerHTML = order.items.map(i => `
            <tr class="border-b border-gray-100 dark:border-gray-800 group" data-item-id="${i.id}">
                <td class="px-2 py-1.5">
                    <div class="flex items-center gap-1.5">
                        ${i.image ? `<img src="${i.image}" class="w-7 h-7 rounded object-cover flex-shrink-0" onerror="this.style.display='none'">` : `<div class="w-7 h-7 rounded bg-gray-100 dark:bg-gray-700 flex-shrink-0 flex items-center justify-center text-gray-400 text-[10px]">?</div>`}
                        <div>
                            <div class="text-xs font-medium text-gray-800 dark:text-gray-200 leading-tight max-w-[140px] truncate">${i.name}</div>
                            ${i.variant ? `<div class="text-[10px] text-gray-400">${i.variant}</div>` : ''}
                        </div>
                    </div>
                </td>
                <td class="px-2 py-1.5 text-center">
                    <span data-field="price" class="text-xs font-medium text-gray-700 dark:text-gray-200">${fmt(i.unit_price)}</span>
                </td>
                <td class="px-2 py-1.5 text-center">
                    <input type="number" value="${i.qty}" min="1" step="1"
                           oninput="qvUpdateItemCalc(${i.id}, this.closest('tr')); qvSaveItemField(${i.id}, 'qty', this.value)"
                           data-field="qty"
                           class="w-14 text-center rounded border border-gray-200 dark:border-gray-700 px-1 py-0.5 text-xs bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-1 focus:ring-primary/40">
                </td>
                <td class="px-2 py-1.5 text-center">
                    <input type="number" value="${i.discount||0}" min="0" step="1"
                           oninput="qvUpdateItemCalc(${i.id}, this.closest('tr')); qvSaveItemField(${i.id}, 'discount', this.value)"
                           data-field="disc"
                           class="w-20 text-center rounded border border-gray-200 dark:border-gray-700 px-1 py-0.5 text-xs bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-1 focus:ring-primary/40">
                </td>
                <td class="px-2 py-1.5 text-center">
                    <span class="item-sub-${i.id} text-xs font-semibold text-gray-700 dark:text-gray-200">${fmt(i.subtotal)}</span>
                </td>
                <td class="px-2 py-1.5 text-center">
                    <button type="button" onclick="qvRemoveItem(${i.id})"
                            class="w-6 h-6 flex items-center justify-center bg-red-50 dark:bg-red-900/20 text-red-400 rounded hover:bg-red-100 dark:hover:bg-red-900/40 transition mx-auto opacity-0 group-hover:opacity-100">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </td>
            </tr>`).join('');
    } else {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-xs text-gray-400">No items</td></tr>';
    }

    // Notes
    qvRenderNotes('order', order.order_notes || []);
    qvRenderNotes('customer', order.customer_notes || []);
    document.getElementById('qv-order-note-count').textContent = order.order_notes?.length ? `(${order.order_notes.length})` : '';
    document.getElementById('qv-customer-note-count').textContent = order.customer_notes?.length ? `(${order.customer_notes.length})` : '';
    qvSwitchTab(qvActiveNoteTab);

    // Courier
    const cEl = document.getElementById('qv-courier-info');
    if (order.shipment) {
        const s = order.shipment;
        cEl.innerHTML = `
            <div class="flex items-center gap-2 flex-wrap">
                <span class="px-2 py-0.5 rounded-md bg-[#1B2A4A] text-white text-[11px] font-semibold">${s.courier}</span>
                <span class="px-2 py-0.5 rounded-md bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 text-[11px]">${s.status}</span>
            </div>
            ${s.tracking ? `<div class="flex items-center gap-2 mt-1.5"><span class="text-xs text-gray-400">Tracking:</span><span class="text-xs font-mono text-gray-700 dark:text-gray-300">${s.tracking}</span>${s.tracking_link ? `<a href="${s.tracking_link}" target="_blank" class="w-5 h-5 flex items-center justify-center bg-blue-100 dark:bg-blue-900/30 text-blue-600 rounded hover:bg-blue-200"><svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg></a>` : ''}</div>` : ''}
            ${s.consignment ? `<div class="text-xs text-gray-400 mt-1">Consignment: <span class="text-gray-600 dark:text-gray-300">${s.consignment}</span></div>` : ''}
            ${s.delivery_fee > 0 ? `<div class="text-xs text-gray-400 mt-1">Delivery Fee: <span class="font-semibold text-gray-700 dark:text-gray-200">${fmt(s.delivery_fee)}</span></div>` : ''}
            ${s.submitted_at ? `<div class="text-xs text-gray-400 mt-1">Submitted: <span class="text-gray-600 dark:text-gray-300">${s.submitted_at}</span></div>` : ''}`;
    } else {
        cEl.innerHTML = '<span class="text-xs text-gray-400">No courier assigned yet.</span>';
    }
}

function qvSwitchTab(tab) {
    qvActiveNoteTab = tab;
    const isOrder = tab === 'order';
    document.getElementById('qv-notes-order').style.display    = isOrder ? '' : 'none';
    document.getElementById('qv-notes-customer').style.display = isOrder ? 'none' : '';
    const tabOrder    = document.getElementById('qv-tab-order');
    const tabCustomer = document.getElementById('qv-tab-customer');
    tabOrder.className    = 'px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors ' + (isOrder  ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800');
    tabCustomer.className = 'px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors ' + (!isOrder ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800');
    document.getElementById('qv-note-input').placeholder = isOrder ? 'Add order note and press Enter…' : 'Add customer note and press Enter…';
}

function qvRenderNotes(type, notes) {
    const el = document.getElementById(`qv-notes-${type}`);
    const isOrder = type === 'order';
    const cardCls = isOrder
        ? 'bg-blue-50 dark:bg-blue-900/10 border-blue-100 dark:border-blue-800/20'
        : 'bg-amber-50 dark:bg-amber-900/10 border-amber-100 dark:border-amber-800/20';
    if (!notes.length) {
        el.innerHTML = `<p class="text-xs text-gray-400 text-center py-3">No ${type} notes yet.</p>`;
        return;
    }
    el.innerHTML = notes.map(n => `
        <div class="group rounded-lg px-2.5 py-1.5 border ${cardCls}">
            <div class="flex items-start gap-2">
                <p class="flex-1 text-xs text-gray-800 dark:text-gray-200 whitespace-pre-wrap">${n.text.replace(/</g,'&lt;')}</p>
                <button type="button" onclick="qvDeleteNote(${n.id})"
                        class="flex-shrink-0 w-5 h-5 flex items-center justify-center bg-red-100 dark:bg-red-900/30 text-red-400 rounded opacity-0 group-hover:opacity-100 transition-opacity hover:bg-red-200 mt-0.5">
                    <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="flex items-center gap-1 mt-0.5">
                <span class="text-[10px] font-medium text-gray-500">${n.user}</span>
                <span class="text-gray-300 dark:text-gray-600 text-[10px]">·</span>
                <span class="text-[10px] text-gray-400">${n.created_at}</span>
            </div>
        </div>`).join('');
}

async function qvAddNote() {
    const input = document.getElementById('qv-note-input');
    const text  = (input.value || '').trim();
    if (!text || !qvOrderId) return;
    input.disabled = true;
    try {
        const r = await fetch(`/admin/orders/${qvOrderId}/notes`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF() },
            body: JSON.stringify({ note_text: text, type: qvActiveNoteTab })
        });
        if (r.ok) {
            input.value = '';
            const data = await fetch(`/admin/orders/${qvOrderId}/quick-view`, { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF() } }).then(r => r.json());
            qvRenderNotes('order', data.order_notes || []);
            qvRenderNotes('customer', data.customer_notes || []);
            document.getElementById('qv-order-note-count').textContent = data.order_notes?.length ? `(${data.order_notes.length})` : '';
            document.getElementById('qv-customer-note-count').textContent = data.customer_notes?.length ? `(${data.customer_notes.length})` : '';
        } else {
            showToast('Note save করা যায়নি।', 'error');
        }
    } catch(e) {
        showToast('Note save করা যায়নি।', 'error');
    } finally { input.disabled = false; input.focus(); }
}

let _qvConfirmResolve = null;

function qvConfirm(message, okLabel = 'Delete') {
    return new Promise(resolve => {
        _qvConfirmResolve = resolve;
        document.getElementById('qv-confirm-message').textContent = message;
        document.getElementById('qv-confirm-ok').textContent = okLabel;
        document.getElementById('qv-confirm-modal').style.display = 'flex';
    });
}
function qvConfirmOk()     { document.getElementById('qv-confirm-modal').style.display = 'none'; if (_qvConfirmResolve) _qvConfirmResolve(true);  _qvConfirmResolve = null; }
function qvConfirmCancel() { document.getElementById('qv-confirm-modal').style.display = 'none'; if (_qvConfirmResolve) _qvConfirmResolve(false); _qvConfirmResolve = null; }

async function qvDeleteNote(noteId) {
    if (!await qvConfirm('এই note টি delete করতে চান?')) return;
    try {
        const r = await fetch(`/admin/orders/${qvOrderId}/notes/${noteId}`, {
            method: 'DELETE',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF() }
        });
        if (r.ok) {
            const data = await fetch(`/admin/orders/${qvOrderId}/quick-view`, { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF() } }).then(r => r.json());
            qvRenderNotes('order', data.order_notes || []);
            qvRenderNotes('customer', data.customer_notes || []);
            document.getElementById('qv-order-note-count').textContent = data.order_notes?.length ? `(${data.order_notes.length})` : '';
            document.getElementById('qv-customer-note-count').textContent = data.customer_notes?.length ? `(${data.customer_notes.length})` : '';
        } else {
            showToast('Note delete করা যায়নি।', 'error');
        }
    } catch(e) {
        showToast('Note delete করা যায়নি।', 'error');
    }
}

async function qvRemoveItem(itemId) {
    if (!await qvConfirm('এই product টি remove করতে চান?', 'Remove')) return;
    const r = await fetch(`/admin/orders/items/${itemId}`, {
        method: 'DELETE',
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF() }
    });
    if (r.ok) {
        const data = await fetch(`/admin/orders/${qvOrderId}/quick-view`, { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF() } }).then(r => r.json());
        qvPopulate(data);
    }
}

function qvSaveItemField(itemId, field, value) {
    clearTimeout(qvTimers[`item_${itemId}_${field}`]);
    qvTimers[`item_${itemId}_${field}`] = setTimeout(() => {
        qvShowSaving();
        fetch(`/admin/orders/${qvOrderId}/quick-field`, {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF() },
            body: JSON.stringify({ field: 'item_field', value: { item_id: itemId, field, value } })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                if (data.subtotal !== undefined) document.getElementById('qv-subtotal').textContent = fmt(data.subtotal);
                if (data.total   !== undefined) document.getElementById('qv-total').textContent    = fmt(data.total);
                if (data.due     !== undefined) document.getElementById('qv-due').textContent      = fmt(data.due);
                if (data.item_subtotal !== undefined) {
                    const el = document.querySelector(`.item-sub-${itemId}`);
                    if (el) el.textContent = fmt(data.item_subtotal);
                }
                qvShowSaved();
            }
        }).catch(() => qvHideSavingIndicators());
    }, 500);
}

// Instant item row recalculation
function qvUpdateItemCalc(itemId, row) {
    const priceEl = row.querySelector('[data-field="price"]');
    const qtyEl   = row.querySelector('[data-field="qty"]');
    const discEl  = row.querySelector('[data-field="disc"]');
    const subEl   = row.querySelector('.item-sub-' + itemId);

    const price = parseFloat(priceEl ? priceEl.textContent.replace(/[^\d.]/g, '') : 0) || 0;
    const qty   = Math.max(1, parseInt(qtyEl ? qtyEl.value : 1) || 1);
    const disc  = parseFloat(discEl ? discEl.value : 0) || 0;
    const sub   = Math.max(0, price * qty - disc);

    if (subEl) subEl.textContent = fmt(sub);

    // Recalculate order subtotal from all rows
    let orderSub = 0;
    document.querySelectorAll('#qv-items-tbody tr[data-item-id]').forEach(r => {
        const p2  = parseFloat((r.querySelector('[data-field="price"]') || {}).textContent?.replace(/[^\d.]/g,'') || 0) || 0;
        const q2  = parseInt((r.querySelector('[data-field="qty"]') || {}).value || 1) || 1;
        const d2  = parseFloat((r.querySelector('[data-field="disc"]') || {}).value || 0) || 0;
        orderSub += Math.max(0, p2 * q2 - d2);
    });

    document.getElementById('qv-subtotal').textContent = fmt(orderSub);
    qvCalcPayment();
}

// Instant local payment recalculation (no server round-trip)
function qvCalcPayment() {
    const subtotalText = document.getElementById('qv-subtotal').textContent.replace(/[^\d.]/g, '');
    const subtotal = parseFloat(subtotalText) || 0;
    const discount = parseFloat(document.getElementById('qv-discount').value) || 0;
    const shipping = parseFloat(document.getElementById('qv-shipping').value) || 0;
    const paid     = parseFloat(document.getElementById('qv-paid').value)     || 0;
    const total    = Math.max(0, subtotal - discount + shipping);
    const due      = Math.max(0, total - paid);
    document.getElementById('qv-total').textContent = fmt(total);
    document.getElementById('qv-due').textContent   = fmt(due);
}

function qvSaveField(field, value) {
    qvShowSaving();
    fetch(`/admin/orders/${qvOrderId}/quick-field`, {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF(), 'Accept': 'application/json' },
        body: JSON.stringify({ field, value })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            // Sync display with server-confirmed values
            if (data.subtotal !== undefined) document.getElementById('qv-subtotal').textContent = fmt(data.subtotal);
            if (data.total    !== undefined) document.getElementById('qv-total').textContent    = fmt(data.total);
            if (data.due      !== undefined) document.getElementById('qv-due').textContent      = fmt(data.due);
            if (field === 'status') refreshSidebarCounts();
            qvShowSaved();
        }
    })
    .catch(() => qvHideSavingIndicators());
}

function qvDebounceSave(field, value) {
    clearTimeout(qvTimers[field]);
    qvTimers[field] = setTimeout(() => qvSaveField(field, value), 700);
}

function qvShowSaving() {
    document.getElementById('qv-saving-indicator').classList.remove('hidden');
    document.getElementById('qv-saved-indicator').classList.add('hidden');
}
function qvShowSaved() {
    document.getElementById('qv-saving-indicator').classList.add('hidden');
    document.getElementById('qv-saved-indicator').classList.remove('hidden');
    setTimeout(() => document.getElementById('qv-saved-indicator').classList.add('hidden'), 2000);
}
function qvHideSavingIndicators() {
    document.getElementById('qv-saving-indicator').classList.add('hidden');
    document.getElementById('qv-saved-indicator').classList.add('hidden');
}

// ── Product Search & Add ──────────────────────────────────────
let qvProductTimer = null;

function qvToggleProductSearch() {
    const wrap = document.getElementById('qv-add-product-wrap');
    const input = document.getElementById('qv-product-search');
    const res = document.getElementById('qv-product-results');
    if (wrap.style.display === 'none') {
        wrap.style.display = 'block';
        input.value = '';
        res.style.display = 'none';
        setTimeout(() => input.focus(), 50);
    } else {
        wrap.style.display = 'none';
        input.value = '';
        res.style.display = 'none';
    }
}

function qvSearchProduct(q) {
    clearTimeout(qvProductTimer);
    const res = document.getElementById('qv-product-results');
    if (!q.trim()) { res.style.display = 'none'; return; }
    res.style.display = 'block';
    res.innerHTML = '<p class="text-center text-xs text-gray-400 py-3">Searching…</p>';
    qvProductTimer = setTimeout(async () => {
        try {
            const r = await fetch(`/admin/orders/${qvOrderId}/products-search?q=${encodeURIComponent(q)}`, {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF() }
            });
            const data = await r.json();
            if (!data.length) {
                res.innerHTML = '<p class="text-center text-xs text-gray-400 py-3">কোনো product পাওয়া যায়নি।</p>';
                return;
            }
            const rows = [];
            data.forEach(p => {
                if (p.variants && p.variants.length) {
                    p.variants.forEach(v => {
                        const label = p.name + (v.name.trim() ? ' — ' + v.name : '');
                        rows.push(`
                        <div class="flex items-center gap-2 px-3 py-2 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer border-b border-gray-100 dark:border-gray-700/50 last:border-0"
                             data-pid="${p.id}" data-vid="${v.id}" data-price="${v.price}"
                             data-name="${encodeURIComponent(label)}"
                             onclick="qvAddProduct(this)">
                            <img src="${p.image_url}" class="w-7 h-7 rounded object-cover flex-shrink-0" onerror="this.style.display='none'">
                            <div class="flex-1 min-w-0">
                                <div class="text-xs font-medium text-gray-800 dark:text-gray-200 truncate">${p.name}</div>
                                ${v.name.trim() ? `<div class="text-[10px] text-gray-400">${v.name}</div>` : ''}
                            </div>
                            <span class="text-xs font-semibold text-gray-700 dark:text-gray-200 flex-shrink-0 ml-2">৳${v.price}</span>
                        </div>`);
                    });
                } else {
                    const price = p.price ?? 0;
                    rows.push(`
                    <div class="flex items-center gap-2 px-3 py-2 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer border-b border-gray-100 dark:border-gray-700/50 last:border-0"
                         data-pid="${p.id}" data-vid="" data-price="${price}"
                         data-name="${encodeURIComponent(p.name)}"
                         onclick="qvAddProduct(this)">
                        <img src="${p.image_url}" class="w-7 h-7 rounded object-cover flex-shrink-0" onerror="this.style.display='none'">
                        <div class="flex-1 min-w-0">
                            <div class="text-xs font-medium text-gray-800 dark:text-gray-200 truncate">${p.name}</div>
                        </div>
                        <span class="text-xs font-semibold text-gray-700 dark:text-gray-200 flex-shrink-0 ml-2">৳${price}</span>
                    </div>`);
                }
            });
            res.innerHTML = rows.join('');
        } catch(e) {
            res.innerHTML = '<p class="text-center text-xs text-red-400 py-3">Error loading products.</p>';
        }
    }, 300);
}

async function qvAddProduct(el) {
    const productId = parseInt(el.dataset.pid);
    const variantId = el.dataset.vid ? parseInt(el.dataset.vid) : null;
    const name      = decodeURIComponent(el.dataset.name);
    const price     = parseFloat(el.dataset.price) || 0;

    document.getElementById('qv-product-results').style.display = 'none';
    document.getElementById('qv-product-search').value = '';
    document.getElementById('qv-add-product-wrap').style.display = 'none';
    qvShowSaving();

    try {
        const body = { product_id: productId, product_name: name, qty: 1, unit_price: price, total: price };
        if (variantId) body.variant_id = variantId;

        const r = await fetch(`/admin/orders/${qvOrderId}/items`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF(), 'Accept': 'application/json' },
            body: JSON.stringify(body)
        });
        const d = await r.json();
        if (d.success || r.ok) {
            // Refresh popup data to show new item + updated totals
            const r2 = await fetch(`/admin/orders/${qvOrderId}/quick-view`, {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF() }
            });
            const order = await r2.json();
            qvPopulate(order);
            qvShowSaved();
        }
    } catch(e) {
        qvHideSavingIndicators();
    }
}

// Close product results when clicking outside
document.addEventListener('click', e => {
    const wrap = document.getElementById('qv-add-product-wrap');
    if (wrap && !wrap.contains(e.target)) {
        const res = document.getElementById('qv-product-results');
        if (res) res.style.display = 'none';
    }
});

document.addEventListener('keydown', e => { if (e.key === 'Escape') closeQuickView(); });
</script>
@endpush

@endsection
