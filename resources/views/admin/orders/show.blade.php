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
        <span class="w-8 h-8 bg-[#1B2A4A]/10 rounded-lg flex items-center justify-center text-[#1B2A4A] dark:bg-[#1B2A4A]/30 dark:text-white text-xs">📦</span>
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
            <div class="flex items-center gap-1">
                <input type="text" name="primary_mobile" value="{{ old('primary_mobile', $order->shipping_phone) }}"
                    id="mobileInput" required maxlength="11"
                    class="flex-1 rounded-xl border border-gray-200 dark:border-gray-700 px-3 py-2 text-sm bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#1B2A4A]">
                <a href="https://wa.me/880{{ ltrim($order->shipping_phone, '0') }}" target="_blank"
                    class="w-8 h-8 flex items-center justify-center bg-green-500 text-white rounded-lg text-xs hover:bg-green-600" title="WhatsApp">💬</a>
                <a href="tel:{{ $order->shipping_phone }}"
                    class="w-8 h-8 flex items-center justify-center bg-blue-500 text-white rounded-lg text-xs hover:bg-blue-600" title="Call">📞</a>
                <button type="button" onclick="copyText('{{ $order->shipping_phone }}')"
                    class="w-8 h-8 flex items-center justify-center bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 rounded-lg text-xs hover:bg-gray-200" title="Copy">📋</button>
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
                    class="w-8 h-8 flex items-center justify-center bg-gray-100 dark:bg-gray-700 rounded-lg text-xs">📋</button>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
        {{-- Address --}}
        <div>
            <label class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1 block">Address</label>
            <div class="flex items-start gap-1">
                <textarea name="address" rows="2"
                    class="flex-1 rounded-xl border border-gray-200 dark:border-gray-700 px-3 py-2 text-sm bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#1B2A4A] resize-none">{{ old('address', $order->shipping_address) }}</textarea>
                <button type="button" onclick="copyText('{{ addslashes($order->shipping_address ?? '') }}')"
                    class="w-8 h-8 flex items-center justify-center bg-gray-100 dark:bg-gray-700 rounded-lg text-xs mt-1">📋</button>
            </div>
        </div>

        <div class="space-y-3">
            {{-- District --}}
            <div>
                <label class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1 block">District</label>
                <select name="district"
                    class="w-full rounded-xl border border-gray-200 dark:border-gray-700 px-3 py-2 text-sm bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#1B2A4A]">
                    <option value="">Select District</option>
                    @foreach($districts as $d)
                    <option value="{{ $d }}" {{ ($order->shipping_district ?? '') === $d ? 'selected' : '' }}>{{ $d }}</option>
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
            <select name="order_tag" class="rounded-lg border border-gray-200 dark:border-gray-700 px-2 py-1 text-xs bg-white dark:bg-gray-800 dark:text-white">
                <option value="">Order Tag</option>
                <option value="vip" {{ $order->order_tag == 'vip' ? 'selected' : '' }}>VIP</option>
                <option value="regular" {{ $order->order_tag == 'regular' ? 'selected' : '' }}>Regular</option>
                <option value="problematic" {{ $order->order_tag == 'problematic' ? 'selected' : '' }}>Problematic</option>
            </select>
            <select name="customer_tag" class="rounded-lg border border-gray-200 dark:border-gray-700 px-2 py-1 text-xs bg-white dark:bg-gray-800 dark:text-white">
                <option value="">Customer Tag</option>
                <option value="loyal" {{ $order->customer_tag == 'loyal' ? 'selected' : '' }}>Loyal</option>
                <option value="new" {{ $order->customer_tag == 'new' ? 'selected' : '' }}>New</option>
                <option value="fraud" {{ $order->customer_tag == 'fraud' ? 'selected' : '' }}>Fraud Risk</option>
            </select>
        </div>
    </div>
</div>

