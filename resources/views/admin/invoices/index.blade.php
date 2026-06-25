@extends('layouts.admin')

@section('title', 'Invoices')
@section('page-title', 'Invoices')

@section('breadcrumb')
    <span class="text-textsecondary">/</span>
    <span class="text-textprimary font-medium">Invoices</span>
@endsection

@section('content')

@if(session('success'))
    <div class="mb-5 flex items-center gap-3 p-3.5 rounded-xl bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800">
        <svg class="w-4 h-4 text-green-600 dark:text-green-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/></svg>
        <p class="text-sm text-green-700 dark:text-green-400">{{ session('success') }}</p>
    </div>
@endif

<x-admin.card>
    <x-slot:header>
        <h3 class="font-heading font-semibold text-textprimary dark:text-gray-100">Invoices
            <span class="ml-2 px-2 py-0.5 text-xs font-medium rounded-full bg-gray-100 dark:bg-gray-700 text-textsecondary dark:text-gray-400">{{ $invoices->total() }}</span>
        </h3>
        <a href="{{ route('admin.invoices.csv') }}"
           class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold border border-border dark:border-gray-600 text-textprimary dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-xl transition-colors">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
            Export All CSV
        </a>
    </x-slot:header>

    <form method="GET" action="{{ route('admin.invoices.index') }}"
          class="flex flex-wrap gap-3 px-6 py-4 border-b border-border dark:border-gray-700">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Invoice # or Order #…"
               class="flex-1 min-w-[180px] px-4 py-2.5 text-sm rounded-xl border border-border dark:border-gray-600 bg-white dark:bg-gray-700/50 text-textprimary dark:text-gray-100 placeholder-textsecondary dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary dark:focus:border-primary transition-all duration-200">
        <div class="relative">
            <select name="status" class="cs pl-3 py-2.5 text-sm rounded-xl border border-border dark:border-gray-600 bg-white dark:bg-gray-700/50 text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary dark:focus:border-primary transition-all duration-200 cursor-pointer">
                <option value="">All Statuses</option>
                @foreach(['draft','sent','paid','cancelled'] as $s)
                    <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
            <span class="pointer-events-none absolute inset-y-0 right-2.5 flex items-center">
                <svg class="w-4 h-4 text-textsecondary dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </span>
        </div>
        <input type="date" name="date_from" value="{{ request('date_from') }}"
               class="px-4 py-2.5 text-sm rounded-xl border border-border dark:border-gray-600 bg-white dark:bg-gray-700/50 text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary dark:focus:border-primary transition-all duration-200">
        <input type="date" name="date_to" value="{{ request('date_to') }}"
               class="px-4 py-2.5 text-sm rounded-xl border border-border dark:border-gray-600 bg-white dark:bg-gray-700/50 text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary dark:focus:border-primary transition-all duration-200">
        <button type="submit" class="px-4 py-2.5 text-sm font-semibold bg-primary hover:bg-primary/90 text-white rounded-xl transition-colors">Filter</button>
        <a href="{{ route('admin.invoices.index') }}" class="px-4 py-2.5 text-sm font-medium border border-border dark:border-gray-600 text-textsecondary dark:text-gray-400 hover:text-textprimary dark:hover:text-gray-200 rounded-xl transition-colors">Clear</a>
    </form>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="border-b border-border dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                <tr>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Invoice #</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Order #</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Customer</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Amount</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border dark:divide-gray-700">
                @forelse($invoices as $invoice)
                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors">
                    <td class="px-6 py-3 font-mono font-semibold text-textprimary dark:text-gray-100">{{ $invoice->invoice_number }}</td>
                    <td class="px-4 py-3 font-mono text-sm text-textsecondary dark:text-gray-400">{{ $invoice->order?->order_number ?? '—' }}</td>
                    <td class="px-4 py-3 text-textprimary dark:text-gray-100">{{ $invoice->order?->shipping_name ?? '—' }}</td>
                    <td class="px-4 py-3 font-semibold text-textprimary dark:text-gray-100">৳{{ number_format($invoice->total, 0) }}</td>
                    <td class="px-4 py-3">
                        @php
                            $bg = match($invoice->status) {
                                'paid'      => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                                'sent'      => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                                'cancelled' => 'bg-red-100 text-danger dark:bg-red-900/30 dark:text-red-400',
                                default     => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
                            };
                        @endphp
                        <span class="px-2 py-0.5 text-xs font-medium rounded-full {{ $bg }}">{{ ucfirst($invoice->status) }}</span>
                    </td>
                    <td class="px-4 py-3 text-xs text-textsecondary dark:text-gray-400">{{ $invoice->created_at->format('d M Y') }}</td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            <a href="{{ route('admin.invoices.show', $invoice) }}"
                               class="px-2.5 py-1 text-xs font-medium bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors">View</a>
                            <a href="{{ route('admin.invoices.print', $invoice) }}" target="_blank"
                               class="px-2.5 py-1 text-xs font-medium border border-border dark:border-gray-600 text-textsecondary dark:text-gray-400 hover:text-textprimary dark:hover:text-gray-200 rounded-lg transition-colors">Print</a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="px-6 py-12 text-center text-textsecondary dark:text-gray-400">No invoices found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($invoices->hasPages())
    <div class="px-6 py-4 border-t border-border dark:border-gray-700">
        {{ $invoices->links() }}
    </div>
    @endif
</x-admin.card>

@endsection
