@extends('layouts.admin')

@section('title', 'Returns')
@section('page-title', 'Returns')

@section('breadcrumb')
    <span class="text-textsecondary">/</span>
    <span class="text-textprimary font-medium">Returns</span>
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
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-border dark:border-gray-700 shadow-sm p-4">
        <p class="text-xs text-textsecondary dark:text-gray-400 mb-1">Total Returns</p>
        <p class="text-2xl font-bold text-textprimary dark:text-gray-100">{{ number_format($stats['total']) }}</p>
        <p class="text-xs text-textsecondary dark:text-gray-500 mt-0.5">All time</p>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-border dark:border-gray-700 shadow-sm p-4">
        <p class="text-xs text-textsecondary dark:text-gray-400 mb-1">Pending</p>
        <p class="text-2xl font-bold text-amber-600 dark:text-amber-400">{{ number_format($stats['pending']) }}</p>
        <p class="text-xs text-amber-500 mt-0.5">Awaiting review</p>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-border dark:border-gray-700 shadow-sm p-4">
        <p class="text-xs text-textsecondary dark:text-gray-400 mb-1">Approved</p>
        <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ number_format($stats['approved']) }}</p>
        <p class="text-xs text-green-500 mt-0.5">Refund in progress</p>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-border dark:border-gray-700 shadow-sm p-4">
        <p class="text-xs text-textsecondary dark:text-gray-400 mb-1">Completed</p>
        <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($stats['completed']) }}</p>
        <p class="text-xs text-blue-500 mt-0.5">Fully resolved</p>
    </div>
</div>

