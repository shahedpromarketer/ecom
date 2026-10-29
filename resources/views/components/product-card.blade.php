@props(['product'])

@php
    $primaryImage = $product->images->firstWhere('is_primary', true) ?? $product->images->first();
    $imageUrl = $primaryImage ? $primaryImage->url : 'https://placehold.co/400x500/f3f4f6/9ca3af?text=No+Image';
    $isNew = $product->created_at->diffInDays(now()) <= 14;
    $onSale = !is_null($product->sale_price) && $product->sale_price < $product->regular_price;
    $discount = ($onSale && $product->regular_price > 0)
        ? round((($product->regular_price - $product->sale_price) / $product->regular_price) * 100)
        : 0;
    $currentPrice = $product->sale_price ?? $product->price;
    $variants = $product->relationLoaded('variants') ? $product->variants : collect();

    $productData = json_encode([
        'id'            => $product->id,
        'name'          => $product->name,
        'price'         => (float) $product->price,
        'regular_price' => (float) ($product->regular_price ?? $product->price),
        'sale_price'    => $product->sale_price ? (float) $product->sale_price : null,
        'image'         => $imageUrl,
        'url'           => route('product.show', $product->slug),
        'variants'      => $variants->map(fn($v) => ['id' => $v->id, 'size' => $v->size ?? $v->name, 'name' => $v->name ?? $v->size])->values()->toArray(),
    ]);
@endphp

<article class="product-card group relative bg-white rounded-2xl overflow-hidden">

    {{-- Image Container --}}
    <div class="relative overflow-hidden bg-gray-50" style="aspect-ratio: 3/4;">
        <a href="{{ route('product.show', $product->slug) }}" class="block w-full h-full">
            <img src="{{ $imageUrl }}"
                 alt="{{ $product->name }}"
                 loading="lazy"
                 class="w-full h-full object-cover transition-transform duration-500 ease-out group-hover:scale-110">
        </a>

        {{-- Badges (top left) --}}
        <div class="absolute top-2.5 left-2.5 flex flex-col gap-1.5 z-10">
            @if($onSale && $discount >= 5)
            <span class="inline-block px-2 py-0.5 bg-red-500 text-white text-[10px] font-bold rounded-lg shadow-sm">
                -{{ $discount }}%
            </span>
            @endif
            @if($isNew && !$onSale)
            <span class="inline-block px-2 py-0.5 bg-emerald-500 text-white text-[10px] font-bold rounded-lg shadow-sm">
                NEW
            </span>
            @endif
        </div>

        {{-- Wishlist (top right, hover on desktop) --}}
        <button class="absolute top-2.5 right-2.5 z-10 w-8 h-8 bg-white/95 rounded-xl flex items-center justify-center shadow-sm
                       opacity-100 md:opacity-0 md:group-hover:opacity-100 transition-all duration-200
                       hover:bg-red-50 active:scale-90"
            onclick="event.preventDefault(); window.showToast && showToast('উইশলিস্টে যোগ হয়েছে!', 'success')"
            title="Wishlist">
            <svg class="w-4 h-4 text-gray-400 hover:text-red-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
            </svg>
        </button>
    </div>

    {{-- Content --}}
    <div class="p-3 md:p-3.5">
        {{-- Product Name --}}
        <a href="{{ route('product.show', $product->slug) }}"
           class="block font-semibold text-[13px] md:text-sm text-textprimary leading-snug line-clamp-2 hover:text-primary transition-colors mb-2">
            {{ $product->name }}
        </a>

        {{-- Price Row --}}
        <div class="flex items-center justify-between mb-3">
            <div class="flex items-baseline gap-1.5 flex-wrap">
                <span class="font-extrabold text-base md:text-lg text-accent leading-none">
                    ৳{{ number_format($currentPrice, 0) }}
                </span>
                @if($onSale && $product->regular_price)
                <span class="text-[11px] text-gray-400 line-through leading-none">
                    ৳{{ number_format($product->regular_price, 0) }}
                </span>
                @endif
            </div>
            {{-- Stock indicator --}}
            <span class="text-[10px] text-emerald-600 font-medium bg-emerald-50 px-1.5 py-0.5 rounded-md flex-shrink-0">
                ✓ স্টকে
            </span>
        </div>

        {{-- Single Order Button (mobile + desktop) --}}
        <button
            @click="$dispatch('quick-order-open', {{ $productData }})"
            class="w-full py-2.5 bg-primary hover:bg-opacity-90 active:scale-95 text-white text-xs font-bold rounded-xl transition-all flex items-center justify-center gap-1.5">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 7H4l1-7z"/>
            </svg>
            অর্ডার করুন
        </button>
    </div>
</article>
