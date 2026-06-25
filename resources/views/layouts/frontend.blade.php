<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @yield('meta', '')
    @yield('schema', '')

    <title>@yield('title', config('app.name', 'Clothonic'))</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @php
        try {
            $activePixels = \App\Models\MarketingPixel::where('is_active', true)
                ->whereNotNull('pixel_id')
                ->get()
                ->keyBy('platform');
        } catch (\Exception $e) {
            $activePixels = collect();
        }
        $globalSettings = [
            'store_name'     => \App\Models\Setting::getValue('store_name', 'Clothonic'),
            'store_phone'    => \App\Models\Setting::getValue('store_phone', ''),
            'store_email'    => \App\Models\Setting::getValue('store_email', ''),
            'site_logo'      => \App\Models\Setting::getValue('site_logo', ''),
            'facebook_url'   => \App\Models\Setting::getValue('facebook_url', ''),
            'instagram_url'  => \App\Models\Setting::getValue('instagram_url', ''),
            'youtube_url'    => \App\Models\Setting::getValue('youtube_url', ''),
            'whatsapp_phone' => \App\Models\Setting::getValue('whatsapp_phone', \App\Models\Setting::getValue('store_phone', '')),
            'free_shipping_above' => \App\Models\Setting::getValue('free_shipping_above', 999),
            'shipping_charge'     => \App\Models\Setting::getValue('shipping_charge', 60),
        ];
        $globalCategories = \App\Models\Category::where('is_active', true)
            ->whereNull('parent_id')
            ->with('children')
            ->orderBy('sort_order')
            ->get();
    @endphp

    {{-- Facebook Pixel --}}
    @if(isset($activePixels['facebook']))
    <script>!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');fbq('init','{{ $activePixels['facebook']->pixel_id }}');fbq('track','PageView');</script>
    <noscript><img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id={{ $activePixels['facebook']->pixel_id }}&ev=PageView&noscript=1"/></noscript>
    @endif

    {{-- Google Analytics 4 --}}
    @if(isset($activePixels['google_analytics']))
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $activePixels['google_analytics']->pixel_id }}"></script>
    <script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','{{ $activePixels['google_analytics']->pixel_id }}');</script>
    @endif
</head>
<body class="font-body text-textprimary antialiased bg-[#F8F8F8]"
    x-data="{
        cartOpen: false,
        quickOrderOpen: false,
        quickOrderProduct: null,
        mobileMenuOpen: false,
        searchOpen: false,
        cartCount: 0,
        announcementVisible: true,
        openQuickOrder(product) {
            this.quickOrderProduct = product;
            this.quickOrderOpen = true;
            document.body.style.overflow = 'hidden';
        },
        closeQuickOrder() {
            this.quickOrderOpen = false;
            document.body.style.overflow = '';
        }
    }">

    {{-- Announcement Bar --}}
    @include('layouts.partials.announcement-bar', ['settings' => $globalSettings])

    {{-- Header / Navbar --}}
    @include('layouts.partials.navbar', ['settings' => $globalSettings, 'categories' => $globalCategories])

    {{-- Main Content --}}
    <main id="main-content" class="pb-16 md:pb-0">
        @yield('content')
    </main>

    {{-- Footer --}}
    @include('layouts.partials.footer', ['settings' => $globalSettings, 'categories' => $globalCategories])

    {{-- Mobile Bottom Nav --}}
    @include('layouts.partials.mobile-nav')

    {{-- Quick Order Modal --}}
    @include('layouts.partials.quick-order-modal', ['settings' => $globalSettings])

    {{-- WhatsApp Float --}}
    @if($globalSettings['whatsapp_phone'])
    <a href="https://wa.me/88{{ preg_replace('/[^0-9]/', '', $globalSettings['whatsapp_phone']) }}"
       target="_blank" rel="noopener"
       class="fixed bottom-24 md:bottom-8 right-4 z-40 w-14 h-14 bg-green-500 rounded-full flex items-center justify-center shadow-lg hover:scale-110 transition-transform duration-200"
       title="WhatsApp">
        <svg class="w-7 h-7 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
    </a>
    @endif

    {{-- Back to Top --}}
    <button id="back-to-top" onclick="scrollToTop()"
        class="hidden fixed bottom-24 md:bottom-8 right-20 z-40 w-10 h-10 bg-primary text-white rounded-full flex items-center justify-center shadow-lg hover:scale-110 transition-all duration-200">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
    </button>

    {{-- Toast Container --}}
    <div id="toast-container" class="fixed bottom-20 md:bottom-4 right-4 z-50 flex flex-col gap-2 pointer-events-none"></div>

</body>
</html>
