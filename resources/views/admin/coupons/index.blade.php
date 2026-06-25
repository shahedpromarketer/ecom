@extends('layouts.admin')

@section('title', 'Coupons')
@section('page-title', 'Coupons')

@section('breadcrumb')
    <span class="text-textsecondary">/</span>
    <span class="text-textprimary font-medium">Coupons</span>
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

{{-- Stats Bar --}}
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-border dark:border-gray-700 shadow-sm p-5">
        <p class="text-xs font-medium text-textsecondary dark:text-gray-400 uppercase tracking-wider mb-1">Total Coupons</p>
        <p class="text-2xl font-bold text-textprimary dark:text-gray-100">{{ $stats['total'] }}</p>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-border dark:border-gray-700 shadow-sm p-5">
        <p class="text-xs font-medium text-textsecondary dark:text-gray-400 uppercase tracking-wider mb-1">Active</p>
        <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $stats['active'] }}</p>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-border dark:border-gray-700 shadow-sm p-5">
        <p class="text-xs font-medium text-textsecondary dark:text-gray-400 uppercase tracking-wider mb-1">Total Used</p>
        <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $stats['used'] }}</p>
    </div>
</div>

<x-admin.card>
    <x-slot:header>
        <h3 class="font-heading font-semibold text-textprimary dark:text-gray-100">
            All Coupons
            <span class="ml-2 px-2 py-0.5 text-xs font-medium rounded-full bg-gray-100 dark:bg-gray-700 text-textsecondary dark:text-gray-400">{{ $coupons->total() }}</span>
        </h3>
        <a href="{{ route('admin.coupons.create') }}"
           class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-semibold bg-primary hover:bg-primary/90 text-white rounded-xl transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Add Coupon
        </a>
    </x-slot:header>

    {{-- Filters --}}
    <form method="GET" action="{{ route('admin.coupons.index') }}"
          class="flex flex-wrap gap-3 px-6 py-4 border-b border-border dark:border-gray-700">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by code…"
               class="flex-1 min-w-[180px] px-4 py-2.5 text-sm rounded-xl border border-border dark:border-gray-600 bg-white dark:bg-gray-700/50 text-textprimary dark:text-gray-100 placeholder-textsecondary dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all duration-200">
        <div class="relative">
            <select name="type" class="cs pl-3 py-2.5 text-sm rounded-xl border border-border dark:border-gray-600 bg-white dark:bg-gray-700/50 text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all duration-200 cursor-pointer">
                <option value="">All Types</option>
                <option value="percent" {{ request('type') === 'percent' ? 'selected' : '' }}>Percent</option>
                <option value="fixed"   {{ request('type') === 'fixed'   ? 'selected' : '' }}>Fixed</option>
            </select>
            <span class="pointer-events-none absolute inset-y-0 right-2.5 flex items-center">
                <svg class="w-4 h-4 text-textsecondary dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </span>
        </div>
        <div class="relative">
            <select name="status" class="cs pl-3 py-2.5 text-sm rounded-xl border border-border dark:border-gray-600 bg-white dark:bg-gray-700/50 text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all duration-200 cursor-pointer">
                <option value="">All Statuses</option>
                <option value="active"   {{ request('status') === 'active'   ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
            </select>
            <span class="pointer-events-none absolute inset-y-0 right-2.5 flex items-center">
                <svg class="w-4 h-4 text-textsecondary dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </span>
        </div>
        <button type="submit"
                class="px-4 py-2.5 text-sm font-semibold bg-primary hover:bg-primary/90 text-white rounded-xl transition-colors">
            Filter
        </button>
        <a href="{{ route('admin.coupons.index') }}"
           class="px-4 py-2.5 text-sm font-medium border border-border dark:border-gray-600 text-textsecondary dark:text-gray-400 hover:text-textprimary dark:hover:text-gray-200 rounded-xl transition-colors">
            Clear
        </a>
    </form>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="border-b border-border dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                <tr>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Code</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Type</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Value</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Min Order</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Max Discount</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Used / Limit</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Expiry</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border dark:divide-gray-700">
                @forelse($coupons as $coupon)
                @php
                    $isExpired   = $coupon->expires_at && $coupon->expires_at->isPast();
                    $statusLabel = $isExpired ? 'Expired' : ($coupon->is_active ? 'Active' : 'Inactive');
                    $statusColor = $isExpired ? 'red' : ($coupon->is_active ? 'green' : 'gray');
                @endphp
                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors">

                    {{-- Code --}}
                    <td class="px-6 py-3">
                        <div class="flex items-center gap-2">
                            <span class="font-mono text-sm font-semibold tracking-widest px-2.5 py-1 rounded-lg bg-gray-100 dark:bg-gray-700 text-textprimary dark:text-gray-100">
                                {{ $coupon->code }}
                            </span>
                            <button type="button"
                                    onclick="navigator.clipboard.writeText('{{ $coupon->code }}').then(() => showCopyToast())"
                                    title="Copy code"
                                    class="p-1 rounded-lg text-textsecondary dark:text-gray-400 hover:text-primary hover:bg-primary/10 transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0013.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 01-.75.75H9a.75.75 0 01-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 01-2.25 2.25H6.75A2.25 2.25 0 014.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 011.927-.184"/>
                                </svg>
                            </button>
                        </div>
                    </td>

                    {{-- Type --}}
                    <td class="px-4 py-3">
                        @if($coupon->type === 'percent')
                            <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-full bg-violet-100 dark:bg-violet-900/30 text-violet-700 dark:text-violet-300">
                                Percent %
                            </span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-full bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300">
                                Fixed ৳
                            </span>
                        @endif
                    </td>

                    {{-- Value --}}
                    <td class="px-4 py-3 font-semibold text-textprimary dark:text-gray-100">
                        @if($coupon->type === 'percent')
                            {{ $coupon->value }}%
                        @else
                            ৳{{ number_format($coupon->value, 0) }}
                        @endif
                    </td>

                    {{-- Min Order --}}
                    <td class="px-4 py-3 text-textsecondary dark:text-gray-400">
                        {{ $coupon->min_order_amount ? '৳' . number_format($coupon->min_order_amount, 0) : '—' }}
                    </td>

                    {{-- Max Discount --}}
                    <td class="px-4 py-3 text-textsecondary dark:text-gray-400">
                        {{ $coupon->max_discount !== null ? '৳' . number_format($coupon->max_discount, 0) : '—' }}
                    </td>

                    {{-- Used / Limit --}}
                    <td class="px-4 py-3 text-textsecondary dark:text-gray-400">
                        <span class="font-medium text-textprimary dark:text-gray-200">{{ $coupon->used_count }}</span>
                        <span class="mx-0.5">/</span>
                        {{ $coupon->usage_limit ?? '∞' }}
                    </td>

                    {{-- Expiry --}}
                    <td class="px-4 py-3">
                        @if($coupon->expires_at)
                            <span class="{{ $isExpired ? 'text-red-600 dark:text-red-400 font-medium' : 'text-textsecondary dark:text-gray-400' }}">
                                {{ $coupon->expires_at->format('d M Y, h:i A') }}
                            </span>
                        @else
                            <span class="text-textsecondary dark:text-gray-400">Never</span>
                        @endif
                    </td>

                    {{-- Status toggle --}}
                    <td class="px-4 py-3">
                        @if($isExpired)
                            <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-full bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400">
                                Expired
                            </span>
                        @else
                            <div x-data="{ active: {{ $coupon->is_active ? 'true' : 'false' }} }">
                                <button
                                    @click="fetch('{{ route('admin.coupons.toggle', $coupon) }}', {
                                        method: 'PATCH',
                                        headers: {
                                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                            'Accept': 'application/json'
                                        }
                                    }).then(r => r.json()).then(d => active = d.is_active)"
                                    :class="active ? 'bg-green-500' : 'bg-gray-300 dark:bg-gray-600'"
                                    class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors focus:outline-none"
                                    type="button">
                                    <span
                                        :class="active ? 'translate-x-4' : 'translate-x-1'"
                                        class="inline-block h-4 w-4 transform bg-white rounded-full transition-transform shadow-sm">
                                    </span>
                                </button>
                            </div>
                        @endif
                    </td>

                    {{-- Actions --}}
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            <a href="{{ route('admin.coupons.edit', $coupon) }}"
                               class="px-2.5 py-1 text-xs font-medium bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors">
                                Edit
                            </a>
                            <form method="POST" action="{{ route('admin.coupons.destroy', $coupon) }}"
                                  onsubmit="return confirm('Delete coupon {{ $coupon->code }}? This cannot be undone.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="px-2.5 py-1 text-xs font-medium border border-red-200 dark:border-red-800 text-danger rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                                    Delete
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="px-6 py-14 text-center">
                        <svg class="w-10 h-10 mx-auto mb-3 text-border dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 14.25l6-6m4.5-3.493V21.75l-3.75-1.5-3.75 1.5-3.75-1.5-3.75 1.5V4.757c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0c1.1.128 1.907 1.077 1.907 2.185zM9.75 9h.008v.008H9.75V9zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm4.125 4.5h.008v.008h-.008V13.5zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/>
                        </svg>
                        <p class="text-sm font-medium text-textsecondary dark:text-gray-400">No coupons found.</p>
                        <a href="{{ route('admin.coupons.create') }}"
                           class="mt-2 inline-block text-xs text-primary hover:underline">
                            Create your first coupon
                        </a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($coupons->hasPages())
    <div class="px-6 py-4 border-t border-border dark:border-gray-700">
        {{ $coupons->links() }}
    </div>
    @endif

</x-admin.card>

{{-- Copy toast --}}
<div id="copy-toast"
     class="fixed bottom-6 right-6 z-50 flex items-center gap-2 px-4 py-2.5 rounded-xl bg-gray-900 dark:bg-gray-700 text-white text-sm shadow-lg opacity-0 translate-y-2 transition-all duration-300 pointer-events-none">
    <svg class="w-4 h-4 text-green-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/></svg>
    Copied to clipboard
</div>

@endsection

@push('scripts')
<script>
function showCopyToast() {
    const toast = document.getElementById('copy-toast');
    toast.classList.remove('opacity-0', 'translate-y-2');
    toast.classList.add('opacity-100', 'translate-y-0');
    setTimeout(() => {
        toast.classList.add('opacity-0', 'translate-y-2');
        toast.classList.remove('opacity-100', 'translate-y-0');
    }, 2000);
}
</script>
@endpush
