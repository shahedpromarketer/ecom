@extends('layouts.frontend')

@php
    $primaryImage = $product->images->firstWhere('is_primary', true) ?? $product->images->first();
    $allImages    = $product->images;
    $regularPrice = $product->regular_price ?? $product->price;
    $onSale       = $product->sale_price && $product->sale_price < $regularPrice;
    $currentPrice = $onSale ? (float) $product->sale_price : (float) $product->price;
    $discount     = $onSale ? round((($regularPrice - $product->sale_price) / $regularPrice) * 100) : 0;
    $variants     = $product->variants;

    $productData = json_encode([
        'id'            => $product->id,
        'name'          => $product->name,
        'price'         => (float) $product->price,
        'regular_price' => (float) $regularPrice,
        'sale_price'    => $product->sale_price ? (float) $product->sale_price : null,
        'image'         => $primaryImage ? $primaryImage->url : '',
        'url'           => route('product.show', $product->slug),
        'variants'      => $variants->map(fn($v) => ['id' => $v->id, 'size' => $v->size, 'name' => $v->size])->values()->toArray(),
    ]);

    $imagesJson      = json_encode($allImages->map(fn($img) => $img->url)->values()->toArray());
    $parentCategory  = $product->categories->first()?->parent ?? $product->categories->first();
@endphp

@section('title', $product->name . ' — ' . ($settings['store_name'] ?? 'Clothonic'))

@section('meta')
@php
    $metaDesc   = Str::limit(strip_tags($product->short_description ?? $product->description ?? $product->name), 160);
    $metaImage  = $primaryImage ? $primaryImage->url : '';
    $productUrl = route('product.show', $product->slug);
    $storeName  = $settings['store_name'] ?? 'Clothonic';
@endphp
<meta name="description" content="{{ $metaDesc }}">
<link rel="canonical" href="{{ $productUrl }}">
<meta property="og:type" content="product">
<meta property="og:title" content="{{ $product->name }} — {{ $storeName }}">
<meta property="og:description" content="{{ $metaDesc }}">
<meta property="og:url" content="{{ $productUrl }}">
@if($metaImage)
<meta property="og:image" content="{{ $metaImage }}">
<meta property="og:image:width" content="800">
<meta property="og:image:height" content="1067">
@endif
<meta property="og:site_name" content="{{ $storeName }}">
<meta property="product:price:amount" content="{{ $currentPrice }}">
<meta property="product:price:currency" content="BDT">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $product->name }}">
<meta name="twitter:description" content="{{ $metaDesc }}">
@if($metaImage)<meta name="twitter:image" content="{{ $metaImage }}">@endif
@endsection

@section('schema')
@php
    $schema = [
        '@context' => 'https://schema.org/',
        '@type'    => 'Product',
        'name'        => $product->name,
        'description' => Str::limit(strip_tags($product->description ?? ''), 500),
        'url'         => $productUrl,
        'brand'  => ['@type' => 'Brand', 'name' => $storeName],
        'offers' => [
            '@type'         => 'Offer',
            'url'           => $productUrl,
            'priceCurrency' => 'BDT',
            'price'         => (string) $currentPrice,
            'availability'  => 'https://schema.org/InStock',
            'seller'        => ['@type' => 'Organization', 'name' => $storeName],
        ],
    ];
    if ($metaImage) { $schema['image'] = $metaImage; }
@endphp
<script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endsection

@section('content')

@php
    $saveAmount = $onSale ? (int)round($regularPrice - $currentPrice) : 0;
    $whyChooseUs = \App\Models\Setting::getValue('why_choose_us', '');
    $returnPolicy = \App\Models\Setting::getValue('return_policy', '');
@endphp

{{-- productPage() wraps EVERYTHING including the sticky CTA --}}
<div x-data="productPage()" class="bg-white min-h-screen">

