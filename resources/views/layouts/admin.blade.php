<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      x-data="{ dark: localStorage.getItem('adminTheme') === 'dark' }"
      x-init="document.documentElement.classList.toggle('dark', dark)">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — Clothonic Admin</title>
    <script>if(localStorage.getItem('adminTheme')==='dark')document.documentElement.classList.add('dark');</script>
    @vite(['resources/css/admin.css'])
    @stack('styles')
    <style>
        .nav-collapsed .sidebar-section-label { display: none !important; }
        .nav-collapsed > div > span { display: none !important; }
        .nav-collapsed a span.truncate,
        .nav-collapsed button span.truncate { display: none !important; }
        .nav-collapsed a, .nav-collapsed > a {
            justify-content: center !important;
            padding-left: 0.5rem !important;
            padding-right: 0.5rem !important;
            margin-left: 0.25rem;
            margin-right: 0.25rem;
            border-left: 0 !important;
        }
        .nav-collapsed a svg { flex-shrink: 0; }
        .nav-collapsed div[x-data] button { justify-content: center; padding-left: 0.5rem; padding-right: 0.5rem; }
        .nav-collapsed div[x-data] a { justify-content: center !important; padding-left: 0.5rem !important; padding-right: 0.5rem !important; }
        .nav-collapsed div[x-data] { display: none !important; }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="font-body bg-gray-50 dark:bg-gray-900 transition-colors duration-200" x-data="sidebar">

{{-- Mobile overlay --}}
<div x-show="mobileOpen"
     x-cloak
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     @click="closeMobile()"
     class="fixed inset-0 bg-black/50 z-40 lg:hidden">
</div>

