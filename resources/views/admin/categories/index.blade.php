@extends('layouts.admin')

@section('title', 'Categories')
@section('page-title', 'Categories')

@section('breadcrumb')
    <span class="text-textsecondary">/</span>
    <span class="text-textprimary font-medium">Categories</span>
@endsection

@section('content')

@if(session('success'))
    <div class="mb-5 flex items-center gap-3 p-3.5 rounded-xl bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800">
        <svg class="w-4 h-4 text-green-600 dark:text-green-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/></svg>
        <p class="text-sm text-green-700 dark:text-green-400">{{ session('success') }}</p>
    </div>
@endif

<x-admin.card>
    <x-slot:header>
        <h3 class="font-heading font-semibold text-textprimary dark:text-gray-100">Categories
            <span class="ml-2 px-2 py-0.5 text-xs font-medium rounded-full bg-gray-100 dark:bg-gray-700 text-textsecondary dark:text-gray-400">{{ $categories->total() }}</span>
        </h3>
        <a href="{{ route('admin.categories.create') }}"
           class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-semibold bg-primary hover:bg-primary/90 text-white rounded-xl transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            New Category
        </a>
    </x-slot:header>

    <form method="GET" action="{{ route('admin.categories.index') }}"
          class="flex flex-wrap gap-3 px-6 py-4 border-b border-border dark:border-gray-700">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search categories…"
               class="flex-1 min-w-[180px] px-4 py-2.5 text-sm rounded-xl border border-border dark:border-gray-600 bg-white dark:bg-gray-700/50 text-textprimary dark:text-gray-100 placeholder-textsecondary dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary dark:focus:border-primary transition-all duration-200">
        <select name="parent" class="px-4 py-2.5 text-sm rounded-xl border border-border dark:border-gray-600 bg-white dark:bg-gray-700/50 text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary dark:focus:border-primary transition-all duration-200">
            <option value="">All Categories</option>
            <option value="0" {{ request('parent') === '0' ? 'selected' : '' }}>Top-level only</option>
        </select>
        <button type="submit" class="px-4 py-2.5 text-sm font-semibold bg-primary hover:bg-primary/90 text-white rounded-xl transition-colors">Filter</button>
        <a href="{{ route('admin.categories.index') }}" class="px-4 py-2.5 text-sm font-medium border border-border dark:border-gray-600 text-textsecondary dark:text-gray-400 hover:text-textprimary dark:hover:text-gray-200 rounded-xl transition-colors">Clear</a>
    </form>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="border-b border-border dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                <tr>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Category</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Parent</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Products</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Sort</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border dark:divide-gray-700">
                @forelse($categories as $category)
                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors">
                    <td class="px-6 py-3">
                        <div class="flex items-center gap-3">
                            @if($category->image)
                                <img src="{{ Storage::url($category->image) }}" alt="{{ $category->name }}"
                                     class="w-8 h-8 object-cover rounded-lg flex-shrink-0">
                            @else
                                <div class="w-8 h-8 bg-gray-100 dark:bg-gray-700 rounded-lg flex-shrink-0 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-textsecondary" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75"/></svg>
                                </div>
                            @endif
                            <div>
                                <p class="font-medium text-textprimary dark:text-gray-100">{{ $category->name }}</p>
                                <p class="text-xs text-textsecondary dark:text-gray-400">/{{ $category->slug }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-textsecondary dark:text-gray-400">{{ $category->parent?->name ?? '—' }}</td>
                    <td class="px-4 py-3 text-textsecondary dark:text-gray-400">{{ $category->products_count ?? 0 }}</td>
                    <td class="px-4 py-3 text-textsecondary dark:text-gray-400">{{ $category->sort_order }}</td>
                    <td class="px-4 py-3">
                        <button id="toggle-btn-{{ $category->id }}"
                                onclick="toggleCategoryStatus(this)"
                                data-id="{{ $category->id }}"
                                data-url="{{ route('admin.categories.toggle', $category) }}"
                                class="relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 focus:outline-none {{ $category->is_active ? 'bg-green-500' : 'bg-gray-300 dark:bg-gray-600' }}"
                                role="switch">
                            <span class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow transition duration-200 {{ $category->is_active ? 'translate-x-4' : 'translate-x-0' }}"></span>
                        </button>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            <a href="{{ route('admin.categories.edit', $category) }}"
                               class="px-2.5 py-1 text-xs font-medium bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors">Edit</a>
                            <button type="button"
                                    onclick="confirmDelete({{ $category->id }}, '{{ route('admin.categories.destroy', $category) }}')"
                                    class="px-2.5 py-1 text-xs font-medium border border-red-200 dark:border-red-800 text-danger rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                                Delete
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center">
                        <svg class="w-10 h-10 mx-auto mb-2 text-border dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z"/>
                        </svg>
                        <p class="text-sm text-textsecondary dark:text-gray-400">No categories found.</p>
                        <a href="{{ route('admin.categories.create') }}" class="mt-2 inline-block text-xs text-accent hover:underline">Create your first category</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($categories->hasPages())
    <div class="px-6 py-4 border-t border-border dark:border-gray-700">
        {{ $categories->links() }}
    </div>
    @endif

</x-admin.card>

@endsection

@push('scripts')
<script>
function toggleCategoryStatus(btn) {
    const url  = btn.dataset.url;
    const csrf = document.querySelector('meta[name="csrf-token"]').content;

    fetch(url, {
        method: 'PATCH',
        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
    })
    .then(r => r.json())
    .then(data => {
        const active = data.is_active;
        btn.className = btn.className
            .replace(/bg-green-500|bg-gray-300 dark:bg-gray-600/g, '')
            .trim();
        btn.classList.add(active ? 'bg-green-500' : 'bg-gray-300', 'dark:bg-gray-600');
        const knob = btn.querySelector('span');
        knob.classList.toggle('translate-x-4', active);
        knob.classList.toggle('translate-x-0', !active);
    })
    .catch(() => alert('Failed to update status.'));
}
</script>
@endpush
