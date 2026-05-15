@extends('layouts.admin')

@section('title', 'Settings')
@section('page-title', 'Settings')

@section('breadcrumb')
    <span class="text-textsecondary">/</span>
    <span class="text-textprimary font-medium">Settings</span>
@endsection

@section('content')

@if(session('success'))
    <div class="mb-5 flex items-center gap-3 p-3.5 rounded-xl bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800">
        <svg class="w-4 h-4 text-green-600 dark:text-green-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/></svg>
        <p class="text-sm text-green-700 dark:text-green-400">{{ session('success') }}</p>
    </div>
@endif

@php
$tabs = [
    'general'      => ['label' => 'General',     'icon' => 'M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 002.25 1.016 2.993 2.993 0 002.25-1.016 3.001 3.001 0 003.75.614m-16.5 0a3.004 3.004 0 01-.621-4.72L4.318 3.44A1.5 1.5 0 015.378 3h13.243a1.5 1.5 0 011.06.44l1.19 1.189a3 3 0 01-.621 4.72m-13.5 8.65h3.75a.75.75 0 00.75-.75V13.5a.75.75 0 00-.75-.75H6.75a.75.75 0 00-.75.75v3.75c0 .415.336.75.75.75z'],
    'order_rules'  => ['label' => 'Order Rules',  'icon' => 'M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007z'],
    'social'       => ['label' => 'Social',       'icon' => 'M18.375 12.739l-7.693 7.693a4.5 4.5 0 01-6.364-6.364l10.94-10.94A3 3 0 1119.5 7.372L8.552 18.32m.009-.01l-.01.01m5.699-9.941l-7.81 7.81a1.5 1.5 0 002.112 2.13'],
    'appearance'   => ['label' => 'Appearance',   'icon' => 'M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z'],
    'notification' => ['label' => 'Notifications', 'icon' => 'M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0'],
];
$activeTab = request('tab', 'general');
@endphp