{{-- Table Card --}}
<div class="bg-white dark:bg-gray-800 rounded-2xl border border-border dark:border-gray-700 shadow-sm overflow-hidden">

    {{-- Filters bar --}}
    <div class="px-5 py-4 border-b border-border dark:border-gray-700 flex flex-wrap items-center gap-3">
        <h3 class="font-heading font-semibold text-textprimary dark:text-gray-100 flex-1">
            Return Requests
            <span class="ml-2 px-2 py-0.5 text-xs font-medium rounded-full bg-gray-100 dark:bg-gray-700 text-textsecondary dark:text-gray-400">{{ $returns->total() }}</span>
        </h3>

        <form method="GET" action="{{ route('admin.returns.index') }}" class="flex flex-wrap items-center gap-2">
            <input
                type="text"
                name="search"
                value="{{ request('search') }}"
                placeholder="Order# or customer name…"
                class="px-3 py-1.5 text-xs rounded-xl border border-border dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 text-textprimary dark:text-gray-100 placeholder-textsecondary dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary w-48"
            >
            <select
                name="status"
                class="px-3 py-1.5 text-xs rounded-xl border border-border dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary"
            >
                <option value="">All Statuses</option>
                <option value="requested"  {{ request('status') === 'requested'  ? 'selected' : '' }}>Requested</option>
                <option value="approved"   {{ request('status') === 'approved'   ? 'selected' : '' }}>Approved</option>
                <option value="rejected"   {{ request('status') === 'rejected'   ? 'selected' : '' }}>Rejected</option>
                <option value="completed"  {{ request('status') === 'completed'  ? 'selected' : '' }}>Completed</option>
            </select>
            <button type="submit" class="px-3 py-1.5 text-xs font-semibold bg-primary text-white rounded-xl hover:bg-primary/90 transition-colors">Filter</button>
            @if(request()->hasAny(['search', 'status']))
                <a href="{{ route('admin.returns.index') }}" class="px-3 py-1.5 text-xs font-medium border border-border dark:border-gray-600 text-textsecondary dark:text-gray-400 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">Clear</a>
            @endif
        </form>
    </div>

    {{-- Showing summary --}}
    @if($returns->total() > 0)
    <div class="px-5 py-2 text-xs text-textsecondary dark:text-gray-400 border-b border-border dark:border-gray-700 bg-gray-50/50 dark:bg-gray-700/20">
        Showing {{ $returns->firstItem() }} to {{ $returns->lastItem() }} of {{ number_format($returns->total()) }} entries
        @if(request()->hasAny(['search', 'status']))
            <span class="text-primary dark:text-blue-400">(filtered)</span>
        @endif
    </div>
    @endif

    {{-- Table --}}
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-700/50 border-b border-border dark:border-gray-700">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Return ID</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Order</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Reason</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Refund</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Requested</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border dark:divide-gray-700">
                @forelse($returns as $return)
                @php
                    $statusCls = match($return->status) {
                        'approved'  => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                        'rejected'  => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                        'completed' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                        default     => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
                    };
                @endphp
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">

                    {{-- Return ID --}}
                    <td class="px-4 py-3 align-top">
                        <span class="font-mono text-xs font-semibold text-textprimary dark:text-gray-200">#{{ $return->id }}</span>
                    </td>

                    {{-- Order --}}
                    <td class="px-4 py-3 align-top">
                        <a href="{{ route('admin.orders.show', $return->order_id) }}"
                           class="text-xs font-semibold text-primary dark:text-blue-400 hover:underline">{{ $return->order->order_number }}</a>
                        <p class="text-xs text-textsecondary dark:text-gray-400 mt-0.5">{{ $return->order->shipping_name }}</p>
                        <p class="text-[10px] text-textsecondary dark:text-gray-500">{{ $return->order->shipping_phone }}</p>
                    </td>

                    {{-- Reason --}}
                    <td class="px-4 py-3 align-top" style="max-width:260px">
                        <p class="text-xs text-textprimary dark:text-gray-200 leading-relaxed">{{ Str::limit($return->reason, 80) }}</p>
                        @if($return->admin_note)
                            <p class="text-[10px] text-textsecondary dark:text-gray-500 mt-1 italic">Note: {{ Str::limit($return->admin_note, 60) }}</p>
                        @endif
                    </td>

                    {{-- Status badge --}}
                    <td class="px-4 py-3 align-top">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $statusCls }}">
                            {{ ucfirst($return->status) }}
                        </span>
                    </td>

                    {{-- Refund --}}
                    <td class="px-4 py-3 align-top">
                        @if($return->refund_amount)
                            <p class="text-xs font-semibold text-textprimary dark:text-gray-200">৳{{ number_format($return->refund_amount, 0) }}</p>
                            @if($return->refund_method)
                                <p class="text-[10px] text-textsecondary dark:text-gray-500 uppercase mt-0.5">{{ $return->refund_method }}</p>
                            @endif
                        @else
                            <span class="text-xs text-textsecondary dark:text-gray-500">—</span>
                        @endif
                    </td>

                    {{-- Requested date --}}
                    <td class="px-4 py-3 align-top whitespace-nowrap">
                        <p class="text-xs text-textprimary dark:text-gray-200">{{ $return->requested_at ? \Carbon\Carbon::parse($return->requested_at)->format('d M Y') : '—' }}</p>
                        @if($return->resolved_at)
                            <p class="text-[10px] text-textsecondary dark:text-gray-500 mt-0.5">Resolved: {{ \Carbon\Carbon::parse($return->resolved_at)->format('d M Y') }}</p>
                        @endif
                    </td>

                    {{-- Actions --}}
                    <td class="px-4 py-3 align-top">
                        <div class="flex flex-wrap items-center gap-1.5">

                            @if($return->status === 'requested')
                                {{-- Approve --}}
                                <form method="POST" action="{{ route('admin.returns.update', $return) }}"
                                      onsubmit="return confirm('Approve this return request?')">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="action" value="approve">
                                    <button type="submit"
                                            class="px-2.5 py-1 text-[10px] font-semibold bg-green-500 hover:bg-green-600 text-white rounded-lg transition-colors whitespace-nowrap">
                                        Approve
                                    </button>
                                </form>

                                {{-- Reject --}}
                                <form method="POST" action="{{ route('admin.returns.update', $return) }}"
                                      onsubmit="return confirm('Reject this return request?')">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="action" value="reject">
                                    <button type="submit"
                                            class="px-2.5 py-1 text-[10px] font-semibold bg-red-500 hover:bg-red-600 text-white rounded-lg transition-colors whitespace-nowrap">
                                        Reject
                                    </button>
                                </form>
                            @endif

                            {{-- View Order --}}
                            <a href="{{ route('admin.orders.show', $return->order_id) }}"
                               class="px-2.5 py-1 text-[10px] font-semibold border border-border dark:border-gray-600 text-textsecondary dark:text-gray-400 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors whitespace-nowrap">
                                View Order
                            </a>

                        </div>
                    </td>

                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-16 text-center">
                        <svg class="w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3"/>
                        </svg>
                        <p class="text-sm font-medium text-textsecondary dark:text-gray-400">No return requests found</p>
                        <p class="text-xs text-textsecondary dark:text-gray-500 mt-1">Try adjusting your filters.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination footer --}}
    <div class="px-5 py-4 border-t border-border dark:border-gray-700 flex flex-wrap items-center justify-between gap-3">
        <p class="text-xs text-textsecondary dark:text-gray-400">
            @if($returns->total() > 0)
                Showing {{ $returns->firstItem() }} to {{ $returns->lastItem() }} of {{ number_format($returns->total()) }} entries
            @else
                No entries
            @endif
        </p>
        @if($returns->hasPages())
            {{ $returns->links() }}
        @endif
    </div>

</div>

@endsection
