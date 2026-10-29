@extends('layouts.admin')

@section('title', 'Customers')
@section('page-title', 'Customers')

@section('breadcrumb')
    <span class="text-textsecondary">/</span>
    <span class="text-textprimary font-medium">Customers</span>
@endsection

@php
    $input = 'px-3 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700/50 text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all';
    $tagColors = ['new' => 'bg-blue-50 text-blue-700', 'loyal' => 'bg-green-50 text-green-700', 'vip' => 'bg-purple-50 text-purple-700', 'fraud' => 'bg-red-50 text-red-700'];
    $rateColor = fn ($r) => $r === null ? 'text-gray-400' : ($r >= 80 ? 'text-green-600' : ($r >= 50 ? 'text-amber-600' : 'text-red-600'));
@endphp

@section('content')

{{-- Stats --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
    @foreach([
        ['Total customers', $stats['total'], route('admin.customers.index')],
        ['Repeat customers', $stats['repeat'], route('admin.customers.index', ['type' => 'repeat'])],
        ['New (last 30 days)', $stats['new_30d'], route('admin.customers.index', ['type' => 'new'])],
        ['Blocked', $stats['blocked'], route('admin.customers.index', ['type' => 'blocked'])],
    ] as [$label, $value, $href])
        <a href="{{ $href }}" class="block p-4 rounded-2xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 hover:border-primary/40 transition-colors">
            <p class="text-xs text-textsecondary dark:text-gray-400">{{ $label }}</p>
            <p class="mt-1 text-2xl font-bold text-textprimary dark:text-white">{{ number_format($value) }}</p>
        </a>
    @endforeach
</div>

{{-- Filters --}}
<form method="GET" class="mb-5 p-4 rounded-2xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Name or phone…" class="{{ $input }} lg:col-span-2">

        <select name="type" class="{{ $input }}">
            <option value="">All customers</option>
            <option value="repeat" @selected(request('type') === 'repeat')>Repeat (2+ orders)</option>
            <option value="new" @selected(request('type') === 'new')>New (1 order)</option>
            <option value="never_delivered" @selected(request('type') === 'never_delivered')>Never delivered</option>
            <option value="blocked" @selected(request('type') === 'blocked')>Blocked</option>
        </select>

        <select name="success" class="{{ $input }}">
            <option value="">Any success rate</option>
            <option value="high" @selected(request('success') === 'high')>80% or more</option>
            <option value="mid" @selected(request('success') === 'mid')>50% – 79%</option>
            <option value="low" @selected(request('success') === 'low')>Below 50%</option>
            <option value="none" @selected(request('success') === 'none')>No finished orders yet</option>
        </select>

        <select name="tag" class="{{ $input }}">
            <option value="">Any tag</option>
            @foreach(\App\Models\Customer::TAGS as $key => $label)
                <option value="{{ $key }}" @selected(request('tag') === $key)>{{ $label }}</option>
            @endforeach
            <option value="none" @selected(request('tag') === 'none')>No tag</option>
        </select>

        <select name="district" class="{{ $input }}">
            <option value="">Any district</option>
            @foreach($districts as $d)
                <option value="{{ $d }}" @selected(request('district') === $d)>{{ $d }}</option>
            @endforeach
        </select>
    </div>
    <div class="mt-3 flex flex-wrap items-center gap-2">
        <select name="sort" class="{{ $input }}">
            <option value="">Sort: last order</option>
            <option value="orders" @selected(request('sort') === 'orders')>Sort: most orders</option>
            <option value="spent" @selected(request('sort') === 'spent')>Sort: most spent</option>
            <option value="name" @selected(request('sort') === 'name')>Sort: name</option>
        </select>
        <button type="submit" class="px-5 py-2.5 text-sm font-semibold bg-primary text-white rounded-xl hover:bg-primary/90">Filter</button>
        @if(request()->query())
            <a href="{{ route('admin.customers.index') }}" class="px-4 py-2.5 text-sm text-textsecondary hover:text-textprimary">Clear</a>
        @endif
        <span class="ml-auto text-xs text-textsecondary">{{ $customers->total() }} customers</span>
    </div>
</form>

{{-- Table --}}
<div class="rounded-2xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                <tr class="text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                    <th class="px-5 py-3">Customer</th>
                    <th class="px-4 py-3 text-center">Orders</th>
                    <th class="px-4 py-3 text-center">Delivered</th>
                    <th class="px-4 py-3 text-center">Cancel / Return</th>
                    <th class="px-4 py-3 text-center">Success</th>
                    <th class="px-4 py-3 text-right">Spent</th>
                    <th class="px-4 py-3">Last order</th>
                    <th class="px-4 py-3">Tag</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse($customers as $c)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 cursor-pointer" onclick="window.location='{{ route('admin.customers.show', $c) }}'">
                        <td class="px-5 py-3">
                            <a href="{{ route('admin.customers.show', $c) }}" class="font-medium text-textprimary dark:text-white hover:text-primary">{{ $c->name ?: 'Unknown' }}</a>
                            <div class="flex items-center gap-2 mt-0.5">
                                <span class="font-mono text-xs text-textsecondary">{{ $c->phone }}</span>
                                @if($c->is_blocked)<span class="px-1.5 py-0.5 text-[10px] font-semibold rounded bg-red-100 text-red-700">BLOCKED</span>@endif
                                @if($c->notes_count)<span class="text-[10px] text-textsecondary">· {{ $c->notes_count }} note{{ $c->notes_count > 1 ? 's' : '' }}</span>@endif
                            </div>
                        </td>
                        <td class="px-4 py-3 text-center font-semibold">{{ $c->total_orders }}</td>
                        <td class="px-4 py-3 text-center text-green-600">{{ $c->delivered_orders }}</td>
                        <td class="px-4 py-3 text-center text-red-500">{{ $c->cancelled_orders }} / {{ $c->returned_orders }}</td>
                        <td class="px-4 py-3 text-center font-semibold {{ $rateColor($c->success_rate) }}">{{ $c->success_rate === null ? '—' : $c->success_rate . '%' }}</td>
                        <td class="px-4 py-3 text-right">৳{{ number_format($c->total_spent) }}</td>
                        <td class="px-4 py-3 text-xs text-textsecondary">{{ $c->last_order_at?->diffForHumans() ?? '—' }}</td>
                        <td class="px-4 py-3">
                            @if($c->tag)
                                <span class="px-2 py-0.5 text-xs font-medium rounded-full {{ $tagColors[$c->tag] ?? 'bg-gray-100 text-gray-600' }}">{{ \App\Models\Customer::TAGS[$c->tag] ?? $c->tag }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-5 py-12 text-center text-textsecondary">No customers found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($customers->hasPages())
        <div class="px-5 py-3 border-t border-gray-100 dark:border-gray-700">{{ $customers->links() }}</div>
    @endif
</div>

@endsection
