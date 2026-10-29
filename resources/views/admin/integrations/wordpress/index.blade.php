@extends('layouts.admin')

@section('title', 'WordPress / Landing Page Integration')
@section('page-title', 'Website & Landing Page Integration')

@section('breadcrumb')
    <span class="text-textsecondary">/</span>
    <span class="text-textprimary font-medium">WordPress Integration</span>
@endsection

@section('content')

{{-- ── Page Header ──────────────────────────────────────────────────────── --}}
<div x-data="{
    showModal: false,
    editMode: false,
    editId: null,
    submitting: false,
    showSecret: false,
    logoPreview: null,
    form: {
        site_name: '', site_url: '', type: 'wordpress',
        consumer_key: '', consumer_secret: '',
        mobile_number: '', invoice_title: '', invoice_address: '',
        logo: null
    },
    openAdd() {
        this.editMode  = false;
        this.editId    = null;
        this.showSecret = false;
        this.logoPreview = null;
        this.form = { site_name:'', site_url:'', type:'wordpress', consumer_key:'', consumer_secret:'', mobile_number:'', invoice_title:'', invoice_address:'', logo:null };
        this.showModal = true;
    },
    openEdit(site) {
        this.editMode  = true;
        this.editId    = site.id;
        this.showSecret = false;
        this.logoPreview = site.logo_url || null;
        this.form = {
            site_name: site.name, site_url: site.site_url, type: site.type || 'wordpress',
            consumer_key: site.consumer_key || '', consumer_secret: site.consumer_secret || '',
            mobile_number: site.mobile_number || '', invoice_title: site.invoice_title || '',
            invoice_address: site.invoice_address || '', logo: null
        };
        this.showModal = true;
    },
    handleLogo(event) {
        const file = event.target.files[0];
        if (!file) return;
        this.form.logo = file;
        const reader = new FileReader();
        reader.onload = e => this.logoPreview = e.target.result;
        reader.readAsDataURL(file);
    },
    async submitForm() {
        this.submitting = true;
        const fd = new FormData();
        Object.entries(this.form).forEach(([k, v]) => {
            if (v !== null && v !== undefined && v !== '') fd.append(k, v);
        });
        fd.append('_token', document.querySelector('meta[name=csrf-token]').content);
        if (this.editMode) fd.append('_method', 'PUT');
        const url = this.editMode
            ? '/admin/integrations/wordpress/' + this.editId
            : '/admin/integrations/wordpress';
        const res  = await fetch(url, { method: 'POST', body: fd });
        const data = await res.json();
        this.submitting = false;
        if (data.success) {
            this.showModal = false;
            wpToast(this.editMode ? 'Site updated!' : 'Site added!', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            wpToast(data.message || 'Failed to save.', 'error');
        }
    },
    async testModalConnection() {
        if (!this.editMode || !this.editId) { wpToast('Save the site first to test connection.', 'error'); return; }
        await testConnection(this.editId);
    }
}">

<div class="flex flex-wrap items-start justify-between gap-4 mb-6">
    <div>
        <h2 class="text-lg font-bold text-gray-900 dark:text-white">Website &amp; Landing Page Integration</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Connect your WordPress/WooCommerce sites to sync orders automatically.</p>
    </div>
    <button @click="openAdd()"
        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-primary text-white text-sm font-semibold hover:bg-primary/90 transition-colors shadow-sm">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
        </svg>
        Add New Site
    </button>
</div>

