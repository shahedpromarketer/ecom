@extends('layouts.admin')

@section('title', 'Order #' . $order->order_number)
@section('page-title', 'Order Detail')


@section('content')


{{-- ALERTS --}}
@if(session('success'))
<div class="mb-4 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-2xl text-green-700 dark:text-green-400 text-sm">
    {{ session('success') }}
</div>
@endif
@if(session('error'))
<div class="mb-4 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-2xl text-red-700 dark:text-red-400 text-sm">
    {{ session('error') }}
</div>
@endif

{{-- MAIN FORM --}}
<form action="{{ route('admin.orders.update', $order->id) }}" method="POST" enctype="multipart/form-data" id="orderForm">
@csrf
@method('PUT')

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6" x-data="orderCalc()" x-init="init()">

{{-- ══════════════ LEFT COLUMN (2/3) ══════════════ --}}
<div class="lg:col-span-2 space-y-6">

{{-- CARD: SHIPPING ADDRESS --}}
<div class="bg-white dark:bg-gray-900 rounded-2xl shadow p-6">
    <h3 class="text-base font-semibold text-[#1B2A4A] dark:text-white mb-4 flex items-center gap-2">
        <span class="w-8 h-8 bg-[#1B2A4A]/10 rounded-lg flex items-center justify-center text-[#1B2A4A] dark:bg-[#1B2A4A]/30 dark:text-white"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg></span>
        Shipping Address
    </h3>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
        {{-- Mobile Number --}}
        <div class="sm:col-span-1">
            <label class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1 block">
                Mobile Number*
                <button type="button" onclick="blockPhone('{{ $order->shipping_phone }}')"
                    class="text-red-500 text-xs ml-1 hover:underline">(Block)</button>
            </label>
            <div class="flex items-center gap-1.5">
                <input type="text" name="primary_mobile" value="{{ old('primary_mobile', $order->shipping_phone) }}"
                    id="mobileInput" required maxlength="11"
                    class="flex-1 min-w-0 rounded-xl border border-gray-200 dark:border-gray-700 px-3 py-2 text-sm bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#1B2A4A]">
                <a href="https://wa.me/880{{ ltrim($order->shipping_phone, '0') }}" target="_blank"
                    class="flex-shrink-0 w-6 h-6 flex items-center justify-center bg-green-500 text-white rounded-md hover:bg-green-600 transition-colors" title="WhatsApp">
                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                </a>
                <a href="tel:{{ $order->shipping_phone }}"
                    class="flex-shrink-0 w-6 h-6 flex items-center justify-center bg-blue-500 text-white rounded-md hover:bg-blue-600 transition-colors" title="Call">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.948V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 7V5z"/></svg>
                </a>
                <button type="button" onclick="copyText('{{ $order->shipping_phone }}')"
                    class="flex-shrink-0 w-6 h-6 flex items-center justify-center bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 rounded-md hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors" title="Copy">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                </button>
            </div>
        </div>

        {{-- Alternative Number --}}
        <div>
            <label class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1 block">Alternative Number</label>
            <input type="text" name="alternative_mobile" value="{{ old('alternative_mobile', $order->alt_phone) }}"
                maxlength="11"
                class="w-full rounded-xl border border-gray-200 dark:border-gray-700 px-3 py-2 text-sm bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#1B2A4A]">
        </div>

        {{-- Full Name --}}
        <div>
            <label class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1 block">Full Name*</label>
            <input type="text" name="full_name" value="{{ old('full_name', $order->shipping_name) }}" required
                class="w-full rounded-xl border border-gray-200 dark:border-gray-700 px-3 py-2 text-sm bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#1B2A4A]">
        </div>

        {{-- Email --}}
        <div>
            <label class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1 block">Email</label>
            <div class="flex items-center gap-1">
                <input type="email" name="email" value="{{ old('email', $order->email) }}"
                    class="flex-1 rounded-xl border border-gray-200 dark:border-gray-700 px-3 py-2 text-sm bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#1B2A4A]">
                <button type="button" onclick="copyText('{{ $order->email ?? '' }}')"
                    class="w-8 h-8 flex items-center justify-center bg-gray-100 dark:bg-gray-700 rounded-lg"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg></button>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
        {{-- Address --}}
        <div>
            <label class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1 block">Address</label>
            <div class="flex items-center gap-1">
                <input type="text" name="address" value="{{ old('address', $order->shipping_address) }}"
                    class="flex-1 rounded-xl border border-gray-200 dark:border-gray-700 px-3 py-2 text-sm bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#1B2A4A]">
                <button type="button" onclick="copyText('{{ addslashes($order->shipping_address ?? '') }}')"
                    class="w-8 h-8 flex items-center justify-center bg-gray-100 dark:bg-gray-700 rounded-lg flex-shrink-0"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg></button>
            </div>
        </div>

        <div class="space-y-3">
            {{-- District --}}
            <div>
                <label class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1 block">District</label>
                <select name="district" id="districtSelect"
                    class="w-full rounded-xl border border-gray-200 dark:border-gray-700 px-3 py-2 text-sm bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#1B2A4A]">
                    <option value="">Select District</option>
                    @foreach($districts as $d)
                    <option value="{{ $d }}" {{ strtolower(trim($order->shipping_district ?? '')) === strtolower($d) ? 'selected' : '' }}>{{ $d }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    {{-- Customer Insight inline row --}}
    @php $phoneOrderCount = \App\Models\Order::where('shipping_phone', $order->shipping_phone)->count(); @endphp
    <div class="flex flex-wrap items-center gap-4 mt-4 pt-4 border-t dark:border-gray-700">
        <div class="flex items-center gap-1 text-sm">
            <span class="text-gray-500 dark:text-gray-400 text-xs">Total Orders:</span>
            <span class="font-bold text-[#1B2A4A] dark:text-white">{{ $phoneOrderCount }}</span>
            <a href="{{ route('admin.orders.index', ['search' => $order->shipping_phone]) }}" class="text-xs text-[#F59E0B] hover:underline ml-1" target="_blank">View All ↗</a>
            @if($order->customer_id)
                @can('view_customers')
                <a href="{{ route('admin.customers.show', $order->customer_id) }}" class="text-xs text-primary hover:underline ml-2">Customer profile →</a>
                <a href="{{ route('admin.customers.show', $order->customer_id) }}#followups" class="text-xs text-primary hover:underline ml-2">Schedule follow-up</a>
                @endcan
            @endif
        </div>
        <div class="flex items-center gap-1 text-sm">
            <span class="text-gray-500 dark:text-gray-400 text-xs">Completed:</span>
            <span class="font-bold text-green-600">{{ \App\Models\Order::where('shipping_phone', $order->shipping_phone)->whereIn('status', ['delivered','completed'])->count() }}</span>
        </div>
        <div class="flex items-center gap-1 text-sm">
            <span class="text-gray-500 dark:text-gray-400 text-xs">Source:</span>
            <span class="text-gray-700 dark:text-gray-300 text-xs font-medium">{{ $order->source ?? 'Direct' }}</span>
        </div>
        <div class="flex items-center gap-2 ml-auto">
            <div class="relative">
                <select name="order_tag" class="appearance-none rounded-lg border border-gray-200 dark:border-gray-700 px-2 py-1 pr-6 text-xs bg-white dark:bg-gray-800 dark:text-white" style="-webkit-appearance:none;-moz-appearance:none;appearance:none;background-image:none">
                    <option value="">Order Tag</option>
                    <option value="vip" {{ $order->order_tag == 'vip' ? 'selected' : '' }}>VIP</option>
                    <option value="regular" {{ $order->order_tag == 'regular' ? 'selected' : '' }}>Regular</option>
                    <option value="problematic" {{ $order->order_tag == 'problematic' ? 'selected' : '' }}>Problematic</option>
                </select>
                <div class="pointer-events-none absolute inset-y-0 right-1.5 flex items-center">
                    <svg class="w-3 h-3 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </div>
            </div>
            <div class="relative">
                <select name="customer_tag" class="appearance-none rounded-lg border border-gray-200 dark:border-gray-700 px-2 py-1 pr-6 text-xs bg-white dark:bg-gray-800 dark:text-white" style="-webkit-appearance:none;-moz-appearance:none;appearance:none;background-image:none">
                    <option value="">Customer Tag</option>
                    <option value="loyal" {{ $order->customer_tag == 'loyal' ? 'selected' : '' }}>Loyal</option>
                    <option value="new" {{ $order->customer_tag == 'new' ? 'selected' : '' }}>New</option>
                    <option value="fraud" {{ $order->customer_tag == 'fraud' ? 'selected' : '' }}>Fraud Risk</option>
                </select>
                <div class="pointer-events-none absolute inset-y-0 right-1.5 flex items-center">
                    <svg class="w-3 h-3 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- CARD: COURIER SUCCESS RATE --}}
<div class="bg-white dark:bg-gray-900 rounded-2xl shadow p-6" x-data="{ open: true }">
    <div class="flex items-center justify-between cursor-pointer" @click="open = !open">
        <h3 class="text-base font-semibold text-[#1B2A4A] dark:text-white">Courier Success Rate</h3>
        <div class="flex items-center gap-2">
            <button type="button" onclick="refreshCourierStats(event)" class="text-xs text-[#F59E0B] hover:underline flex items-center gap-1"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg> Refresh</button>
            <span x-text="open ? '▲' : '▼'" class="text-gray-400 text-xs"></span>
        </div>
    </div>
    <div x-show="open" x-transition class="mt-4">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-xs text-gray-500 dark:text-gray-400 border-b dark:border-gray-700">
                        <th class="text-left pb-2">Courier</th>
                        <th class="text-left pb-2">Score</th>
                        <th class="text-center pb-2">Total</th>
                        <th class="text-center pb-2">Success</th>
                        <th class="text-center pb-2">Failed</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($activeCouriers as $courier)
                    @php
                        $cName   = $courier->courier;
                        $total   = \App\Models\CourierShipment::where('courier_name', $cName)->count();
                        $success = \App\Models\CourierShipment::where('courier_name', $cName)
                            ->whereHas('order', fn($q) => $q->whereIn('status', ['delivered','completed']))->count();
                        $failed  = \App\Models\CourierShipment::where('courier_name', $cName)
                            ->whereHas('order', fn($q) => $q->whereIn('status', ['cancelled','returned','failed']))->count();
                        $pct     = $total > 0 ? round(($success / $total) * 100) : 0;
                        $barCls  = $pct >= 70 ? 'bg-green-500' : ($pct >= 40 ? 'bg-yellow-500' : 'bg-red-500');
                        $txtCls  = $pct >= 70 ? 'text-green-600' : ($pct >= 40 ? 'text-yellow-600' : 'text-red-600');
                    @endphp
                    <tr class="border-b dark:border-gray-800">
                        <td class="py-2 font-medium text-gray-700 dark:text-gray-300 capitalize">{{ $cName }}</td>
                        <td class="py-2 w-32">
                            <div class="flex items-center gap-2">
                                <div class="flex-1 h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                    <div class="h-full rounded-full {{ $barCls }}" style="width: {{ $pct }}%"></div>
                                </div>
                                <span class="text-xs font-bold {{ $txtCls }}">{{ $pct }}%</span>
                            </div>
                        </td>
                        <td class="py-2 text-center text-gray-600 dark:text-gray-400">{{ $total }}</td>
                        <td class="py-2 text-center text-green-600 font-medium">{{ $success }}</td>
                        <td class="py-2 text-center text-red-500 font-medium">{{ $failed }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- CARD: ORDER PRODUCTS --}}
