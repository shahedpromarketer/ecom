@extends('layouts.admin')

@section('title', 'Create Coupon')
@section('page-title', 'Coupons')

@section('breadcrumb')
    <span class="text-textsecondary">/</span>
    <a href="{{ route('admin.coupons.index') }}" class="text-textsecondary hover:text-textprimary dark:hover:text-gray-200 transition-colors">Coupons</a>
    <span class="text-textsecondary">/</span>
    <span class="text-textprimary font-medium">Create</span>
@endsection

@section('content')

<form method="POST" action="{{ route('admin.coupons.store') }}" x-data="couponForm()">
    @csrf

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

        {{-- Left: Coupon Details --}}
        <div class="xl:col-span-2 space-y-6">

            <x-admin.card>
                <x-slot:header>
                    <h3 class="font-heading font-semibold text-textprimary dark:text-gray-100">Coupon Details</h3>
                </x-slot:header>
                <div class="p-6 space-y-5">

                    {{-- Code --}}
                    <div>
                        <label class="block text-sm font-medium mb-1.5 text-textprimary dark:text-gray-200">
                            Coupon Code <span class="text-danger">*</span>
                        </label>
                        <div class="flex gap-2">
                            <input type="text" name="code" x-model="code"
                                   @input="code = $event.target.value.toUpperCase()"
                                   value="{{ old('code') }}"
                                   placeholder="e.g. SAVE20"
                                   required
                                   class="flex-1 px-4 py-2.5 text-sm rounded-xl border font-mono tracking-widest uppercase transition-all duration-200
                                          focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary
                                          text-textprimary dark:text-gray-100 placeholder-textsecondary dark:placeholder-gray-500
                                          @error('code') border-danger bg-red-50 dark:bg-red-900/10 dark:border-red-700
                                          @else border-border dark:border-gray-600 bg-white dark:bg-gray-700/50 @enderror">
                            <button type="button" @click="generateCode()"
                                    class="px-4 py-2.5 text-sm font-medium border border-border dark:border-gray-600 rounded-xl
                                           bg-white dark:bg-gray-700/50 text-textprimary dark:text-gray-200
                                           hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors whitespace-nowrap">
                                Generate
                            </button>
                        </div>
                        <p class="mt-1.5 text-xs text-textsecondary dark:text-gray-500">Customers enter this code at checkout.</p>
                        @error('code')<p class="mt-1 text-xs text-danger dark:text-red-400">{{ $message }}</p>@enderror
                    </div>

                    {{-- Type --}}
                    <div>
                        <label class="block text-sm font-medium mb-2 text-textprimary dark:text-gray-200">
                            Discount Type <span class="text-danger">*</span>
                        </label>
                        <div class="inline-flex rounded-xl border border-border dark:border-gray-600 overflow-hidden bg-gray-50 dark:bg-gray-700/50 p-1 gap-1">
                            <label class="cursor-pointer">
                                <input type="radio" name="type" value="percent" x-model="type"
                                       class="sr-only peer" {{ old('type', 'percent') === 'percent' ? 'checked' : '' }}>
                                <span class="inline-block px-5 py-2 text-sm font-medium rounded-lg transition-all duration-150
                                             peer-checked:bg-white dark:peer-checked:bg-gray-600 peer-checked:text-primary peer-checked:shadow-sm
                                             text-textsecondary dark:text-gray-400 hover:text-textprimary dark:hover:text-gray-200">
                                    Percent (%)
                                </span>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" name="type" value="fixed" x-model="type"
                                       class="sr-only peer" {{ old('type') === 'fixed' ? 'checked' : '' }}>
                                <span class="inline-block px-5 py-2 text-sm font-medium rounded-lg transition-all duration-150
                                             peer-checked:bg-white dark:peer-checked:bg-gray-600 peer-checked:text-primary peer-checked:shadow-sm
                                             text-textsecondary dark:text-gray-400 hover:text-textprimary dark:hover:text-gray-200">
                                    Fixed Amount (৳)
                                </span>
                            </label>
                        </div>
                        @error('type')<p class="mt-1.5 text-xs text-danger dark:text-red-400">{{ $message }}</p>@enderror
                    </div>

                    {{-- Discount Value --}}
                    <div>
                        <label class="block text-sm font-medium mb-1.5 text-textprimary dark:text-gray-200">
                            Discount Value <span class="text-danger">*</span>
                        </label>
                        <div class="relative">
                            <span x-show="type === 'fixed'"
                                  class="absolute left-3.5 top-1/2 -translate-y-1/2 text-sm font-medium text-textsecondary dark:text-gray-400 pointer-events-none">
                                ৳
                            </span>
                            <input type="number" name="value" x-model="value"
                                   value="{{ old('value') }}"
                                   min="0" step="0.01" required
                                   :class="type === 'fixed' ? 'pl-8' : 'pr-8'"
                                   class="w-full px-4 py-2.5 text-sm rounded-xl border transition-all duration-200
                                          focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary
                                          text-textprimary dark:text-gray-100
                                          @error('value') border-danger bg-red-50 dark:bg-red-900/10 dark:border-red-700
                                          @else border-border dark:border-gray-600 bg-white dark:bg-gray-700/50 @enderror">
                            <span x-show="type === 'percent'"
                                  class="absolute right-3.5 top-1/2 -translate-y-1/2 text-sm font-medium text-textsecondary dark:text-gray-400 pointer-events-none">
                                %
                            </span>
                        </div>
                        @error('value')<p class="mt-1.5 text-xs text-danger dark:text-red-400">{{ $message }}</p>@enderror
                    </div>

                    {{-- Min Order Amount --}}
                    <div>
                        <label class="block text-sm font-medium mb-1.5 text-textprimary dark:text-gray-200">
                            Minimum Order Amount (৳)
                        </label>
                        <div class="relative">
                            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-sm text-textsecondary dark:text-gray-400 pointer-events-none">৳</span>
                            <input type="number" name="min_order_amount"
                                   value="{{ old('min_order_amount') }}"
                                   min="0" step="1"
                                   class="w-full pl-8 pr-4 py-2.5 text-sm rounded-xl border border-border dark:border-gray-600 bg-white dark:bg-gray-700/50 transition-all duration-200
                                          focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary
                                          text-textprimary dark:text-gray-100
                                          @error('min_order_amount') border-danger bg-red-50 dark:bg-red-900/10 dark:border-red-700 @enderror">
                        </div>
                        @error('min_order_amount')<p class="mt-1.5 text-xs text-danger dark:text-red-400">{{ $message }}</p>@enderror
                    </div>

                    {{-- Max Discount (percent only) --}}
                    <div x-show="type === 'percent'" x-transition>
                        <label class="block text-sm font-medium mb-1.5 text-textprimary dark:text-gray-200">
                            Maximum Discount (৳)
                        </label>
                        <div class="relative">
                            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-sm text-textsecondary dark:text-gray-400 pointer-events-none">৳</span>
                            <input type="number" name="max_discount"
                                   value="{{ old('max_discount') }}"
                                   min="0" step="1"
                                   class="w-full pl-8 pr-4 py-2.5 text-sm rounded-xl border border-border dark:border-gray-600 bg-white dark:bg-gray-700/50 transition-all duration-200
                                          focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary
                                          text-textprimary dark:text-gray-100
                                          @error('max_discount') border-danger bg-red-50 dark:bg-red-900/10 dark:border-red-700 @enderror">
                        </div>
                        <p class="mt-1.5 text-xs text-textsecondary dark:text-gray-500">Cap the maximum discount amount for percentage coupons.</p>
                        @error('max_discount')<p class="mt-1 text-xs text-danger dark:text-red-400">{{ $message }}</p>@enderror
                    </div>

                    {{-- Usage Limit --}}
                    <div>
                        <label class="block text-sm font-medium mb-1.5 text-textprimary dark:text-gray-200">
                            Usage Limit
                        </label>
                        <input type="number" name="usage_limit"
                               value="{{ old('usage_limit') }}"
                               min="1" step="1"
                               placeholder="Unlimited"
                               class="w-full px-4 py-2.5 text-sm rounded-xl border border-border dark:border-gray-600 bg-white dark:bg-gray-700/50 transition-all duration-200
                                      focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary
                                      text-textprimary dark:text-gray-100 placeholder-textsecondary dark:placeholder-gray-500
                                      @error('usage_limit') border-danger bg-red-50 dark:bg-red-900/10 dark:border-red-700 @enderror">
                        <p class="mt-1.5 text-xs text-textsecondary dark:text-gray-500">Leave blank for unlimited uses.</p>
                        @error('usage_limit')<p class="mt-1 text-xs text-danger dark:text-red-400">{{ $message }}</p>@enderror
                    </div>

                    {{-- Expiry Date --}}
                    <div>
                        <label class="block text-sm font-medium mb-1.5 text-textprimary dark:text-gray-200">
                            Expiry Date
                        </label>
                        <input type="datetime-local" name="expires_at"
                               value="{{ old('expires_at') }}"
                               class="w-full px-4 py-2.5 text-sm rounded-xl border border-border dark:border-gray-600 bg-white dark:bg-gray-700/50 transition-all duration-200
                                      focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary
                                      text-textprimary dark:text-gray-100
                                      @error('expires_at') border-danger bg-red-50 dark:bg-red-900/10 dark:border-red-700 @enderror">
                        <p class="mt-1.5 text-xs text-textsecondary dark:text-gray-500">Leave blank for no expiry.</p>
                        @error('expires_at')<p class="mt-1 text-xs text-danger dark:text-red-400">{{ $message }}</p>@enderror
                    </div>

                </div>
            </x-admin.card>

        </div>

        {{-- Right: Settings + Preview --}}
        <div class="space-y-6">

            <x-admin.card>
                <x-slot:header>
                    <h3 class="font-heading font-semibold text-textprimary dark:text-gray-100">Settings</h3>
                </x-slot:header>
                <div class="p-6 space-y-5">

                    {{-- Status toggle --}}
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-textprimary dark:text-gray-200">Active</p>
                            <p class="text-xs text-textsecondary dark:text-gray-500 mt-0.5">Coupon can be used at checkout</p>
                        </div>
                        <label class="relative inline-flex cursor-pointer">
                            <input type="checkbox" name="is_active" value="1" class="sr-only peer"
                                   {{ old('is_active', '1') == '1' ? 'checked' : '' }}>
                            <div class="w-9 h-5 rounded-full peer-checked:bg-green-500 bg-gray-300 dark:bg-gray-600
                                        after:content-[''] after:absolute after:top-0.5 after:left-0.5
                                        after:bg-white after:rounded-full after:h-4 after:w-4
                                        after:transition-all peer-checked:after:translate-x-4"></div>
                        </label>
                    </div>

                    <hr class="border-border dark:border-gray-700">

                    {{-- Action buttons --}}
                    <div class="flex gap-3">
                        <a href="{{ route('admin.coupons.index') }}"
                           class="flex-1 text-center px-4 py-2.5 text-sm font-medium border border-border dark:border-gray-600 rounded-xl
                                  text-textprimary dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            Cancel
                        </a>
                        <button type="submit"
                                class="flex-1 px-4 py-2.5 text-sm font-semibold bg-primary hover:bg-primary/90 text-white rounded-xl transition-colors">
                            Save Coupon
                        </button>
                    </div>

                </div>
            </x-admin.card>

            {{-- Live Preview --}}
            <x-admin.card>
                <x-slot:header>
                    <h3 class="font-heading font-semibold text-textprimary dark:text-gray-100">Preview</h3>
                </x-slot:header>
                <div class="p-6">
                    <div class="rounded-xl border-2 border-dashed border-primary/30 bg-primary/5 dark:bg-primary/10 p-5 space-y-3">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-primary flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 14.25l6-6m4.5-3.493V21.75l-3.75-1.5-3.75 1.5-3.75-1.5-3.75 1.5V4.757c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0c1.1.128 1.907 1.077 1.907 2.185zM9.75 9h.008v.008H9.75V9zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm4.125 4.5h.008v.008h-.008V13.5zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/>
                            </svg>
                            <span class="text-xs font-semibold uppercase tracking-wider text-primary">Coupon Preview</span>
                        </div>
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-textsecondary dark:text-gray-400">Code</span>
                                <span x-text="code || 'YOURCODE'"
                                      class="font-mono font-bold tracking-widest text-sm text-textprimary dark:text-gray-100 bg-white dark:bg-gray-700 px-2 py-0.5 rounded-lg">
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-textsecondary dark:text-gray-400">Type</span>
                                <span x-text="type === 'percent' ? 'Percentage' : 'Fixed Amount'"
                                      class="text-sm font-medium text-textprimary dark:text-gray-200">
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-textsecondary dark:text-gray-400">Discount</span>
                                <span x-text="value ? (type === 'percent' ? value + '%' : '৳' + value) : '—'"
                                      class="text-sm font-bold text-primary">
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </x-admin.card>

        </div>
    </div>
</form>

@endsection

@push('scripts')
<script>
function couponForm() {
    return {
        code: '{{ old('code') }}',
        type: '{{ old('type', 'percent') }}',
        value: '{{ old('value') }}',
        generateCode() {
            const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            let code = '';
            for (let i = 0; i < 8; i++) {
                code += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            this.code = code;
        }
    }
}
</script>
@endpush
