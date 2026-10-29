@extends('layouts.admin')

@section('title', 'Follow-ups')
@section('page-title', 'Follow-ups')

@section('breadcrumb')
    <span class="text-textsecondary">/</span>
    <span class="text-textprimary font-medium">Follow-ups</span>
@endsection

@php
    use App\Models\Followup;
    use App\Http\Controllers\Admin\FollowupController;
    $input = 'px-3 py-2 text-sm rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700/50 text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary';
    $canAll = auth()->user()->can('view_all_followups');
    $open = in_array($tab, ['today', 'overdue', 'upcoming'], true);
@endphp

@section('content')

@if(session('success'))
    <div class="mb-4 p-3.5 rounded-xl bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-sm text-green-700 dark:text-green-400">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="mb-4 p-3.5 rounded-xl bg-red-50 border border-red-200 text-sm text-red-700">{{ $errors->first() }}</div>
@endif

{{-- Tabs --}}
<div class="mb-4 flex flex-wrap gap-2">
    @foreach(FollowupController::TABS as $key => $label)
        <a href="{{ route('admin.followups.index', array_merge(request()->except(['tab', 'page']), ['tab' => $key])) }}"
           class="px-4 py-2 rounded-xl text-sm font-medium border transition-colors
                  {{ $tab === $key ? 'bg-primary text-white border-primary' : 'bg-white dark:bg-gray-800 text-textprimary dark:text-gray-200 border-gray-200 dark:border-gray-700 hover:border-primary/40' }}">
            {{ $label }}
            @isset($counts[$key])
                <span class="ml-1 px-1.5 py-0.5 rounded-md text-xs {{ $tab === $key ? 'bg-white/20' : ($key === 'overdue' && $counts[$key] ? 'bg-red-100 text-red-700' : 'bg-gray-100 dark:bg-gray-700') }}">{{ $counts[$key] }}</span>
            @endisset
        </a>
    @endforeach
    @unless($canAll)
        <span class="self-center text-xs text-textsecondary ml-2">Showing follow-ups assigned to you</span>
    @endunless
</div>

{{-- Filters --}}
<x-admin.saved-filters page="followups" />
<form method="GET" class="mb-4 flex flex-wrap items-center gap-2">
    <input type="hidden" name="tab" value="{{ $tab }}">
    <input type="text" name="search" value="{{ request('search') }}" placeholder="Customer name or phone…" class="{{ $input }} w-56">
    <select name="tag" class="{{ $input }}">
        <option value="">Any reason</option>
        @foreach(Followup::TAGS as $k => $v)<option value="{{ $k }}" @selected(request('tag') === $k)>{{ $v }}</option>@endforeach
    </select>
    @if($canAll)
        <select name="assigned_to" class="{{ $input }}">
            <option value="">Anyone</option>
            <option value="none" @selected(request('assigned_to') === 'none')>Unassigned</option>
            @foreach($staff as $s)<option value="{{ $s->id }}" @selected((string) request('assigned_to') === (string) $s->id)>{{ $s->name }}</option>@endforeach
        </select>
    @endif
    <button class="px-4 py-2 text-sm font-semibold bg-primary text-white rounded-xl hover:bg-primary/90">Filter</button>
    @if(request()->except(['tab', 'page']))
        <a href="{{ route('admin.followups.index', ['tab' => $tab]) }}" class="text-sm text-textsecondary hover:text-textprimary">Clear</a>
    @endif
</form>

