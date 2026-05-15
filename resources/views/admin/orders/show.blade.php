@extends('layouts.admin')

@section('title', 'Order #' . $order->order_number)
@section('page-title', 'Order Detail')

@section('breadcrumb')
    <span class="text-textsecondary">/</span>
    <a href="{{ route('admin.orders.index') }}" class="hover:text-primary transition-colors">Orders</a>
    <span class="text-textsecondary">/</span>
    <span class="text-textprimary font-medium">{{ $order->order_number }}</span>
@endsection

@section('content')

@if(session('success'))
<div class="mb-4 flex items-center gap-3 p-3.5 rounded-xl bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800">
    <svg class="w-4 h-4 text-green-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/></svg>
    <p class="text-sm text-green-700 dark:text-green-400">{{ session('success') }}</p>
</div>
@endif
@if(session('error'))
<div class="mb-4 flex items-center gap-3 p-3.5 rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800">
    <svg class="w-4 h-4 text-red-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd"/></svg>
    <p class="text-sm text-red-700 dark:text-red-400">{{ session('error') }}</p>
</div>
@endif

{{-- Page Header --}}
<div class="flex flex-wrap items-start justify-between gap-4 mb-5">
    <div>
        <h1 class="font-heading text-xl font-bold text-textprimary dark:text-gray-100">#{{ $order->order_number }}</h1>
        <div class="flex items-center gap-2 mt-1 flex-wrap">
            <span class="text-xs text-textsecondary dark:text-gray-400">Created {{ $order->created_at->format('d M Y, H:i') }}</span>
            @php
                $sourceBadge = match($order->source ?? 'manual') {
                    'wordpress' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                    'cartflow'  => 'bg-purple-100 text-purple-700',
                    default     => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
                };
            @endphp
            <span class="px-2 py-0.5 text-[10px] font-semibold rounded-full {{ $sourceBadge }}">{{ ucfirst($order->source ?? 'manual') }}</span>
        </div>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('admin.invoices.generatePrint', $order) }}" target="_blank"
           class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold border border-border dark:border-gray-600 bg-white dark:bg-gray-800 text-textprimary dark:text-gray-200 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z"/></svg>
            Print Invoice
        </a>
        <a href="{{ route('admin.orders.index') }}"
           class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold bg-primary text-white rounded-xl hover:bg-primary/90 transition-colors">
            &larr; Back to Orders
        </a>
    </div>
</div>

{{-- Status Timeline --}}
@php
    $terminalStatuses = ['cancelled','returned','damage','failed'];
    $isTerminal = in_array($order->status, $terminalStatuses);
@endphp
<div class="bg-white dark:bg-gray-800 rounded-2xl border border-border dark:border-gray-700 shadow-sm p-4 mb-5">
    @if($isTerminal)
        <div class="flex items-center gap-3">
            <span class="w-8 h-8 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                <svg class="w-4 h-4 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </span>
            <div>
                <p class="text-sm font-semibold text-red-600 dark:text-red-400">{{ ucwords(str_replace('_',' ',$order->status)) }}</p>
                <p class="text-xs text-textsecondary dark:text-gray-400">This order has been {{ strtolower(str_replace('_',' ',$order->status)) }}</p>
            </div>
        </div>
    @else
        <div class="flex items-center">
            @foreach($statusSteps as $i => $step)
            @php
                $isDone    = $currentStep > $i;
                $isCurrent = $currentStep === $i;
                $label     = ucwords(str_replace('_', ' ', $step));
            @endphp
            <div class="flex items-center {{ $i < count($statusSteps) - 1 ? 'flex-1' : '' }}">
                <div class="flex flex-col items-center">
                    <div class="w-7 h-7 rounded-full flex items-center justify-center flex-shrink-0 text-xs font-bold
                        {{ $isDone    ? 'bg-primary text-white' : '' }}
                        {{ $isCurrent ? 'bg-accent text-white ring-2 ring-accent/30' : '' }}
                        {{ !$isDone && !$isCurrent ? 'bg-gray-100 dark:bg-gray-700 text-gray-400 dark:text-gray-500 border-2 border-gray-200 dark:border-gray-600' : '' }}">
                        @if($isDone)
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                        @else
                            {{ $i + 1 }}
                        @endif
                    </div>
                    <span class="text-[9px] mt-1 font-medium whitespace-nowrap
                        {{ $isCurrent ? 'text-accent' : ($isDone ? 'text-primary dark:text-blue-400' : 'text-gray-400 dark:text-gray-500') }}">
                        {{ $label }}
                    </span>
                </div>
                @if($i < count($statusSteps) - 1)
                <div class="flex-1 h-0.5 mx-1 {{ $isDone ? 'bg-primary' : 'bg-gray-200 dark:bg-gray-700' }}"></div>
                @endif
            </div>
            @endforeach
        </div>
    @endif