{{-- CARD: COURIER SUCCESS RATE --}}
<div class="bg-white dark:bg-gray-900 rounded-2xl shadow p-6" x-data="{ open: true }">
    <div class="flex items-center justify-between cursor-pointer" @click="open = !open">
        <h3 class="text-base font-semibold text-[#1B2A4A] dark:text-white">Courier Success Rate</h3>
        <div class="flex items-center gap-2">
            <button type="button" onclick="refreshCourierStats(event)" class="text-xs text-[#F59E0B] hover:underline">↻ Refresh</button>
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
        <h3 class="text-base font-semibold text-[#1B2A4A] dark:text-white flex items-center gap-2">🛒 Order Products</h3>
        <div class="flex gap-2">
            <button type="button" onclick="openProductModal()"
                class="bg-[#F59E0B] text-white px-4 py-2 rounded-xl text-xs font-medium hover:bg-yellow-500 transition">
                + Add More Item
            </button>
            <button type="button" onclick="togglePackingItems()"
                class="border border-gray-300 dark:border-gray-600 px-4 py-2 rounded-xl text-xs dark:text-white hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                📦 Show Packing Items
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
                            <img src="{{ asset('storage/'.$item->product->images->first()->image_path) }}"
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
                            class="w-8 h-8 flex items-center justify-center bg-red-100 dark:bg-red-900/30 text-red-500 rounded-lg hover:bg-red-200 mx-auto">✕</button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Order Logistics --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-6 pt-4 border-t dark:border-gray-700">
        <div>
            <label class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1 block">Order Status*</label>
            <select name="order_status" required
                class="w-full rounded-xl border border-gray-200 dark:border-gray-700 px-3 py-2 text-sm bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#1B2A4A]">
                @foreach(['pending'=>'Pending','confirmed'=>'Confirmed','processing'=>'Processing','shipped'=>'Shipped','delivered'=>'Delivered','cancelled'=>'Cancelled','hold'=>'Hold','failed'=>'Failed','returned'=>'Returned','damage'=>'Damage','hand_delivery'=>'Hand Delivery','completed'=>'Completed'] as $s => $l)
                <option value="{{ $s }}" {{ $order->status === $s ? 'selected' : '' }}>{{ $l }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1 block">Payment Method</label>
            <select name="payment_method"
                class="w-full rounded-xl border border-gray-200 dark:border-gray-700 px-3 py-2 text-sm bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#1B2A4A]">
                @foreach(['cod'=>'Cash on Delivery','bkash'=>'bKash','nagad'=>'Nagad','rocket'=>'Rocket','card'=>'Bank Transfer'] as $v=>$l)
                <option value="{{ $v }}" {{ $order->payment_method === $v ? 'selected' : '' }}>{{ $l }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1 block">Courier Note</label>
            <textarea name="courier_note" rows="2"
                class="w-full rounded-xl border border-gray-200 dark:border-gray-700 px-3 py-2 text-sm bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#1B2A4A] resize-none">{{ old('courier_note', $order->courier_note) }}</textarea>
        </div>
        <div>
            <label class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1 block">Packing Note</label>
            <textarea name="packing_note" rows="2"
                class="w-full rounded-xl border border-gray-200 dark:border-gray-700 px-3 py-2 text-sm bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#1B2A4A] resize-none">{{ old('packing_note', $order->packing_note) }}</textarea>
        </div>
        <div class="sm:col-span-2">
            <label class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1 block">Attachments</label>
            <input type="file" name="attachments[]" multiple accept="image/*,.pdf"
                class="w-full rounded-xl border border-gray-200 dark:border-gray-700 px-3 py-2 text-sm bg-white dark:bg-gray-800 dark:text-white">
        </div>
    </div>
</div>

{{-- CARD: ORDER NOTES --}}
<div class="bg-white dark:bg-gray-900 rounded-2xl shadow p-6" x-data="{ open: false }">
    <div class="flex items-center justify-between cursor-pointer" @click="open = !open">
        <h3 class="text-base font-semibold text-[#1B2A4A] dark:text-white">
            Order Notes <span x-text="open ? '▲' : '▼'" class="text-xs text-gray-400 ml-1"></span>
        </h3>
        <button type="button" onclick="event.stopPropagation(); showAddNoteModal('order')"
            class="bg-[#1B2A4A] text-white px-3 py-1.5 rounded-xl text-xs hover:bg-[#243a63] transition">
            + Add Note
        </button>
    </div>
    <div x-show="open" x-transition class="mt-4">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-xs text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-800">
                        <th class="text-left px-3 py-2 rounded-l-xl">SL</th>
                        <th class="text-left px-3 py-2">Note</th>
                        <th class="text-left px-3 py-2">Added By</th>
                        <th class="text-left px-3 py-2">Created At</th>
                        <th class="text-center px-3 py-2 rounded-r-xl">Action</th>
                    </tr>
                </thead>
                <tbody id="orderNotesBody">
                    @forelse($order->orderNotes->filter(fn($n) => $n->type === 'order') as $i => $note)
                    <tr class="border-b dark:border-gray-800" id="note-row-{{ $note->id }}">
                        <td class="px-3 py-2 text-gray-500">{{ $i + 1 }}</td>
                        <td class="px-3 py-2 text-gray-700 dark:text-gray-300">{{ $note->note_text }}</td>
                        <td class="px-3 py-2 text-gray-500">{{ $note->user->name ?? 'System' }}</td>
                        <td class="px-3 py-2 text-gray-400 text-xs">{{ $note->created_at->format('d M Y H:i') }}</td>
                        <td class="px-3 py-2 text-center">
                            <button type="button" onclick="deleteNote({{ $note->id }})"
                                class="w-7 h-7 flex items-center justify-center bg-red-100 dark:bg-red-900/30 text-red-500 rounded-lg hover:bg-red-200 mx-auto text-xs">✕</button>
                        </td>
                    </tr>
                    @empty
                    <tr id="noOrderNotes"><td colspan="5" class="text-center py-4 text-gray-400 text-sm">No notes yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- CARD: CUSTOMER NOTES --}}
