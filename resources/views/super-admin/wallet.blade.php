@extends('super-admin.layout')

@section('page-title', 'Wallet & recharges')

@php
    $card  = 'bg-white rounded-xl shadow-sm border border-gray-100';
    $input = 'w-full border border-gray-200 rounded-lg px-3 py-2 text-sm';
    $badge = ['pending' => 'bg-amber-100 text-amber-700', 'approved' => 'bg-green-100 text-green-700', 'rejected' => 'bg-red-100 text-red-700'];
@endphp

@section('content')
<div class="space-y-5 max-w-6xl">
    @if ($errors->any())<div class="p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">{{ $errors->first() }}</div>@endif

    {{-- Requests --}}
    <div class="{{ $card }}">
        <div class="flex flex-wrap items-center justify-between gap-2 px-4 py-3 border-b border-gray-100">
            <p class="font-semibold">Recharge requests @if ($pending)<span class="ml-1 px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 text-xs">{{ $pending }} waiting</span>@endif</p>
            <div class="flex gap-1 text-xs">
                @foreach (['pending' => 'Waiting', 'approved' => 'Approved', 'rejected' => 'Rejected', 'all' => 'All'] as $k => $l)
                    <a href="?status={{ $k }}" class="px-3 py-1.5 rounded-lg {{ $status === $k ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700' }}">{{ $l }}</a>
                @endforeach
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="ep-stack min-w-full text-sm">
                <thead><tr class="text-left text-xs text-gray-500 border-b border-gray-100">
                    <th class="p-3">Shop</th><th>Amount</th><th>Paid with</th><th>Transaction</th><th>Sent</th><th>Screenshot</th><th>Status</th><th class="pr-3">Action</th></tr></thead>
                <tbody>
                    @forelse ($requests as $r)
                        <tr class="border-b border-gray-100 align-top">
                            <td class="p-3 font-medium">{{ $r->tenant?->name }}<div class="text-xs text-gray-500">{{ $r->requester?->name }}</div></td>
                            <td class="font-bold">৳{{ number_format((float) $r->amount) }}</td>
                            <td>{{ \App\Models\WalletRechargeRequest::METHODS[$r->method] ?? $r->method }}<div class="text-xs text-gray-500">{{ $r->sender }}</div></td>
                            <td class="font-mono text-xs">{{ $r->transaction_id }}@if ($r->note)<div class="font-sans text-gray-500">{{ $r->note }}</div>@endif</td>
                            <td class="whitespace-nowrap text-xs">{{ $r->created_at->format('d M, h:i A') }}</td>
                            <td>@if ($r->screenshot_path)<a href="{{ route('super.wallet.screenshot', $r->id) }}" target="_blank" class="text-blue-600 text-xs underline">View</a>@endif</td>
                            <td><span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $badge[$r->status] ?? '' }}">{{ ucfirst($r->status) }}</span>
                                @if ($r->reviewer)<div class="text-xs text-gray-500">{{ $r->reviewer->name }}, {{ $r->reviewed_at?->format('d M') }}</div>@endif
                                @if ($r->reject_reason)<div class="text-xs text-red-600">{{ $r->reject_reason }}</div>@endif</td>
                            <td class="pr-3">
                                @if ($r->status === 'pending')
                                    <div class="flex flex-col gap-1.5 min-w-[180px]">
                                        <form method="POST" action="{{ route('super.wallet.approve', $r->id) }}" class="flex gap-1"
                                              onsubmit="return confirm('Add this money to {{ addslashes($r->tenant?->name) }}?')">@csrf
                                            <input type="number" name="amount" step="0.01" min="1" value="{{ (float) $r->amount }}" class="w-24 border border-gray-200 rounded-lg px-2 py-1 text-xs" title="Amount actually received">
                                            <button class="px-3 py-1 rounded-lg bg-green-600 text-white text-xs font-semibold">Approve</button>
                                        </form>
                                        <form method="POST" action="{{ route('super.wallet.reject', $r->id) }}" class="flex gap-1">@csrf
                                            <input name="reason" required maxlength="255" placeholder="Reason" class="w-24 border border-gray-200 rounded-lg px-2 py-1 text-xs">
                                            <button class="px-3 py-1 rounded-lg bg-red-100 text-red-700 text-xs font-semibold">Reject</button>
                                        </form>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="p-6 text-center text-gray-500">No requests here.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-3">{{ $requests->links() }}</div>
    </div>

    <div class="grid lg:grid-cols-2 gap-5">
        {{-- Shops --}}
        <div class="{{ $card }} p-4 space-y-3">
            <p class="font-semibold">Shops: balance and charge per order</p>
            @foreach ($tenants as $t)
                <form method="POST" action="{{ route('super.wallet.tenant', $t->id) }}" class="p-3 rounded-lg border border-gray-100 space-y-2">@csrf
                    <div class="flex justify-between text-sm">
                        <b>{{ $t->name }}</b>
                        <span class="{{ $t->walletLocked() ? 'text-red-600 font-bold' : '' }}">৳{{ number_format((float) $t->wallet_balance, 2) }}{{ $t->walletLocked() ? ' · locked' : '' }}</span>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                        <label class="text-xs text-gray-500">Per order (৳, 0 = free)
                            <input type="number" name="order_charge" step="0.01" min="0" value="{{ (float) $t->order_charge }}" class="{{ $input }}"></label>
                        <label class="text-xs text-gray-500">Charge
                            <select name="charge_on" class="{{ $input }}">
                                @foreach (\App\Services\Wallet::CHARGE_ON as $k => $l)<option value="{{ $k }}" @selected(($t->charge_on ?? 'confirmed') === $k)>{{ $l }}</option>@endforeach
                            </select></label>
                        <label class="text-xs text-gray-500">Add / take (৳)
                            <input type="number" name="adjust" step="0.01" placeholder="e.g. 100 or -50" class="{{ $input }}"></label>
                        <label class="text-xs text-gray-500">Note
                            <input name="note" maxlength="255" placeholder="Welcome credit" class="{{ $input }}"></label>
                    </div>
                    <button class="px-3 py-1.5 rounded-lg bg-blue-600 text-white text-xs font-semibold">Save</button>
                </form>
            @endforeach
        </div>

        <div class="space-y-5">
            {{-- Payment details shown to shops --}}
            <form method="POST" action="{{ route('super.wallet.payment-info') }}" class="{{ $card }} p-4 space-y-2">@csrf
                <p class="font-semibold">Where shops send money</p>
                <p class="text-xs text-gray-500">Shown on every shop's Wallet page.</p>
                <textarea name="payment_info" rows="5" maxlength="1000" class="{{ $input }}" placeholder="bKash (Personal): 01XXXXXXXXX&#10;Nagad: 01XXXXXXXXX&#10;Send money, then send the transaction ID and screenshot below.">{{ $paymentInfo }}</textarea>
                <button class="px-3 py-1.5 rounded-lg bg-blue-600 text-white text-xs font-semibold">Save</button>
            </form>

            <div class="{{ $card }} p-4">
                <p class="font-semibold mb-2">Latest recharges & adjustments</p>
                @forelse ($recent as $t)
                    <div class="flex justify-between text-xs py-1.5 border-b border-gray-100">
                        <span>{{ $tenants->firstWhere('id', $t->tenant_id)?->name }} · {{ $t->note }}</span>
                        <span class="{{ (float) $t->amount < 0 ? 'text-red-600' : 'text-green-700' }} font-semibold whitespace-nowrap">{{ (float) $t->amount < 0 ? '−' : '+' }}৳{{ number_format(abs((float) $t->amount), 2) }}</span>
                    </div>
                @empty
                    <p class="text-xs text-gray-500">Nothing yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
