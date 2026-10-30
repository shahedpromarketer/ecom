<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin — Ecom Pilot</title>
    @vite(['resources/css/admin.css'])
    @include('layouts.partials.responsive')
    <style>
        .sa-sidebar { background-color: #1B2A4A; }
        /* Phones: the menu slides in from the left; desktop: always open. */
        @media (max-width: 1023px) {
            .sa-sidebar { transform: translateX(-100%); transition: transform .2s ease; }
            body.sa-menu-open .sa-sidebar { transform: none; }
            body.sa-menu-open .sa-overlay { display: block; }
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen overflow-x-hidden">

{{-- Dark background behind the open menu on phones --}}
<div class="sa-overlay hidden fixed inset-0 bg-black/40 z-40 lg:hidden" onclick="document.body.classList.remove('sa-menu-open')"></div>

{{-- Sidebar --}}
<aside class="sa-sidebar w-64 h-screen overflow-y-auto flex flex-col text-white fixed left-0 top-0 z-50">
    <div class="p-6 border-b border-white/10 flex items-start justify-between">
        <div>
            <h1 class="text-lg font-bold leading-tight">Ecom Pilot</h1>
            <p class="text-xs text-blue-300 mt-0.5">Super Admin Panel</p>
        </div>
        <button type="button" class="lg:hidden p-1 -mr-2 text-gray-300 hover:text-white" aria-label="Close menu"
                onclick="document.body.classList.remove('sa-menu-open')">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>

    <nav class="flex-1 p-4 space-y-1">
        @php $nav = fn($r) => request()->routeIs($r) ? 'bg-white/20 text-white' : 'text-gray-300 hover:bg-white/10'; @endphp

        <a href="{{ route('super.dashboard') }}" class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium {{ $nav('super.dashboard') }} transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
            Dashboard
        </a>

        <a href="{{ route('super.tenants.index') }}" class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium {{ $nav('super.tenants.*') }} transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            Tenants
        </a>

        <a href="{{ route('super.tenants.create') }}" class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium text-gray-300 hover:bg-white/10 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Add Tenant
        </a>

        @php $walletPending = \App\Models\WalletRechargeRequest::withoutGlobalScopes()->where('status', 'pending')->count(); @endphp
        <a href="{{ route('super.wallet') }}" class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium {{ $nav('super.wallet*') }} transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a2.25 2.25 0 0 0-2.25-2.25H15a3 3 0 1 1-6 0H5.25A2.25 2.25 0 0 0 3 12m18 0v6a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 18v-6m18 0V9M3 12V9m18 0a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 9m18 0V6a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 6v3"/></svg>
            Wallet & recharges
            @if ($walletPending)<span class="ml-auto px-1.5 py-0.5 text-[10px] font-bold rounded-md bg-amber-400 text-gray-900">{{ $walletPending }}</span>@endif
        </a>

        <div class="pt-2 pb-1">
            <p class="px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Tools</p>
        </div>

        <a href="{{ route('super.announcements') }}" class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium {{ $nav('super.announcements*') }} transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
            Announcements
        </a>

        <a href="{{ route('super.system-health') }}" class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium {{ $nav('super.system-health*') }} transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2V9M9 21H5a2 2 0 01-2-2V9m0 0h18"/></svg>
            System Health
        </a>

        <a href="{{ route('super.activity-log') }}" class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium {{ $nav('super.activity-log*') }} transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            Activity Log
        </a>
    </nav>

    <div class="p-4 border-t border-white/10">
        <form method="POST" action="{{ route('admin.logout') }}">
            @csrf
            <button type="submit" class="flex items-center gap-2 text-sm text-gray-300 hover:text-white transition w-full px-4 py-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                Logout
            </button>
        </form>
    </div>
</aside>

{{-- Main Content --}}
<div class="lg:ml-64 flex flex-col min-h-screen min-w-0">
    {{-- Top Bar --}}
    <header class="bg-white shadow-sm px-4 sm:px-6 py-3 sm:py-4 flex items-center gap-3 sticky top-0 z-30">
        <button type="button" class="lg:hidden p-2 -ml-2 rounded-lg text-gray-600 hover:bg-gray-100" aria-label="Open menu"
                onclick="document.body.classList.add('sa-menu-open')">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
        </button>
        <h2 class="text-base sm:text-lg font-semibold text-gray-800 truncate min-w-0 flex-1">@yield('page-title', 'Super Admin')</h2>
        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700 whitespace-nowrap">
            Super Admin
        </span>
    </header>

    {{-- Flash Messages --}}
    <div class="px-4 sm:px-6 pt-4">
        @if (session('success'))
            <div class="p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm mb-4">
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm mb-4">
                {{ session('error') }}
            </div>
        @endif
    </div>

    {{-- Page Content --}}
    <main class="flex-1 p-4 sm:p-6 min-w-0">
        @yield('content')
    </main>
</div>

</body>
</html>
