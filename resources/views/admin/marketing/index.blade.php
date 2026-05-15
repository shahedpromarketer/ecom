@extends('layouts.admin')

@section('title', 'Marketing & Tracking')
@section('page-title', 'Marketing & Tracking')

@section('breadcrumb')
    <span class="text-textsecondary">/</span>
    <span class="text-textprimary font-medium">Marketing & Pixels</span>
@endsection

@section('content')

@php
$platformConfig = [
    'facebook' => [
        'name'        => 'Facebook Pixel',
        'subtitle'    => 'Pixel & Conversions API',
        'icon_bg'     => 'bg-blue-50 dark:bg-blue-900/20',
        'icon_color'  => 'text-blue-600 dark:text-blue-400',
        'icon_vb'     => '0 0 24 24',
        'icon_path'   => '<path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>',
        'id_label'    => 'Pixel ID',
        'id_ph'       => 'e.g. 1234567890123456',
        'id_help'     => 'Found in Events Manager → Data Sources',
        'token_label' => 'Conversions API Access Token',
        'token_help'  => 'Generate in Events Manager → Settings → Conversions API',
        'has_token'   => true,
        'has_test'    => true,
        'has_info'    => false,
    ],
    'google_analytics' => [
        'name'        => 'Google Analytics 4',
        'subtitle'    => 'GA4 & Measurement Protocol',
        'icon_bg'     => 'bg-orange-50 dark:bg-orange-900/20',
        'icon_color'  => 'text-orange-500 dark:text-orange-400',
        'icon_vb'     => '0 0 24 24',
        'icon_path'   => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/>',
        'id_label'    => 'Measurement ID',
        'id_ph'       => 'G-XXXXXXXXXX',
        'id_help'     => 'Admin → Data Streams → Web Stream Details',
        'token_label' => 'Measurement Protocol API Secret',
        'token_help'  => 'Required for server-side event tracking. Admin → Data Streams → Measurement Protocol → Create',
        'has_token'   => true,
        'has_test'    => false,
        'has_info'    => false,
    ],
    'tiktok' => [
        'name'        => 'TikTok Pixel',
        'subtitle'    => 'Pixel & Events API',
        'icon_bg'     => 'bg-gray-900 dark:bg-gray-700',
        'icon_color'  => 'text-white',
        'icon_vb'     => '0 0 24 24',
        'icon_path'   => '<path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-2.88 2.5 2.89 2.89 0 01-2.89-2.89 2.89 2.89 0 012.89-2.89c.28 0 .54.04.79.1V9.01a6.33 6.33 0 00-.79-.05 6.34 6.34 0 00-6.34 6.34 6.34 6.34 0 006.34 6.34 6.34 6.34 0 006.33-6.34V8.95a8.28 8.28 0 004.84 1.54V7.04a4.85 4.85 0 01-1.07-.35z"/>',
        'id_label'    => 'Pixel ID',
        'id_ph'       => 'e.g. CXXXXXXXXXXXXXXX',
        'id_help'     => 'TikTok Ads Manager → Assets → Events → Web Events',
        'token_label' => 'Events API Access Token',
        'token_help'  => 'Events Manager → Manage → Settings → Generate Token',
        'has_token'   => true,
        'has_test'    => true,
        'has_info'    => false,
    ],
    'google_tag_manager' => [
        'name'        => 'Google Tag Manager',
        'subtitle'    => 'Container (Head + Body)',
        'icon_bg'     => 'bg-green-50 dark:bg-green-900/20',
        'icon_color'  => 'text-green-600 dark:text-green-400',
        'icon_vb'     => '0 0 24 24',
        'icon_path'   => '<path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z"/><path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z"/>',
        'id_label'    => 'GTM Container ID',
        'id_ph'       => 'GTM-XXXXXXX',
        'id_help'     => 'Found next to your container name in GTM dashboard',
        'token_label' => '',
        'token_help'  => '',
        'has_token'   => false,
        'has_test'    => false,
        'has_info'    => true,
    ],
    'microsoft_clarity' => [
        'name'        => 'Microsoft Clarity',
        'subtitle'    => 'Session Recording & Heatmaps',
        'icon_bg'     => 'bg-blue-50 dark:bg-blue-900/20',
        'icon_color'  => 'text-blue-500 dark:text-blue-400',
        'icon_vb'     => '0 0 24 24',
        'icon_path'   => '<path d="M11.4 24H0V12.6L11.4 24zM12.6 24H24V12.6L12.6 24zM24 11.4V0H12.6L24 11.4zM11.4 0H0v11.4L11.4 0z"/>',
        'id_label'    => 'Project ID',
        'id_ph'       => 'e.g. abcdefghij',
        'id_help'     => 'Clarity Dashboard → Settings → Overview → Project ID',
        'token_label' => '',
        'token_help'  => '',
        'has_token'   => false,
        'has_test'    => false,
        'has_info'    => false,
    ],
];
@endphp

