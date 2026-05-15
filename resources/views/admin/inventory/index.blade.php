@extends('layouts.admin')

@section('title', 'Inventory Management')
@section('page-title', 'Inventory Management')

@section('breadcrumb')
    <span class="text-textsecondary">/</span>
    <span class="text-textprimary font-medium">Inventory</span>
@endsection

@section('content')

@if(session('success'))
    <div class="mb-5 flex items-center gap-3 p-3.5 rounded-xl bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800">
        <svg class="w-4 h-4 text-green-600 dark:text-green-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/></svg>
        <p class="text-sm text-green-700 dark:text-green-400">{{ session('success') }}</p>
    </div>
@endif

<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-border dark:border-gray-700 shadow-sm p-5 flex items-center gap-4">
        <div class="w-12 h-12 bg-blue-50 dark:bg-blue-900/20 rounded-2xl flex items-center justify-center">
            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/></svg>
        </div>
        <div>
            <p class="text-2xl font-bold text-textprimary dark:text-gray-100">{{ number_format($totalSkus) }}</p>
            <p class="text-xs text-textsecondary dark:text-gray-400">Total SKUs</p>
        </div>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-border dark:border-gray-700 shadow-sm p-5 flex items-center gap-4">
        <div class="w-12 h-12 bg-red-50 dark:bg-red-900/20 rounded-2xl flex items-center justify-center">
            <svg class="w-6 h-6 text-danger dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
        </div>
        <div>
            <p class="text-2xl font-bold text-danger dark:text-red-400">{{ number_format($outOfStock) }}</p>
            <p class="text-xs text-textsecondary dark:text-gray-400">Out of Stock</p>
        </div>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-border dark:border-gray-700 shadow-sm p-5 flex items-center gap-4">
        <div class="w-12 h-12 bg-amber-50 dark:bg-amber-900/20 rounded-2xl flex items-center justify-center">
            <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
        </div>
        <div>
            <p class="text-2xl font-bold text-amber-600 dark:text-amber-400">{{ number_format($lowStock) }}</p>
            <p class="text-xs text-textsecondary dark:text-gray-400">Low Stock (≤10)</p>
        </div>
    </div>
</div>

<x-admin.card>
    <x-slot:header>
        <h3 class="font-heading font-semibold text-textprimary dark:text-gray-100">Stock Levels</h3>
        <div id="saveToast" class="hidden items-center gap-2 px-3 py-1.5 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 text-xs font-medium rounded-xl">
            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/></svg>
            Saved!
        </div>
    </x-slot:header>

    <div class="flex flex-wrap items-center gap-2 px-6 py-3 border-b border-border dark:border-gray-700">
        @foreach(['all' => 'All', 'in' => 'In Stock', 'low' => 'Low Stock', 'out' => 'Out of Stock'] as $val => $label)
        <a href="{{ request()->fullUrlWithQuery(['filter' => $val]) }}"
           class="px-3 py-1.5 text-xs font-medium rounded-xl transition-colors {{ $filter === $val ? 'bg-accent text-white' : 'border border-border dark:border-gray-600 text-textsecondary dark:text-gray-400 hover:text-textprimary dark:hover:text-gray-200' }}">
            {{ $label }}
        </a>
        @endforeach

        <form method="GET" action="{{ route('admin.inventory.index') }}" class="ml-auto flex gap-2">
            <input type="hidden" name="filter" value="{{ $filter }}">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search product or SKU…"
                   class="px-3 py-1.5 text-xs rounded-xl border border-border dark:border-gray-600 bg-white dark:bg-gray-700/50 text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary dark:focus:border-primary transition-all duration-200">
            <button type="submit" class="px-3 py-1.5 text-xs font-semibold bg-primary text-white rounded-xl hover:bg-primary/90 transition-colors">Search</button>
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="border-b border-border dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                <tr>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Product</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Size</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Color</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">SKU</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Stock</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border dark:divide-gray-700" x-data="inventoryTable()">
                @forelse($variants as $variant)
                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors">
                    <td class="px-6 py-3 font-medium text-textprimary dark:text-gray-100 max-w-[200px] truncate">{{ $variant->product?->name }}</td>
                    <td class="px-4 py-3 text-textsecondary dark:text-gray-400">{{ $variant->size ?? '—' }}</td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-1.5">
                            @if($variant->color_hex)
                            <span class="w-3 h-3 rounded-full flex-shrink-0 border border-border dark:border-gray-600"
                                  style="background-color: {{ $variant->color_hex }}"></span>
                            @endif
                            <span class="text-textsecondary dark:text-gray-400">{{ $variant->color ?? '—' }}</span>
                        </div>
                    </td>
                    <td class="px-4 py-3 font-mono text-xs text-textsecondary dark:text-gray-400">{{ $variant->sku ?? '—' }}</td>
                    <td class="px-4 py-3">
                        <div x-data="{ editing: false, stock: {{ $variant->stock }}, saving: false }" class="flex items-center gap-2">
                            <span x-show="!editing" @click="editing = true"
                                  class="font-semibold text-textprimary dark:text-gray-100 cursor-pointer hover:text-accent transition-colors">
                                <span x-text="stock"></span>
                            </span>
                            <div x-show="editing" x-cloak class="flex items-center gap-1">
                                <input type="number" x-model.number="stock" min="0"
                                       @keydown.enter="saveStock({{ $variant->id }}, $data)"
                                       @keydown.escape="editing = false"
                                       class="w-20 px-2 py-1 text-sm font-semibold rounded-lg border border-accent bg-white dark:bg-gray-700/50 text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30">
                                <button @click="saveStock({{ $variant->id }}, $data)" :disabled="saving"
                                        class="p-1 text-green-600 dark:text-green-400 hover:text-green-700 transition-colors disabled:opacity-50">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                                </button>
                                <button @click="editing = false" class="p-1 text-danger hover:text-red-700 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        @if($variant->stock === 0)
                            <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-red-100 text-danger dark:bg-red-900/30 dark:text-red-400">Out of Stock</span>
                        @elseif($variant->stock <= 10)
                            <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">Low Stock</span>
                        @else
                            <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">In Stock</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-6 py-12 text-center text-textsecondary dark:text-gray-400">No variants found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($variants->hasPages())
    <div class="px-6 py-4 border-t border-border dark:border-gray-700">
        {{ $variants->links() }}
    </div>
    @endif
</x-admin.card>

@endsection

@push('scripts')
<script>
function inventoryTable() {
    return {};
}

function saveStock(variantId, component) {
    component.saving = true;
    const stock = component.stock;
    const toast = document.getElementById('saveToast');

    fetch(`/admin/inventory/variants/${variantId}`, {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        },
        body: JSON.stringify({ stock }),
    })
    .then(r => r.json())
    .then(data => {
        component.stock   = data.stock;
        component.editing = false;
        component.saving  = false;
        toast.classList.remove('hidden');
        toast.classList.add('flex');
        setTimeout(() => { toast.classList.add('hidden'); toast.classList.remove('flex'); }, 2000);
    })
    .catch(() => { component.saving = false; alert('Failed to save stock'); });
}
</script>
@endpush
