{{--
    Big wallet balance, coloured by how many orders it still covers:
    green = plenty, yellow = getting low, red = almost empty / empty.
    Needs $w = WalletStats::summary($tenant). Optional $compact.
--}}
@php
    $tone = [
        'green'  => ['box' => 'bg-green-50 border-green-200 dark:bg-green-900/20 dark:border-green-800', 'num' => 'text-green-700 dark:text-green-400', 'bar' => 'bg-green-500', 'msg' => 'Balance is good'],
        'yellow' => ['box' => 'bg-amber-50 border-amber-200 dark:bg-amber-900/20 dark:border-amber-800', 'num' => 'text-amber-600 dark:text-amber-400', 'bar' => 'bg-amber-500', 'msg' => 'Balance is getting low — recharge soon'],
        'red'    => ['box' => 'bg-red-50 border-red-200 dark:bg-red-900/20 dark:border-red-800', 'num' => 'text-red-600 dark:text-red-400', 'bar' => 'bg-red-500', 'msg' => $w['balance'] <= 0 ? 'Balance is empty — order work is locked' : 'Balance is almost empty — recharge now'],
        'free'   => ['box' => 'bg-gray-50 border-gray-200 dark:bg-gray-800 dark:border-gray-700', 'num' => 'text-gray-800 dark:text-white', 'bar' => 'bg-gray-400', 'msg' => 'Free — no fee per order'],
    ][$w['level']];
    $pct = $w['left'] === null ? 100 : min(100, (int) round($w['left'] / max(1, \App\Services\WalletStats::YELLOW_BELOW * 2) * 100));
    $compact = $compact ?? false;
@endphp
<div class="rounded-2xl border {{ $tone['box'] }} {{ $compact ? 'p-3' : 'p-5' }}">
    <div class="flex flex-wrap items-end justify-between gap-2">
        <div>
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Balance</p>
            <p class="{{ $compact ? 'text-2xl' : 'text-4xl' }} font-extrabold tracking-tight {{ $tone['num'] }}">৳{{ number_format($w['balance'], 2) }}</p>
        </div>
        @if ($w['left'] !== null)
            <div class="text-right">
                <p class="{{ $compact ? 'text-base' : 'text-xl' }} font-bold {{ $tone['num'] }}">≈ {{ number_format($w['left']) }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">more orders</p>
            </div>
        @endif
    </div>
    @if ($w['left'] !== null)
        <div class="mt-3 h-2 rounded-full bg-white/70 dark:bg-gray-700 overflow-hidden"><div class="h-2 rounded-full {{ $tone['bar'] }}" style="width: {{ max(3, $pct) }}%"></div></div>
    @endif
    <p class="mt-2 text-xs font-medium {{ $tone['num'] }}">{{ $tone['msg'] }}
        @if ($w['fee'] > 0)<span class="text-gray-500 dark:text-gray-400 font-normal">· ৳{{ number_format($w['fee'], 2) }} per order, taken {{ $w['charge_on'] === 'created' ? 'when an order arrives' : 'when an order is confirmed' }}</span>@endif
    </p>
</div>
