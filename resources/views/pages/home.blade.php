@extends('layouts.frontend')

@section('title', ($settings['store_name'] ?? 'Clothonic') . ' - মেনস ক্লোদিং')

@section('meta')
<meta name="description" content="বাংলাদেশের সেরা মেনস ক্লোদিং শপ। সর্বোচ্চ মানের পণ্য, সেরা দাম এবং দ্রুত ডেলিভারি।">
<meta property="og:title" content="{{ $settings['store_name'] ?? 'Clothonic' }}">
<meta property="og:type" content="website">
@endsection

@section('content')

{{-- ══════════════════════════════════════════════════════ --}}
{{-- SECTION 1: HERO BANNER                               --}}
{{-- ══════════════════════════════════════════════════════ --}}
<section class="relative w-full h-[60vh] md:h-[85vh] overflow-hidden bg-primary"
         x-data="heroSlider()">

    @if($banners->isNotEmpty())
    {{-- Slides --}}
    <div class="relative w-full h-full">
        @foreach($banners as $i => $banner)
        <div data-slide="{{ $i }}"
             class="absolute inset-0 transition-all duration-700 ease-in-out"
             :class="current === {{ $i }} ? 'opacity-100 z-10' : 'opacity-0 z-0'">
            {{-- Background Image --}}
            <div class="absolute inset-0 bg-cover bg-center bg-no-repeat"
                 style="background-image: url('{{ asset('storage/' . $banner->image) }}')">
                <div class="absolute inset-0 bg-gradient-to-r from-black/70 via-black/40 to-transparent"></div>
            </div>

            {{-- Content --}}
            <div class="relative z-10 h-full flex items-center">
                <div class="container-main">
                    <div class="max-w-lg text-white">
                        @if($banner->subtitle)
                        <span class="inline-block bg-accent text-white text-xs font-bold px-3 py-1 rounded-full uppercase tracking-wider mb-4">
                            {{ $banner->subtitle }}
                        </span>
                        @endif
                        <h1 class="font-heading font-bold text-3xl md:text-5xl leading-tight mb-4 drop-shadow-lg">
                            {{ $banner->title }}
                        </h1>
                        <a href="{{ $banner->link ?? route('shop') }}"
                           class="btn-accent mt-2 text-sm md:text-base shadow-lg">
                            এখনই কিনুন
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Prev/Next Arrows (desktop only) --}}
    @if($banners->count() > 1)
    <button @click="prev()" class="hidden md:flex absolute left-4 top-1/2 -translate-y-1/2 z-20 w-11 h-11 bg-white/20 hover:bg-white/40 backdrop-blur-sm rounded-full items-center justify-center transition-all">
        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
    </button>
    <button @click="next()" class="hidden md:flex absolute right-4 top-1/2 -translate-y-1/2 z-20 w-11 h-11 bg-white/20 hover:bg-white/40 backdrop-blur-sm rounded-full items-center justify-center transition-all">
        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
    </button>

    {{-- Dot Indicators --}}
    <div class="absolute bottom-5 left-1/2 -translate-x-1/2 z-20 flex items-center gap-2">
        @foreach($banners as $i => $banner)
        <button @click="goTo({{ $i }})"
            class="transition-all duration-300 rounded-full"
            :class="current === {{ $i }} ? 'w-6 h-2 bg-white' : 'w-2 h-2 bg-white/50 hover:bg-white/70'"></button>
        @endforeach
    </div>
    @endif

    @else
    {{-- Fallback Hero --}}
    <div class="absolute inset-0 bg-gradient-to-br from-primary via-blue-900 to-primary">
        <div class="absolute inset-0 opacity-10" style="background-image: radial-gradient(circle at 2px 2px, white 1px, transparent 0); background-size: 32px 32px;"></div>
    </div>
    <div class="relative z-10 h-full flex items-center">
        <div class="container-main text-center text-white">
            <h1 class="font-heading font-extrabold text-4xl md:text-6xl mb-4">
                {{ $settings['store_name'] ?? 'Clothonic' }}
            </h1>
            <p class="text-lg md:text-xl text-white/80 mb-8">বাংলাদেশের সেরা মেনস ক্লোদিং শপ</p>
            <a href="{{ route('shop') }}" class="btn-accent text-lg shadow-xl">
                শপিং শুরু করুন
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                </svg>
            </a>
        </div>
    </div>
    @endif
</section>


