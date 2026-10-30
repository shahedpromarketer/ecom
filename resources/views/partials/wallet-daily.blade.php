{{--
    Day-by-day wallet summary. Needs $days = WalletStats::daily(). Optional $dayUrl = fn (Carbon $day) => url
    to open that day's orders.
--}}
<div class="overflow-x-auto">
    <table class="ep-stack w-full text-sm">
        <thead><tr class="text-left text-xs text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
            <th class="p-3">Day</th><th class="p-3 text-right">Orders charged</th><th class="p-3 text-right">Cost</th><th class="p-3 text-right">Added</th><th class="p-3 text-right">Balance at day end</th>@isset($dayUrl)<th class="p-3"></th>@endisset
        </tr></thead>
        <tbody>
            @forelse ($days as $d)
                <tr class="border-b border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/30">
                    <td class="p-3 whitespace-nowrap font-medium">{{ $d->day->isToday() ? 'Today' : ($d->day->isYesterday() ? 'Yesterday' : $d->day->format('d M Y, D')) }}</td>
                    <td class="p-3 text-right">{{ $d->orders ?: '—' }}</td>
                    <td class="p-3 text-right whitespace-nowrap font-semibold {{ $d->cost > 0 ? 'text-red-600' : 'text-gray-400' }}">{{ $d->cost > 0 ? '−৳' . number_format($d->cost, 2) : '—' }}</td>
                    <td class="p-3 text-right whitespace-nowrap font-semibold {{ $d->added > 0 ? 'text-green-700' : 'text-gray-400' }}">{{ $d->added > 0 ? '+৳' . number_format($d->added, 2) : '—' }}@if ($d->taken > 0)<div class="text-xs text-red-600 font-normal">−৳{{ number_format($d->taken, 2) }} adjusted</div>@endif</td>
                    <td class="p-3 text-right">৳{{ number_format($d->balance, 2) }}</td>
                    @isset($dayUrl)<td class="p-3 text-right"><a href="{{ $dayUrl($d->day) }}" class="text-xs font-semibold text-blue-600 hover:underline whitespace-nowrap">Details →</a></td>@endisset
                </tr>
            @empty
                <tr><td colspan="6" class="p-6 text-center text-gray-500">No wallet activity yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
