@extends('layouts.admin')

@section('title', 'Wallet history')
@section('page-title', 'Wallet')

@section('breadcrumb')
    <span class="text-textsecondary">/</span>
    <a href="{{ route('admin.wallet.index') }}" class="text-textsecondary hover:text-textprimary">Wallet</a>
    <span class="text-textsecondary">/</span>
    <span class="text-textprimary font-medium">History</span>
@endsection

@php $card = 'bg-white dark:bg-gray-800 rounded-2xl border border-border dark:border-gray-700 shadow-sm'; @endphp

@section('content')
<div class="max-w-5xl space-y-5">
    @include('admin.wallet._tabs', ['active' => 'history'])

    @include('partials.wallet-balance', ['w' => $w, 'compact' => true])

    @if ($day)
        {{-- One day: every order fee / recharge of that day --}}
        <div class="{{ $card }}">
            <div class="flex flex-wrap items-center justify-between gap-2 px-4 py-3 border-b border-border dark:border-gray-700">
                <div>
                    <a href="{{ route('admin.wallet.history', ['month' => $day->format('Y-m')]) }}" class="text-xs text-primary hover:underline">← All days</a>
                    <p class="font-semibold text-textprimary dark:text-white">{{ $day->format('d M Y, l') }}</p>
                </div>
                @php $fees = $transactions->getCollection()->where('type', 'order'); @endphp
                <p class="text-sm text-textsecondary"><b class="text-textprimary dark:text-white">{{ $fees->count() }}</b> orders ·
                    cost <b class="text-red-600">৳{{ number_format(abs((float) $fees->sum('amount')), 2) }}</b></p>
            </div>
            @include('partials.wallet-transactions', ['transactions' => $transactions, 'linkOrders' => true])
        </div>
        {{ $transactions->links() }}
    @else
        {{-- Day by day for a month --}}
        <div class="{{ $card }}">
            <div class="flex flex-wrap items-center justify-between gap-2 px-4 py-3 border-b border-border dark:border-gray-700">
                <p class="font-semibold text-textprimary dark:text-white">Day by day</p>
                <div class="flex items-center gap-1 text-sm">
                    <a href="{{ route('admin.wallet.history', ['month' => $month->copy()->subMonth()->format('Y-m')]) }}" class="px-2.5 py-1 rounded-lg bg-gray-100 dark:bg-gray-700 hover:bg-gray-200">‹</a>
                    <span class="px-3 font-semibold min-w-[110px] text-center">{{ $month->format('F Y') }}</span>
                    @if ($month->lt(now()->startOfMonth()))
                        <a href="{{ route('admin.wallet.history', ['month' => $month->copy()->addMonth()->format('Y-m')]) }}" class="px-2.5 py-1 rounded-lg bg-gray-100 dark:bg-gray-700 hover:bg-gray-200">›</a>
                    @endif
                </div>
            </div>
            <div class="grid grid-cols-3 divide-x divide-border dark:divide-gray-700 border-b border-border dark:border-gray-700 text-center">
                <div class="p-3"><p class="text-xs text-textsecondary">Orders charged</p><p class="text-lg font-bold">{{ number_format($days->sum('orders')) }}</p></div>
                <div class="p-3"><p class="text-xs text-textsecondary">Cost</p><p class="text-lg font-bold text-red-600">৳{{ number_format($days->sum('cost'), 2) }}</p></div>
                <div class="p-3"><p class="text-xs text-textsecondary">Added</p><p class="text-lg font-bold text-green-700">৳{{ number_format($days->sum('added'), 2) }}</p></div>
            </div>
            @include('partials.wallet-daily', ['days' => $days, 'dayUrl' => fn ($d) => route('admin.wallet.history', ['day' => $d->toDateString()])])
        </div>
    @endif
</div>
@endsection
