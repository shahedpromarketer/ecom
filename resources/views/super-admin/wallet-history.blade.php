@extends('super-admin.layout')

@section('page-title', $tenant->name . ' — wallet')

@php
    $card = 'bg-white rounded-xl shadow-sm border border-gray-100';
    $fee  = (float) $tenant->order_charge;
@endphp

@section('content')
<div class="space-y-5 max-w-6xl">
    <div class="flex gap-4 text-sm"><a href="{{ route('super.wallet') }}" class="text-blue-600 hover:underline">← Wallet & recharges</a><a href="{{ route('super.tenants.show', $tenant->id) }}#wallet" class="text-blue-600 hover:underline">Shop details</a></div>

    @include('partials.wallet-balance', ['w' => $w])
    @include('partials.wallet-spend', ['w' => $w])

    <div class="{{ $card }}">
        <div class="flex flex-wrap items-center justify-between gap-2 px-4 py-3 border-b border-gray-100">
            <p class="font-semibold">History</p>
            <div class="flex gap-1 text-xs">
                @foreach ([null => 'All', 'order' => 'Order fees', 'recharge' => 'Recharges', 'adjustment' => 'Adjustments'] as $k => $l)
                    <a href="?{{ $k ? 'type=' . $k : '' }}" class="px-3 py-1.5 rounded-lg {{ $type === ($k ?: null) ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">{{ $l }}</a>
                @endforeach
            </div>
        </div>
        @include('partials.wallet-transactions', ['transactions' => $transactions])
        @if ($transactions->hasPages())<div class="p-3">{{ $transactions->links() }}</div>@endif
    </div>
</div>
@endsection
