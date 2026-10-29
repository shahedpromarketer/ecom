@extends('layouts.admin')

@section('title', 'Failed Orders')
@section('page-title', 'Failed Orders')

@section('breadcrumb')
    <span class="text-textsecondary">/</span>
    <span class="text-textprimary font-medium">Failed Orders</span>
@endsection

@php
    use App\Models\IncompleteOrder;
    use App\Http\Controllers\Admin\FailedOrderController;
    $input = 'px-3 py-2 text-sm rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700/50 text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary';
    $canManage = auth()->user()->can('manage_orders');
    $canBlock = auth()->user()->can('ban_customers');
@endphp

@section('content')

@if(session('success'))
    <div class="mb-4 p-3.5 rounded-xl bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-sm text-green-700 dark:text-green-400">{{ session('success') }}</div>
@endif

<p class="mb-4 text-sm text-textsecondary">
    People who typed their phone number at checkout but did not finish. Call them — many still want to buy.
    When the same number orders, the entry moves to <strong>Recovered</strong> by itself.
</p>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
    @foreach([
        ['To call now', $stats['pending'], 'text-amber-600'],
        ['New today', $stats['today'], 'text-textprimary dark:text-white'],
        ['Recovered (30 days)', $stats['recovered'], 'text-green-600'],
        ['Recovery rate (30 days)', $stats['rate'] === null ? '—' : $stats['rate'] . '%', 'text-green-600'],
    ] as [$label, $value, $cls])
        <div class="p-4 rounded-2xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
            <p class="text-xs text-textsecondary">{{ $label }}</p>
            <p class="mt-1 text-2xl font-bold {{ $cls }}">{{ $value }}</p>
        </div>
    @endforeach
</div>