<div class="bg-white dark:bg-gray-900 rounded-2xl shadow p-6">
    <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
        <h3 class="text-base font-semibold text-[#1B2A4A] dark:text-white flex items-center gap-2"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg> Order Products</h3>
        <div class="flex gap-2">
            <button type="button" onclick="openProductModal()"
                class="bg-[#F59E0B] text-black px-4 py-2 rounded-xl text-xs font-medium hover:bg-yellow-500 transition">
                + Add More Item
            </button>
            <button type="button" onclick="togglePackingItems()"
                class="inline-flex items-center gap-1.5 border border-gray-300 dark:border-gray-600 px-4 py-2 rounded-xl text-xs font-medium text-gray-700 dark:text-white hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                Show Packing Items
            </button>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm" id="productsTable">
            <thead>
                <tr class="text-xs text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-800 rounded-xl">
                    <th class="text-left px-3 py-2 rounded-l-xl">Product</th>
                    <th class="text-center px-3 py-2">Unit Price</th>
                    <th class="text-center px-3 py-2">Qty</th>
                    <th class="text-center px-3 py-2">Stock</th>
                    <th class="text-center px-3 py-2">Discount</th>
                    <th class="text-center px-3 py-2">Subtotal</th>
                    <th class="text-center px-3 py-2 rounded-r-xl">Action</th>
                </tr>
            </thead>
            <tbody id="productRows">
                @foreach($order->items as $index => $item)
                @php
                    $stock    = $item->product ? ($item->product->variants->sum('stock') ?: ($item->product->stock ?? 0)) : 0;
                    $rowDisc  = $item->discount ?? 0;
                    $rowSub   = ($item->unit_price * $item->qty) - $rowDisc;
                @endphp
                <tr class="product-row border-b dark:border-gray-800" data-row="{{ $index }}">
                    <td class="px-3 py-3">
                        <div class="flex items-center gap-3">
                            @if($item->product?->images->first())
                            <img src="{{ $item->product->images->first()->url }}"
                                class="w-12 h-12 object-cover rounded-lg border dark:border-gray-700" alt="">
                            @else
                            <div class="w-12 h-12 bg-gray-100 dark:bg-gray-700 rounded-lg flex items-center justify-center text-gray-400 text-xs">No Img</div>
                            @endif
                            <div>
                                <div class="font-medium text-gray-800 dark:text-gray-200 text-xs leading-tight">{{ $item->product_name }}</div>
                                <select name="items[{{ $index }}][variant]" class="mt-1 text-xs border border-gray-200 dark:border-gray-700 rounded-lg px-2 py-1 bg-white dark:bg-gray-800 dark:text-white">
                                    @if($item->product)
                                        @foreach($item->product->variants as $v)
                                        <option value="{{ trim($v->size.' '.$v->color) }}" {{ $item->variant_info === trim($v->size.' '.$v->color) ? 'selected' : '' }}>
                                            {{ trim($v->size.' '.$v->color) }}
                                        </option>
                                        @endforeach
                                    @endif
                                    @if($item->variant_info && !($item->product?->variants->count()))
                                    <option value="{{ $item->variant_info }}" selected>{{ $item->variant_info }}</option>
                                    @endif
                                </select>
                                <input type="hidden" name="items[{{ $index }}][product_id]" value="{{ $item->product_id }}">
                                <input type="hidden" name="items[{{ $index }}][id]" value="{{ $item->id }}">
                            </div>
                        </div>
                    </td>
                    <td class="px-3 py-3 text-center">
                        <input type="number" name="items[{{ $index }}][unit_price]" value="{{ $item->unit_price }}"
                            class="w-24 text-center rounded-lg border border-gray-200 dark:border-gray-700 px-2 py-1 text-sm bg-white dark:bg-gray-800 dark:text-white item-price"
                            step="0.01" onchange="recalcRow(this)">
                    </td>
                    <td class="px-3 py-3 text-center">
                        <input type="number" name="items[{{ $index }}][quantity]" value="{{ $item->qty }}"
                            class="w-16 text-center rounded-lg border border-gray-200 dark:border-gray-700 px-2 py-1 text-sm bg-white dark:bg-gray-800 dark:text-white item-qty"
                            min="1" onchange="recalcRow(this)">
                    </td>
                    <td class="px-3 py-3 text-center">
                        <span class="px-2 py-1 rounded-lg text-xs font-bold {{ $stock < 0 ? 'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400' : 'bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400' }}">{{ $stock }}</span>
                    </td>
                    <td class="px-3 py-3 text-center">
                        <input type="number" name="items[{{ $index }}][discount]" value="{{ $rowDisc }}"
                            class="w-20 text-center rounded-lg border border-gray-200 dark:border-gray-700 px-2 py-1 text-sm bg-white dark:bg-gray-800 dark:text-white item-discount"
                            min="0" onchange="recalcRow(this)">
                    </td>
                    <td class="px-3 py-3 text-center">
                        <span class="item-subtotal font-bold text-[#1B2A4A] dark:text-white text-sm">৳{{ number_format($rowSub, 2) }}</span>
                    </td>
                    <td class="px-3 py-3 text-center">
                        <button type="button" onclick="removeProductRow(this)"
                            class="w-8 h-8 flex items-center justify-center bg-red-100 dark:bg-red-900/30 text-red-500 rounded-lg hover:bg-red-200 mx-auto"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Order Logistics --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-6 pt-4 border-t dark:border-gray-700">
        <div>
            <label class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1 block">Order Status*</label>
            <select name="order_status" required
                class="cs w-full rounded-xl border border-gray-200 dark:border-gray-700 pl-3 py-2 text-sm bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#1B2A4A] cursor-pointer">
                @foreach(['pending'=>'Pending','confirmed'=>'Confirmed','processing'=>'Processing','shipped'=>'Shipped','delivered'=>'Delivered','cancelled'=>'Cancelled','hold'=>'Hold','returned'=>'Returned','damage'=>'Damage','hand_delivery'=>'Hand Delivery','completed'=>'Completed'] as $s => $l)
                <option value="{{ $s }}" {{ $order->status === $s ? 'selected' : '' }}>{{ $l }}</option>
                @endforeach
            </select>
            <button type="button" onclick="reOrderNow()"
                class="mt-2 flex items-center gap-2 px-4 py-2 bg-[#F59E0B] text-black rounded-xl text-xs font-semibold hover:bg-yellow-500 transition w-full justify-center">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg> Re-Order
            </button>
        </div>
        <div>
            <label class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1 block">Payment Method</label>
            <select name="payment_method"
                class="cs w-full rounded-xl border border-gray-200 dark:border-gray-700 pl-3 py-2 text-sm bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#1B2A4A] cursor-pointer">
                @foreach(['cod'=>'Cash on Delivery','bkash'=>'bKash','nagad'=>'Nagad','rocket'=>'Rocket','card'=>'Bank Transfer'] as $v=>$l)
                <option value="{{ $v }}" {{ $order->payment_method === $v ? 'selected' : '' }}>{{ $l }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1 block">Attachments</label>
            <input type="file" name="attachments[]" multiple accept="image/*,.pdf"
                class="w-full rounded-xl border border-gray-200 dark:border-gray-700 px-3 py-2 text-sm bg-white dark:bg-gray-800 dark:text-white">
        </div>
        <div class="sm:col-span-3 grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1 block">Courier Note</label>
                <textarea name="courier_note" rows="2"
                    class="w-full rounded-xl border border-gray-200 dark:border-gray-700 px-3 py-2 text-sm bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#1B2A4A] resize-none">{{ old('courier_note', $order->courier_note) }}</textarea>
            </div>
            <div>
                <label class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1 block">Packing Note</label>
                @php
                    $packingNoteValue = old('packing_note', $order->packing_note);
                    if (empty($packingNoteValue)) {
                        $packingNoteValue = $order->items
                            ->filter(fn($i) => !empty($i->variant_info))
                            ->map(fn($i) => $i->product_name . ': ' . $i->variant_info . ' x' . $i->qty)
                            ->implode("\n");
                    }
                @endphp
                <textarea name="packing_note" rows="2"
                    class="w-full rounded-xl border border-gray-200 dark:border-gray-700 px-3 py-2 text-sm bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#1B2A4A] resize-none">{{ $packingNoteValue }}</textarea>
            </div>
        </div>
    </div>

</div>

{{-- CARD: ORDER NOTES --}}
@php $orderNoteCount = $order->orderNotes->filter(fn($n) => $n->type === 'order')->count(); @endphp
<div class="bg-white dark:bg-gray-900 rounded-2xl shadow" x-data="{ open: false }">
    {{-- Accordion Header --}}
    <div class="flex items-center justify-between px-5 py-4 cursor-pointer select-none"
         @click="open = !open">
        <div class="flex items-center gap-2">
            <svg class="w-4 h-4 text-blue-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            <span class="text-sm font-semibold text-[#1B2A4A] dark:text-white">Order Notes</span>
            <svg class="w-4 h-4 text-black dark:text-white transition-transform duration-200"
                 :class="open ? 'rotate-180' : ''"
                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
            </svg>
            @if($orderNoteCount > 0)
            <span class="px-1.5 py-0.5 bg-blue-100 dark:bg-blue-900/40 text-blue-600 dark:text-blue-400 text-xs font-bold rounded-full">{{ $orderNoteCount }}</span>
            @endif
        </div>
        <button type="button" onclick="event.stopPropagation(); showAddNoteModal('order')"
            class="inline-flex items-center gap-1 bg-[#1B2A4A] text-white px-3 py-1.5 rounded-lg text-xs font-medium hover:bg-[#243a63] transition">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Add Note
        </button>
    </div>
    {{-- Accordion Body --}}
    <div x-show="open" x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-1"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-1">
        <div class="border-t border-gray-100 dark:border-gray-700/60 px-5 pb-5 pt-3">
            <div id="orderNotesBody" class="space-y-2">
                @forelse($order->orderNotes->filter(fn($n) => $n->type === 'order')->sortByDesc('created_at') as $note)
                <div class="group relative bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-800/30 rounded-xl px-3.5 py-3" id="note-row-{{ $note->id }}">
                    <p class="text-sm text-gray-800 dark:text-gray-200 leading-relaxed pr-7">{{ $note->note_text }}</p>
                    <div class="flex items-center gap-1.5 mt-2">
                        <div class="w-4 h-4 rounded-full bg-[#1B2A4A] flex items-center justify-center text-white text-[9px] font-bold flex-shrink-0">{{ substr($note->user->name ?? 'S', 0, 1) }}</div>
                        <span class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ $note->user->name ?? 'System' }}</span>
                        <span class="text-gray-300 dark:text-gray-600 text-xs">·</span>
                        <span class="text-xs text-gray-400 dark:text-gray-500">{{ $note->created_at->format('d M Y, H:i') }}</span>
                    </div>
                    <button type="button" onclick="deleteNote({{ $note->id }})"
                        class="absolute top-2.5 right-2.5 w-5 h-5 flex items-center justify-center bg-red-100 dark:bg-red-900/40 text-red-400 rounded-md hover:bg-red-200 opacity-0 group-hover:opacity-100 transition-opacity">
                        <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                @empty
                <div id="noOrderNotes" class="flex flex-col items-center justify-center py-6 text-gray-400">
                    <svg class="w-8 h-8 mb-1.5 text-gray-200 dark:text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <p class="text-xs">No notes yet</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

