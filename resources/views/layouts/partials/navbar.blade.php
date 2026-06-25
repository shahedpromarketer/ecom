<header id="main-header"
    class="sticky top-0 z-50 bg-white shadow-sm transition-all duration-300"
    x-data="siteSearch()">

    <div class="container-main">
        <div class="flex items-center justify-between py-3 md:py-4 transition-all duration-300" id="header-inner">

            {{-- LEFT: Logo --}}
            <a href="{{ route('home') }}" class="flex items-center gap-2 flex-shrink-0">
                @if($settings['site_logo'])
                    <img src="{{ asset('storage/' . $settings['site_logo']) }}"
                         alt="{{ $settings['store_name'] }}"
                         class="h-10 md:h-12 w-auto object-contain">
                @else
                    <span class="font-heading font-bold text-xl md:text-2xl text-primary tracking-tight">
                        {{ $settings['store_name'] ?? 'Clothonic' }}
                    </span>
                @endif
            </a>

            {{-- CENTER: Desktop Nav --}}
            <nav class="hidden md:flex items-center gap-1">
                <a href="{{ route('home') }}"
                   class="px-4 py-2 text-sm font-medium text-textprimary hover:text-primary rounded-lg hover:bg-primary/5 transition-all duration-150">
                    Home
                </a>
                <a href="{{ route('shop') }}"
                   class="px-4 py-2 text-sm font-medium text-textprimary hover:text-primary rounded-lg hover:bg-primary/5 transition-all duration-150">
                    Shop
                </a>

                {{-- Categories mega dropdown --}}
                <div class="relative" x-data="{ open: false }"
                     @mouseenter="open = true" @mouseleave="open = false">
                    <button class="flex items-center gap-1 px-4 py-2 text-sm font-medium text-textprimary hover:text-primary rounded-lg hover:bg-primary/5 transition-all duration-150">
                        Categories
                        <svg class="w-4 h-4 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>

                    <div x-show="open" x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-100"
                         x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-1"
                         class="absolute top-full left-0 mt-1 w-64 bg-white rounded-2xl shadow-xl border border-gray-100 py-2 z-50">
                        @foreach($categories as $category)
                        <div>
                            <a href="{{ route('shop', ['category' => $category->slug]) }}"
                               class="flex items-center px-4 py-2.5 text-sm font-medium text-textprimary hover:text-primary hover:bg-primary/5 transition-all">
                                {{ $category->name }}
                                @if($category->children->isNotEmpty())
                                <svg class="w-4 h-4 ml-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                                @endif
                            </a>
                            @if($category->children->isNotEmpty())
                            <div class="pl-6 pb-1">
                                @foreach($category->children as $child)
                                <a href="{{ route('shop', ['category' => $child->slug]) }}"
                                   class="block px-4 py-1.5 text-xs text-textsecondary hover:text-primary hover:bg-primary/5 rounded-lg transition-all">
                                    {{ $child->name }}
                                </a>
                                @endforeach
                            </div>
                            @endif
                        </div>
                        @endforeach
                        @if($categories->isEmpty())
                        <p class="px-4 py-3 text-sm text-textsecondary">No categories yet</p>
                        @endif
                    </div>
                </div>

                <a href="{{ route('shop', ['sale' => 1]) }}"
                   class="px-4 py-2 text-sm font-medium text-red-500 hover:text-red-600 rounded-lg hover:bg-red-50 transition-all duration-150">
                    Sale 🔥
                </a>
            </nav>

            {{-- RIGHT: Icons --}}
            <div class="flex items-center gap-1 md:gap-2">

                {{-- Search --}}
                <div class="relative" x-data="siteSearch()">
                    <div class="flex items-center">
                        <div x-show="open" x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 w-0" x-transition:enter-end="opacity-100 w-48 md:w-64"
                             class="overflow-hidden">
                            <input x-ref="searchInput" type="text" x-model="query" @input.debounce.300ms="search()"
                                   @keydown.escape="open = false; query = ''; results = []"
                                   placeholder="পণ্য খুঁজুন..."
                                   class="w-48 md:w-64 px-4 py-1.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all">
                        </div>
                        <button @click="toggle()" class="w-9 h-9 flex items-center justify-center rounded-xl hover:bg-gray-100 transition-colors ml-1" aria-label="Search">
                            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </button>
                    </div>

                    {{-- Search Results --}}
                    <div x-show="results.length > 0" x-transition
                         class="absolute right-0 top-full mt-2 w-80 bg-white rounded-2xl shadow-xl border border-gray-100 z-50 overflow-hidden">
                        <template x-for="result in results" :key="result.id">
                            <a :href="result.url" class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 transition-colors border-b border-gray-50 last:border-0">
                                <img :src="result.image" :alt="result.name" class="w-10 h-10 rounded-lg object-cover bg-gray-100">
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-textprimary truncate" x-text="result.name"></p>
                                    <p class="text-xs text-accent font-semibold" x-text="'৳' + result.price"></p>
                                </div>
                            </a>
                        </template>
                    </div>
                </div>

                {{-- Wishlist (desktop) --}}
                <a href="{{ route('wishlist') }}" class="hidden md:flex w-9 h-9 items-center justify-center rounded-xl hover:bg-gray-100 transition-colors relative" aria-label="Wishlist">
                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                    </svg>
                </a>

                {{-- Cart --}}
                <button @click="cartOpen = !cartOpen"
                    class="w-9 h-9 flex items-center justify-center rounded-xl hover:bg-gray-100 transition-colors relative" aria-label="Cart">
                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 7H4l1-7z"/>
                    </svg>
                    <span x-show="cartCount > 0"
                          class="absolute -top-1 -right-1 w-4 h-4 bg-accent text-white text-[10px] font-bold rounded-full flex items-center justify-center"
                          x-text="cartCount"></span>
                </button>

                {{-- Account --}}
                <div class="hidden md:block relative" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false">
                    <button class="w-9 h-9 flex items-center justify-center rounded-xl hover:bg-gray-100 transition-colors" aria-label="Account">
                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </button>
                    <div x-show="open" x-transition
                         class="absolute right-0 top-full mt-1 w-48 bg-white rounded-2xl shadow-xl border border-gray-100 py-2 z-50">
                        @auth
                        <div class="px-4 py-2 border-b border-gray-100">
                            <p class="text-xs font-semibold text-textprimary truncate">{{ Auth::user()->name }}</p>
                        </div>
                        <a href="{{ route('orders.index') }}" class="block px-4 py-2.5 text-sm text-textprimary hover:text-primary hover:bg-primary/5 transition-all">My Orders</a>
                        <a href="{{ route('profile.edit') }}" class="block px-4 py-2.5 text-sm text-textprimary hover:text-primary hover:bg-primary/5 transition-all">Profile</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full text-left px-4 py-2.5 text-sm text-danger hover:bg-red-50 transition-all">Logout</button>
                        </form>
                        @else
                        <a href="{{ route('login') }}" class="block px-4 py-2.5 text-sm text-textprimary hover:text-primary hover:bg-primary/5 transition-all">Login</a>
                        <a href="{{ route('register') }}" class="block px-4 py-2.5 text-sm text-textprimary hover:text-primary hover:bg-primary/5 transition-all">Register</a>
                        @endauth
                    </div>
                </div>

                {{-- Mobile Hamburger --}}
                <button @click="mobileMenuOpen = !mobileMenuOpen"
                    class="md:hidden w-9 h-9 flex items-center justify-center rounded-xl hover:bg-gray-100 transition-colors" aria-label="Menu">
                    <svg x-show="!mobileMenuOpen" class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                    <svg x-show="mobileMenuOpen" class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    {{-- Mobile Menu --}}
    <div x-show="mobileMenuOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-2"
         class="md:hidden absolute top-full left-0 right-0 bg-white border-t border-gray-100 shadow-lg z-40 max-h-[80vh] overflow-y-auto"
         @click.outside="mobileMenuOpen = false">
        <div class="container-main py-4 space-y-1">
            <a href="{{ route('home') }}" class="block px-4 py-3 text-sm font-medium text-textprimary hover:text-primary hover:bg-primary/5 rounded-xl transition-all" @click="mobileMenuOpen = false">Home</a>
            <a href="{{ route('shop') }}" class="block px-4 py-3 text-sm font-medium text-textprimary hover:text-primary hover:bg-primary/5 rounded-xl transition-all" @click="mobileMenuOpen = false">All Products</a>

            {{-- Mobile Category Accordion --}}
            @foreach($categories as $category)
            <div x-data="{ catOpen: false }">
                <button @click="catOpen = !catOpen"
                    class="w-full flex items-center justify-between px-4 py-3 text-sm font-medium text-textprimary hover:text-primary hover:bg-primary/5 rounded-xl transition-all">
                    {{ $category->name }}
                    @if($category->children->isNotEmpty())
                    <svg class="w-4 h-4 transition-transform duration-200" :class="catOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                    @endif
                </button>
                @if($category->children->isNotEmpty())
                <div x-show="catOpen" x-transition class="pl-6 space-y-1 pb-1">
                    @foreach($category->children as $child)
                    <a href="{{ route('shop', ['category' => $child->slug]) }}"
                       class="block px-4 py-2 text-xs text-textsecondary hover:text-primary hover:bg-primary/5 rounded-lg transition-all"
                       @click="mobileMenuOpen = false">
                        {{ $child->name }}
                    </a>
                    @endforeach
                </div>
                @endif
            </div>
            @endforeach

            <a href="{{ route('shop', ['sale' => 1]) }}" class="block px-4 py-3 text-sm font-medium text-red-500 hover:bg-red-50 rounded-xl transition-all" @click="mobileMenuOpen = false">Sale 🔥</a>

            <div class="border-t border-gray-100 pt-3 mt-3">
                @auth
                <a href="{{ route('orders.index') }}" class="block px-4 py-3 text-sm font-medium text-textprimary hover:text-primary hover:bg-primary/5 rounded-xl" @click="mobileMenuOpen = false">My Orders</a>
                <a href="{{ route('profile.edit') }}" class="block px-4 py-3 text-sm font-medium text-textprimary hover:text-primary hover:bg-primary/5 rounded-xl" @click="mobileMenuOpen = false">Profile</a>
                <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="w-full text-left px-4 py-3 text-sm font-medium text-danger hover:bg-red-50 rounded-xl">Logout</button></form>
                @else
                <a href="{{ route('login') }}" class="block px-4 py-3 text-sm font-medium text-primary hover:bg-primary/5 rounded-xl" @click="mobileMenuOpen = false">Login</a>
                <a href="{{ route('register') }}" class="block px-4 py-3 text-sm font-medium text-white bg-primary rounded-xl text-center mt-2" @click="mobileMenuOpen = false">Register</a>
                @endauth
            </div>
        </div>
    </div>
</header>
