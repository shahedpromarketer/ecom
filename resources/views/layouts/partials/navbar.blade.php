@php
    $isHome     = request()->routeIs('home');
    $isShop     = request()->routeIs('shop');
    $currentUrl = url()->current();
@endphp

<script>
document.addEventListener('DOMContentLoaded', function () {
    const header = document.getElementById('main-header');
    const onScroll = () => header?.classList.toggle('header-scrolled', window.scrollY > 10);
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
});
</script>

<header id="main-header"
    class="sticky top-0 z-50 transition-all duration-300"
    x-data="{ searchOpen: false, query: '', results: [], loading: false,
               async doSearch() {
                   if (this.query.length < 2) { this.results = []; return; }
                   this.loading = true;
                   try {
                       const r = await fetch('/search?q=' + encodeURIComponent(this.query), { headers: { Accept: 'application/json' } });
                       this.results = await r.json();
                   } catch(e) { this.results = []; }
                   this.loading = false;
               }
             }">

    {{-- Glass backdrop when scrolled --}}
    <div class="absolute inset-0 bg-white/95 backdrop-blur-md border-b border-gray-100 transition-all duration-300" id="header-bg"></div>

    {{-- Accent line bottom --}}
    <div class="absolute bottom-0 left-0 right-0 h-[2px] bg-gradient-to-r from-primary via-accent to-primary opacity-20"></div>

    <div class="relative container-main">
        <div class="flex items-center justify-between h-16 md:h-[70px]" id="header-inner">

            {{-- ── LEFT: Logo ── --}}
            <a href="{{ route('home') }}" class="flex items-center gap-2.5 flex-shrink-0 group">
                @if(!empty($settings['site_logo']))
                    <img src="{{ asset('storage/' . $settings['site_logo']) }}"
                         alt="{{ $settings['store_name'] }}"
                         class="h-9 md:h-11 w-auto object-contain transition-transform duration-300 group-hover:scale-105">
                @else
                    <div class="flex items-center gap-1.5">
                        <div class="w-8 h-8 rounded-xl bg-primary flex items-center justify-center flex-shrink-0 shadow-sm group-hover:scale-105 transition-transform duration-300">
                            <svg class="w-4 h-4 text-accent" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M6.5 16a1.5 1.5 0 100 3 1.5 1.5 0 000-3zm11 0a1.5 1.5 0 100 3 1.5 1.5 0 000-3zM1 1h2l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 002-1.61L21 5H5"/>
                            </svg>
                        </div>
                        <div>
                            <span class="font-heading font-extrabold text-lg md:text-xl text-primary leading-none tracking-tight">
                                {{ $settings['store_name'] ?? 'Clothonic' }}
                            </span>
                        </div>
                    </div>
                @endif
            </a>

            {{-- ── CENTER: Desktop Nav ── --}}
            <nav class="hidden md:flex items-center gap-0.5">
                <a href="{{ route('home') }}"
                   class="relative px-4 py-2 text-sm font-semibold rounded-xl transition-all duration-150 group
                          {{ $isHome ? 'text-primary bg-primary/8' : 'text-textprimary hover:text-primary hover:bg-primary/5' }}">
                    হোম
                    @if($isHome)
                    <span class="absolute bottom-0.5 left-1/2 -translate-x-1/2 w-4 h-0.5 bg-primary rounded-full"></span>
                    @endif
                </a>

                <a href="{{ route('shop') }}"
                   class="relative px-4 py-2 text-sm font-semibold rounded-xl transition-all duration-150
                          {{ $isShop ? 'text-primary bg-primary/8' : 'text-textprimary hover:text-primary hover:bg-primary/5' }}">
                    সব পণ্য
                    @if($isShop)
                    <span class="absolute bottom-0.5 left-1/2 -translate-x-1/2 w-4 h-0.5 bg-primary rounded-full"></span>
                    @endif
                </a>

                {{-- Categories Dropdown --}}
                @if($categories->isNotEmpty())
                <div class="relative" x-data="{ open: false }"
                     @mouseenter="open = true" @mouseleave="open = false">
                    <button class="flex items-center gap-1 px-4 py-2 text-sm font-semibold text-textprimary hover:text-primary hover:bg-primary/5 rounded-xl transition-all duration-150">
                        ক্যাটাগরি
                        <svg class="w-3.5 h-3.5 text-textsecondary transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>

                    <div x-show="open"
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 scale-95 translate-y-1"
                         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-100"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         class="absolute top-full left-0 mt-2 w-60 bg-white rounded-2xl shadow-2xl border border-gray-100/80 py-2 z-50"
                         style="display:none;">
                        @foreach($categories as $category)
                        <a href="{{ route('shop', ['category' => $category->slug]) }}"
                           class="flex items-center gap-3 px-4 py-2.5 text-sm font-medium text-textprimary hover:text-primary hover:bg-primary/5 transition-all duration-100 group/item mx-1 rounded-xl">
                            <span class="w-1.5 h-1.5 rounded-full bg-accent flex-shrink-0 opacity-0 group-hover/item:opacity-100 transition-opacity"></span>
                            {{ $category->name }}
                        </a>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Sale badge --}}
                <a href="{{ route('shop', ['sale' => 1]) }}"
                   class="flex items-center gap-1.5 px-4 py-2 text-sm font-bold text-red-500 hover:text-red-600 hover:bg-red-50 rounded-xl transition-all duration-150">
                    <span>সেল</span>
                    <span class="px-1.5 py-0.5 bg-red-500 text-white text-[10px] font-bold rounded-md leading-none">HOT</span>
                </a>
            </nav>

            {{-- ── RIGHT: Action Icons ── --}}
            <div class="flex items-center gap-1">

                {{-- Search --}}
                <div class="relative">
                    {{-- Expanded Search Bar --}}
                    <div x-show="searchOpen"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         class="absolute right-0 top-1/2 -translate-y-1/2 z-10"
                         style="display:none;"
                         @click.outside="searchOpen = false; query = ''; results = []">
                        <div class="flex items-center gap-2 bg-white border-2 border-primary/20 rounded-2xl px-3 py-2 shadow-lg w-56 md:w-72">
                            <svg class="w-4 h-4 text-textsecondary flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <input x-ref="searchInput"
                                   type="text"
                                   x-model="query"
                                   @input.debounce.300ms="doSearch()"
                                   @keydown.escape="searchOpen = false; query = ''; results = []"
                                   placeholder="পণ্য খুঁজুন..."
                                   class="flex-1 text-sm text-textprimary placeholder-textsecondary bg-transparent focus:outline-none">
                            <button x-show="query" @click="query = ''; results = []" class="text-gray-400 hover:text-gray-600">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>

                        {{-- Search Results Dropdown --}}
                        <div x-show="results.length > 0 || loading"
                             class="absolute top-full right-0 mt-2 w-80 bg-white rounded-2xl shadow-2xl border border-gray-100 overflow-hidden z-50">
                            <template x-if="loading">
                                <div class="flex items-center justify-center py-6">
                                    <svg class="animate-spin w-5 h-5 text-primary" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                </div>
                            </template>
                            <template x-if="!loading && results.length > 0">
                                <div>
                                    <template x-for="result in results.slice(0,6)" :key="result.id">
                                        <a :href="result.url"
                                           class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 transition-colors border-b border-gray-50 last:border-0">
                                            <img :src="result.image" :alt="result.name"
                                                 class="w-10 h-12 rounded-xl object-cover bg-gray-100 flex-shrink-0">
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-semibold text-textprimary truncate" x-text="result.name"></p>
                                                <p class="text-xs text-accent font-bold mt-0.5" x-text="'৳' + result.price"></p>
                                            </div>
                                            <svg class="w-4 h-4 text-gray-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                            </svg>
                                        </a>
                                    </template>
                                    <a :href="'/search?q=' + encodeURIComponent(query)"
                                       class="block px-4 py-3 text-xs font-semibold text-primary text-center hover:bg-primary/5 transition-colors">
                                        সব ফলাফল দেখুন →
                                    </a>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- Search Icon Button --}}
                    <button @click="searchOpen = !searchOpen; if(searchOpen) $nextTick(() => $refs.searchInput?.focus())"
                            class="w-9 h-9 flex items-center justify-center rounded-xl hover:bg-gray-100 transition-colors"
                            aria-label="খুঁজুন">
                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </button>
                </div>

                {{-- Wishlist (desktop only) --}}
                <a href="{{ route('wishlist') }}"
                   class="hidden md:flex w-9 h-9 items-center justify-center rounded-xl hover:bg-gray-100 transition-colors"
                   aria-label="উইশলিস্ট">
                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                    </svg>
                </a>

                {{-- Cart --}}
                <button @click="cartOpen = !cartOpen"
                        class="relative w-9 h-9 flex items-center justify-center rounded-xl hover:bg-gray-100 transition-colors"
                        aria-label="কার্ট">
                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 7H4l1-7z"/>
                    </svg>
                    <span x-show="cartCount > 0" x-cloak
                          class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] px-1 bg-accent text-white text-[10px] font-bold rounded-full flex items-center justify-center leading-none"
                          x-text="cartCount"></span>
                </button>

                {{-- Account (desktop only) --}}
                <div class="hidden md:block relative" x-data="{ open: false }"
                     @mouseenter="open = true" @mouseleave="open = false">
                    <button class="w-9 h-9 flex items-center justify-center rounded-xl hover:bg-gray-100 transition-colors" aria-label="অ্যাকাউন্ট">
                        @auth
                        <div class="w-7 h-7 rounded-lg bg-primary flex items-center justify-center">
                            <span class="text-white text-xs font-bold leading-none">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</span>
                        </div>
                        @else
                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        @endauth
                    </button>
                    <div x-show="open" x-transition
                         class="absolute right-0 top-full mt-2 w-52 bg-white rounded-2xl shadow-2xl border border-gray-100 py-2 z-50 overflow-hidden"
                         style="display:none;">
                        @auth
                        <div class="px-4 py-3 border-b border-gray-100 bg-gray-50/70">
                            <p class="text-xs text-textsecondary">স্বাগতম,</p>
                            <p class="text-sm font-bold text-textprimary truncate">{{ Auth::user()->name }}</p>
                        </div>
                        <a href="{{ route('orders.index') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm text-textprimary hover:text-primary hover:bg-primary/5 transition-all">
                            <svg class="w-4 h-4 text-textsecondary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            আমার অর্ডার
                        </a>
                        <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm text-textprimary hover:text-primary hover:bg-primary/5 transition-all">
                            <svg class="w-4 h-4 text-textsecondary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            প্রোফাইল
                        </a>
                        <div class="border-t border-gray-100 mt-1 pt-1">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="flex items-center gap-3 w-full px-4 py-2.5 text-sm text-danger hover:bg-red-50 transition-all">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                    লগআউট
                                </button>
                            </form>
                        </div>
                        @else
                        <a href="{{ route('login') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm text-textprimary hover:text-primary hover:bg-primary/5 transition-all">
                            <svg class="w-4 h-4 text-textsecondary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
                            লগইন
                        </a>
                        <div class="px-4 pb-3 pt-1">
                            <a href="{{ route('register') }}" class="block w-full py-2 bg-primary text-white text-sm font-semibold rounded-xl text-center hover:bg-opacity-90 transition-all">
                                রেজিস্টার করুন
                            </a>
                        </div>
                        @endauth
                    </div>
                </div>

                {{-- Mobile Hamburger --}}
                <button @click="mobileMenuOpen = !mobileMenuOpen"
                        class="md:hidden w-9 h-9 flex items-center justify-center rounded-xl hover:bg-gray-100 transition-colors"
                        aria-label="মেনু">
                    <svg x-show="!mobileMenuOpen" class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6h16M4 12h10M4 18h16"/>
                    </svg>
                    <svg x-show="mobileMenuOpen" x-cloak class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    {{-- ── Mobile Drawer Menu ── --}}
    {{-- Backdrop --}}
    <div x-show="mobileMenuOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="mobileMenuOpen = false"
         class="md:hidden fixed inset-0 bg-black/40 z-30"
         style="display:none;"></div>

    {{-- Drawer Panel --}}
    <div x-show="mobileMenuOpen"
         x-transition:enter="transition ease-out duration-250"
         x-transition:enter-start="opacity-0 -translate-y-3"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-3"
         class="md:hidden absolute top-full left-0 right-0 bg-white border-t border-gray-100 shadow-2xl z-40 max-h-[calc(100vh-130px)] overflow-y-auto"
         style="display:none;">

        <div class="container-main py-3">

            {{-- Main Nav Links --}}
            <div class="space-y-0.5 mb-3">
                <a href="{{ route('home') }}"
                   @click="mobileMenuOpen = false"
                   class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold transition-all
                          {{ $isHome ? 'bg-primary/8 text-primary' : 'text-textprimary hover:bg-gray-50' }}">
                    <svg class="w-4 h-4 {{ $isHome ? 'text-primary' : 'text-textsecondary' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    হোম
                </a>

                <a href="{{ route('shop') }}"
                   @click="mobileMenuOpen = false"
                   class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold transition-all
                          {{ $isShop ? 'bg-primary/8 text-primary' : 'text-textprimary hover:bg-gray-50' }}">
                    <svg class="w-4 h-4 {{ $isShop ? 'text-primary' : 'text-textsecondary' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                    সব পণ্য
                </a>
            </div>

            {{-- Categories --}}
            @if($categories->isNotEmpty())
            <div class="border-t border-gray-100 pt-3 mb-3">
                <p class="px-4 mb-2 text-[10px] font-bold text-textsecondary uppercase tracking-widest">ক্যাটাগরি</p>
                @foreach($categories as $category)
                <div x-data="{ catOpen: false }">
                    @if($category->children->isNotEmpty())
                    <button @click="catOpen = !catOpen"
                        class="w-full flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium text-textprimary hover:bg-gray-50 transition-all">
                        <span class="w-1.5 h-1.5 rounded-full bg-accent flex-shrink-0"></span>
                        <span class="flex-1 text-left">{{ $category->name }}</span>
                        <svg class="w-3.5 h-3.5 text-textsecondary transition-transform duration-200 flex-shrink-0"
                             :class="catOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="catOpen" x-transition class="pl-8 space-y-0.5 pb-1">
                        <a href="{{ route('shop', ['category' => $category->slug]) }}"
                           @click="mobileMenuOpen = false"
                           class="flex items-center gap-2 px-4 py-2 text-xs font-medium text-textsecondary hover:text-primary hover:bg-primary/5 rounded-xl transition-all">
                            সব {{ $category->name }}
                        </a>
                        @foreach($category->children as $child)
                        <a href="{{ route('shop', ['category' => $child->slug]) }}"
                           @click="mobileMenuOpen = false"
                           class="flex items-center gap-2 px-4 py-2 text-xs font-medium text-textsecondary hover:text-primary hover:bg-primary/5 rounded-xl transition-all">
                            {{ $child->name }}
                        </a>
                        @endforeach
                    </div>
                    @else
                    <a href="{{ route('shop', ['category' => $category->slug]) }}"
                       @click="mobileMenuOpen = false"
                       class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium text-textprimary hover:bg-gray-50 transition-all">
                        <span class="w-1.5 h-1.5 rounded-full bg-accent flex-shrink-0"></span>
                        {{ $category->name }}
                    </a>
                    @endif
                </div>
                @endforeach
            </div>
            @endif

            {{-- Sale & Bottom Links --}}
            <div class="border-t border-gray-100 pt-3 space-y-0.5">
                <a href="{{ route('shop', ['sale' => 1]) }}"
                   @click="mobileMenuOpen = false"
                   class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold text-red-500 hover:bg-red-50 transition-all">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                    </svg>
                    সেল অফার 🔥
                </a>

                @auth
                <div class="border-t border-gray-100 mt-2 pt-2 space-y-0.5">
                    <a href="{{ route('orders.index') }}" @click="mobileMenuOpen = false"
                       class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-textprimary hover:bg-gray-50 transition-all">
                        <svg class="w-4 h-4 text-textsecondary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        আমার অর্ডার
                    </a>
                    <a href="{{ route('profile.edit') }}" @click="mobileMenuOpen = false"
                       class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-textprimary hover:bg-gray-50 transition-all">
                        <svg class="w-4 h-4 text-textsecondary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        প্রোফাইল
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="flex items-center gap-3 w-full px-4 py-3 rounded-xl text-sm font-medium text-danger hover:bg-red-50 transition-all">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                            লগআউট
                        </button>
                    </form>
                </div>
                @else
                <div class="grid grid-cols-2 gap-2 px-4 pt-3 pb-4">
                    <a href="{{ route('login') }}"
                       class="flex items-center justify-center py-3 border-2 border-primary text-primary text-sm font-bold rounded-xl hover:bg-primary hover:text-white transition-all">
                        লগইন
                    </a>
                    <a href="{{ route('register') }}"
                       class="flex items-center justify-center py-3 bg-primary text-white text-sm font-bold rounded-xl hover:bg-opacity-90 transition-all">
                        রেজিস্টার
                    </a>
                </div>
                @endauth
            </div>

        </div>
    </div>
</header>
