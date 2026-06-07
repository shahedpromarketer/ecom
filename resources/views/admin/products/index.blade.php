@extends('layouts.admin')

@section('title', 'Products')
@section('page-title', 'Products')


@section('content')

@if(session('success'))
    <div class="mb-5 flex items-center gap-3 p-3.5 rounded-xl bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800">
        <svg class="w-4 h-4 text-green-600 dark:text-green-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/></svg>
        <p class="text-sm text-green-700 dark:text-green-400">{{ session('success') }}</p>
    </div>
@endif

<div x-data="bulkActions()">

<x-admin.card>
    <x-slot:header>
        <h3 class="font-heading font-semibold text-textprimary dark:text-gray-100">Products
            <span class="ml-2 px-2 py-0.5 text-xs font-medium rounded-full bg-gray-100 dark:bg-gray-700 text-textsecondary dark:text-gray-400">{{ $products->total() }}</span>
        </h3>
        <div class="flex items-center gap-2">
            <div x-show="selected.length > 0" x-cloak class="flex items-center gap-2">
                <span class="text-xs text-textsecondary dark:text-gray-400" x-text="selected.length + ' selected'"></span>
                <button @click="bulkDelete()"
                        class="px-3 py-1.5 text-xs font-semibold text-danger border border-red-200 dark:border-red-800 rounded-xl hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                    Delete Selected
                </button>
            </div>
            <a href="{{ route('admin.products.create') }}"
               class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-semibold bg-primary hover:bg-primary/90 text-white rounded-xl transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                New Product
            </a>
        </div>
    </x-slot:header>

    <form method="GET" action="{{ route('admin.products.index') }}"
          class="flex flex-wrap gap-3 px-6 py-4 border-b border-border dark:border-gray-700">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search products…"
               class="flex-1 min-w-[200px] px-4 py-2.5 text-sm rounded-xl border border-border dark:border-gray-600 bg-white dark:bg-gray-700/50 text-textprimary dark:text-gray-100 placeholder-textsecondary dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary dark:focus:border-primary transition-all duration-200">
        <select name="status" class="px-4 py-2.5 text-sm rounded-xl border border-border dark:border-gray-600 bg-white dark:bg-gray-700/50 text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary dark:focus:border-primary transition-all duration-200">
            <option value="">All Statuses</option>
            <option value="active"   {{ request('status') === 'active'   ? 'selected' : '' }}>Active</option>
            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
            <option value="draft"    {{ request('status') === 'draft'    ? 'selected' : '' }}>Draft</option>
        </select>
        <select name="category" class="px-4 py-2.5 text-sm rounded-xl border border-border dark:border-gray-600 bg-white dark:bg-gray-700/50 text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary dark:focus:border-primary transition-all duration-200">
            <option value="">All Categories</option>
            @foreach($categories as $cat)
                <option value="{{ $cat->id }}" {{ request('category') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
            @endforeach
        </select>
        <button type="submit" class="px-4 py-2.5 text-sm font-semibold bg-primary hover:bg-primary/90 text-white rounded-xl transition-colors">Filter</button>
        <a href="{{ route('admin.products.index') }}" class="px-4 py-2.5 text-sm font-medium border border-border dark:border-gray-600 text-textsecondary dark:text-gray-400 hover:text-textprimary dark:hover:text-gray-200 rounded-xl transition-colors">Clear</a>
    </form>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="border-b border-border dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                <tr>
                    <th class="px-6 py-3 w-10">
                        <input type="checkbox" @change="toggleAll($event)"
                               class="w-4 h-4 rounded border-border dark:border-gray-600 text-accent focus:ring-primary/30">
                    </th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Product</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Category</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Price</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Stock</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border dark:divide-gray-700">
                @forelse($products as $product)
                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors" :class="{ 'bg-primary/5 dark:bg-primary/10': selected.includes({{ $product->id }}) }">
                    <td class="px-6 py-3">
                        <input type="checkbox" :value="{{ $product->id }}" x-model="selected"
                               class="w-4 h-4 rounded border-border dark:border-gray-600 text-accent focus:ring-primary/30">
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            @if($product->primaryImage)
                                <img src="{{ Storage::url($product->primaryImage->image_path) }}" alt="{{ $product->name }}"
                                     class="w-10 h-10 object-cover rounded-xl flex-shrink-0">
                            @else
                                <div class="w-10 h-10 bg-gray-100 dark:bg-gray-700 rounded-xl flex-shrink-0 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-textsecondary" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581"/></svg>
                                </div>
                            @endif
                            <div class="min-w-0">
                                <p class="font-medium text-textprimary dark:text-gray-100 truncate max-w-[180px]">{{ $product->name }}</p>
                                <p class="text-xs text-textsecondary dark:text-gray-400">{{ $product->sku ?? '—' }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-sm text-textsecondary dark:text-gray-400">
                        {{ $product->categories->first()?->name ?? '—' }}
                    </td>
                    <td class="px-4 py-3">
                        <p class="font-semibold text-textprimary dark:text-gray-100">৳{{ number_format($product->price, 0) }}</p>
                        @if($product->sale_price)
                            <p class="text-xs text-green-600 dark:text-green-400">৳{{ number_format($product->sale_price, 0) }} sale</p>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        @php $totalStock = $product->variants->sum('stock'); @endphp
                        <span class="text-sm font-medium {{ $totalStock === 0 ? 'text-danger' : ($totalStock <= 10 ? 'text-amber-600 dark:text-amber-400' : 'text-textprimary dark:text-gray-100') }}">
                            {{ $totalStock }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        @php
                            $sc = match($product->status) {
                                'active'   => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                                'inactive' => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
                                default    => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
                            };
                        @endphp
                        <span class="px-2 py-0.5 text-xs font-medium rounded-full {{ $sc }}">{{ ucfirst($product->status) }}</span>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            <a href="{{ route('admin.products.edit', $product) }}"
                               class="px-2.5 py-1 text-xs font-medium bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors">Edit</a>
                            <form method="POST" action="{{ route('admin.products.clone', $product) }}" class="inline">
                                @csrf
                                <button type="submit"
                                        class="px-2.5 py-1 text-xs font-medium border border-gray-200 dark:border-gray-700 text-textsecondary dark:text-gray-400 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                    Clone
                                </button>
                            </form>
                            <button type="button"
                                    onclick="confirmDelete({{ $product->id }}, '{{ route('admin.products.destroy', $product) }}')"
                                    class="px-2.5 py-1 text-xs font-medium border border-red-200 dark:border-red-800 text-danger rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                                Delete
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-12 text-center">
                        <svg class="w-10 h-10 mx-auto mb-2 text-border dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z"/>
                        </svg>
                        <p class="text-sm text-textsecondary dark:text-gray-400">No products found.</p>
                        <a href="{{ route('admin.products.create') }}" class="mt-2 inline-block text-xs text-accent hover:underline">Add your first product</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($products->hasPages())
    <div class="px-6 py-4 border-t border-border dark:border-gray-700">
        {{ $products->links() }}
    </div>
    @endif

</x-admin.card>
</div>

@endsection

@push('scripts')
<script>
function bulkActions() {
    return {
        selected: [],
        toggleAll(e) {
            if (e.target.checked) {
                this.selected = @json($products->pluck('id'));
            } else {
                this.selected = [];
            }
        },
        bulkDelete() {
            if (!this.selected.length) return;
            if (!confirm(`Delete ${this.selected.length} selected product(s)? This cannot be undone.`)) return;
            const csrf = document.querySelector('meta[name="csrf-token"]').content;
            fetch('{{ route('admin.products.bulkAction') }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ action: 'delete', ids: this.selected }),
            })
            .then(r => r.json())
            .then(() => window.location.reload())
            .catch(() => alert('Failed to delete selected products.'));
        },
    };
}
</script>
@endpush