<div class="container-main py-6 md:py-10">

    {{-- Breadcrumb --}}
    <nav class="hidden flex items-center gap-1.5 text-xs text-textsecondary mb-5" aria-label="breadcrumb">
        <a href="{{ route('home') }}" class="hover:text-primary transition-colors">হোম</a>
        <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        @if($parentCategory)
        <a href="{{ route('shop', ['category' => $parentCategory->slug]) }}" class="hover:text-primary transition-colors">{{ $parentCategory->name }}</a>
        <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        @endif
        <span class="text-textprimary font-medium line-clamp-1">{{ $product->name }}</span>
    </nav>

    {{-- Main Product Section --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 lg:gap-10">

        {{-- ── LEFT: Image Gallery ── --}}
        <div>
            {{-- Main Image --}}
            <div class="relative overflow-hidden bg-gray-50 mb-3 cursor-zoom-in"
                 style="aspect-ratio: 3/4; border: 1px solid #e5e7eb;"
                 @click="zoomImage = activeImage; zoomOpen = true">
                <img :src="activeImage" alt="{{ $product->name }}"
                     class="w-full h-full object-cover transition-transform duration-500 hover:scale-105">

                @if($onSale && $discount >= 5)
                <div class="absolute top-3 right-3 pointer-events-none">
                    <span class="px-2.5 py-1 bg-red-500 text-white text-xs font-bold rounded shadow">{{ $discount }}% Off</span>
                </div>
                @endif

                {{-- Zoom hint --}}
                <div class="absolute bottom-2 right-2 bg-black/30 text-white rounded p-1 pointer-events-none">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v6m-3-3h6"/>
                    </svg>
                </div>
            </div>

            {{-- Thumbnail Strip --}}
            <div class="flex gap-2 overflow-x-auto scrollbar-hide pb-1">
                <template x-for="imgUrl in images" :key="imgUrl">
                    <button type="button"
                        @click="activeImage = imgUrl"
                        class="flex-shrink-0 w-[72px] h-[90px] overflow-hidden transition-all focus:outline-none"
                        style="border: 2px solid transparent;"
                        :style="activeImage === imgUrl ? 'border-color: #1B2A4A;' : 'border-color: #e5e7eb;'">
                        <img :src="imgUrl" alt="{{ $product->name }}" class="w-full h-full object-cover">
                    </button>
                </template>
            </div>
        </div>

        {{-- ── RIGHT: Product Info ── --}}
        <div class="flex flex-col">

            {{-- Title --}}
            <h1 class="font-heading font-bold text-xl md:text-2xl text-textprimary leading-snug mb-3">
                {{ $product->name }}
            </h1>

            {{-- Star Rating --}}
            <div class="flex items-center gap-2 mb-3">
                <div class="flex items-center gap-0.5">
                    @for($i = 1; $i <= 5; $i++)
                    <svg class="w-4 h-4 text-accent" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                    @endfor
                </div>
                <span class="text-sm text-textsecondary">{{ number_format($product->reviews_avg_rating ?? 0, 1) }} / 5</span>
            </div>

            {{-- Category & SKU --}}
            <div class="space-y-1 mb-4">
                @if($parentCategory)
                <p class="text-sm text-textprimary">
                    <span class="font-semibold">Category :</span>
                    <a href="{{ route('shop', ['category' => $parentCategory->slug]) }}" class="text-textsecondary hover:text-primary transition-colors ml-1">{{ $parentCategory->name }}</a>
                </p>
                @endif
                @if($product->sku)
                <p class="text-sm text-textprimary">
                    <span class="font-semibold">Sku :</span>
                    <span class="text-textsecondary ml-1">{{ $product->sku }}</span>
                </p>
                @endif
            </div>

            {{-- Price Row --}}
            <div class="flex items-center flex-wrap gap-3 mb-4 pb-4 border-b border-gray-100">
                @if($onSale)
                <span class="text-base text-gray-400 line-through font-medium">{{ number_format($regularPrice, 0) }} Tk</span>
                @endif
                <span class="font-extrabold text-2xl md:text-3xl text-textprimary leading-none">
                    {{ number_format($currentPrice, 0) }} Tk
                </span>
                @if($onSale && $saveAmount > 0)
                <span class="px-3 py-1 bg-textprimary text-white text-xs font-bold rounded">Save {{ number_format($saveAmount, 0) }} Tk</span>
                @endif
            </div>

            {{-- Stock Status --}}
            <div class="flex items-center gap-2 mb-4 text-sm">
                <span class="font-semibold text-textprimary">Stock Status :</span>
                <span class="font-semibold text-green-600">In Stock</span>
            </div>

            {{-- Size / Variants --}}
            @if($variants->isNotEmpty())
            <div class="mb-4">
                <div class="flex items-center gap-2 mb-2">
                    <p class="text-sm font-semibold text-textprimary">সাইজ বেছে নিন :</p>
                    <span x-show="selectedVariant" x-cloak
                          class="text-xs font-bold text-primary"
                          x-text="selectedVariant?.size || selectedVariant?.name"></span>
                </div>
                <div class="flex flex-wrap gap-2" data-size-picker>
                    @foreach($variants as $variant)
                    <button type="button"
                        @click="selectedVariant = {{ json_encode(['id' => $variant->id, 'size' => $variant->size, 'name' => $variant->size]) }}; sizeError = ''"
                        class="px-4 py-2 border-2 text-sm font-semibold transition-all duration-150 active:scale-95 focus:outline-none"
                        :class="selectedVariant?.id === {{ $variant->id }}
                            ? 'border-primary bg-primary text-white'
                            : 'border-gray-300 text-textprimary hover:border-primary hover:text-primary'">
                        {{ $variant->size }}
                    </button>
                    @endforeach
                </div>
                <p x-show="sizeError" x-cloak class="text-xs text-red-500 mt-2 font-medium" x-text="sizeError"></p>
            </div>
            @endif

            {{-- Quantity Selector --}}
            <div class="flex items-center gap-0 mb-5">
                <button type="button"
                    @click="qty = Math.max(1, qty - 1)"
                    class="w-9 h-9 border border-gray-300 flex items-center justify-center text-lg font-bold text-textprimary hover:bg-gray-50 transition-colors focus:outline-none">
                    -
                </button>
                <div class="w-12 h-9 border-t border-b border-gray-300 flex items-center justify-center font-semibold text-sm text-textprimary" x-text="qty"></div>
                <button type="button"
                    @click="qty = Math.min(20, qty + 1)"
                    class="w-9 h-9 border border-gray-300 flex items-center justify-center text-lg font-bold text-textprimary hover:bg-gray-50 transition-colors focus:outline-none">
                    +
                </button>
            </div>

            {{-- CTA Buttons --}}
            <div class="grid grid-cols-2 gap-3 mb-6">
                {{-- Add to Cart --}}
                <button type="button"
                    @click="addToCartFromPage()"
                    class="flex items-center justify-center gap-2 py-3.5 bg-textprimary hover:bg-gray-800 active:scale-[0.98] text-white font-bold text-sm transition-all duration-150 focus:outline-none">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    Add To Cart
                </button>
                {{-- Order Now --}}
                <button type="button"
                    @click="openOrder()"
                    class="product-order-btn flex items-center justify-center py-3.5 bg-accent hover:bg-yellow-500 active:scale-[0.98] text-white font-bold text-sm transition-all duration-150 focus:outline-none">
                    Order Now
                </button>
            </div>

            {{-- Accordion Sections --}}
            <div class="border-t border-gray-200">

                {{-- Short Description --}}
                <div x-data="{ open: false }" class="border-b border-gray-200">
                    <button type="button"
                        @click="open = !open"
                        class="w-full flex items-center gap-3 py-4 text-left focus:outline-none">
                        <svg class="w-4 h-4 text-textprimary flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="flex-1 font-bold text-sm text-textprimary">Short Description</span>
                        <svg class="w-4 h-4 text-textsecondary transition-transform duration-200 flex-shrink-0" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="open" x-transition class="pb-4 px-7 text-sm text-textsecondary leading-relaxed">
                        @if($product->short_description)
                            {{ $product->short_description }}
                        @else
                            <span class="text-gray-400">কোনো সংক্ষিপ্ত বিবরণ নেই।</span>
                        @endif
                    </div>
                </div>

                {{-- Product Description --}}
                <div x-data="{ open: false }" class="border-b border-gray-200">
                    <button type="button"
                        @click="open = !open"
                        class="w-full flex items-center gap-3 py-4 text-left focus:outline-none">
                        <svg class="w-4 h-4 text-textprimary flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <span class="flex-1 font-bold text-sm text-textprimary">Product Description</span>
                        <svg class="w-4 h-4 text-textsecondary transition-transform duration-200 flex-shrink-0" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="open" x-transition class="pb-4 px-7 text-sm text-textsecondary leading-relaxed">
                        @if($product->description)
                            {!! nl2br(e($product->description)) !!}
                        @else
                            <span class="text-gray-400">কোনো বিবরণ নেই।</span>
                        @endif
                    </div>
                </div>

                {{-- Why Choose Us --}}
                <div x-data="{ open: false }" class="border-b border-gray-200">
                    <button type="button"
                        @click="open = !open"
                        class="w-full flex items-center gap-3 py-4 text-left focus:outline-none">
                        <svg class="w-4 h-4 text-textprimary flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                        </svg>
                        <span class="flex-1 font-bold text-sm text-textprimary">Why Choose Us</span>
                        <svg class="w-4 h-4 text-textsecondary transition-transform duration-200 flex-shrink-0" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="open" x-transition class="pb-4 px-7 text-sm text-textsecondary leading-relaxed">
                        @if($whyChooseUs)
                            {!! nl2br(e($whyChooseUs)) !!}
                        @else
                            <ul class="space-y-2">
                                <li class="flex items-start gap-2"><span class="text-green-500 font-bold mt-0.5">✓</span> সেরা মানের পণ্য</li>
                                <li class="flex items-start gap-2"><span class="text-green-500 font-bold mt-0.5">✓</span> দ্রুত ডেলিভারি</li>
                                <li class="flex items-start gap-2"><span class="text-green-500 font-bold mt-0.5">✓</span> ক্যাশ অন ডেলিভারি সুবিধা</li>
                                <li class="flex items-start gap-2"><span class="text-green-500 font-bold mt-0.5">✓</span> সহজ রিটার্ন পলিসি</li>
                            </ul>
                        @endif
                    </div>
                </div>

                {{-- Return Policy --}}
                <div x-data="{ open: false }" class="border-b border-gray-200">
                    <button type="button"
                        @click="open = !open"
                        class="w-full flex items-center gap-3 py-4 text-left focus:outline-none">
                        <svg class="w-4 h-4 text-textprimary flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                        <span class="flex-1 font-bold text-sm text-textprimary">Return Policy</span>
                        <svg class="w-4 h-4 text-textsecondary transition-transform duration-200 flex-shrink-0" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="open" x-transition class="pb-4 px-7 text-sm text-textsecondary leading-relaxed">
                        @if($returnPolicy)
                            {!! nl2br(e($returnPolicy)) !!}
                        @else
                            পণ্য পেয়ে সমস্যা হলে ৭ দিনের মধ্যে আমাদের সাথে যোগাযোগ করুন। ত্রুটিপূর্ণ পণ্য বিনামূল্যে পরিবর্তন করা হবে।
                        @endif
                    </div>
                </div>

            </div>
            {{-- end accordions --}}

        </div>
        {{-- end right column --}}

    </div>
    {{-- end grid --}}

    {{-- Related Products --}}
    @if($related->isNotEmpty())
    <div class="mt-12">
        <h2 class="font-heading font-extrabold text-xl text-textprimary mb-5">একই ক্যাটাগরির আরও পণ্য</h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 md:gap-4">
            @foreach($related as $rel)
                <x-product-card :product="$rel" />
            @endforeach
        </div>
    </div>
    @endif

</div>

{{-- Image Zoom Lightbox --}}
<div x-show="zoomOpen"
     x-cloak
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     @click="zoomOpen = false"
     @keydown.escape.window="zoomOpen = false"
     class="fixed inset-0 z-[60] flex items-center justify-center bg-black/90 cursor-zoom-out p-4">
    <button type="button"
            @click.stop="zoomOpen = false"
            class="absolute top-4 right-4 w-10 h-10 bg-white/20 hover:bg-white/40 rounded-full flex items-center justify-center text-white transition-colors focus:outline-none">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
        </svg>
    </button>
    <img :src="zoomImage" alt="{{ $product->name }}"
         @click.stop
         class="max-w-full max-h-full object-contain shadow-2xl"
         style="max-height: 90vh; max-width: 90vw;">
</div>

{{-- Mobile Sticky CTA --}}
<div x-show="stickyVisible"
     x-cloak
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0 translate-y-4"
     x-transition:enter-end="opacity-100 translate-y-0"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100 translate-y-0"
     x-transition:leave-end="opacity-0 translate-y-4"
     class="fixed bottom-16 left-0 right-0 z-30 md:hidden px-4 pb-2">
    <div class="bg-white rounded-2xl shadow-xl border border-gray-100 flex items-center gap-3 px-4 py-3">
        @if($primaryImage)
        <img src="{{ $primaryImage->url }}" alt="{{ $product->name }}"
             class="w-10 h-12 rounded object-cover flex-shrink-0">
        @endif
        <div class="flex-1 min-w-0">
            <p class="text-xs text-textsecondary leading-tight line-clamp-1">{{ $product->name }}</p>
            <p class="font-extrabold text-base text-textprimary leading-tight">{{ number_format($currentPrice, 0) }} Tk</p>
        </div>
        <button type="button"
            @click="openOrder()"
            class="flex-shrink-0 px-5 py-2.5 bg-accent hover:bg-yellow-500 active:scale-95 text-white font-extrabold text-sm transition-all focus:outline-none">
            Order Now
        </button>
    </div>
</div>

</div>{{-- end productPage() --}}

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof fbq === 'function') {
        fbq('track', 'ViewContent', {
            content_ids: ['{{ $product->id }}'],
            content_name: @json($product->name),
            content_type: 'product',
            value: {{ $currentPrice }},
            currency: 'BDT'
        });
    }
});