<div class="flex flex-col lg:flex-row gap-6">

    <div class="lg:w-48 flex-shrink-0">
        <nav class="bg-white dark:bg-gray-800 rounded-2xl border border-border dark:border-gray-700 shadow-sm overflow-hidden">
            @foreach($tabs as $key => $tab)
            <a href="{{ request()->fullUrlWithQuery(['tab' => $key]) }}"
               class="flex items-center gap-3 px-4 py-3 text-sm font-medium transition-colors border-b border-border dark:border-gray-700 last:border-0 {{ $activeTab === $key ? 'bg-accent text-white' : 'text-textsecondary dark:text-gray-400 hover:text-textprimary dark:hover:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $tab['icon'] }}"/>
                </svg>
                {{ $tab['label'] }}
            </a>
            @endforeach
        </nav>
    </div>

    <div class="flex-1">
        <x-admin.card>
            <x-slot:header>
                <h3 class="font-heading font-semibold text-textprimary dark:text-gray-100">{{ $tabs[$activeTab]['label'] }} Settings</h3>
            </x-slot:header>

            <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data"
                  class="p-6 space-y-5">
                @csrf
                <input type="hidden" name="group" value="{{ $activeTab }}">

                @if($activeTab === 'general')
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-textsecondary dark:text-gray-400 mb-1.5">Store Name</label>
                        <input type="text" name="store_name" value="{{ $settings['store_name'] ?? '' }}"
                               class="w-full px-4 py-2.5 text-sm rounded-xl border border-border dark:border-gray-600 bg-white dark:bg-gray-700/50 text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary dark:focus:border-primary transition-all duration-200">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-textsecondary dark:text-gray-400 mb-1.5">Store Tagline</label>
                        <input type="text" name="store_tagline" value="{{ $settings['store_tagline'] ?? '' }}"
                               class="w-full px-4 py-2.5 text-sm rounded-xl border border-border dark:border-gray-600 bg-white dark:bg-gray-700/50 text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary dark:focus:border-primary transition-all duration-200">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-textsecondary dark:text-gray-400 mb-1.5">Store Phone</label>
                        <input type="text" name="store_phone" value="{{ $settings['store_phone'] ?? '' }}"
                               class="w-full px-4 py-2.5 text-sm rounded-xl border border-border dark:border-gray-600 bg-white dark:bg-gray-700/50 text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary dark:focus:border-primary transition-all duration-200">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-textsecondary dark:text-gray-400 mb-1.5">Store Email</label>
                        <input type="email" name="store_email" value="{{ $settings['store_email'] ?? '' }}"
                               class="w-full px-4 py-2.5 text-sm rounded-xl border border-border dark:border-gray-600 bg-white dark:bg-gray-700/50 text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary dark:focus:border-primary transition-all duration-200">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-medium text-textsecondary dark:text-gray-400 mb-1.5">Store Address</label>
                        <textarea name="store_address" rows="2"
                                  class="w-full px-4 py-2.5 text-sm rounded-xl border border-border dark:border-gray-600 bg-white dark:bg-gray-700/50 text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary dark:focus:border-primary transition-all duration-200 resize-none">{{ $settings['store_address'] ?? '' }}</textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-textsecondary dark:text-gray-400 mb-1.5">Currency Symbol</label>
                        <input type="text" name="currency_symbol" value="{{ $settings['currency_symbol'] ?? '৳' }}"
                               class="w-full px-4 py-2.5 text-sm rounded-xl border border-border dark:border-gray-600 bg-white dark:bg-gray-700/50 text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary dark:focus:border-primary transition-all duration-200">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-textsecondary dark:text-gray-400 mb-1.5">Timezone</label>
                        <select name="timezone" class="w-full px-4 py-2.5 text-sm rounded-xl border border-border dark:border-gray-600 bg-white dark:bg-gray-700/50 text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary dark:focus:border-primary transition-all duration-200">
                            <option value="Asia/Dhaka" {{ ($settings['timezone'] ?? 'Asia/Dhaka') === 'Asia/Dhaka' ? 'selected' : '' }}>Asia/Dhaka</option>
                            <option value="UTC" {{ ($settings['timezone'] ?? '') === 'UTC' ? 'selected' : '' }}>UTC</option>
                            <option value="Asia/Kolkata" {{ ($settings['timezone'] ?? '') === 'Asia/Kolkata' ? 'selected' : '' }}>Asia/Kolkata</option>
                        </select>
                    </div>
                </div>

                @elseif($activeTab === 'order_rules')
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @php
                    $orderFields = [
                        ['name' => 'order_max_amount',              'label' => 'Max Order Amount (৳)',        'default' => '5000'],
                        ['name' => 'order_limit_count',             'label' => 'Order Limit Per Customer',    'default' => '2'],
                        ['name' => 'order_limit_hours',             'label' => 'Limit Timeframe (Hours)',     'default' => '24'],
                        ['name' => 'min_order_amount',              'label' => 'Min Order Amount (৳)',        'default' => '0'],
                        ['name' => 'free_shipping_above',           'label' => 'Free Shipping Above (৳)',     'default' => '999'],
                        ['name' => 'default_shipping_charge',       'label' => 'Default Shipping Charge (৳)', 'default' => '60'],
                        ['name' => 'order_duplicate_block_minutes', 'label' => 'Duplicate Block (minutes)',   'default' => '60'],
                        ['name' => 'cod_cancel_block_count',        'label' => 'COD Cancel Block Count',      'default' => '3'],
                        ['name' => 'max_qty_per_product',           'label' => 'Max Qty Per Product',         'default' => '10'],
                        ['name' => 'auto_cancel_hours',             'label' => 'Auto-Cancel Hours',           'default' => '24'],
                    ];
                    @endphp
                    @foreach($orderFields as $field)
                    <div>
                        <label class="block text-xs font-medium text-textsecondary dark:text-gray-400 mb-1.5">{{ $field['label'] }}</label>
                        <input type="number" name="{{ $field['name'] }}" value="{{ $settings[$field['name']] ?? $field['default'] }}" min="0"
                               class="w-full px-4 py-2.5 text-sm rounded-xl border border-border dark:border-gray-600 bg-white dark:bg-gray-700/50 text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary dark:focus:border-primary transition-all duration-200">
                    </div>
                    @endforeach
                </div>

                @elseif($activeTab === 'social')
                <div class="space-y-4">
                    @php
                    $socialFields = [
                        ['name' => 'facebook_url',    'label' => 'Facebook URL',    'type' => 'url'],
                        ['name' => 'instagram_url',   'label' => 'Instagram URL',   'type' => 'url'],
                        ['name' => 'youtube_url',     'label' => 'YouTube URL',     'type' => 'url'],
                        ['name' => 'whatsapp_number', 'label' => 'WhatsApp Number', 'type' => 'text'],
                        ['name' => 'support_email',   'label' => 'Support Email',   'type' => 'email'],
                    ];
                    @endphp
                    @foreach($socialFields as $field)
                    <div>
                        <label class="block text-xs font-medium text-textsecondary dark:text-gray-400 mb-1.5">{{ $field['label'] }}</label>
                        <input type="{{ $field['type'] }}" name="{{ $field['name'] }}" value="{{ $settings[$field['name']] ?? '' }}"
                               class="w-full px-4 py-2.5 text-sm rounded-xl border border-border dark:border-gray-600 bg-white dark:bg-gray-700/50 text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary dark:focus:border-primary transition-all duration-200">
                    </div>
                    @endforeach
                </div>

                @elseif($activeTab === 'appearance')
                <div class="space-y-5">
                    <div>
                        <label class="block text-xs font-medium text-textsecondary dark:text-gray-400 mb-1.5">Site Logo</label>
                        @if($settings['site_logo'] ?? null)
                            <img src="{{ asset($settings['site_logo']) }}" alt="Logo" class="h-12 mb-2 rounded-xl">
                        @endif
                        <input type="file" name="site_logo" accept="image/*"
                               class="w-full text-sm text-textsecondary dark:text-gray-400 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-accent file:text-white hover:file:bg-yellow-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-textsecondary dark:text-gray-400 mb-1.5">Favicon</label>
                        @if($settings['favicon'] ?? null)
                            <img src="{{ asset($settings['favicon']) }}" alt="Favicon" class="h-8 mb-2 rounded-xl">
                        @endif
                        <input type="file" name="favicon" accept="image/*"
                               class="w-full text-sm text-textsecondary dark:text-gray-400 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-accent file:text-white hover:file:bg-yellow-500">
                    </div>
                </div>

                @elseif($activeTab === 'notification')
                <div class="space-y-3">
                    @php
                    $notifFields = [
                        ['name' => 'notify_admin_new_order',         'label' => 'Email admin on new order'],
                        ['name' => 'notify_customer_order_placed',   'label' => 'Email customer on order placed'],
                        ['name' => 'notify_customer_status_change',  'label' => 'Email customer on status change'],
                    ];
                    @endphp
                    @foreach($notifFields as $field)
                    <label class="flex items-center gap-3 cursor-pointer p-3.5 rounded-xl border border-border dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        <input type="hidden" name="{{ $field['name'] }}" value="0">
                        <input type="checkbox" name="{{ $field['name'] }}" value="1"
                               {{ ($settings[$field['name']] ?? '0') ? 'checked' : '' }}
                               class="w-4 h-4 rounded border-border dark:border-gray-600 text-accent focus:ring-primary/30">
                        <span class="text-sm font-medium text-textprimary dark:text-gray-100">{{ $field['label'] }}</span>
                    </label>
                    @endforeach
                </div>
                @endif

                <div class="pt-2">
                    <button type="submit"
                            class="px-6 py-2.5 font-semibold bg-accent hover:bg-yellow-500 text-white rounded-xl transition-colors">
                        Save {{ $tabs[$activeTab]['label'] }} Settings
                    </button>
                </div>
            </form>
        </x-admin.card>
    </div>
</div>

@endsection
