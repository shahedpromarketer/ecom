@extends('layouts.admin')

@section('title', 'Add Banner')
@section('page-title', 'Banners & Sliders')

@section('breadcrumb')
    <span class="text-textsecondary">/</span>
    <a href="{{ route('admin.banners.index') }}"
       class="text-textsecondary hover:text-textprimary dark:hover:text-gray-200 transition-colors">Banners</a>
    <span class="text-textsecondary">/</span>
    <span class="text-textprimary font-medium">Add</span>
@endsection

@section('content')

<form method="POST"
      action="{{ route('admin.banners.store') }}"
      enctype="multipart/form-data"
      x-data="bannerForm()">
    @csrf

    <div class="max-w-2xl mx-auto">
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-border dark:border-gray-700 shadow-sm">

            {{-- Card header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-border dark:border-gray-700">
                <h3 class="font-heading font-semibold text-textprimary dark:text-gray-100">Add Banner</h3>
            </div>

            <div class="p-6 space-y-5">

                {{-- Title --}}
                <div>
                    <label class="block text-sm font-medium mb-1.5 text-textprimary dark:text-gray-200">
                        Title <span class="text-danger">*</span>
                    </label>
                    <input type="text"
                           name="title"
                           value="{{ old('title') }}"
                           required
                           placeholder="e.g. Summer Sale Banner"
                           class="w-full px-4 py-2.5 text-sm rounded-xl border @error('title') border-danger bg-red-50 dark:bg-red-900/10 dark:border-red-700 @else border-border dark:border-gray-600 bg-white dark:bg-gray-700/50 @enderror text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all duration-200">
                    @error('title')
                        <p class="mt-1.5 text-xs text-danger dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Subtitle --}}
                <div>
                    <label class="block text-sm font-medium mb-1.5 text-textprimary dark:text-gray-200">
                        Subtitle <span class="text-xs font-normal text-textsecondary dark:text-gray-400">(optional)</span>
                    </label>
                    <input type="text"
                           name="subtitle"
                           value="{{ old('subtitle') }}"
                           placeholder="Short supporting text under the title"
                           class="w-full px-4 py-2.5 text-sm rounded-xl border @error('subtitle') border-danger bg-red-50 dark:bg-red-900/10 dark:border-red-700 @else border-border dark:border-gray-600 bg-white dark:bg-gray-700/50 @enderror text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all duration-200">
                    @error('subtitle')
                        <p class="mt-1.5 text-xs text-danger dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Link URL --}}
                <div>
                    <label class="block text-sm font-medium mb-1.5 text-textprimary dark:text-gray-200">
                        Link URL <span class="text-xs font-normal text-textsecondary dark:text-gray-400">(optional)</span>
                    </label>
                    <input type="url"
                           name="link"
                           value="{{ old('link') }}"
                           placeholder="https://..."
                           class="w-full px-4 py-2.5 text-sm rounded-xl border @error('link') border-danger bg-red-50 dark:bg-red-900/10 dark:border-red-700 @else border-border dark:border-gray-600 bg-white dark:bg-gray-700/50 @enderror text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all duration-200">
                    @error('link')
                        <p class="mt-1.5 text-xs text-danger dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Position --}}
                <div>
                    <label class="block text-sm font-medium mb-1.5 text-textprimary dark:text-gray-200">
                        Position <span class="text-danger">*</span>
                    </label>
                    <select name="position"
                            x-model="position"
                            required
                            class="w-full px-4 py-2.5 text-sm rounded-xl border @error('position') border-danger bg-red-50 dark:bg-red-900/10 dark:border-red-700 @else border-border dark:border-gray-600 bg-white dark:bg-gray-700/50 @enderror text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all duration-200">
                        <option value="hero"    {{ old('position', 'hero') === 'hero'    ? 'selected' : '' }}>Hero Banner</option>
                        <option value="section" {{ old('position') === 'section' ? 'selected' : '' }}>Section Banner</option>
                        <option value="popup"   {{ old('position') === 'popup'   ? 'selected' : '' }}>Popup</option>
                    </select>
                    @error('position')
                        <p class="mt-1.5 text-xs text-danger dark:text-red-400">{{ $message }}</p>
                    @enderror
                    <p class="mt-1.5 text-xs text-textsecondary dark:text-gray-400">
                        Recommended size: <span class="font-medium text-textprimary dark:text-gray-200" x-text="recommendedSize"></span>
                    </p>
                </div>

                {{-- Sort Order --}}
                <div>
                    <label class="block text-sm font-medium mb-1.5 text-textprimary dark:text-gray-200">Sort Order</label>
                    <input type="number"
                           name="sort_order"
                           value="{{ old('sort_order', 0) }}"
                           min="0"
                           class="w-full px-4 py-2.5 text-sm rounded-xl border @error('sort_order') border-danger bg-red-50 dark:bg-red-900/10 dark:border-red-700 @else border-border dark:border-gray-600 bg-white dark:bg-gray-700/50 @enderror text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all duration-200">
                    @error('sort_order')
                        <p class="mt-1.5 text-xs text-danger dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Status toggle --}}
                <div class="flex items-center justify-between py-3 px-4 rounded-xl bg-gray-50 dark:bg-gray-700/50 border border-border dark:border-gray-600">
                    <div>
                        <p class="text-sm font-medium text-textprimary dark:text-gray-200">Status</p>
                        <p class="text-xs text-textsecondary dark:text-gray-400 mt-0.5">Visible on the storefront when active</p>
                    </div>
                    <label class="relative inline-flex cursor-pointer">
                        <input type="checkbox"
                               name="is_active"
                               value="1"
                               class="sr-only peer"
                               {{ old('is_active', '1') == '1' ? 'checked' : '' }}>
                        <div class="w-9 h-5 rounded-full peer-checked:bg-green-500 bg-gray-300 dark:bg-gray-600
                                    after:content-[''] after:absolute after:top-0.5 after:left-0.5
                                    after:bg-white after:rounded-full after:h-4 after:w-4
                                    after:transition-all peer-checked:after:translate-x-4"></div>
                    </label>
                </div>

                {{-- Image upload --}}
                <div>
                    <label class="block text-sm font-medium mb-1.5 text-textprimary dark:text-gray-200">
                        Image <span class="text-danger">*</span>
                    </label>

                    {{-- Dropzone --}}
                    <div class="relative border-2 border-dashed rounded-xl transition-colors duration-200
                                @error('image') border-danger bg-red-50 dark:bg-red-900/10 @else border-border dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 @enderror
                                hover:border-primary dark:hover:border-primary cursor-pointer"
                         @click="$refs.imageInput.click()">

                        {{-- Preview --}}
                        <div x-show="preview" class="p-3">
                            <img :src="preview"
                                 class="w-full rounded-xl object-cover max-h-48"
                                 alt="Banner preview">
                            <p class="text-xs text-center text-textsecondary dark:text-gray-400 mt-2">Click to change image</p>
                        </div>

                        {{-- Placeholder (shown when no preview) --}}
                        <div x-show="!preview" class="flex flex-col items-center justify-center py-10 px-4 text-center">
                            <div class="w-12 h-12 rounded-xl bg-gray-100 dark:bg-gray-600 flex items-center justify-center mb-3">
                                <svg class="w-6 h-6 text-textsecondary dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/>
                                </svg>
                            </div>
                            <p class="text-sm font-medium text-textprimary dark:text-gray-200">Click or drag to upload</p>
                            <p class="text-xs text-textsecondary dark:text-gray-400 mt-1">
                                Recommended: <span class="font-medium" x-text="recommendedSize"></span>
                            </p>
                            <p class="text-xs text-textsecondary dark:text-gray-500 mt-1">JPEG, PNG, WebP · Max 2 MB</p>
                        </div>

                        <input type="file"
                               name="image"
                               accept="image/*"
                               @change="previewImage($event)"
                               x-ref="imageInput"
                               class="hidden">
                    </div>

                    @error('image')
                        <p class="mt-1.5 text-xs text-danger dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Form buttons --}}
                <div class="flex items-center gap-3 pt-2 border-t border-border dark:border-gray-700">
                    <a href="{{ route('admin.banners.index') }}"
                       class="flex-1 text-center px-4 py-2.5 text-sm font-medium border border-border dark:border-gray-600 rounded-xl
                              text-textprimary dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        Cancel
                    </a>
                    <button type="submit"
                            class="flex-1 px-4 py-2.5 text-sm font-semibold bg-primary hover:bg-primary/90 text-white rounded-xl transition-colors">
                        Save Banner
                    </button>
                </div>

            </div>
        </div>
    </div>

</form>

@endsection

@push('scripts')
<script>
function bannerForm() {
    return {
        preview: null,
        position: '{{ old('position', 'hero') }}',
        sizeHints: {
            hero: '1920 × 600 px',
            section: '1200 × 400 px',
            popup: '600 × 600 px',
        },
        get recommendedSize() {
            return this.sizeHints[this.position] || '';
        },
        previewImage(event) {
            const file = event.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = e => this.preview = e.target.result;
            reader.readAsDataURL(file);
        },
    }
}
</script>
@endpush
