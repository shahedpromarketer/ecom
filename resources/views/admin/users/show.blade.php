@extends('layouts.admin')

@section('title', $user->name)
@section('page-title', 'User Detail')

@section('breadcrumb')
    <span class="text-textsecondary">/</span>
    <a href="{{ route('admin.users.index') }}" class="text-textsecondary hover:text-textprimary dark:hover:text-gray-200 transition-colors">Users</a>
    <span class="text-textsecondary">/</span>
    <span class="text-textprimary font-medium">{{ $user->name }}</span>
@endsection

@section('content')

@if(session('success'))
    <div class="mb-5 flex items-center gap-3 p-3.5 rounded-xl bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800">
        <svg class="w-4 h-4 text-green-600 dark:text-green-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/></svg>
        <p class="text-sm text-green-700 dark:text-green-400">{{ session('success') }}</p>
    </div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- Left: Order History --}}
    <div class="lg:col-span-2">
        <x-admin.card>
            <x-slot:header>
                <h3 class="font-heading font-semibold text-textprimary dark:text-gray-100">Order History</h3>
            </x-slot:header>
            <div class="overflow-x-auto">
                <table class="ep-stack w-full text-sm">
                    <thead class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Order #</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Items</th>
                            <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($user->orders as $order)
                        @php
                            $bg = match($order->status) {
                                'delivered','completed' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                                'cancelled','returned'  => 'bg-red-100 text-danger dark:bg-red-900/30 dark:text-red-400',
                                'processing','shipped'  => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                                default                 => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
                            };
                        @endphp
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors">
                            <td class="px-6 py-3 font-mono font-semibold text-textprimary dark:text-gray-100">
                                <a href="{{ route('admin.orders.show', $order) }}" class="hover:text-primary transition-colors">{{ $order->order_number }}</a>
                            </td>
                            <td class="px-4 py-3 text-textsecondary dark:text-gray-400">{{ $order->items->count() }} item(s)</td>
                            <td class="px-4 py-3 text-right font-semibold text-textprimary dark:text-gray-100">৳{{ number_format($order->total, 0) }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-0.5 text-xs font-medium rounded-full {{ $bg }}">{{ ucfirst($order->status) }}</span>
                            </td>
                            <td class="px-4 py-3 text-xs text-textsecondary dark:text-gray-400">{{ $order->created_at->format('d M Y') }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="px-6 py-12 text-center text-textsecondary dark:text-gray-400">No orders yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-admin.card>
    </div>

    {{-- Right: Info + Stats + Status --}}
    <div class="space-y-4">

        {{-- User Info --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm p-6">
            @php
                $avatarColors = ['bg-blue-500','bg-purple-500','bg-green-500','bg-red-500','bg-yellow-500','bg-indigo-500','bg-pink-500','bg-teal-500'];
                $avatarColor  = $avatarColors[ord(strtolower($user->name[0] ?? 'a')) % count($avatarColors)];
            @endphp
            <div class="flex items-center gap-4 mb-4">
                <div class="w-14 h-14 rounded-full {{ $avatarColor }} flex items-center justify-center text-white text-xl font-bold flex-shrink-0">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
                <div>
                    <h3 class="font-semibold text-textprimary dark:text-gray-100">{{ $user->name }}</h3>
                    <p class="text-sm text-textsecondary dark:text-gray-400">{{ $user->email }}</p>
                    <span class="mt-1 inline-block px-2 py-0.5 text-xs font-medium rounded-full bg-primary/10 text-primary dark:bg-primary/20 dark:text-blue-300">{{ $user->role_display_name }}</span>
                </div>
            </div>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between"><span class="text-textsecondary dark:text-gray-400">Phone</span><span class="text-textprimary dark:text-gray-100">{{ $user->phone ?? '—' }}</span></div>
                <div class="flex justify-between"><span class="text-textsecondary dark:text-gray-400">Joined</span><span class="text-textprimary dark:text-gray-100">{{ $user->created_at->format('d M Y') }}</span></div>
                @if($user->last_login_at)
                <div class="flex justify-between"><span class="text-textsecondary dark:text-gray-400">Last Login</span><span class="text-textprimary dark:text-gray-100">{{ $user->last_login_at->diffForHumans() }}</span></div>
                @endif
            </div>
            <a href="{{ route('admin.users.edit', $user) }}"
               class="mt-4 block text-center w-full py-2.5 text-sm font-semibold border border-gray-200 dark:border-gray-600 text-textprimary dark:text-gray-200 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                Edit Profile
            </a>
        </div>

        {{-- Stats --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm p-6">
            <h4 class="text-xs font-semibold uppercase tracking-wider text-textsecondary dark:text-gray-400 mb-3">Stats</h4>
            <div class="grid grid-cols-2 gap-4">
                <div class="text-center">
                    <p class="text-2xl font-bold text-textprimary dark:text-gray-100">{{ $user->orders_count }}</p>
                    <p class="text-xs text-textsecondary dark:text-gray-400">Orders</p>
                </div>
                <div class="text-center">
                    <p class="text-2xl font-bold text-textprimary dark:text-gray-100">৳{{ number_format($totalSpent, 0) }}</p>
                    <p class="text-xs text-textsecondary dark:text-gray-400">Total Spent</p>
                </div>
            </div>
        </div>

        {{-- Account Status --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm p-6"
             x-data="{
                banModal: false, banReason: '', loading: false,
                async submitBan() {
                    this.loading = true;
                    const res = await fetch('{{ route('admin.users.ban', $user) }}', {
                        method: 'PATCH',
                        headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content},
                        body: JSON.stringify({ reason: this.banReason })
                    });
                    if (res.ok) window.location.reload();
                    this.loading = false;
                }
             }">
            <h4 class="text-xs font-semibold uppercase tracking-wider text-textsecondary dark:text-gray-400 mb-3">Account Status</h4>

            @if($user->is_banned)
                <div class="mb-3 p-3 rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800">
                    <p class="text-sm font-medium text-red-700 dark:text-red-400">Banned</p>
                    @if($user->ban_reason)
                    <p class="text-xs text-red-600 dark:text-red-400 mt-0.5">{{ $user->ban_reason }}</p>
                    @endif
                </div>
                <form method="POST" action="{{ route('admin.users.unban', $user) }}">
                    @csrf @method('PATCH')
                    <button type="submit" class="w-full py-2.5 text-sm font-semibold bg-green-600 hover:bg-green-700 text-white rounded-xl transition-colors active:scale-95">Unban User</button>
                </form>
            @else
                <div class="mb-3 p-3 rounded-xl bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800">
                    <p class="text-sm font-medium text-green-700 dark:text-green-400">Active</p>
                    <p class="text-xs text-green-600 dark:text-green-400 mt-0.5">This user can place orders.</p>
                </div>
                <button @click="banModal = true" type="button"
                        class="w-full py-2.5 text-sm font-semibold bg-red-600 hover:bg-red-700 text-white rounded-xl transition-colors active:scale-95">
                    Ban User
                </button>
                <div x-show="banModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50">
                    <div @click.away="banModal = false" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-xl p-6 w-full max-w-sm">
                        <h4 class="font-semibold text-textprimary dark:text-gray-100 mb-4">Ban Reason</h4>
                        <textarea x-model="banReason" rows="3" placeholder="Reason (optional)…"
                                  class="w-full px-4 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700/50 text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30 resize-none mb-4"></textarea>
                        <div class="flex gap-3">
                            <button @click="banModal = false" class="flex-1 py-2.5 text-sm font-semibold border border-gray-200 dark:border-gray-600 text-textsecondary rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700">Cancel</button>
                            <button @click="submitBan()" :disabled="loading" class="flex-1 py-2.5 text-sm font-semibold bg-red-600 hover:bg-red-700 text-white rounded-xl active:scale-95">Confirm</button>
                        </div>
                    </div>
                </div>
            @endif
        </div>

    </div>
</div>

@endsection
