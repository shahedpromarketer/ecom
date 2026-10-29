@extends('layouts.admin')

@section('title', ($customer->name ?: $customer->phone) . ' — Customer')
@section('page-title', 'Customer Profile')

@section('breadcrumb')
    <span class="text-textsecondary">/</span>
    <a href="{{ route('admin.customers.index') }}" class="text-textsecondary hover:text-textprimary">Customers</a>
    <span class="text-textsecondary">/</span>
    <span class="text-textprimary font-medium">{{ $customer->name ?: $customer->phone }}</span>
@endsection

@php
    $input = 'w-full px-3 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700/50 text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all';
    $label = 'block text-xs font-medium text-textsecondary dark:text-gray-400 mb-1';
    $rate  = $customer->success_rate;
    $canEdit = auth()->user()->can('manage_customers');
    $canBlock = auth()->user()->can('ban_customers');
    $statusColors = [
        'pending' => 'bg-yellow-100 text-yellow-800', 'confirmed' => 'bg-blue-100 text-blue-800',
        'processing' => 'bg-indigo-100 text-indigo-800', 'shipped' => 'bg-purple-100 text-purple-800',
        'delivered' => 'bg-green-100 text-green-800', 'completed' => 'bg-green-100 text-green-800',
        'cancelled' => 'bg-red-100 text-red-800', 'returned' => 'bg-orange-100 text-orange-800',
        'pending_return' => 'bg-orange-100 text-orange-800',
    ];
@endphp

@section('content')

@if(session('success'))
    <div class="mb-4 p-3.5 rounded-xl bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-sm text-green-700 dark:text-green-400">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="mb-4 p-3.5 rounded-xl bg-red-50 border border-red-200 text-sm text-red-700">{{ $errors->first() }}</div>
@endif

