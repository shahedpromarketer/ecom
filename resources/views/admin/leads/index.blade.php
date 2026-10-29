@extends('layouts.admin')

@section('title', 'Leads')
@section('page-title', 'Leads / Pre-orders')

@section('breadcrumb')
    <span class="text-textsecondary">/</span>
    <span class="text-textprimary font-medium">Leads</span>
@endsection

@php
    use App\Models\Lead;
    use App\Http\Controllers\Admin\LeadController;
    $input = 'px-3 py-2 text-sm rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700/50 text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary';
    $canAll = auth()->user()->can('view_all_leads');
    $canManage = auth()->user()->can('manage_orders');
    $statusColor = ['pending' => 'bg-amber-100 text-amber-800', 'confirmed' => 'bg-green-100 text-green-700', 'canceled' => 'bg-red-100 text-red-700'];
@endphp

@section('content')

@if(session('success'))
    <div class="mb-4 p-3.5 rounded-xl bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-sm text-green-700 dark:text-green-400">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="mb-4 p-3.5 rounded-xl bg-red-50 border border-red-200 text-sm text-red-700">{{ $errors->first() }}</div>
@endif

<div x-data="{ panel: null, selected: [], open: null }">

    {{-- Actions --}}
    @if($canManage)
    <div class="mb-4 flex flex-wrap gap-2">
        <button type="button" @click="panel = panel === 'add' ? null : 'add'" class="px-4 py-2 text-sm font-semibold rounded-xl bg-primary text-white hover:bg-primary/90">+ Add lead</button>
        @if($canAll)
            <button type="button" @click="panel = panel === 'import' ? null : 'import'" class="px-4 py-2 text-sm font-semibold rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 hover:border-primary">Import leads</button>
        @endif
    </div>

    {{-- Add one --}}
    <form method="POST" action="{{ route('admin.leads.store') }}" x-show="panel === 'add'" x-cloak
          class="mb-5 p-5 rounded-2xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 grid grid-cols-1 md:grid-cols-3 gap-3">
        @csrf
        <input name="phone" required placeholder="Mobile number *" class="{{ $input }}">
        <input name="name" placeholder="Name" class="{{ $input }}">
        <select name="source" class="{{ $input }}">
            @foreach(Lead::SOURCES as $k => $v)<option value="{{ $k }}" @selected($k === 'messenger')>{{ $v }}</option>@endforeach
        </select>
        <input name="address" placeholder="Address" class="{{ $input }} md:col-span-2">
        @if($canAll)
            <select name="assigned_to" class="{{ $input }}">
                <option value="">Assign to me</option>
                @foreach($staff as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach
            </select>
        @endif
        <input name="interest" placeholder="Asked about (product, size…)" class="{{ $input }} md:col-span-2">
        <input name="note" placeholder="Note" class="{{ $input }}">
        <div class="md:col-span-3"><button class="px-5 py-2.5 text-sm font-semibold rounded-xl bg-primary text-white hover:bg-primary/90">Save lead</button></div>
    </form>

    {{-- Import --}}
    @if($canAll)
    <form method="POST" action="{{ route('admin.leads.import') }}" enctype="multipart/form-data" x-show="panel === 'import'" x-cloak
          class="mb-5 p-5 rounded-2xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 space-y-3">
        @csrf
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs text-textsecondary mb-1">Mobile numbers — one per line, a name beside it if you have one</label>
                <textarea name="numbers" rows="7" placeholder="01712345678 Rahim&#10;01812345678, Karim&#10;+880 1912-345678" class="{{ $input }} w-full font-mono"></textarea>
                <label class="block text-xs text-textsecondary mt-3 mb-1">…or a CSV file (the number in any column, the name beside it)</label>
                <input type="file" name="file" accept=".csv,.txt" class="text-sm">
            </div>
            <div class="space-y-3">
                <div>
                    <label class="block text-xs text-textsecondary mb-1">Source</label>
                    <select name="source" class="{{ $input }} w-full">
                        @foreach(Lead::SOURCES as $k => $v)<option value="{{ $k }}" @selected($k === 'import')>{{ $v }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-textsecondary mb-1">Note added to every lead</label>
                    <input name="note" class="{{ $input }} w-full" placeholder="e.g. Eid campaign list">
                </div>
                <div>
                    <label class="block text-xs text-textsecondary mb-1">Share equally between (leave empty for unassigned)</label>
                    <div class="grid grid-cols-2 gap-1 max-h-32 overflow-y-auto p-2 rounded-xl border border-gray-200 dark:border-gray-600">
                        @foreach($staff as $s)
                            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="staff[]" value="{{ $s->id }}"> {{ $s->name }}</label>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        <p class="text-xs text-textsecondary">Numbers that already have an open lead are skipped. Invalid numbers are ignored.</p>
        <button class="px-5 py-2.5 text-sm font-semibold rounded-xl bg-primary text-white hover:bg-primary/90">Import</button>
    </form>
    @endif
    @endif

    {{-- Tabs --}}
    <div class="mb-3 flex flex-wrap gap-2">
        @foreach(LeadController::TABS as $key => $label)
            <a href="{{ route('admin.leads.index', array_merge(request()->except(['tab', 'page']), ['tab' => $key])) }}"
               class="px-4 py-2 rounded-xl text-sm font-medium border transition-colors
                      {{ $tab === $key ? 'bg-primary text-white border-primary' : 'bg-white dark:bg-gray-800 text-textprimary dark:text-gray-200 border-gray-200 dark:border-gray-700 hover:border-primary/40' }}">
                {{ $label }}
                @isset($counts[$key])<span class="ml-1 px-1.5 py-0.5 rounded-md text-xs {{ $tab === $key ? 'bg-white/20' : 'bg-gray-100 dark:bg-gray-700' }}">{{ $counts[$key] }}</span>@endisset
            </a>
        @endforeach
        @unless($canAll)<span class="self-center text-xs text-textsecondary ml-2">Showing leads assigned to you</span>@endunless
    </div>

    {{-- Filters --}}
    <x-admin.saved-filters page="leads" />
    <form method="GET" class="mb-4 flex flex-wrap items-center gap-2">
        <input type="hidden" name="tab" value="{{ $tab }}">
        <input name="search" value="{{ request('search') }}" placeholder="Name or phone…" class="{{ $input }} w-52">
        <select name="source" class="{{ $input }}">
            <option value="">Any source</option>
            @foreach(Lead::SOURCES as $k => $v)<option value="{{ $k }}" @selected(request('source') === $k)>{{ $v }}</option>@endforeach
        </select>
        @if($canAll)
            <select name="assigned_to" class="{{ $input }}">
                <option value="">Anyone</option>
                <option value="none" @selected(request('assigned_to') === 'none')>Unassigned</option>
                @foreach($staff as $s)<option value="{{ $s->id }}" @selected((string) request('assigned_to') === (string) $s->id)>{{ $s->name }}</option>@endforeach
            </select>
        @endif
        <label class="flex items-center gap-1 text-sm"><input type="checkbox" name="star" value="1" @checked(request('star'))> ⭐ only</label>
        <button class="px-4 py-2 text-sm font-semibold bg-primary text-white rounded-xl hover:bg-primary/90">Filter</button>
    </form>

    {{-- Bulk --}}
    <form method="POST" action="{{ route('admin.leads.bulk') }}" x-show="selected.length" x-cloak
          class="mb-3 p-3 rounded-xl bg-primary/5 border border-primary/20 flex flex-wrap items-center gap-2">
        @csrf
        <template x-for="id in selected" :key="id"><input type="hidden" name="ids[]" :value="id"></template>
        <span class="text-sm font-medium"><span x-text="selected.length"></span> selected</span>
        <button name="action" value="read" class="px-3 py-2 text-xs rounded-xl bg-white border border-gray-200 hover:border-primary">Mark read</button>
        <button name="action" value="star" class="px-3 py-2 text-xs rounded-xl bg-white border border-gray-200 hover:border-primary">⭐ Star</button>
        @if($canManage)<button name="action" value="cancel" class="px-3 py-2 text-xs rounded-xl bg-white border border-gray-200 hover:border-red-400">Cancel</button>@endif
        @if($canAll)
            <select name="assigned_to" class="{{ $input }}">
                <option value="">Choose staff…</option>
                @foreach($staff as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach
            </select>
            <button name="action" value="assign" class="px-3 py-2 text-xs rounded-xl bg-white border border-gray-200 hover:border-primary">Transfer</button>
            <button name="action" value="delete" onclick="return confirm('Delete the selected leads?')" class="px-3 py-2 text-xs rounded-xl bg-red-500 text-white hover:bg-red-600">Delete</button>
        @endif
    </form>

    {{-- List --}}
    <div class="rounded-2xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                    <tr class="text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        <th class="pl-5 py-3 w-8"></th>
                        <th class="px-4 py-3">Lead</th>
                        <th class="px-4 py-3">History</th>
                        <th class="px-4 py-3">Asked about / note</th>
                        <th class="px-4 py-3">Source</th>
                        <th class="px-4 py-3">Assigned</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse($leads as $lead)
                    @php $c = $lead->customer; @endphp
                    <tr class="{{ ! $lead->read_at && $lead->status === 'pending' ? 'bg-blue-50/40 dark:bg-blue-900/10' : '' }}">
                        <td class="pl-5 py-3"><input type="checkbox" value="{{ $lead->id }}" x-model.number="selected"></td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-1.5">
                                @if($lead->is_star)<span title="Star lead">⭐</span>@endif
                                <a href="{{ route('admin.customers.show', $c) }}" class="font-medium text-textprimary dark:text-white hover:text-primary">{{ $c->name ?: 'Unknown' }}</a>
                                @if(! $lead->read_at && $lead->status === 'pending')<span class="px-1.5 py-0.5 text-[10px] font-bold rounded bg-blue-100 text-blue-700">NEW</span>@endif
                            </div>
                            <div class="flex items-center gap-2 text-xs mt-0.5">
                                <a href="tel:{{ $c->phone }}" class="font-mono text-primary hover:underline">{{ $c->phone }}</a>
                                <a href="https://wa.me/88{{ $c->phone }}" target="_blank" rel="noopener" class="text-green-600 hover:underline">WhatsApp</a>
                            </div>
                            <div class="text-[11px] text-textsecondary mt-0.5">{{ $lead->created_at->diffForHumans() }}</div>
                        </td>
                        <td class="px-4 py-3 text-xs">
                            @if($c->total_orders)
                                {{ $c->total_orders }} orders · {{ $c->delivered_orders }} delivered
                                @if($c->success_rate !== null)<div class="{{ $c->success_rate >= 80 ? 'text-green-600' : ($c->success_rate >= 50 ? 'text-amber-600' : 'text-red-600') }}">{{ $c->success_rate }}% success</div>@endif
                            @else
                                <span class="text-textsecondary">New customer</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-xs max-w-xs">
                            @if($lead->interest)<div class="text-textprimary dark:text-gray-200">{{ $lead->interest }}</div>@endif
                            @if($lead->note)<div class="text-textsecondary whitespace-pre-line">{{ \Illuminate\Support\Str::limit($lead->note, 160) }}</div>@endif
                            @if($lead->cancel_reason)<div class="text-red-600">Canceled: {{ $lead->cancel_reason }}</div>@endif
                        </td>
                        <td class="px-4 py-3 text-xs">{{ Lead::SOURCES[$lead->source] ?? '—' }}</td>
                        <td class="px-4 py-3 text-xs">{{ $lead->assignee?->name ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 text-xs rounded-full {{ $statusColor[$lead->status] ?? '' }}">{{ Lead::STATUSES[$lead->status] ?? $lead->status }}</span>
                            @if($lead->order)<div class="mt-1"><a href="{{ route('admin.orders.show', $lead->order) }}" class="text-xs text-primary hover:underline">#{{ $lead->order->order_number }}</a></div>@endif
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            @if($lead->status === 'pending' && $canManage)
                                <a href="{{ route('admin.orders.create', ['phone' => $c->phone]) }}" class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-primary text-white hover:bg-primary/90">Create order</a>
                            @endif
                            <button type="button" @click="open = open === {{ $lead->id }} ? null : {{ $lead->id }}" class="ml-1 px-2 py-1.5 text-xs rounded-lg border border-gray-200 dark:border-gray-600 hover:border-primary">More</button>
                        </td>
                    </tr>
                    <tr x-show="open === {{ $lead->id }}" x-cloak class="bg-gray-50 dark:bg-gray-700/30">
                        <td colspan="8" class="px-5 py-4">
                            <div class="flex flex-wrap items-end gap-2">
                                <form method="POST" action="{{ route('admin.leads.update', $lead) }}" class="flex gap-2 items-end">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="action" value="note">
                                    <input name="note" required placeholder="Add a call note…" class="{{ $input }} w-72">
                                    <button class="px-3 py-2 text-xs font-semibold rounded-xl bg-primary text-white">Save note</button>
                                </form>
                                <form method="POST" action="{{ route('admin.leads.update', $lead) }}">@csrf @method('PATCH')
                                    <input type="hidden" name="action" value="star"><button class="px-3 py-2 text-xs rounded-xl bg-white border border-gray-200">{{ $lead->is_star ? 'Unstar' : '⭐ Star' }}</button></form>
                                @if(! $lead->read_at)
                                <form method="POST" action="{{ route('admin.leads.update', $lead) }}">@csrf @method('PATCH')
                                    <input type="hidden" name="action" value="read"><button class="px-3 py-2 text-xs rounded-xl bg-white border border-gray-200">Mark read</button></form>
                                @endif
                                @if($lead->status === 'pending' && $canManage)
                                    <form method="POST" action="{{ route('admin.leads.update', $lead) }}">@csrf @method('PATCH')
                                        <input type="hidden" name="action" value="confirm"><button class="px-3 py-2 text-xs rounded-xl bg-green-600 text-white" title="Confirmed outside the CRM">Confirm (no order)</button></form>
                                    <form method="POST" action="{{ route('admin.leads.update', $lead) }}" class="flex gap-1">@csrf @method('PATCH')
                                        <input type="hidden" name="action" value="cancel">
                                        <select name="cancel_reason" class="{{ $input }}">
                                            @foreach(Lead::CANCEL_REASONS as $r)<option>{{ $r }}</option>@endforeach
                                        </select>
                                        <button class="px-3 py-2 text-xs rounded-xl bg-red-500 text-white">Cancel lead</button></form>
                                @elseif($lead->status !== 'pending' && $canManage)
                                    <form method="POST" action="{{ route('admin.leads.update', $lead) }}">@csrf @method('PATCH')
                                        <input type="hidden" name="action" value="reopen"><button class="px-3 py-2 text-xs rounded-xl bg-white border border-gray-200">Re-open</button></form>
                                @endif
                                <a href="{{ route('admin.customers.show', $c) }}#followups" class="px-3 py-2 text-xs rounded-xl bg-white border border-gray-200 hover:border-primary">Schedule follow-up</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-5 py-12 text-center text-textsecondary">No leads here.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($leads->hasPages())
            <div class="px-5 py-3 border-t border-gray-100 dark:border-gray-700">{{ $leads->links() }}</div>
        @endif
    </div>
</div>

@endsection