function productPage() {
    return {
        activeImage: @json($primaryImage ? $primaryImage->url : ''),
        images: {!! $imagesJson !!},
        zoomOpen: false,
        zoomImage: '',
        stickyVisible: false,
        selectedVariant: null,
        qty: 1,
        sizeError: '',

        init() {
            const orderBtn = document.querySelector('.product-order-btn');
            if (orderBtn) {
                const observer = new IntersectionObserver(
                    ([entry]) => { this.stickyVisible = !entry.isIntersecting; },
                    { threshold: 0 }
                );
                observer.observe(orderBtn);
            }
        },

        openOrder() {
            @if($variants->isNotEmpty())
            if (!this.selectedVariant) {
                this.sizeError = 'অনুগ্রহ করে একটি সাইজ বেছে নিন';
                document.querySelector('[data-size-picker]')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return;
            }
            @endif
            this.sizeError = '';

            const data = {!! $productData !!};
            data.qty = this.qty;

            if (this.selectedVariant) {
                data.variants = data.variants.map(v =>
                    v.id === this.selectedVariant.id ? { ...v, selected: true } : v
                );
            }

            this.$dispatch('quick-order-open', data);
        },

        addToCartFromPage() {
            @if($variants->isNotEmpty())
            if (!this.selectedVariant) {
                this.sizeError = 'অনুগ্রহ করে একটি সাইজ বেছে নিন';
                document.querySelector('[data-size-picker]')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return;
            }
            @endif
            this.sizeError = '';

            const data = {!! $productData !!};
            data.qty = this.qty;
            data.selectedVariant = this.selectedVariant;

            this.$dispatch('add-to-cart', data);
        },
    };
}
</script>
@endpush

@endsection
