@extends('layouts.admin')

@section('title', 'API Tokens')

@section('page-title', 'API Tokens')

@section('breadcrumb')
    <span class="text-textsecondary">/</span>
    <span class="text-textprimary font-medium">API Tokens</span>
@endsection

@section('content')

{{-- Info Banner --}}
<div class="mb-6 p-4 rounded-2xl bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800">
    <div class="flex items-start gap-3">
        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
            <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/>
        </svg>
        <div class="min-w-0 flex-1">
            <p class="text-sm font-semibold text-blue-800 dark:text-blue-300 mb-1">Clothonic WordPress Plugin Integration</p>
            <p class="text-xs text-blue-700 dark:text-blue-400 mb-2">Use these tokens in your Clothonic WordPress plugin to push orders to this CRM.</p>
            <div class="space-y-1">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="text-xs font-medium text-blue-600 dark:text-blue-400 flex-shrink-0">Endpoint:</span>
                    <code class="text-xs bg-blue-100 dark:bg-blue-900/40 text-blue-800 dark:text-blue-300 px-2 py-0.5 rounded-lg font-mono break-all">{{ url('/api/orders/receive') }}</code>
                </div>
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="text-xs font-medium text-blue-600 dark:text-blue-400 flex-shrink-0">Header:</span>
                    <code class="text-xs bg-blue-100 dark:bg-blue-900/40 text-blue-800 dark:text-blue-300 px-2 py-0.5 rounded-lg font-mono">Authorization: Bearer {your_token}</code>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Generate Token Form --}}
<div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl mb-6">
    <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
        <h2 class="text-sm font-bold text-gray-900 dark:text-white">Generate New Token</h2>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Tokens are stored as SHA-256 hashes and shown only once.</p>
    </div>
    <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div class="space-y-1.5">
                <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Token Name <span class="text-red-500">*</span></label>
                <input type="text" id="token-name"
                       placeholder="e.g. My WordPress Store"
                       class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-600
                              bg-white dark:bg-gray-700 text-sm text-gray-900 dark:text-white
                              placeholder-gray-400 dark:placeholder-gray-500
                              focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary">
            </div>
            <div class="space-y-1.5">
                <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Expires At <span class="text-gray-400 font-normal normal-case">(Optional)</span></label>
                <input type="date" id="token-expires"
                       min="{{ date('Y-m-d', strtotime('+1 day')) }}"
                       class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-600
                              bg-white dark:bg-gray-700 text-sm text-gray-900 dark:text-white
                              focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary">
            </div>
        </div>
        <button type="button" onclick="generateToken()"
                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl
                       bg-primary text-white text-sm font-semibold
                       hover:bg-primary/90 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z"/>
            </svg>
            Generate Token
        </button>
    </div>
</div>

