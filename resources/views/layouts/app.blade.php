<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @php
            $tenantId    = auth()->user()?->tenant_id;
            $cacheKey    = 'active_pixels_' . ($tenantId ?? 0);
            $activePixels = \Illuminate\Support\Facades\Cache::store('file')->remember($cacheKey, 3600, function () use ($tenantId) {
                return \App\Models\MarketingPixel::where('is_active', true)
                    ->whereNotNull('pixel_id')
                    ->where('tenant_id', $tenantId)
                    ->pluck('pixel_id', 'platform')
                    ->toArray();
            });
        @endphp

        {{-- Facebook Pixel --}}
        @if(!empty($activePixels['facebook']))
        <script>
        !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');
        fbq('init','{{ $activePixels['facebook'] }}');fbq('track','PageView');
        </script>
        <noscript><img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id={{ $activePixels['facebook'] }}&ev=PageView&noscript=1"/></noscript>
        @endif

        {{-- TikTok Pixel --}}
        @if(!empty($activePixels['tiktok']))
        <script>
        !function(w,d,t){w.TiktokAnalyticsObject=t;var ttq=w[t]=w[t]||[];ttq.methods=["page","track","identify","instances","debug","on","off","once","ready","alias","group","enableCookie","disableCookie"],ttq.setAndDefer=function(t,e){t[e]=function(){t.push([e].concat(Array.prototype.slice.call(arguments,0)))}};for(var i=0;i<ttq.methods.length;i++)ttq.setAndDefer(ttq,ttq.methods[i]);ttq.instance=function(t){for(var e=ttq._i[t]||[],n=0;n<ttq.methods.length;n++)ttq.setAndDefer(e,ttq.methods[n]);return e},ttq.load=function(e,n){var i="https://analytics.tiktok.com/i18n/pixel/events.js";ttq._i=ttq._i||{},ttq._i[e]=[],ttq._i[e]._u=i,ttq._t=ttq._t||{},ttq._t[e]=+new Date,ttq._o=ttq._o||{},ttq._o[e]=n||{};n=document.createElement("script");n.type="text/javascript",n.async=!0,n.src=i+"?sdkid="+e+"&lib="+t;e=document.getElementsByTagName("script")[0];e.parentNode.insertBefore(n,e)};
        ttq.load('{{ $activePixels['tiktok'] }}');ttq.page();
        }(window,document,'ttq');
        </script>
        @endif

        {{-- Google Tag Manager (head) --}}
        @if(!empty($activePixels['google_tag_manager']))
        <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','{{ $activePixels['google_tag_manager'] }}');</script>
        @endif

        {{-- Google Analytics 4 --}}
        @if(!empty($activePixels['google_analytics']))
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ $activePixels['google_analytics'] }}"></script>
        <script>
        window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}
        gtag('js',new Date());gtag('config','{{ $activePixels['google_analytics'] }}');
        </script>
        @endif

        {{-- Microsoft Clarity --}}
        @if(!empty($activePixels['microsoft_clarity']))
        <script>
        (function(c,l,a,r,i,t,y){c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};t=l.createElement(r);t.async=1;t.src="https://www.clarity.ms/tag/"+i;y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y)})(window,document,"clarity","script","{{ $activePixels['microsoft_clarity'] }}");
        </script>
        @endif
    </head>
    <body class="font-sans antialiased">
        {{-- GTM noscript --}}
        @if(!empty($activePixels['google_tag_manager']))
        <noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ $activePixels['google_tag_manager'] }}" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
        @endif
        <div class="min-h-screen bg-gray-100">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>
    </body>
</html>
