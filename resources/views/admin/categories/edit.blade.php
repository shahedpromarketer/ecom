@extends('layouts.admin')

@section('title', 'Edit Category')
@section('page-title', 'Categories')

@section('breadcrumb')
    <span class="text-textsecondary">/</span>
    <a href="{{ route('admin.categories.index') }}" class="text-textsecondary hover:text-textprimary dark:hover:text-gray-200 transition-colors">Categories</a>
    <span class="text-textsecondary">/</span>
    <span class="text-textprimary font-medium">Edit</span>
@endsection

@section('content')

<form method="POST" action="{{ route('admin.categories.update', $category) }}" enctype="multipart/form-data"
      x-data="categoryForm()">
    @csrf
    @method('PUT')

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

        <div class="xl:col-span-2 space-y-6">

            <x-admin.card>
                <x-slot:header>
                    <h3 class="font-heading font-semibold text-textprimary dark:text-gray-100">Category Details</h3>
                </x-slot:header>
                <div class="p-6 space-y-5">

                    <div>
                        <label class="block text-sm font-medium mb-1.5 text-textprimary dark:text-gray-200">
                            Name <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="name" x-model="name"
                               value="{{ old('name', $category->name) }}" required
                               class="w-full px-4 py-2.5 text-sm rounded-xl border transition-all duration-200
                                      focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary dark:focus:border-primary
                                      text-textprimary dark:text-gray-100 placeholder-textsecondary dark:placeholder-gray-500
                                      @error('name') border-danger bg-red-50 dark:bg-red-900/10 dark:border-red-700
                                      @else border-border dark:border-gray-600 bg-white dark:bg-gray-700/50 @enderror">
                        @error('name')<p class="mt-1.5 text-xs text-danger dark:text-red-400">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1.5 text-textprimary dark:text-gray-200">
                            Slug <span class="text-danger">*</span>
                        </label>
                        <div class="flex rounded-xl overflow-hidden border @error('slug') border-danger @else border-border dark:border-gray-600 @enderror">
                            <span class="px-3 py-2.5 text-sm bg-gray-50 dark:bg-gray-700 text-textsecondary dark:text-gray-400 border-r border-border dark:border-gray-600 select-none">/</span>
                            <input type="text" name="slug" x-model="slug"
                                   value="{{ old('slug', $category->slug) }}" required
                                   class="flex-1 px-3 py-2.5 text-sm bg-white dark:bg-gray-700/50
                                          focus:outline-none focus:ring-2 focus:ring-primary/30
                                          text-textprimary dark:text-gray-100
                                          @error('slug') bg-red-50 dark:bg-red-900/10 @endif">
                        </div>
                        @error('slug')<p class="mt-1.5 text-xs text-danger dark:text-red-400">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1.5 text-textprimary dark:text-gray-200">Description</label>
                        <textarea name="description" rows="4"
                                  class="w-full px-4 py-2.5 text-sm rounded-xl border border-border dark:border-gray-600 bg-white dark:bg-gray-700/50 resize-none transition-all duration-200
                                         focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary dark:focus:border-primary
                                         text-textprimary dark:text-gray-100">{{ old('description', $category->description) }}</textarea>
                        @error('description')<p class="mt-1.5 text-xs text-danger dark:text-red-400">{{ $message }}</p>@enderror
                    </div>

                </div>
            </x-admin.card>

            <x-admin.card>
                <x-slot:header>
                    <h3 class="font-heading font-semibold text-textprimary dark:text-gray-100">Category Image</h3>
                </x-slot:header>
                <div class="p-6">
                    <div class="flex flex-col sm:flex-row gap-5 items-start">
                        <div class="flex-shrink-0 w-32 h-32 rounded-2xl border-2 border-dashed border-border dark:border-gray-600
                                    bg-gray-50 dark:bg-gray-700 overflow-hidden flex items-center justify-center">
                            <template x-if="!imagePreview">
                                @if($category->image)
                                    <img src="{{ Storage::url($category->image) }}" alt="{{ $category->name }}"
                                         class="w-full h-full object-cover">
                                @else
                                    <svg class="w-10 h-10 text-border dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/>
                                    </svg>
                                @endif
                            </template>
                            <template x-if="imagePreview">
                                <img :src="imagePreview" alt="Preview" class="w-full h-full object-cover">
                            </template>
                        </div>
                        <div class="flex-1">
                            <label for="image"
                                   class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium cursor-pointer
                                          border border-border dark:border-gray-600 rounded-xl
                                          bg-white dark:bg-gray-700/50 text-textprimary dark:text-gray-200
                                          hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/></svg>
                                {{ $category->image ? 'Replace Image' : 'Choose Image' }}
                            </label>
                            <input type="file" id="image" name="image" accept="image/*" class="hidden"
                                   x-on:change="previewImage($event)">
                            <p class="text-xs text-textsecondary dark:text-gray-500 mt-2">JPEG, PNG, WebP · Max 2 MB</p>
                            @error('image')<p class="mt-1.5 text-xs text-danger dark:text-red-400">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>
            </x-admin.card>

        </div>

        <div class="space-y-6">

            <x-admin.card>
                <x-slot:header>
                    <h3 class="font-heading font-semibold text-textprimary dark:text-gray-100">Publish</h3>
                </x-slot:header>
                <div class="p-6 space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-textprimary dark:text-gray-200">Active</p>
                            <p class="text-xs text-textsecondary dark:text-gray-500 mt-0.5">Visible on the storefront</p>
                        </div>
                        <label class="relative inline-flex cursor-pointer">
                            <input type="checkbox" name="is_active" value="1" class="sr-only peer"
                                   {{ old('is_active', $category->is_active ? '1' : '') == '1' ? 'checked' : '' }}>
                            <div class="w-9 h-5 rounded-full peer-checked:bg-green-500 bg-gray-300 dark:bg-gray-600
                                        after:content-[''] after:absolute after:top-0.5 after:left-0.5
                                        after:bg-white after:rounded-full after:h-4 after:w-4
                                        after:transition-all peer-checked:after:translate-x-4"></div>
                        </label>
                    </div>
                    <hr class="border-border dark:border-gray-700">
                    <div class="flex gap-3">
                        <a href="{{ route('admin.categories.index') }}"
                           class="flex-1 text-center px-4 py-2.5 text-sm font-medium border border-border dark:border-gray-600 rounded-xl
                                  text-textprimary dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            Cancel
                        </a>
                        <button type="submit"
                                class="flex-1 px-4 py-2.5 text-sm font-semibold text-black bg-accent hover:bg-yellow-500 rounded-xl transition-colors">
                            Update
                        </button>
                    </div>
                </div>
            </x-admin.card>

            <x-admin.card>
                <x-slot:header>
                    <h3 class="font-heading font-semibold text-textprimary dark:text-gray-100">Organisation</h3>
                </x-slot:header>
                <div class="p-6 space-y-5">

                    <div>
                        <label class="block text-sm font-medium mb-1.5 text-textprimary dark:text-gray-200">Parent Category</label>
                        <select name="parent_id"
                                class="w-full px-4 py-2.5 text-sm rounded-xl border border-border dark:border-gray-600
                                       bg-white dark:bg-gray-700/50 text-textprimary dark:text-gray-100
                                       focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary dark:focus:border-primary transition-all duration-200">
                            <option value="">None (top-level)</option>
                            @foreach($parents as $parent)
                                <option value="{{ $parent->id }}"
                                        {{ old('parent_id', $category->parent_id) == $parent->id ? 'selected' : '' }}>
                                    {{ $parent->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('parent_id')<p class="mt-1.5 text-xs text-danger dark:text-red-400">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1.5 text-textprimary dark:text-gray-200">Sort Order</label>
                        <input type="number" name="sort_order" value="{{ old('sort_order', $category->sort_order) }}" min="0"
                               class="w-full px-4 py-2.5 text-sm rounded-xl border border-border dark:border-gray-600 bg-white dark:bg-gray-700/50 transition-all duration-200
                                      focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary dark:focus:border-primary
                                      text-textprimary dark:text-gray-100">
                        @error('sort_order')<p class="mt-1.5 text-xs text-danger dark:text-red-400">{{ $message }}</p>@enderror
                    </div>

                </div>
            </x-admin.card>

            <x-admin.card>
                <x-slot:header>
                    <h3 class="font-heading font-semibold text-danger">Danger Zone</h3>
                </x-slot:header>
                <div class="p-6">
                    <p class="text-xs text-textsecondary dark:text-gray-500 mb-3">
                        Deleting this category is permanent. Categories with sub-categories cannot be deleted.
                    </p>
                    <button type="button"
                            onclick="confirmDelete({{ $category->id }}, '{{ route('admin.categories.destroy', $category) }}')"
                            class="w-full px-4 py-2.5 text-sm font-medium text-danger border border-danger
                                   rounded-xl hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                        Delete Category
                    </button>
                </div>
            </x-admin.card>

        </div>
    </div>
</form>

@push('scripts')
<script>
function categoryForm() {
    return {
        name: '{{ old('name', $category->name) }}',
        slug: '{{ old('slug', $category->slug) }}',
        imagePreview: null,
        previewImage(event) {
            const file = event.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = e => { this.imagePreview = e.target.result; };
            reader.readAsDataURL(file);
        },
    };
}
</script>
@endpush

@endsection