{{-- ══════════════════════════ SIDEBAR ══════════════════════════ --}}
<aside id="admin-sidebar"
       class="fixed inset-y-0 left-0 z-50 flex flex-col
              bg-[#1B2A4A] border-r border-white/5
              -translate-x-full lg:translate-x-0
              transition-all duration-300 ease-in-out"
       :class="{ 'translate-x-0': mobileOpen, 'w-16': collapsed, 'w-64': !collapsed }">

    {{-- Logo --}}
    <div class="flex items-center h-16 px-3 border-b border-white/10 flex-shrink-0 overflow-hidden"
         :class="collapsed ? 'justify-center' : 'gap-2 px-5 justify-between'">
        <div class="flex items-center gap-2 overflow-hidden" x-show="!collapsed">
            <span class="font-heading text-xl font-bold text-white whitespace-nowrap">Clothonic</span>
            <span class="text-xs bg-accent text-white px-1.5 py-0.5 rounded font-medium flex-shrink-0">Admin</span>
        </div>
        <button @click="collapseToggle()" class="hidden lg:flex w-7 h-7 rounded-lg bg-white/10 hover:bg-white/20 items-center justify-center text-white/70 hover:text-white transition-colors flex-shrink-0" title="Toggle sidebar">
            <svg class="w-4 h-4 transition-transform duration-300" :class="collapsed ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 9l-3 3m0 0l3 3m-3-3h7.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </button>
    </div>

    {{-- Navigation --}}
    <nav id="sidebar" class="flex-1 overflow-y-auto overflow-x-hidden py-4" :class="{'nav-collapsed': collapsed}">

        @php
            $navItem = fn(string $route) =>
                request()->routeIs($route)
                    ? 'bg-white/10 text-white border-l-[3px] border-accent pl-[13px]'
                    : 'text-white/70 hover:bg-white/10 hover:text-white pl-4';
            $navItemCollapsed = fn(string $route) =>
                request()->routeIs($route)
                    ? 'bg-white/10 text-white'
                    : 'text-white/70 hover:bg-white/10 hover:text-white';
        @endphp

        {{-- MAIN --}}
        <div class="px-4 mb-1 mt-1">
            <span class="text-white/30 text-xs font-semibold uppercase tracking-widest sidebar-section-label">Main</span>
        </div>

        <a href="{{ route('admin.dashboard') }}"
           class="flex items-center gap-3 py-3 pr-4 text-sm font-medium transition-colors duration-150 {{ $navItem('admin.dashboard') }}"
           title="Dashboard">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"/>
            </svg>
            <span class="truncate">Dashboard</span>
        </a>

        {{-- CATALOG --}}
        <div class="px-4 mt-4 mb-1">
            <span class="text-white/30 text-xs font-semibold uppercase tracking-widest">Catalog</span>
        </div>

        <a href="{{ url('/admin/products') }}"
           class="flex items-center gap-3 py-3 pr-4 text-sm font-medium transition-colors duration-150 {{ $navItem('admin.products*') }}">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z"/>
            </svg>
            <span class="truncate">Products</span>
        </a>

        <a href="{{ url('/admin/categories') }}"
           class="flex items-center gap-3 py-3 pr-4 text-sm font-medium transition-colors duration-150 {{ $navItem('admin.categories*') }}">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z"/>
            </svg>
            <span class="truncate">Categories</span>
        </a>

        {{-- SALES --}}
        <div class="px-4 mt-4 mb-1">
            <span class="text-white/30 text-xs font-semibold uppercase tracking-widest">Sales</span>
        </div>

        @php
            $counts = \Illuminate\Support\Facades\Cache::remember('sidebar_order_counts', 120, fn() => [
                'all'            => \App\Models\Order::count(),
                'pending'        => \App\Models\Order::where('status','pending')->count(),
                'confirmed'      => \App\Models\Order::where('status','confirmed')->count(),
                'processing'     => \App\Models\Order::where('status','processing')->count(),
                'hold'           => \App\Models\Order::where('status','hold')->count(),
                'shipped'        => \App\Models\Order::where('status','shipped')->count(),
                'delivered'      => \App\Models\Order::where('status','delivered')->count(),
                'completed'      => \App\Models\Order::where('status','completed')->count(),
                'cancelled'      => \App\Models\Order::where('status','cancelled')->count(),
                'pending_return' => \App\Models\Order::where('status','pending_return')->count(),
                'returned'       => \App\Models\Order::where('status','returned')->count(),
            ]);
            $ordersActive = request()->is('admin/orders*') || request()->is('admin/payments*');
        @endphp

        <div x-data="{ ordersOpen: {{ $ordersActive ? 'true' : 'false' }} }" class="px-2">

            {{-- Parent button --}}
            <button type="button" @click="ordersOpen = !ordersOpen"
                    class="w-full flex items-center justify-between px-3 py-2.5 text-white/80 hover:text-white hover:bg-white/[0.08] rounded-lg transition-all duration-150 group">
                <div class="flex items-center gap-2.5 min-w-0">
                    <span class="w-8 h-8 rounded-lg bg-white/10 flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007z"/>
                        </svg>
                    </span>
                    <span class="text-sm font-medium truncate">Orders</span>
                </div>
                <div class="flex items-center gap-1.5 flex-shrink-0">
                    @if($counts['pending'] > 0)
                        <span class="px-1.5 py-0.5 text-[10px] font-bold rounded-md bg-accent/20 text-accent border border-accent/30 min-w-[20px] text-center tabular-nums">{{ $counts['pending'] > 999 ? '999+' : $counts['pending'] }}</span>
                    @endif
                    <svg class="w-3.5 h-3.5 text-white/40 transition-transform duration-200" :class="ordersOpen ? 'rotate-180' : ''"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/>
                    </svg>
                </div>
            </button>

            {{-- Sub-menu --}}
            <div x-show="ordersOpen" x-cloak
                 x-transition:enter="transition-all ease-out duration-150"
                 x-transition:enter-start="opacity-0 -translate-y-1"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition-all ease-in duration-100"
                 x-transition:leave-start="opacity-100 translate-y-0"
                 x-transition:leave-end="opacity-0 -translate-y-1"
                 class="mt-1 space-y-0.5 max-h-[60vh] overflow-y-auto pb-1">

                {{-- Create New --}}
                <a href="{{ route('admin.orders.create') }}"
                   class="flex items-center gap-2 pl-4 pr-3 py-2 text-xs font-semibold text-yellow-400 hover:text-yellow-300 hover:bg-yellow-400/10 rounded-lg transition-all duration-150">
                    <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                    </svg>
                    <span>Create New</span>
                </a>

                @php
                $sidebarItems = [
                    ['key' => null,             'label' => 'All Orders',     'count' => $counts['all'],            'param' => null],
                    ['key' => 'pending',        'label' => 'Pending',        'count' => $counts['pending'],        'param' => 'status'],
                    ['key' => 'confirmed',      'label' => 'Confirmed',      'count' => $counts['confirmed'],      'param' => 'status'],
                    ['key' => 'processing',     'label' => 'Processing',     'count' => $counts['processing'],     'param' => 'status'],
                    ['key' => 'hold',           'label' => 'Hold',           'count' => $counts['hold'],           'param' => 'status'],
                    ['key' => 'shipped',        'label' => 'In Courier',     'count' => $counts['shipped'],        'param' => 'status'],
                    ['key' => 'delivered',      'label' => 'Delivered',      'count' => $counts['delivered'],      'param' => 'status'],
                    ['key' => 'completed',      'label' => 'Completed',      'count' => $counts['completed'],      'param' => 'status'],
                    ['key' => 'cancelled',      'label' => 'Canceled',       'count' => $counts['cancelled'],      'param' => 'status'],
                    ['key' => 'pending_return', 'label' => 'Pending Return', 'count' => $counts['pending_return'], 'param' => 'status'],
                    ['key' => 'returned',       'label' => 'Returned',       'count' => $counts['returned'],       'param' => 'status'],
                ];
                @endphp

                @foreach($sidebarItems as $item)
                @php
                    if ($item['param'] === null) {
                        $href     = route('admin.orders.index');
                        $isActive = request()->is('admin/orders') && !request('status') && !request('cod_changed');
                    } elseif ($item['param'] === 'cod_changed') {
                        $href     = route('admin.orders.index') . '?cod_changed=1';
                        $isActive = request()->is('admin/orders') && request('cod_changed') === '1';
                    } else {
                        $href     = route('admin.orders.index') . '?status=' . $item['key'];
                        $isActive = request()->is('admin/orders') && request()->get('status') === $item['key'];
                    }
                @endphp
                <a href="{{ $href }}"
                   class="group flex items-center justify-between pl-4 pr-3 py-2 text-xs rounded-lg transition-all duration-150 {{ $isActive ? 'bg-white/[0.12] text-white font-semibold border-l-2 border-yellow-400' : 'text-white/60 font-medium hover:text-white hover:bg-white/[0.08]' }}">
                    <span class="flex items-center gap-2 min-w-0">
                        <span class="w-1 h-1 rounded-full flex-shrink-0 transition-colors {{ $isActive ? 'bg-yellow-400' : 'bg-white/30 group-hover:bg-white/60' }}"></span>
                        <span class="truncate">{{ $item['label'] }}</span>
                    </span>
                    @if($item['count'] > 0)
                        <span class="px-1.5 py-0.5 text-[10px] font-bold rounded-md bg-yellow-400/20 text-yellow-300 border border-yellow-400/40 min-w-[20px] text-center tabular-nums flex-shrink-0 ml-1">{{ $item['count'] > 999 ? '999+' : number_format($item['count']) }}</span>
                    @endif
                </a>
                @endforeach

                {{-- Divider --}}
                <div class="mx-1 my-1.5 border-t border-white/10"></div>

                {{-- Tool links --}}
                @php
                $toolLinks = [
                    ['href' => '#', 'label' => 'Bulk Print'],
                    ['href' => '#', 'label' => 'Send Courier by Barcode'],
                    ['href' => route('admin.payments.index'), 'label' => 'Payments'],
                ];
                @endphp
                @foreach($toolLinks as $tool)
                <a href="{{ $tool['href'] }}"
                   class="group flex items-center pl-4 pr-3 py-2 text-xs rounded-lg transition-all duration-150 {{ request()->url() === $tool['href'] ? 'bg-white/[0.12] text-white font-semibold border-l-2 border-yellow-400' : 'text-white/60 font-medium hover:text-white hover:bg-white/[0.08]' }}">
                    <span class="w-1 h-1 rounded-full flex-shrink-0 mr-2 transition-colors {{ request()->url() === $tool['href'] ? 'bg-yellow-400' : 'bg-white/30 group-hover:bg-white/60' }}"></span>
                    <span class="flex-1 truncate">{{ $tool['label'] }}</span>
                </a>
                @endforeach

            </div>
        </div>

        {{-- Returns --}}
        <a href="{{ route('admin.returns.index') }}"
           class="flex items-center gap-3 py-3 pr-4 text-sm font-medium transition-colors duration-150 {{ $navItem('admin.returns*') }}">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3"/>
            </svg>
            <span class="truncate">Returns</span>
        </a>

        {{-- USER MANAGEMENT --}}
        <div class="px-4 mt-4 mb-1">
            <span class="text-white/30 text-xs font-semibold uppercase tracking-widest">User Management</span>
        </div>

        <a href="{{ route('admin.users.index') }}"
           class="flex items-center gap-3 py-3 pr-4 text-sm font-medium transition-colors duration-150 {{ $navItem('admin.users*') }}">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
            </svg>
            <span class="truncate">Users</span>
        </a>

        <a href="{{ route('admin.roles.index') }}"
           class="flex items-center gap-3 py-3 pr-4 text-sm font-medium transition-colors duration-150 {{ $navItem('admin.roles*') }}">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/>
            </svg>
            <span class="truncate">Roles</span>
        </a>

        {{-- MARKETING --}}
        <div class="px-4 mt-4 mb-1">
            <span class="text-white/30 text-xs font-semibold uppercase tracking-widest">Marketing</span>
        </div>

        <a href="{{ route('admin.coupons.index') }}"
           class="flex items-center gap-3 py-3 pr-4 text-sm font-medium transition-colors duration-150 {{ $navItem('admin.coupons*') }}">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v3.026a2.999 2.999 0 010 5.198v3.026c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-3.026a2.999 2.999 0 010-5.198V6.375c0-.621-.504-1.125-1.125-1.125H3.375z"/>
            </svg>
            <span class="truncate">Coupons</span>
        </a>

        <a href="{{ route('admin.banners.index') }}"
           class="flex items-center gap-3 py-3 pr-4 text-sm font-medium transition-colors duration-150 {{ $navItem('admin.banners*') }}">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/>
            </svg>
            <span class="truncate">Banners</span>
        </a>

        <a href="{{ route('admin.marketing.index') }}"
           class="flex items-center gap-3 py-3 pr-4 text-sm font-medium transition-colors duration-150 {{ $navItem('admin.marketing*') }}">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5M9 11.25v1.5M12 9v3.75m3-6v6"/>
            </svg>
            <span class="truncate">Marketing & Pixels</span>
        </a>

        {{-- COURIER --}}
        <div class="px-4 mt-4 mb-1">
            <span class="text-white/30 text-xs font-semibold uppercase tracking-widest">Courier</span>
        </div>

        <a href="{{ route('admin.courier.index') }}"
           class="flex items-center gap-3 py-3 pr-4 text-sm font-medium transition-colors duration-150 {{ $navItem('admin.courier*') }}">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12"/>
            </svg>
            <span class="truncate">Courier Setup</span>
        </a>

        {{-- INTEGRATIONS --}}
        <div class="px-4 mt-4 mb-1">
            <span class="text-white/30 text-xs font-semibold uppercase tracking-widest">Integrations</span>
        </div>

        <a href="{{ route('admin.integrations.wordpress.index') }}"
           class="flex items-center gap-3 py-3 pr-4 text-sm font-medium transition-colors duration-150 {{ $navItem('admin.integrations*') }}">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244"/>
            </svg>
            <span class="truncate">WordPress Sync</span>
        </a>

        {{-- FRAUD CONTROL --}}
        <div class="px-4 mt-4 mb-1">
            <span class="text-white/30 text-xs font-semibold uppercase tracking-widest">Fraud Control</span>
        </div>

        <a href="{{ route('admin.blacklist.index') }}"
           class="flex items-center gap-3 py-3 pr-4 text-sm font-medium transition-colors duration-150 {{ $navItem('admin.blacklist*') }}">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
            </svg>
            <span class="truncate">Blacklisted Phones</span>
        </a>

        {{-- FINANCE --}}
        <div class="px-4 mt-4 mb-1">
            <span class="text-white/30 text-xs font-semibold uppercase tracking-widest">Finance</span>
        </div>

        <a href="{{ route('admin.invoices.index') }}"
           class="flex items-center gap-3 py-3 pr-4 text-sm font-medium transition-colors duration-150 {{ $navItem('admin.invoices*') }}">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z"/>
            </svg>
            <span class="truncate">Invoices</span>
        </a>

        <a href="{{ route('admin.invoice-setup.index') }}"
           class="flex items-center gap-3 py-3 pr-4 text-sm font-medium transition-colors duration-150 {{ $navItem('admin.invoice-setup*') }}">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z"/>
            </svg>
            <span class="truncate">Invoice Print Setup</span>
        </a>

        {{-- ANALYTICS --}}
        <div class="px-4 mt-4 mb-1">
            <span class="text-white/30 text-xs font-semibold uppercase tracking-widest">Analytics</span>
        </div>

        <a href="{{ route('admin.analytics.index') }}"
           class="flex items-center gap-3 py-3 pr-4 text-sm font-medium transition-colors duration-150 {{ $navItem('admin.analytics*') }}">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/>
            </svg>
            <span class="truncate">Analytics</span>
        </a>

        {{-- INVENTORY --}}
        <div class="px-4 mt-4 mb-1">
            <span class="text-white/30 text-xs font-semibold uppercase tracking-widest">Inventory</span>
        </div>

        <a href="{{ route('admin.inventory.index') }}"
           class="flex items-center gap-3 py-3 pr-4 text-sm font-medium transition-colors duration-150 {{ $navItem('admin.inventory*') }}">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m8.25 3v6.75m0-6.75H6.375m5.625 0h5.625M6 7.5l.25-3.75A.75.75 0 016.99 3h10.02a.75.75 0 01.74.75L18 7.5H6z"/>
            </svg>
            <span class="truncate">Inventory</span>
        </a>

        {{-- REPORTS --}}
        <div class="px-4 mt-4 mb-1">
            <span class="text-white/30 text-xs font-semibold uppercase tracking-widest">Reports</span>
        </div>

        <a href="{{ route('admin.reports.index') }}"
           class="flex items-center gap-3 py-3 pr-4 text-sm font-medium transition-colors duration-150 {{ $navItem('admin.reports*') }}">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
            </svg>
            <span class="truncate">Reports</span>
        </a>

        {{-- SYSTEM --}}
        <div class="px-4 mt-4 mb-1">
            <span class="text-white/30 text-xs font-semibold uppercase tracking-widest">System</span>
        </div>

        <a href="{{ route('admin.settings.index') }}"
           class="flex items-center gap-3 py-3 pr-4 text-sm font-medium transition-colors duration-150 {{ $navItem('admin.settings*') }}">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            <span class="truncate">Settings</span>
        </a>

        <a href="{{ route('admin.settings.api-tokens') }}"
           class="flex items-center gap-3 py-3 pr-4 text-sm font-medium transition-colors duration-150 {{ $navItem('admin.settings.api-tokens*') }}">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z"/>
            </svg>
            <span class="truncate">API Tokens</span>
        </a>

        <a href="{{ route('admin.activity-log.index') }}"
           class="flex items-center gap-3 py-3 pr-4 text-sm font-medium transition-colors duration-150 {{ $navItem('admin.activity-log*') }}">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
            </svg>
            <span class="truncate">Activity Log</span>
        </a>

    </nav>

    {{-- Sidebar footer --}}
    <div class="flex-shrink-0 border-t border-white/10 p-3">
        <div class="flex items-center gap-3 px-2" :class="collapsed ? 'justify-center' : ''">
            <div class="w-8 h-8 bg-accent rounded-full flex-shrink-0 flex items-center justify-center text-white text-xs font-bold" :title="collapsed ? '{{ auth()->user()->name ?? 'Admin' }}' : ''">
                {{ substr(auth()->user()->name ?? 'A', 0, 1) }}
            </div>
            <div class="min-w-0" x-show="!collapsed">
                <p class="text-white text-xs font-medium truncate">{{ auth()->user()->name ?? 'Admin' }}</p>
                <p class="text-white/40 text-xs truncate">Administrator</p>
            </div>
        </div>
    </div>

</aside>

{{-- ══════════════════════════ MAIN ══════════════════════════ --}}
<div class="flex flex-col min-h-screen transition-all duration-300"
     :class="collapsed ? 'lg:ml-16' : 'lg:ml-64'">

    {{-- Top Header --}}
    <header class="sticky top-0 z-30
                   bg-white dark:bg-gray-800
                   border-b border-border dark:border-gray-700
                   h-16 flex items-center px-4 sm:px-6 gap-3
                   transition-colors duration-200">

        {{-- Mobile hamburger (hidden on desktop) --}}
        <button @click="toggle()"
                class="p-2 rounded-xl flex-shrink-0 transition-colors lg:hidden
                       text-textsecondary dark:text-gray-400
                       hover:bg-gray-100 dark:hover:bg-gray-700
                       hover:text-textprimary dark:hover:text-gray-200"
                aria-label="Toggle sidebar">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>
            </svg>
        </button>

        <div class="flex items-center gap-3 flex-1 min-w-0">
            <h1 class="font-heading font-semibold text-base sm:text-lg truncate flex-shrink-0
                       text-textprimary dark:text-gray-100">
                @yield('page-title', 'Dashboard')
            </h1>
            @if(request()->routeIs('admin.dashboard'))
            <div class="relative ml-4" id="globalSearchWrapper">
                <div class="flex items-center gap-2 bg-gray-100 dark:bg-gray-800 rounded-xl px-3 py-1.5 w-72">
                    <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" id="globalSearchInput"
                        placeholder="Search order ID or phone..."
                        autocomplete="off"
                        style="outline:none !important; box-shadow:none !important; border:none !important;"
                        class="flex-1 bg-transparent text-sm text-gray-700 dark:text-white placeholder-gray-400"
                        oninput="globalSearch(this.value)"
                        onkeydown="if(event.key==='Escape') document.getElementById('globalSearchResults').classList.add('hidden')">
                    <span id="searchSpinner" class="hidden">
                        <svg class="w-4 h-4 text-gray-400 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                        </svg>
                    </span>
                </div>
                <div id="globalSearchResults"
                    class="absolute top-full left-0 mt-2 w-full bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-700 rounded-2xl shadow-xl z-50 hidden overflow-hidden">
                    <div id="searchResultsInner"></div>
                </div>
            </div>
            @endif
        </div>

        <div class="flex items-center gap-1 flex-shrink-0">

            {{-- Dark mode toggle --}}
            <button @click="$store.theme.toggle()"
                    class="p-2 rounded-xl transition-colors
                           text-textsecondary dark:text-gray-400
                           hover:bg-gray-100 dark:hover:bg-gray-700
                           hover:text-textprimary dark:hover:text-gray-200">
                <svg class="w-5 h-5 dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z"/>
                </svg>
                <svg class="w-5 h-5 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z"/>
                </svg>
            </button>

            {{-- Notification bell --}}
            <button class="relative p-2 rounded-xl transition-colors
                           text-textsecondary dark:text-gray-400
                           hover:bg-gray-100 dark:hover:bg-gray-700
                           hover:text-textprimary dark:hover:text-gray-200">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/>
                </svg>
                <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-accent rounded-full"></span>
            </button>

            {{-- User dropdown --}}
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open"
                        class="flex items-center gap-2 px-2 py-1.5 rounded-xl transition-colors text-sm
                               hover:bg-gray-100 dark:hover:bg-gray-700
                               text-textprimary dark:text-gray-200">
                    <div class="w-8 h-8 bg-primary dark:bg-accent rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                        {{ substr(auth()->user()->name ?? 'A', 0, 1) }}
                    </div>
                    <span class="hidden sm:block font-medium max-w-[120px] truncate">{{ auth()->user()->name ?? 'Admin' }}</span>
                    <svg class="w-4 h-4 text-textsecondary dark:text-gray-400 flex-shrink-0 transition-transform duration-200"
                         :class="{ 'rotate-180': open }"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/>
                    </svg>
                </button>

                <div x-show="open"
                     x-cloak
                     @click.away="open = false"
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                     x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-100"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-95"
                     class="absolute right-0 mt-2 w-52 rounded-2xl shadow-lg py-1.5 z-50
                            bg-white dark:bg-gray-800
                            border border-border dark:border-gray-700">
                    <div class="px-3 py-2 border-b border-border dark:border-gray-700 mb-1">
                        <p class="text-xs font-medium text-textprimary dark:text-gray-200 truncate">{{ auth()->user()->name ?? 'Admin' }}</p>
                        <p class="text-xs text-textsecondary dark:text-gray-500 truncate">{{ auth()->user()->email ?? '' }}</p>
                    </div>
                    <a href="#"
                       class="flex items-center gap-2 px-3 py-2 text-sm transition-colors
                              text-textprimary dark:text-gray-200
                              hover:bg-gray-50 dark:hover:bg-gray-700">
                        <svg class="w-4 h-4 text-textsecondary dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                        </svg>
                        Profile
                    </a>
                    <div class="border-t border-border dark:border-gray-700 my-1"></div>
                    <form method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <button type="submit"
                                class="w-full flex items-center gap-2 px-3 py-2 text-sm transition-colors
                                       text-danger hover:bg-red-50 dark:hover:bg-red-900/20">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"/>
                            </svg>
                            Logout
                        </button>
                    </form>
                </div>
            </div>

        </div>
    </header>

    {{-- Page Content --}}
    <main class="flex-1 p-4 sm:p-6">
        @yield('content')
    </main>

    {{-- Footer --}}
    <footer class="border-t px-6 py-3
                   bg-white dark:bg-gray-800
                   border-border dark:border-gray-700
                   transition-colors duration-200">
        <p class="text-xs text-center text-textsecondary dark:text-gray-500">
            &copy; {{ date('Y') }} Clothonic Admin. All rights reserved.
        </p>
    </footer>

</div>

<x-admin.confirm-modal />

@vite(['resources/js/admin.js'])
@stack('scripts')

<script>
document.addEventListener('DOMContentLoaded', function () {
    const aside = document.getElementById('admin-sidebar');
    if (!aside) return;
    const nav = aside.querySelector('nav');
    if (!nav) return;

    const activeLink = nav.querySelector('a.bg-white\\/10, a.text-white.font-semibold, a.border-l-2');
    if (!activeLink) return;

    // offsetTop of both elements is relative to aside (position:fixed = offsetParent)
    const itemPos    = activeLink.offsetTop - nav.offsetTop;
    const itemHeight = activeLink.offsetHeight;
    nav.scrollTop    = itemPos - (nav.offsetHeight / 2) + (itemHeight / 2);
});
</script>

<script>
let globalSearchTimeout;
function globalSearch(q) {
    clearTimeout(globalSearchTimeout);
    const results = document.getElementById('globalSearchResults');
    const inner = document.getElementById('searchResultsInner');
    const spinner = document.getElementById('searchSpinner');
    if (!results) return;
    if (q.length < 2) { results.classList.add('hidden'); return; }
    spinner.classList.remove('hidden');
    globalSearchTimeout = setTimeout(() => {
        fetch('/admin/orders/search-live?q=' + encodeURIComponent(q), {
            headers: {'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content}
        })
        .then(r => r.json())
        .then(data => {
            spinner.classList.add('hidden');
            if (!data.length) {
                inner.innerHTML = '<div class="px-4 py-4 text-sm text-gray-400 text-center">No results found</div>';
            } else {
                inner.innerHTML = data.map(o => `
                    <a href="/admin/orders/${o.id}"
                        class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-800 border-b border-gray-50 dark:border-gray-800 last:border-0 transition">
                        <div class="w-8 h-8 rounded-xl bg-[#1B2A4A]/10 dark:bg-[#1B2A4A]/30 flex items-center justify-center flex-shrink-0">
                            <span class="text-[#1B2A4A] dark:text-blue-400 text-xs font-bold">#</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="text-[#1B2A4A] dark:text-blue-400 font-bold text-sm">#${o.id}</span>
                                <span class="text-gray-700 dark:text-gray-300 text-sm truncate">${o.customer_name}</span>
                            </div>
                            <div class="text-xs text-gray-400">${o.phone}</div>
                        </div>
                        <span class="px-2 py-0.5 rounded-lg text-xs font-semibold flex-shrink-0
                            ${o.status === 'pending' ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400' :
                              o.status === 'confirmed' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' :
                              o.status === 'delivered' || o.status === 'completed' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' :
                              o.status === 'cancelled' || o.status === 'failed' ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' :
                              'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400'}">${o.status}</span>
                    </a>
                `).join('') + `<div class="px-4 py-2 bg-gray-50 dark:bg-gray-800 text-center">
                    <a href="/admin/orders?search=${encodeURIComponent(q)}" class="text-xs text-[#F59E0B] hover:underline font-medium">View all results →</a>
                </div>`;
            }
            results.classList.remove('hidden');
        })
        .catch(() => spinner.classList.add('hidden'));
    }, 300);
}

document.addEventListener('click', function(e) {
    const wrapper = document.getElementById('globalSearchWrapper');
    if (wrapper && !wrapper.contains(e.target)) {
        document.getElementById('globalSearchResults').classList.add('hidden');
    }
});
</script>

</body>
</html>
