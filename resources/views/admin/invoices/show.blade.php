@extends('layouts.admin')

@section('title', 'Invoice ' . $invoice->invoice_number)
@section('page-title', 'Invoice Detail')

@section('breadcrumb')
    <span class="text-textsecondary">/</span>
    <a href="{{ route('admin.invoices.index') }}" class="text-textsecondary hover:text-textprimary dark:hover:text-gray-200 transition-colors">Invoices</a>
    <span class="text-textsecondary">/</span>
    <span class="text-textprimary font-medium">{{ $invoice->invoice_number }}</span>
@endsection

@section('content')

@if(session('success'))
    <div class="mb-5 flex items-center gap-3 p-3.5 rounded-xl bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800">
        <svg class="w-4 h-4 text-green-600 dark:text-green-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/></svg>
        <p class="text-sm text-green-700 dark:text-green-400">{{ session('success') }}</p>
    </div>
@endif

<div class="flex flex-wrap items-center justify-between gap-3 mb-6">
    <div class="flex items-center gap-3">
        <h2 class="text-lg font-heading font-bold text-textprimary dark:text-gray-100">{{ $invoice->invoice_number }}</h2>
        @php
            $bg = match($invoice->status) {
                'paid'      => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                'sent'      => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                'cancelled' => 'bg-red-100 text-danger dark:bg-red-900/30 dark:text-red-400',
                default     => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
            };
        @endphp
        <span class="px-2.5 py-1 text-xs font-medium rounded-full {{ $bg }}">{{ ucfirst($invoice->status) }}</span>
    </div>
    <div class="flex items-center gap-2">
        <form method="POST" action="{{ route('admin.invoices.show', $invoice) }}" class="flex items-center gap-2">
            @csrf @method('PATCH')
            <select name="status" class="px-4 py-2.5 text-sm rounded-xl border border-border dark:border-gray-600 bg-white dark:bg-gray-700/50 text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary dark:focus:border-primary transition-all duration-200">
                @foreach(['draft','sent','paid','cancelled'] as $s)
                    <option value="{{ $s }}" {{ $invoice->status === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
            <button type="submit" class="px-3 py-2.5 text-xs font-semibold bg-primary text-white rounded-xl hover:bg-primary/90 transition-colors">Update Status</button>
        </form>
        <a href="{{ route('admin.invoices.print', $invoice) }}" target="_blank"
           class="inline-flex items-center gap-1.5 px-3 py-2.5 text-xs font-semibold bg-accent hover:bg-yellow-500 text-black rounded-xl transition-colors">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z"/></svg>
            Print Invoice
        </a>
    </div>
</div>

<x-admin.card>
    <div class="p-6">
        <div class="flex flex-wrap justify-between gap-4 mb-8 pb-6 border-b border-border dark:border-gray-700">
            <div>
                <h2 class="text-2xl font-heading font-bold text-primary dark:text-accent">Ecom Pilot</h2>
                <p class="text-sm text-textsecondary dark:text-gray-400 mt-1">Invoice</p>
            </div>
            <div class="text-right">
                <p class="font-mono text-lg font-bold text-textprimary dark:text-gray-100">{{ $invoice->invoice_number }}</p>
                <p class="text-sm text-textsecondary dark:text-gray-400">Date: {{ $invoice->created_at->format('d M Y') }}</p>
                <p class="text-sm text-textsecondary dark:text-gray-400">Order: {{ $invoice->order?->order_number }}</p>
            </div>
        </div>

        <div class="mb-6">
            <h4 class="text-xs font-semibold uppercase tracking-widest text-textsecondary dark:text-gray-400 mb-2">Bill To</h4>
            <p class="font-semibold text-textprimary dark:text-gray-100">{{ $invoice->order?->shipping_name }}</p>
            <p class="text-sm text-textsecondary dark:text-gray-400">{{ $invoice->order?->shipping_phone }}</p>
            <p class="text-sm text-textsecondary dark:text-gray-400">{{ $invoice->order?->shipping_address }}</p>
            <p class="text-sm text-textsecondary dark:text-gray-400">{{ $invoice->order?->shipping_area }}, {{ $invoice->order?->shipping_district }}</p>
        </div>

        <table class="w-full text-sm mb-6">
            <thead class="border-b-2 border-border dark:border-gray-700">
                <tr>
                    <th class="text-left py-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Product</th>
                    <th class="text-left py-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Variant</th>
                    <th class="text-center py-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Qty</th>
                    <th class="text-right py-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Unit Price</th>
                    <th class="text-right py-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border dark:divide-gray-700">
                @foreach($invoice->order?->items ?? [] as $item)
                <tr>
                    <td class="py-2 text-textprimary dark:text-gray-100">{{ $item->product?->name }}</td>
                    <td class="py-2 text-textsecondary dark:text-gray-400">{{ $item->variant ? $item->variant->size . ' / ' . $item->variant->color : '—' }}</td>
                    <td class="py-2 text-center text-textprimary dark:text-gray-100">{{ $item->qty }}</td>
                    <td class="py-2 text-right text-textprimary dark:text-gray-100">৳{{ number_format($item->unit_price, 0) }}</td>
                    <td class="py-2 text-right font-semibold text-textprimary dark:text-gray-100">৳{{ number_format($item->total, 0) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="flex justify-end">
            <div class="w-64 space-y-1 text-sm">
                <div class="flex justify-between"><span class="text-textsecondary dark:text-gray-400">Subtotal</span><span>৳{{ number_format($invoice->subtotal, 0) }}</span></div>
                @if($invoice->discount > 0)
                <div class="flex justify-between text-green-600 dark:text-green-400"><span>Discount</span><span>-৳{{ number_format($invoice->discount, 0) }}</span></div>
                @endif
                <div class="flex justify-between"><span class="text-textsecondary dark:text-gray-400">Shipping</span><span>৳{{ number_format($invoice->shipping, 0) }}</span></div>
                <div class="flex justify-between font-bold text-base pt-2 border-t border-border dark:border-gray-700 text-textprimary dark:text-gray-100">
                    <span>Grand Total</span><span>৳{{ number_format($invoice->total, 0) }}</span>
                </div>
            </div>
        </div>

        @if($invoice->notes)
        <div class="mt-6 pt-4 border-t border-border dark:border-gray-700">
            <p class="text-xs text-textsecondary dark:text-gray-400">{{ $invoice->notes }}</p>
        </div>
        @endif
    </div>
</x-admin.card>

@endsection
