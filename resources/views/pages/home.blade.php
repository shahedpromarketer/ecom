@extends('layouts.frontend')

@section('title', ($settings['store_name'] ?? 'Clothonic') . ' — বাংলাদেশের সেরা মেনস ক্লোদিং')

@section('meta')
<meta name="description" content="বাংলাদেশের সেরা মেনস ক্লোদিং শপ। সর্বোচ্চ মানের পণ্য, সেরা দাম এবং দ্রুত ডেলিভারি।">
@endsection

@section('content')

{{-- ═══════════════════════════════════════════════════════════════════════ --}}
{{-- 1. HERO BANNER                                                         --}}
{{-- Breakpoints: mobile fluid | sm(≥768) | md(≥992) | lg(≥1200)           --}}
{{-- ═══════════════════════════════════════════════════════════════════════ --}}
<section class="relative w-full overflow-hidden bg-primary"
         style="aspect-ratio: 1920/800; max-height: 800px; min-height: 220px;"
         x-data="heroSlider()">

    @if($banners->isNotEmpty())
        @foreach($banners as $i => $banner)
        <div data-slide="{{ $i }}"
             class="absolute inset-0 transition-all duration-700 ease-in-out"
             :class="current === {{ $i }} ? 'opacity-100 z-10' : 'opacity-0 z-0'">
            <img src="{{ asset('storage/'.$banner->image) }}"
                 alt="{{ $banner->title }}"
                 class="w-full h-full object-contain object-center">
        </div>
        @endforeach

        @if($banners->count() > 1)
        <button @click="prev()"
            class="hidden md:flex absolute left-5 top-1/2 -translate-y-1/2 z-20 w-11 h-11 rounded-full bg-white/20 hover:bg-white/40 backdrop-blur-sm items-center justify-center transition-all">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
        </button>
        <button @click="next()"
            class="hidden md:flex absolute right-5 top-1/2 -translate-y-1/2 z-20 w-11 h-11 rounded-full bg-white/20 hover:bg-white/40 backdrop-blur-sm items-center justify-center transition-all">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
        </button>
        <div class="absolute bottom-5 left-1/2 -translate-x-1/2 z-20 flex gap-2">
            @foreach($banners as $i => $b)
            <button @click="goTo({{ $i }})"
                class="rounded-full transition-all duration-300"
                :class="current === {{ $i }} ? 'w-6 h-2 bg-white' : 'w-2 h-2 bg-white/50'"></button>
            @endforeach
        </div>
        @endif

    @else
    {{-- Fallback hero --}}
    <div class="absolute inset-0" style="background:linear-gradient(135deg,#1B2A4A 0%,#243a65 50%,#1B2A4A 100%);">
        <div class="absolute inset-0 opacity-[0.06]"
             style="background-image:radial-gradient(circle at 1px 1px,#fff 1px,transparent 0);background-size:28px 28px;"></div>
    </div>
    <div class="relative z-10 h-full flex items-center">
        <div class="container-main">
            <div class="max-w-xl">
                <p class="text-accent font-semibold text-xs uppercase tracking-widest mb-3">Bangladesh's Premium Men's Store</p>
                <h1 class="font-heading font-extrabold text-3xl sm:text-4xl md:text-5xl text-white mb-4 leading-tight">
                    {{ $settings['store_name'] ?? 'Clothonic' }}
                </h1>
                <p class="text-white/70 text-sm sm:text-base mb-8 max-w-md">
                    সেরা মানের পোশাক, সেরা দামে। সারাদেশে ডেলিভারি।
                </p>
                <div class="flex flex-col sm:flex-row gap-3">
                    <a href="{{ route('shop') }}" class="btn-accent shadow-xl">
                        শপিং শুরু করুন
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                    </a>
                    <a href="{{ route('shop', ['sale' => 1]) }}"
                       class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-white/10 hover:bg-white/20 text-white font-semibold rounded-xl border border-white/30 transition-all">
                        অফার দেখুন 🔥
                    </a>
                </div>
            </div>
        </div>
    </div>
    @endif
</section>


