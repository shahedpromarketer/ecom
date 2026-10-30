{{-- Phones: one card per order instead of the wide table. Uses the list's Alpine state (selectedOrders). --}}
@php
    $badge = [
        'pending' => 'bg-yellow-100 text-yellow-700', 'confirmed' => 'bg-blue-100 text-blue-700',
        'processing' => 'bg-purple-100 text-purple-700', 'hold' => 'bg-orange-100 text-orange-700',
        'shipped' => 'bg-cyan-100 text-cyan-700', 'delivered' => 'bg-green-100 text-green-700',
        'completed' => 'bg-green-100 text-green-700', 'cancelled' => 'bg-red-100 text-red-700',
        'returned' => 'bg-red-100 text-red-700', 'pending_return' => 'bg-rose-100 text-rose-700',
    ];
    $cardPhoneCounts = $orders->countBy(fn ($o) => $o->shipping_phone);
    $cardSeen = [];
@endphp
<div class="md:hidden">
    @if ($orders->count())
        <label class="px-4 py-2 flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700">
            <input type="checkbox" @click="toggleAll()" :checked="selectAll" class="w-4 h-4 rounded border-gray-300">
            Select all on this page
        </label>
    @endif
    <div class="divide-y divide-gray-100 dark:divide-gray-700">
        @forelse ($orders as $order)
            @php
                $dup = ($cardPhoneCounts[$order->shipping_phone] ?? 1) > 1 && in_array($order->shipping_phone, $cardSeen, true);
                $cardSeen[] = $order->shipping_phone;
                $due = (float) ($order->total ?? 0) - (float) ($order->paid_amount ?? 0);
            @endphp
            <div class="px-4 py-3 flex gap-3 {{ $dup ? 'bg-yellow-50 dark:bg-yellow-900/20' : '' }}">
                <input type="checkbox" class="mt-1 w-4 h-4 rounded border-gray-300 flex-shrink-0"
                       :checked="selectedOrders.includes('{{ $order->id }}')"
                       @change="
                           const id = '{{ $order->id }}';
                           selectedOrders = $event.target.checked ? [...new Set([...selectedOrders, id])] : selectedOrders.filter(i => i !== id);
                           const row = document.querySelector('.order-checkbox[value=\'' + id + '\']'); if (row) row.checked = $event.target.checked;
                           updateBulkSummary(selectedOrders);
                       ">

                <a href="{{ route('admin.orders.show', $order->id) }}" class="flex-1 min-w-0 space-y-1">
                    <div class="flex items-center justify-between gap-2">
                        <span class="font-bold text-sm text-blue-600 dark:text-blue-400">#{{ $order->id }}
                            @if ($dup)<span class="ml-1 px-1 py-0.5 bg-yellow-400 text-yellow-900 text-[9px] font-bold rounded align-middle">DUP</span>@endif
                        </span>
                        <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold whitespace-nowrap {{ $badge[$order->status] ?? 'bg-gray-100 text-gray-700' }}">
                            {{ ucfirst(str_replace('_', ' ', $order->status)) }}
                        </span>
                    </div>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ $order->shipping_name ?: 'N/A' }}
                        <span class="font-normal text-gray-500 font-mono text-xs">{{ $order->shipping_phone }}</span></p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $order->shipping_address }}{{ $order->shipping_district ? ', ' . $order->shipping_district : '' }}</p>
                    <p class="text-xs text-gray-700 dark:text-gray-300 truncate">
                        {{ $order->items->map(fn ($i) => $i->qty . 'x ' . ($i->product_name ?? $i->product?->name ?? 'Product'))->implode(', ') }}
                    </p>
                    @if ($order->shipment)
                        <p class="text-[11px] text-gray-500 truncate">{{ ucfirst($order->shipment->courier_name) }} · {{ $order->shipment->tracking_code }} · {{ $order->shipment->statusLabel() }}</p>
                    @endif
                    <div class="flex items-center justify-between text-xs pt-0.5">
                        <span class="text-gray-400">{{ $order->created_at->format('d M, h:i A') }}</span>
                        <span class="font-bold text-gray-900 dark:text-white">৳{{ number_format($order->total ?? 0) }}
                            @if ($due > 0 && $due < (float) $order->total)<span class="text-red-500 font-semibold">(due ৳{{ number_format($due) }})</span>@endif
                        </span>
                    </div>
                </a>

                <div class="flex flex-col gap-1.5 flex-shrink-0">
                    <a href="tel:{{ $order->shipping_phone }}" class="w-8 h-8 flex items-center justify-center bg-blue-500 text-white rounded-lg" aria-label="Call">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z"/></svg>
                    </a>
                    <a href="https://wa.me/880{{ ltrim((string) $order->shipping_phone, '0') }}" target="_blank" class="w-8 h-8 flex items-center justify-center bg-green-500 text-white rounded-lg" aria-label="WhatsApp">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91C21.95 6.45 17.5 2 12.04 2Zm5.8 14.13c-.24.68-1.4 1.3-1.93 1.35-.5.05-1.12.07-1.81-.11-.42-.13-.95-.31-1.64-.6-2.88-1.25-4.76-4.15-4.9-4.34-.14-.19-1.17-1.56-1.17-2.97s.74-2.11 1-2.4c.26-.28.57-.35.76-.35h.55c.18 0 .41-.07.64.49.24.57.81 1.98.88 2.12.07.14.12.31.02.5-.09.19-.14.31-.28.47-.14.17-.3.37-.43.5-.14.14-.29.3-.12.58.16.28.73 1.2 1.57 1.94 1.08.96 1.99 1.26 2.27 1.4.28.14.45.12.61-.07.17-.19.71-.83.9-1.11.19-.28.38-.24.64-.14.26.09 1.66.78 1.94.92.28.14.47.21.54.33.07.12.07.68-.17 1.36Z"/></svg>
                    </a>
                </div>
            </div>
        @empty
            <div class="px-4 py-10 text-center text-sm text-gray-500">No orders found</div>
        @endforelse
    </div>
</div>
