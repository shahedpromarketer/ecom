@extends('layouts.admin')

@section('title', 'Create User')
@section('page-title', 'Create User')

@section('breadcrumb')
    <span class="text-textsecondary">/</span>
    <a href="{{ route('admin.users.index') }}" class="text-textsecondary hover:text-textprimary dark:hover:text-gray-200 transition-colors">Users</a>
    <span class="text-textsecondary">/</span>
    <span class="text-textprimary font-medium">Create</span>
@endsection

@section('content')
<div class="max-w-2xl">

@if(isset($maxUsers))
@php $pct = min(100, round(($current / $maxUsers) * 100)); @endphp
<div class="mb-4 flex items-center gap-3 p-3 rounded-xl border {{ $pct >= 80 ? 'bg-orange-50 border-orange-200 dark:bg-orange-900/20 dark:border-orange-800' : 'bg-blue-50 border-blue-200 dark:bg-blue-900/20 dark:border-blue-800' }}">
    <svg class="w-4 h-4 flex-shrink-0 {{ $pct >= 80 ? 'text-orange-500' : 'text-blue-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0"/></svg>
    <p class="text-xs {{ $pct >= 80 ? 'text-orange-700 dark:text-orange-400' : 'text-blue-700 dark:text-blue-400' }}">
        Your plan allows <strong>{{ $maxUsers }}</strong> users. Currently using <strong>{{ $current }}</strong>/{{ $maxUsers }}.
    </p>
</div>
@endif

<x-admin.card>
    <x-slot:header>
        <h3 class="font-heading font-semibold text-textprimary dark:text-gray-100">New User</h3>
    </x-slot:header>
    <form method="POST" action="{{ route('admin.users.store') }}" class="p-6 space-y-5">
        @csrf

        @if($errors->any())
        <div class="p-3.5 rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800">
            <ul class="list-disc list-inside text-sm text-red-700 dark:text-red-400 space-y-1">
                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
        @endif

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-medium text-textsecondary dark:text-gray-400 mb-1.5">Full Name <span class="text-danger">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}" required
                       class="w-full px-4 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700/50 text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary dark:focus:border-primary transition-all duration-200">
            </div>
            <div>
                <label class="block text-xs font-medium text-textsecondary dark:text-gray-400 mb-1.5">Mobile number</label>
                <input type="text" name="phone" value="{{ old('phone') }}" placeholder="01XXXXXXXXX"
                       class="w-full px-4 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700/50 text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary dark:focus:border-primary transition-all duration-200">
            </div>
        </div>

        <div>
            <label class="block text-xs font-medium text-textsecondary dark:text-gray-400 mb-1.5">Email <span class="text-textsecondary font-normal">(or give a mobile number — either one is used to sign in)</span></label>
            <input type="email" name="email" value="{{ old('email') }}"
                   class="w-full px-4 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700/50 text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary dark:focus:border-primary transition-all duration-200">
        </div>

        <div x-data="{ show: false }">
            <label class="block text-xs font-medium text-textsecondary dark:text-gray-400 mb-1.5">Password <span class="text-danger">*</span></label>
            <div class="relative">
                <input :type="show ? 'text' : 'password'" name="password" required
                       class="w-full px-4 py-2.5 pr-10 text-sm rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700/50 text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary dark:focus:border-primary transition-all duration-200">
                <button type="button" @click="show = !show" class="absolute right-3 top-1/2 -translate-y-1/2 text-textsecondary hover:text-textprimary">
                    <svg x-show="!show" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <svg x-show="show" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" x-cloak><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/></svg>
                </button>
            </div>
        </div>

        <div>
            <label class="block text-xs font-medium text-textsecondary dark:text-gray-400 mb-1.5">Role</label>
            <select name="role_id"
                    class="w-full px-4 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700/50 text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary dark:focus:border-primary transition-all duration-200">
                <option value="">No Role</option>
                @foreach($roles as $role)
                    <option value="{{ $role->id }}" {{ old('role_id') == $role->id ? 'selected' : '' }}>{{ $role->display_name }}</option>
                @endforeach
            </select>
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit" class="flex-1 py-2.5 font-semibold bg-accent hover:bg-yellow-500 text-black rounded-xl transition-colors active:scale-95">Create User</button>
            <a href="{{ route('admin.users.index') }}" class="flex-1 py-2.5 text-center font-semibold border border-gray-200 dark:border-gray-600 text-textsecondary dark:text-gray-400 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">Cancel</a>
        </div>
    </form>
</x-admin.card>
</div>
@endsection
