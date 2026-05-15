@extends('layouts.admin')

@section('title', 'Invoice Print Setup')
@section('page-title', 'Invoice Print Setup')

@section('breadcrumb')
    <span class="text-textsecondary">/</span>
    <span class="text-textprimary font-medium">Invoice Setup</span>
@endsection

@section('content')

@if(session('success'))
    <div class="mb-5 flex items-center gap-3 p-3.5 rounded-xl bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800">
        <svg class="w-4 h-4 text-green-600 dark:text-green-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/></svg>
        <p class="text-sm text-green-700 dark:text-green-400">{{ session('success') }}</p>
    </div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6"
     x-data="{
        storeName:    '{{ addslashes($settings['store_name'] ?? 'Clothonic') }}',
        storeAddress: '{{ addslashes($settings['store_address'] ?? 'Dhaka, Bangladesh') }}',
        storePhone:   '{{ addslashes($settings['store_phone'] ?? '') }}',
        storeEmail:   '{{ addslashes($settings['store_email'] ?? '') }}',
        prefix:       '{{ addslashes($settings['invoice_prefix'] ?? 'CLT-INV') }}',
        startNumber:  '{{ $settings['invoice_start_number'] ?? '1001' }}',
        footerNote:   '{{ addslashes($settings['invoice_footer_note'] ?? 'Thank you for shopping with Clothonic!') }}',
        accentColor:  '{{ $settings['invoice_accent_color'] ?? '#1a3c5e' }}',
        paperSize:    '{{ $settings['invoice_paper_size'] ?? 'A4' }}',
        showLogo:     {{ ($settings['invoice_show_logo'] ?? '1') ? 'true' : 'false' }},
        showAddress:  {{ ($settings['invoice_show_address'] ?? '1') ? 'true' : 'false' }},
     }">

    <x-admin.card>
        <x-slot:header>
            <h3 class="font-heading font-semibold text-textprimary dark:text-gray-100">Invoice Settings</h3>
        </x-slot:header>
        <form method="POST" action="{{ route('admin.invoice-setup.update') }}" enctype="multipart/form-data"
              class="p-6 space-y-5">
            @csrf

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-textsecondary dark:text-gray-400 mb-1.5">Store Name</label>
                    <input type="text" name="store_name" x-model="storeName" value="{{ $settings['store_name'] ?? '' }}"
                           class="w-full px-4 py-2.5 text-sm rounded-xl border border-border dark:border-gray-600 bg-white dark:bg-gray-700/50 text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary dark:focus:border-primary transition-all duration-200">
                </div>
                <div>
                    <label class="block text-xs font-medium text-textsecondary dark:text-gray-400 mb-1.5">Store Phone</label>
                    <input type="text" name="store_phone" x-model="storePhone" value="{{ $settings['store_phone'] ?? '' }}"
                           class="w-full px-4 py-2.5 text-sm rounded-xl border border-border dark:border-gray-600 bg-white dark:bg-gray-700/50 text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary dark:focus:border-primary transition-all duration-200">
                </div>
            </div>

            <div>
                <label class="block text-xs font-medium text-textsecondary dark:text-gray-400 mb-1.5">Store Email</label>
                <input type="email" name="store_email" x-model="storeEmail" value="{{ $settings['store_email'] ?? '' }}"
                       class="w-full px-4 py-2.5 text-sm rounded-xl border border-border dark:border-gray-600 bg-white dark:bg-gray-700/50 text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary dark:focus:border-primary transition-all duration-200">
            </div>

            <div>
                <label class="block text-xs font-medium text-textsecondary dark:text-gray-400 mb-1.5">Store Address</label>
                <textarea name="store_address" x-model="storeAddress" rows="2"
                          class="w-full px-4 py-2.5 text-sm rounded-xl border border-border dark:border-gray-600 bg-white dark:bg-gray-700/50 text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary dark:focus:border-primary transition-all duration-200 resize-none">{{ $settings['store_address'] ?? '' }}</textarea>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-textsecondary dark:text-gray-400 mb-1.5">Invoice Prefix</label>
                    <input type="text" name="invoice_prefix" x-model="prefix" value="{{ $settings['invoice_prefix'] ?? 'CLT-INV' }}"
                           class="w-full px-4 py-2.5 text-sm rounded-xl border border-border dark:border-gray-600 bg-white dark:bg-gray-700/50 text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary dark:focus:border-primary transition-all duration-200">
                </div>
                <div>
                    <label class="block text-xs font-medium text-textsecondary dark:text-gray-400 mb-1.5">Starting Number</label>
                    <input type="number" name="invoice_start_number" x-model="startNumber" value="{{ $settings['invoice_start_number'] ?? '1001' }}"
                           class="w-full px-4 py-2.5 text-sm rounded-xl border border-border dark:border-gray-600 bg-white dark:bg-gray-700/50 text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary dark:focus:border-primary transition-all duration-200">
                </div>
            </div>

            <div>
                <label class="block text-xs font-medium text-textsecondary dark:text-gray-400 mb-1.5">Footer Note</label>
                <textarea name="invoice_footer_note" x-model="footerNote" rows="2"
                          class="w-full px-4 py-2.5 text-sm rounded-xl border border-border dark:border-gray-600 bg-white dark:bg-gray-700/50 text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary dark:focus:border-primary transition-all duration-200 resize-none">{{ $settings['invoice_footer_note'] ?? '' }}</textarea>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-textsecondary dark:text-gray-400 mb-1.5">Accent Color</label>
                    <div class="flex items-center gap-2">
                        <input type="color" name="invoice_accent_color" x-model="accentColor" value="{{ $settings['invoice_accent_color'] ?? '#1a3c5e' }}"
                               class="w-10 h-10 rounded-xl border border-border dark:border-gray-600 cursor-pointer p-0.5 bg-white dark:bg-gray-700">
                        <span class="text-sm text-textsecondary dark:text-gray-400" x-text="accentColor"></span>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-textsecondary dark:text-gray-400 mb-1.5">Paper Size</label>
                    <div class="flex gap-3 mt-1">
                        @foreach(['A4','A5','Letter'] as $size)
                        <label class="flex items-center gap-1.5 cursor-pointer">
                            <input type="radio" name="invoice_paper_size" value="{{ $size }}" {{ ($settings['invoice_paper_size'] ?? 'A4') === $size ? 'checked' : '' }}
                                   x-model="paperSize"
                                   class="w-4 h-4 text-accent focus:ring-primary/30">
                            <span class="text-sm text-textprimary dark:text-gray-100">{{ $size }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="flex gap-6">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="invoice_show_logo" value="1" {{ ($settings['invoice_show_logo'] ?? '1') ? 'checked' : '' }}
                           x-model="showLogo" class="w-4 h-4 rounded border-border dark:border-gray-600 text-accent focus:ring-primary/30">
                    <span class="text-sm text-textprimary dark:text-gray-100">Show Logo</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="invoice_show_address" value="1" {{ ($settings['invoice_show_address'] ?? '1') ? 'checked' : '' }}
                           x-model="showAddress" class="w-4 h-4 rounded border-border dark:border-gray-600 text-accent focus:ring-primary/30">
                    <span class="text-sm text-textprimary dark:text-gray-100">Show Address</span>
                </label>
            </div>

            <div>
                <label class="block text-xs font-medium text-textsecondary dark:text-gray-400 mb-1.5">Store Logo</label>
                <input type="file" name="store_logo" accept="image/*"
                       class="w-full text-sm text-textsecondary dark:text-gray-400 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-accent file:text-white hover:file:bg-yellow-500">
            </div>

            <button type="submit" class="w-full py-2.5 font-semibold bg-accent hover:bg-yellow-500 text-white rounded-xl transition-colors">
                Save Invoice Settings
            </button>
        </form>
    </x-admin.card>

    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-border dark:border-gray-700 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-border dark:border-gray-700">
            <h3 class="font-heading font-semibold text-textprimary dark:text-gray-100">Live Preview</h3>
            <p class="text-xs text-textsecondary dark:text-gray-400 mt-0.5">Updates as you type</p>
        </div>
        <div class="p-6">
            <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm text-xs" style="font-family: 'Segoe UI', Arial, sans-serif;">
                <div :style="`height:3px;background:linear-gradient(90deg,${accentColor},#f59e0b);border-radius:2px;margin-bottom:16px;`"></div>
                <div style="display:flex;justify-content:space-between;margin-bottom:12px;padding-bottom:12px;border-bottom:1px solid #e5e7eb;">
                    <div>
                        <div :style="`font-size:16px;font-weight:800;color:${accentColor};`" x-text="storeName"></div>
                        <div style="color:#6b7280;font-size:10px;margin-top:2px;" x-text="storeAddress" x-show="showAddress"></div>
                        <div style="color:#6b7280;font-size:10px;" x-text="storePhone"></div>
                    </div>
                    <div style="text-align:right;">
                        <div style="font-size:10px;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:1px;">Invoice</div>
                        <div style="font-family:monospace;font-size:13px;font-weight:700;" x-text="prefix + '-' + startNumber"></div>
                        <div style="font-size:10px;color:#6b7280;">{{ now()->format('d M Y') }}</div>
                    </div>
                </div>
                <div style="margin-bottom:12px;">
                    <div style="font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:#9ca3af;margin-bottom:4px;">Bill To</div>
                    <div style="font-weight:600;color:#1f2937;">Sample Customer</div>
                    <div style="color:#6b7280;">01700000000</div>
                    <div style="color:#6b7280;">123 Gulshan Ave, Dhaka</div>
                </div>
                <table style="width:100%;border-collapse:collapse;font-size:10px;margin-bottom:12px;">
                    <thead><tr style="border-bottom:2px solid;" :style="`border-color:${accentColor};`">
                        <th style="text-align:left;padding:6px 4px;font-weight:700;">Product</th>
                        <th style="text-align:center;padding:6px 4px;font-weight:700;">Qty</th>
                        <th style="text-align:right;padding:6px 4px;font-weight:700;">Price</th>
                        <th style="text-align:right;padding:6px 4px;font-weight:700;">Total</th>
                    </tr></thead>
                    <tbody>
                        <tr style="border-bottom:1px solid #f3f4f6;">
                            <td style="padding:6px 4px;color:#374151;">Sample T-Shirt (M/Blue)</td>
                            <td style="text-align:center;padding:6px 4px;">2</td>
                            <td style="text-align:right;padding:6px 4px;">৳800</td>
                            <td style="text-align:right;padding:6px 4px;font-weight:600;">৳1,600</td>
                        </tr>
                    </tbody>
                </table>
                <div style="display:flex;justify-content:flex-end;margin-bottom:12px;">
                    <div style="width:160px;">
                        <div style="display:flex;justify-content:space-between;padding:3px 0;color:#6b7280;"><span>Subtotal</span><span>৳1,600</span></div>
                        <div style="display:flex;justify-content:space-between;padding:3px 0;color:#6b7280;"><span>Shipping</span><span>৳60</span></div>
                        <div :style="`display:flex;justify-content:space-between;padding:6px 0;border-top:2px solid;font-weight:700;font-size:12px;border-color:${accentColor};`"><span>Total</span><span>৳1,660</span></div>
                    </div>
                </div>
                <div style="border-top:1px solid #e5e7eb;padding-top:10px;text-align:center;color:#6b7280;font-size:10px;">
                    <div :style="`font-weight:600;color:${accentColor};margin-bottom:3px;`" x-text="footerNote"></div>
                </div>
            </div>
        </div>
    </div>

</div>

@endsection
