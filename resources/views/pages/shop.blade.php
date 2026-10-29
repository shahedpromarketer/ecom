@extends('layouts.frontend')

@section('title', ($currentCategory ? $currentCategory->name . ' — ' : 'সকল পণ্য — ') . ($settings['store_name'] ?? 'Clothonic'))

@section('content')

{{-- Hero / Category Banner --}}
<div class="bg-gradient-to-r from-primary/10 to-accent/5 border-b border-gray-100">
    <div class="container-main py-8 md:py-10">

        {{-- Breadcrumb --}}
        <nav class="flex items-center gap-2 text-xs text-textsecondary mb-3">
            <a href="{{ route('home') }}" class="hover:text-primary transition-colors">হোম</a>
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            @if($currentCategory)
                @if($currentCategory->parent)
                    <a href="{{ route('shop', ['category' => $currentCategory->parent->slug]) }}" class="hover:text-primary transition-colors">{{ $currentCategory->parent->name }}</a>
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                @endif
                <span class="text-textprimary font-medium">{{ $currentCategory->name }}</span>
            @else
                <span class="text-textprimary font-medium">সকল পণ্য</span>
            @endif
        </nav>

        <h1 class="font-heading font-extrabold text-2xl md:text-3xl text-textprimary">
            {{ $currentCategory ? $currentCategory->name : 'সকল পণ্য' }}
        </h1>

        @if($currentCategory && $currentCategory->description)
        <p class="text-sm text-textsecondary mt-1 max-w-xl">{{ $currentCategory->description }}</p>
        @endif

        {{-- Sub-category pills --}}
        @if($subCategories->isNotEmpty())
        <div class="flex flex-wrap gap-2 mt-4">
            <a href="{{ route('shop', ['category' => $currentCategory->slug]) }}"
               class="px-4 py-1.5 rounded-full text-xs font-semibold transition-all
                      {{ !request('sub') ? 'bg-primary text-white shadow-sm' : 'bg-white text-textprimary border border-gray-200 hover:border-primary hover:text-primary' }}">
                সব
            </a>
            @foreach($subCategories as $sub)
            <a href="{{ route('shop', ['category' => $currentCategory->slug, 'sub' => $sub->slug]) }}"
               class="px-4 py-1.5 rounded-full text-xs font-semibold transition-all
                      {{ request('sub') === $sub->slug ? 'bg-primary text-white shadow-sm' : 'bg-white text-textprimary border border-gray-200 hover:border-primary hover:text-primary' }}">
                {{ $sub->name }}
            </a>
            @endforeach
        </div>
        @endif

    </div>
</div>

{{-- Product Grid --}}
<div class="container-main section-padding">

    @if($products->isNotEmpty())

    {{-- Count + Sort bar --}}
    <div class="flex items-center justify-between mb-6">
        <p class="text-sm text-textsecondary">
            <span class="font-semibold text-textprimary">{{ $products->total() }}</span> টি পণ্য পাওয়া গেছে
        </p>
    </div>

    {{-- Grid --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 md:gap-4">
        @foreach($products as $product)
            <x-product-card :product="$product" />
        @endforeach
    </div>

    {{-- Pagination --}}
    @if($products->hasPages())
    <div class="mt-10 flex justify-center">
        <div class="flex items-center gap-2">
            @if($products->onFirstPage())
                <span class="w-10 h-10 flex items-center justify-center rounded-xl border border-gray-200 text-gray-300 cursor-not-allowed">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </span>
            @else
                <a href="{{ $products->previousPageUrl() }}" class="w-10 h-10 flex items-center justify-center rounded-xl border border-gray-200 hover:border-primary hover:text-primary transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </a>
            @endif

            @foreach($products->getUrlRange(max(1, $products->currentPage()-2), min($products->lastPage(), $products->currentPage()+2)) as $page => $url)
                @if($page == $products->currentPage())
                    <span class="w-10 h-10 flex items-center justify-center rounded-xl bg-primary text-white font-semibold text-sm">{{ $page }}</span>
                @else
                    <a href="{{ $url }}" class="w-10 h-10 flex items-center justify-center rounded-xl border border-gray-200 hover:border-primary hover:text-primary transition-all text-sm font-medium">{{ $page }}</a>
                @endif
            @endforeach

            @if($products->hasMorePages())
                <a href="{{ $products->nextPageUrl() }}" class="w-10 h-10 flex items-center justify-center rounded-xl border border-gray-200 hover:border-primary hover:text-primary transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
            @else
                <span class="w-10 h-10 flex items-center justify-center rounded-xl border border-gray-200 text-gray-300 cursor-not-allowed">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </span>
            @endif
        </div>
    </div>
    @endif

    @else
    {{-- Empty state --}}
    <div class="flex flex-col items-center justify-center py-24 text-center">
        <div class="w-20 h-20 rounded-full bg-gray-100 flex items-center justify-center mb-4">
            <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
            </svg>
        </div>
        <h3 class="font-heading font-bold text-lg text-textprimary mb-2">কোনো পণ্য পাওয়া যায়নি</h3>
        <p class="text-sm text-textsecondary mb-6">এই ক্যাটাগরিতে এখনো কোনো পণ্য যোগ করা হয়নি।</p>
        <a href="{{ route('home') }}" class="inline-flex items-center gap-2 px-6 py-3 bg-primary text-white font-semibold rounded-xl text-sm hover:bg-opacity-90 transition-all">
            হোমে ফিরে যান
        </a>
    </div>
    @endif

</div>

@endsection