{{-- Tokens Table --}}
<div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
        <div>
            <h2 class="text-sm font-bold text-gray-900 dark:text-white">Active Tokens</h2>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $tokens->count() }} token{{ $tokens->count() !== 1 ? 's' : '' }}</p>
        </div>
    </div>

    @if($tokens->isEmpty())
        <div class="px-6 py-16 text-center">
            <div class="w-14 h-14 rounded-2xl bg-gray-100 dark:bg-gray-700 flex items-center justify-center mx-auto mb-3">
                <svg class="w-7 h-7 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z"/>
                </svg>
            </div>
            <p class="text-sm font-semibold text-gray-500 dark:text-gray-400">No tokens yet</p>
            <p class="text-xs text-gray-400 mt-1">Generate your first API token above.</p>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full min-w-[700px]">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-700/50 border-b border-gray-100 dark:border-gray-700">
                        <th class="px-5 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Name</th>
                        <th class="px-5 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Token</th>
                        <th class="px-5 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Last Used</th>
                        <th class="px-5 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Expires</th>
                        <th class="px-5 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Status</th>
                        <th class="px-5 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-gray-700/50">
                    @foreach($tokens as $token)
                    <tr class="hover:bg-gray-50/80 dark:hover:bg-gray-700/20 transition-colors" id="token-row-{{ $token->id }}">
                        <td class="px-5 py-3.5">
                            <div>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $token->name }}</p>
                                @if($token->creator)
                                    <p class="text-xs text-gray-400">by {{ $token->creator->name }}</p>
                                @endif
                            </div>
                        </td>
                        <td class="px-5 py-3.5">
                            <code class="text-xs font-mono bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 px-2 py-1 rounded-lg">
                                ••••••••••••••••{{ substr($token->token, -8) }}
                            </code>
                        </td>
                        <td class="px-5 py-3.5">
                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $token->last_used_at ? $token->last_used_at->diffForHumans() : 'Never' }}
                            </span>
                        </td>
                        <td class="px-5 py-3.5">
                            @if($token->expires_at)
                                <span class="text-xs {{ $token->expires_at->isPast() ? 'text-red-500 font-semibold' : 'text-gray-500 dark:text-gray-400' }}">
                                    {{ $token->expires_at->format('d M Y') }}
                                    @if($token->expires_at->isPast())
                                        <span class="ml-1 text-[10px] bg-red-100 text-red-600 px-1.5 py-0.5 rounded-md">Expired</span>
                                    @endif
                                </span>
                            @else
                                <span class="text-xs text-gray-400">Never</span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5">
                            <button type="button"
                                    onclick="toggleToken({{ $token->id }}, this)"
                                    data-active="{{ $token->is_active ? '1' : '0' }}"
                                    class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors
                                           {{ $token->is_active ? 'bg-primary' : 'bg-gray-300 dark:bg-gray-600' }}">
                                <span class="inline-block h-3.5 w-3.5 transform rounded-full bg-white shadow transition-transform
                                             {{ $token->is_active ? 'translate-x-4' : 'translate-x-0.5' }}"></span>
                            </button>
                            <span class="ml-2 text-xs {{ $token->is_active ? 'text-green-600 dark:text-green-400' : 'text-gray-400' }}" id="status-label-{{ $token->id }}">
                                {{ $token->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="px-5 py-3.5">
                            <button type="button"
                                    onclick="deleteToken({{ $token->id }}, '{{ addslashes($token->name) }}')"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl
                                           bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400
                                           text-xs font-semibold
                                           hover:bg-red-100 dark:hover:bg-red-900/40 transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
                                </svg>
                                Delete
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

{{-- Token Reveal Modal --}}
<div id="token-modal"
     class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60"
     onclick="handleModalOverlayClick(event)">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-lg border border-gray-200 dark:border-gray-700"
         onclick="event.stopPropagation()">

        <div class="p-6">
            {{-- Header --}}
            <div class="flex items-center gap-3 mb-5">
                <div class="w-10 h-10 rounded-xl bg-green-100 dark:bg-green-900/30 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">Token Generated!</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Copy this token now. It won't be shown again.</p>
                </div>
            </div>

            {{-- Token display --}}
            <div class="bg-gray-50 dark:bg-gray-700 rounded-xl p-4 border border-gray-200 dark:border-gray-600 mb-3">
                <p class="font-mono text-sm break-all text-gray-900 dark:text-white leading-relaxed" id="plain-token"></p>
            </div>

            {{-- Copy button --}}
            <button type="button" onclick="copyToken()"
                    class="w-full flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl
                           bg-primary text-white text-sm font-semibold
                           hover:bg-primary/90 transition-colors mb-3">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0013.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 01-.75.75H9a.75.75 0 01-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 01-2.25 2.25H6.75A2.25 2.25 0 014.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 011.927-.184"/>
                </svg>
                Copy Token
            </button>

            <p class="text-xs text-red-500 dark:text-red-400 text-center mb-3">
                ⚠️ Store this token safely. You cannot view it again.
            </p>

            <button type="button" onclick="closeTokenModal()"
                    class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-600
                           text-sm font-semibold text-gray-700 dark:text-gray-300
                           hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                I've copied the token
            </button>
        </div>
    </div>
</div>

{{-- Toast --}}
<div id="api-toast"
     class="fixed bottom-5 right-5 z-[9999] flex items-center gap-2 px-4 py-2.5 rounded-xl shadow-lg text-sm font-medium text-white transition-all duration-300 opacity-0 translate-y-2 pointer-events-none"
     style="background:#22c55e">
    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
    </svg>
    <span id="api-toast-msg">Done</span>
</div>

@push('scripts')
<script>
    const CSRF = document.querySelector('meta[name=csrf-token]').content;

    function showApiToast(message, type = 'success') {
        const toast = document.getElementById('api-toast');
        const msg   = document.getElementById('api-toast-msg');
        toast.style.background = type === 'success' ? '#22c55e' : '#ef4444';
        msg.textContent = message;
        toast.classList.remove('opacity-0', 'translate-y-2');
        toast.classList.add('opacity-100', 'translate-y-0');
        setTimeout(() => {
            toast.classList.add('opacity-0', 'translate-y-2');
            toast.classList.remove('opacity-100', 'translate-y-0');
        }, 3000);
    }

    async function generateToken() {
        const name       = document.getElementById('token-name').value.trim();
        const expires_at = document.getElementById('token-expires').value;

        if (!name) {
            showApiToast('Token name is required', 'error');
            document.getElementById('token-name').focus();
            return;
        }

        const res = await fetch('{{ route('admin.settings.api-tokens.store') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ name, expires_at: expires_at || null }),
        });

        if (!res.ok) {
            const err = await res.json().catch(() => ({}));
            showApiToast(err.message ?? 'Failed to generate token', 'error');
            return;
        }

        const data = await res.json();
        if (data.success) {
            document.getElementById('plain-token').textContent = data.token;
            document.getElementById('token-modal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            document.getElementById('token-name').value   = '';
            document.getElementById('token-expires').value = '';
            setTimeout(() => window.location.reload(), 200);
        }
    }

    function copyToken() {
        const token = document.getElementById('plain-token').textContent.trim();
        navigator.clipboard.writeText(token).then(() => {
            showApiToast('Token copied to clipboard!', 'success');
        });
    }

    function closeTokenModal() {
        document.getElementById('token-modal').classList.add('hidden');
        document.body.style.overflow = '';
    }

    function handleModalOverlayClick(e) {
        if (e.target === document.getElementById('token-modal')) {
            closeTokenModal();
        }
    }

    async function toggleToken(id, btn) {
        const res = await fetch(`/admin/settings/api-tokens/${id}/toggle`, {
            method: 'PATCH',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        });

        const data = await res.json();
        if (data.success) {
            const isActive = data.is_active;
            btn.dataset.active = isActive ? '1' : '0';
            btn.className = `relative inline-flex h-5 w-9 items-center rounded-full transition-colors ${isActive ? 'bg-primary' : 'bg-gray-300 dark:bg-gray-600'}`;
            const knob = btn.querySelector('span');
            knob.className = `inline-block h-3.5 w-3.5 transform rounded-full bg-white shadow transition-transform ${isActive ? 'translate-x-4' : 'translate-x-0.5'}`;
            const label = document.getElementById(`status-label-${id}`);
            if (label) {
                label.textContent = isActive ? 'Active' : 'Inactive';
                label.className = `ml-2 text-xs ${isActive ? 'text-green-600 dark:text-green-400' : 'text-gray-400'}`;
            }
        }
    }

    async function deleteToken(id, name) {
        if (!confirm(`Delete token "${name}"? This action cannot be undone.`)) return;

        const res = await fetch(`/admin/settings/api-tokens/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        });

        const data = await res.json();
        if (data.success) {
            const row = document.getElementById(`token-row-${id}`);
            if (row) row.remove();
            showApiToast('Token deleted successfully', 'success');
        }
    }
</script>
@endpush

@endsection
