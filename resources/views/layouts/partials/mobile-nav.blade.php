<nav class="mobile-nav fixed bottom-0 left-0 right-0 z-40 bg-white border-t border-gray-200 md:hidden safe-area-inset-bottom">
    <div class="flex items-center justify-around h-16">

        {{-- Home --}}
        <a href="{{ route('home') }}"
           class="flex flex-col items-center gap-0.5 px-4 py-2 group {{ request()->routeIs('home') ? 'text-primary' : 'text-textsecondary' }} transition-colors">
            <svg class="w-5 h-5 group-active:scale-90 transition-transform" fill="{{ request()->routeIs('home') ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            <span class="text-[10px] font-medium">হোম</span>
        </a>

        {{-- Shop --}}
        <a href="{{ route('shop') }}"
           class="flex flex-col items-center gap-0.5 px-4 py-2 group {{ request()->routeIs('shop') ? 'text-primary' : 'text-textsecondary' }} transition-colors">
            <svg class="w-5 h-5 group-active:scale-90 transition-transform" fill="{{ request()->routeIs('shop') ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
            </svg>
            <span class="text-[10px] font-medium">শপ</span>
        </a>

        {{-- Cart (center, accent) --}}
        <button @click="cartOpen = !cartOpen"
            class="relative -mt-5 w-14 h-14 bg-accent text-white rounded-full flex flex-col items-center justify-center shadow-lg active:scale-95 transition-transform group">
            <svg class="w-6 h-6 group-active:scale-90 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 7H4l1-7z"/>
            </svg>
            <span x-show="cartCount > 0"
                  class="absolute -top-1 -right-1 w-5 h-5 bg-primary text-white text-[10px] font-bold rounded-full flex items-center justify-center"
                  x-text="cartCount"></span>
        </button>

        {{-- Wishlist --}}
        <a href="{{ route('wishlist') }}"
           class="flex flex-col items-center gap-0.5 px-4 py-2 group text-textsecondary hover:text-primary transition-colors">
            <svg class="w-5 h-5 group-active:scale-90 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
            </svg>
            <span class="text-[10px] font-medium">ইচ্ছা</span>
        </a>

        {{-- Account --}}
        <a href="{{ Auth::check() ? route('profile.edit') : route('login') }}"
           class="flex flex-col items-center gap-0.5 px-4 py-2 group text-textsecondary hover:text-primary transition-colors">
            <svg class="w-5 h-5 group-active:scale-90 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
            <span class="text-[10px] font-medium">অ্যাকাউন্ট</span>
        </a>
    </div>
</nav>
