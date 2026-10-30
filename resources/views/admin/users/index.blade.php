@extends('layouts.admin')

@section('title', 'Users')
@section('page-title', 'Users')

@section('breadcrumb')
    <span class="text-textsecondary">/</span>
    <span class="text-textprimary font-medium">Users</span>
@endsection

@section('content')

@if(session('success'))
    <div class="mb-5 flex items-center gap-3 p-3.5 rounded-xl bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800">
        <svg class="w-4 h-4 text-green-600 dark:text-green-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/></svg>
        <p class="text-sm text-green-700 dark:text-green-400">{{ session('success') }}</p>
    </div>
@endif
@if(session('error'))
    <div class="mb-5 flex items-center gap-3 p-3.5 rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800">
        <svg class="w-4 h-4 text-red-600 dark:text-red-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd"/></svg>
        <p class="text-sm text-red-700 dark:text-red-400">{{ session('error') }}</p>
    </div>
@endif

{{-- Stats --}}
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm p-5">
        <p class="text-xs font-medium text-textsecondary dark:text-gray-400 uppercase tracking-wider">Total Users</p>
        <p class="text-2xl font-bold text-textprimary dark:text-gray-100 mt-1">{{ number_format($total) }}</p>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-red-200 dark:border-red-800 shadow-sm p-5">
        <p class="text-xs font-medium text-red-500 dark:text-red-400 uppercase tracking-wider">Banned Users</p>
        <p class="text-2xl font-bold text-red-600 dark:text-red-400 mt-1">{{ number_format($banned) }}</p>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm p-5">
        <p class="text-xs font-medium text-textsecondary dark:text-gray-400 uppercase tracking-wider">New This Month</p>
        <p class="text-2xl font-bold text-textprimary dark:text-gray-100 mt-1">{{ number_format($newThisMonth) }}</p>
    </div>
</div>

