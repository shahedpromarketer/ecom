@extends('layouts.admin')

@section('title', 'Courier Integration Setup')
@section('page-title', 'Courier Integration Setup')

@section('breadcrumb')
    <span class="text-textsecondary">/</span>
    <span class="text-textprimary font-medium">Courier Setup</span>
@endsection

@section('content')

@if(session('success'))
    <div class="mb-5 flex items-center gap-3 p-3.5 rounded-xl bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800">
        <svg class="w-4 h-4 text-green-600 dark:text-green-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/></svg>
        <p class="text-sm text-green-700 dark:text-green-400">{{ session('success') }}</p>
    </div>
@endif

@php
$courierMeta = [
    'pathao' => [
        'name'  => 'Pathao',
        'color' => 'bg-red-500',
        'fields' => [
            ['key' => 'api_key',    'label' => 'Client ID'],
            ['key' => 'api_secret', 'label' => 'Client Secret'],
            ['key' => 'username',   'label' => 'Username (Email)', 'source' => 'extra'],
            ['key' => 'password',   'label' => 'Password',         'source' => 'extra', 'type' => 'password'],
            ['key' => 'store_id',   'label' => 'Store ID',         'source' => 'extra'],
        ],
    ],
    'steadfast' => [
        'name'  => 'Steadfast',
        'color' => 'bg-green-600',
        'fields' => [
            ['key' => 'api_key',    'label' => 'API Key'],
            ['key' => 'api_secret', 'label' => 'Secret Key'],
        ],
    ],
    'carrybe' => [
        'name'  => 'CarryBe',
        'color' => 'bg-blue-600',
        'fields' => [
            ['key' => 'api_key',    'label' => 'API Key'],
            ['key' => 'api_secret', 'label' => 'API Secret'],
        ],
    ],
    'redx' => [
        'name'  => 'REDx',
        'color' => 'bg-red-700',
        'fields' => [
            ['key' => 'api_key', 'label' => 'API Key (Bearer Token)'],
        ],
    ],
    'paperfly' => [
        'name'  => 'Paperfly',
        'color' => 'bg-yellow-500',
        'fields' => [
            ['key' => 'api_key',    'label' => 'API Key'],
            ['key' => 'api_secret', 'label' => 'API Secret'],
        ],
    ],
];
@endphp

<div class="space-y-4" x-data="{ open: null }">
    @foreach($courierMeta as $courier => $meta)
    @php $setting = $settings[$courier] ?? null; @endphp
    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-border dark:border-gray-700 shadow-sm overflow-hidden">

        <button @click="open = (open === '{{ $courier }}') ? null : '{{ $courier }}'"
                class="w-full flex items-center gap-4 px-6 py-4 text-left hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors">
            <div class="w-10 h-10 {{ $meta['color'] }} rounded-2xl flex items-center justify-center flex-shrink-0">
                <span class="text-white text-xs font-bold">{{ substr($meta['name'], 0, 2) }}</span>
            </div>
            <div class="flex-1">
                <h3 class="font-heading font-semibold text-textprimary dark:text-gray-100">{{ $meta['name'] }}</h3>
                <div class="flex items-center gap-2 mt-0.5">
                    <span class="flex items-center gap-1 text-xs {{ $setting?->is_active ? 'text-green-600 dark:text-green-400' : 'text-textsecondary dark:text-gray-400' }}">
                        <span class="w-1.5 h-1.5 rounded-full {{ $setting?->is_active ? 'bg-green-500' : 'bg-gray-400 dark:bg-gray-500' }}"></span>
                        {{ $setting?->is_active ? 'Active' : 'Inactive' }}
                    </span>
                    @if($setting?->api_key)
                        <span class="text-xs text-green-600 dark:text-green-400">• Credentials set</span>
                    @else
                        <span class="text-xs text-textsecondary dark:text-gray-400">• No credentials</span>
                    @endif
                </div>
            </div>
            <svg class="w-5 h-5 text-textsecondary dark:text-gray-400 flex-shrink-0 transition-transform duration-200"
                 :class="{ 'rotate-180': open === '{{ $courier }}' }"
                 fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/>
            </svg>
        </button>

        <div x-show="open === '{{ $courier }}'" x-cloak
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0">
            <form method="POST" action="{{ route('admin.courier.update') }}"
                  class="px-6 pb-6 border-t border-border dark:border-gray-700 space-y-4">
                @csrf
                <input type="hidden" name="courier" value="{{ $courier }}">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-5">
                    @foreach($meta['fields'] as $field)
                    @php
                        $isExtra    = ($field['source'] ?? 'main') === 'extra';
                        $fieldName  = $isExtra ? "extra_config[{$field['key']}]" : $field['key'];
                        $fieldValue = $isExtra
                            ? ($setting?->extra_config[$field['key']] ?? '')
                            : ($setting?->{$field['key']} ?? '');
                        $inputType  = $field['type'] ?? 'text';
                    @endphp
                    <div>
                        <label class="block text-xs font-medium text-textsecondary dark:text-gray-400 mb-1.5">{{ $field['label'] }}</label>
                        <input type="{{ $inputType }}" name="{{ $fieldName }}" value="{{ $inputType === 'password' ? '' : $fieldValue }}"
                               placeholder="{{ $field['label'] }}"
                               class="w-full px-4 py-2.5 text-sm rounded-xl border border-border dark:border-gray-600 bg-white dark:bg-gray-700/50 text-textprimary dark:text-gray-100 placeholder-textsecondary dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary dark:focus:border-primary transition-all duration-200">
                        @if($inputType === 'password' && $fieldValue)
                            <p class="mt-1 text-[10px] text-green-600 dark:text-green-400">Password saved — leave blank to keep unchanged</p>
                        @endif
                    </div>
                    @endforeach

                    <div>
                        <label class="block text-xs font-medium text-textsecondary dark:text-gray-400 mb-1.5">Webhook URL (auto)</label>
                        <input type="text" value="{{ url('/webhook/' . $courier) }}" readonly
                               class="w-full px-4 py-2.5 text-sm rounded-xl border border-border dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 text-textsecondary dark:text-gray-400 cursor-not-allowed">
                    </div>
                </div>

                <div class="flex items-center justify-between pt-2">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" {{ $setting?->is_active ? 'checked' : '' }}
                               class="w-4 h-4 rounded border-border dark:border-gray-600 text-accent focus:ring-primary/30">
                        <span class="text-sm font-medium text-textprimary dark:text-gray-100">Enable {{ $meta['name'] }}</span>
                    </label>

                    <div class="flex items-center gap-3">
                        <button type="button"
                                @click="fetch('{{ route('admin.courier.test') }}', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }, body: JSON.stringify({ courier: '{{ $courier }}' }) }).then(r => r.json()).then(d => alert(d.message))"
                                class="px-3 py-2.5 text-xs font-medium border border-border dark:border-gray-600 text-textsecondary dark:text-gray-400 hover:text-textprimary dark:hover:text-gray-200 rounded-xl transition-colors">
                            Test Connection
                        </button>
                        <button type="submit"
                                class="px-4 py-2.5 text-xs font-semibold bg-accent hover:bg-yellow-500 text-black rounded-xl transition-colors">
                            Save Settings
                        </button>
                    </div>
                </div>
            </form>
        </div>

    </div>
    @endforeach
</div>

@endsection