<div x-data="{ selected: [], open: null }">

    <div class="mb-3 flex flex-wrap gap-2">
        @foreach(FailedOrderController::TABS as $key => $label)
            <a href="{{ route('admin.failed-orders.index', array_merge(request()->except(['tab', 'page']), ['tab' => $key])) }}"
               class="px-4 py-2 rounded-xl text-sm font-medium border {{ $tab === $key ? 'bg-primary text-white border-primary' : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 hover:border-primary/40' }}">{{ $label }}</a>
        @endforeach
    </div>

    <form method="GET" class="mb-4 flex flex-wrap items-center gap-2">
        <input type="hidden" name="tab" value="{{ $tab }}">
        <input name="search" value="{{ request('search') }}" placeholder="Name or phone…" class="{{ $input }} w-52">
        <select name="period" class="{{ $input }}">
            <option value="">Any time</option>
            <option value="today" @selected(request('period') === 'today')>Today</option>
            <option value="7d" @selected(request('period') === '7d')>Last 7 days</option>
            <option value="30d" @selected(request('period') === '30d')>Last 30 days</option>
        </select>
        @if($stores->count() > 1)
            <select name="store" class="{{ $input }}">
                <option value="">All stores</option>
                @foreach($stores as $st)<option value="{{ $st->id }}" @selected((string) request('store') === (string) $st->id)>{{ $st->name }}</option>@endforeach
            </select>
        @endif
        <button class="px-4 py-2 text-sm font-semibold bg-primary text-white rounded-xl hover:bg-primary/90">Filter</button>
    </form>

    @if($tab === 'pending' && $canManage)
    <form method="POST" action="{{ route('admin.failed-orders.bulk') }}" x-show="selected.length" x-cloak
          class="mb-3 p-3 rounded-xl bg-primary/5 border border-primary/20 flex flex-wrap items-center gap-2">
        @csrf
        <template x-for="id in selected" :key="id"><input type="hidden" name="ids[]" :value="id"></template>
        <span class="text-sm font-medium"><span x-text="selected.length"></span> selected</span>
        <button name="action" value="lead" class="px-3 py-2 text-xs rounded-xl bg-white border border-gray-200 hover:border-primary">Move to leads</button>
        <select name="delete_reason" class="{{ $input }}">@foreach(IncompleteOrder::DELETE_REASONS as $r)<option>{{ $r }}</option>@endforeach</select>
        <button name="action" value="delete" class="px-3 py-2 text-xs rounded-xl bg-white border border-gray-200 hover:border-red-400">Delete</button>
        @if($canBlock)<button name="action" value="block" onclick="return confirm('Block these numbers (and IPs)?')" class="px-3 py-2 text-xs rounded-xl bg-red-500 text-white">Block number + IP</button>@endif
    </form>
    @endif

    <div class="rounded-2xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                    <tr class="text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        <th class="pl-5 py-3 w-8"></th>
                        <th class="px-4 py-3">Visitor</th>
                        <th class="px-4 py-3">Past orders</th>
                        <th class="px-4 py-3">In the cart</th>
                        <th class="px-4 py-3">Seen</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse($items as $f)
                    @php $h = $history[$f->phone] ?? null; @endphp
                    <tr>
                        <td class="pl-5 py-3">@if($f->status === 'pending')<input type="checkbox" value="{{ $f->id }}" x-model.number="selected">@endif</td>
                        <td class="px-4 py-3">
                            <div class="font-medium text-textprimary dark:text-white">{{ $f->name ?: 'No name yet' }}</div>
                            <div class="flex items-center gap-2 text-xs mt-0.5">
                                <a href="tel:{{ $f->phone }}" class="font-mono text-primary hover:underline">{{ $f->phone }}</a>
                                <a href="https://wa.me/88{{ $f->phone }}" target="_blank" rel="noopener" class="text-green-600 hover:underline">WhatsApp</a>
                            </div>
                            @if($f->address)<div class="text-xs text-textsecondary mt-0.5">{{ \Illuminate\Support\Str::limit($f->address, 80) }}</div>@endif
                        </td>
                        <td class="px-4 py-3 text-xs">
                            @if($h && $h->total_orders)
                                {{ $h->total_orders }} orders · {{ $h->delivered_orders }} delivered
                                @if($h->success_rate !== null)<div class="{{ $h->success_rate >= 80 ? 'text-green-600' : ($h->success_rate >= 50 ? 'text-amber-600' : 'text-red-600') }}">{{ $h->success_rate }}% success</div>@endif
                                @if($h->is_blocked)<div class="text-red-600 font-semibold">Blocked customer</div>@endif
                            @else
                                <span class="text-textsecondary">New number</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-xs max-w-xs">
                            {{ $f->itemsSummary() ?: '—' }}
                            @if($f->total > 0)<div class="font-semibold">৳{{ number_format($f->total) }}</div>@endif
                            @if($f->note)<div class="mt-1 text-textsecondary whitespace-pre-line">{{ \Illuminate\Support\Str::limit($f->note, 160) }}</div>@endif
                        </td>
                        <td class="px-4 py-3 text-xs text-textsecondary whitespace-nowrap">
                            {{ $f->last_activity_at?->diffForHumans() }}
                            <div>{{ $f->store?->name }}</div>
                            @if($f->ip)<div class="font-mono" title="{{ $f->user_agent }}">{{ $f->ip }}</div>@endif
                        </td>
                        <td class="px-4 py-3 text-xs">
                            @if($f->status === 'recovered')
                                <span class="px-2 py-0.5 rounded-full bg-green-100 text-green-700">Recovered</span>
                                @if($f->order)<div class="mt-1"><a href="{{ route('admin.orders.show', $f->order) }}" class="text-primary hover:underline">#{{ $f->order->order_number }}</a></div>@endif
                            @elseif($f->status === 'deleted')
                                <span class="px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">{{ $f->delete_reason ?: 'Deleted' }}</span>
                            @else
                                <span class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-800">To call</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            @if($f->status === 'pending' && $canManage)
                                <a href="{{ route('admin.orders.create', ['phone' => $f->phone]) }}" class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-primary text-white hover:bg-primary/90">Create order</a>
                            @endif
                            @if($canManage)
                                <button type="button" @click="open = open === {{ $f->id }} ? null : {{ $f->id }}" class="ml-1 px-2 py-1.5 text-xs rounded-lg border border-gray-200 dark:border-gray-600 hover:border-primary">More</button>
                            @endif
                        </td>
                    </tr>
                    @if($canManage)
                    <tr x-show="open === {{ $f->id }}" x-cloak class="bg-gray-50 dark:bg-gray-700/30">
                        <td colspan="7" class="px-5 py-4">
                            <div class="flex flex-wrap items-end gap-2">
                                <form method="POST" action="{{ route('admin.failed-orders.update', $f) }}" class="flex gap-2">@csrf @method('PATCH')
                                    <input type="hidden" name="action" value="note">
                                    <input name="note" required placeholder="Call note…" class="{{ $input }} w-64">
                                    <button class="px-3 py-2 text-xs font-semibold rounded-xl bg-primary text-white">Save note</button>
                                </form>
                                @if($f->status === 'pending')
                                    <form method="POST" action="{{ route('admin.failed-orders.update', $f) }}">@csrf @method('PATCH')
                                        <input type="hidden" name="action" value="lead"><button class="px-3 py-2 text-xs rounded-xl bg-white border border-gray-200">Move to leads</button></form>
                                    <form method="POST" action="{{ route('admin.failed-orders.update', $f) }}" class="flex gap-1">@csrf @method('PATCH')
                                        <input type="hidden" name="action" value="delete">
                                        <select name="delete_reason" class="{{ $input }}">@foreach(IncompleteOrder::DELETE_REASONS as $r)<option>{{ $r }}</option>@endforeach</select>
                                        <button class="px-3 py-2 text-xs rounded-xl bg-white border border-gray-200 hover:border-red-400">Delete</button></form>
                                    @if($canBlock)
                                    <form method="POST" action="{{ route('admin.failed-orders.update', $f) }}" onsubmit="return confirm('Block {{ $f->phone }}{{ $f->ip ? ' and IP ' . $f->ip : '' }}?')">@csrf @method('PATCH')
                                        <input type="hidden" name="action" value="block"><button class="px-3 py-2 text-xs rounded-xl bg-red-500 text-white">Block number{{ $f->ip ? ' + IP' : '' }}</button></form>
                                    @endif
                                @elseif($f->status === 'deleted')
                                    <form method="POST" action="{{ route('admin.failed-orders.update', $f) }}">@csrf @method('PATCH')
                                        <input type="hidden" name="action" value="restore"><button class="px-3 py-2 text-xs rounded-xl bg-white border border-gray-200">Restore</button></form>
                                @endif
                                @if($f->page_url)<a href="{{ $f->page_url }}" target="_blank" rel="noopener noreferrer" class="text-xs text-primary hover:underline">Checkout page ↗</a>@endif
                            </div>
                        </td>
                    </tr>
                    @endif
                @empty
                    <tr><td colspan="7" class="px-5 py-12 text-center text-textsecondary">
                        {{ $tab === 'pending' ? 'No unfinished checkouts to call.' : 'Nothing here.' }}
                    </td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($items->hasPages())
            <div class="px-5 py-3 border-t border-gray-100 dark:border-gray-700">{{ $items->links() }}</div>
        @endif
    </div>
</div>
@endsection
