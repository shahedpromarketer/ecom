{{--
    Wallet lines: date, what, order + its status, amount, balance after.
    Needs $transactions (with 'order' loaded). Optional $linkOrders (shop side: order numbers open the order).
--}}
@php $typeLabel = ['recharge' => 'Recharge', 'order' => 'Order fee', 'adjustment' => 'Adjustment']; @endphp
<div class="overflow-x-auto">
    <table class="ep-stack w-full text-sm">
        <thead><tr class="text-left text-xs text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
            <th class="p-3">Time</th><th class="p-3">What</th><th class="p-3">Order</th><th class="p-3">Order status</th><th class="p-3 text-right">Amount</th><th class="p-3 text-right">Balance after</th>
        </tr></thead>
        <tbody>
            @forelse ($transactions as $t)
                <tr class="border-b border-gray-100 dark:border-gray-700">
                    <td class="p-3 whitespace-nowrap">{{ $t->created_at->format('d M Y, h:i A') }}</td>
                    <td class="p-3">
                        <span class="inline-flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full {{ $t->type === 'order' ? 'bg-red-400' : ((float) $t->amount >= 0 ? 'bg-green-500' : 'bg-amber-500') }}"></span>
                            {{ $typeLabel[$t->type] ?? $t->type }}
                        </span>
                        @if ($t->type !== 'order' && $t->note)<div class="text-xs text-gray-500 dark:text-gray-400">{{ $t->note }}</div>@endif
                    </td>
                    <td class="p-3 font-mono text-xs">
                        @if ($t->type === 'order')
                            @php $num = $t->order?->order_number ?? str_replace('Order #', '', (string) $t->note); @endphp
                            @if (! empty($linkOrders) && $t->order)<a href="{{ route('admin.orders.show', $t->order_id) }}" class="text-blue-600 hover:underline">#{{ $num }}</a>@else #{{ $num }} @endif
                        @endif
                    </td>
                    <td class="p-3">
                        @if ($t->type === 'order')
                            @if ($t->order)<span class="px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-700 dark:text-gray-200">{{ ucwords(str_replace('_', ' ', $t->order->status)) }}</span>
                            @else<span class="text-xs text-gray-400">Deleted</span>@endif
                        @endif
                    </td>
                    <td class="p-3 text-right font-semibold {{ (float) $t->amount < 0 ? 'text-red-600' : 'text-green-700' }}">{{ (float) $t->amount < 0 ? '−' : '+' }}৳{{ number_format(abs((float) $t->amount), 2) }}</td>
                    <td class="p-3 text-right">৳{{ number_format((float) $t->balance_after, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="p-6 text-center text-gray-500">Nothing here.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