<x-admin.card>
    <x-slot:header>
        <div class="flex items-center gap-3">
            <h3 class="font-heading font-semibold text-textprimary dark:text-gray-100">
                Users <span class="ml-2 px-2 py-0.5 text-xs font-medium rounded-full bg-gray-100 dark:bg-gray-700 text-textsecondary dark:text-gray-400">{{ $users->total() }}</span>
            </h3>
            @if($maxUsers && !auth()->user()->is_super_admin)
            @php $pct = min(100, round(($total / $maxUsers) * 100)); @endphp
            <span class="text-xs font-medium px-2 py-0.5 rounded-full {{ $pct >= 100 ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' : ($pct >= 80 ? 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400') }}">
                {{ $total }}/{{ $maxUsers }} users
            </span>
            @endif
        </div>
        <a href="{{ route('admin.users.create') }}"
           class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold bg-accent hover:bg-yellow-500 text-black rounded-xl transition-colors">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Add User
        </a>
    </x-slot:header>

    {{-- Filters --}}
    <form method="GET" action="{{ route('admin.users.index') }}"
          class="flex flex-wrap gap-3 px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name, email, phone…"
               class="flex-1 min-w-[180px] px-4 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700/50 text-textprimary dark:text-gray-100 placeholder-textsecondary dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary dark:focus:border-primary transition-all duration-200">
        <div class="relative">
            <select name="role" class="cs pl-3 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700/50 text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary dark:focus:border-primary transition-all duration-200 cursor-pointer">
                <option value="">All Roles</option>
                <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admin (full access)</option>
                <option value="staff" {{ request('role') === 'staff' ? 'selected' : '' }}>Staff</option>
            </select>
            <span class="pointer-events-none absolute inset-y-0 right-2.5 flex items-center">
                <svg class="w-4 h-4 text-textsecondary dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </span>
        </div>
        <div class="relative">
            <select name="status" class="cs pl-3 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700/50 text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary dark:focus:border-primary transition-all duration-200 cursor-pointer">
                <option value="">All Status</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="banned" {{ request('status') === 'banned' ? 'selected' : '' }}>Banned</option>
            </select>
            <span class="pointer-events-none absolute inset-y-0 right-2.5 flex items-center">
                <svg class="w-4 h-4 text-textsecondary dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </span>
        </div>
        <button type="submit" class="px-4 py-2.5 text-sm font-semibold bg-primary hover:bg-primary/90 text-white rounded-xl transition-colors">Filter</button>
        <a href="{{ route('admin.users.index') }}" class="px-4 py-2.5 text-sm font-medium border border-gray-200 dark:border-gray-600 text-textsecondary dark:text-gray-400 hover:text-textprimary dark:hover:text-gray-200 rounded-xl transition-colors">Clear</a>
    </form>

    {{-- Ban modal --}}
    <div x-data="{
            banModal: false,
            banUserId: null,
            banReason: '',
            loading: false,
            openBan(id) { this.banUserId = id; this.banReason = ''; this.banModal = true; },
            async submitBan() {
                this.loading = true;
                const res = await fetch('/admin/users/' + this.banUserId + '/ban', {
                    method: 'PATCH',
                    headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content},
                    body: JSON.stringify({ reason: this.banReason })
                });
                if (res.ok) window.location.reload();
                this.loading = false;
            }
         }">

        {{-- Ban modal overlay --}}
        <div x-show="banModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50">
            <div @click.away="banModal = false" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-xl p-6 w-full max-w-sm">
                <h4 class="font-semibold text-textprimary dark:text-gray-100 mb-1">Ban User</h4>
                <p class="text-sm text-textsecondary dark:text-gray-400 mb-4">Provide an optional reason for banning this user.</p>
                <textarea x-model="banReason" rows="3" placeholder="Reason (optional)…"
                          class="w-full px-4 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700/50 text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary dark:focus:border-primary transition-all duration-200 resize-none mb-4"></textarea>
                <div class="flex gap-3">
                    <button @click="banModal = false" type="button"
                            class="flex-1 py-2.5 text-sm font-semibold border border-gray-200 dark:border-gray-600 text-textsecondary dark:text-gray-400 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        Cancel
                    </button>
                    <button @click="submitBan()" :disabled="loading" type="button"
                            class="flex-1 py-2.5 text-sm font-semibold bg-red-600 hover:bg-red-700 text-white rounded-xl transition-colors active:scale-95">
                        <span x-show="!loading">Confirm Ban</span>
                        <span x-show="loading">Banning…</span>
                    </button>
                </div>
            </div>
        </div>

    <div class="overflow-x-auto">
        <table class="ep-stack w-full text-sm">
            <thead class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                <tr>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">User</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Phone</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Role</th>
                    <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Orders</th>
                    <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Spent</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Joined</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($users as $user)
                @php
                    $avatarColors = ['bg-blue-500','bg-purple-500','bg-green-500','bg-red-500','bg-yellow-500','bg-indigo-500','bg-pink-500','bg-teal-500'];
                    $avatarColor  = $avatarColors[ord(strtolower($user->name[0] ?? 'a')) % count($avatarColors)];
                @endphp
                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors {{ $user->is_banned ? 'bg-red-50/50 dark:bg-red-900/10' : '' }}">
                    <td class="px-6 py-3">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full {{ $avatarColor }} flex items-center justify-center text-white text-sm font-bold flex-shrink-0">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </div>
                            <div class="min-w-0">
                                <p class="font-medium text-textprimary dark:text-gray-100 truncate">{{ $user->name }}</p>
                                <p class="text-xs text-textsecondary dark:text-gray-400 truncate">{{ $user->email ?: $user->phone }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-sm text-textsecondary dark:text-gray-400">{{ $user->phone ?? '—' }}</td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-primary/10 text-primary dark:bg-primary/20 dark:text-blue-300">{{ $user->role_display_name }}</span>
                    </td>
                    <td class="px-4 py-3 text-center font-medium text-textprimary dark:text-gray-100">{{ $user->orders_count }}</td>
                    <td class="px-4 py-3 text-right font-medium text-textprimary dark:text-gray-100">৳{{ number_format($user->orders_sum_total ?? 0, 0) }}</td>
                    <td class="px-4 py-3">
                        @if($user->is_banned)
                            <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">Banned</span>
                        @else
                            <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">Active</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-xs text-textsecondary dark:text-gray-400">{{ $user->created_at->format('d M Y') }}</td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-1.5">
                            <a href="{{ route('admin.users.show', $user) }}"
                               class="px-2.5 py-1 text-xs font-medium bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors">View</a>
                            <a href="{{ route('admin.users.edit', $user) }}"
                               class="px-2.5 py-1 text-xs font-medium border border-gray-200 dark:border-gray-600 text-textsecondary dark:text-gray-400 hover:text-textprimary dark:hover:text-gray-200 rounded-lg transition-colors">Edit</a>
                            @if($user->is_banned)
                                <form method="POST" action="{{ route('admin.users.unban', $user) }}">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="px-2.5 py-1 text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 rounded-lg hover:bg-green-200 dark:hover:bg-green-900/50 transition-colors">Unban</button>
                                </form>
                            @else
                                <button @click="openBan({{ $user->id }})" type="button"
                                        class="px-2.5 py-1 text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 rounded-lg hover:bg-red-200 dark:hover:bg-red-900/50 transition-colors">Ban</button>
                            @endif
                            <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Delete this user?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="px-2.5 py-1 text-xs font-medium text-danger hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="px-6 py-12 text-center text-textsecondary dark:text-gray-400">No users found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($users->hasPages())
    <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
        {{ $users->links() }}
    </div>
    @endif

    </div>{{-- end x-data ban modal --}}
</x-admin.card>

@endsection
