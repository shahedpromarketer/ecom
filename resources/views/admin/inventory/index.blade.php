@extends('layouts.admin')

@section('title', 'Inventory Management')
@section('page-title', 'Inventory Management')

@section('breadcrumb')
    <span class="text-textsecondary">/</span>
    <span class="text-textprimary font-medium">Inventory</span>
@endsection

@section('content')

@if(session('success'))
<div class="mb-5 flex items-center gap-3 p-3.5 rounded-xl bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800">
    <svg class="w-4 h-4 text-green-600 dark:text-green-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/></svg>
    <p class="text-sm text-green-700 dark:text-green-400">{{ session('success') }}</p>
</div>
@endif

{{-- Stock is optional: only shops that enter stock here use it; nothing ever waits on it --}}
<div class="mb-5 p-3.5 rounded-2xl border border-border dark:border-gray-700 bg-white dark:bg-gray-800 text-xs text-textsecondary">
    <b class="text-textprimary dark:text-white">Stock is optional.</b>
    Enter your stock here if you want it counted: it goes down when an order is sent to the courier and comes back on cancel / return.
    Your website's stock is not used. Orders are never blocked by stock, so you can use Ecom Pilot without entering any.
</div>
{{-- Stats --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-border dark:border-gray-700 shadow-sm p-5 flex items-center gap-4">
        <div class="w-11 h-11 bg-blue-50 dark:bg-blue-900/20 rounded-xl flex items-center justify-center flex-shrink-0">
            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/></svg>
        </div>
        <div>
            <p class="text-xl font-bold text-textprimary dark:text-gray-100">{{ number_format($totalSkus) }}</p>
            <p class="text-xs text-textsecondary dark:text-gray-400">Total SKUs</p>
        </div>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-border dark:border-gray-700 shadow-sm p-5 flex items-center gap-4">
        <div class="w-11 h-11 bg-green-50 dark:bg-green-900/20 rounded-xl flex items-center justify-center flex-shrink-0">
            <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div>
            <p class="text-xl font-bold text-green-600 dark:text-green-400">{{ number_format($totalStock) }}</p>
            <p class="text-xs text-textsecondary dark:text-gray-400">Total Stock</p>
        </div>
    </div>
    <div x-data class="bg-white dark:bg-gray-800 rounded-2xl border border-border dark:border-gray-700 shadow-sm p-5 flex items-center gap-4 cursor-pointer"
         @click="$store.inv.filter='low'">
        <div class="w-11 h-11 bg-amber-50 dark:bg-amber-900/20 rounded-xl flex items-center justify-center flex-shrink-0">
            <svg class="w-5 h-5 text-amber-500 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
        </div>
        <div>
            <p class="text-xl font-bold text-amber-500 dark:text-amber-400">{{ number_format($lowStock) }}</p>
            <p class="text-xs text-textsecondary dark:text-gray-400">Low Stock (≤10)</p>
        </div>
    </div>
    <div x-data class="bg-white dark:bg-gray-800 rounded-2xl border border-border dark:border-gray-700 shadow-sm p-5 flex items-center gap-4 cursor-pointer"
         @click="$store.inv.filter='out'">
        <div class="w-11 h-11 bg-red-50 dark:bg-red-900/20 rounded-xl flex items-center justify-center flex-shrink-0">
            <svg class="w-5 h-5 text-red-500 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
        </div>
        <div>
            <p class="text-xl font-bold text-red-500 dark:text-red-400">{{ number_format($outOfStock) }}</p>
            <p class="text-xs text-textsecondary dark:text-gray-400">Out of Stock</p>
        </div>
    </div>
</div>

{{-- Table Card --}}
<div class="bg-white dark:bg-gray-800 rounded-2xl border border-border dark:border-gray-700 shadow-sm overflow-hidden">

    <div class="flex flex-wrap items-center justify-between gap-3 px-6 py-4 border-b border-border dark:border-gray-700">
        <h3 class="font-semibold text-textprimary dark:text-gray-100">Stock Management</h3>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.inventory.exportCsv') }}"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-xl border border-border dark:border-gray-600 text-textsecondary dark:text-gray-400 hover:text-textprimary dark:hover:text-gray-200 hover:border-gray-300 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                Export CSV
            </a>
            <div id="saveToast" class="hidden items-center gap-2 px-3 py-1.5 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 text-xs font-medium rounded-xl">
            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/></svg>
            Saved!
        </div>
        </div>{{-- end flex items-center gap-3 --}}
    </div>

    {{-- Filter tabs + Search (on the server, so any number of products stays fast) --}}
    @php
        $tab = fn ($key) => request()->fullUrlWithQuery(['filter' => $key === 'all' ? null : $key, 'page' => null]);
        $tabClass = fn ($key) => $filter === $key ? 'bg-primary text-white' : 'border border-border dark:border-gray-600 text-textsecondary dark:text-gray-400 hover:text-textprimary dark:hover:text-gray-200';
    @endphp
    <div class="flex flex-wrap items-center gap-2 px-4 sm:px-6 py-3 border-b border-border dark:border-gray-700">
        @foreach (['all' => 'All', 'in' => 'In Stock', 'low' => 'Low Stock', 'out' => 'Out of Stock'] as $key => $label)
            <a href="{{ $tab($key) }}" class="px-3 py-1.5 text-xs font-medium rounded-xl transition-colors {{ $tabClass($key) }}">{{ $label }}</a>
        @endforeach

        <form method="GET" class="w-full sm:w-auto sm:ml-auto">
            @if ($filter !== 'all')<input type="hidden" name="filter" value="{{ $filter }}">@endif
            <input type="search" name="q" value="{{ $search }}" placeholder="Search product or SKU…"
                   class="px-3 py-1.5 text-xs rounded-xl border border-border dark:border-gray-600 bg-white dark:bg-gray-700/50 text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30 transition-all w-full sm:w-52">
        </form>
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto">
        <table class="ep-stack w-full text-sm">
            <thead class="border-b border-border dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                <tr>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Product</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Size</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Color</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">SKU</th>
                    <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Stock</th>
                    <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Unit Price</th>
                    <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Stock Value</th>
                    <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                    <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border dark:divide-gray-700">
                @forelse($variants as $variant)
                @php
                    $unitPrice = (float)($variant->product?->sale_price ?? $variant->product?->price ?? 0)
                               + (float)($variant->extra_price ?? 0);
                @endphp
                <tr x-data="stockRow({{ $variant->id }}, {{ $variant->stock }}, '{{ addslashes($variant->product?->name ?? '') }}', '{{ addslashes($variant->sku ?? '') }}', {{ $unitPrice }})"
                    x-show="rowVisible()"
                    class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors">

                    <td class="px-6 py-3">
                        <div class="flex items-center gap-3">
                            @php $primaryImg = $variant->product?->primaryImage; @endphp
                            @if($primaryImg)
                                <img src="{{ $primaryImg->url }}" alt=""
                                     class="w-9 h-9 rounded-lg object-cover flex-shrink-0 border border-border dark:border-gray-600">
                            @else
                                <div class="w-9 h-9 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                </div>
                            @endif
                            <span class="font-medium text-textprimary dark:text-gray-100 truncate max-w-[180px]">{{ $variant->product?->name }}</span>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-textsecondary dark:text-gray-400">{{ $variant->size ?? '—' }}</td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-1.5">
                            @if($variant->color_hex)
                            <span class="w-3 h-3 rounded-full border border-border dark:border-gray-600" style="background-color:{{ $variant->color_hex }}"></span>
                            @endif
                            <span class="text-textsecondary dark:text-gray-400">{{ $variant->color ?? '—' }}</span>
                        </div>
                    </td>
                    <td class="px-4 py-3 font-mono text-xs text-textsecondary dark:text-gray-400">{{ $variant->sku ?? '—' }}</td>

                    <td class="px-4 py-3 text-center">
                        <span class="text-lg font-bold"
                              :class="stock===0?'text-red-500 dark:text-red-400':stock<=10?'text-amber-500 dark:text-amber-400':'text-textprimary dark:text-gray-100'"
                              x-text="stock"></span>
                    </td>

                    <td class="px-4 py-3 text-right text-sm text-textprimary dark:text-gray-100">
                        ৳<span x-text="price.toLocaleString('en-BD')"></span>
                    </td>
                    <td class="px-4 py-3 text-right text-sm font-medium text-textprimary dark:text-gray-100">
                        ৳<span x-text="(price * stock).toLocaleString('en-BD')"></span>
                    </td>

                    <td class="px-4 py-3 text-center">
                        <span x-show="stock===0" class="px-2 py-0.5 text-xs font-medium rounded-full bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400">Out of Stock</span>
                        <span x-show="stock>0&&stock<=10" class="px-2 py-0.5 text-xs font-medium rounded-full bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">Low Stock</span>
                        <span x-show="stock>10" class="px-2 py-0.5 text-xs font-medium rounded-full bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">In Stock</span>
                    </td>

                    <td class="px-4 py-3">
                        <div class="flex items-center justify-center gap-1.5">
                            <button @click="openAdjust('add')" title="Add Stock"
                                    class="w-7 h-7 rounded-lg bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400 hover:bg-green-100 flex items-center justify-center transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                            </button>
                            <button @click="openAdjust('remove')" title="Remove Stock"
                                    class="w-7 h-7 rounded-lg bg-red-50 dark:bg-red-900/20 text-red-500 dark:text-red-400 hover:bg-red-100 flex items-center justify-center transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12h-15"/></svg>
                            </button>
                            <button @click="openAdjust('return')" title="Return Stock"
                                    class="w-7 h-7 rounded-lg bg-blue-50 dark:bg-blue-900/20 text-blue-500 dark:text-blue-400 hover:bg-blue-100 flex items-center justify-center transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3"/></svg>
                            </button>
                            <button @click="openAdjust('set')" title="Set Stock"
                                    class="w-7 h-7 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 hover:bg-gray-200 flex items-center justify-center transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/></svg>
                            </button>
                            <button @click="loadLogs()" title="Stock History"
                                    class="w-7 h-7 rounded-lg bg-purple-50 dark:bg-purple-900/20 text-purple-500 dark:text-purple-400 hover:bg-purple-100 flex items-center justify-center transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </button>
                        </div>

                        {{-- Adjust Panel --}}
                        <div x-show="showAdjust" x-cloak class="mt-2 p-3 rounded-xl border border-border dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 space-y-2">
                            <p class="text-xs font-semibold text-textprimary dark:text-gray-200"
                               x-text="adjustType==='add'?'+ Add Stock':adjustType==='remove'?'− Remove Stock':adjustType==='return'?'↩ Return Stock':'= Set Stock'"></p>
                            <input type="number" x-model.number="adjustQty" min="1" placeholder="Quantity"
                                   class="w-full px-3 py-1.5 text-sm rounded-lg border border-border dark:border-gray-600 bg-white dark:bg-gray-700 text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30">
                            <input type="text" x-model="adjustNote" placeholder="Note (optional)"
                                   class="w-full px-3 py-1.5 text-sm rounded-lg border border-border dark:border-gray-600 bg-white dark:bg-gray-700 text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30">
                            <div class="flex gap-2">
                                <button @click="submitAdjust()" :disabled="saving||!adjustQty"
                                        class="flex-1 py-1.5 text-xs font-semibold bg-primary text-white rounded-lg hover:bg-primary/90 disabled:opacity-50 transition-colors">
                                    <span x-show="!saving">Confirm</span><span x-show="saving">Saving…</span>
                                </button>
                                <button @click="showAdjust=false"
                                        class="px-3 py-1.5 text-xs border border-border dark:border-gray-600 rounded-lg text-textsecondary hover:text-textprimary transition-colors">
                                    Cancel
                                </button>
                            </div>
                        </div>

                        {{-- Log Panel --}}
                        <div x-show="showLogs" x-cloak class="mt-2 p-3 rounded-xl border border-border dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 max-h-52 overflow-y-auto space-y-1.5">
                            <div class="flex items-center justify-between mb-1">
                                <p class="text-xs font-semibold text-textprimary dark:text-gray-200">Stock History</p>
                                <button @click="showLogs=false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                            <template x-if="logs.length===0">
                                <p class="text-xs text-textsecondary dark:text-gray-400 text-center py-2">No history yet.</p>
                            </template>
                            <template x-for="log in logs" :key="log.id">
                                <div class="flex items-start gap-2 text-xs py-1.5 border-b border-border dark:border-gray-600 last:border-0">
                                    <span class="font-bold flex-shrink-0 w-8 text-right"
                                          :class="log.change>0?'text-green-600 dark:text-green-400':'text-red-500 dark:text-red-400'"
                                          x-text="(log.change>0?'+':'')+log.change"></span>
                                    <div class="min-w-0 flex-1">
                                        <span class="text-textprimary dark:text-gray-200 capitalize font-medium" x-text="log.type.replace('_',' ')"></span>
                                        <template x-if="log.note">
                                            <span class="text-textsecondary dark:text-gray-400"> — <span x-text="log.note"></span></span>
                                        </template>
                                        <div class="text-gray-400 dark:text-gray-500 mt-0.5">
                                            <span x-text="log.user"></span> · <span x-text="log.date"></span>
                                        </div>
                                    </div>
                                    <span class="flex-shrink-0 text-textsecondary dark:text-gray-400"><span x-text="log.qty_before"></span>→<span x-text="log.qty_after"></span></span>
                                </div>
                            </template>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="px-6 py-16 text-center text-textsecondary dark:text-gray-400">No variants found.</td>
                </tr>
                @endforelse
            </tbody>
            <tfoot class="border-t-2 border-border dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50">
                <tr>
                    <td colspan="6" class="px-6 py-3 text-sm font-semibold text-textprimary dark:text-gray-100 text-right">
                        Grand Total Stock Value
                    </td>
                    <td colspan="3" class="px-4 py-3 text-right text-base font-bold text-primary dark:text-primary">
                        ৳{{ number_format($grandTotalValue, 2) }}
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
    @if ($variants->hasPages())
        <div class="px-4 sm:px-6 py-4 border-t border-border dark:border-gray-700">{{ $variants->links() }}</div>
    @endif
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.store('inv', {
        filter: @js($filter),
        search: '',
    });
});

