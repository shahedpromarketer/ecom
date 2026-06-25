@extends('layouts.admin')

@section('title', 'Incomplete Orders')
@section('page-title', 'Incomplete Orders')

@section('breadcrumb')
    <span class="text-textsecondary">/</span>
    <a href="{{ route('admin.orders.index') }}" class="hover:text-primary dark:hover:text-accent transition-colors">Orders</a>
    <span class="text-textsecondary">/</span>
    <span class="text-textprimary font-medium dark:text-gray-200">Incomplete</span>
@endsection

@section('content')
@php
    $allOrders = $orders->getCollection();
    $totalAll  = $orders->total();
    $cFresh    = $allOrders->filter(fn($o) => $o->created_at->gt(now()->subHours(1)))->count();
    $cMid      = $allOrders->filter(fn($o) => $o->created_at->lte(now()->subHours(1)) && $o->created_at->gt(now()->subHours(24)))->count();
    $cOld      = $allOrders->filter(fn($o) => $o->created_at->lte(now()->subHours(24)))->count();
@endphp

<div class="space-y-5">

{{-- ── Stat Cards ─────────────────────────────────────────────── --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
    <div class="bg-white dark:bg-gray-800 border border-border dark:border-gray-700 rounded-2xl px-5 py-4 flex items-center gap-4">
        <div class="w-10 h-10 rounded-xl bg-gray-100 dark:bg-gray-700 flex items-center justify-center flex-shrink-0">
            <svg class="w-5 h-5 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
        </div>
        <div>
            <div class="text-2xl font-bold text-textprimary dark:text-white leading-none">{{ $totalAll }}</div>
            <div class="text-xs text-textsecondary dark:text-gray-400 mt-1">মোট Incomplete</div>
        </div>
    </div>
    <div class="bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-100 dark:border-emerald-800/30 rounded-2xl px-5 py-4 flex items-center gap-4">
        <div class="w-10 h-10 rounded-xl bg-emerald-100 dark:bg-emerald-800/40 flex items-center justify-center flex-shrink-0">
            <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div>
            <div class="text-2xl font-bold text-emerald-700 dark:text-emerald-400 leading-none">{{ $cFresh }}</div>
            <div class="text-xs text-emerald-600 dark:text-emerald-500 mt-1">গত ১ ঘণ্টায়</div>
        </div>
    </div>
    <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-100 dark:border-amber-800/30 rounded-2xl px-5 py-4 flex items-center gap-4">
        <div class="w-10 h-10 rounded-xl bg-amber-100 dark:bg-amber-800/40 flex items-center justify-center flex-shrink-0">
            <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
        </div>
        <div>
            <div class="text-2xl font-bold text-amber-700 dark:text-amber-400 leading-none">{{ $cMid }}</div>
            <div class="text-xs text-amber-600 dark:text-amber-500 mt-1">১–২৪ ঘণ্টা আগে</div>
        </div>
    </div>
    <div class="bg-red-50 dark:bg-red-900/20 border border-red-100 dark:border-red-800/30 rounded-2xl px-5 py-4 flex items-center gap-4">
        <div class="w-10 h-10 rounded-xl bg-red-100 dark:bg-red-800/40 flex items-center justify-center flex-shrink-0">
            <svg class="w-5 h-5 text-red-500 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
        </div>
        <div>
            <div class="text-2xl font-bold text-red-600 dark:text-red-400 leading-none">{{ $cOld }}</div>
            <div class="text-xs text-red-500 dark:text-red-400 mt-1">২৪+ ঘণ্টা আগে</div>
        </div>
    </div>
</div>

{{-- ── Filter Bar ──────────────────────────────────────────────── --}}
<form method="GET" action="{{ route('admin.orders.incomplete') }}"
      class="bg-white dark:bg-gray-800 border border-border dark:border-gray-700 rounded-2xl px-5 py-3.5 flex flex-wrap gap-3 items-center">
    <div class="relative flex-1 min-w-[200px]">
        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        <input type="text" name="search" value="{{ request('search') }}"
               placeholder="নাম, ফোন নম্বর বা Order ID..."
               class="w-full pl-9 pr-3 py-2.5 text-sm rounded-xl border border-border dark:border-gray-600 bg-white dark:bg-gray-700 text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary/50">
    </div>
    <select name="age" class="px-3 py-2.5 text-sm rounded-xl border border-border dark:border-gray-600 bg-white dark:bg-gray-700 text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30">
        <option value="">সব সময়</option>
        <option value="1"  {{ request('age') == '1'  ? 'selected' : '' }}>১ ঘণ্টার বেশি</option>
        <option value="3"  {{ request('age') == '3'  ? 'selected' : '' }}>৩ ঘণ্টার বেশি</option>
        <option value="24" {{ request('age') == '24' ? 'selected' : '' }}>২৪ ঘণ্টার বেশি</option>
    </select>
    <button type="submit"
            class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold bg-primary hover:bg-primary/90 text-white rounded-xl transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/></svg>
        Filter
    </button>
    @if(request('search') || request('age'))
    <a href="{{ route('admin.orders.incomplete') }}"
       class="inline-flex items-center gap-1.5 px-4 py-2.5 text-sm font-medium border border-border dark:border-gray-600 text-textsecondary dark:text-gray-400 hover:text-textprimary dark:hover:text-gray-200 rounded-xl transition-colors">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        Clear
    </a>
    @endif
    <span class="ml-auto text-xs text-textsecondary dark:text-gray-400 tabular-nums">{{ $orders->total() }} results</span>
</form>

{{-- ── Order Cards ─────────────────────────────────────────────── --}}
<div class="space-y-3">
    @forelse($orders as $order)
    @php
        $ageH = $order->created_at->diffInHours(now());
        $ageM = $order->created_at->diffInMinutes(now());
        $ageD = $order->created_at->diffInDays(now());

        if ($ageH < 1) {
            $urgency      = 'fresh';
            $rowBorder    = 'border-l-emerald-400';
            $ageBg        = 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300';
            $ageText      = $ageM . ' মিনিট আগে';
            $rowBg        = 'bg-white dark:bg-gray-800';
        } elseif ($ageH < 24) {
            $urgency      = 'mid';
            $rowBorder    = 'border-l-amber-400';
            $ageBg        = 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300';
            $ageText      = $ageH . ' ঘণ্টা আগে';
            $rowBg        = 'bg-amber-50/40 dark:bg-amber-900/10';
        } else {
            $urgency      = 'old';
            $rowBorder    = 'border-l-red-400';
            $ageBg        = 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300';
            $ageText      = $ageD . ' দিন আগে';
            $rowBg        = 'bg-red-50/40 dark:bg-red-900/10';
        }
        $waPhone = '880' . ltrim($order->shipping_phone ?? '', '0');
        $itemCount = $order->items->count();
    @endphp

    <div class="{{ $rowBg }} border border-border dark:border-gray-700 border-l-4 {{ $rowBorder }} rounded-2xl overflow-hidden hover:shadow-md transition-shadow">

        {{-- Top row --}}
        <div class="flex flex-col sm:flex-row sm:items-start gap-4 px-5 py-4">

            {{-- Left: Customer info --}}
            <div class="flex-1 min-w-0 space-y-2">

                {{-- Name + ID + age --}}
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-base font-bold text-textprimary dark:text-white leading-none">
                        {{ $order->shipping_name ?? 'N/A' }}
                    </span>
                    <span class="text-[11px] font-mono text-textsecondary dark:text-gray-400 bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded-md">#{{ $order->id }}</span>
                    <span class="text-[11px] font-semibold px-2 py-0.5 rounded-lg {{ $ageBg }}">{{ $ageText }}</span>
                </div>

                {{-- Phone + District --}}
                <div class="flex flex-wrap items-center gap-2">
                    <div class="flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.948V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 7V5z"/></svg>
                        <span class="text-sm font-mono font-semibold text-textprimary dark:text-white tracking-wide">{{ $order->shipping_phone ?? '—' }}</span>
                    </div>
                    @if($order->shipping_district)
                    <span class="text-gray-300 dark:text-gray-600">•</span>
                    <div class="flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span class="text-sm text-textsecondary dark:text-gray-400">{{ $order->shipping_district }}</span>
                    </div>
                    @endif
                    @if($order->shipping_address)
                    <span class="text-gray-300 dark:text-gray-600 hidden sm:inline">•</span>
                    <span class="text-xs text-textsecondary dark:text-gray-500 truncate max-w-[240px] hidden sm:inline">{{ $order->shipping_address }}</span>
                    @endif
                </div>

                {{-- Items --}}
                @if($itemCount > 0)
                <div class="flex flex-wrap gap-1.5 pt-0.5">
                    @foreach($order->items->take(4) as $item)
                    <div class="inline-flex items-center gap-1 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg px-2 py-1 text-xs">
                        <span class="font-bold text-primary dark:text-accent">{{ $item->qty }}×</span>
                        <span class="text-gray-700 dark:text-gray-300 max-w-[160px] truncate">{{ $item->product_name ?? 'Product' }}</span>
                    </div>
                    @endforeach
                    @if($itemCount > 4)
                    <div class="inline-flex items-center bg-gray-100 dark:bg-gray-700 rounded-lg px-2 py-1 text-xs text-textsecondary dark:text-gray-400">
                        +{{ $itemCount - 4 }} আরও
                    </div>
                    @endif
                </div>
                @endif
            </div>

            {{-- Right: Total + Actions --}}
            <div class="flex sm:flex-col items-center sm:items-end justify-between sm:justify-start gap-3 sm:gap-4 flex-shrink-0">

                {{-- Amount --}}
                <div class="text-right">
                    <div class="text-xl font-bold text-textprimary dark:text-white leading-none">৳{{ number_format($order->total ?? 0, 0) }}</div>
                    <div class="text-[11px] text-textsecondary dark:text-gray-500 mt-0.5">{{ $itemCount }} টি পণ্য</div>
                </div>

                {{-- Action buttons --}}
                <div class="flex items-center gap-2">
                    {{-- WhatsApp --}}
                    <a href="https://wa.me/{{ $waPhone }}" target="_blank"
                       class="inline-flex items-center gap-1.5 px-3 py-2 bg-green-500 hover:bg-green-600 text-white text-xs font-semibold rounded-xl transition-colors">
                        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                        <span class="hidden sm:inline">WhatsApp</span>
                    </a>
                    {{-- Call --}}
                    <a href="tel:{{ $order->shipping_phone }}"
                       class="inline-flex items-center gap-1.5 px-3 py-2 bg-blue-500 hover:bg-blue-600 text-white text-xs font-semibold rounded-xl transition-colors">
                        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.948V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 7V5z"/></svg>
                        <span class="hidden sm:inline">Call</span>
                    </a>
                    {{-- View --}}
                    <a href="{{ route('admin.orders.show', $order) }}"
                       class="w-9 h-9 flex items-center justify-center bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-500 dark:text-gray-400 rounded-xl transition-colors" title="Order দেখুন">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    </a>
                </div>
            </div>
        </div>

        {{-- Bottom: date + source --}}
        <div class="px-5 py-2 bg-gray-50/80 dark:bg-gray-700/30 border-t border-border/50 dark:border-gray-700/50 flex items-center justify-between">
            <span class="text-[11px] text-textsecondary dark:text-gray-500">
                {{ $order->created_at->format('d M Y, h:i A') }}
            </span>
            <span class="text-[11px] text-textsecondary dark:text-gray-500">
                {{ $order->source ?? 'WooCommerce' }}
            </span>
        </div>

    </div>
    @empty

    {{-- Empty state --}}
    <div class="bg-white dark:bg-gray-800 border border-border dark:border-gray-700 rounded-2xl px-6 py-20 text-center">
        <div class="w-16 h-16 mx-auto rounded-2xl bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center mb-4">
            <svg class="w-8 h-8 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <p class="text-base font-semibold text-gray-600 dark:text-gray-300">কোনো incomplete order নেই!</p>
        <p class="text-sm text-gray-400 dark:text-gray-500 mt-1.5">সব customers তাদের purchase সম্পন্ন করেছে।</p>
        <a href="{{ route('admin.orders.index') }}" class="inline-flex items-center gap-2 mt-5 px-4 py-2 text-sm font-medium bg-primary text-white rounded-xl hover:bg-primary/90 transition-colors">
            All Orders দেখুন
        </a>
    </div>

    @endforelse
</div>

{{-- Pagination --}}
@if($orders->hasPages())
<div class="flex justify-center pt-2">
    {{ $orders->links() }}
</div>
@endif

</div>
@endsection
