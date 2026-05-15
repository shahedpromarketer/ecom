@extends('layouts.admin')

@section('title', 'Incomplete Orders')
@section('page-title', 'Incomplete Orders')

@section('breadcrumb')
    <span class="text-textsecondary">/</span>
    <a href="{{ route('admin.orders.index') }}" class="hover:text-primary dark:hover:text-accent transition-colors">Orders</a>
    <span class="text-textsecondary">/</span>
    <span class="text-textprimary font-medium">Incomplete</span>
@endsection

@section('content')

<x-admin.card>
    <x-slot:header>
        <h3 class="font-heading font-semibold text-textprimary dark:text-gray-100">
            Incomplete Orders
            <span class="ml-2 px-2 py-0.5 text-xs font-medium rounded-full bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400">{{ $orders->total() }}</span>
        </h3>
        <a href="{{ route('admin.orders.index') }}"
           class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium border border-border dark:border-gray-600 text-textsecondary dark:text-gray-400 hover:text-textprimary dark:hover:text-gray-200 rounded-lg transition-colors">
            All Orders
        </a>
    </x-slot:header>

    <form method="GET" action="{{ route('admin.orders.incomplete') }}"
          class="flex flex-wrap gap-3 px-6 py-4 border-b border-border dark:border-gray-700">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search order# or phone…"
               class="flex-1 min-w-[180px] px-3 py-2 text-sm rounded-lg border border-border dark:border-gray-600 bg-white dark:bg-gray-700 text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-accent/40">
        <select name="status" class="px-3 py-2 text-sm rounded-lg border border-border dark:border-gray-600 bg-white dark:bg-gray-700 text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-accent/40">
            <option value="">All Incomplete</option>
            <option value="pending"    {{ request('status') === 'pending'    ? 'selected' : '' }}>Pending</option>
            <option value="confirmed"  {{ request('status') === 'confirmed'  ? 'selected' : '' }}>Confirmed</option>
            <option value="processing" {{ request('status') === 'processing' ? 'selected' : '' }}>Processing</option>
        </select>
        <button type="submit" class="px-4 py-2 text-sm font-semibold bg-primary hover:bg-primary/90 text-white rounded-lg transition-colors">Filter</button>
        <a href="{{ route('admin.orders.incomplete') }}" class="px-4 py-2 text-sm font-medium border border-border dark:border-gray-600 text-textsecondary dark:text-gray-400 hover:text-textprimary dark:hover:text-gray-200 rounded-lg transition-colors">Clear</a>
    </form>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="border-b border-border dark:border-gray-700 bg-muted/50 dark:bg-gray-700/50">
                <tr>
                    <th class="text-left px-6 py-3 font-medium text-textsecondary dark:text-gray-400 text-xs uppercase tracking-wide">Order</th>
                    <th class="text-left px-4 py-3 font-medium text-textsecondary dark:text-gray-400 text-xs uppercase tracking-wide">Customer</th>
                    <th class="text-left px-4 py-3 font-medium text-textsecondary dark:text-gray-400 text-xs uppercase tracking-wide">Items</th>
                    <th class="text-left px-4 py-3 font-medium text-textsecondary dark:text-gray-400 text-xs uppercase tracking-wide">Total</th>
                    <th class="text-left px-4 py-3 font-medium text-textsecondary dark:text-gray-400 text-xs uppercase tracking-wide">Status</th>
                    <th class="text-left px-4 py-3 font-medium text-textsecondary dark:text-gray-400 text-xs uppercase tracking-wide">Age</th>
                    <th class="text-left px-4 py-3 font-medium text-textsecondary dark:text-gray-400 text-xs uppercase tracking-wide">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border dark:divide-gray-700">
                @forelse($orders as $order)
                @php $isOld = $order->created_at->lt(now()->subHours(24)); @endphp
                <tr class="{{ $isOld ? 'bg-red-50/30 dark:bg-red-900/10' : 'hover:bg-muted/30 dark:hover:bg-gray-700/30' }} transition-colors">
                    <td class="px-6 py-3">
                        <div class="flex items-center gap-2">
                            @if($isOld)
                                <span class="w-2 h-2 rounded-full bg-red-500 flex-shrink-0"></span>
                            @endif
                            <span class="font-mono font-semibold text-textprimary dark:text-gray-100">{{ $order->order_number }}</span>
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        <p class="font-medium text-textprimary dark:text-gray-100">{{ $order->shipping_name }}</p>
                        <p class="text-xs text-textsecondary dark:text-gray-400">{{ $order->shipping_phone }}</p>
                    </td>
                    <td class="px-4 py-3 text-textsecondary dark:text-gray-400">{{ $order->items->count() }}</td>
                    <td class="px-4 py-3 font-semibold text-textprimary dark:text-gray-100">৳{{ number_format($order->total, 0) }}</td>
                    <td class="px-4 py-3">
                        @php
                            $sBg = match($order->status) {
                                'confirmed'  => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                                'processing' => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400',
                                default      => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
                            };
                        @endphp
                        <span class="px-2 py-0.5 text-xs font-medium rounded-full {{ $sBg }}">{{ ucfirst($order->status) }}</span>
                    </td>
                    <td class="px-4 py-3">
                        <span class="text-xs {{ $isOld ? 'text-danger font-semibold' : 'text-textsecondary dark:text-gray-400' }}">
                            {{ $order->created_at->diffForHumans() }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            <a href="{{ route('admin.orders.show', $order) }}"
                               class="px-2.5 py-1 text-xs font-medium bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors">View</a>
                            <form method="POST" action="{{ route('admin.orders.updateStatus', $order) }}" class="inline">
                                @csrf @method('PATCH')
                                <select name="status" onchange="this.form.submit()"
                                        class="text-xs px-2 py-1 rounded-lg border border-border dark:border-gray-600 bg-white dark:bg-gray-700 text-textprimary dark:text-gray-100 focus:outline-none">
                                    @foreach(['pending','confirmed','processing','shipped','delivered','cancelled'] as $s)
                                        <option value="{{ $s }}" {{ $order->status === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                                    @endforeach
                                </select>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="px-6 py-12 text-center text-textsecondary dark:text-gray-400">No incomplete orders. All caught up!</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($orders->hasPages())
    <div class="px-6 py-4 border-t border-border dark:border-gray-700">
        {{ $orders->links() }}
    </div>
    @endif
</x-admin.card>

@endsection