{{-- ── Info / Webhook Card ───────────────────────────────────────────────── --}}
<div class="mb-6 flex flex-wrap items-start gap-4 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-2xl">
    <svg class="w-5 h-5 text-blue-500 dark:text-blue-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/>
    </svg>
    <div class="flex-1 min-w-0">
        <p class="text-sm font-semibold text-blue-800 dark:text-blue-300">Connect a WooCommerce store in 3 steps</p>
        <ol class="mt-2 text-sm text-blue-800 dark:text-blue-300 list-decimal list-inside space-y-1">
            <li>Add the store below (its WordPress address, e.g. <code>https://shop.example.com</code>).</li>
            <li><a href="{{ asset('downloads/ecom-pilot-connector.zip') }}" class="font-semibold underline">Download the Ecom Pilot Connector plugin</a> and install it in WordPress (Plugins → Add New → Upload).</li>
            <li>In WordPress open <strong>WooCommerce → Ecom Pilot</strong>, paste the store's <strong>connection key</strong> (button "Copy connection key" below) and click Save.</li>
        </ol>
        <p class="mt-2 text-xs text-blue-600 dark:text-blue-400">New orders then arrive here within seconds, from any checkout (classic, block or headless). Status changes made here are sent back to WooCommerce. No WooCommerce API keys or webhooks needed.</p>
    </div>
</div>

{{-- ── Sites Table ───────────────────────────────────────────────────────── --}}
<div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm mb-6">
    <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
        <h3 class="text-sm font-bold text-gray-900 dark:text-white">Connected Sites
            <span class="ml-2 px-2 py-0.5 text-xs font-medium rounded-full bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400">{{ $sites->count() }}</span>
        </h3>
    </div>

    @if($sites->isEmpty())
    <div class="px-6 py-16 text-center">
        <div class="w-14 h-14 rounded-2xl bg-gray-100 dark:bg-gray-700 flex items-center justify-center mx-auto mb-3">
            <svg class="w-7 h-7 text-gray-300 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253"/>
            </svg>
        </div>
        <p class="text-sm font-semibold text-gray-500 dark:text-gray-400">No sites connected yet</p>
        <button @click="openAdd()"
            class="mt-3 inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-primary text-white text-xs font-semibold hover:bg-primary/90 transition">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Add Your First Site
        </button>
    </div>
    @else
    <div class="overflow-x-clip">
        <table class="w-full table-fixed">
            <thead class="bg-gray-50 dark:bg-gray-700/50 border-b border-gray-100 dark:border-gray-700">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide w-[28%]">Site</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide w-[12%]">Type</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide w-[22%]">Last Order Sync</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide w-[14%]">Last Product Sync</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide w-[10%]">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide w-[14%]">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50 dark:divide-gray-700/50">
                @foreach($sites as $site)
                @php
                    $typeBadge = match($site->type ?? 'wordpress') {
                        'woocommerce' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
                        'cartflow'    => 'bg-pink-100 text-pink-700 dark:bg-pink-900/30 dark:text-pink-400',
                        'landing_page'=> 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                        default       => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                    };
                    $typeLabel = match($site->type ?? 'wordpress') {
                        'woocommerce'  => 'WooCommerce',
                        'cartflow'     => 'CartFlow',
                        'landing_page' => 'Landing Page',
                        default        => 'WordPress',
                    };
                    $siteJson = json_encode([
                        'id'              => $site->id,
                        'name'            => $site->name,
                        'site_url'        => $site->site_url,
                        'type'            => $site->type ?? 'wordpress',
                        'consumer_key'    => $site->consumer_key,
                        'consumer_secret' => $site->consumer_secret,
                        'mobile_number'   => $site->mobile_number,
                        'invoice_title'   => $site->invoice_title,
                        'invoice_address' => $site->invoice_address,
                        'logo_url'        => $site->logo_url,
                    ]);
                @endphp
                <tr class="hover:bg-gray-50/80 dark:hover:bg-gray-700/20 transition-colors">

                    {{-- Site --}}
                    <td class="px-5 py-3">
                        <div class="flex items-center gap-3">
                            <img src="{{ $site->logo_url }}" alt="{{ $site->name }}"
                                 class="w-10 h-10 rounded-xl object-contain bg-gray-100 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 flex-shrink-0">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ $site->name }}</p>
                                <a href="{{ $site->site_url }}" target="_blank"
                                   class="text-xs text-primary dark:text-blue-400 hover:underline truncate block w-full">
                                    {{ $site->site_url }}
                                </a>
                                {{-- Plugin status badge --}}
                                @if($site->plugin_connected)
                                <span class="inline-flex items-center gap-1 mt-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">
                                    <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span>
                                    Plugin Connected
                                    @if($site->plugin_version) <span class="opacity-60">v{{ $site->plugin_version }}</span> @endif
                                </span>
                                @else
                                <span class="inline-flex items-center gap-1 mt-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">
                                    <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                    Plugin Not Connected
                                </span>
                                @endif
                                {{-- Connection key for the plugin: "{site id}.{secret}" --}}
                                @php $connKey = \App\Support\PluginSignature::connectionKey($site->id, (string) $site->api_key); @endphp
                                <div x-data="{ copied: false }" class="mt-1 flex items-center gap-1">
                                    <span class="text-[10px] text-gray-400 font-mono truncate max-w-[100px]">{{ substr($connKey, 0, 12) }}…</span>
                                    <button @click="navigator.clipboard.writeText('{{ $connKey }}'); copied = true; setTimeout(() => copied = false, 2000)"
                                        class="text-[10px] px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 hover:bg-primary/10 hover:text-primary transition"
                                        :title="copied ? 'Copied!' : 'Copy connection key for the plugin'">
                                        <span x-show="!copied">Copy connection key</span>
                                        <span x-show="copied" x-cloak class="text-green-600">Copied!</span>
                                    </button>
                                </div>
                                @if($site->last_seen_at)
                                    <div class="mt-0.5 text-[10px] text-gray-400">Plugin last heard {{ $site->last_seen_at->diffForHumans() }}</div>
                                @endif
                            </div>
                        </div>
                    </td>

                    {{-- Type --}}
                    <td class="px-4 py-3">
                        <span class="px-2.5 py-1 rounded-lg text-xs font-semibold {{ $typeBadge }}">{{ $typeLabel }}</span>
                    </td>

                    {{-- Last Order Sync --}}
                    <td class="px-4 py-3">
                        <div class="space-y-1">
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $site->last_order_sync_at ? $site->last_order_sync_at->diffForHumans() : 'Never' }}
                            </p>
                            <p class="text-xs text-gray-400">{{ number_format($site->total_synced) }} orders synced</p>
                            <button id="sync-orders-{{ $site->id }}"
                                onclick="syncOrders({{ $site->id }})"
                                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-primary/10 text-primary text-xs font-semibold hover:bg-primary/20 transition">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/>
                                </svg>
                                Sync Now
                            </button>
                            <div class="flex items-center gap-1 mt-0.5"
                                 x-data="{ open: false, days: '2', label: '2 Days' }">
                                {{-- Custom dropdown --}}
                                <div class="relative">
                                    <button type="button"
                                        @click="open = !open"
                                        @click.outside="open = false"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 text-orange-600 dark:text-orange-400 text-xs font-semibold hover:bg-orange-100 dark:hover:bg-orange-900/30 transition select-none">
                                        <span x-text="label"></span>
                                        <svg class="w-3 h-3 transition-transform duration-150" :class="open ? 'rotate-180' : ''"
                                             fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/>
                                        </svg>
                                    </button>
                                    <div x-show="open" x-cloak
                                         x-transition:enter="transition ease-out duration-100"
                                         x-transition:enter-start="opacity-0 scale-95"
                                         x-transition:enter-end="opacity-100 scale-100"
                                         x-transition:leave="transition ease-in duration-75"
                                         x-transition:leave-start="opacity-100 scale-100"
                                         x-transition:leave-end="opacity-0 scale-95"
                                         class="absolute left-0 top-full mt-1 z-30 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-lg overflow-hidden min-w-[90px]">
                                        <button type="button"
                                            @click="days = '1'; label = '1 Day'; open = false"
                                            class="w-full px-3 py-2 text-left text-xs font-medium text-gray-700 dark:text-gray-300 hover:bg-orange-50 dark:hover:bg-orange-900/20 hover:text-orange-600 dark:hover:text-orange-400 transition flex items-center gap-2">
                                            <span class="w-1.5 h-1.5 rounded-full bg-orange-400 flex-shrink-0" :class="days === '1' ? 'opacity-100' : 'opacity-0'"></span>
                                            1 Day
                                        </button>
                                        <button type="button"
                                            @click="days = '2'; label = '2 Days'; open = false"
                                            class="w-full px-3 py-2 text-left text-xs font-medium text-gray-700 dark:text-gray-300 hover:bg-orange-50 dark:hover:bg-orange-900/20 hover:text-orange-600 dark:hover:text-orange-400 transition flex items-center gap-2">
                                            <span class="w-1.5 h-1.5 rounded-full bg-orange-400 flex-shrink-0" :class="days === '2' ? 'opacity-100' : 'opacity-0'"></span>
                                            2 Days
                                        </button>
                                    </div>
                                </div>
                                {{-- Import button --}}
                                <button type="button"
                                    :id="'import-recent-{{ $site->id }}'"
                                    @click="importRecentOrders({{ $site->id }}, days)"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-orange-500 hover:bg-orange-600 text-white text-xs font-semibold transition shadow-sm active:scale-95">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                                    </svg>
                                    Import
                                </button>
                            </div>
                        </div>
                    </td>

                    {{-- Last Product Sync --}}
                    <td class="px-4 py-3">
                        <div class="space-y-1">
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $site->last_product_sync_at ? $site->last_product_sync_at->diffForHumans() : 'Never' }}
                            </p>
                            <button id="sync-products-{{ $site->id }}"
                                onclick="syncProducts({{ $site->id }})"
                                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-teal-50 text-teal-600 dark:bg-teal-900/20 dark:text-teal-400 text-xs font-semibold hover:bg-teal-100 transition">
                                Sync Now
                            </button>
                        </div>
                    </td>

                    {{-- Status toggle --}}
                    <td class="px-4 py-3">
                        <button onclick="toggleActive({{ $site->id }})"
                            class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors {{ $site->is_active ? 'bg-green-500' : 'bg-gray-300 dark:bg-gray-600' }}">
                            <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform {{ $site->is_active ? 'translate-x-6' : 'translate-x-1' }}"></span>
                        </button>
                        <p class="text-xs mt-1 {{ $site->is_active ? 'text-green-600 dark:text-green-400' : 'text-gray-400' }}">
                            {{ $site->is_active ? 'Active' : 'Inactive' }}
                        </p>
                    </td>

                    {{-- Actions --}}
                    <td class="px-4 py-3">
                        <div class="flex flex-wrap items-center gap-1.5">
                            <button onclick="testConnection({{ $site->id }})"
                                class="px-2.5 py-1.5 rounded-lg border border-gray-200 dark:border-gray-600 text-xs font-medium text-gray-600 dark:text-gray-300 hover:border-blue-400 hover:text-blue-600 transition whitespace-nowrap">
                                Test
                            </button>
                            <button onclick="forceConnect({{ $site->id }}, {{ $site->plugin_connected ? 'true' : 'false' }})"
                                id="fc-btn-{{ $site->id }}"
                                class="px-2.5 py-1.5 rounded-lg border text-xs font-medium transition whitespace-nowrap
                                    {{ $site->plugin_connected
                                        ? 'border-orange-200 dark:border-orange-800 text-orange-600 dark:text-orange-400 hover:bg-orange-50'
                                        : 'border-green-200 dark:border-green-800 text-green-600 dark:text-green-400 hover:bg-green-50' }}">
                                {{ $site->plugin_connected ? 'Disconnect' : 'Force Connect' }}
                            </button>
                            <button @click="openEdit({{ $siteJson }})"
                                class="px-2.5 py-1.5 rounded-lg border border-gray-200 dark:border-gray-600 text-xs font-medium text-gray-600 dark:text-gray-300 hover:border-primary hover:text-primary transition">
                                Edit
                            </button>
                            <button onclick="deleteSite({{ $site->id }}, '{{ addslashes($site->name) }}')"
                                class="px-2.5 py-1.5 rounded-lg border border-red-200 dark:border-red-800 text-xs font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition">
                                Delete
                            </button>
                        </div>
                    </td>

                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