{{-- Page intro --}}
<div class="flex items-start justify-between mb-6">
    <div>
        <p class="text-sm text-textsecondary dark:text-gray-400">
            Manage tracking pixels and conversion APIs. Active integrations are automatically injected into every storefront page.
        </p>
    </div>
</div>

{{-- Info alert --}}
<div class="flex gap-3 p-4 rounded-2xl bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 mb-6">
    <svg class="w-5 h-5 text-blue-500 dark:text-blue-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd"/>
    </svg>
    <p class="text-sm text-blue-700 dark:text-blue-300">
        Toggle each platform ON/OFF without losing saved credentials. Changes take effect immediately on the storefront.
    </p>
</div>

{{-- Platform cards --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    @foreach($platformConfig as $platform => $cfg)
    @php $px = $pixels[$platform] ?? null; @endphp

    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden"
         x-data="{
             saving: false,
             saved: false,
             showToken: false,
             active: {{ ($px && $px->is_active) ? 'true' : 'false' }},
             async save(form) {
                 this.saving = true;
                 this.saved = false;
                 const fd = new FormData(form);
                 try {
                     const res = await fetch('/admin/marketing/{{ $platform }}', {
                         method: 'POST',
                         headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                         body: fd
                     });
                     const data = await res.json();
                     if (data.success) {
                         this.saved = true;
                         setTimeout(() => { this.saved = false; }, 3000);
                     }
                 } catch (e) {}
                 this.saving = false;
             },
             async toggle() {
                 const prev = this.active;
                 this.active = !this.active;
                 try {
                     const res = await fetch('/admin/marketing/{{ $platform }}/toggle', {
                         method: 'PATCH',
                         headers: {
                             'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                             'Content-Type': 'application/json'
                         }
                     });
                     const data = await res.json();
                     if (data.success) {
                         this.active = data.is_active;
                     } else {
                         this.active = prev;
                     }
                 } catch (e) {
                     this.active = prev;
                 }
             }
         }">

        {{-- ── Card Header ── --}}
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
            <div class="flex items-center justify-between">
                {{-- Icon + name --}}
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 {{ $cfg['icon_bg'] }}">
                        <svg class="w-5 h-5 {{ $cfg['icon_color'] }}" viewBox="{{ $cfg['icon_vb'] }}" fill="currentColor">
                            {!! $cfg['icon_path'] !!}
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white leading-tight">{{ $cfg['name'] }}</h3>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ $cfg['subtitle'] }}</p>
                    </div>
                </div>
                {{-- Status badge + toggle --}}
                <div class="flex items-center gap-3 flex-shrink-0">
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-medium border transition-colors"
                          :class="active
                              ? 'bg-green-50 text-green-700 border-green-200 dark:bg-green-900/30 dark:text-green-400 dark:border-green-800'
                              : 'bg-gray-100 text-gray-500 border-gray-200 dark:bg-gray-700 dark:text-gray-400 dark:border-gray-600'"
                          x-text="active ? 'Active' : 'Inactive'">
                    </span>
                    <button type="button" @click="toggle()"
                            class="relative inline-flex h-5 w-10 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-primary/30"
                            :class="active ? 'bg-primary' : 'bg-gray-200 dark:bg-gray-600'">
                        <span class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow-sm transition-transform duration-200"
                              :class="active ? 'translate-x-5' : 'translate-x-0'">
                        </span>
                    </button>
                </div>
            </div>
        </div>

        {{-- ── Card Body ── --}}
        <form @submit.prevent="save($event.target)">
            <div class="p-6 space-y-5">
                @csrf

                {{-- Pixel / Measurement / Container / Project ID --}}
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ $cfg['id_label'] }}
                    </label>
                    <input type="text" name="pixel_id"
                           value="{{ $px?->pixel_id }}"
                           placeholder="{{ $cfg['id_ph'] }}"
                           class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700/50 text-gray-900 dark:text-white text-sm placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all duration-200">
                    <p class="text-xs text-gray-400 dark:text-gray-500">{{ $cfg['id_help'] }}</p>
                </div>

                @if($cfg['has_token'])
                {{-- API Secret / Access Token --}}
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ $cfg['token_label'] }}
                    </label>
                    <div class="relative">
                        <input :type="showToken ? 'text' : 'password'"
                               name="api_secret"
                               value="{{ $px?->api_secret }}"
                               placeholder="Paste your access token"
                               class="w-full px-4 py-2.5 pr-11 rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700/50 text-gray-900 dark:text-white text-sm placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all duration-200">
                        <button type="button" @click="showToken = !showToken"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                            <svg x-show="!showToken" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <svg x-show="showToken" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" x-cloak>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/>
                            </svg>
                        </button>
                    </div>
                    <p class="text-xs text-gray-400 dark:text-gray-500">{{ $cfg['token_help'] }}</p>
                </div>
                @endif

                @if($cfg['has_test'])
                {{-- Test Event Code --}}
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Test Event Code
                        <span class="text-xs font-normal text-gray-400 dark:text-gray-500 ml-1">(Optional)</span>
                    </label>
                    <input type="text" name="test_code"
                           value="{{ $px?->test_code }}"
                           placeholder="e.g. TEST12345"
                           class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700/50 text-gray-900 dark:text-white text-sm placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all duration-200">
                    <p class="text-xs text-gray-400 dark:text-gray-500">Use during testing only. Remove before going live.</p>
                </div>
                @endif

                @if($cfg['has_info'])
                {{-- GTM info notice --}}
                <div class="flex items-start gap-2.5 p-3.5 rounded-xl bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800">
                    <svg class="w-4 h-4 text-blue-500 dark:text-blue-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd"/>
                    </svg>
                    <p class="text-xs text-blue-700 dark:text-blue-300">
                        Both &lt;head&gt; script and &lt;body&gt; noscript will be automatically injected into every storefront page.
                    </p>
                </div>
                @endif

            </div>

            {{-- ── Card Footer ── --}}
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700/30 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between gap-4">
                <p class="text-xs text-gray-400 dark:text-gray-500 min-w-0">
                    @if($px?->pixel_id && $px?->updated_at)
                        Last saved: {{ $px->updated_at->diffForHumans() }}
                    @else
                        Not configured yet
                    @endif
                </p>
                <button type="submit"
                        :disabled="saving"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary hover:bg-primary/90 text-white text-sm font-semibold rounded-xl active:scale-95 transition-all duration-150 shadow-sm disabled:opacity-60 disabled:cursor-not-allowed flex-shrink-0">
                    <svg x-show="saving" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span x-text="saved ? '✓ Saved' : (saving ? 'Saving…' : 'Save Changes')"></span>
                </button>
            </div>
        </form>

    </div>
    @endforeach

</div>

@endsection
