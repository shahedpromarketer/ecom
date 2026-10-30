@extends('layouts.admin')

@section('title', 'Edit User')
@section('page-title', 'Edit User')

@section('breadcrumb')
    <span class="text-textsecondary">/</span>
    <a href="{{ route('admin.users.index') }}" class="text-textsecondary hover:text-textprimary dark:hover:text-gray-200 transition-colors">Users</a>
    <span class="text-textsecondary">/</span>
    <a href="{{ route('admin.users.show', $user) }}" class="text-textsecondary hover:text-textprimary dark:hover:text-gray-200 transition-colors">{{ $user->name }}</a>
    <span class="text-textsecondary">/</span>
    <span class="text-textprimary font-medium">Edit</span>
@endsection

@section('content')
<div class="max-w-2xl">
<x-admin.card>
    <x-slot:header>
        <h3 class="font-heading font-semibold text-textprimary dark:text-gray-100">Edit: {{ $user->name }}</h3>
    </x-slot:header>
    <form method="POST" action="{{ route('admin.users.update', $user) }}" class="p-6 space-y-5">
        @csrf @method('PUT')

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
                <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                       class="w-full px-4 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700/50 text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary dark:focus:border-primary transition-all duration-200">
            </div>
            <div>
                <label class="block text-xs font-medium text-textsecondary dark:text-gray-400 mb-1.5">Mobile number</label>
                <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" placeholder="01XXXXXXXXX"
                       class="w-full px-4 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700/50 text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary dark:focus:border-primary transition-all duration-200">
            </div>
        </div>

        <div>
            <label class="block text-xs font-medium text-textsecondary dark:text-gray-400 mb-1.5">Email <span class="text-textsecondary font-normal">(or give a mobile number — either one is used to sign in)</span></label>
            <input type="email" name="email" value="{{ old('email', $user->email) }}"
                   class="w-full px-4 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700/50 text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary dark:focus:border-primary transition-all duration-200">
        </div>

        @if ($user->id !== auth()->id())
        <div x-data="{ show: false }">
            <label class="block text-xs font-medium text-textsecondary dark:text-gray-400 mb-1.5">New password <span class="font-normal">(leave empty to keep the current one)</span></label>
            <div class="flex gap-2">
                <input :type="show ? 'text' : 'password'" name="password" x-ref="pw" minlength="8" maxlength="100" autocomplete="new-password" placeholder="Min 8 characters"
                       class="flex-1 min-w-0 px-4 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700/50 text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary font-mono">
                <button type="button" class="px-3 rounded-xl border border-gray-200 dark:border-gray-600 text-xs font-semibold hover:bg-gray-50 dark:hover:bg-gray-700"
                        @click="$refs.pw.value = Array.from(crypto.getRandomValues(new Uint32Array(3)), n => n.toString(36)).join('').slice(0, 10); show = true">Generate</button>
            </div>
            <p class="text-xs text-textsecondary mt-1">Share it with {{ $user->name }}; they are signed out everywhere and use the new one.</p>
        </div>
        @endif

        <div>
            <label class="block text-xs font-medium text-textsecondary dark:text-gray-400 mb-1.5">Role</label>
            <select name="role_id"
                    class="w-full px-4 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700/50 text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary dark:focus:border-primary transition-all duration-200">
                <option value="">No Role</option>
                @foreach($roles as $role)
                    <option value="{{ $role->id }}" {{ old('role_id', $user->role_id) == $role->id ? 'selected' : '' }}>{{ $role->display_name }}</option>
                @endforeach
            </select>
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit" class="flex-1 py-2.5 font-semibold bg-accent hover:bg-yellow-500 text-black rounded-xl transition-colors active:scale-95">Save Changes</button>
            <a href="{{ route('admin.users.show', $user) }}" class="flex-1 py-2.5 text-center font-semibold border border-gray-200 dark:border-gray-600 text-textsecondary dark:text-gray-400 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">Cancel</a>
        </div>
    </form>
</x-admin.card>
</div>
@endsection