<div x-data="{ selected: [], logFor: null }">

    {{-- Bulk bar --}}
    @if($open)
    <form method="POST" action="{{ route('admin.followups.bulk') }}" x-show="selected.length" x-cloak
          class="mb-3 p-3 rounded-xl bg-primary/5 border border-primary/20 flex flex-wrap items-center gap-2">
        @csrf
        <template x-for="id in selected" :key="id"><input type="hidden" name="ids[]" :value="id"></template>
        <span class="text-sm font-medium text-textprimary dark:text-white"><span x-text="selected.length"></span> selected</span>
        <input type="date" name="scheduled_for" min="{{ today()->toDateString() }}" class="{{ $input }}">
        <button name="action" value="reschedule" class="px-3 py-2 text-xs font-semibold rounded-xl bg-white border border-gray-200 hover:border-primary">Move to date</button>
        @if($canAll)
            <select name="assigned_to" class="{{ $input }}">
                <option value="">Choose staff…</option>
                @foreach($staff as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach
            </select>
            <button name="action" value="assign" class="px-3 py-2 text-xs font-semibold rounded-xl bg-white border border-gray-200 hover:border-primary">Assign</button>
        @endif
        <button name="action" value="done" class="px-3 py-2 text-xs font-semibold rounded-xl bg-green-600 text-white hover:bg-green-700">Mark done</button>
    </form>
    @endif

    <div class="rounded-2xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                    <tr class="text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        @if($open)<th class="pl-5 py-3 w-8"></th>@endif
                        <th class="px-4 py-3">Customer</th>
                        <th class="px-4 py-3">{{ $open ? 'Call on' : 'Finished' }}</th>
                        <th class="px-4 py-3">Reason</th>
                        <th class="px-4 py-3">Note</th>
                        <th class="px-4 py-3">Assigned</th>
                        <th class="px-4 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse($followups as $f)
                    @php $c = $f->customer; $rate = $c?->success_rate; @endphp
                    <tr class="{{ $f->is_overdue ? 'bg-red-50/40 dark:bg-red-900/10' : '' }}">
                        @if($open)
                            <td class="pl-5 py-3"><input type="checkbox" value="{{ $f->id }}" x-model.number="selected"></td>
                        @endif
                        <td class="px-4 py-3">
                            @if($c)
                                <a href="{{ route('admin.customers.show', $c) }}" class="font-medium text-textprimary dark:text-white hover:text-primary">{{ $c->name ?: 'Unknown' }}</a>
                                <div class="flex items-center gap-2 mt-0.5 text-xs">
                                    <a href="tel:{{ $c->phone }}" class="font-mono text-primary hover:underline">{{ $c->phone }}</a>
                                    <a href="https://wa.me/88{{ $c->phone }}" target="_blank" rel="noopener" class="text-green-600 hover:underline">WhatsApp</a>
                                    <span class="text-textsecondary">· {{ $c->total_orders }} orders{{ $rate !== null ? ', ' . $rate . '% success' : '' }}</span>
                                </div>
                            @endif
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            @if($open)
                                <span class="{{ $f->is_overdue ? 'text-red-600 font-semibold' : 'text-textprimary dark:text-gray-200' }}">{{ $f->scheduled_for->format('d M Y') }}</span>
                                @if($f->is_overdue)<div class="text-[11px] text-red-500">{{ $f->scheduled_for->diffForHumans() }}</div>@endif
                            @else
                                <span class="text-xs text-textsecondary">{{ $f->done_at?->format('d M Y, h:i a') }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-xs">{{ Followup::TAGS[$f->tag] ?? '—' }}</td>
                        <td class="px-4 py-3 text-xs text-textsecondary max-w-xs">
                            {{ \Illuminate\Support\Str::limit($f->note, 120) }}
                            @if($f->order)<div><a href="{{ route('admin.orders.show', $f->order) }}" class="text-primary hover:underline">Order #{{ $f->order->order_number }}</a></div>@endif
                            @if($f->result_note)<div class="mt-1 text-textprimary dark:text-gray-300"><strong>Result:</strong> {{ $f->result_note }}</div>@endif
                        </td>
                        <td class="px-4 py-3 text-xs">{{ $f->assignee?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            @if($open)
                                <button type="button" @click="logFor = (logFor === {{ $f->id }} ? null : {{ $f->id }})"
                                        class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-primary text-white hover:bg-primary/90">Log call</button>
                            @else
                                <form method="POST" action="{{ route('admin.followups.reopen', $f) }}" class="inline">
                                    @csrf @method('PATCH')
                                    <button class="px-3 py-1.5 text-xs rounded-lg border border-gray-200 hover:border-primary">Re-open</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                    @if($open)
                    <tr x-show="logFor === {{ $f->id }}" x-cloak class="bg-gray-50 dark:bg-gray-700/30">
                        <td colspan="7" class="px-5 py-4">
                            <form method="POST" action="{{ route('admin.followups.complete', $f) }}" class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
                                @csrf @method('PATCH')
                                <div class="md:col-span-2">
                                    <label class="block text-xs text-textsecondary mb-1">What happened on the call?</label>
                                    <input name="result_note" maxlength="2000" placeholder="e.g. Happy with the shirt, will order 2 more next week" class="{{ $input }} w-full">
                                </div>
                                <div>
                                    <label class="block text-xs text-textsecondary mb-1">Next follow-up (optional)</label>
                                    <input type="date" name="next_date" min="{{ today()->addDay()->toDateString() }}" class="{{ $input }} w-full">
                                </div>
                                <div class="flex gap-2">
                                    <button name="status" value="done" class="flex-1 px-3 py-2 text-xs font-semibold rounded-xl bg-green-600 text-white hover:bg-green-700">Done</button>
                                    <button name="status" value="negative" class="flex-1 px-3 py-2 text-xs font-semibold rounded-xl bg-red-500 text-white hover:bg-red-600">Negative</button>
                                </div>
                            </form>
                        </td>
                    </tr>
                    @endif
                @empty
                    <tr><td colspan="7" class="px-5 py-12 text-center text-textsecondary">
                        {{ $tab === 'today' ? 'Nothing to call today.' : 'No follow-ups here.' }}
                    </td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($followups->hasPages())
            <div class="px-5 py-3 border-t border-gray-100 dark:border-gray-700">{{ $followups->links() }}</div>
        @endif
    </div>
</div>

@endsection
