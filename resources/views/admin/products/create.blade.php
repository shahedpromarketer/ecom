@extends('layouts.admin')

@section('title', 'Add Product')
@section('page-title', 'Products')

@section('breadcrumb')
    <span class="text-textsecondary">/</span>
    <a href="{{ route('admin.products.index') }}" class="text-textsecondary hover:text-textprimary dark:hover:text-gray-200 transition-colors">Products</a>
    <span class="text-textsecondary">/</span>
    <span class="text-textprimary font-medium">Add New</span>
@endsection

@section('content')

<form method="POST" action="{{ route('admin.products.store') }}" enctype="multipart/form-data"
      x-data="productForm()" @submit="syncFileInput">
    @csrf

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

        <div class="xl:col-span-2 space-y-6">

            <x-admin.card>
                <x-slot:header>
                    <h3 class="font-heading font-semibold text-textprimary dark:text-gray-100">Basic Information</h3>
                </x-slot:header>
                <div class="p-6 space-y-5">

                    <div>
                        <label class="block text-sm font-medium mb-1.5 text-textprimary dark:text-gray-200">
                            Product Name <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="name" x-model="name" @input="onNameInput"
                               value="{{ old('name') }}" required placeholder="e.g. Classic Polo Shirt"
                               class="w-full px-4 py-2.5 text-sm rounded-xl border transition-all duration-200
                                      focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary dark:focus:border-primary
                                      text-textprimary dark:text-gray-100 placeholder-textsecondary dark:placeholder-gray-500
                                      @error('name') border-danger bg-red-50 dark:bg-red-900/10 dark:border-red-700
                                      @else border-border dark:border-gray-600 bg-white dark:bg-gray-700/50 @enderror">
                        @error('name')<p class="mt-1.5 text-xs text-danger dark:text-red-400">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1.5 text-textprimary dark:text-gray-200">Slug <span class="text-danger">*</span></label>
                        <div class="flex rounded-xl overflow-hidden border @error('slug') border-danger @else border-border dark:border-gray-600 @enderror">
                            <span class="px-3 py-2.5 text-sm bg-gray-50 dark:bg-gray-700 text-textsecondary dark:text-gray-400 border-r border-border dark:border-gray-600 select-none">/</span>
                            <input type="text" name="slug" x-model="slug" @input="slugLocked = !!slug"
                                   value="{{ old('slug') }}" required placeholder="classic-polo-shirt"
                                   class="flex-1 px-3 py-2.5 text-sm bg-white dark:bg-gray-700/50
                                          focus:outline-none focus:ring-2 focus:ring-primary/30
                                          text-textprimary dark:text-gray-100
                                          @error('slug') bg-red-50 dark:bg-red-900/10 @endif">
                        </div>
                        @error('slug')<p class="mt-1.5 text-xs text-danger dark:text-red-400">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1.5 text-textprimary dark:text-gray-200">SKU</label>
                        <input type="text" name="sku" value="{{ old('sku') }}" placeholder="CLT-001"
                               class="w-full px-4 py-2.5 text-sm rounded-xl border border-border dark:border-gray-600 bg-white dark:bg-gray-700/50 transition-all duration-200
                                      focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary dark:focus:border-primary
                                      text-textprimary dark:text-gray-100 placeholder-textsecondary dark:placeholder-gray-500">
                        @error('sku')<p class="mt-1.5 text-xs text-danger dark:text-red-400">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="text-sm font-medium text-textprimary dark:text-gray-200">Short Description</label>
                            <span class="text-xs" :class="shortDescLen > 480 ? 'text-danger' : 'text-textsecondary dark:text-gray-500'">
                                <span x-text="shortDescLen"></span>/500
                            </span>
                        </div>
                        <textarea name="short_description" rows="2"
                                  x-model="shortDesc"
                                  @input="shortDescLen = shortDesc.length"
                                  maxlength="500" placeholder="Brief product summary shown in listings…"
                                  class="w-full px-4 py-2.5 text-sm rounded-xl border border-border dark:border-gray-600 bg-white dark:bg-gray-700/50 resize-none transition-all duration-200
                                         focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary dark:focus:border-primary
                                         text-textprimary dark:text-gray-100 placeholder-textsecondary dark:placeholder-gray-500">{{ old('short_description') }}</textarea>
                        @error('short_description')<p class="mt-1.5 text-xs text-danger dark:text-red-400">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1.5 text-textprimary dark:text-gray-200">Description</label>
                        <textarea name="description" rows="5" placeholder="Full product description…"
                                  class="w-full px-4 py-2.5 text-sm rounded-xl border border-border dark:border-gray-600 bg-white dark:bg-gray-700/50 resize-none transition-all duration-200
                                         focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary dark:focus:border-primary
                                         text-textprimary dark:text-gray-100 placeholder-textsecondary dark:placeholder-gray-500">{{ old('description') }}</textarea>
                        @error('description')<p class="mt-1.5 text-xs text-danger dark:text-red-400">{{ $message }}</p>@enderror
                    </div>

                </div>
            </x-admin.card>

            <x-admin.card>
                <x-slot:header>
                    <h3 class="font-heading font-semibold text-textprimary dark:text-gray-100">Product Images</h3>
                    <span class="text-xs text-textsecondary dark:text-gray-500">First image = primary. Max 2 MB each.</span>
                </x-slot:header>
                <div class="p-6">

                    <input type="file" name="images[]" multiple accept="image/jpeg,image/jpg,image/png,image/webp"
                           x-ref="fileInput" class="hidden" @change="handleFileChange($event)">

                    <div @drop.prevent="handleDrop($event)"
                         @dragover.prevent="isDragging = true"
                         @dragleave.prevent="isDragging = false"
                         @click="$refs.fileInput.click()"
                         :class="isDragging ? 'border-accent bg-accent/5 dark:bg-accent/10' : 'border-border dark:border-gray-600 hover:border-accent/60'"
                         class="border-2 border-dashed rounded-xl p-8 text-center cursor-pointer transition-all
                                bg-gray-50 dark:bg-gray-700/40">
                        <svg class="w-10 h-10 mx-auto mb-3 text-textsecondary dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/>
                        </svg>
                        <p class="text-sm font-medium text-textprimary dark:text-gray-200">Drop images here or click to browse</p>
                        <p class="text-xs text-textsecondary dark:text-gray-500 mt-1">JPEG, PNG, WebP · Up to 2 MB each</p>
                    </div>

                    @error('images.*')<p class="mt-2 text-xs text-danger dark:text-red-400">{{ $message }}</p>@enderror

                    <div x-show="images.length > 0" x-cloak class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 gap-3 mt-4">
                        <template x-for="(img, i) in images" :key="img.name + i">
                            <div class="relative group rounded-xl overflow-hidden border-2 transition-colors"
                                 :class="img.primary ? 'border-accent' : 'border-border dark:border-gray-600'">
                                <img :src="img.preview" class="w-full aspect-square object-cover">
                                <div x-show="img.primary"
                                     class="absolute top-1 left-1 px-1 py-0.5 bg-accent text-black text-[10px] font-bold rounded leading-none">
                                    Primary
                                </div>
                                <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                                    <button type="button" @click.stop="setPrimaryNew(i)" title="Set as primary"
                                            class="p-1 bg-white/20 hover:bg-accent rounded text-black transition-colors">
                                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.006 5.404.434c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.434 2.082-5.005z"/></svg>
                                    </button>
                                    <button type="button" @click.stop="removeNewImage(i)" title="Remove"
                                            class="p-1 bg-white/20 hover:bg-danger rounded text-white transition-colors">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>

                </div>
            </x-admin.card>

            <x-admin.card>
                <x-slot:header>
                    <h3 class="font-heading font-semibold text-textprimary dark:text-gray-100">Pricing</h3>
                </x-slot:header>
                <div class="p-6 space-y-5">

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-medium mb-1.5 text-textprimary dark:text-gray-200">
                                Regular Price (৳) <span class="text-danger">*</span>
                            </label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-textsecondary dark:text-gray-400 text-sm font-medium select-none">৳</span>
                                <input type="number" name="price" x-model="price" @input="price = $event.target.value"
                                       value="{{ old('price') }}" min="0" step="0.01" required placeholder="0.00"
                                       class="w-full pl-8 pr-4 py-2.5 text-sm rounded-xl border transition-all duration-200
                                              focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary dark:focus:border-primary
                                              text-textprimary dark:text-gray-100
                                              @error('price') border-danger bg-red-50 dark:bg-red-900/10 dark:border-red-700
                                              @else border-border dark:border-gray-600 bg-white dark:bg-gray-700/50 @enderror">
                            </div>
                            @error('price')<p class="mt-1.5 text-xs text-danger dark:text-red-400">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1.5 text-textprimary dark:text-gray-200">Sale Price (৳)</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-textsecondary dark:text-gray-400 text-sm font-medium select-none">৳</span>
                                <input type="number" name="sale_price" x-model="salePrice" @input="salePrice = $event.target.value"
                                       value="{{ old('sale_price') }}" min="0" step="0.01" placeholder="0.00"
                                       class="w-full pl-8 pr-4 py-2.5 text-sm rounded-xl border transition-all duration-200
                                              focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary dark:focus:border-primary
                                              text-textprimary dark:text-gray-100
                                              @error('sale_price') border-danger bg-red-50 dark:bg-red-900/10 dark:border-red-700
                                              @else border-border dark:border-gray-600 bg-white dark:bg-gray-700/50 @enderror">
                            </div>
                            @error('sale_price')<p class="mt-1.5 text-xs text-danger dark:text-red-400">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <template x-if="savingsPercent !== null">
                        <div class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800">
                            <svg class="w-4 h-4 text-green-600 dark:text-green-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/></svg>
                            <p class="text-sm text-green-700 dark:text-green-400 font-medium">
                                Customers save <span x-text="savingsPercent + '%'"></span> with this sale price
                            </p>
                        </div>
                    </template>
                    <template x-if="salePriceError">
                        <p class="text-xs text-danger dark:text-red-400" x-text="salePriceError"></p>
                    </template>

                </div>
            </x-admin.card>

            <x-admin.card>
                <x-slot:header>
                    <div>
                        <h3 class="text-sm font-semibold text-textprimary dark:text-white">Variants</h3>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">Add size, color and stock for each variant</p>
                    </div>
                    <button type="button" @click="addVariant()"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-primary text-white text-xs font-semibold rounded-lg hover:bg-primary/90 transition-colors">
                        + Add Variant
                    </button>
                </x-slot:header>
                <div class="p-6">

                    {{-- Quick add size pills --}}
                    <div class="flex items-center gap-2 flex-wrap mb-4 pb-4 border-b border-gray-100 dark:border-gray-700">
                        <span class="text-xs font-medium text-gray-500 dark:text-gray-400 mr-1">Quick add size:</span>
                        <template x-for="size in ['S','M','L','XL','XXL','XXXL','Free Size']" :key="size">
                            <button type="button" @click="quickAddSize(size)"
                                    class="px-3 py-1 text-xs font-medium rounded-full border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:bg-primary hover:text-white hover:border-primary transition-all duration-150">
                                <span x-text="size"></span>
                            </button>
                        </template>
                    </div>

                    {{-- Table header --}}
                    <div x-show="variants.length > 0" x-cloak
                         class="grid gap-2 px-3 py-2 bg-gray-50 dark:bg-gray-700/50 rounded-lg mb-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide"
                         style="grid-template-columns: 2fr 2fr 80px 100px 1.5fr 100px 36px; min-width: 580px;">
                        <span>Size</span>
                        <span>Color</span>
                        <span>Hex</span>
                        <span>Stock</span>
                        <span>SKU</span>
                        <span>+Price</span>
                        <span></span>
                    </div>

                    {{-- Variant rows --}}
                    <div class="overflow-x-auto">
                        <div class="space-y-2" style="min-width: 580px;">
                            <template x-for="(v, i) in variants" :key="v.key">
                                <div class="grid gap-2 px-3 py-2.5 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 hover:border-primary/40 hover:shadow-sm transition-all duration-150"
                                     style="grid-template-columns: 2fr 2fr 80px 100px 1.5fr 100px 36px">
                                    <input type="text" :name="`variants[${i}][size]`" x-model="v.size" placeholder="e.g. M"
                                           class="w-full px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-600 bg-transparent text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all">
                                    <input type="text" :name="`variants[${i}][color]`" x-model="v.color" placeholder="e.g. Black"
                                           class="w-full px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-600 bg-transparent text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all">
                                    <input type="color" :name="`variants[${i}][color_hex]`" x-model="v.color_hex"
                                           class="w-full h-9 rounded-lg border border-gray-200 dark:border-gray-600 cursor-pointer p-0.5 focus:outline-none focus:ring-2 focus:ring-primary/30">
                                    <div class="flex items-center gap-1">
                                        <button type="button" @click="v.stock = Math.max(0, parseInt(v.stock||0) - 1)"
                                                class="w-7 h-7 flex items-center justify-center rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition text-sm font-bold flex-shrink-0">−</button>
                                        <input type="number" :name="`variants[${i}][stock]`" x-model="v.stock" min="0"
                                               class="w-full px-2 py-2 rounded-lg border border-gray-200 dark:border-gray-600 bg-transparent text-sm text-center text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all">
                                        <button type="button" @click="v.stock = parseInt(v.stock||0) + 1"
                                                class="w-7 h-7 flex items-center justify-center rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition text-sm font-bold flex-shrink-0">+</button>
                                    </div>
                                    <input type="text" :name="`variants[${i}][sku]`" x-model="v.sku" placeholder="Auto or manual"
                                           class="w-full px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-600 bg-transparent text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all">
                                    <div class="relative">
                                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs text-gray-400">৳</span>
                                        <input type="number" :name="`variants[${i}][extra_price]`" x-model="v.extra_price" min="0" step="0.01" placeholder="0"
                                               class="w-full pl-7 pr-3 py-2 rounded-lg border border-gray-200 dark:border-gray-600 bg-transparent text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all">
                                    </div>
                                    <button type="button" @click="removeVariant(i)"
                                            class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition-all duration-150">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- Empty state --}}
                    <div x-show="variants.length === 0"
                         class="flex flex-col items-center justify-center py-8 border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-xl text-center">
                        <svg class="w-8 h-8 text-gray-300 dark:text-gray-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.429 9.75L2.25 12l4.179 2.25m0-4.5l5.571 3 5.571-3m-11.142 0L2.25 7.5 12 2.25l9.75 5.25-4.179 2.25m0 0L21.75 12l-4.179 2.25m0 0l4.179 2.25L12 21.75 2.25 16.5l4.179-2.25m11.142 0l-5.571 3-5.571-3"/>
                        </svg>
                        <p class="text-sm text-gray-500 dark:text-gray-400">No variants added yet</p>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Use quick add buttons or click "+ Add Variant"</p>
                    </div>

                    {{-- Summary bar --}}
                    <div x-show="variants.length > 0" x-cloak
                         class="mt-3 px-4 py-2.5 bg-primary/5 dark:bg-primary/10 rounded-xl border border-primary/20 flex items-center justify-between">
                        <div class="flex items-center gap-4 text-xs text-gray-600 dark:text-gray-400">
                            <span><span class="font-semibold text-gray-900 dark:text-white" x-text="variants.length"></span> variants</span>
                            <span>Total stock: <span class="font-semibold text-gray-900 dark:text-white" x-text="variants.reduce((s,v) => s + parseInt(v.stock||0), 0)"></span></span>
                        </div>
                        <button type="button" @click="variants = []"
                                class="text-xs text-red-500 hover:text-red-600 font-medium transition-colors">Clear all</button>
                    </div>

                </div>
            </x-admin.card>

        </div>

        <div class="space-y-6">

            <x-admin.card>
                <x-slot:header>
                    <h3 class="font-heading font-semibold text-textprimary dark:text-gray-100">Publish</h3>
                </x-slot:header>
                <div class="p-6 space-y-5">

                    <div>
                        <label class="block text-sm font-medium mb-1.5 text-textprimary dark:text-gray-200">Status</label>
                        <select name="status"
                                class="w-full px-4 py-2.5 text-sm rounded-xl border border-border dark:border-gray-600
                                       bg-white dark:bg-gray-700/50 text-textprimary dark:text-gray-100
                                       focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary dark:focus:border-primary transition-all duration-200">
                            <option value="draft"    {{ old('status', 'draft') === 'draft'    ? 'selected' : '' }}>Draft</option>
                            <option value="active"   {{ old('status') === 'active'   ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                        @error('status')<p class="mt-1.5 text-xs text-danger dark:text-red-400">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-textprimary dark:text-gray-200">Featured</p>
                            <p class="text-xs text-textsecondary dark:text-gray-500 mt-0.5">Show on homepage</p>
                        </div>
                        <label class="relative inline-flex cursor-pointer">
                            <input type="checkbox" name="is_featured" value="1" class="sr-only peer"
                                   {{ old('is_featured') ? 'checked' : '' }}>
                            <div class="w-9 h-5 rounded-full peer-checked:bg-accent bg-gray-300 dark:bg-gray-600
                                        after:content-[''] after:absolute after:top-0.5 after:left-0.5
                                        after:bg-white after:rounded-full after:h-4 after:w-4
                                        after:transition-all peer-checked:after:translate-x-4"></div>
                        </label>
                    </div>

                    <hr class="border-border dark:border-gray-700">

                    <div class="flex gap-3">
                        <a href="{{ route('admin.products.index') }}"
                           class="flex-1 text-center px-4 py-2.5 text-sm font-medium border border-border dark:border-gray-600 rounded-xl
                                  text-textprimary dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            Cancel
                        </a>
                        <button type="submit"
                                class="flex-1 px-4 py-2.5 text-sm font-semibold text-black bg-accent hover:bg-yellow-500 active:bg-yellow-600 rounded-xl transition-colors">
                            Publish
                        </button>
                    </div>

                </div>
            </x-admin.card>

            <x-admin.card>
                <x-slot:header>
                    <h3 class="font-heading font-semibold text-textprimary dark:text-gray-100">Categories</h3>
                    <span class="text-xs text-textsecondary dark:text-gray-500" x-data x-text="document.querySelectorAll('input[name=\'categories[]\']:checked').length + ' selected'"></span>
                </x-slot:header>
                <div class="p-4 max-h-72 overflow-y-auto space-y-1">
                    @error('categories')
                        <p class="mb-3 text-xs text-danger dark:text-red-400 px-2">{{ $message }}</p>
                    @enderror
                    @forelse($categories as $parent)
                        <label class="flex items-center gap-2.5 px-2 py-1.5 rounded-xl cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            <input type="checkbox" name="categories[]" value="{{ $parent->id }}"
                                   {{ in_array($parent->id, old('categories', [])) ? 'checked' : '' }}
                                   class="w-4 h-4 rounded text-accent border-border dark:border-gray-600 focus:ring-accent focus:ring-1">
                            <span class="text-sm font-medium text-textprimary dark:text-gray-200">{{ $parent->name }}</span>
                        </label>
                        @foreach($parent->children as $child)
                            <label class="flex items-center gap-2.5 px-2 py-1.5 rounded-xl cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors ml-5">
                                <input type="checkbox" name="categories[]" value="{{ $child->id }}"
                                       {{ in_array($child->id, old('categories', [])) ? 'checked' : '' }}
                                       class="w-4 h-4 rounded text-accent border-border dark:border-gray-600 focus:ring-accent focus:ring-1">
                                <span class="text-sm text-textsecondary dark:text-gray-400">↳ {{ $child->name }}</span>
                            </label>
                        @endforeach
                    @empty
                        <p class="text-xs text-textsecondary dark:text-gray-500 px-2 py-4 text-center">No categories found.</p>
                    @endforelse
                </div>
            </x-admin.card>

            <x-admin.card>
                <x-slot:header>
                    <h3 class="font-heading font-semibold text-textprimary dark:text-gray-100">SEO</h3>
                </x-slot:header>
                <div class="p-6 space-y-4">

                    <div>
                        <label class="block text-sm font-medium mb-1.5 text-textprimary dark:text-gray-200">Meta Title</label>
                        <input type="text" name="meta_title" value="{{ old('meta_title') }}"
                               placeholder="Leave blank to use product name"
                               class="w-full px-4 py-2.5 text-sm rounded-xl border border-border dark:border-gray-600 bg-white dark:bg-gray-700/50 transition-all duration-200
                                      focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary dark:focus:border-primary
                                      text-textprimary dark:text-gray-100 placeholder-textsecondary dark:placeholder-gray-500">
                        @error('meta_title')<p class="mt-1.5 text-xs text-danger dark:text-red-400">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="text-sm font-medium text-textprimary dark:text-gray-200">Meta Description</label>
                            <span class="text-xs" :class="metaDescLen > 150 ? (metaDescLen > 160 ? 'text-danger' : 'text-yellow-500') : 'text-textsecondary dark:text-gray-500'">
                                <span x-text="metaDescLen"></span>/160
                            </span>
                        </div>
                        <textarea name="meta_description" rows="3"
                                  x-model="metaDesc"
                                  @input="metaDescLen = metaDesc.length"
                                  placeholder="SEO description…"
                                  class="w-full px-4 py-2.5 text-sm rounded-xl border border-border dark:border-gray-600 bg-white dark:bg-gray-700/50 resize-none transition-all duration-200
                                         focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary dark:focus:border-primary
                                         text-textprimary dark:text-gray-100 placeholder-textsecondary dark:placeholder-gray-500">{{ old('meta_description') }}</textarea>
                        @error('meta_description')<p class="mt-1.5 text-xs text-danger dark:text-red-400">{{ $message }}</p>@enderror
                    </div>

                </div>
            </x-admin.card>

        </div>

    </div>
</form>

@push('scripts')
<script>
function productForm() {
    return {
        name: @js(old('name', '')),
        slug: @js(old('slug', '')),
        slugLocked: !!@js(old('slug', '')),
        price: @js(old('price', '')),
        salePrice: @js(old('sale_price', '')),
        shortDesc: @js(old('short_description', '')),
        shortDescLen: {{ strlen(old('short_description', '')) }},
        metaDesc: @js(old('meta_description', '')),
        metaDescLen: {{ strlen(old('meta_description', '')) }},
        isDragging: false,
        images: [],
        variants: @js(
            collect(old('variants', []))->values()->map(fn ($v, $i) => [
                'key'         => $i,
                'id'          => null,
                'size'        => $v['size'] ?? '',
                'color'       => $v['color'] ?? '',
                'color_hex'   => $v['color_hex'] ?? '#000000',
                'stock'       => (int) ($v['stock'] ?? 0),
                'sku'         => $v['sku'] ?? '',
                'extra_price' => $v['extra_price'] ?? '',
                '_delete'     => false,
            ])->all()
        ),
        variantKey: {{ count(old('variants', [])) }},

        onNameInput() {
            if (!this.slugLocked) {
                this.slug = this.name
                    .toLowerCase()
                    .replace(/[^a-z0-9\s-]/g, '')
                    .trim()
                    .replace(/\s+/g, '-')
                    .replace(/-+/g, '-');
            }
        },

        handleDrop(e) {
            this.isDragging = false;
            this.handleFiles(e.dataTransfer.files);
        },

        handleFileChange(e) {
            this.handleFiles(e.target.files);
        },

        handleFiles(fileList) {
            Array.from(fileList).forEach(file => {
                if (!file.type.match('image/(jpeg|jpg|png|webp)')) return;
                const reader = new FileReader();
                reader.onload = ev => {
                    this.images.push({
                        preview: ev.target.result,
                        name: file.name + Date.now(),
                        file,
                        primary: this.images.length === 0,
                    });
                    this.syncFileInput();
                };
                reader.readAsDataURL(file);
            });
        },

        removeNewImage(idx) {
            this.images.splice(idx, 1);
            if (this.images.length > 0 && !this.images.some(i => i.primary)) {
                this.images[0].primary = true;
            }
            this.syncFileInput();
        },

        setPrimaryNew(idx) {
            this.images.forEach((img, i) => img.primary = i === idx);
        },

        syncFileInput() {
            if (!this.$refs.fileInput) return;
            const dt = new DataTransfer();
            this.images.forEach(img => dt.items.add(img.file));
            this.$refs.fileInput.files = dt.files;
        },

        addVariant() {
            this.variants.push({
                key: ++this.variantKey,
                id: null,
                size: '',
                color: '',
                color_hex: '#000000',
                stock: 0,
                sku: '',
                extra_price: '',
                _delete: false,
            });
        },

        quickAddSize(size) {
            if (this.variants.find(v => v.size === size)) return;
            this.variants.push({
                key: ++this.variantKey,
                id: null,
                size: size,
                color: '',
                color_hex: '#000000',
                stock: 0,
                sku: '',
                extra_price: '',
                _delete: false,
            });
        },

        removeVariant(idx) {
            if (this.variants.length === 1) {
                this.variants.splice(idx, 1, {
                    key: this.variants[idx].key,
                    id: null, size: '', color: '', color_hex: '#000000',
                    stock: 0, sku: '', extra_price: '', _delete: false,
                });
                return;
            }
            this.variants.splice(idx, 1);
        },

        get savingsPercent() {
            const p = parseFloat(this.price);
            const s = parseFloat(this.salePrice);
            if (!p || !s || s >= p) return null;
            return Math.round((1 - s / p) * 100);
        },

        get salePriceError() {
            const p = parseFloat(this.price);
            const s = parseFloat(this.salePrice);
            if (this.salePrice && p && s >= p) return 'Sale price must be less than the regular price.';
            return null;
        },
    };
}
</script>
@endpush

@endsection
