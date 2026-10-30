@extends('super-admin.layout')

@section('page-title', $tenant->name . ' — wallet')

@php
    $card = 'bg-white rounded-xl shadow-sm border border-gray-100';
    $fee  = (float) $tenant->order_charge;
@endphp

@section('content')
<div class="space-y-5 max-w-6xl">
    <a href="{{ route('super.wallet') }}" class="text-sm text-blue-600 hover:underline">← Wallet & recharges</a>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <div class="{{ $card }} p-4 {{ $tenant->walletLocked() ? 'ring-2 ring-red-300' : '' }}">
            <p class="text-xs text-gray-500">Balance</p>
            <p class="text-2xl font-bold {{ (float) $tenant->wallet_balance <= 0 ? 'text-red-600' : 'text-green-700' }}">৳{{ number_format((float) $tenant->wallet_balance, 2) }}</p>
        </div>
        <div class="{{ $card }} p-4">
            <p class="text-xs text-gray-500">Fee per order</p>
            <p class="text-2xl font-bold">{{ $fee > 0 ? '৳' . number_format($fee, 2) : 'Free' }}</p>
            @if ($fee > 0)<p class="text-xs text-gray-500">{{ \App\Services\Wallet::CHARGE_ON[$tenant->charge_on ?? 'confirmed'] ?? '' }}</p>@endif
        </div>
        <div class="{{ $card }} p-4">
            <p class="text-xs text-gray-500">This month</p>
            <p class="text-2xl font-bold">৳{{ number_format($feesMonth, 2) }}</p>
            <p class="text-xs text-gray-500">{{ number_format($ordersMonth) }} orders charged</p>
        </div>
        <div class="{{ $card }} p-4">
            <p class="text-xs text-gray-500">All-time fees</p>
            <p class="text-2xl font-bold">৳{{ number_format($feesAll, 2) }}</p>
        </div>
    </div>

    <div class="{{ $card }}">
        <div class="flex flex-wrap items-center justify-between gap-2 px-4 py-3 border-b border-gray-100">
            <p class="font-semibold">History</p>
            <div class="flex gap-1 text-xs">
                @foreach ([null => 'All', 'order' => 'Order fees', 'recharge' => 'Recharges', 'adjustment' => 'Adjustments'] as $k => $l)
                    <a href="?{{ $k ? 'type=' . $k : '' }}" class="px-3 py-1.5 rounded-lg {{ $type === ($k ?: null) ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">{{ $l }}</a>
                @endforeach
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="ep-stack min-w-full text-sm">
                <thead><tr class="text-left text-xs text-gray-500 border-b border-gray-100">
                    <th class="p-3">Date</th><th class="p-3">What</th><th class="p-3">Order</th><th class="p-3">Order status</th><th class="p-3 text-right">Amount</th><th class="p-3 text-right">Balance</th></tr></thead>
                <tbody>
                    @forelse ($transactions as $t)
                        <tr class="border-b border-gray-100">
                            <td class="p-3 whitespace-nowrap">{{ $t->created_at->format('d M Y, h:i A') }}</td>
                            <td class="p-3">{{ ['recharge' => 'Recharge', 'order' => 'Order fee', 'adjustment' => 'Adjustment'][$t->type] ?? $t->type }}
                                @if ($t->type !== 'order' && $t->note)<div class="text-xs text-gray-500">{{ $t->note }}</div>@endif</td>
                            <td class="p-3 font-mono text-xs">{{ $t->type === 'order' ? ($t->order?->order_number ?? str_replace('Order #', '', (string) $t->note)) : '' }}</td>
                            <td class="p-3">@if ($t->type === 'order')
                                @if ($t->order)<span class="px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100">{{ ucwords(str_replace('_', ' ', $t->order->status)) }}</span>
                                @else<span class="text-xs text-gray-400">Deleted</span>@endif
                            @endif</td>
                            <td class="p-3 text-right font-semibold {{ (float) $t->amount < 0 ? 'text-red-600' : 'text-green-700' }}">{{ (float) $t->amount < 0 ? '−' : '+' }}৳{{ number_format(abs((float) $t->amount), 2) }}</td>
                            <td class="p-3 text-right">৳{{ number_format((float) $t->balance_after, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="p-6 text-center text-gray-500">Nothing yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($transactions->hasPages())<div class="p-3">{{ $transactions->links() }}</div>@endif
    </div>
</div>
@endsection