<div class="bg-white dark:bg-gray-900 rounded-2xl shadow p-6" x-data="{ open: false }">
    <div class="flex items-center justify-between cursor-pointer" @click="open = !open">
        <h3 class="text-base font-semibold text-[#1B2A4A] dark:text-white">
            Customer Notes/Feedback <span x-text="open ? '▲' : '▼'" class="text-xs text-gray-400 ml-1"></span>
        </h3>
        <button type="button" onclick="event.stopPropagation(); showAddNoteModal('customer')"
            class="bg-[#1B2A4A] text-white px-3 py-1.5 rounded-xl text-xs hover:bg-[#243a63] transition">
            + Add Note
        </button>
    </div>
    <div x-show="open" x-transition class="mt-4">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-xs text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-800">
                        <th class="text-left px-3 py-2 rounded-l-xl">SL</th>
                        <th class="text-left px-3 py-2">Note</th>
                        <th class="text-left px-3 py-2">Added By</th>
                        <th class="text-left px-3 py-2">Created At</th>
                        <th class="text-center px-3 py-2 rounded-r-xl">Action</th>
                    </tr>
                </thead>
                <tbody id="customerNotesBody">
                    @forelse($order->orderNotes->filter(fn($n) => $n->type === 'customer') as $i => $note)
                    <tr class="border-b dark:border-gray-800" id="note-row-{{ $note->id }}">
                        <td class="px-3 py-2 text-gray-500">{{ $i + 1 }}</td>
                        <td class="px-3 py-2 text-gray-700 dark:text-gray-300">{{ $note->note_text }}</td>
                        <td class="px-3 py-2 text-gray-500">{{ $note->user->name ?? 'System' }}</td>
                        <td class="px-3 py-2 text-gray-400 text-xs">{{ $note->created_at->format('d M Y H:i') }}</td>
                        <td class="px-3 py-2 text-center">
                            <button type="button" onclick="deleteNote({{ $note->id }})"
                                class="w-7 h-7 flex items-center justify-center bg-red-100 dark:bg-red-900/30 text-red-500 rounded-lg hover:bg-red-200 mx-auto text-xs">✕</button>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center py-4 text-gray-400 text-sm">No customer notes yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- CARD: ACTIVITY LOG --}}