{{-- CARD: CUSTOMER NOTES --}}
@php $custNoteCount = $order->orderNotes->filter(fn($n) => $n->type === 'customer')->count(); @endphp
<div class="bg-white dark:bg-gray-900 rounded-2xl shadow" x-data="{ open: false }">
    {{-- Accordion Header --}}
    <div class="flex items-center justify-between px-5 py-4 cursor-pointer select-none"
         @click="open = !open">
        <div class="flex items-center gap-2">
            <svg class="w-4 h-4 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
            <span class="text-sm font-semibold text-[#1B2A4A] dark:text-white">Customer Notes</span>
            <svg class="w-4 h-4 text-black dark:text-white transition-transform duration-200"
                 :class="open ? 'rotate-180' : ''"
                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
            </svg>
            @if($custNoteCount > 0)
            <span class="px-1.5 py-0.5 bg-amber-100 dark:bg-amber-900/40 text-amber-600 dark:text-amber-400 text-xs font-bold rounded-full">{{ $custNoteCount }}</span>
            @endif
        </div>
        <button type="button" onclick="event.stopPropagation(); showAddNoteModal('customer')"
            class="inline-flex items-center gap-1 bg-[#1B2A4A] text-white px-3 py-1.5 rounded-lg text-xs font-medium hover:bg-[#243a63] transition">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Add Note
        </button>
    </div>
    {{-- Accordion Body --}}
    <div x-show="open" x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-1"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-1">
        <div class="border-t border-gray-100 dark:border-gray-700/60 px-5 pb-5 pt-3">
            <div id="customerNotesBody" class="space-y-2">
                @forelse($order->orderNotes->filter(fn($n) => $n->type === 'customer')->sortByDesc('created_at') as $note)
                <div class="group relative bg-amber-50 dark:bg-amber-900/10 border border-amber-100 dark:border-amber-800/30 rounded-xl px-3.5 py-3" id="note-row-{{ $note->id }}">
                    <p class="text-sm text-gray-800 dark:text-gray-200 leading-relaxed pr-7">{{ $note->note_text }}</p>
                    <div class="flex items-center gap-1.5 mt-2">
                        <div class="w-4 h-4 rounded-full bg-amber-500 flex items-center justify-center text-white text-[9px] font-bold flex-shrink-0">{{ substr($note->user->name ?? 'S', 0, 1) }}</div>
                        <span class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ $note->user->name ?? 'System' }}</span>
                        <span class="text-gray-300 dark:text-gray-600 text-xs">·</span>
                        <span class="text-xs text-gray-400 dark:text-gray-500">{{ $note->created_at->format('d M Y, H:i') }}</span>
                    </div>
                    <button type="button" onclick="deleteNote({{ $note->id }})"
                        class="absolute top-2.5 right-2.5 w-5 h-5 flex items-center justify-center bg-red-100 dark:bg-red-900/40 text-red-400 rounded-md hover:bg-red-200 opacity-0 group-hover:opacity-100 transition-opacity">
                        <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                @empty
                <div class="flex flex-col items-center justify-center py-6 text-gray-400">
                    <svg class="w-8 h-8 mb-1.5 text-gray-200 dark:text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                    <p class="text-xs">No customer notes yet</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

{{-- CARD: ACTIVITY LOG --}}
<div class="bg-white dark:bg-gray-900 rounded-2xl shadow p-6" x-data="{ open: false }">
    <div class="flex items-center justify-between cursor-pointer" @click="open = !open">
        <h3 class="flex items-center gap-2 text-base font-semibold text-[#1B2A4A] dark:text-white">
            Employee Activity Log
            <svg class="w-4 h-4 text-black dark:text-white transition-transform duration-200 flex-shrink-0"
                 :class="open ? 'rotate-180' : ''"
                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
            </svg>
        </h3>
    </div>
    <div x-show="open" x-transition class="mt-4">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-xs text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-800">
                        <th class="text-left px-3 py-2 rounded-l-xl">SL</th>
                        <th class="text-left px-3 py-2">Date</th>
                        <th class="text-left px-3 py-2">Employee</th>
                        <th class="text-left px-3 py-2">Action</th>
                        <th class="text-left px-3 py-2">Old Data</th>
                        <th class="text-left px-3 py-2 rounded-r-xl">Updated Data</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($order->activityLogs->take(10) as $i => $log)
                    <tr class="border-b dark:border-gray-800">
                        <td class="px-3 py-2 text-gray-500">{{ $i + 1 }}</td>
                        <td class="px-3 py-2 text-gray-400 text-xs">{{ $log->created_at->format('d M Y H:i') }}</td>
                        <td class="px-3 py-2 text-gray-700 dark:text-gray-300">{{ $log->user->name ?? 'System' }}</td>
                        <td class="px-3 py-2"><span class="px-2 py-0.5 bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-lg text-xs">{{ $log->action_performed ?? $log->action }}</span></td>
                        <td class="px-3 py-2 text-xs text-gray-500 max-w-xs truncate">
                            @if($log->old_data) {{ Str::limit(json_encode($log->old_data), 80) }} @else — @endif
                        </td>
                        <td class="px-3 py-2 text-xs text-gray-500 max-w-xs truncate">
                            @if($log->updated_data) {{ Str::limit(json_encode($log->updated_data), 80) }} @else {{ $log->description }} @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center py-4 text-gray-400 text-sm">No activity yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <p class="text-xs text-gray-400 mt-3 italic">NB: 90 days older entries will be deleted automatically!</p>
    </div>
</div>


</div>{{-- END LEFT COLUMN --}}

