@extends('layouts.admin')

@section('title', 'Payments')
@section('page-title', 'Payment Management')

@section('breadcrumb')
    <span class="text-textsecondary">/</span>
    <span class="text-textprimary font-medium">Payments</span>
@endsection

@section('content')

@if(session('success'))
    <div class="mb-5 flex items-center gap-3 p-3.5 rounded-xl bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800">
        <svg class="w-4 h-4 text-green-600 dark:text-green-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/></svg>
        <p class="text-sm text-green-700 dark:text-green-400">{{ session('success') }}</p>
    </div>
@endif

{{-- Summary Cards --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-border dark:border-gray-700 shadow-sm p-4">
        <p class="text-xs text-textsecondary dark:text-gray-400 mb-1">Unpaid Orders</p>
        <p class="text-2xl font-bold text-textprimary dark:text-gray-100">{{ number_format($unpaidCount) }}</p>
        <p class="text-xs text-red-500 mt-0.5">৳{{ number_format($unpaidTotal, 0) }} pending</p>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-border dark:border-gray-700 shadow-sm p-4">
        <p class="text-xs text-textsecondary dark:text-gray-400 mb-1">COD Pending</p>
        <p class="text-2xl font-bold text-textprimary dark:text-gray-100">{{ number_format($codPending) }}</p>
        <p class="text-xs text-amber-500 mt-0.5">Cash on delivery</p>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-border dark:border-gray-700 shadow-sm p-4">
        <p class="text-xs text-textsecondary dark:text-gray-400 mb-1">Total Paid</p>
        <p class="text-2xl font-bold text-green-600 dark:text-green-400">৳{{ number_format($paidTotal, 0) }}</p>
        <p class="text-xs text-textsecondary dark:text-gray-400 mt-0.5">Collected</p>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-border dark:border-gray-700 shadow-sm p-4">
        <p class="text-xs text-textsecondary dark:text-gray-400 mb-1">Unpaid Amount</p>
        <p class="text-2xl font-bold text-red-600 dark:text-red-400">৳{{ number_format($unpaidTotal, 0) }}</p>
        <p class="text-xs text-textsecondary dark:text-gray-400 mt-0.5">To be collected</p>
    </div>
</div>

{{-- Payment Method Breakdown --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
    @php
        $methodColors = ['cod' => 'bg-amber-500', 'bkash' => 'bg-pink-500', 'nagad' => 'bg-orange-500', 'card' => 'bg-blue-500'];
    @endphp
    @foreach($summary as $method => $rows)
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-border dark:border-gray-700 shadow-sm p-4">
        <div class="flex items-center gap-2 mb-2">
            <span class="w-2.5 h-2.5 rounded-full {{ $methodColors[$method] ?? 'bg-gray-400' }}"></span>
            <span class="text-xs font-semibold text-textprimary dark:text-gray-200 uppercase">{{ $method }}</span>
        </div>
        @foreach($rows as $row)
        <div class="flex justify-between text-xs py-0.5">
            <span class="text-textsecondary dark:text-gray-400 capitalize">{{ $row->payment_status }}</span>
            <span class="font-medium text-textprimary dark:text-gray-200">{{ number_format($row->order_count) }}</span>
        </div>
        @endforeach
    </div>
    @endforeach
</div>

{{-- Filters + Table --}}
<div class="bg-white dark:bg-gray-800 rounded-2xl border border-border dark:border-gray-700 shadow-sm overflow-hidden">

    <div class="px-5 py-4 border-b border-border dark:border-gray-700 flex flex-wrap items-center gap-3">
        <h3 class="font-heading font-semibold text-textprimary dark:text-gray-100 flex-1">Orders
            <span class="ml-2 px-2 py-0.5 text-xs font-medium rounded-full bg-gray-100 dark:bg-gray-700 text-textsecondary dark:text-gray-400">{{ $orders->total() }}</span>
        </h3>

        <form method="GET" action="{{ route('admin.payments.index') }}" class="flex flex-wrap gap-2">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Order# or phone…"
                   class="px-3 py-1.5 text-xs rounded-xl border border-border dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 text-textprimary dark:text-gray-100 placeholder-textsecondary focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary w-40">
            <select name="payment_method" class="px-3 py-1.5 text-xs rounded-xl border border-border dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary">
                <option value="">All Methods</option>
                @foreach(['cod','bkash','nagad','card'] as $pm)
                    <option value="{{ $pm }}" {{ request('payment_method') === $pm ? 'selected' : '' }}>{{ strtoupper($pm) }}</option>
                @endforeach
            </select>
            <select name="payment_status" class="px-3 py-1.5 text-xs rounded-xl border border-border dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary">
                <option value="">All Statuses</option>
                <option value="unpaid"   {{ request('payment_status') === 'unpaid'   ? 'selected' : '' }}>Unpaid</option>
                <option value="paid"     {{ request('payment_status') === 'paid'     ? 'selected' : '' }}>Paid</option>
                <option value="failed"   {{ request('payment_status') === 'failed'   ? 'selected' : '' }}>Failed</option>
                <option value="refunded" {{ request('payment_status') === 'refunded' ? 'selected' : '' }}>Refunded</option>
            </select>
            <button type="submit" class="px-3 py-1.5 text-xs font-semibold bg-primary text-white rounded-xl hover:bg-primary/90 transition-colors">Filter</button>
            @if(request()->hasAny(['search','payment_method','payment_status']))
            <a href="{{ route('admin.payments.index') }}" class="px-3 py-1.5 text-xs font-medium border border-border dark:border-gray-600 text-textsecondary dark:text-gray-400 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">Clear</a>
            @endif
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-700/50 border-b border-border dark:border-gray-700">
                <tr>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Order</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Customer</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Amount</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Method</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Pay Status</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Order Status</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border dark:divide-gray-700">
                @forelse($orders as $order)
                @php
                    $payBg = match($order->payment_status) {
                        'paid'     => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                        'failed'   => 'bg-red-100 text-danger dark:bg-red-900/30 dark:text-red-400',
                        'refunded' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
                        default    => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
                    };
                @endphp
                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors">
                    <td class="px-4 py-3">
                        <a href="{{ route('admin.orders.show', $order) }}" class="font-mono font-semibold text-xs text-primary dark:text-blue-400 hover:underline">{{ $order->order_number }}</a>
                    </td>
                    <td class="px-4 py-3">
                        <p class="text-xs font-medium text-textprimary dark:text-gray-100">{{ $order->shipping_name }}</p>
                        <p class="text-xs text-textsecondary dark:text-gray-400">{{ $order->shipping_phone }}</p>
                    </td>
                    <td class="px-4 py-3 text-xs font-semibold text-textprimary dark:text-gray-100 whitespace-nowrap">৳{{ number_format($order->total, 0) }}</td>
                    <td class="px-4 py-3 text-xs font-semibold text-textprimary dark:text-gray-200 uppercase">{{ $order->payment_method }}</td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-0.5 text-[10px] font-medium rounded-full {{ $payBg }}">{{ ucfirst($order->payment_status) }}</span>
                    </td>
                    <td class="px-4 py-3">
                        <span class="text-xs text-textsecondary dark:text-gray-400">{{ ucwords(str_replace('_', ' ', $order->status)) }}</span>
                    </td>
                    <td class="px-4 py-3 text-xs text-textsecondary dark:text-gray-400 whitespace-nowrap">{{ $order->created_at->format('d M Y') }}</td>
                    <td class="px-4 py-3">
                        @if($order->payment_status !== 'paid')
                        <form method="POST" action="{{ route('admin.payments.markPaid', $order) }}">
                            @csrf @method('PATCH')
                            <button type="submit" class="px-2.5 py-1 text-[10px] font-semibold bg-green-500 hover:bg-green-600 text-white rounded-lg transition-colors whitespace-nowrap">Mark Paid</button>
                        </form>
                        @else
                        <form method="POST" action="{{ route('admin.payments.markUnpaid', $order) }}">
                            @csrf @method('PATCH')
                            <button type="submit" class="px-2.5 py-1 text-[10px] font-semibold border border-border dark:border-gray-600 text-textsecondary dark:text-gray-400 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors whitespace-nowrap">Mark Unpaid</button>
                        </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="px-6 py-12 text-center text-textsecondary dark:text-gray-400 text-sm">No orders found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($orders->hasPages())
    <div class="px-4 py-4 border-t border-border dark:border-gray-700">
        {{ $orders->links() }}
    </div>
    @endif
</div>

@endsection
