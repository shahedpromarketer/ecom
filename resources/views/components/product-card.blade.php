@props(['product'])

@php
    $primaryImage = $product->images->firstWhere('is_primary', true) ?? $product->images->first();
    $imageUrl = $primaryImage ? asset('storage/' . $primaryImage->image_path) : asset('images/placeholder.jpg');
    $isNew = $product->created_at->diffInDays(now()) <= 7;
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

<article class="product-card group relative bg-white rounded-2xl overflow-hidden shadow-sm hover:shadow-lg transition-all duration-300 hover:-translate-y-1">
    {{-- Image --}}
    <a href="{{ route('product.show', $product->slug) }}" class="block product-image-wrap aspect-square bg-gray-50 overflow-hidden">
        <img src="{{ $imageUrl }}"
             alt="{{ $product->name }}"
             loading="lazy"
             class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
    </a>

    {{-- Badges --}}
    <div class="absolute top-2 left-2 flex flex-col gap-1">
        @if($onSale && $discount > 0)
        <span class="inline-flex items-center px-2 py-0.5 rounded-lg bg-red-500 text-white text-[10px] font-bold">
            -{{ $discount }}%
        </span>
        @endif
        @if($isNew)
        <span class="inline-flex items-center px-2 py-0.5 rounded-lg bg-primary text-white text-[10px] font-bold">
            NEW
        </span>
        @endif
    </div>

    {{-- Wishlist Button --}}
    <button class="absolute top-2 right-2 w-8 h-8 bg-white/90 backdrop-blur-sm rounded-xl flex items-center justify-center shadow-sm opacity-0 group-hover:opacity-100 transition-opacity duration-200 hover:bg-red-50 hover:text-red-500"
        onclick="event.preventDefault(); window.showToast('উইশলিস্টে যোগ হয়েছে!', 'success')"
        title="Wishlist">
        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
        </svg>
    </button>

    {{-- Content --}}
    <div class="p-3">
        <a href="{{ route('product.show', $product->slug) }}">
            <h3 class="text-sm font-medium text-textprimary leading-snug line-clamp-1 hover:text-primary transition-colors mb-1">
                {{ $product->name }}
            </h3>
        </a>

        {{-- Stars (placeholder) --}}
        <div class="flex items-center gap-1 mb-2">
            <div class="flex text-amber-400">
                @for($i = 1; $i <= 5; $i++)
                <svg class="w-3 h-3 {{ $i <= 4 ? 'fill-current' : 'fill-gray-200' }}" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                </svg>
                @endfor
            </div>
            <span class="text-[10px] text-textsecondary">(4.5)</span>
        </div>

        {{-- Price --}}
        <div class="flex items-center gap-1.5 mb-3">
            <span class="font-bold text-accent text-base">৳{{ number_format($currentPrice, 0) }}</span>
            @if($onSale && $product->regular_price)
            <span class="text-xs text-gray-400 line-through">৳{{ number_format($product->regular_price, 0) }}</span>
            @endif
        </div>

        {{-- Order Button --}}
        <button
            @click="$dispatch('quick-order-open', {{ $productData }}); openQuickOrder({{ $productData }})"
            class="w-full py-2 text-xs font-semibold text-white bg-primary hover:bg-opacity-90 active:scale-95 rounded-xl transition-all duration-150 flex items-center justify-center gap-1">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 7H4l1-7z"/>
            </svg>
            অর্ডার করুন
        </button>
    </div>
</article>