<div class="grid grid-cols-1 xl:grid-cols-3 gap-5">

    {{-- ── Left: profile ── --}}
    <div class="space-y-5">
        <div class="p-5 rounded-2xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 class="text-lg font-bold text-textprimary dark:text-white">{{ $customer->name ?: 'Unknown' }}</h2>
                    <p class="font-mono text-sm text-textsecondary mt-0.5">{{ $customer->phone }}</p>
                </div>
                @if($customer->is_blocked)
                    <span class="px-2 py-1 text-xs font-semibold rounded-lg bg-red-100 text-red-700">BLOCKED</span>
                @endif
            </div>
            <div class="mt-4 flex gap-2">
                <a href="tel:{{ $customer->phone }}" class="flex-1 text-center px-3 py-2 text-xs font-medium rounded-xl bg-blue-50 text-blue-700 hover:bg-blue-100">Call</a>
                <a href="https://wa.me/88{{ $customer->phone }}" target="_blank" rel="noopener" class="flex-1 text-center px-3 py-2 text-xs font-medium rounded-xl bg-green-50 text-green-700 hover:bg-green-100">WhatsApp</a>
                @can('manage_orders')
                <a href="{{ route('admin.orders.create', ['phone' => $customer->phone]) }}" class="flex-1 text-center px-3 py-2 text-xs font-medium rounded-xl bg-primary text-white hover:bg-primary/90">New order</a>
                @endcan
            </div>
        </div>

        <form method="POST" action="{{ route('admin.customers.update', $customer) }}"
              class="p-5 rounded-2xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 space-y-3">
            @csrf @method('PATCH')
            <h3 class="text-sm font-semibold text-textprimary dark:text-white">Details</h3>

            <div><label class="{{ $label }}">Name</label><input name="name" value="{{ old('name', $customer->name) }}" class="{{ $input }}" @disabled(!$canEdit)></div>
            <div><label class="{{ $label }}">Alternative phone</label><input name="alt_phone" value="{{ old('alt_phone', $customer->alt_phone) }}" class="{{ $input }}" @disabled(!$canEdit)></div>
            <div><label class="{{ $label }}">Email</label><input name="email" type="email" value="{{ old('email', $customer->email) }}" class="{{ $input }}" @disabled(!$canEdit)></div>
            <div><label class="{{ $label }}">Address</label><textarea name="address" rows="2" class="{{ $input }}" @disabled(!$canEdit)>{{ old('address', $customer->address) }}</textarea></div>
            <div><label class="{{ $label }}">District</label><input name="district" value="{{ old('district', $customer->district) }}" class="{{ $input }}" @disabled(!$canEdit)></div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="{{ $label }}">Tag</label>
                    <select name="tag" class="{{ $input }}" @disabled(!$canEdit)>
                        <option value="">No tag</option>
                        @foreach(\App\Models\Customer::TAGS as $key => $text)
                            <option value="{{ $key }}" @selected(old('tag', $customer->tag) === $key)>{{ $text }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="{{ $label }}">Assigned to</label>
                    <select name="assigned_to" class="{{ $input }}" @disabled(!$canEdit)>
                        <option value="">Nobody</option>
                        @foreach($staff as $s)
                            <option value="{{ $s->id }}" @selected((int) old('assigned_to', $customer->assigned_to) === $s->id)>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            @if($canBlock)
                <label class="flex items-start gap-2 p-3 rounded-xl bg-red-50/60 dark:bg-red-900/10 border border-red-100 dark:border-red-900/40">
                    <input type="hidden" name="is_blocked" value="0">
                    <input type="checkbox" name="is_blocked" value="1" class="mt-0.5" @checked(old('is_blocked', $customer->is_blocked))>
                    <span class="text-xs text-red-700 dark:text-red-400">
                        <strong>Block this customer.</strong> Their number is added to the blacklist, so website checkout (plugin) refuses new orders from it.
                    </span>
                </label>
            @else
                <input type="hidden" name="is_blocked" value="{{ $customer->is_blocked ? 1 : 0 }}">
            @endif

            @if($canEdit)
                <button type="submit" class="w-full px-4 py-2.5 text-sm font-semibold bg-primary text-white rounded-xl hover:bg-primary/90">Save</button>
            @endif
        </form>
    </div>

    {{-- ── Right: stats, orders, notes ── --}}
    <div class="xl:col-span-2 space-y-5">

        <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
            @foreach([
                ['Orders', $customer->total_orders, 'text-textprimary dark:text-white'],
                ['Delivered', $customer->delivered_orders, 'text-green-600'],
                ['Cancelled', $customer->cancelled_orders, 'text-red-500'],
                ['Returned', $customer->returned_orders, 'text-orange-500'],
                ['Success', $rate === null ? '—' : $rate . '%', $rate === null ? 'text-gray-400' : ($rate >= 80 ? 'text-green-600' : ($rate >= 50 ? 'text-amber-600' : 'text-red-600'))],
            ] as [$t, $v, $cls])
                <div class="p-4 rounded-2xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
                    <p class="text-xs text-textsecondary">{{ $t }}</p>
                    <p class="mt-1 text-xl font-bold {{ $cls }}">{{ $v }}</p>
                </div>
            @endforeach
        </div>
        <p class="text-xs text-textsecondary -mt-2">
            Spent ৳{{ number_format($customer->total_spent) }} on delivered orders ·
            customer since {{ $customer->first_order_at?->format('d M Y') ?? '—' }} ·
            last order {{ $customer->last_order_at?->diffForHumans() ?? '—' }}
        </p>

        {{-- Orders --}}
        <div class="rounded-2xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-100 dark:border-gray-700 text-sm font-semibold text-textprimary dark:text-white">Orders</div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            <th class="px-5 py-2.5">Order</th><th class="px-4 py-2.5">Products</th>
                            <th class="px-4 py-2.5">Status</th><th class="px-4 py-2.5 text-right">Total</th><th class="px-4 py-2.5">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($orders as $o)
                            <tr>
                                <td class="px-5 py-2.5"><a href="{{ route('admin.orders.show', $o) }}" class="font-medium text-primary hover:underline">#{{ $o->order_number }}</a></td>
                                <td class="px-4 py-2.5 text-xs text-textsecondary">{{ $o->items->map(fn ($i) => $i->qty . '× ' . $i->product_name)->implode(', ') ?: '—' }}</td>
                                <td class="px-4 py-2.5"><span class="px-2 py-0.5 text-xs rounded-full {{ $statusColors[$o->status] ?? 'bg-gray-100 text-gray-700' }}">{{ ucwords(str_replace('_', ' ', $o->status)) }}</span></td>
                                <td class="px-4 py-2.5 text-right">৳{{ number_format($o->total) }}</td>
                                <td class="px-4 py-2.5 text-xs text-textsecondary">{{ $o->created_at?->format('d M Y') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-5 py-8 text-center text-textsecondary">No orders.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($orders->hasPages())
                <div class="px-5 py-3 border-t border-gray-100 dark:border-gray-700">{{ $orders->links() }}</div>
            @endif
        </div>

        {{-- Follow-ups --}}
        <div id="followups" class="rounded-2xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
            <div class="px-5 py-3 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                <span class="text-sm font-semibold text-textprimary dark:text-white">Follow-ups</span>
                <a href="{{ route('admin.followups.index') }}" class="text-xs text-primary hover:underline">Open call list →</a>
            </div>
            <form method="POST" action="{{ route('admin.followups.store') }}" class="p-5 border-b border-gray-100 dark:border-gray-700 grid grid-cols-1 md:grid-cols-6 gap-2">
                @csrf
                <input type="hidden" name="customer_id" value="{{ $customer->id }}">
                <input type="date" name="scheduled_for" required min="{{ today()->toDateString() }}" value="{{ today()->addDays(3)->toDateString() }}" class="{{ $input }} md:col-span-1">
                <select name="tag" class="{{ $input }} md:col-span-1">
                    @foreach(\App\Models\Followup::TAGS as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
                </select>
                @can('view_all_followups')
                <select name="assigned_to" class="{{ $input }} md:col-span-1">
                    <option value="">Me</option>
                    @foreach($staff as $s)<option value="{{ $s->id }}" @selected($customer->assigned_to === $s->id)>{{ $s->name }}</option>@endforeach
                </select>
                @endcan
                <input name="note" maxlength="2000" placeholder="What to talk about…" class="{{ $input }} md:col-span-2">
                <button class="px-4 py-2.5 text-sm font-semibold bg-primary text-white rounded-xl hover:bg-primary/90">Schedule</button>
            </form>
            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse($followups as $f)
                    <div class="px-5 py-3 flex items-start gap-3 text-sm">
                        <span class="mt-0.5 px-2 py-0.5 text-[11px] font-semibold rounded-full
                            {{ $f->status === 'done' ? 'bg-green-100 text-green-700' : ($f->status === 'negative' ? 'bg-red-100 text-red-700' : ($f->is_overdue ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-800')) }}">
                            {{ $f->status === 'listed' ? ($f->is_overdue ? 'Overdue' : 'To call') : \App\Models\Followup::STATUSES[$f->status] }}
                        </span>
                        <div class="flex-1">
                            <p class="text-textprimary dark:text-gray-200">
                                {{ $f->scheduled_for->format('d M Y') }} · {{ \App\Models\Followup::TAGS[$f->tag] ?? 'Follow-up' }}
                                <span class="text-xs text-textsecondary">· {{ $f->assignee?->name ?? 'Unassigned' }}</span>
                            </p>
                            @if($f->note)<p class="text-xs text-textsecondary mt-0.5">{{ $f->note }}</p>@endif
                            @if($f->result_note)<p class="text-xs mt-0.5"><strong>Result:</strong> {{ $f->result_note }} <span class="text-textsecondary">— {{ $f->doneByUser?->name }}</span></p>@endif
                        </div>
                    </div>
                @empty
                    <p class="px-5 py-6 text-sm text-center text-textsecondary">No follow-ups yet.</p>
                @endforelse
            </div>
        </div>

        {{-- Notes --}}
        <div class="rounded-2xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
            <div class="px-5 py-3 border-b border-gray-100 dark:border-gray-700 text-sm font-semibold text-textprimary dark:text-white">Customer notes</div>
            @if($canEdit)
                <form method="POST" action="{{ route('admin.customers.notes.store', $customer) }}" class="p-5 border-b border-gray-100 dark:border-gray-700 flex gap-2">
                    @csrf
                    <input name="note" required maxlength="2000" placeholder="e.g. Prefers delivery after 5pm, asked about size L…" class="{{ $input }}">
                    <button type="submit" class="px-4 py-2.5 text-sm font-semibold bg-primary text-white rounded-xl hover:bg-primary/90 whitespace-nowrap">Add note</button>
                </form>
            @endif
            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse($customer->notes as $note)
                    <div class="px-5 py-3 flex items-start gap-3">
                        <div class="flex-1">
                            <p class="text-sm text-textprimary dark:text-gray-200 whitespace-pre-line">{{ $note->note }}</p>
                            <p class="mt-1 text-xs text-textsecondary">{{ $note->user?->name ?? 'System' }} · {{ $note->created_at->diffForHumans() }}</p>
                        </div>
                        @if($canEdit)
                            <form method="POST" action="{{ route('admin.customers.notes.destroy', $note) }}" onsubmit="return confirm('Delete this note?')">
                                @csrf @method('DELETE')
                                <button class="text-xs text-red-500 hover:underline">Delete</button>
                            </form>
                        @endif
                    </div>
                @empty
                    <p class="px-5 py-6 text-sm text-center text-textsecondary">No notes yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

@endsection
