{{-- Orders charged and their cost: today / 7 days / this month / all time. Needs $w = WalletStats::summary(). --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
    @foreach (['today' => 'Today', 'week' => 'Last 7 days', 'month' => 'This month', 'all' => 'All time'] as $k => $label)
        <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4">
            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $label }}</p>
            <p class="text-xl font-bold text-gray-900 dark:text-white">৳{{ number_format($w[$k]['cost'], 2) }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">{{ number_format($w[$k]['orders']) }} {{ $w[$k]['orders'] === 1 ? 'order' : 'orders' }}@if ($w['fee'] > 0 && $w[$k]['orders']) × ৳{{ number_format($w['fee'], 2) }}@endif</p>
        </div>
    @endforeach
</div>
