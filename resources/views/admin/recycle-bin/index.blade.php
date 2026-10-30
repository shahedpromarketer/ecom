@extends('layouts.admin')

@section('title', 'Recycle Bin')
@section('page-title', 'Recycle Bin')

@section('breadcrumb')
    <span class="text-textsecondary">/</span>
    <span class="text-textprimary font-medium">Recycle Bin</span>
@endsection

@php use App\Http\Controllers\Admin\RecycleBinController; @endphp

@section('content')

@if(session('success'))
    <div class="mb-4 p-3.5 rounded-xl bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-sm text-green-700 dark:text-green-400">{{ session('success') }}</div>
@endif

<p class="mb-4 text-sm text-textsecondary">Deleted items wait here. Restore puts them back exactly as they were. Deleting permanently cannot be undone.</p>

<div class="mb-4 flex flex-wrap gap-2">
    @foreach(RecycleBinController::TYPES as $key => $t)
        <a href="{{ route('admin.recycle-bin.index', ['type' => $key]) }}"
           class="px-4 py-2 rounded-xl text-sm font-medium border {{ $type === $key ? 'bg-primary text-white border-primary' : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 hover:border-primary/40' }}">
            {{ $t['label'] }} <span class="ml-1 px-1.5 py-0.5 rounded-md text-xs {{ $type === $key ? 'bg-white/20' : 'bg-gray-100 dark:bg-gray-700' }}">{{ $counts[$key] }}</span>
        </a>
    @endforeach
</div>

<form method="POST" x-data="{ selected: [] }" class="rounded-2xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 overflow-hidden">
    @csrf
    <div class="px-5 py-3 border-b border-gray-100 dark:border-gray-700 flex flex-wrap items-center gap-2">
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" @change="selected = $event.target.checked ? {{ json_encode($items->pluck('id')) }} : []"> Select all on this page
        </label>
        <template x-for="id in selected" :key="id"><input type="hidden" name="ids[]" :value="id"></template>
        <span class="text-xs text-textsecondary" x-show="selected.length"><span x-text="selected.length"></span> selected</span>
        <div class="ml-auto flex gap-2">
            <button formaction="{{ route('admin.recycle-bin.restore', $type) }}" :disabled="!selected.length"
                    class="px-4 py-2 text-xs font-semibold rounded-xl bg-green-600 text-white hover:bg-green-700 disabled:opacity-40">Restore</button>
            @if(auth()->user()->hasFullAccess())
                <button formaction="{{ route('admin.recycle-bin.destroy', $type) }}" :disabled="!selected.length"
                        onclick="return confirm('Delete the selected items permanently? This cannot be undone.')"
                        class="px-4 py-2 text-xs font-semibold rounded-xl bg-red-500 text-white hover:bg-red-600 disabled:opacity-40">Delete permanently</button>
            @endif
        </div>
    </div>

    <table class="ep-stack w-full text-sm">
        <thead class="bg-gray-50 dark:bg-gray-700/50">
            <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                <th class="pl-5 py-2.5 w-8"></th>
                <th class="px-4 py-2.5">Item</th>
                <th class="px-4 py-2.5">Deleted</th>
                <th class="px-4 py-2.5">By</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
            @forelse($items as $item)
                <tr>
                    <td class="pl-5 py-3"><input type="checkbox" value="{{ $item->id }}" x-model.number="selected"></td>
                    <td class="px-4 py-3">
                        @switch($type)
                            @case('orders')
                                <span class="font-medium">#{{ $item->order_number }}</span>
                                <span class="text-xs text-textsecondary">· {{ $item->shipping_name }} · {{ $item->shipping_phone }} · ৳{{ number_format($item->total) }} · {{ ucwords(str_replace('_', ' ', $item->status)) }}</span>
                                @break
                            @case('customers')
                                <span class="font-medium">{{ $item->name ?: 'Unknown' }}</span>
                                <span class="text-xs text-textsecondary font-mono">· {{ $item->phone }} · {{ $item->total_orders }} orders</span>
                                @break
                            @case('products')
                                <span class="font-medium">{{ $item->name }}</span>
                                <span class="text-xs text-textsecondary">· ৳{{ number_format($item->sale_price ?? $item->price) }}</span>
                                @break
                            @default
                                <span class="font-medium">{{ $item->name }}</span>
                        @endswitch
                    </td>
                    <td class="px-4 py-3 text-xs text-textsecondary">{{ $item->deleted_at->format('d M Y, h:i a') }} ({{ $item->deleted_at->diffForHumans() }})</td>
                    <td class="px-4 py-3 text-xs">{{ $item->deletedByUser?->name ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="px-5 py-12 text-center text-textsecondary">Nothing deleted here.</td></tr>
            @endforelse
        </tbody>
    </table>
    @if($items->hasPages())
        <div class="px-5 py-3 border-t border-gray-100 dark:border-gray-700">{{ $items->links() }}</div>
    @endif
</form>

@endsection