{{-- ══════════════════════════════════════════════════════ --}}
{{-- SECTION 2: TRUST BADGES                              --}}
{{-- ══════════════════════════════════════════════════════ --}}
<section class="bg-white py-4 md:py-5 shadow-sm" data-animate>
    <div class="container-main">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-4">
            @foreach([
                ['icon' => '🚚', 'title' => 'ফ্রি ডেলিভারি', 'sub' => '৳' . ($settings['free_shipping_above'] ?? 999) . ' এর উপরে'],
                ['icon' => '💵', 'title' => 'ক্যাশ অন ডেলিভারি', 'sub' => 'সারাদেশে'],
                ['icon' => '🔄', 'title' => '৭ দিন রিটার্ন', 'sub' => 'সহজ রিটার্ন পলিসি'],
                ['icon' => '✅', 'title' => '১০০% অরিজিনাল', 'sub' => 'গ্যারান্টেড'],
            ] as $badge)
            <div class="flex items-center gap-3 p-3 rounded-2xl hover:bg-primary/5 hover:scale-105 transition-all duration-200 cursor-default">
                <span class="text-2xl md:text-3xl flex-shrink-0">{{ $badge['icon'] }}</span>
                <div>
                    <p class="text-xs md:text-sm font-semibold text-textprimary leading-tight">{{ $badge['title'] }}</p>
                    <p class="text-[10px] md:text-xs text-textsecondary">{{ $badge['sub'] }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>


{{-- ══════════════════════════════════════════════════════ --}}
{{-- SECTION 3: FEATURED CATEGORIES                       --}}
{{-- ══════════════════════════════════════════════════════ --}}
@if($featuredCategories->isNotEmpty())
<section class="section-padding" data-animate>
    <div class="container-main">
        <x-section-heading title="ক্যাটাগরি অনুযায়ী শপিং করুন" :link="route('shop')" link-text="সব দেখুন" />

        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-3 md:gap-4">
            @foreach($featuredCategories as $category)
            <a href="{{ route('shop', ['category' => $category->slug]) }}"
               class="group relative aspect-square rounded-2xl overflow-hidden bg-gray-200 shadow-sm hover:shadow-md hover:scale-105 transition-all duration-300">
                @if($category->image)
                <img src="{{ asset('storage/' . $category->image) }}"
                     alt="{{ $category->name }}"
                     loading="lazy"
                     class="absolute inset-0 w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
                @else
                <div class="absolute inset-0 bg-gradient-to-br from-primary/80 to-blue-800"></div>
                @endif
                <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent"></div>
                <div class="absolute bottom-0 left-0 right-0 p-3 text-white">
                    <p class="font-semibold text-xs md:text-sm leading-tight">{{ $category->name }}</p>
                    @if($category->products_count > 0)
                    <p class="text-[10px] text-white/70">{{ $category->products_count }} পণ্য</p>
                    @endif
                </div>
            </a>
            @endforeach
        </div>
    </div>
</section>
@endif


{{-- ══════════════════════════════════════════════════════ --}}
{{-- SECTION 4: NEW ARRIVALS                              --}}
{{-- ══════════════════════════════════════════════════════ --}}
@if($newArrivals->isNotEmpty())
<section class="section-padding bg-white" data-animate>
    <div class="container-main">
        <x-section-heading title="নতুন পণ্য" subtitle="সদ্য যোগ হওয়া কালেকশন" :link="route('shop', ['sort' => 'newest'])" />

        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 md:gap-5">
            @foreach($newArrivals as $product)
            <x-product-card :product="$product" />
            @endforeach
        </div>
    </div>
</section>
@endif


{{-- ══════════════════════════════════════════════════════ --}}
{{-- SECTION 5: PROMO BANNER                              --}}
{{-- ══════════════════════════════════════════════════════ --}}
<section class="py-10 md:py-14 bg-gradient-to-r from-accent to-yellow-400" data-animate>
    <div class="container-main text-center">
        <p class="text-primary/80 font-semibold text-sm uppercase tracking-widest mb-2">সীমিত সময়ের অফার</p>
        <h2 class="font-heading font-extrabold text-2xl md:text-4xl text-primary mb-4 drop-shadow">
            ৫০% পর্যন্ত ছাড়! 🎉
        </h2>
        <p class="text-primary/70 text-sm md:text-base mb-6">নির্বাচিত পণ্যে আকর্ষণীয় ডিসকাউন্ট পাচ্ছেন এখনই</p>
        <a href="{{ route('shop', ['sale' => 1]) }}"
           class="inline-flex items-center gap-2 px-8 py-3.5 bg-primary text-white font-bold rounded-xl hover:bg-opacity-90 active:scale-95 transition-all text-sm md:text-base shadow-lg">
            এখনই কিনুন
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
            </svg>
        </a>
    </div>
</section>


{{-- ══════════════════════════════════════════════════════ --}}
{{-- SECTION 6: TOP CATEGORIES (product rows)             --}}
{{-- ══════════════════════════════════════════════════════ --}}
@foreach($topCategories as $cat)
@if($cat->topProducts->isNotEmpty())
<section class="section-padding {{ $loop->even ? 'bg-white' : '' }}" data-animate>
    <div class="container-main">
        <x-section-heading :title="$cat->name" :link="route('shop', ['category' => $cat->slug])" />

        {{-- Horizontal scroll on mobile, grid on desktop --}}
        <div class="flex gap-3 md:gap-5 overflow-x-auto pb-2 md:grid md:grid-cols-4 md:overflow-visible snap-x snap-mandatory md:snap-none">
            @foreach($cat->topProducts as $product)
            <div class="flex-shrink-0 w-44 md:w-auto snap-start">
                <x-product-card :product="$product" />
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif
@endforeach


{{-- ══════════════════════════════════════════════════════ --}}
{{-- SECTION 7: BESTSELLERS / FEATURED                    --}}
{{-- ══════════════════════════════════════════════════════ --}}
@if($featuredProducts->isNotEmpty())
<section class="section-padding bg-white" data-animate>
    <div class="container-main">
        <x-section-heading title="বেস্টসেলার" subtitle="সবচেয়ে জনপ্রিয় পণ্য" :link="route('shop', ['featured' => 1])" />

        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 md:gap-5">
            @foreach($featuredProducts as $product)
            <x-product-card :product="$product" />
            @endforeach
        </div>
    </div>
</section>
@endif


{{-- ══════════════════════════════════════════════════════ --}}
{{-- SECTION 8: SALE PRODUCTS                             --}}
{{-- ══════════════════════════════════════════════════════ --}}
@if($saleProducts->isNotEmpty())
<section class="section-padding bg-red-50" data-animate>
    <div class="container-main">
        <div class="flex items-end justify-between mb-6">
            <div>
                <h2 class="font-heading font-bold text-xl md:text-2xl text-textprimary flex items-center gap-2">
                    মেগা সেল 🔥
                </h2>
                <p class="text-sm text-textsecondary mt-1">সীমিত সময়ের জন্য বিশেষ ছাড়</p>
            </div>
            <a href="{{ route('shop', ['sale' => 1]) }}"
               class="flex items-center gap-1 text-sm font-semibold text-red-500 hover:text-red-600 transition-colors flex-shrink-0 ml-4">
                সব দেখুন
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 md:gap-5">
            @foreach($saleProducts as $product)
            <x-product-card :product="$product" />
            @endforeach
        </div>
    </div>
</section>
@endif


{{-- ══════════════════════════════════════════════════════ --}}
{{-- SECTION 9: WHY CHOOSE US                             --}}
{{-- ══════════════════════════════════════════════════════ --}}
<section class="section-padding bg-white" data-animate>
    <div class="container-main">
        <div class="text-center mb-10">
            <h2 class="font-heading font-bold text-2xl md:text-3xl text-textprimary mb-2">কেন আমাদের বেছে নেবেন?</h2>
            <p class="text-textsecondary text-sm md:text-base">আমরা সর্বোচ্চ মানের পণ্য ও সেবা প্রদানে প্রতিশ্রুতিবদ্ধ</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @foreach([
                ['icon' => '🧵', 'title' => 'উন্নত মানের কাপড়', 'desc' => 'আমরা শুধুমাত্র সর্বোচ্চ মানের কাপড় ব্যবহার করি। প্রতিটি পণ্য কঠোর মান নিয়ন্ত্রণ প্রক্রিয়ার মধ্য দিয়ে যায়।'],
                ['icon' => '💰', 'title' => 'সেরা দাম', 'desc' => 'বাজারে সবচেয়ে প্রতিযোগিতামূলক মূল্যে সেরা মানের পণ্য পাবেন। কোনো লুকানো চার্জ নেই।'],
                ['icon' => '🚀', 'title' => 'দ্রুত ডেলিভারি', 'desc' => 'অর্ডার দেওয়ার ২৪-৭২ ঘন্টার মধ্যে আপনার দরজায় পৌঁছে দেওয়ার চেষ্টা করি।'],
            ] as $item)
            <div class="text-center p-6 rounded-2xl bg-[#F8F8F8] hover:shadow-md hover:-translate-y-1 transition-all duration-300 group">
                <div class="text-4xl mb-4 group-hover:scale-110 transition-transform duration-200">{{ $item['icon'] }}</div>
                <h3 class="font-heading font-bold text-base md:text-lg text-textprimary mb-2">{{ $item['title'] }}</h3>
                <p class="text-sm text-textsecondary leading-relaxed">{{ $item['desc'] }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

@endsection