<div class="bg-white dark:bg-gray-900 rounded-2xl shadow p-6" x-data="{ open: false }">
    <div class="flex items-center justify-between cursor-pointer" @click="open = !open">
        <h3 class="text-base font-semibold text-[#1B2A4A] dark:text-white">
            Employee Activity Log <span x-text="open ? '▲' : '▼'" class="text-xs text-gray-400 ml-1"></span>
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

{{-- CARD: COURIER & SHIPMENT --}}
<div class="bg-white dark:bg-gray-900 rounded-2xl shadow p-6" x-data="{ open: false }">
    <div class="flex items-center justify-between cursor-pointer" @click="open = !open">
        <h3 class="text-base font-semibold text-[#1B2A4A] dark:text-white">
            Courier &amp; Shipment <span x-text="open ? '▲' : '▼'" class="text-xs text-gray-400 ml-1"></span>
        </h3>
    </div>
    <div x-show="open" x-transition class="mt-4 border-t dark:border-gray-700 pt-4">
        @if($order->shipment)
            <div class="space-y-2">
                <div class="flex justify-between"><span class="text-xs text-gray-500 dark:text-gray-400">Courier</span><span class="font-medium capitalize text-gray-700 dark:text-gray-300">{{ $order->shipment->courier_name }}</span></div>
                @if($order->shipment->tracking_code)
                <div class="flex justify-between items-center"><span class="text-xs text-gray-500 dark:text-gray-400">Tracking</span>
                    <div class="flex items-center gap-1">
                        <span class="font-mono text-xs">{{ $order->shipment->tracking_code }}</span>
                        <button type="button" onclick="copyText('{{ $order->shipment->tracking_code }}')" class="text-xs">📋</button>
                    </div>
                </div>
                @endif
                <div class="flex justify-between"><span class="text-xs text-gray-500 dark:text-gray-400">Status</span><span class="px-2 py-0.5 text-xs rounded-full bg-blue-100 text-blue-700 capitalize">{{ $order->shipment->status }}</span></div>
            </div>
        @else
            <form method="POST" action="{{ route('admin.orders.submitCourier', $order) }}" class="space-y-3">
                @csrf
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs text-gray-500 dark:text-gray-400 mb-1 block">Courier</label>
                        <select name="courier" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 px-3 py-2 text-sm bg-white dark:bg-gray-800 dark:text-white focus:outline-none">
                            <option value="manual">Manual</option>
                            @foreach($activeCouriers as $c)<option value="{{ $c->courier }}">{{ ucfirst($c->courier) }}</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-xs text-gray-500 dark:text-gray-400 mb-1 block">Delivery Fee (৳)</label>
                        <input type="number" name="delivery_fee" min="0" placeholder="0" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 px-3 py-2 text-sm bg-white dark:bg-gray-800 dark:text-white">
                    </div>
                </div>
                <input type="text" name="tracking_code" placeholder="Tracking code (manual)" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 px-3 py-2 text-sm bg-white dark:bg-gray-800 dark:text-white">
                <button type="submit" class="w-full py-2 text-xs font-semibold bg-[#1B2A4A] text-white rounded-xl hover:bg-[#243a63] transition">Assign Courier</button>
            </form>
        @endif
    </div>
</div>

</div>{{-- END LEFT COLUMN --}}