{{-- ═══════════════════════════════════════════════════════════════════════ --}}
{{-- 2. TRUST BADGES                                                        --}}
{{-- Mobile: 2-col (4÷2) | sm(768)+: 4-col (12÷3 each)                    --}}
{{-- ═══════════════════════════════════════════════════════════════════════ --}}
<section class="bg-white border-b border-gray-100">
    <div class="container-main">
        <div class="grid grid-cols-2 sm:grid-cols-4 divide-x divide-y sm:divide-y-0 divide-gray-100">
            @foreach([
                ['emoji'=>'🚚','title'=>'ফ্রি ডেলিভারি','sub'=>'৳'.($settings['free_shipping_above']??999).' এর উপরে'],
                ['emoji'=>'💵','title'=>'ক্যাশ অন ডেলিভারি','sub'=>'সারাদেশে পাওয়া যায়'],
                ['emoji'=>'🔄','title'=>'৭ দিন রিটার্ন','sub'=>'ঝামেলামুক্ত রিটার্ন'],
                ['emoji'=>'✅','title'=>'১০০% অরিজিনাল','sub'=>'গ্যারান্টেড মানসম্পন্ন'],
            ] as $b)
            <div class="trust-badge flex items-center gap-3 px-4 sm:px-5 py-5">
                <span class="text-2xl sm:text-3xl leading-none flex-shrink-0">{{ $b['emoji'] }}</span>
                <div>
                    <p class="text-xs sm:text-sm font-semibold text-textprimary leading-tight">{{ $b['title'] }}</p>
                    <p class="text-[10px] sm:text-xs text-textsecondary mt-0.5">{{ $b['sub'] }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>


{{-- ═══════════════════════════════════════════════════════════════════════ --}}
{{-- 3. FEATURED CATEGORIES                                                 --}}
{{-- Mobile: 3-col | sm(768): 4-col | md(992)+: 6-col (each = 2 of 12)    --}}
{{-- ═══════════════════════════════════════════════════════════════════════ --}}
@if($featuredCategories->isNotEmpty())
<section class="section-padding bg-[#F8F8F8]">
    <div class="container-main">
        <div class="text-center mb-8 md:mb-10">
            <div class="section-divider mx-auto"></div>
            <h2 class="text-xl sm:text-2xl text-textprimary">ক্যাটাগরি অনুযায়ী শপিং করুন</h2>
            <p class="text-textsecondary text-sm mt-1">আপনার পছন্দের স্টাইল বেছে নিন</p>
        </div>
        <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 gap-3 md:gap-5">
            @foreach($featuredCategories as $category)
            <a href="{{ route('shop', ['category' => $category->slug]) }}"
               class="category-card group relative rounded-2xl overflow-hidden shadow-sm"
               style="aspect-ratio:1;">
                @if($category->image)
                <img src="{{ asset('storage/'.$category->image) }}" alt="{{ $category->name }}"
                     loading="lazy" class="absolute inset-0 w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
                @else
                <div class="absolute inset-0 bg-gradient-to-br from-primary/80 to-blue-800"></div>
                @endif
                <div class="absolute inset-0" style="background:linear-gradient(to top,rgba(0,0,0,.7) 0%,rgba(0,0,0,.1) 60%,transparent 100%)"></div>
                <div class="absolute bottom-0 left-0 right-0 p-2 sm:p-3 text-white">
                    <p class="font-semibold text-[10px] sm:text-xs md:text-sm leading-tight">{{ $category->name }}</p>
                    @if(isset($category->products_count) && $category->products_count > 0)
                    <p class="text-[9px] sm:text-[10px] text-white/70 mt-0.5">{{ $category->products_count }} পণ্য</p>
                    @endif
                </div>
            </a>
            @endforeach
        </div>
    </div>
</section>
@endif


{{-- ═══════════════════════════════════════════════════════════════════════ --}}
{{-- 4. NEW ARRIVALS                                                        --}}
{{-- Mobile: 2-col (4÷2) | sm(768): 3-col | lg(1200): 4-col (12÷4=3 each) --}}
{{-- ═══════════════════════════════════════════════════════════════════════ --}}
@if($newArrivals->isNotEmpty())
<section class="section-padding bg-white">
    <div class="container-main">
        <div class="flex items-end justify-between mb-7">
            <div>
                <div class="section-divider"></div>
                <h2 class="text-xl sm:text-2xl text-textprimary">নতুন পণ্য</h2>
                <p class="text-textsecondary text-sm mt-0.5">সদ্য যোগ হওয়া কালেকশন</p>
            </div>
            <a href="{{ route('shop', ['sort'=>'newest']) }}"
               class="flex items-center gap-1 text-sm font-semibold text-primary hover:text-accent transition-colors shrink-0 ml-4">
                সব দেখুন
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>
        {{-- 2 cols mobile → 3 cols sm(768) → 4 cols lg(1200) --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-5 md:gap-6">
            @foreach($newArrivals as $product)
            <x-product-card :product="$product"/>
            @endforeach
        </div>
    </div>
</section>
@endif


{{-- ═══════════════════════════════════════════════════════════════════════ --}}
{{-- 5. PROMO BANNER                                                        --}}
{{-- ═══════════════════════════════════════════════════════════════════════ --}}
<section class="relative overflow-hidden" style="background:linear-gradient(135deg,#F59E0B 0%,#f7b731 50%,#F59E0B 100%);">
    <div class="absolute inset-0 opacity-10"
         style="background-image:radial-gradient(circle at 1px 1px,#fff 1px,transparent 0);background-size:20px 20px;"></div>
    <div class="container-main py-12 md:py-16 relative z-10 text-center">
        <span class="inline-block bg-white/20 text-primary text-xs font-bold px-3 py-1 rounded-full uppercase tracking-widest mb-3">
            সীমিত সময়ের অফার
        </span>
        <h2 class="font-heading font-extrabold text-2xl sm:text-3xl md:text-4xl text-primary mb-3 leading-tight">
            ৫০% পর্যন্ত ছাড়! 🎉
        </h2>
        <p class="text-primary/70 text-sm md:text-base mb-7 max-w-sm mx-auto">
            নির্বাচিত পণ্যে আকর্ষণীয় ডিসকাউন্ট — আজই অর্ডার করুন
        </p>
        <a href="{{ route('shop', ['sale'=>1]) }}"
           class="inline-flex items-center gap-2 px-8 py-3.5 bg-primary text-white font-bold rounded-xl hover:bg-opacity-90 active:scale-95 transition-all shadow-lg">
            এখনই কিনুন
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
        </a>
    </div>
</section>


{{-- ═══════════════════════════════════════════════════════════════════════ --}}
{{-- 6. TOP CATEGORY PRODUCT ROWS                                           --}}
{{-- Mobile: horizontal scroll (4-col fluid) | sm: 2-col | lg: 4-col       --}}
{{-- ═══════════════════════════════════════════════════════════════════════ --}}
@foreach($topCategories as $cat)
@if($cat->topProducts->isNotEmpty())
<section class="section-padding {{ $loop->even ? 'bg-white' : 'bg-[#F8F8F8]' }}">
    <div class="container-main">
        <div class="flex items-end justify-between mb-7">
            <div>
                <div class="section-divider"></div>
                <h2 class="text-xl sm:text-2xl text-textprimary">{{ $cat->name }}</h2>
            </div>
            <a href="{{ route('shop', ['category'=>$cat->slug]) }}"
               class="flex items-center gap-1 text-sm font-semibold text-primary hover:text-accent transition-colors shrink-0 ml-4">
                সব দেখুন
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>
        {{-- Mobile: horizontal scroll | sm(768)+: 2-col grid | lg(1200): 4-col --}}
        <div class="flex gap-3 overflow-x-auto pb-3 scrollbar-hide snap-x snap-mandatory
                    sm:grid sm:grid-cols-2 sm:overflow-visible sm:pb-0 sm:gap-5
                    lg:grid-cols-4 lg:gap-6">
            @foreach($cat->topProducts as $product)
            <div class="flex-none w-44 sm:w-auto snap-start">
                <x-product-card :product="$product"/>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif
@endforeach


{{-- ═══════════════════════════════════════════════════════════════════════ --}}
{{-- 7. BESTSELLERS                                                         --}}
{{-- Mobile: 2-col | sm(768): 3-col | lg(1200): 4-col                      --}}
{{-- ═══════════════════════════════════════════════════════════════════════ --}}
@if($featuredProducts->isNotEmpty())
<section class="section-padding bg-white">
    <div class="container-main">
        <div class="flex items-end justify-between mb-7">
            <div>
                <div class="section-divider"></div>
                <h2 class="text-xl sm:text-2xl text-textprimary">বেস্টসেলার</h2>
                <p class="text-textsecondary text-sm mt-0.5">সবচেয়ে জনপ্রিয় পণ্য</p>
            </div>
            <a href="{{ route('shop', ['featured'=>1]) }}"
               class="flex items-center gap-1 text-sm font-semibold text-primary hover:text-accent transition-colors shrink-0 ml-4">
                সব দেখুন
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-5 md:gap-6">
            @foreach($featuredProducts as $product)
            <x-product-card :product="$product"/>
            @endforeach
        </div>
    </div>
</section>
@endif


{{-- ═══════════════════════════════════════════════════════════════════════ --}}
{{-- 8. SALE PRODUCTS                                                       --}}
{{-- Mobile: 2-col | sm(768): 3-col | lg(1200): 4-col                      --}}
{{-- ═══════════════════════════════════════════════════════════════════════ --}}
@if($saleProducts->isNotEmpty())
<section class="section-padding" style="background:linear-gradient(135deg,#fff5f5 0%,#fff 100%);">
    <div class="container-main">
        <div class="flex items-end justify-between mb-7">
            <div>
                <div class="section-divider" style="background:linear-gradient(90deg,#EF4444,#F59E0B,#EF4444);"></div>
                <h2 class="text-xl sm:text-2xl text-textprimary">মেগা সেল 🔥</h2>
                <p class="text-textsecondary text-sm mt-0.5">সীমিত সময়ের জন্য বিশেষ ছাড়</p>
            </div>
            <a href="{{ route('shop', ['sale'=>1]) }}"
               class="flex items-center gap-1 text-sm font-semibold text-red-500 hover:text-red-600 transition-colors shrink-0 ml-4">
                সব দেখুন
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-5 md:gap-6">
            @foreach($saleProducts as $product)
            <x-product-card :product="$product"/>
            @endforeach
        </div>
    </div>
</section>
@endif


{{-- ═══════════════════════════════════════════════════════════════════════ --}}
{{-- 9. WHY CHOOSE US                                                       --}}
{{-- Mobile: 1-col stacked | sm(768): 3-col (each = 4 of 12)               --}}
{{-- ═══════════════════════════════════════════════════════════════════════ --}}
<section class="section-padding bg-[#F8F8F8]">
    <div class="container-main">
        <div class="text-center mb-10">
            <div class="section-divider mx-auto"></div>
            <h2 class="text-xl sm:text-2xl text-textprimary mb-2">কেন আমাদের বেছে নেবেন?</h2>
            <p class="text-textsecondary text-sm max-w-md mx-auto">আমরা সর্বোচ্চ মানের পণ্য ও সেবা প্রদানে প্রতিশ্রুতিবদ্ধ</p>
        </div>
        {{-- 1 col mobile → 3 col sm (each = 4 of 12 columns) --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 md:gap-6">
            @foreach([
                ['emoji'=>'🧵','title'=>'উন্নত মানের কাপড়','desc'=>'প্রতিটি পণ্য কঠোর মান নিয়ন্ত্রণ প্রক্রিয়ার মধ্য দিয়ে যায়। শুধুমাত্র সর্বোচ্চ মানের উপকরণ ব্যবহার করা হয়।'],
                ['emoji'=>'💰','title'=>'সেরা দাম','desc'=>'বাজারে সবচেয়ে প্রতিযোগিতামূলক মূল্যে সেরা মানের পণ্য পাবেন। কোনো লুকানো চার্জ নেই।'],
                ['emoji'=>'🚀','title'=>'দ্রুত ডেলিভারি','desc'=>'অর্ডার দেওয়ার ২৪–৭২ ঘণ্টার মধ্যে আপনার দরজায়। সারাদেশে ক্যাশ অন ডেলিভারি।'],
            ] as $item)
            <div class="bg-white rounded-2xl p-6 text-center shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300 group">
                <div class="w-14 h-14 bg-primary/5 rounded-2xl flex items-center justify-center mx-auto mb-4 group-hover:bg-primary/10 transition-colors">
                    <span class="text-2xl">{{ $item['emoji'] }}</span>
                </div>
                <h3 class="font-heading font-bold text-base sm:text-lg text-textprimary mb-2">{{ $item['title'] }}</h3>
                <p class="text-sm text-textsecondary leading-relaxed">{{ $item['desc'] }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

@endsection
