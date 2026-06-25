@extends('layouts.admin')

@section('title', 'My Profile')
@section('page-title', 'My Profile')

@section('breadcrumb')
    <span class="text-textsecondary">/</span>
    <span class="text-textprimary font-medium">Profile</span>
@endsection

@section('content')
<div x-data="{ showCurrent: false, showNew: false, showConfirm: false }">

{{-- Flash Messages --}}
@if(session('success'))
<div class="mb-4 flex items-center gap-2 p-3 rounded-xl bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800">
    <svg class="w-4 h-4 text-green-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/>
    </svg>
    <p class="text-sm text-green-700 dark:text-green-400">{{ session('success') }}</p>
</div>
@endif
@if(session('password_success'))
<div class="mb-4 flex items-center gap-2 p-3 rounded-xl bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800">
    <svg class="w-4 h-4 text-green-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/>
    </svg>
    <p class="text-sm text-green-700 dark:text-green-400">{{ session('password_success') }}</p>
</div>
@endif

{{-- Two-column layout --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">

    {{-- LEFT: Personal Information --}}
    <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-2xl border border-border dark:border-gray-700 shadow-sm">

        {{-- Card header with avatar --}}
        <div class="flex items-center gap-4 px-6 py-4 border-b border-border dark:border-gray-700">

            {{-- Avatar (clickable, auto-submit) --}}
            <form id="avatar-form" method="POST" action="{{ route('admin.profile.update') }}" enctype="multipart/form-data">
                @csrf
                <button type="button" onclick="document.getElementById('avatar-input').click()"
                        class="relative group flex-shrink-0 focus:outline-none">
                    @if($user->avatar)
                        <img src="{{ asset('storage/' . $user->avatar) }}" alt="{{ $user->name }}"
                             class="w-12 h-12 rounded-xl object-cover border-2 border-border dark:border-gray-600">
                    @else
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-primary to-blue-400
                                    flex items-center justify-center text-white text-lg font-bold
                                    border-2 border-border dark:border-gray-600">
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        </div>
                    @endif
                    <div class="absolute inset-0 rounded-xl bg-black/40 opacity-0 group-hover:opacity-100
                                transition-opacity flex items-center justify-center">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                </button>
                <input type="file" id="avatar-input" name="avatar" accept="image/*" class="hidden"
                       onchange="document.getElementById('avatar-form').submit()">
                @error('avatar') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </form>

            {{-- Title + user info --}}
            <div class="min-w-0">
                <p class="text-sm font-semibold text-textprimary dark:text-gray-100">Personal Information</p>
                <p class="text-xs text-textsecondary dark:text-gray-400 truncate">{{ $user->email }}</p>
            </div>
        </div>

        {{-- Form --}}
        <form method="POST" action="{{ route('admin.profile.update') }}" enctype="multipart/form-data" class="p-6 space-y-4">
            @csrf

            {{-- Full Name --}}
            <div>
                <label class="block text-xs font-semibold text-textprimary dark:text-gray-300 mb-1.5">Full Name</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-3 flex items-center pointer-events-none text-gray-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </span>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}"
                           class="w-full pl-9 pr-4 py-2.5 text-sm rounded-xl border border-border dark:border-gray-600
                                  bg-white dark:bg-gray-700/50 text-textprimary dark:text-gray-100
                                  focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all">
                </div>
                @error('name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Email + Phone --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-textprimary dark:text-gray-300 mb-1.5">Email Address</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-3 flex items-center pointer-events-none text-gray-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </span>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}"
                               class="w-full pl-9 pr-4 py-2.5 text-sm rounded-xl border border-border dark:border-gray-600
                                      bg-white dark:bg-gray-700/50 text-textprimary dark:text-gray-100
                                      focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all">
                    </div>
                    @error('email') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-textprimary dark:text-gray-300 mb-1.5">Phone Number</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-3 flex items-center pointer-events-none text-gray-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                        </span>
                        <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" placeholder="Optional"
                               class="w-full pl-9 pr-4 py-2.5 text-sm rounded-xl border border-border dark:border-gray-600
                                      bg-white dark:bg-gray-700/50 text-textprimary dark:text-gray-100 placeholder-gray-400
                                      focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all">
                    </div>
                    @error('phone') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Role + Joined (readonly) --}}
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-textprimary dark:text-gray-300 mb-1.5">Role</label>
                    <div class="flex items-center gap-2 px-3.5 py-2.5 text-sm rounded-xl
                                border border-border dark:border-gray-600 bg-gray-50 dark:bg-gray-700
                                text-textsecondary dark:text-gray-400 capitalize">
                        <span class="w-2 h-2 rounded-full bg-green-400 flex-shrink-0"></span>
                        {{ $user->role ?? 'admin' }}
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-textprimary dark:text-gray-300 mb-1.5">Member Since</label>
                    <div class="px-3.5 py-2.5 text-sm rounded-xl border border-border dark:border-gray-600
                                bg-gray-50 dark:bg-gray-700 text-textsecondary dark:text-gray-400">
                        {{ $user->created_at->format('d M Y') }}
                    </div>
                </div>
            </div>

            <div class="flex justify-end pt-1">
                <button type="submit"
                        class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold
                               bg-primary hover:bg-primary/90 text-white rounded-xl transition-colors shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Save Changes
                </button>
            </div>
        </form>
    </div>

    {{-- RIGHT: Security --}}
    <div class="lg:col-span-1 bg-white dark:bg-gray-800 rounded-2xl border border-border dark:border-gray-700 shadow-sm">

        <div class="flex items-center gap-3 px-6 py-4 border-b border-border dark:border-gray-700">
            <div class="w-10 h-10 rounded-xl bg-amber-50 dark:bg-amber-900/20 flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
            </div>
            <div>
                <p class="text-sm font-semibold text-textprimary dark:text-gray-100">Security</p>
                <p class="text-xs text-textsecondary dark:text-gray-400">Change your password.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.profile.password') }}" class="p-6 space-y-4">
            @csrf

            {{-- Current Password --}}
            <div>
                <label class="block text-xs font-semibold text-textprimary dark:text-gray-300 mb-1.5">Current Password</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-3 flex items-center pointer-events-none text-gray-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </span>
                    <input :type="showCurrent ? 'text' : 'password'" name="current_password"
                           class="w-full pl-9 pr-10 py-2.5 text-sm rounded-xl border border-border dark:border-gray-600
                                  bg-white dark:bg-gray-700/50 text-textprimary dark:text-gray-100
                                  focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all">
                    <button type="button" @click="showCurrent=!showCurrent"
                            class="absolute inset-y-0 right-3 flex items-center text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg x-show="!showCurrent" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        <svg x-show="showCurrent" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                        </svg>
                    </button>
                </div>
                @error('current_password') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- New Password --}}
            <div>
                <label class="block text-xs font-semibold text-textprimary dark:text-gray-300 mb-1.5">New Password</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-3 flex items-center pointer-events-none text-gray-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                        </svg>
                    </span>
                    <input :type="showNew ? 'text' : 'password'" name="password"
                           class="w-full pl-9 pr-10 py-2.5 text-sm rounded-xl border border-border dark:border-gray-600
                                  bg-white dark:bg-gray-700/50 text-textprimary dark:text-gray-100
                                  focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all">
                    <button type="button" @click="showNew=!showNew"
                            class="absolute inset-y-0 right-3 flex items-center text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg x-show="!showNew" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        <svg x-show="showNew" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                        </svg>
                    </button>
                </div>
                @error('password') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Confirm Password --}}
            <div>
                <label class="block text-xs font-semibold text-textprimary dark:text-gray-300 mb-1.5">Confirm New Password</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-3 flex items-center pointer-events-none text-gray-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </span>
                    <input :type="showConfirm ? 'text' : 'password'" name="password_confirmation"
                           class="w-full pl-9 pr-10 py-2.5 text-sm rounded-xl border border-border dark:border-gray-600
                                  bg-white dark:bg-gray-700/50 text-textprimary dark:text-gray-100
                                  focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all">
                    <button type="button" @click="showConfirm=!showConfirm"
                            class="absolute inset-y-0 right-3 flex items-center text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg x-show="!showConfirm" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        <svg x-show="showConfirm" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                        </svg>
                    </button>
                </div>
            </div>

            <div class="pt-1">
                <button type="submit"
                        class="w-full inline-flex items-center justify-center gap-2 px-5 py-2.5 text-sm font-semibold
                               bg-amber-500 hover:bg-amber-600 text-white rounded-xl transition-colors shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    Update Password
                </button>
            </div>
        </form>
    </div>

</div>
</div>
@endsection