{{-- ── Sync Log ──────────────────────────────────────────────────────────── --}}
<div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
        <h3 class="text-sm font-bold text-gray-900 dark:text-white">Recent Sync Logs</h3>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Last 10 sync attempts across all connected sites.</p>
    </div>

    @if($logs->isEmpty())
    <div class="px-6 py-10 text-center">
        <p class="text-sm text-gray-400 dark:text-gray-500">No sync attempts recorded yet.</p>
    </div>
    @else
    <div class="overflow-x-auto">
        <table class="w-full min-w-[500px]">
            <thead class="bg-gray-50 dark:bg-gray-700/50 border-b border-gray-100 dark:border-gray-700">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Site</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Orders</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Message</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Time</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50 dark:divide-gray-700/50">
                @foreach($logs as $log)
                @php
                    $logBadge = match($log->status) {
                        'success'   => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                        'failed'    => 'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400',
                        'duplicate' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
                        default     => 'bg-gray-100 text-gray-600',
                    };
                @endphp
                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/20 transition-colors">
                    <td class="px-5 py-2.5 text-xs font-medium text-gray-900 dark:text-gray-200">{{ $log->site?->name ?? '—' }}</td>
                    <td class="px-4 py-2.5">
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $logBadge }}">{{ ucfirst($log->status) }}</span>
                        @if($log->order)
                            <a href="{{ route('admin.orders.show', $log->order) }}" class="ml-1 text-[10px] text-primary dark:text-blue-400 hover:underline">{{ $log->order->order_number }}</a>
                        @endif
                    </td>
                    <td class="px-4 py-2.5 text-xs text-gray-500 dark:text-gray-400 font-mono">#{{ $log->source_order_id }}</td>
                    <td class="px-4 py-2.5 text-xs text-gray-500 dark:text-gray-400 max-w-[200px] truncate">{{ $log->message }}</td>
                    <td class="px-4 py-2.5 text-xs text-gray-400 dark:text-gray-500 whitespace-nowrap">{{ $log->created_at->diffForHumans() }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

{{-- ══════════════════════════════ ADD / EDIT MODAL ══════════════════════════════ --}}
<div x-show="showModal"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4"
     x-cloak>

    {{-- Backdrop --}}
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="showModal = false"></div>

    {{-- Panel --}}
    <div class="relative bg-white dark:bg-gray-800 rounded-t-3xl sm:rounded-2xl shadow-2xl
                w-full sm:max-w-lg max-h-[95vh] overflow-y-auto z-10">

        {{-- Header --}}
        <div class="sticky top-0 z-10 flex items-center justify-between px-6 py-4
                    border-b border-gray-100 dark:border-gray-700
                    bg-white dark:bg-gray-800 rounded-t-3xl sm:rounded-t-2xl">
            <h3 class="text-base font-bold text-gray-900 dark:text-white"
                x-text="editMode ? 'Edit Site' : 'Add New Site'"></h3>
            <button @click="showModal = false"
                class="w-8 h-8 flex items-center justify-center rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Form --}}
        <form @submit.prevent="submitForm()" class="p-6 space-y-5" enctype="multipart/form-data">

            {{-- Site Name --}}
            <div class="space-y-1.5">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Site Name <span class="text-red-500">*</span></label>
                <input type="text" x-model="form.site_name" required placeholder="e.g. My Main Store"
                    class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-600
                           bg-white dark:bg-gray-700 text-sm text-gray-900 dark:text-white placeholder-gray-400
                           focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition">
            </div>

            {{-- Site URL --}}
            <div class="space-y-1.5">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Website URL <span class="text-red-500">*</span></label>
                <input type="url" x-model="form.site_url" required placeholder="https://yoursite.com"
                    class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-600
                           bg-white dark:bg-gray-700 text-sm text-gray-900 dark:text-white placeholder-gray-400
                           focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition">
                <p class="text-xs text-gray-400">NB: WordPress Timezone must be Asia/Dhaka</p>
            </div>

            {{-- Type --}}
            <div class="space-y-1.5">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Type <span class="text-red-500">*</span></label>
                <select x-model="form.type"
                    class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-600
                           bg-white dark:bg-gray-700 text-sm text-gray-900 dark:text-white
                           focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition">
                    <option value="wordpress">WordPress</option>
                    <option value="woocommerce">WooCommerce</option>
                    <option value="cartflow">CartFlow</option>
                    <option value="landing_page">Landing Page</option>
                </select>
            </div>

            {{-- Consumer Key + Secret --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Consumer Key</label>
                    <input type="text" x-model="form.consumer_key" placeholder="ck_xxxxxxxxxx"
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-600
                               bg-white dark:bg-gray-700 text-sm text-gray-900 dark:text-white placeholder-gray-400
                               focus:outline-none focus:ring-2 focus:ring-primary/30 transition">
                </div>
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Consumer Secret</label>
                    <div class="relative">
                        <input :type="showSecret ? 'text' : 'password'" x-model="form.consumer_secret" placeholder="cs_xxxxxxxxxx"
                            class="w-full px-4 py-2.5 pr-10 rounded-xl border border-gray-200 dark:border-gray-600
                                   bg-white dark:bg-gray-700 text-sm text-gray-900 dark:text-white placeholder-gray-400
                                   focus:outline-none focus:ring-2 focus:ring-primary/30 transition">
                        <button type="button" @click="showSecret = !showSecret"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                            <svg x-show="!showSecret" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <svg x-show="showSecret" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Mobile Number --}}
            <div class="space-y-1.5">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Mobile Number</label>
                <input type="text" x-model="form.mobile_number" placeholder="01XXXXXXXXX"
                    class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-600
                           bg-white dark:bg-gray-700 text-sm text-gray-900 dark:text-white placeholder-gray-400
                           focus:outline-none focus:ring-2 focus:ring-primary/30 transition">
            </div>

            {{-- Invoice Title --}}
            <div class="space-y-1.5">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Title (For Invoice)</label>
                <input type="text" x-model="form.invoice_title" placeholder="Your Store Name"
                    class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-600
                           bg-white dark:bg-gray-700 text-sm text-gray-900 dark:text-white placeholder-gray-400
                           focus:outline-none focus:ring-2 focus:ring-primary/30 transition">
            </div>

            {{-- Invoice Address --}}
            <div class="space-y-1.5">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Address (For Invoice)</label>
                <textarea x-model="form.invoice_address" rows="2" placeholder="Your business address"
                    class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-600
                           bg-white dark:bg-gray-700 text-sm text-gray-900 dark:text-white placeholder-gray-400
                           focus:outline-none focus:ring-2 focus:ring-primary/30 transition resize-none"></textarea>
            </div>

            {{-- Logo --}}
            <div class="space-y-1.5">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Logo</label>
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 rounded-2xl border-2 border-dashed border-gray-200 dark:border-gray-600
                                overflow-hidden flex items-center justify-center bg-gray-50 dark:bg-gray-700 flex-shrink-0">
                        <img x-show="logoPreview" :src="logoPreview" class="w-full h-full object-contain">
                        <svg x-show="!logoPreview" class="w-8 h-8 text-gray-300 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/>
                        </svg>
                    </div>
                    <div>
                        <label class="cursor-pointer inline-flex items-center gap-2 px-4 py-2.5 rounded-xl
                                      border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700
                                      text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 transition">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/>
                            </svg>
                            Choose Logo
                            <input type="file" accept="image/*" class="hidden" @change="handleLogo($event)">
                        </label>
                        <p class="text-xs text-gray-400 mt-1">PNG, JPG max 1MB</p>
                    </div>
                </div>
            </div>

        </form>

        {{-- Footer --}}
        <div class="sticky bottom-0 z-10 px-6 py-4 border-t border-gray-100 dark:border-gray-700
                    bg-white dark:bg-gray-800 flex items-center justify-between">
            <button type="button" @click="testModalConnection()"
                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-600
                       text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.288 15.038a5.25 5.25 0 017.424 0M5.106 11.856c3.807-3.808 9.98-3.808 13.788 0M1.924 8.674c5.565-5.565 14.587-5.565 20.152 0M12.53 18.22l-.53.53-.53-.53a.75.75 0 011.06 0z"/>
                </svg>
                Test Connection
            </button>
            <div class="flex items-center gap-3">
                <button type="button" @click="showModal = false"
                    class="px-5 py-2.5 rounded-xl border border-gray-200 dark:border-gray-600
                           text-sm font-medium text-gray-700 dark:text-gray-300
                           hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                    Cancel
                </button>
                <button type="button" @click="submitForm()" :disabled="submitting"
                    class="px-5 py-2.5 rounded-xl bg-primary text-white text-sm font-semibold
                           hover:bg-primary/90 active:scale-95 transition shadow-sm
                           disabled:opacity-60 disabled:cursor-not-allowed">
                    <span x-show="!submitting" x-text="editMode ? 'Update Site' : 'Add Site'"></span>
                    <span x-show="submitting" x-cloak>Saving...</span>
                </button>
            </div>
        </div>

    </div>
</div>

</div>{{-- end x-data --}}

{{-- Toast --}}
<div id="wp-toast"
     class="fixed bottom-5 right-5 z-[60] flex items-center gap-2 px-4 py-2.5 rounded-xl shadow-lg text-sm font-medium text-white transition-all duration-300 opacity-0 translate-y-2 pointer-events-none"
     style="background:#22c55e">
    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
    </svg>
    <span id="wp-toast-msg">Done</span>
</div>

@push('scripts')
<script>
function wpToast(message, type = 'success') {
    const toast = document.getElementById('wp-toast');
    const msg   = document.getElementById('wp-toast-msg');
    if (!toast || !msg) return;
    toast.style.background = type === 'success' ? '#22c55e' : '#ef4444';
    msg.textContent = message;
    toast.classList.remove('opacity-0', 'translate-y-2');
    toast.classList.add('opacity-100', 'translate-y-0');
    setTimeout(() => {
        toast.classList.add('opacity-0', 'translate-y-2');
        toast.classList.remove('opacity-100', 'translate-y-0');
    }, 3500);
}

async function syncOrders(siteId) {
    const btn = document.getElementById('sync-orders-' + siteId);
    if (!btn) return;
    const orig = btn.innerHTML;
    btn.disabled = true;
    btn.textContent = 'Syncing...';
    try {
        const res  = await fetch('/admin/integrations/wordpress/' + siteId + '/sync-orders', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
        });
        const data = await res.json();
        if (data.success) {
            wpToast(data.synced + ' orders synced!', 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            wpToast(data.message || 'Sync failed', 'error');
        }
    } catch(e) {
        wpToast('Network error', 'error');
    } finally {
        btn.disabled  = false;
        btn.innerHTML = orig;
    }
}

async function syncProducts(siteId) {
    const btn = document.getElementById('sync-products-' + siteId);
    if (!btn) return;
    const orig = btn.innerHTML;
    btn.disabled = true;
    btn.textContent = 'Syncing...';
    try {
        const res  = await fetch('/admin/integrations/wordpress/' + siteId + '/sync-products', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
        });
        const data = await res.json();
        if (data.success) {
            wpToast(data.synced + ' products synced!', 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            wpToast(data.message || 'Sync failed', 'error');
        }
    } catch(e) {
        wpToast('Network error', 'error');
    } finally {
        btn.disabled  = false;
        btn.innerHTML = orig;
    }
}

async function importRecentOrders(siteId, days = '2') {
    const btn = document.getElementById('import-recent-' + siteId);
    if (!btn) return;
    const orig = btn.innerHTML;
    btn.disabled = true;
    btn.textContent = 'Importing...';
    try {
        const res  = await fetch('/admin/integrations/wordpress/' + siteId + '/import-recent-orders', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ days: days }),
        });
        const data = await res.json();
        if (data.success) {
            const msg = data.imported > 0
                ? data.imported + ' orders imported! (' + days + ' day' + (days > 1 ? 's' : '') + ')'
                : 'No new orders found (last ' + days + ' day' + (days > 1 ? 's' : '') + ')';
            wpToast(msg, 'success');
            if (data.imported > 0) setTimeout(() => location.reload(), 1500);
        } else {
            wpToast(data.message || 'Import failed', 'error');
        }
    } catch(e) {
        wpToast('Network error', 'error');
    } finally {
        btn.disabled  = false;
        btn.innerHTML = orig;
    }
}