{{-- ══════════════ RIGHT COLUMN: SUMMARY SIDEBAR ══════════════ --}}
<div class="lg:col-span-1">
<div class="bg-white dark:bg-gray-900 rounded-2xl shadow p-6 lg:sticky lg:top-4 space-y-4">

    {{-- Summary Header --}}
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-bold text-gray-800 dark:text-white">Summary</h3>
        <button type="button" onclick="copyOrderInfo()"
            class="bg-[#F59E0B] text-black px-3 py-1.5 rounded-xl text-xs font-medium hover:bg-yellow-500 transition flex items-center gap-1">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg> Copy Info
        </button>
    </div>

    {{-- Invoice Meta --}}
    <div class="space-y-1 mb-4 text-sm">
        <div><span class="font-bold text-gray-800 dark:text-white">Invoice ID: </span><span class="text-gray-700 dark:text-gray-300">#{{ str_pad($order->id, 5, '0', STR_PAD_LEFT) }}</span></div>
        <div><span class="font-bold text-gray-800 dark:text-white">Created Date: </span><span class="text-gray-600 dark:text-gray-400">{{ $order->created_at->format('d/m/Y g:ia') }}</span></div>
        <div><span class="font-bold text-gray-800 dark:text-white">Last Update: </span><span class="text-gray-600 dark:text-gray-400">{{ $order->updated_at->format('d/m/Y g:ia') }}</span></div>
        <div class="flex items-center gap-2">
            <span class="font-bold text-gray-800 dark:text-white">User IP: </span>
            <span class="text-gray-600 dark:text-gray-400">{{ $order->user_ip ?? 'N/A' }}</span>
            @if($order->user_ip)
            <button type="button" onclick="blockIP('{{ $order->user_ip }}')" class="text-blue-500 text-xs hover:underline">(Block IP)</button>
            @endif
        </div>
        {{-- Courier Info --}}
        <div class="border-t dark:border-gray-700 pt-2 mt-1 space-y-1">
            <div><span class="font-bold text-gray-800 dark:text-white">Courier: </span><span class="text-gray-600 dark:text-gray-400">{{ $order->shipment?->courier_name ? ucfirst($order->shipment->courier_name) : 'N/A' }}</span></div>
            <div><span class="font-bold text-gray-800 dark:text-white">Courier Status: </span>
                @if($order->shipment?->status)
                <span class="px-1.5 py-0.5 rounded text-xs font-semibold
                    {{ $order->shipment->status === 'submitted' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' :
                       ($order->shipment->status === 'delivered' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' :
                       ($order->shipment->status === 'returned' ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' :
                       'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300')) }}">
                    {{ ucfirst($order->shipment->status) }}
                </span>
                @else
                <span class="text-gray-400">N/A</span>
                @endif
            </div>
            <div class="flex items-center gap-1 flex-wrap">
                <span class="font-bold text-gray-800 dark:text-white">Courier Inv: </span>
                <span class="text-gray-600 dark:text-gray-400 text-xs break-all" id="courierInvDisplay">{{ $order->shipment?->tracking_code ?? 'N/A' }}</span>
                @if($order->shipment)
                @php
                    $tc = $order->shipment->tracking_code;
                    // Use API-provided tracking_link if available (e.g. Steadfast returns it)
                    $trackingUrl = $order->shipment->tracking_link ?? ($tc ? match($order->shipment->courier_name) {
                        'pathao'   => 'https://pathao.com/parcel/' . $tc,
                        'redx'     => 'https://redx.com.bd/track-parcel/?trackingId=' . $tc,
                        'paperfly' => 'https://paperfly.com.bd/tracking/' . $tc,
                        'carrybe'  => 'https://carrybe.com/track?tracking=' . $tc,
                        default    => null,
                    } : null);
                @endphp
                @if($trackingUrl)
                {{-- Link icon: courier tracking page --}}
                <a href="{{ $trackingUrl }}" target="_blank"
                   class="w-6 h-6 flex items-center justify-center bg-blue-100 dark:bg-blue-900/30 text-blue-600 rounded-md hover:bg-blue-200 flex-shrink-0" title="Courier ওয়েবসাইটে ট্র্যাক করুন">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                </a>
                {{-- Eye icon: fetch tracking history --}}
                <button type="button" onclick="fetchCourierTracking()"
                    class="w-6 h-6 flex items-center justify-center bg-purple-100 dark:bg-purple-900/30 text-purple-600 rounded-md hover:bg-purple-200 flex-shrink-0" title="ট্র্যাকিং হিস্ট্রি দেখুন">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                </button>
                @endif
                {{-- Edit icon: always visible when shipment exists --}}
                <button type="button" onclick="openEditTracking()"
                    class="w-6 h-6 flex items-center justify-center bg-amber-100 dark:bg-amber-900/30 text-amber-600 rounded-md hover:bg-amber-200 flex-shrink-0" title="Tracking code এডিট করুন">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </button>
                @endif
            </div>
            <div><span class="font-bold text-gray-800 dark:text-white">Courier Inv 2: </span><span class="text-gray-600 dark:text-gray-400 text-xs break-all">{{ $order->shipment?->consignment_id ?? 'N/A' }}</span></div>
            <div><span class="font-bold text-gray-800 dark:text-white">Courier Submitted At: </span><span class="text-gray-600 dark:text-gray-400 text-xs">{{ $order->shipment?->created_at?->format('d/m/Y g:ia') ?? 'N/A' }}</span></div>
        </div>
        <div class="flex items-center gap-2">
            <span class="font-bold text-gray-800 dark:text-white">Assigned To: </span>
            <div class="relative flex-1">
                <select name="assigned_to" class="appearance-none w-full rounded-lg border border-gray-200 dark:border-gray-700 px-2 py-1 pr-6 text-xs bg-white dark:bg-gray-800 dark:text-white" style="-webkit-appearance:none;-moz-appearance:none;appearance:none;background-image:none">
                    <option value="">Unassigned</option>
                    @foreach($admins as $admin)
                    <option value="{{ $admin->id }}" {{ $order->assigned_to == $admin->id ? 'selected' : '' }}>{{ $admin->name }}</option>
                    @endforeach
                </select>
                <div class="pointer-events-none absolute inset-y-0 right-1.5 flex items-center">
                    <svg class="w-3 h-3 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </div>
            </div>
            <a href="#" class="text-[#F59E0B] text-xs hover:underline whitespace-nowrap">(Transfer To)</a>
        </div>
    </div>

    {{-- Reference No --}}
    <div class="mb-1">
        <label class="text-sm font-bold text-gray-800 dark:text-white block mb-1">Reference No</label>
        <input type="text" name="reference_no" value="{{ $order->reference_no ?? '' }}"
            class="w-full rounded-lg border border-gray-200 dark:border-gray-700 px-3 py-1.5 text-sm bg-white dark:bg-gray-800 dark:text-white">
    </div>

    {{-- Source Website --}}
    <div class="mb-4">
        <span class="text-sm font-bold text-gray-800 dark:text-white">Source Website: </span>
        <span class="text-sm text-gray-600 dark:text-gray-400">{{ $order->source_website ?? $order->source ?? '' }}</span>
    </div>

    {{-- Platform --}}
    @if($order->platform)
    <div class="mb-4">
        <span class="text-sm font-bold text-gray-800 dark:text-white">Platform: </span>
        @php
            $platformMap = [
                'facebook'          => ['label' => 'Facebook',  'color' => 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300'],
                'google'            => ['label' => 'Google',    'color' => 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300'],
                'tiktok'            => ['label' => 'TikTok',    'color' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200'],
                'instagram'         => ['label' => 'Instagram', 'color' => 'bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-300'],
                'youtube'           => ['label' => 'YouTube',   'color' => 'bg-red-100 text-red-600 dark:bg-red-900 dark:text-red-300'],
                'organic'           => ['label' => 'Organic',   'color' => 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300'],
                'direct'            => ['label' => 'Direct',    'color' => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300'],
                'referral'          => ['label' => 'Referral',  'color' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300'],
            ];
            $p = $platformMap[strtolower($order->platform)] ?? ['label' => ucfirst($order->platform), 'color' => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300'];
        @endphp
        <span class="inline-block px-2 py-0.5 rounded text-xs font-semibold {{ $p['color'] }}">
            {{ $p['label'] }}
        </span>
    </div>
    @endif

    {{-- Financial Table --}}
    <table class="w-full text-sm mb-4 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
        <tbody>
            <tr class="border-b border-gray-200 dark:border-gray-700">
                <td class="px-3 py-2 font-bold text-gray-800 dark:text-white bg-gray-50 dark:bg-gray-800">Subtotal:</td>
                <td class="px-3 py-2 text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-900" x-text="'৳' + subtotal.toFixed(2) + ' Tk'">৳{{ number_format($order->subtotal ?? 0, 2) }} Tk</td>
            </tr>
            <input type="hidden" name="subtotal" :value="subtotal">
            <tr class="border-b border-gray-200 dark:border-gray-700">
                <td class="px-3 py-2 font-bold text-gray-800 dark:text-white bg-gray-50 dark:bg-gray-800">
                    Discount:
                    <button type="button" @click="discountType = discountType === 'fixed' ? 'percent' : 'fixed'"
                        class="ml-1 px-1.5 py-0.5 rounded border border-gray-300 dark:border-gray-600 text-xs dark:text-white"
                        x-text="discountType === 'fixed' ? '৳' : '%'">৳</button>
                </td>
                <td class="px-3 py-2 bg-white dark:bg-gray-900">
                    <input type="number" name="discount_value" x-model="discountValue" step="0.01" min="0"
                        class="w-full rounded border border-gray-200 dark:border-gray-700 px-2 py-1 text-sm bg-white dark:bg-gray-800 dark:text-white text-right">
                    <input type="hidden" name="discount_type" :value="discountType">
                </td>
            </tr>
            <tr class="border-b border-gray-200 dark:border-gray-700">
                <td class="px-3 py-2 font-bold text-gray-800 dark:text-white bg-gray-50 dark:bg-gray-800">Shipping Charge:</td>
                <td class="px-3 py-2 bg-white dark:bg-gray-900">
                    <input type="number" name="shipping_charge" x-model="shipping" step="0.01" min="0"
                        class="w-full rounded border border-gray-200 dark:border-gray-700 px-2 py-1 text-sm bg-white dark:bg-gray-800 dark:text-white text-right">
                </td>
            </tr>
            <tr class="border-b border-gray-200 dark:border-gray-700">
                <td class="px-3 py-2 font-bold text-gray-800 dark:text-white bg-gray-50 dark:bg-gray-800">Grand Total:</td>
                <td class="px-3 py-2 font-bold text-gray-800 dark:text-white bg-white dark:bg-gray-900" x-text="'৳' + grandTotal.toFixed(2) + ' Tk'"></td>
                <input type="hidden" name="grand_total" :value="grandTotal">
            </tr>
            <tr class="border-b border-gray-200 dark:border-gray-700">
                <td class="px-3 py-2 font-bold text-gray-800 dark:text-white bg-gray-50 dark:bg-gray-800">Paid Amount:</td>
                <td class="px-3 py-2 bg-white dark:bg-gray-900">
                    <input type="number" name="paid_amount" x-model="paid" step="0.01" min="0"
                        class="w-full rounded border border-gray-200 dark:border-gray-700 px-2 py-1 text-sm bg-white dark:bg-gray-800 dark:text-white text-right mb-1">
                    <a href="#" onclick="openAddPaymentModal()" class="text-[#F59E0B] text-xs hover:underline">Add Payment</a>
                </td>
            </tr>
            <tr class="border-b border-gray-200 dark:border-gray-700">
                <td class="px-3 py-2 font-bold text-gray-800 dark:text-white bg-gray-50 dark:bg-gray-800">Due:</td>
                <td class="px-3 py-2 font-bold bg-white dark:bg-gray-900" :class="due > 0 ? 'text-red-500' : 'text-green-500'" x-text="'৳' + due.toFixed(2) + ' Tk'"></td>
                <input type="hidden" name="due" :value="due">
            </tr>
            <tr>
                <td class="px-3 py-2 font-bold text-gray-800 dark:text-white bg-gray-50 dark:bg-gray-800">Courier Charged to Me:</td>
                <td class="px-3 py-2 bg-white dark:bg-gray-900">
                    <input type="number" name="courier_charge" x-model="courierCharge" step="0.01" min="0"
                        class="w-full rounded border border-gray-200 dark:border-gray-700 px-2 py-1 text-sm bg-white dark:bg-gray-800 dark:text-white text-right">
                </td>
            </tr>
        </tbody>
    </table>

    {{-- Apply Coupon --}}
    <div class="flex justify-end mb-3">
        <a href="#" onclick="applyCoupon()" class="text-[#F59E0B] text-sm hover:underline">Apply Coupon</a>
    </div>

    {{-- Skip Followup --}}
    <label class="flex items-center gap-2 cursor-pointer mb-4">
        <input type="checkbox" name="skip_followup" value="1" {{ $order->skip_followup ? 'checked' : '' }}
            class="rounded border-gray-300 text-[#1B2A4A]">
        <span class="text-sm text-gray-600 dark:text-gray-400">Skip/Done Followup</span>
    </label>

    {{-- Buttons --}}
    <button type="submit"
        class="w-full flex items-center justify-center gap-2 bg-[#1B2A4A] text-white py-3 rounded-xl text-sm font-semibold hover:bg-[#243a63] transition mb-2">
        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Update
    </button>
    <p class="text-xs text-gray-400 mb-2">NB: (*) marked are required field.</p>
    <a href="{{ route('admin.invoices.generatePrint', $order) }}" target="_blank"
        class="w-full flex items-center justify-center gap-2 bg-[#0d9488] text-white py-2.5 rounded-xl text-sm font-semibold hover:bg-teal-700 transition">
        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg> Invoice Print
    </a>

    <button type="button" onclick="openCourierModal()"
        class="w-full flex items-center justify-center gap-2 bg-purple-600 text-white py-2.5 rounded-xl text-sm font-semibold hover:bg-purple-700 transition mt-2">
        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg> Submit Courier
    </button>

</div>
</div>

</div>{{-- END MAIN GRID --}}
</form>

{{-- PRODUCT SEARCH MODAL --}}
<div id="productModal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4">
    <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-xl w-full max-w-lg p-6">
        <div class="flex items-center justify-between mb-4">
            <h4 class="font-semibold text-[#1B2A4A] dark:text-white">Add Product</h4>
            <button type="button" onclick="closeProductModal()" class="text-gray-400 hover:text-gray-600 text-xl">×</button>
        </div>
        <input type="text" id="productSearchInput" placeholder="Search product name..."
            class="w-full rounded-xl border border-gray-200 dark:border-gray-700 px-4 py-2 text-sm mb-4 bg-white dark:bg-gray-800 dark:text-white focus:outline-none"
            oninput="searchProducts(this.value)">
        <div id="productSearchResults" class="max-h-64 overflow-y-auto space-y-2">
            <p class="text-center text-gray-400 text-sm py-4">Type to search products...</p>
        </div>
    </div>
</div>

{{-- ADD NOTE MODAL --}}
<div id="noteModal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4">
    <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-xl w-full max-w-md p-6">
        <div class="flex items-center justify-between mb-4">
            <h4 class="font-semibold text-[#1B2A4A] dark:text-white" id="noteModalTitle">Add Note</h4>
            <button type="button" onclick="closeNoteModal()" class="text-gray-400 hover:text-gray-600 text-xl">×</button>
        </div>
        <textarea id="noteTextarea" rows="4" placeholder="Write note here..."
            class="w-full rounded-xl border border-gray-200 dark:border-gray-700 px-4 py-2 text-sm bg-white dark:bg-gray-800 dark:text-white resize-none focus:outline-none focus:ring-2 focus:ring-[#1B2A4A]"></textarea>
        <input type="hidden" id="noteType" value="order">
        <div class="flex gap-3 mt-4">
            <button type="button" onclick="submitNote()"
                class="flex-1 bg-[#1B2A4A] text-white py-2 rounded-xl text-sm font-medium hover:bg-[#243a63] transition">
                Save Note
            </button>
            <button type="button" onclick="closeNoteModal()"
                class="px-4 py-2 border dark:border-gray-600 rounded-xl text-sm dark:text-white hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                Cancel
            </button>
        </div>
    </div>
</div>

{{-- Add Payment Modal --}}
<div id="addPaymentModal" class="hidden fixed inset-0 z-[10000] flex items-center justify-center p-4"
     onclick="if(event.target===this) closeAddPaymentModal()">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>
    <div class="relative w-full max-w-md bg-white dark:bg-gray-900 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        {{-- Header --}}
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-gray-800">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-xl bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                    <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Add Payment</h3>
                    <p class="text-xs text-gray-400 dark:text-gray-500">Order #{{ $order->id }}</p>
                </div>
            </div>
            <button onclick="closeAddPaymentModal()"
                    class="w-8 h-8 flex items-center justify-center rounded-full text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        {{-- Body --}}
        <div class="px-5 py-4 space-y-4">
            {{-- Amount --}}
            <div>
                <label class="text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1.5 block">Amount <span class="text-red-500">*</span></label>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm font-bold text-gray-400">৳</span>
                    <input type="number" id="paymentAmountInput" placeholder="0.00" min="0" step="0.01"
                           class="w-full pl-7 pr-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-amber-400/50 transition">
                </div>
            </div>
            {{-- Payment Method --}}
            <div>
                <label class="text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1.5 block">Payment Method</label>
                <select id="paymentMethodInput"
                        class="w-full px-3 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-amber-400/50 transition">
                    <option value="cod">Cash on Delivery</option>
                    <option value="bkash">bKash</option>
                    <option value="nagad">Nagad</option>
                    <option value="rocket">Rocket</option>
                    <option value="card">Bank Transfer</option>
                </select>
            </div>
            {{-- Note --}}
            <div>
                <label class="text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1.5 block">Note <span class="text-gray-400 font-normal">(optional)</span></label>
                <textarea id="paymentNoteInput" rows="2" placeholder="Payment note..."
                          class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-amber-400/50 resize-none transition"></textarea>
            </div>
        </div>
        {{-- Footer --}}
        <div class="flex items-center justify-end gap-2 px-5 py-3 border-t border-gray-100 dark:border-gray-800">
            <button onclick="closeAddPaymentModal()"
                    class="px-4 py-2 rounded-lg text-sm font-medium text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">
                Cancel
            </button>
            <button onclick="submitAddPayment()"
                    class="px-5 py-2 rounded-lg text-sm font-semibold text-black bg-amber-500 hover:bg-amber-600 transition-colors">
                Confirm Payment
            </button>
        </div>
    </div>
</div>

{{-- Submit Courier Modal --}}
<div id="courierModal" class="hidden fixed inset-0 z-[10000] flex items-center justify-center p-4"
     onclick="if(event.target===this) closeCourierModal()">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>
    <div class="relative w-full max-w-md bg-white dark:bg-gray-900 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        {{-- Header --}}
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-gray-800">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-xl bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
                    <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Submit to Courier</h3>
                    <p class="text-xs text-gray-400">Order #{{ $order->order_number }}</p>
                </div>
            </div>
            <button onclick="closeCourierModal()"
                    class="w-8 h-8 flex items-center justify-center rounded-full text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        {{-- Existing Shipment Info --}}
        @if($order->shipment)
        <div class="px-5 pt-4">
            <div class="flex items-center gap-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl px-4 py-3">
                <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-semibold text-green-700 dark:text-green-400 capitalize">{{ $order->shipment->courier_name }} — {{ ucfirst($order->shipment->status) }}</p>
                    @if($order->shipment->tracking_code)
                    <p class="text-xs text-green-600 dark:text-green-500 truncate">Tracking: {{ $order->shipment->tracking_code }}</p>
                    @endif
                </div>
                <span class="text-xs text-gray-400">Re-submit করুন নিচে</span>
            </div>
        </div>
        @endif
        {{-- Body --}}
        <form id="courierSubmitForm" action="{{ route('admin.orders.submitCourier', $order) }}" method="POST">
        @csrf
        <input type="hidden" name="delivery_fee" value="{{ $order->shipment?->delivery_fee ?? $order->shipping_charge ?? 60 }}">
        <input type="hidden" name="notes" value="{{ $order->shipment?->notes ?? $order->courier_note ?? '' }}">
        <div class="px-5 py-4">
            <label class="text-xs font-semibold text-gray-600 dark:text-gray-400 mb-2 block">Courier নির্বাচন করুন <span class="text-red-500">*</span></label>
            <div class="grid grid-cols-3 gap-2" id="courierBtnGroup">
                @foreach($activeCouriers as $ac)
                @php $isSelected = $order->shipment && $order->shipment->courier_name === $ac->courier; @endphp
                <button type="button"
                    onclick="selectCourier('{{ $ac->courier }}')"
                    data-courier="{{ $ac->courier }}"
                    title="{{ $ac->is_active ? ucfirst($ac->courier) : ucfirst($ac->courier).' (Not configured)' }}"
                    class="courier-btn relative flex flex-col items-center justify-center gap-1 px-2 py-3 rounded-xl border-2 transition-all text-xs font-semibold
                        {{ $isSelected ? 'border-purple-500 bg-purple-50 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300' : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:border-purple-400 hover:text-purple-600' }}
                        {{ !$ac->is_active ? 'opacity-50' : '' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>
                    {{ ucfirst($ac->courier) }}
                    @if(!$ac->is_active)
                    <span class="absolute top-1 right-1 w-1.5 h-1.5 rounded-full bg-red-400"></span>
                    @else
                    <span class="absolute top-1 right-1 w-1.5 h-1.5 rounded-full bg-green-400"></span>
                    @endif
                </button>
                @endforeach
                {{-- Manual always available --}}
                @php $manualSelected = $order->shipment && $order->shipment->courier_name === 'manual'; @endphp
                <button type="button"
                    onclick="selectCourier('manual')"
                    data-courier="manual"
                    class="courier-btn flex flex-col items-center justify-center gap-1 px-2 py-3 rounded-xl border-2 transition-all text-xs font-semibold
                        {{ $manualSelected ? 'border-purple-500 bg-purple-50 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300' : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:border-purple-400 hover:text-purple-600' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    Manual
                </button>
            </div>
            <input type="hidden" name="courier" id="selectedCourierInput" value="{{ $order->shipment?->courier_name ?? '' }}">
        </div>
        {{-- Footer --}}
        <div class="flex items-center justify-end gap-2 px-5 py-3 border-t border-gray-100 dark:border-gray-800">
            <button type="button" onclick="closeCourierModal()"
                    class="px-4 py-2 rounded-lg text-sm font-medium text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">
                Cancel
            </button>
            <button type="submit" id="courierSubmitBtn"
                    class="px-5 py-2 rounded-lg text-sm font-semibold text-white bg-purple-600 hover:bg-purple-700 transition-colors flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                Submit Courier
            </button>
        </div>
        </form>
    </div>
</div>

{{-- Tracking History Modal --}}
<div id="trackingModal" class="hidden fixed inset-0 z-[10001] flex items-center justify-center p-4"
     onclick="if(event.target===this) closeTrackingModal()">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>
    <div class="relative w-full max-w-2xl bg-white dark:bg-gray-900 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700 overflow-hidden max-h-[90vh] flex flex-col">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-gray-800 flex-shrink-0">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-xl bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
                    <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Tracking History</h3>
                    <p class="text-xs text-gray-400" id="trackingModalSubtitle">Loading…</p>
                </div>
            </div>
            <button onclick="closeTrackingModal()"
                    class="w-8 h-8 flex items-center justify-center rounded-full text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div id="trackingModalBody" class="overflow-y-auto flex-1 p-5">
            <div id="trackingLoader" class="flex items-center justify-center py-12">
                <svg class="w-6 h-6 animate-spin text-purple-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                <span class="ml-2 text-sm text-gray-500">Fetching tracking data…</span>
            </div>
            <div id="trackingError" class="hidden text-center py-10 text-red-500 text-sm"></div>
            <div id="trackingContent" class="hidden">
                <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
                    <table class="w-full text-xs">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-3 py-2.5 text-left font-semibold text-gray-600 dark:text-gray-400 w-10">SL</th>
                                <th class="px-3 py-2.5 text-left font-semibold text-gray-600 dark:text-gray-400 w-36">Date</th>
                                <th class="px-3 py-2.5 text-left font-semibold text-gray-600 dark:text-gray-400">Note</th>
                                <th class="px-3 py-2.5 text-left font-semibold text-gray-600 dark:text-gray-400 w-32">Delivery Man</th>
                            </tr>
                        </thead>
                        <tbody id="trackingTableBody" class="divide-y divide-gray-100 dark:divide-gray-800"></tbody>
                    </table>
                </div>
                <div id="trackingDeliveryMan" class="hidden mt-3 px-4 py-3 bg-blue-50 dark:bg-blue-900/20 rounded-xl text-xs text-blue-700 dark:text-blue-300"></div>
            </div>
        </div>
    </div>
</div>

{{-- Edit Tracking Modal --}}
<div id="editTrackingModal" class="hidden fixed inset-0 z-[10001] flex items-center justify-center p-4"
     onclick="if(event.target===this) closeEditTrackingModal()">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>
    <div class="relative w-full max-w-sm bg-white dark:bg-gray-900 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-gray-800">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-xl bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                    <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Edit Tracking Info</h3>
                    <p class="text-xs text-gray-400">Order #{{ $order->order_number }}</p>
                </div>
            </div>
            <button onclick="closeEditTrackingModal()"
                    class="w-8 h-8 flex items-center justify-center rounded-full text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="px-5 py-4 space-y-3">
            <div>
                <label class="text-xs font-semibold text-gray-600 dark:text-gray-400 block mb-1">Courier Inv (Tracking Code)</label>
                <input type="text" id="editTrackingCode"
                    class="w-full text-sm px-3 py-2 border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-amber-400"
                    placeholder="Tracking code">
            </div>
            <div>
                <label class="text-xs font-semibold text-gray-600 dark:text-gray-400 block mb-1">Courier Inv 2 (Consignment ID)</label>
                <input type="text" id="editConsignmentId"
                    class="w-full text-sm px-3 py-2 border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-amber-400"
                    placeholder="Consignment ID (optional)">
            </div>
        </div>
        <div class="px-5 pb-5 flex gap-2 justify-end">
            <button onclick="closeEditTrackingModal()"
                    class="px-4 py-2 text-sm font-medium text-gray-600 dark:text-gray-400 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-lg transition-colors">
                Cancel
            </button>
            <button id="saveTrackingBtn" onclick="saveTracking()"
                    class="px-4 py-2 text-sm font-medium text-white bg-amber-500 hover:bg-amber-600 rounded-lg transition-colors flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                Save
            </button>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
const ORDER_ID = {{ $order->id }};
const NOTES_URL      = '/admin/orders/' + ORDER_ID + '/notes';
const CUST_NOTES_URL = '/admin/orders/' + ORDER_ID + '/customer-notes';
const SEARCH_URL     = '/admin/orders/' + ORDER_ID + '/products-search';
const CSRF = document.querySelector('meta[name="csrf-token"]').content;

// ─── Alpine.js Calculator ─────────────────────────────────────────────────────
function orderCalc() {
    return {
        subtotal:      {{ (float)($order->subtotal ?? 0) }},
        discountType:  '{{ $order->discount_type ?? "fixed" }}',
        discountValue: {{ ($order->discount ?? 0) > 0 ? (float)$order->discount : 'null' }},
        shipping:      {{ ($order->shipping_charge ?? 0) > 0 ? (float)$order->shipping_charge : 'null' }},
        paid:          {{ ($order->paid_amount ?? 0) > 0 ? (float)$order->paid_amount : 'null' }},
        courierCharge: {{ ($order->courier_charge ?? 0) > 0 ? (float)$order->courier_charge : 'null' }},

        get discount() {
            return this.discountType === 'percent'
                ? (this.subtotal * this.discountValue / 100)
                : (parseFloat(this.discountValue) || 0);
        },
        get grandTotal() {
            return Math.max(0, this.subtotal - this.discount + (parseFloat(this.shipping) || 0));
        },
        get due() {
            return Math.max(0, this.grandTotal - (parseFloat(this.paid) || 0));
        },
        init() {
            window.updateSubtotal = (val) => { this.subtotal = val; };
        }
    };
}

// ─── Product Row Calculations ─────────────────────────────────────────────────
function recalcRow(el) {
    const row = el.closest('tr');
    const price    = parseFloat(row.querySelector('.item-price').value) || 0;
    const qty      = parseInt(row.querySelector('.item-qty').value) || 1;
    const discount = parseFloat(row.querySelector('.item-discount').value) || 0;
    const sub      = (price * qty) - discount;
    row.querySelector('.item-subtotal').textContent = '৳' + sub.toFixed(2);
    recalcAllSubtotals();
}

function recalcAllSubtotals() {
    let total = 0;
    document.querySelectorAll('#productRows .product-row').forEach(row => {
        const text = row.querySelector('.item-subtotal').textContent.replace('৳','').replace(/,/g,'');
        total += parseFloat(text) || 0;
    });
    if (window.updateSubtotal) window.updateSubtotal(total);
}

function removeProductRow(btn) {
    btn.closest('tr').remove();
    recalcAllSubtotals();
}

// ─── Product Search Modal ─────────────────────────────────────────────────────
function openProductModal() {
    const m = document.getElementById('productModal');
    m.classList.remove('hidden'); m.classList.add('flex');
    document.getElementById('productSearchInput').focus();
    searchProducts('');
}
function closeProductModal() {
    const m = document.getElementById('productModal');
    m.classList.add('hidden'); m.classList.remove('flex');
}

let searchTimeout;
function searchProducts(q) {
    clearTimeout(searchTimeout);
    if (q.length < 1) {
        document.getElementById('productSearchResults').innerHTML = '<p class="text-center text-gray-400 text-sm py-4">Type to search products...</p>';
        if (q === '') {
            searchTimeout = setTimeout(() => loadProducts(''), 0);
        }
        return;
    }
    searchTimeout = setTimeout(() => loadProducts(q), 300);
}
function loadProducts(q) {
    fetch(SEARCH_URL + '?q=' + encodeURIComponent(q), {
        headers: {'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json'}
    })
    .then(r => r.json())
    .then(products => {
        const c = document.getElementById('productSearchResults');
        if (!products.length) {
            c.innerHTML = '<p class="text-center text-gray-400 text-sm py-4">No products found.</p>';
            return;
        }
        c.innerHTML = products.map(p => `
            <div class="flex items-center gap-3 p-3 border dark:border-gray-700 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer" onclick='addProductRow(${JSON.stringify(p)})'>
                <img src="${p.image_url}" class="w-10 h-10 object-cover rounded-lg" onerror="this.src='/images/no-image.png'">
                <div class="flex-1">
                    <div class="text-sm font-medium dark:text-white">${p.name}</div>
                    <div class="text-xs text-gray-400">Stock: ${p.stock} | ${p.variants.length} variants</div>
                </div>
            </div>
        `).join('');
    })
    .catch(err => {
        document.getElementById('productSearchResults').innerHTML = '<p class="text-center text-red-400 text-sm py-4">Error loading products.</p>';
        console.error(err);
    });
}

let rowIndex = {{ $order->items->count() }};
function addProductRow(product) {
    const variant = product.variants[0] || {name: 'Default', price: 0};
    const row = document.createElement('tr');
    row.className = 'product-row border-b dark:border-gray-800';
    row.setAttribute('data-row', rowIndex);
    row.innerHTML = `
        <td class="px-3 py-3">
            <div class="flex items-center gap-3">
                <img src="${product.image_url}" class="w-12 h-12 object-cover rounded-lg border dark:border-gray-700">
                <div>
                    <div class="font-medium text-gray-800 dark:text-gray-200 text-xs leading-tight">${product.name}</div>
                    <select name="items[${rowIndex}][variant]" class="mt-1 text-xs border border-gray-200 dark:border-gray-700 rounded-lg px-2 py-1 bg-white dark:bg-gray-800 dark:text-white">
                        ${product.variants.map(v => `<option value="${v.name}">${v.name}</option>`).join('')}
                    </select>
                    <input type="hidden" name="items[${rowIndex}][product_id]" value="${product.id}">
                    <input type="hidden" name="items[${rowIndex}][id]" value="">
                </div>
            </div>
        </td>
        <td class="px-3 py-3 text-center">
            <input type="number" name="items[${rowIndex}][unit_price]" value="${variant.price || 0}"
                class="w-24 text-center rounded-lg border border-gray-200 dark:border-gray-700 px-2 py-1 text-sm bg-white dark:bg-gray-800 dark:text-white item-price"
                step="0.01" onchange="recalcRow(this)">
        </td>
        <td class="px-3 py-3 text-center">
            <input type="number" name="items[${rowIndex}][quantity]" value="1"
                class="w-16 text-center rounded-lg border border-gray-200 dark:border-gray-700 px-2 py-1 text-sm bg-white dark:bg-gray-800 dark:text-white item-qty"
                min="1" onchange="recalcRow(this)">
        </td>
        <td class="px-3 py-3 text-center">
            <span class="${product.stock < 0 ? 'bg-red-100 text-red-600' : 'bg-green-100 text-green-600'} px-2 py-1 rounded-lg text-xs font-bold">${product.stock}</span>
        </td>
        <td class="px-3 py-3 text-center">
            <input type="number" name="items[${rowIndex}][discount]" value="0"
                class="w-20 text-center rounded-lg border border-gray-200 dark:border-gray-700 px-2 py-1 text-sm bg-white dark:bg-gray-800 dark:text-white item-discount"
                min="0" onchange="recalcRow(this)">
        </td>
        <td class="px-3 py-3 text-center">
            <span class="item-subtotal font-bold text-[#1B2A4A] dark:text-white text-sm">৳${parseFloat(variant.price || 0).toFixed(2)}</span>
        </td>
        <td class="px-3 py-3 text-center">
            <button type="button" onclick="removeProductRow(this)"
                class="w-8 h-8 flex items-center justify-center bg-red-100 dark:bg-red-900/30 text-red-500 rounded-lg hover:bg-red-200 mx-auto"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </td>
    `;
    document.getElementById('productRows').appendChild(row);
    rowIndex++;
    recalcAllSubtotals();
    closeProductModal();
}

// ─── Notes ────────────────────────────────────────────────────────────────────
let currentNoteType = 'order';
function showAddNoteModal(type) {
    currentNoteType = type;
    document.getElementById('noteType').value = type;
    document.getElementById('noteModalTitle').textContent = type === 'order' ? 'Add Order Note' : 'Add Customer Note';
    document.getElementById('noteTextarea').value = '';
    const m = document.getElementById('noteModal');
    m.classList.remove('hidden'); m.classList.add('flex');
}
function closeNoteModal() {
    const m = document.getElementById('noteModal');
    m.classList.add('hidden'); m.classList.remove('flex');
}

function submitNote() {
    const text = document.getElementById('noteTextarea').value.trim();
    const type = document.getElementById('noteType').value;
    if (!text) return alert('Note cannot be empty');

    const url = type === 'customer' ? CUST_NOTES_URL : NOTES_URL;
    fetch(url, {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json'},
        body: JSON.stringify({note_text: text})
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) return showToast(data.message || 'Error saving note', 'error');
        const container = type === 'customer'
            ? document.getElementById('customerNotesBody')
            : document.getElementById('orderNotesBody');
        const noRow = document.getElementById('noOrderNotes');
        if (noRow) noRow.remove();
        const isCustomer = type === 'customer';
        const bgClass      = isCustomer ? 'bg-amber-50 dark:bg-amber-900/10 border-amber-100 dark:border-amber-800/30' : 'bg-blue-50 dark:bg-blue-900/20 border-blue-100 dark:border-blue-800/30';
        const avatarClass  = isCustomer ? 'bg-amber-500' : 'bg-[#1B2A4A]';
        const initial      = (data.note.user_name || 'S').charAt(0).toUpperCase();
        const card = document.createElement('div');
        card.id        = 'note-row-' + data.note.id;
        card.className = `group relative ${bgClass} border rounded-xl px-3.5 py-3`;
        card.innerHTML = `
            <p class="text-sm text-gray-800 dark:text-gray-200 leading-relaxed pr-7">${data.note.note_text}</p>
            <div class="flex items-center gap-1.5 mt-2">
                <div class="w-4 h-4 rounded-full ${avatarClass} flex items-center justify-center text-white text-[9px] font-bold flex-shrink-0">${initial}</div>
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400">${data.note.user_name}</span>
                <span class="text-gray-300 dark:text-gray-600 text-xs">·</span>
                <span class="text-xs text-gray-400 dark:text-gray-500">${data.note.created_at}</span>
            </div>
            <button type="button" onclick="deleteNote(${data.note.id})"
                class="absolute top-2.5 right-2.5 w-5 h-5 flex items-center justify-center bg-red-100 dark:bg-red-900/40 text-red-400 rounded-md hover:bg-red-200 opacity-0 group-hover:opacity-100 transition-opacity">
                <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>`;
        container.prepend(card);
        closeNoteModal();
        showToast('Note added successfully');
    });
}

function deleteNote(noteId) {
    if (!confirm('Delete this note?')) return;
    fetch(NOTES_URL + '/' + noteId, {
        method: 'DELETE',
        headers: {'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json'}
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) document.getElementById('note-row-' + noteId)?.remove();
    });
}

// ─── Copy / Block ─────────────────────────────────────────────────────────────
function copyText(text) {
    if (!text) return;
    navigator.clipboard.writeText(text).then(() => showToast('Copied!'));
}

function copyOrderInfo() {
    const name     = document.querySelector('[name="full_name"]')?.value || '';
    const phone    = document.querySelector('[name="primary_mobile"]')?.value || '';
    const address  = document.querySelector('[name="address"]')?.value || '';
    const district = document.querySelector('[name="district"]')?.value || '';
    const status   = document.querySelector('[name="order_status"]')?.value || '';
    const payment  = document.querySelector('[name="payment_method"]')?.value || '';

    let products = '';
    document.querySelectorAll('#productRows .product-row').forEach(row => {
        const pName = row.querySelector('.font-medium')?.textContent?.trim() || '';
        const qty   = row.querySelector('.item-qty')?.value || '';
        const sub   = row.querySelector('.item-subtotal')?.textContent?.trim() || '';
        products   += `\n  - ${pName} x${qty} = ${sub}`;
    });

    const grandTotal = document.querySelector('[name="grand_total"]')?.value || '0';
    const paid       = document.querySelector('[name="paid_amount"]')?.value || '0';
    const due        = document.querySelector('[name="due"]')?.value || '0';
    const invoiceId  = '{{ str_pad($order->id, 5, "0", STR_PAD_LEFT) }}';

    const text = `📦 Invoice: #${invoiceId}\n👤 Name: ${name}\n📱 Phone: ${phone}\n📍 Address: ${address}, ${district}\n🛍 Products:${products}\n💰 Grand Total: ৳${parseFloat(grandTotal).toFixed(2)}\n✅ Paid: ৳${parseFloat(paid).toFixed(2)}\n❌ Due: ৳${parseFloat(due).toFixed(2)}\n💳 Payment: ${payment}\n📊 Status: ${status}`;

    navigator.clipboard.writeText(text).then(() => showToast('Order info copied!'));
}

function blockPhone(phone) {
    if (!phone || !confirm('Block this phone number: ' + phone + '?')) return;
    fetch('/admin/blacklist', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF},
        body: JSON.stringify({phone: phone, reason: 'Blocked from order page'})
    }).then(r => r.json()).then(d => showToast(d.message || 'Phone blocked'));
}

function blockIP(ip) {
    if (!confirm('Block IP: ' + ip + '?')) return;
    showToast('IP block - implement as needed');
}

function applyCoupon() {
    const code = document.querySelector('[name="coupon_code"]')?.value;
    if (!code) return;
    fetch('/admin/coupons/validate', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF},
        body: JSON.stringify({code: code, order_id: ORDER_ID})
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) showToast('Coupon applied: ' + d.discount);
        else showToast('Invalid coupon code', 'error');
    }).catch(() => showToast('Coupon validation failed', 'error'));
}

function openAddPaymentModal() {
    const currentAmount = document.querySelector('[name="paid_amount"]')?.value || '';
    const currentMethod = document.querySelector('[name="payment_method"]')?.value || 'cod';
    document.getElementById('paymentAmountInput').value = currentAmount ? parseFloat(currentAmount).toFixed(2) : '';
    document.getElementById('paymentMethodInput').value = currentMethod;
    document.getElementById('paymentNoteInput').value = '';
    document.getElementById('addPaymentModal').classList.remove('hidden');
    setTimeout(() => document.getElementById('paymentAmountInput').focus(), 50);
}

function closeAddPaymentModal() {
    document.getElementById('addPaymentModal').classList.add('hidden');
}

function submitAddPayment() {
    const amount = document.getElementById('paymentAmountInput').value;
    const method = document.getElementById('paymentMethodInput').value;
    if (!amount || isNaN(amount) || parseFloat(amount) < 0) {
        document.getElementById('paymentAmountInput').focus();
        return;
    }
    // Update Alpine.js reactive data so due recalculates instantly
    const calcEl = document.querySelector('[x-data="orderCalc()"]');
    if (calcEl && calcEl._x_dataStack) {
        calcEl._x_dataStack[0].paid = parseFloat(amount);
    }
    document.querySelector('[name="payment_method"]').value = method;
    closeAddPaymentModal();
    showToast('Payment updated — save the order to confirm.', 'success');
}

function refreshCourierStats(e) {
    e.stopPropagation();
    showToast('Refreshing...');
    location.reload();
}

function togglePackingItems() {
    showToast('Packing items view - coming soon');
}

function reOrderNow() {
    if (!confirm('Same products & customer diye নতুন order create করবেন?')) return;
    fetch('/admin/orders/' + ORDER_ID + '/reorder', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json'}
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            showToast('Re-order created! Redirecting...');
            setTimeout(() => window.location.href = d.redirect_url, 1500);
        } else {
            showToast(d.message || 'Failed', 'error');
        }
    });
}

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') { closeAddPaymentModal(); closeCourierModal(); closeTrackingModal(); closeEditTrackingModal(); }
});

// ─── Submit Courier Modal ──────────────────────────────────────────────────────
function openCourierModal() {
    document.getElementById('courierModal').classList.remove('hidden');
}
function closeCourierModal() {
    document.getElementById('courierModal').classList.add('hidden');
}
function selectCourier(name) {
    document.getElementById('selectedCourierInput').value = name;
    document.querySelectorAll('.courier-btn').forEach(btn => {
        const active = btn.dataset.courier === name;
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
document.getElementById('courierSubmitForm')?.addEventListener('submit', function(e) {
    const courier = document.getElementById('selectedCourierInput').value;
    if (!courier) {
        e.preventDefault();
        showToast('Courier নির্বাচন করুন', 'error');
        return;
    }
    const btn = document.getElementById('courierSubmitBtn');
    btn.disabled = true;
    btn.innerHTML = '<svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg> Submitting...';
});

// ─── Courier Tracking History ─────────────────────────────────────────────────
const TRACK_URL       = '/admin/orders/{{ $order->id }}/track-courier';
const UPDATE_TRACK_URL= '/admin/orders/{{ $order->id }}/update-tracking';

function fetchCourierTracking() {
    const modal = document.getElementById('trackingModal');
    const loader  = document.getElementById('trackingLoader');
    const errEl   = document.getElementById('trackingError');
    const content = document.getElementById('trackingContent');
    const subtitle= document.getElementById('trackingModalSubtitle');

    modal.classList.remove('hidden');
    loader.classList.remove('hidden');
    errEl.classList.add('hidden');
    content.classList.add('hidden');
    subtitle.textContent = 'Loading…';

    fetch(TRACK_URL, { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF } })
        .then(r => r.json())
        .then(data => {
            loader.classList.add('hidden');
            if (data.error) {
                errEl.classList.remove('hidden');
                errEl.textContent = data.error;
                return;
            }
            subtitle.textContent = (data.courier || '') + (data.tracking_code ? ' — ' + data.tracking_code : '');
            const tbody = document.getElementById('trackingTableBody');
            tbody.innerHTML = '';
            const events = data.events || [];
            if (!events.length) {
                tbody.innerHTML = '<tr><td colspan="4" class="text-center py-6 text-gray-400">No tracking events found.</td></tr>';
            } else {
                events.forEach((ev, i) => {
                    const tr = document.createElement('tr');
                    tr.className = i % 2 === 0 ? 'bg-white dark:bg-gray-900' : 'bg-gray-50/50 dark:bg-gray-800/30';
                    tr.innerHTML = `
                        <td class="px-3 py-2.5 text-gray-500 dark:text-gray-400">${i + 1}</td>
                        <td class="px-3 py-2.5 text-gray-700 dark:text-gray-300 whitespace-nowrap">${ev.date || '—'}</td>
                        <td class="px-3 py-2.5 text-gray-700 dark:text-gray-300">${ev.note || '—'}</td>
                        <td class="px-3 py-2.5 text-gray-700 dark:text-gray-300">${ev.delivery_man || '—'}</td>
                    `;
                    tbody.appendChild(tr);
                });
            }
            // Show delivery man info if present on last event
            const last = events.length ? events[0] : null;
            const dmEl = document.getElementById('trackingDeliveryMan');
            if (last && (last.delivery_man_name || last.delivery_man_phone)) {
                dmEl.classList.remove('hidden');
                dmEl.innerHTML = `<span class="font-semibold">Delivery Man:</span> ${last.delivery_man_name || '—'} ${last.delivery_man_phone ? '&nbsp;|&nbsp; ' + last.delivery_man_phone : ''}`;
            } else {
                dmEl.classList.add('hidden');
            }
            content.classList.remove('hidden');
        })
        .catch(err => {
            loader.classList.add('hidden');
            errEl.classList.remove('hidden');
            errEl.textContent = 'Failed to fetch tracking data.';
        });
}
function closeTrackingModal() {
    document.getElementById('trackingModal').classList.add('hidden');
}

// ─── Edit Tracking ─────────────────────────────────────────────────────────────
function openEditTracking() {
    document.getElementById('editTrackingCode').value = '{{ $order->shipment?->tracking_code ?? '' }}';
    document.getElementById('editConsignmentId').value = '{{ $order->shipment?->consignment_id ?? '' }}';
    document.getElementById('editTrackingModal').classList.remove('hidden');
}
function closeEditTrackingModal() {
    document.getElementById('editTrackingModal').classList.add('hidden');
}
function saveTracking() {
    const btn = document.getElementById('saveTrackingBtn');
    const trackingCode   = document.getElementById('editTrackingCode').value.trim();
    const consignmentId  = document.getElementById('editConsignmentId').value.trim();
    if (!trackingCode) { showToast('Tracking code প্রয়োজন', 'error'); return; }

    btn.disabled = true;
    btn.innerHTML = '<svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg> Saving…';

    fetch(UPDATE_TRACK_URL, {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        body: JSON.stringify({ tracking_code: trackingCode, consignment_id: consignmentId || null }),
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Save';
        if (data.error) { showToast(data.error, 'error'); return; }
        document.getElementById('courierInvDisplay').textContent = trackingCode;
        closeEditTrackingModal();
        showToast('Tracking code আপডেট হয়েছে');
    })
    .catch(() => {
        btn.disabled = false;
        btn.innerHTML = '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Save';
        showToast('Update failed', 'error');
    });
}

// ─── Toast ────────────────────────────────────────────────────────────────────
function showToast(msg, type = 'success') {
    const t = document.createElement('div');
    t.className = `fixed top-4 right-4 z-[9999] px-4 py-3 rounded-xl text-white text-sm font-medium shadow-lg ${type === 'error' ? 'bg-red-500' : 'bg-green-500'}`;
    t.textContent = msg;
    document.body.appendChild(t);
    setTimeout(() => t.remove(), 3000);
}
</script>
@endpush
