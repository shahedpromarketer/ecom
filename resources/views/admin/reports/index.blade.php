@extends('layouts.admin')

@section('title', 'Reports')
@section('page-title', 'Reports')

@section('breadcrumb')
    <span class="text-textsecondary">/</span>
    <span class="text-textprimary font-medium">Reports</span>
@endsection

@section('content')

<div x-data="{ tab: 'products', loading: false, rows: [], from: '{{ now()->subDays(30)->toDateString() }}', to: '{{ now()->toDateString() }}' }">

<div class="flex flex-wrap gap-1 mb-6 bg-white dark:bg-gray-800 rounded-2xl border border-border dark:border-gray-700 shadow-sm p-1.5 w-fit">
    @foreach(['products' => 'Products', 'users' => 'Customers', 'stock' => 'Stock', 'payment' => 'Payments'] as $key => $label)
    <button @click="tab = '{{ $key }}'; loadReport()"
            :class="tab === '{{ $key }}' ? 'bg-accent text-white' : 'text-textsecondary dark:text-gray-400 hover:text-textprimary dark:hover:text-gray-200'"
            class="px-4 py-2 text-sm font-medium rounded-xl transition-colors">
        {{ $label }}
    </button>
    @endforeach
</div>

<div x-show="tab !== 'stock' && tab !== 'users'" class="flex flex-wrap items-center gap-3 mb-5">
    <div class="flex items-center gap-2">
        <label class="text-xs font-medium text-textsecondary dark:text-gray-400">From</label>
        <input type="date" x-model="from" @change="loadReport()"
               class="px-3 py-1.5 text-xs rounded-xl border border-border dark:border-gray-600 bg-white dark:bg-gray-700/50 text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary dark:focus:border-primary transition-all duration-200">
    </div>
    <div class="flex items-center gap-2">
        <label class="text-xs font-medium text-textsecondary dark:text-gray-400">To</label>
        <input type="date" x-model="to" @change="loadReport()"
               class="px-3 py-1.5 text-xs rounded-xl border border-border dark:border-gray-600 bg-white dark:bg-gray-700/50 text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary dark:focus:border-primary transition-all duration-200">
    </div>
</div>

<x-admin.card>
    <x-slot:header>
        <h3 class="font-heading font-semibold text-textprimary dark:text-gray-100" x-text="{ products: 'Product Report', users: 'Customer Report', stock: 'Stock Report', payment: 'Payment Report' }[tab]"></h3>
        <a :href="getExportUrl()" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold border border-border dark:border-gray-600 text-textprimary dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-xl transition-colors">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
            Export CSV
        </a>
    </x-slot:header>

    <div x-show="loading" class="flex items-center justify-center py-16">
        <svg class="animate-spin w-6 h-6 text-accent" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
    </div>

    <div x-show="!loading" class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="border-b border-border dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                <tr>
                    <template x-if="tab === 'products'">
                        <template x-for="h in ['Product','Category','Units Sold','Revenue']"><th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider" x-text="h"></th></template>
                    </template>
                    <template x-if="tab === 'users'">
                        <template x-for="h in ['Customer','Phone','Email','Orders','Total Spent','Last Order','Status']"><th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider" x-text="h"></th></template>
                    </template>
                    <template x-if="tab === 'stock'">
                        <template x-for="h in ['Product','Size','Color','SKU','Stock','Status']"><th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider" x-text="h"></th></template>
                    </template>
                    <template x-if="tab === 'payment'">
                        <template x-for="h in ['Order #','Customer','Amount','Method','Payment Status','Date']"><th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider" x-text="h"></th></template>
                    </template>
                </tr>
            </thead>
            <tbody class="divide-y divide-border dark:divide-gray-700">
                <template x-if="rows.length === 0 && !loading">
                    <tr><td colspan="10" class="px-6 py-12 text-center text-textsecondary dark:text-gray-400">No data for selected range</td></tr>
                </template>
                <template x-for="(row, i) in rows" :key="i">
                    <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors">
                        <template x-if="tab === 'products'">
                            <template x-for="val in [row.product, row.category, row.units_sold, '৳' + Number(row.revenue).toLocaleString()]">
                                <td class="px-4 py-3 text-sm text-textprimary dark:text-gray-100" x-text="val"></td>
                            </template>
                        </template>
                        <template x-if="tab === 'users'">
                            <template x-for="val in [row.name, row.phone, row.email, row.orders_count, '৳' + Number(row.total_spent).toLocaleString(), row.last_order ?? '—', row.status]">
                                <td class="px-4 py-3 text-sm text-textprimary dark:text-gray-100" x-text="val"></td>
                            </template>
                        </template>
                        <template x-if="tab === 'stock'">
                            <template x-for="val in [row.product, row.size, row.color, row.sku, row.stock, row.status]">
                                <td class="px-4 py-3 text-sm text-textprimary dark:text-gray-100" x-text="val"></td>
                            </template>
                        </template>
                        <template x-if="tab === 'payment'">
                            <template x-for="val in [row.order, row.customer, '৳' + Number(row.amount).toLocaleString(), row.payment_method, row.payment_status, row.date]">
                                <td class="px-4 py-3 text-sm text-textprimary dark:text-gray-100" x-text="val"></td>
                            </template>
                        </template>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>
</x-admin.card>

</div>

@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('reportsPage', () => ({
        tab: 'products',
        loading: false,
        rows: [],
        from: '{{ now()->subDays(30)->toDateString() }}',
        to: '{{ now()->toDateString() }}',

        init() { this.loadReport(); },

        loadReport() {
            this.loading = true;
            this.rows = [];
            const url = this.getApiUrl();
            fetch(url, { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content } })
                .then(r => r.json())
                .then(data => { this.rows = data; this.loading = false; })
                .catch(() => { this.loading = false; });
        },

        getApiUrl() {
            const map = { products: '/admin/reports/products', users: '/admin/reports/users', stock: '/admin/reports/stock', payment: '/admin/reports/payment' };
            const base = map[this.tab];
            if (this.tab !== 'stock' && this.tab !== 'users') {
                return `${base}?from=${this.from}&to=${this.to}`;
            }
            return base;
        },

        getExportUrl() {
            return this.getApiUrl() + (this.getApiUrl().includes('?') ? '&' : '?') + 'export=csv';
        },
    }));
});
</script>
@endpush