{{-- ══════════════ RIGHT COLUMN: SUMMARY SIDEBAR ══════════════ --}}
<div class="lg:col-span-1">
<div class="bg-white dark:bg-gray-900 rounded-2xl shadow p-6 lg:sticky lg:top-4 space-y-4">

    <div class="flex items-center justify-between">
        <h3 class="text-base font-semibold text-[#1B2A4A] dark:text-white">Summary</h3>
        <button type="button" onclick="copyOrderInfo()"
            class="bg-[#F59E0B] text-white px-3 py-1.5 rounded-xl text-xs font-medium hover:bg-yellow-500 transition flex items-center gap-1">
            📋 Copy Info
        </button>
    </div>

    {{-- Invoice Meta --}}
    <div class="space-y-2 text-sm border-b dark:border-gray-700 pb-4">
        <div class="flex justify-between">
            <span class="text-gray-500 dark:text-gray-400 text-xs">Invoice ID</span>
            <span class="font-bold text-[#1B2A4A] dark:text-white">#{{ str_pad($order->id, 5, '0', STR_PAD_LEFT) }}</span>
        </div>
        <div class="flex justify-between">
            <span class="text-gray-500 dark:text-gray-400 text-xs">Created</span>
            <span class="text-xs text-gray-600 dark:text-gray-400">{{ $order->created_at->format('d/m/Y H:i') }}</span>
        </div>
        <div class="flex justify-between">
            <span class="text-gray-500 dark:text-gray-400 text-xs">Last Update</span>
            <span class="text-xs text-gray-600 dark:text-gray-400">{{ $order->updated_at->format('d/m/Y H:i') }}</span>
        </div>
        <div class="flex justify-between items-center">
            <span class="text-gray-500 dark:text-gray-400 text-xs">User IP</span>
            <div class="flex items-center gap-1">
                <span class="text-xs text-gray-600 dark:text-gray-400 font-mono">{{ $order->user_ip ?? 'N/A' }}</span>
                @if($order->user_ip)
                <button type="button" onclick="blockIP('{{ $order->user_ip }}')" class="text-xs text-red-500 hover:underline">(Block)</button>
                @endif
            </div>
        </div>
        <div>
            <label class="text-xs text-gray-500 dark:text-gray-400 block mb-1">Assigned To</label>
            <select name="assigned_to" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 px-3 py-1.5 text-xs bg-white dark:bg-gray-800 dark:text-white">
                <option value="">Unassigned</option>
                @foreach($admins as $admin)
                <option value="{{ $admin->id }}" {{ $order->assigned_to == $admin->id ? 'selected' : '' }}>{{ $admin->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs text-gray-500 dark:text-gray-400 block mb-1">Reference No</label>
            <input type="text" name="reference_no" value="{{ old('reference_no', $order->reference_no) }}"
                class="w-full rounded-xl border border-gray-200 dark:border-gray-700 px-3 py-1.5 text-xs bg-white dark:bg-gray-800 dark:text-white">
        </div>
        <div>
            <label class="text-xs text-gray-500 dark:text-gray-400 block mb-1">Source Website</label>
            <input type="text" name="source_website" value="{{ old('source_website', $order->source_website) }}"
                class="w-full rounded-xl border border-gray-200 dark:border-gray-700 px-3 py-1.5 text-xs bg-white dark:bg-gray-800 dark:text-white">
        </div>
    </div>

    {{-- Financial Calculator (Alpine.js reactive) --}}
    <div class="space-y-3 text-sm">
        <div class="flex justify-between items-center">
            <span class="text-gray-500 dark:text-gray-400 text-xs">Subtotal</span>
            <span class="font-semibold dark:text-white">৳<span x-text="subtotal.toFixed(2)"></span></span>
            <input type="hidden" name="subtotal" :value="subtotal" id="subtotalInput">
        </div>

        <div class="flex justify-between items-center gap-2">
            <span class="text-gray-500 dark:text-gray-400 text-xs flex items-center gap-1">
                Discount
                <button type="button" @click="discountType = discountType === 'fixed' ? 'percent' : 'fixed'"
                    class="px-2 py-0.5 rounded-lg text-xs border border-gray-300 dark:border-gray-600 dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700"
                    x-text="discountType === 'fixed' ? '৳' : '%'"></button>
            </span>
            <input type="number" name="discount_value" x-model="discountValue" step="0.01" min="0"
                class="w-24 text-right rounded-xl border border-gray-200 dark:border-gray-700 px-2 py-1 text-xs bg-white dark:bg-gray-800 dark:text-white">
            <input type="hidden" name="discount_type" :value="discountType">
        </div>

        <div class="flex justify-between items-center gap-2">
            <span class="text-gray-500 dark:text-gray-400 text-xs">Shipping Charge</span>
            <input type="number" name="shipping_charge" x-model="shipping" step="0.01" min="0"
                class="w-24 text-right rounded-xl border border-gray-200 dark:border-gray-700 px-2 py-1 text-xs bg-white dark:bg-gray-800 dark:text-white">
        </div>

        <div class="flex justify-between items-center pt-2 border-t dark:border-gray-700">
            <span class="font-semibold text-gray-700 dark:text-white text-sm">Grand Total</span>
            <span class="font-bold text-[#1B2A4A] dark:text-white text-base">৳<span x-text="grandTotal.toFixed(2)"></span></span>
            <input type="hidden" name="grand_total" :value="grandTotal">
        </div>

        <div class="flex justify-between items-center gap-2">
            <span class="text-gray-500 dark:text-gray-400 text-xs">Paid Amount</span>
            <div class="flex items-center gap-1">
                <input type="number" name="paid_amount" x-model="paid" step="0.01" min="0"
                    class="w-24 text-right rounded-xl border border-gray-200 dark:border-gray-700 px-2 py-1 text-xs bg-white dark:bg-gray-800 dark:text-white">
                <button type="button" onclick="openAddPaymentModal()"
                    class="text-xs text-[#F59E0B] whitespace-nowrap hover:underline">+ Add</button>
            </div>
        </div>

        <div class="flex justify-between items-center pt-2 border-t dark:border-gray-700">
            <span class="font-semibold text-gray-700 dark:text-white text-sm">Due</span>
            <span class="font-bold text-base" :class="due > 0 ? 'text-red-500' : 'text-green-500'">৳<span x-text="due.toFixed(2)"></span></span>
            <input type="hidden" name="due" :value="due">
        </div>

        <div class="flex justify-between items-center gap-2">
            <span class="text-gray-500 dark:text-gray-400 text-xs">Courier Charged to Me</span>
            <input type="number" name="courier_charge" x-model="courierCharge" step="0.01" min="0"
                class="w-24 text-right rounded-xl border border-gray-200 dark:border-gray-700 px-2 py-1 text-xs bg-white dark:bg-gray-800 dark:text-white">
        </div>

        {{-- Coupon --}}
        <div class="flex gap-2">
            <input type="text" name="coupon_code" placeholder="Coupon code..."
                value="{{ $order->coupon?->code ?? old('coupon_code') }}"
                class="flex-1 rounded-xl border border-gray-200 dark:border-gray-700 px-3 py-1.5 text-xs bg-white dark:bg-gray-800 dark:text-white">
            <button type="button" onclick="applyCoupon()"
                class="px-3 py-1.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-white rounded-xl text-xs hover:bg-gray-200 transition">Apply</button>
        </div>

        {{-- Skip Followup --}}
        <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" name="skip_followup" value="1" {{ $order->skip_followup ? 'checked' : '' }}
                class="rounded border-gray-300 text-[#1B2A4A]">
            <span class="text-xs text-gray-600 dark:text-gray-400">Skip/Done Followup</span>
        </label>

        {{-- Action Buttons --}}
        <button type="submit" class="w-full bg-[#1B2A4A] text-white py-3 rounded-xl text-sm font-semibold hover:bg-[#243a63] transition mt-2">
            ✔ Update Order
        </button>
        <a href="{{ route('admin.invoices.generatePrint', $order) }}" target="_blank"
            class="w-full block text-center border-2 border-[#1B2A4A] dark:border-gray-600 text-[#1B2A4A] dark:text-white py-2.5 rounded-xl text-sm font-semibold hover:bg-gray-50 dark:hover:bg-gray-800 transition">
            🖨 Invoice Print
        </a>
    </div>

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
        discountValue: {{ (float)($order->discount ?? 0) }},
        shipping:      {{ (float)($order->shipping_charge ?? 0) }},
        paid:          {{ (float)($order->paid_amount ?? 0) }},
        courierCharge: {{ (float)($order->courier_charge ?? 0) }},

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
}
function closeProductModal() {
    const m = document.getElementById('productModal');
    m.classList.add('hidden'); m.classList.remove('flex');
}

let searchTimeout;
function searchProducts(q) {
    clearTimeout(searchTimeout);
    if (q.length < 2) { document.getElementById('productSearchResults').innerHTML = '<p class="text-center text-gray-400 text-sm py-4">Type to search...</p>'; return; }
    searchTimeout = setTimeout(() => {
        fetch(SEARCH_URL + '?q=' + encodeURIComponent(q), {
            headers: {'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json'}
        })
        .then(r => r.json())
        .then(products => {
            const c = document.getElementById('productSearchResults');
            if (!products.length) { c.innerHTML = '<p class="text-center text-gray-400 text-sm py-4">No products found.</p>'; return; }
            c.innerHTML = products.map(p => `
                <div class="flex items-center gap-3 p-3 border dark:border-gray-700 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer" onclick='addProductRow(${JSON.stringify(p)})'>
                    <img src="${p.image_url}" class="w-10 h-10 object-cover rounded-lg" onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%2240%22 height=%2240%22/>'">
                    <div class="flex-1">
                        <div class="text-sm font-medium dark:text-white">${p.name}</div>
                        <div class="text-xs text-gray-400">Stock: ${p.stock} | ${p.variants.length} variants</div>
                    </div>
                </div>
            `).join('');
        });
    }, 300);
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
                class="w-8 h-8 flex items-center justify-center bg-red-100 dark:bg-red-900/30 text-red-500 rounded-lg hover:bg-red-200 mx-auto">✕</button>
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
        const tbody = type === 'customer'
            ? document.getElementById('customerNotesBody')
            : document.getElementById('orderNotesBody');
        const noRow = document.getElementById('noOrderNotes');
        if (noRow) noRow.remove();
        const rows  = tbody.querySelectorAll('tr').length;
        const tr    = document.createElement('tr');
        tr.id        = 'note-row-' + data.note.id;
        tr.className = 'border-b dark:border-gray-800';
        tr.innerHTML = `
            <td class="px-3 py-2 text-gray-500">${rows + 1}</td>
            <td class="px-3 py-2 text-gray-700 dark:text-gray-300">${data.note.note_text}</td>
            <td class="px-3 py-2 text-gray-500">${data.note.user_name}</td>
            <td class="px-3 py-2 text-gray-400 text-xs">${data.note.created_at}</td>
            <td class="px-3 py-2 text-center">
                <button type="button" onclick="deleteNote(${data.note.id})"
                    class="w-7 h-7 flex items-center justify-center bg-red-100 dark:bg-red-900/30 text-red-500 rounded-lg hover:bg-red-200 mx-auto text-xs">✕</button>
            </td>`;
        tbody.prepend(tr);
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
    const amount = prompt('Enter paid amount:');
    if (amount && !isNaN(amount)) {
        document.querySelector('[name="paid_amount"]').value = parseFloat(amount).toFixed(2);
    }
}

function refreshCourierStats(e) {
    e.stopPropagation();
    showToast('Refreshing...');
    location.reload();
}

function togglePackingItems() {
    showToast('Packing items view - coming soon');
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
