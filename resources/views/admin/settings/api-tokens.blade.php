@extends('layouts.admin')

@section('title', 'API Access Token')

@section('page-title', 'API Access Token')

@section('breadcrumb')
    <span class="text-textsecondary">/</span>
    <span class="text-textprimary font-medium">API Access Token</span>
@endsection

@section('content')

<div class="max-w-2xl space-y-6">

    {{-- Token Card --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm p-6">

        <div class="flex items-center gap-3 mb-6">
            <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z"/>
                </svg>
            </div>
            <div>
                <h3 class="text-sm font-bold text-gray-900 dark:text-white">Plugin Access Token</h3>
                <p class="text-xs text-gray-400 mt-0.5">Add this token to your Ecom Pilot WordPress Plugin settings</p>
            </div>
        </div>

        {{-- Token display --}}
        <div class="space-y-1.5 mb-6">
            <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Access Token</label>
            <div class="flex items-center gap-2">
                <div class="flex-1 px-4 py-3 bg-gray-50 dark:bg-gray-700 rounded-xl border border-gray-200 dark:border-gray-600
                            font-mono text-sm text-gray-900 dark:text-white break-all select-all leading-relaxed">
                    {{ $token->token }}
                </div>
                <button onclick="copyText('{{ $token->token }}')"
                        title="Copy token"
                        class="flex-shrink-0 w-10 h-10 flex items-center justify-center rounded-xl
                               bg-primary text-white hover:bg-primary/90 active:scale-95 transition-all">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </button>
            </div>
        </div>

        {{-- Plugin Settings info --}}
        <div class="space-y-1.5">
            <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Plugin Settings</label>
            <div class="bg-gray-50 dark:bg-gray-700 rounded-xl p-4 space-y-3 border border-gray-200 dark:border-gray-600">

                <div class="flex items-center justify-between gap-3">
                    <span class="text-xs text-gray-500 dark:text-gray-400 flex-shrink-0 w-24">CRM URL:</span>
                    <div class="flex items-center gap-2 flex-1 min-w-0">
                        <code class="text-xs text-primary truncate flex-1">{{ config('app.url') }}</code>
                        <button onclick="copyText('{{ config('app.url') }}')"
                                title="Copy URL"
                                class="flex-shrink-0 w-6 h-6 flex items-center justify-center rounded-lg text-gray-400 hover:text-primary transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="border-t border-gray-200 dark:border-gray-600"></div>

                <div class="flex items-center justify-between gap-3">
                    <span class="text-xs text-gray-500 dark:text-gray-400 flex-shrink-0 w-24">API Endpoint:</span>
                    <div class="flex items-center gap-2 flex-1 min-w-0">
                        <code class="text-xs text-primary truncate flex-1">{{ config('app.url') }}/api/orders/receive</code>
                        <button onclick="copyText('{{ config('app.url') }}/api/orders/receive')"
                                title="Copy endpoint"
                                class="flex-shrink-0 w-6 h-6 flex items-center justify-center rounded-lg text-gray-400 hover:text-primary transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                        </button>
                    </div>
                </div>

            </div>
        </div>

        {{-- Footer: last used + regenerate --}}
        <div class="mt-6 pt-4 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between gap-4 flex-wrap">
            <p class="text-xs text-gray-400">
                Last used:
                <span class="font-medium text-gray-500 dark:text-gray-300">
                    {{ $token->last_used_at ? $token->last_used_at->diffForHumans() : 'Never' }}
                </span>
            </p>
            <button onclick="regenerateToken()"
                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl
                           border border-red-200 dark:border-red-800
                           text-red-600 dark:text-red-400 text-xs font-medium
                           hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/>
                </svg>
                Regenerate Token
            </button>
        </div>

    </div>

    {{-- How to Connect --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm p-6">
        <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-4">How to Connect</h3>
        <div class="space-y-3">
            @foreach([
                'Download and install Ecom Pilot Plugin on your WordPress site.',
                'Go to WordPress Admin → Ecom Pilot → Settings.',
                'Paste the CRM URL and Access Token shown above.',
                'Click "Test Connection" to verify the connection.',
                'Enable "Auto Sync Orders" to start receiving orders in this CRM.',
            ] as $i => $step)
            <div class="flex items-start gap-3">
                <span class="w-6 h-6 rounded-full bg-primary text-white text-xs font-bold flex items-center justify-center flex-shrink-0 mt-0.5">
                    {{ $i + 1 }}
                </span>
                <p class="text-sm text-gray-600 dark:text-gray-400">{{ $step }}</p>
            </div>
            @endforeach
        </div>
    </div>

</div>

@push('scripts')
<script>
    function copyText(text) {
        navigator.clipboard.writeText(text).then(() => {
            showToast('Copied to clipboard!', 'success');
        });
    }

    async function regenerateToken() {
        if (!confirm('Regenerate token? The old token will stop working immediately.')) return;

        const res = await fetch('{{ route('admin.settings.api-tokens.regenerate') }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                'Accept': 'application/json',
            },
        });

        const data = await res.json();
        if (data.success) {
            showToast('Token regenerated!', 'success');
            setTimeout(() => window.location.reload(), 800);
        }
    }
</script>
@endpush

@endsection
