@extends('layouts.admin')

@section('title', 'Order Distribution')
@section('page-title', 'Order Distribution')

@section('breadcrumb')
    <span class="text-textsecondary">/</span>
    <span class="text-textprimary font-medium">Order Distribution</span>
@endsection

@php
    use App\Services\OrderDistributor;
    $input = 'px-3 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700/50 text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary';
@endphp

@section('content')

@if(session('success'))
    <div class="mb-4 p-3.5 rounded-xl bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-sm text-green-700 dark:text-green-400">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="mb-4 p-3.5 rounded-xl bg-red-50 border border-red-200 text-sm text-red-700">{{ $errors->first() }}</div>
@endif

<div class="mb-5 p-4 rounded-xl bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 text-sm text-blue-800 dark:text-blue-300">
    Orders that arrive from your website, the plugin or the API without anyone responsible are handed to the staff ticked below.
    Orders made by hand in the CRM stay with whoever made them.
    Staff whose role does not have <strong>view_all_orders</strong> only see the orders assigned to them.
</div>

<form method="POST" action="{{ route('admin.distribution.update') }}" class="space-y-5">
    @csrf

    <div class="p-5 rounded-2xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <label class="block text-xs font-medium text-textsecondary mb-1">How to share orders</label>
            <select name="mode" class="{{ $input }} w-full">
                @foreach(OrderDistributor::MODES as $k => $v)
                    <option value="{{ $k }}" @selected($settings['mode'] === $k)>{{ $v }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-textsecondary mb-1">Max open orders per person (0 = no limit)</label>
            <input type="number" name="max_open" min="0" value="{{ $settings['max_open'] }}" class="{{ $input }} w-full">
            <p class="text-[11px] text-textsecondary mt-1">Open = pending, confirmed, processing or on hold. When everyone is full, new orders wait in the queue.</p>
        </div>
        <div class="flex items-start pt-5">
            <label class="flex items-start gap-2 text-sm">
                <input type="hidden" name="sticky" value="0">
                <input type="checkbox" name="sticky" value="1" class="mt-0.5" @checked($settings['sticky'])>
                <span>Returning customers go back to the person who handled them before (if they take orders)</span>
            </label>
        </div>
    </div>

    <div class="rounded-2xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100 dark:border-gray-700 flex flex-wrap items-center justify-between gap-2">
            <span class="text-sm font-semibold text-textprimary dark:text-white">Who receives orders</span>
            <span class="text-xs {{ $queued ? 'text-amber-600 font-semibold' : 'text-textsecondary' }}">{{ $queued }} order(s) waiting in the queue</span>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-700/50">
                <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                    <th class="px-5 py-2.5">Receives orders</th>
                    <th class="px-4 py-2.5">Staff</th>
                    <th class="px-4 py-2.5">Role</th>
                    <th class="px-4 py-2.5 text-center">Open orders now</th>
                    <th class="px-4 py-2.5 text-center">Assigned today</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse($staff as $s)
                    <tr class="{{ $s->is_banned ? 'opacity-50' : '' }}">
                        <td class="px-5 py-3"><input type="checkbox" name="receivers[]" value="{{ $s->id }}" @checked($s->receives_orders) @disabled($s->is_banned)></td>
                        <td class="px-4 py-3 font-medium text-textprimary dark:text-white">{{ $s->name }} @if($s->is_banned)<span class="text-xs text-red-500">(disabled)</span>@endif</td>
                        <td class="px-4 py-3 text-xs">{{ $s->role_display_name }}</td>
                        <td class="px-4 py-3 text-center {{ $settings['max_open'] && $s->open_orders >= $settings['max_open'] ? 'text-red-600 font-semibold' : '' }}">
                            {{ $s->open_orders }}{{ $settings['max_open'] ? ' / ' . $settings['max_open'] : '' }}
                        </td>
                        <td class="px-4 py-3 text-center">{{ $s->assigned_today }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-8 text-center text-textsecondary">No staff yet. Add staff on the Users page.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="flex flex-wrap gap-2">
        <button class="px-5 py-2.5 text-sm font-semibold bg-primary text-white rounded-xl hover:bg-primary/90">Save</button>
    </div>
</form>

@if($queued)
<form method="POST" action="{{ route('admin.distribution.run') }}" class="mt-3">
    @csrf
    <button class="px-5 py-2.5 text-sm font-semibold rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 hover:border-primary">Assign queued orders now</button>
</form>
@endif

@endsection
