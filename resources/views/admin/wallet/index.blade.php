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
    $statusCls = ['pending' => 'bg-amber-100 text-amber-700', 'approved' => 'bg-green-100 text-green-700', 'rejected' => 'bg-red-100 text-red-700'];
@endphp

@section('content')
<div class="max-w-5xl space-y-5">
    @foreach (['success' => 'green', 'error' => 'red'] as $k => $c)
        @if (session($k))<div class="p-3 rounded-xl bg-{{ $c }}-50 text-{{ $c }}-800 border border-{{ $c }}-200 text-sm">{{ session($k) }}</div>@endif
    @endforeach
    @if ($errors->any())<div class="p-3 rounded-xl bg-red-50 text-red-800 border border-red-200 text-sm">{{ $errors->first() }}</div>@endif

    @include('admin.wallet._tabs', ['active' => 'overview'])

    @include('partials.wallet-balance', ['w' => $w])

    @if ($tenant->walletLocked())
        <div class="p-4 rounded-2xl bg-red-50 border border-red-200 text-sm text-red-800">
            <b>Your balance is empty.</b> New orders from your website are still saved, nothing is lost —
            but orders can't be opened, confirmed or sent to the courier until you recharge.
        </div>
    @endif

    {{-- What orders cost --}}
    <div>
        <p class="text-sm font-semibold text-textprimary dark:text-white mb-2">What your orders cost</p>
        @include('partials.wallet-spend', ['w' => $w])
    </div>

    {{-- Last 30 days chart --}}
    <div class="{{ $card }} p-5">
        <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
            <p class="font-semibold text-textprimary dark:text-white">Last 30 days</p>
            <div class="flex items-center gap-3 text-xs text-textsecondary">
                <span class="inline-flex items-center gap-1"><span class="w-3 h-3 rounded-sm bg-blue-500"></span>Orders charged</span>
                <span class="inline-flex items-center gap-1"><span class="w-3 h-0.5 bg-red-500"></span>Cost (৳)</span>
                <a href="{{ route('admin.wallet.history') }}" class="font-semibold text-primary hover:underline">Day by day →</a>
            </div>
        </div>
        <div class="h-56"><canvas id="walletChart"></canvas></div>
    </div>

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
            @if ($w['recharged'] > 0)
                <p class="text-xs text-textsecondary mb-2">Recharged so far: <b class="text-green-700">৳{{ number_format($w['recharged'], 2) }}</b>
                    @if ($w['last_recharge']) · last on {{ $w['last_recharge']->created_at->format('d M Y') }}@endif</p>
            @endif
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
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/vendor/chart-4.4.0.umd.min.js') }}"></script>
<script>
(function () {
    const el = document.getElementById('walletChart');
    if (!el || !window.Chart) return;
    const data = @json($chart);
    const dark = document.documentElement.classList.contains('dark');
    const grid = dark ? 'rgba(255,255,255,.08)' : 'rgba(0,0,0,.06)';
    const text = dark ? '#9ca3af' : '#6b7280';
    new Chart(el, {
        data: {
            labels: data.labels,
            datasets: [
                { type: 'bar', label: 'Orders charged', data: data.orders, backgroundColor: 'rgba(59,130,246,.75)', borderRadius: 4, yAxisID: 'y' },
                { type: 'line', label: 'Cost (৳)', data: data.cost, borderColor: '#ef4444', backgroundColor: '#ef4444', tension: .3, pointRadius: 2, yAxisID: 'y1' },
            ],
        },
        options: {
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: { legend: { display: false },
                tooltip: { callbacks: { label: c => c.dataset.yAxisID === 'y1' ? ' Cost: ৳' + Number(c.raw).toFixed(2) : ' Orders: ' + c.raw } } },
            scales: {
                x:  { grid: { display: false }, ticks: { color: text, maxRotation: 0, autoSkipPadding: 12 } },
                y:  { beginAtZero: true, grid: { color: grid }, ticks: { color: text, precision: 0 } },
                y1: { beginAtZero: true, position: 'right', grid: { display: false }, ticks: { color: text, callback: v => '৳' + v } },
            },
        },
    });
})();
</script>
@endpush
