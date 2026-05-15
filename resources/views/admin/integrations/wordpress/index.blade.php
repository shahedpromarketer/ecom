@extends('layouts.admin')

@section('title', 'WordPress Sync')
@section('page-title', 'WordPress Integration')

@section('breadcrumb')
    <span class="text-textsecondary">/</span>
    <span class="text-textprimary font-medium">WordPress Sync</span>
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

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

    {{-- Left column: Webhook URL + Setup + Add Site --}}
    <div class="xl:col-span-1 space-y-5">

        {{-- Webhook Endpoint --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-border dark:border-gray-700 shadow-sm p-5">
            <h3 class="font-heading font-semibold text-textprimary dark:text-gray-100 mb-1">Webhook URL</h3>
            <p class="text-xs text-textsecondary dark:text-gray-400 mb-4">Use this URL in your WooCommerce webhook settings.</p>

            <div x-data="{ copied: false }" class="relative">
                <div class="flex items-center gap-2 bg-gray-50 dark:bg-gray-700/50 rounded-xl border border-border dark:border-gray-600 px-3 py-2.5">
                    <code class="text-xs text-primary dark:text-blue-400 flex-1 break-all select-all">{{ $webhookUrl }}</code>
                    <button type="button"
                            @click="navigator.clipboard.writeText('{{ $webhookUrl }}'); copied = true; setTimeout(() => copied = false, 2000)"
                            class="flex-shrink-0 p-1.5 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors text-textsecondary dark:text-gray-400 hover:text-textprimary dark:hover:text-gray-200">
                        <svg x-show="!copied" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                        <svg x-show="copied" x-cloak class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                        </svg>
                    </button>
                </div>
                <p x-show="copied" x-cloak class="text-xs text-green-600 dark:text-green-400 mt-1 text-right">Copied!</p>
            </div>

            <div class="mt-4 space-y-2 text-xs text-textsecondary dark:text-gray-400">
                <p class="font-semibold text-textprimary dark:text-gray-300">Request format:</p>
                <p>• Method: <code class="bg-gray-100 dark:bg-gray-700 px-1.5 rounded">POST</code></p>
                <p>• Header: <code class="bg-gray-100 dark:bg-gray-700 px-1.5 rounded">X-API-KEY: your_api_key</code></p>
                <p>• Body: WooCommerce order JSON payload</p>
            </div>
        </div>

        {{-- Setup Instructions --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-border dark:border-gray-700 shadow-sm p-5">
            <h3 class="font-heading font-semibold text-textprimary dark:text-gray-100 mb-3">Setup Guide</h3>
            <ol class="space-y-3 text-xs text-textsecondary dark:text-gray-400">
                <li class="flex gap-2.5">
                    <span class="w-5 h-5 rounded-full bg-primary text-white flex items-center justify-center font-bold flex-shrink-0 text-[10px]">1</span>
                    <span>Add a new site below and copy the generated API key.</span>
                </li>
                <li class="flex gap-2.5">
                    <span class="w-5 h-5 rounded-full bg-primary text-white flex items-center justify-center font-bold flex-shrink-0 text-[10px]">2</span>
                    <span>In WordPress, go to <strong class="text-textprimary dark:text-gray-300">WooCommerce → Settings → Advanced → Webhooks</strong>.</span>
                </li>
                <li class="flex gap-2.5">
                    <span class="w-5 h-5 rounded-full bg-primary text-white flex items-center justify-center font-bold flex-shrink-0 text-[10px]">3</span>
                    <span>Add a new webhook: Topic <strong class="text-textprimary dark:text-gray-300">Order Created</strong>, Delivery URL = the webhook URL above.</span>
                </li>
                <li class="flex gap-2.5">
                    <span class="w-5 h-5 rounded-full bg-primary text-white flex items-center justify-center font-bold flex-shrink-0 text-[10px]">4</span>
                    <span>Install a custom plugin or use WP hooks to add the <code class="bg-gray-100 dark:bg-gray-700 px-1.5 rounded">X-API-KEY</code> header to outgoing webhook requests.</span>
                </li>
                <li class="flex gap-2.5">
                    <span class="w-5 h-5 rounded-full bg-primary text-white flex items-center justify-center font-bold flex-shrink-0 text-[10px]">5</span>
                    <span>Test by creating a WooCommerce order — it should appear in your Orders list within seconds.</span>
                </li>
            </ol>
        </div>

        {{-- Add New Site --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-border dark:border-gray-700 shadow-sm p-5">
            <h3 class="font-heading font-semibold text-textprimary dark:text-gray-100 mb-4">Connect a Site</h3>

            <form method="POST" action="{{ route('admin.integrations.wordpress.store') }}" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-textsecondary dark:text-gray-400 mb-1.5">Site Name</label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                           placeholder="My WooCommerce Store"
                           class="w-full px-3 py-2.5 text-sm rounded-xl border @error('name') border-red-400 @else border-border dark:border-gray-600 @enderror bg-gray-50 dark:bg-gray-700/50 text-textprimary dark:text-gray-100 placeholder-textsecondary dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all">
                    @error('name') <p class="text-xs text-danger mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-medium text-textsecondary dark:text-gray-400 mb-1.5">Site URL</label>
                    <input type="url" name="site_url" value="{{ old('site_url') }}" required
                           placeholder="https://example.com"
                           class="w-full px-3 py-2.5 text-sm rounded-xl border @error('site_url') border-red-400 @else border-border dark:border-gray-600 @enderror bg-gray-50 dark:bg-gray-700/50 text-textprimary dark:text-gray-100 placeholder-textsecondary dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all">
                    @error('site_url') <p class="text-xs text-danger mt-1">{{ $message }}</p> @enderror
                </div>

                <button type="submit"
                        class="w-full py-2.5 text-sm font-semibold bg-primary hover:bg-primary/90 text-white rounded-xl transition-colors">
                    Connect Site &amp; Generate API Key
                </button>
            </form>
        </div>

    </div>

    {{-- Right column: Connected Sites + Sync Logs --}}
    <div class="xl:col-span-2 space-y-5">

        {{-- Connected Sites --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-border dark:border-gray-700 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-border dark:border-gray-700 flex items-center justify-between">
                <h3 class="font-heading font-semibold text-textprimary dark:text-gray-100">Connected Sites
                    <span class="ml-2 px-2 py-0.5 text-xs font-medium rounded-full bg-gray-100 dark:bg-gray-700 text-textsecondary dark:text-gray-400">{{ $sites->count() }}</span>
                </h3>
            </div>

            @if($sites->isEmpty())
            <div class="px-5 py-12 text-center">
                <svg class="w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244"/>
                </svg>
                <p class="text-sm text-textsecondary dark:text-gray-400">No sites connected yet.</p>
                <p class="text-xs text-textsecondary dark:text-gray-500 mt-1">Add a site using the form on the left.</p>
            </div>
            @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700/50 border-b border-border dark:border-gray-700">
                        <tr>
                            <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Site</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">API Key</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Synced</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Last Sync</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border dark:divide-gray-700">
                        @foreach($sites as $site)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors" x-data="{ key: '{{ $site->api_key }}', masked: true }">
                            <td class="px-5 py-3">
                                <p class="font-medium text-textprimary dark:text-gray-100 text-xs">{{ $site->name }}</p>
                                <a href="{{ $site->site_url }}" target="_blank" class="text-xs text-primary dark:text-blue-400 hover:underline truncate block max-w-[160px]">{{ $site->site_url }}</a>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-1.5">
                                    <code class="text-xs font-mono text-textsecondary dark:text-gray-400 bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded-lg">
                                        <span x-text="masked ? key.substring(0,8)+'••••••••••••••••' : key"></span>
                                    </code>
                                    <button type="button" @click="masked = !masked" class="text-textsecondary dark:text-gray-500 hover:text-textprimary dark:hover:text-gray-300 transition-colors">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path x-show="masked" stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                                            <path x-show="masked" stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path x-show="!masked" x-cloak stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/>
                                        </svg>
                                    </button>
                                    <button type="button"
                                            @click="navigator.clipboard.writeText(key)"
                                            class="text-textsecondary dark:text-gray-500 hover:text-primary dark:hover:text-blue-400 transition-colors"
                                            title="Copy key">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                @if($site->is_active)
                                    <span class="px-2 py-0.5 text-[10px] font-medium rounded-full bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">Active</span>
                                @else
                                    <span class="px-2 py-0.5 text-[10px] font-medium rounded-full bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">Inactive</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs text-textsecondary dark:text-gray-400">{{ number_format($site->total_synced) }}</td>
                            <td class="px-4 py-3 text-xs text-textsecondary dark:text-gray-400 whitespace-nowrap">
                                {{ $site->last_sync_at ? $site->last_sync_at->diffForHumans() : 'Never' }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <button type="button"
                                            @click="if(confirm('Regenerate API key for {{ addslashes($site->name) }}? The old key will stop working immediately.')) {
                                                fetch('{{ route('admin.integrations.wordpress.regenerate', $site) }}', {
                                                    method: 'POST',
                                                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' }
                                                }).then(r=>r.json()).then(d=>{ if(d.success) { key = d.api_key; masked = true; } });
                                            }"
                                            class="text-xs px-2 py-1 rounded-lg border border-border dark:border-gray-600 text-textsecondary dark:text-gray-400 hover:border-amber-400 hover:text-amber-600 dark:hover:text-amber-400 transition-colors whitespace-nowrap">
                                        Regenerate
                                    </button>
                                    <form method="POST" action="{{ route('admin.integrations.wordpress.destroy', $site) }}"
                                          onsubmit="return confirm('Delete {{ addslashes($site->name) }}? This will stop syncing orders from this site.')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-xs px-2 py-1 rounded-lg border border-red-200 dark:border-red-800 text-danger hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>

        {{-- Sync Logs --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-border dark:border-gray-700 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-border dark:border-gray-700">
                <h3 class="font-heading font-semibold text-textprimary dark:text-gray-100">Recent Sync Logs</h3>
                <p class="text-xs text-textsecondary dark:text-gray-400 mt-0.5">Last 50 sync attempts across all connected sites.</p>
            </div>

            @if($logs->isEmpty())
            <div class="px-5 py-10 text-center">
                <p class="text-sm text-textsecondary dark:text-gray-400">No sync attempts recorded yet.</p>
            </div>
            @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700/50 border-b border-border dark:border-gray-700">
                        <tr>
                            <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Site</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">WP Order</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Result</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Message</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Time</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border dark:divide-gray-700">
                        @foreach($logs as $log)
                        @php
                            $logBg = match($log->status) {
                                'success'   => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                                'failed'    => 'bg-red-100 text-danger dark:bg-red-900/30 dark:text-red-400',
                                'duplicate' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
                            };
                        @endphp
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors">
                            <td class="px-5 py-2.5 text-xs font-medium text-textprimary dark:text-gray-200">{{ $log->site?->name ?? '—' }}</td>
                            <td class="px-4 py-2.5 text-xs font-mono text-textsecondary dark:text-gray-400">#{{ $log->source_order_id }}</td>
                            <td class="px-4 py-2.5">
                                <span class="px-2 py-0.5 text-[10px] font-medium rounded-full {{ $logBg }}">{{ ucfirst($log->status) }}</span>
                                @if($log->order)
                                    <a href="{{ route('admin.orders.show', $log->order) }}" class="ml-1 text-[10px] text-primary dark:text-blue-400 hover:underline">{{ $log->order->order_number }}</a>
                                @endif
                            </td>
                            <td class="px-4 py-2.5 text-xs text-textsecondary dark:text-gray-400 max-w-[200px] truncate">{{ $log->message }}</td>
                            <td class="px-4 py-2.5 text-xs text-textsecondary dark:text-gray-400 whitespace-nowrap">{{ $log->created_at->diffForHumans() }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>

    </div>
</div>

@endsection