const CSRF = document.querySelector('meta[name="csrf-token"]').content;

function stockRow(variantId, initialStock, productName, sku, price) {
    return {
        variantId,
        stock: initialStock,
        price: price || 0,
        productName: productName.toLowerCase(),
        sku: sku.toLowerCase(),
        showAdjust: false,
        showLogs: false,
        adjustType: 'add',
        adjustQty: null,
        adjustNote: '',
        saving: false,
        logs: [],

        rowVisible() {
            const store  = Alpine.store('inv');
            const filter = store.filter;
            const search = store.search.toLowerCase().trim();

            const matchFilter =
                filter === 'all' ||
                (filter === 'out' && this.stock === 0) ||
                (filter === 'low' && this.stock > 0 && this.stock <= 10) ||
                (filter === 'in'  && this.stock > 10);

            const matchSearch = !search ||
                this.productName.includes(search) ||
                this.sku.includes(search);

            return matchFilter && matchSearch;
        },

        openAdjust(type) {
            this.adjustType = type;
            this.adjustQty  = null;
            this.adjustNote = '';
            this.showLogs   = false;
            this.showAdjust = true;
        },

        submitAdjust() {
            if (!this.adjustQty || this.adjustQty < 1) return;
            this.saving = true;
            fetch(`/admin/inventory/variants/${this.variantId}/adjust`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    type: this.adjustType,
                    qty: this.adjustQty,
                    note: this.adjustNote,
                }),
            })
            .then(r => r.json())
            .then(data => {
                this.stock      = data.stock;
                this.showAdjust = false;
                this.saving     = false;
                const t = document.getElementById('saveToast');
                t.classList.remove('hidden');
                t.classList.add('flex');
                setTimeout(() => {
                    t.classList.add('hidden');
                    t.classList.remove('flex');
                }, 2500);
            })
            .catch(() => {
                this.saving = false;
                alert('Failed to update stock.');
            });
        },

        loadLogs() {
            this.showAdjust = false;
            this.showLogs   = true;
            fetch(`/admin/inventory/variants/${this.variantId}/logs`, {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
            })
            .then(r => r.json())
            .then(data => { this.logs = data; });
        },
    };
}
</script>
@endpush