async function testConnection(siteId) {
    wpToast('Testing connection...', 'success');
    try {
        const res  = await fetch('/admin/integrations/wordpress/' + siteId + '/test', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
        });
        const data = await res.json();
        if (data.connected) {
            wpToast('Connected successfully!', 'success');
        } else {
            wpToast('Connection failed: ' + (data.message || 'Unknown error'), 'error');
        }
    } catch(e) {
        wpToast('Network error', 'error');
    }
}

async function toggleActive(siteId) {
    const res  = await fetch('/admin/integrations/wordpress/' + siteId + '/toggle', {
        method: 'PATCH',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
    });
    const data = await res.json();
    if (data.success) {
        setTimeout(() => location.reload(), 300);
    }
}

async function forceConnect(siteId, isConnected) {
    const btn  = document.getElementById('fc-btn-' + siteId);
    const orig = btn ? btn.textContent : '';
    if (btn) { btn.disabled = true; btn.textContent = '...'; }
    try {
        const res  = await fetch('/admin/integrations/wordpress/' + siteId + '/force-connect', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
        });
        const data = await res.json();
        if (data.success) {
            wpToast(data.plugin_connected ? 'Plugin manually connected!' : 'Plugin disconnected.', 'success');
            setTimeout(() => location.reload(), 800);
        } else {
            wpToast('Failed', 'error');
        }
    } catch(e) {
        wpToast('Network error', 'error');
    } finally {
        if (btn) { btn.disabled = false; btn.textContent = orig; }
    }
}

async function deleteSite(siteId, siteName) {
    if (!confirm('Delete "' + siteName + '"? This cannot be undone.')) return;
    const res  = await fetch('/admin/integrations/wordpress/' + siteId, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
            'Accept': 'application/json'
        }
    });
    const data = await res.json();
    if (data.success) {
        wpToast('Site deleted', 'success');
        setTimeout(() => location.reload(), 1000);
    }
}
</script>
@endpush

@endsection
