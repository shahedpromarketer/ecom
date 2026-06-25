<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login — Ecom Pilot</title>
    {{-- Anti-flash: apply dark class before CSS loads --}}
    <script>if(localStorage.getItem('adminTheme')==='dark')document.documentElement.classList.add('dark');</script>
    @vite(['resources/css/admin.css'])
</head>
<body class="font-body min-h-screen bg-muted dark:bg-gray-900 transition-colors duration-200">

<div class="min-h-screen flex flex-col lg:flex-row">

    {{-- Left brand panel (always dark navy — unchanged by theme) --}}
    <div class="hidden lg:flex lg:w-1/2 bg-primary flex-col items-center justify-center p-12 relative overflow-hidden">
        <div class="absolute inset-0 opacity-5">
            <div class="absolute top-0 left-0 w-64 h-64 border border-white rounded-full -translate-x-1/2 -translate-y-1/2"></div>
            <div class="absolute bottom-0 right-0 w-96 h-96 border border-white rounded-full translate-x-1/3 translate-y-1/3"></div>
            <div class="absolute top-1/2 right-0 w-48 h-48 border border-white rounded-full translate-x-1/2 -translate-y-1/2"></div>
        </div>

        <div class="relative text-center">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-white/10 rounded-2xl mb-6">
                <svg class="w-8 h-8 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 002.25 1.016c.896 0 1.7-.393 2.25-1.016a3.001 3.001 0 003.75.614m-16.5 0a3.004 3.004 0 01-.621-4.72L4.318 3.44A1.5 1.5 0 015.378 3h13.243a1.5 1.5 0 011.06.44l1.19 1.189a3 3 0 01-.621 4.72m-13.5 8.65h3.75a.75.75 0 00.75-.75V13.5a.75.75 0 00-.75-.75H6.75a.75.75 0 00-.75.75v3.75c0 .415.336.75.75.75z"/>
                </svg>
            </div>
            <h1 class="font-heading text-5xl font-bold text-white tracking-tight mb-3">Ecom Pilot</h1>
            <div class="w-12 h-0.5 bg-accent mx-auto mb-4"></div>
            <p class="text-white/70 text-xl font-medium">Admin Panel</p>
            <p class="text-white/40 text-sm mt-3 max-w-xs mx-auto">
                Manage your store, track orders and grow your business.
            </p>
        </div>
    </div>

    {{-- Right: login form --}}
    <div class="flex-1 flex flex-col items-center justify-center p-6 sm:p-8">

        {{-- Mobile logo --}}
        <div class="lg:hidden text-center mb-8">
            <h1 class="font-heading text-3xl font-bold text-primary dark:text-white">Ecom Pilot</h1>
            <p class="text-textsecondary dark:text-gray-400 text-sm mt-1">Admin Panel</p>
        </div>

        <div class="w-full max-w-md">
            <div class="rounded-2xl shadow-sm p-8
                        bg-white dark:bg-gray-800
                        border border-border dark:border-gray-700">

                <div class="mb-7">
                    <h2 class="font-heading text-2xl font-semibold text-textprimary dark:text-gray-100">
                        Welcome back
                    </h2>
                    <p class="text-textsecondary dark:text-gray-400 text-sm mt-1">
                        Sign in to your admin account to continue
                    </p>
                </div>

                {{-- Session error --}}
                @if(session('error'))
                    <div class="mb-5 flex items-center gap-3 p-3.5 rounded-lg
                                bg-red-50 dark:bg-red-900/20
                                border border-red-200 dark:border-red-800">
                        <svg class="w-4 h-4 text-danger dark:text-red-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z"/>
                        </svg>
                        <p class="text-sm text-danger dark:text-red-400">{{ session('error') }}</p>
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.login') }}" class="space-y-5">
                    @csrf

                    {{-- Email --}}
                    <div>
                        <label for="email" class="block text-sm font-medium mb-1.5
                                                   text-textprimary dark:text-gray-200">
                            Email address
                        </label>
                        <input type="email" id="email" name="email"
                               value="{{ old('email') }}"
                               required autofocus autocomplete="email"
                               class="w-full px-4 py-2.5 text-sm rounded-lg transition-colors
                                      focus:outline-none focus:ring-2 focus:ring-accent/40 focus:border-accent
                                      text-textprimary dark:text-gray-100
                                      dark:placeholder-gray-500
                                      @error('email')
                                          border-danger bg-red-50 dark:bg-red-900/10 dark:border-red-700
                                      @else
                                          border border-border dark:border-gray-600 dark:bg-gray-700
                                      @enderror">
                        @error('email')
                            <p class="mt-1.5 text-xs text-danger dark:text-red-400 flex items-center gap-1">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-5a.75.75 0 01.75.75v4.5a.75.75 0 01-1.5 0v-4.5A.75.75 0 0110 5zm0 10a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/></svg>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- Password --}}
                    <div>
                        <label for="password" class="block text-sm font-medium mb-1.5
                                                      text-textprimary dark:text-gray-200">
                            Password
                        </label>
                        <input type="password" id="password" name="password"
                               required autocomplete="current-password"
                               class="w-full px-4 py-2.5 text-sm rounded-lg transition-colors
                                      focus:outline-none focus:ring-2 focus:ring-accent/40 focus:border-accent
                                      text-textprimary dark:text-gray-100
                                      @error('password')
                                          border-danger bg-red-50 dark:bg-red-900/10 dark:border-red-700
                                      @else
                                          border border-border dark:border-gray-600 dark:bg-gray-700
                                      @enderror">
                        @error('password')
                            <p class="mt-1.5 text-xs text-danger dark:text-red-400 flex items-center gap-1">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-5a.75.75 0 01.75.75v4.5a.75.75 0 01-1.5 0v-4.5A.75.75 0 0110 5zm0 10a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/></svg>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- Remember me --}}
                    <div class="flex items-center">
                        <input type="checkbox" id="remember" name="remember"
                               class="w-4 h-4 text-accent rounded focus:ring-accent focus:ring-2
                                      border-border dark:border-gray-600 dark:bg-gray-700">
                        <label for="remember" class="ml-2 text-sm select-none cursor-pointer
                                                     text-textsecondary dark:text-gray-400">
                            Keep me signed in
                        </label>
                    </div>

                    {{-- Submit --}}
                    <button type="submit"
                            class="w-full flex items-center justify-center gap-2
                                   bg-accent hover:bg-yellow-500 active:bg-yellow-600
                                   text-white font-semibold py-2.5 px-4 rounded-lg
                                   transition-colors duration-200 text-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"/>
                        </svg>
                        Sign In
                    </button>
                </form>
            </div>

            <p class="text-center text-xs mt-5 text-textsecondary dark:text-gray-600">
                &copy; {{ date('Y') }} Ecom Pilot. All rights reserved.
            </p>
        </div>
    </div>
</div>

@vite(['resources/js/admin.js'])
</body>
</html>
