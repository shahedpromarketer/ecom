@extends('super-admin.layout')

@section('page-title', 'Wallet & recharges')

@php
    $card  = 'bg-white rounded-xl shadow-sm border border-gray-100';
    $input = 'w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-400';
    $badge = ['pending' => 'bg-amber-100 text-amber-700', 'approved' => 'bg-green-100 text-green-700', 'rejected' => 'bg-red-100 text-red-700'];
    $paying = $tenants->filter(fn ($t) => (float) $t->order_charge > 0);
    $locked = $tenants->filter(fn ($t) => $t->walletLocked())->count();
@endphp

@section('content')
<div class="space-y-5 max-w-6xl">
    @if ($errors->any())<div class="p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">{{ $errors->first() }}</div>@endif

    {{-- Summary --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <div class="{{ $card }} p-4 {{ $pending ? 'ring-2 ring-amber-300' : '' }}">
            <p class="text-xs text-gray-500">Waiting requests</p>
            <p class="text-2xl font-bold {{ $pending ? 'text-amber-600' : '' }}">{{ $pending }}</p>
        </div>
        <div class="{{ $card }} p-4">
            <p class="text-xs text-gray-500">Paying shops</p>
            <p class="text-2xl font-bold">{{ $paying->count() }} <span class="text-sm font-normal text-gray-400">/ {{ $tenants->count() }}</span></p>
            @if ($locked)<p class="text-xs text-red-600">{{ $locked }} locked (empty wallet)</p>@endif
        </div>
        <div class="{{ $card }} p-4">
            <p class="text-xs text-gray-500">Recharged this month</p>
            <p class="text-2xl font-bold text-green-700">৳{{ number_format($rechargedMonth, 2) }}</p>
        </div>
        <div class="{{ $card }} p-4">
            <p class="text-xs text-gray-500">Order fees this month</p>
            <p class="text-2xl font-bold">৳{{ number_format($feesMonth, 2) }}</p>
        </div>
    </div>

    {{-- Requests --}}
    <div class="{{ $card }}">
        <div class="flex flex-wrap items-center justify-between gap-2 px-4 py-3 border-b border-gray-100">
            <p class="font-semibold">Recharge requests</p>
            <div class="flex gap-1 text-xs">
                @foreach (['pending' => 'Waiting', 'approved' => 'Approved', 'rejected' => 'Rejected', 'all' => 'All'] as $k => $l)
                    <a href="?status={{ $k }}" class="px-3 py-1.5 rounded-lg {{ $status === $k ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">{{ $l }}</a>
                @endforeach
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="ep-stack min-w-full text-sm">
                <thead><tr class="text-left text-xs text-gray-500 border-b border-gray-100">
                    <th class="p-3">Shop</th><th class="p-3">Amount</th><th class="p-3">Paid with</th><th class="p-3">Screenshot</th><th class="p-3">Sent</th><th class="p-3">Status</th><th class="p-3">Action</th></tr></thead>
                <tbody>
                    @forelse ($requests as $r)
                        <tr class="border-b border-gray-100 align-top">
                            <td class="p-3 font-medium">{{ $r->tenant?->name }}<div class="text-xs text-gray-500">{{ $r->requester?->name }}</div>
                                @if ($r->note)<div class="text-xs text-gray-500 font-normal mt-0.5">“{{ $r->note }}”</div>@endif</td>
                            <td class="p-3 font-bold">৳{{ number_format((float) $r->amount) }}</td>
                            <td class="p-3">{{ \App\Models\WalletRechargeRequest::METHODS[$r->method] ?? $r->method }}
                                @if ($r->transaction_id)<div class="text-xs text-gray-500 font-mono">{{ $r->transaction_id }}</div>@endif</td>
                            <td class="p-3">@if ($r->screenshot_path)
                                <a href="{{ route('super.wallet.screenshot', $r->id) }}" target="_blank" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-blue-50 text-blue-700 text-xs font-semibold hover:bg-blue-100">View screenshot</a>
                            @endif</td>
                            <td class="p-3 whitespace-nowrap text-xs">{{ $r->created_at->format('d M, h:i A') }}</td>
                            <td class="p-3"><span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $badge[$r->status] ?? '' }}">{{ ucfirst($r->status) }}</span>
                                @if ($r->reviewer)<div class="text-xs text-gray-500">{{ $r->reviewer->name }}, {{ $r->reviewed_at?->format('d M') }}</div>@endif
                                @if ($r->reject_reason)<div class="text-xs text-red-600">{{ $r->reject_reason }}</div>@endif</td>
                            <td class="p-3">
                                @if ($r->status === 'pending')
                                    <div class="flex flex-col gap-1.5 min-w-[200px]">
                                        <form method="POST" action="{{ route('super.wallet.approve', $r->id) }}" class="flex gap-1"
                                              onsubmit="return confirm('Add this money to {{ addslashes($r->tenant?->name) }}?')">@csrf
                                            <input type="number" name="amount" step="0.01" min="1" value="{{ (float) $r->amount }}" class="w-24 border border-gray-200 rounded-lg px-2 py-1 text-xs" title="Amount actually received">
                                            <button class="flex-1 px-3 py-1 rounded-lg bg-green-600 text-white text-xs font-semibold hover:bg-green-700">Approve</button>
                                        </form>
                                        <form method="POST" action="{{ route('super.wallet.reject', $r->id) }}" class="flex gap-1">@csrf
                                            <input name="reason" required maxlength="255" placeholder="Reason" class="w-24 border border-gray-200 rounded-lg px-2 py-1 text-xs">
                                            <button class="flex-1 px-3 py-1 rounded-lg bg-red-100 text-red-700 text-xs font-semibold hover:bg-red-200">Reject</button>
                                        </form>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="p-6 text-center text-gray-500">No requests here.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($requests->hasPages())<div class="p-3">{{ $requests->links() }}</div>@endif
    </div>

    {{-- Shops --}}
    <div class="{{ $card }}">
        <div class="px-4 py-3 border-b border-gray-100">
            <p class="font-semibold">Shops: balance and charge per order</p>
            <p class="text-xs text-gray-500">Set each shop's fee (0 = free) and when it is taken. To add or take money by hand, write the amount (e.g. 100 or -50) with a note.</p>
        </div>
        <div class="divide-y divide-gray-100">
            @foreach ($tenants as $t)
                @php $fee = (float) $t->order_charge; $sw = \App\Services\WalletStats::summary($t); @endphp
                <form method="POST" action="{{ route('super.wallet.tenant', $t->id) }}" class="p-4 grid lg:grid-cols-3 gap-4">@csrf
                    <div class="space-y-2">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="w-9 h-9 rounded-lg bg-blue-50 text-blue-700 font-bold flex items-center justify-center shrink-0">{{ mb_strtoupper(mb_substr($t->name, 0, 1)) }}</span>
                            <div class="min-w-0">
                                <a href="{{ route('super.tenants.show', $t->id) }}#wallet" class="font-semibold truncate hover:underline block">{{ $t->name }}</a>
                                <a href="{{ route('super.wallet.history', $t->id) }}" class="text-xs font-medium text-blue-600 hover:underline">History →</a>
                            </div>
                        </div>
                        @include('partials.wallet-balance', ['w' => $sw, 'compact' => true])
                    </div>
                    <div class="lg:col-span-2 space-y-3">
                    <div class="grid grid-cols-3 gap-2 text-center">
                        @foreach (['today' => 'Today', 'month' => 'This month', 'all' => 'All time'] as $k => $l)
                            <div class="rounded-lg bg-gray-50 p-2">
                                <p class="text-[11px] text-gray-500">{{ $l }}</p>
                                <p class="font-bold">৳{{ number_format($sw[$k]['cost'], 2) }}</p>
                                <p class="text-[11px] text-gray-500">{{ number_format($sw[$k]['orders']) }} orders</p>
                            </div>
                        @endforeach
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 items-end">
                        <label class="block text-xs text-gray-500">Fee per order (৳)
                            <input type="number" name="order_charge" step="0.01" min="0" value="{{ $fee }}" class="{{ $input }} mt-1"></label>
                        <label class="block text-xs text-gray-500">Take the fee
                            <select name="charge_on" class="{{ $input }} mt-1">
                                @foreach (\App\Services\Wallet::CHARGE_ON as $k => $l)<option value="{{ $k }}" @selected(($t->charge_on ?? 'confirmed') === $k)>{{ $l }}</option>@endforeach
                            </select></label>
                        <label class="block text-xs text-gray-500">Add / take (৳)
                            <input type="number" name="adjust" step="0.01" placeholder="100 or -50" class="{{ $input }} mt-1"></label>
                        <label class="block text-xs text-gray-500">Note
                            <input name="note" maxlength="255" placeholder="e.g. Welcome credit" class="{{ $input }} mt-1"></label>
                        <button class="sm:col-span-2 px-5 py-2 rounded-lg bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700">Save</button>
                    </div>
                    </div>
                </form>
            @endforeach
        </div>
    </div>

    <div class="grid lg:grid-cols-2 gap-5">
        {{-- Payment details shown to shops --}}
        <form method="POST" action="{{ route('super.wallet.payment-info') }}" class="{{ $card }} p-4 space-y-2">@csrf
            <p class="font-semibold">Where shops send money</p>
            <p class="text-xs text-gray-500">Shown on every shop's Wallet page.</p>
            <textarea name="payment_info" rows="5" maxlength="1000" class="{{ $input }}" placeholder="bKash (Personal): 01XXXXXXXXX&#10;Nagad: 01XXXXXXXXX&#10;Send money, then upload the payment screenshot below.">{{ $paymentInfo }}</textarea>
            <button class="px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700">Save</button>
        </form>

        <div class="{{ $card }} p-4">
            <p class="font-semibold mb-2">Latest recharges & adjustments</p>
            @forelse ($recent as $t)
                <div class="flex justify-between gap-2 text-xs py-1.5 border-b border-gray-100">
                    <span class="min-w-0"><b>{{ $tenants->firstWhere('id', $t->tenant_id)?->name }}</b> · {{ $t->note }}
                        <span class="text-gray-400">· {{ $t->created_at->format('d M') }}</span></span>
                    <span class="{{ (float) $t->amount < 0 ? 'text-red-600' : 'text-green-700' }} font-semibold whitespace-nowrap">{{ (float) $t->amount < 0 ? '−' : '+' }}৳{{ number_format(abs((float) $t->amount), 2) }}</span>
                </div>
            @empty
                <p class="text-xs text-gray-500">Nothing yet.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
