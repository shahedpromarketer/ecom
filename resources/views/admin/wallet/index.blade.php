@extends('layouts.admin')

@section('title', 'Wallet')
@section('page-title', 'Wallet')

@section('breadcrumb')
    <span class="text-textsecondary">/</span>
    <span class="text-textprimary font-medium">Wallet</span>
@endsection

@php
    $card  = 'bg-white dark:bg-gray-800 rounded-2xl border border-border dark:border-gray-700 shadow-sm';
    $input = 'w-full px-3 py-2 text-sm rounded-xl border border-border dark:border-gray-600 bg-white dark:bg-gray-700/50';
    $fee = (float) $tenant->order_charge;
    $statusCls = ['pending' => 'bg-amber-100 text-amber-700', 'approved' => 'bg-green-100 text-green-700', 'rejected' => 'bg-red-100 text-red-700'];
@endphp

@section('content')
<div class="max-w-5xl space-y-5">
    @foreach (['success' => 'green', 'error' => 'red'] as $k => $c)
        @if (session($k))<div class="p-3 rounded-xl bg-{{ $c }}-50 text-{{ $c }}-800 border border-{{ $c }}-200 text-sm">{{ session($k) }}</div>@endif
    @endforeach
    @if ($errors->any())<div class="p-3 rounded-xl bg-red-50 text-red-800 border border-red-200 text-sm">{{ $errors->first() }}</div>@endif

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <div class="{{ $card }} p-4 col-span-2 lg:col-span-1 {{ $tenant->walletLocked() ? 'ring-2 ring-red-400' : '' }}">
            <p class="text-xs text-textsecondary">Balance</p>
            <p class="text-2xl font-bold {{ (float) $tenant->wallet_balance <= 0 ? 'text-red-600' : 'text-textprimary dark:text-white' }}">৳{{ number_format((float) $tenant->wallet_balance, 2) }}</p>
            @if ($fee > 0)<p class="text-xs text-textsecondary">≈ {{ number_format($tenant->ordersLeft()) }} more orders</p>@endif
        </div>
        <div class="{{ $card }} p-4"><p class="text-xs text-textsecondary">Per order</p>
            <p class="text-xl font-bold">{{ $fee > 0 ? '৳' . number_format($fee, 2) : 'Free' }}</p>
            @if ($fee > 0)<p class="text-xs text-textsecondary">{{ ($tenant->charge_on ?? 'confirmed') === 'created' ? 'Taken when an order arrives' : 'Taken when an order is confirmed' }}</p>@endif</div>
        <div class="{{ $card }} p-4"><p class="text-xs text-textsecondary">Charged orders this month</p>
            <p class="text-xl font-bold">{{ number_format($ordersMonth) }}</p></div>
        <div class="{{ $card }} p-4"><p class="text-xs text-textsecondary">Spent this month</p>
            <p class="text-xl font-bold">৳{{ number_format($spentMonth, 2) }}</p></div>
    </div>

    @if ($tenant->walletLocked())
        <div class="p-4 rounded-2xl bg-red-50 border border-red-200 text-sm text-red-800">
            <b>Your balance is empty.</b> New orders from your website are still saved, nothing is lost —
            but orders can't be opened, confirmed or sent to the courier until you recharge.
        </div>
    @endif

    <div class="grid lg:grid-cols-2 gap-4">
        <form method="POST" action="{{ route('admin.wallet.request') }}" enctype="multipart/form-data" class="{{ $card }} p-5 space-y-3">
            @csrf
            <p class="font-semibold text-textprimary dark:text-white">Recharge</p>
            @if ($paymentInfo)
                <div class="text-sm p-3 rounded-xl bg-gray-50 dark:bg-gray-900 whitespace-pre-line">{{ $paymentInfo }}</div>
            @endif
            <ol class="text-xs text-textsecondary space-y-1 list-decimal list-inside">
                <li>Send the money to the number above.</li>
                <li>Write the amount and upload a screenshot of the payment.</li>
                <li>We add it to your balance as soon as we check it.</li>
            </ol>
            <div class="grid grid-cols-2 gap-3">
                <label class="block"><span class="text-xs text-textsecondary">Amount (৳)</span>
                    <input type="number" name="amount" min="10" step="1" required value="{{ old('amount') }}" class="{{ $input }}"></label>
                <label class="block"><span class="text-xs text-textsecondary">Paid with</span>
                    <select name="method" class="{{ $input }}">
                        @foreach (\App\Models\WalletRechargeRequest::METHODS as $k => $l)<option value="{{ $k }}" @selected(old('method') === $k)>{{ $l }}</option>@endforeach
                    </select></label>
            </div>
            <label class="block"><span class="text-xs text-textsecondary">Payment screenshot</span>
                <input type="file" name="screenshot" accept="image/*" required class="{{ $input }}"></label>
            <label class="block"><span class="text-xs text-textsecondary">Note (optional)</span>
                <input name="note" maxlength="500" value="{{ old('note') }}" class="{{ $input }}"></label>
            <button class="w-full sm:w-auto px-5 py-2.5 rounded-xl bg-primary text-white text-sm font-semibold">Send request</button>
        </form>

        <div class="{{ $card }} p-5">
            <p class="font-semibold text-textprimary dark:text-white mb-2">Recharge requests</p>
            @forelse ($requests as $r)
                <div class="flex items-start justify-between gap-2 py-2 border-b border-border dark:border-gray-700 text-sm">
                    <div>
                        <b>৳{{ number_format((float) $r->amount) }}</b> · {{ \App\Models\WalletRechargeRequest::METHODS[$r->method] ?? $r->method }}
                        <span class="text-xs text-textsecondary">· {{ $r->created_at->format('d M, h:i A') }}</span>
                        @if ($r->status === 'rejected' && $r->reject_reason)<p class="text-xs text-red-600">{{ $r->reject_reason }}</p>@endif
                    </div>
                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $statusCls[$r->status] ?? '' }}">{{ ucfirst($r->status) }}</span>
                </div>
            @empty
                <p class="text-sm text-textsecondary">No requests yet.</p>
            @endforelse
        </div>
    </div>

    <div class="{{ $card }} overflow-x-auto">
        <table class="ep-stack w-full text-sm">
            <thead><tr class="text-left text-xs text-textsecondary border-b border-border dark:border-gray-700">
                <th class="p-3">Date</th><th>What</th><th class="text-right">Amount</th><th class="text-right pr-3">Balance</th></tr></thead>
            <tbody>
                @forelse ($transactions as $t)
                    <tr class="border-b border-border dark:border-gray-700">
                        <td class="p-3 whitespace-nowrap">{{ $t->created_at->format('d M Y, h:i A') }}</td>
                        <td>{{ ['recharge' => 'Recharge', 'order' => 'Order fee', 'adjustment' => 'Adjustment'][$t->type] ?? $t->type }}
                            <span class="text-xs text-textsecondary">{{ $t->note }}</span></td>
                        <td class="text-right font-semibold {{ (float) $t->amount < 0 ? 'text-red-600' : 'text-green-700' }}">{{ (float) $t->amount < 0 ? '−' : '+' }}৳{{ number_format(abs((float) $t->amount), 2) }}</td>
                        <td class="text-right pr-3">৳{{ number_format((float) $t->balance_after, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="p-6 text-center text-textsecondary">No wallet activity yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $transactions->links() }}
</div>
@endsection