</div>

{{-- 2-column layout --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

    {{-- LEFT COLUMN --}}
    <div class="lg:col-span-2 space-y-5">

        {{-- Card: Order Items --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-border dark:border-gray-700 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-border dark:border-gray-700">
                <h3 class="font-heading font-semibold text-sm text-textprimary dark:text-gray-100">Order Items</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Product</th>
                            <th class="px-4 py-2.5 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Qty</th>
                            <th class="px-4 py-2.5 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Unit Price</th>
                            <th class="px-4 py-2.5 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border dark:divide-gray-700">
                        @foreach($order->items as $item)
                        @php $thumb = $item->product?->images->first(); @endphp
                        <tr>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    @if($thumb)
                                        <img src="{{ asset('storage/' . $thumb->path) }}" alt="{{ $item->product_name }}" class="w-10 h-10 rounded-lg object-cover flex-shrink-0 border border-border dark:border-gray-600">
                                    @else
                                        <div class="w-10 h-10 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center flex-shrink-0">
                                            <svg class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909"/></svg>
                                        </div>
                                    @endif
                                    <div>
                                        <p class="text-sm font-medium text-textprimary dark:text-gray-100">{{ $item->product_name }}</p>
                                        @if($item->variant)
                                            <p class="text-xs text-textsecondary dark:text-gray-400">
                                                {{ $item->variant->size ? 'Size: ' . $item->variant->size : '' }}
                                                {{ $item->variant->color ? '· Color: ' . $item->variant->color : '' }}
                                            </p>
                                        @elseif($item->variant_info)
                                            <p class="text-xs text-textsecondary dark:text-gray-400">{{ $item->variant_info }}</p>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-center text-sm text-textprimary dark:text-gray-200">{{ $item->qty }}</td>
                            <td class="px-4 py-3 text-right text-sm text-textprimary dark:text-gray-200">৳{{ number_format($item->unit_price, 0) }}</td>
                            <td class="px-4 py-3 text-right text-sm font-semibold text-textprimary dark:text-gray-100">৳{{ number_format($item->total, 0) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="border-t border-border dark:border-gray-700 bg-gray-50/50 dark:bg-gray-700/20">
                        <tr>
                            <td colspan="3" class="px-4 py-2 text-right text-xs text-textsecondary dark:text-gray-400">Subtotal</td>
                            <td class="px-4 py-2 text-right text-sm text-textprimary dark:text-gray-200">৳{{ number_format($order->subtotal, 0) }}</td>
                        </tr>
                        @if($order->discount > 0)
                        <tr>
                            <td colspan="3" class="px-4 py-2 text-right text-xs text-green-600 dark:text-green-400">
                                Discount{{ $order->coupon ? ' (' . $order->coupon->code . ')' : '' }}
                            </td>
                            <td class="px-4 py-2 text-right text-sm text-green-600 dark:text-green-400">-৳{{ number_format($order->discount, 0) }}</td>
                        </tr>
                        @endif
                        <tr>
                            <td colspan="3" class="px-4 py-2 text-right text-xs text-textsecondary dark:text-gray-400">Shipping</td>
                            <td class="px-4 py-2 text-right text-sm text-textprimary dark:text-gray-200">৳{{ number_format($order->shipping_charge, 0) }}</td>
                        </tr>
                        <tr class="border-t border-border dark:border-gray-700">
                            <td colspan="3" class="px-4 py-3 text-right text-sm font-bold text-textprimary dark:text-gray-100">Grand Total</td>
                            <td class="px-4 py-3 text-right text-base font-bold text-primary dark:text-blue-400">৳{{ number_format($order->total, 0) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- Card: Status Management --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-border dark:border-gray-700 shadow-sm">
            <div class="px-5 py-4 border-b border-border dark:border-gray-700">
                <h3 class="font-heading font-semibold text-sm text-textprimary dark:text-gray-100">Status Management</h3>
            </div>
            <div class="p-5">
                @php
                    $statusCls = match($order->status) {
                        'pending'       => 'text-yellow-700 bg-yellow-50 dark:text-yellow-400 dark:bg-yellow-900/20',
                        'confirmed'     => 'text-blue-700 bg-blue-50 dark:text-blue-400 dark:bg-blue-900/20',
                        'processing'    => 'text-teal-700 bg-teal-50 dark:text-teal-400 dark:bg-teal-900/20',
                        'in_courier'    => 'text-purple-700 bg-purple-50 dark:text-purple-400 dark:bg-purple-900/20',
                        'delivered'     => 'text-green-700 bg-green-50 dark:text-green-400 dark:bg-green-900/20',
                        'completed'     => 'text-green-800 bg-green-100 dark:text-green-300 dark:bg-green-900/30',
                        'cancelled','failed' => 'text-red-700 bg-red-50 dark:text-red-400 dark:bg-red-900/20',
                        default         => 'text-gray-700 bg-gray-100 dark:text-gray-400 dark:bg-gray-700',
                    };
                @endphp
                <div class="mb-4">
                    <span class="text-xs text-textsecondary dark:text-gray-400 mb-1 block">Current Status</span>
                    <span class="px-3 py-1 rounded-lg text-sm font-semibold {{ $statusCls }}">{{ ucwords(str_replace('_',' ',$order->status)) }}</span>
                </div>
                <form method="POST" action="{{ route('admin.orders.updateStatus', $order) }}" class="space-y-3">
                    @csrf @method('PATCH')
                    <div>
                        <label class="block text-xs font-medium text-textsecondary dark:text-gray-400 mb-1">Change Status To</label>
                        <select name="status" class="w-full px-3 py-2 text-sm rounded-xl border border-border dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30">
                            @foreach([
                                'pending'=>'Pending','confirmed'=>'Confirmed','failed'=>'Failed',
                                'processing'=>'Processing','hold'=>'Hold','hold_followup'=>'Hold Followup',
                                'in_courier'=>'In Courier','delivered'=>'Delivered','completed'=>'Completed',
                                'cancelled'=>'Canceled','pending_return'=>'Pending Return','returned'=>'Returned',
                                'damage'=>'Damage','hand_delivery'=>'Hand Delivery',
                                'hand_delivery_completed'=>'Hand Delivery Completed',
                                'others'=>'Others','return_collection'=>'Return Collection',
                                'courier_payment_validate'=>'Courier Pmt. Validate',
                            ] as $val => $lbl)
                                <option value="{{ $val }}" {{ $order->status === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="w-full px-4 py-2 text-sm font-semibold bg-primary text-white rounded-xl hover:bg-primary/90 transition-colors">Update Status</button>
                </form>
            </div>
        </div>

        {{-- Card: Order Notes (AJAX) --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-border dark:border-gray-700 shadow-sm"
             x-data="orderNotes({{ $order->id }})">
            <div class="px-5 py-4 border-b border-border dark:border-gray-700">
                <h3 class="font-heading font-semibold text-sm text-textprimary dark:text-gray-100">Internal Notes</h3>
            </div>
            <div class="p-5 space-y-4">
                {{-- Notes list --}}
                <div id="notes-list" class="space-y-3">
                    @forelse($order->orderNotes as $note)
                    <div class="flex gap-3" id="note-{{ $note->id }}">
                        <div class="w-7 h-7 rounded-full bg-primary flex items-center justify-center text-white text-[10px] font-bold flex-shrink-0">{{ strtoupper(substr($note->user->name ?? 'A', 0, 1)) }}</div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-0.5">
                                <span class="text-xs font-semibold text-textprimary dark:text-gray-200">{{ $note->user->name ?? 'Admin' }}</span>
                                @if($note->is_customer_visible)
                                    <span class="px-1.5 py-0.5 text-[9px] font-semibold bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 rounded">Customer visible</span>
                                @endif
                                <span class="text-[10px] text-textsecondary dark:text-gray-500 ml-auto">{{ $note->created_at->diffForHumans() }}</span>
                            </div>
                            <p class="text-sm text-textprimary dark:text-gray-300">{{ $note->note }}</p>
                        </div>
                        <button onclick="deleteNote({{ $note->id }})" class="text-gray-300 dark:text-gray-600 hover:text-red-400 transition-colors flex-shrink-0" title="Delete">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    @empty
                    <p class="text-sm text-textsecondary dark:text-gray-500 text-center py-3" id="no-notes-msg">No notes yet.</p>
                    @endforelse
                </div>
                {{-- Add note form --}}
                <form id="noteForm" class="border-t border-border dark:border-gray-700 pt-4 space-y-2">
                    @csrf
                    <textarea id="noteText" rows="3" placeholder="Write an internal note&hellip;"
                              class="w-full px-3 py-2 text-sm rounded-xl border border-border dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 text-textprimary dark:text-gray-100 placeholder-textsecondary focus:outline-none focus:ring-2 focus:ring-primary/30 resize-none"></textarea>
                    <div class="flex items-center justify-between">
                        <label class="flex items-center gap-2 text-sm text-textprimary dark:text-gray-200 cursor-pointer">
                            <input type="checkbox" id="customerVisible" class="w-4 h-4 rounded border-border text-primary focus:ring-primary/30">
                            Customer visible
                        </label>
                        <button type="button" onclick="submitNote()" class="px-4 py-2 text-xs font-semibold bg-primary text-white rounded-xl hover:bg-primary/90 transition-colors">Add Note</button>
                    </div>
                </form>
            </div>
        </div>

    </div>

    {{-- RIGHT COLUMN --}}
    <div class="space-y-5">

        {{-- Customer Info --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-border dark:border-gray-700 shadow-sm p-5">
            <h3 class="font-heading font-semibold text-sm text-textprimary dark:text-gray-100 mb-4">Customer</h3>
            <div class="space-y-2">
                <div class="flex items-start justify-between">
                    <p class="text-sm font-semibold text-textprimary dark:text-gray-100">{{ $order->shipping_name }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="px-2 py-0.5 text-xs rounded-full bg-gray-800 text-white dark:bg-gray-600">{{ $order->shipping_phone }}</span>
                    <a href="https://wa.me/880{{ ltrim($order->shipping_phone,'0') }}" target="_blank" class="text-green-500 hover:text-green-600" title="WhatsApp">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                    </a>
                    <button onclick="navigator.clipboard.writeText('{{ $order->shipping_phone }}')" class="text-gray-400 hover:text-primary transition-colors" title="Copy">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
                    </button>
                </div>
            </div>
        </div>

        {{-- Shipping Address --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-border dark:border-gray-700 shadow-sm p-5">
            <h3 class="font-heading font-semibold text-sm text-textprimary dark:text-gray-100 mb-4">Shipping Address</h3>
            <div class="space-y-1 text-sm text-textsecondary dark:text-gray-400">
                @if($order->shipping_division)<p>{{ $order->shipping_division }}</p>@endif
                @if($order->shipping_district)<p>{{ $order->shipping_district }}</p>@endif
                @if($order->shipping_area)<p>{{ $order->shipping_area }}</p>@endif
                @if($order->shipping_address)<p>{{ $order->shipping_address }}</p>@endif
            </div>
            @php
                $addr = collect([$order->shipping_address, $order->shipping_area, $order->shipping_district, $order->shipping_division])->filter()->implode(', ');
            @endphp
            @if($addr)
            <button onclick="navigator.clipboard.writeText('{{ addslashes($addr) }}')"
                    class="mt-3 flex items-center gap-1 text-xs text-primary dark:text-blue-400 hover:underline">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
                Copy address
            </button>
            @endif
        </div>

        {{-- Payment Info --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-border dark:border-gray-700 shadow-sm p-5">
            <h3 class="font-heading font-semibold text-sm text-textprimary dark:text-gray-100 mb-4">Payment</h3>
            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-xs text-textsecondary dark:text-gray-400">Method</span>
                    <span class="text-sm font-semibold text-textprimary dark:text-gray-100 uppercase">{{ $order->payment_method }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-xs text-textsecondary dark:text-gray-400">Status</span>
                    @php
                        $payBadge = match($order->payment_status) {
                            'paid'     => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                            'failed'   => 'bg-red-100 text-red-700',
                            'refunded' => 'bg-purple-100 text-purple-700',
                            default    => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
                        };
                    @endphp
                    <span class="px-2 py-0.5 text-xs font-semibold rounded-full {{ $payBadge }}">{{ ucfirst($order->payment_status) }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-xs text-textsecondary dark:text-gray-400">Total</span>
                    <span class="text-sm font-bold text-textprimary dark:text-gray-100">৳{{ number_format($order->total, 0) }}</span>
                </div>
                @if($order->payment_status !== 'paid')
                <form method="POST" action="{{ route('admin.payments.markPaid', $order) }}" class="pt-1">
                    @csrf @method('PATCH')
                    <button type="submit" class="w-full py-2 text-xs font-semibold bg-green-500 hover:bg-green-600 text-white rounded-xl transition-colors">Mark as Paid</button>
                </form>
                @endif
            </div>
        </div>

        {{-- Courier & Shipment --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-border dark:border-gray-700 shadow-sm p-5">
            <h3 class="font-heading font-semibold text-sm text-textprimary dark:text-gray-100 mb-4">Courier &amp; Shipment</h3>
            @if($order->shipment)
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-textsecondary dark:text-gray-400">Courier</span>
                        <span class="text-sm font-semibold text-textprimary dark:text-gray-100 capitalize">{{ $order->shipment->courier_name }}</span>
                    </div>
                    @if($order->shipment->tracking_code)
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-textsecondary dark:text-gray-400">Tracking</span>
                        <div class="flex items-center gap-1">
                            <span class="text-xs font-mono text-textprimary dark:text-gray-200">{{ $order->shipment->tracking_code }}</span>
                            <button onclick="navigator.clipboard.writeText('{{ $order->shipment->tracking_code }}')" class="text-gray-400 hover:text-primary transition-colors">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
                            </button>
                        </div>
                    </div>
                    @endif
                    @if($order->shipment->consignment_id)
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-textsecondary dark:text-gray-400">Consignment ID</span>
                        <span class="text-xs font-mono text-textprimary dark:text-gray-200">{{ $order->shipment->consignment_id }}</span>
                    </div>
                    @endif
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-textsecondary dark:text-gray-400">Status</span>
                        <span class="px-2 py-0.5 text-xs rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 capitalize">{{ $order->shipment->status }}</span>
                    </div>
                </div>
            @else
                <form method="POST" action="{{ route('admin.orders.submitCourier', $order) }}" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-xs font-medium text-textsecondary dark:text-gray-400 mb-1">Select Courier</label>
                        <select name="courier" class="w-full px-3 py-2 text-sm rounded-xl border border-border dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30">
                            <option value="manual">Manual Assignment</option>
                            @foreach($activeCouriers as $c)
                                <option value="{{ $c->courier }}">{{ ucfirst($c->courier) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-textsecondary dark:text-gray-400 mb-1">Delivery Fee (৳)</label>
                        <input type="number" name="delivery_fee" min="0" step="1" placeholder="0"
                               class="w-full px-3 py-2 text-sm rounded-xl border border-border dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-textsecondary dark:text-gray-400 mb-1">Tracking Code (manual only)</label>
                        <input type="text" name="tracking_code" placeholder="e.g. SS-12345"
                               class="w-full px-3 py-2 text-sm rounded-xl border border-border dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-textsecondary dark:text-gray-400 mb-1">Notes</label>
                        <input type="text" name="notes" placeholder="Optional notes"
                               class="w-full px-3 py-2 text-sm rounded-xl border border-border dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30">
                    </div>
                    <button type="submit" class="w-full py-2 text-xs font-semibold bg-primary text-white rounded-xl hover:bg-primary/90 transition-colors">Assign Courier</button>
                </form>
            @endif
        </div>

        {{-- Return/Refund --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-border dark:border-gray-700 shadow-sm p-5">
            <h3 class="font-heading font-semibold text-sm text-textprimary dark:text-gray-100 mb-4">Return / Refund</h3>
            @if($order->orderReturn)
                @php
                    $retBadge = match($order->orderReturn->status) {
                        'approved'  => 'bg-green-100 text-green-700',
                        'rejected'  => 'bg-red-100 text-red-700',
                        'completed' => 'bg-blue-100 text-blue-700',
                        default     => 'bg-amber-100 text-amber-700',
                    };
                @endphp
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-textsecondary dark:text-gray-400">Status</span>
                        <span class="px-2 py-0.5 text-xs font-semibold rounded-full {{ $retBadge }}">{{ ucfirst($order->orderReturn->status) }}</span>
                    </div>
                    <p class="text-xs text-textsecondary dark:text-gray-400">{{ $order->orderReturn->reason }}</p>
                    @if($order->orderReturn->refund_amount > 0)
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-textsecondary dark:text-gray-400">Refund</span>
                        <span class="text-sm font-bold text-green-600">৳{{ number_format($order->orderReturn->refund_amount, 0) }}</span>
                    </div>
                    @endif
                    @if($order->orderReturn->status === 'requested')
                    <div class="flex gap-2 pt-1">
                        <form method="POST" action="{{ route('admin.returns.update', $order->orderReturn) }}" class="flex-1">
                            @csrf @method('PATCH')
                            <input type="hidden" name="action" value="approve">
                            <input type="hidden" name="refund_amount" value="{{ $order->total }}">
                            <input type="hidden" name="refund_method" value="manual">
                            <button type="submit" class="w-full py-1.5 text-xs font-semibold bg-green-500 hover:bg-green-600 text-white rounded-lg transition-colors">Approve</button>
                        </form>
                        <form method="POST" action="{{ route('admin.returns.update', $order->orderReturn) }}" class="flex-1">
                            @csrf @method('PATCH')
                            <input type="hidden" name="action" value="reject">
                            <button type="submit" class="w-full py-1.5 text-xs font-semibold border border-red-300 text-red-600 hover:bg-red-50 rounded-lg transition-colors">Reject</button>
                        </form>
                    </div>
                    @endif
                </div>
            @else
                <form method="POST" action="{{ route('admin.returns.store', $order) }}" class="space-y-3">
                    @csrf
                    <textarea name="reason" rows="3" placeholder="Return reason&hellip;" required
                              class="w-full px-3 py-2 text-sm rounded-xl border border-border dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30 resize-none"></textarea>
                    <div>
                        <label class="block text-xs font-medium text-textsecondary dark:text-gray-400 mb-1">Refund Amount (৳)</label>
                        <input type="number" name="refund_amount" min="0" step="1" placeholder="{{ $order->total }}"
                               class="w-full px-3 py-2 text-sm rounded-xl border border-border dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-textsecondary dark:text-gray-400 mb-1">Refund Method</label>
                        <select name="refund_method"
                                class="w-full px-3 py-2 text-sm rounded-xl border border-border dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30">
                            <option value="manual">Manual</option>
                            <option value="cod_adjustment">COD Adjustment</option>
                            <option value="bkash">bKash</option>
                            <option value="nagad">Nagad</option>
                        </select>
                    </div>
                    <button type="submit" class="w-full py-2 text-xs font-semibold border border-border dark:border-gray-600 text-textprimary dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-xl transition-colors">Process Return</button>
                </form>
            @endif
        </div>

    </div>
</div>

@endsection

@push('scripts')
<script>
const ORDER_ID = {{ $order->id }};
const CSRF = document.querySelector('meta[name=csrf-token]').content;
const NOTES_URL = `/admin/orders/${ORDER_ID}/notes`;
const DELETE_URL = `/admin/orders/notes/`;

function submitNote() {
    const note = document.getElementById('noteText').value.trim();
    if (!note) return;
    const visible = document.getElementById('customerVisible').checked;

    fetch(NOTES_URL, {
        method: 'POST',
        headers: {'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,'Accept':'application/json'},
        body: JSON.stringify({note, is_customer_visible: visible})
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) return;
        const n = data.note;
        document.getElementById('no-notes-msg')?.remove();
        const list = document.getElementById('notes-list');
        const el = document.createElement('div');
        el.className = 'flex gap-3';
        el.id = 'note-' + n.id;
        el.innerHTML = `
            <div class="w-7 h-7 rounded-full bg-primary flex items-center justify-center text-white text-[10px] font-bold flex-shrink-0">${n.user_initial}</div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 mb-0.5">
                    <span class="text-xs font-semibold text-textprimary dark:text-gray-200">${n.user_name}</span>
                    ${n.is_customer_visible ? '<span class="px-1.5 py-0.5 text-[9px] font-semibold bg-blue-100 text-blue-700 rounded">Customer visible</span>' : ''}
                    <span class="text-[10px] text-textsecondary ml-auto">${n.time_ago}</span>
                </div>
                <p class="text-sm text-textprimary dark:text-gray-300">${n.note}</p>
            </div>
            <button onclick="deleteNote(${n.id})" class="text-gray-300 hover:text-red-400 transition-colors flex-shrink-0">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>`;
        list.prepend(el);
        document.getElementById('noteText').value = '';
        document.getElementById('customerVisible').checked = false;
    });
}

function deleteNote(id) {
    if (!confirm('Delete this note?')) return;
    fetch(DELETE_URL + id, {
        method: 'DELETE',
        headers: {'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json'}
    })
    .then(r => r.json())
    .then(data => { if (data.success) document.getElementById('note-' + id)?.remove(); });
}
</script>
@endpush
