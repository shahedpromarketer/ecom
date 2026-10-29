{{-- Quick Order Modal --}}
<div x-show="quickOrderOpen"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-50 flex items-end md:items-center justify-center"
     @click.self="closeQuickOrder()"
     style="display: none;">

    {{-- Backdrop --}}
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="closeQuickOrder()"></div>

    {{-- Modal Panel --}}
    <div x-show="quickOrderOpen"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-full md:translate-y-4 md:scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 md:scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0 md:scale-100"
         x-transition:leave-end="opacity-0 translate-y-full md:translate-y-4 md:scale-95"
         class="relative w-full md:max-w-md bg-white rounded-t-3xl md:rounded-2xl shadow-2xl z-10 flex flex-col"
         style="max-height: 92vh;"
         x-data="quickOrderForm()"
         @quick-order-open.window="openModal($event.detail)">

        {{-- Drag Handle (mobile) --}}
        <div class="md:hidden flex justify-center pt-3 pb-1 flex-shrink-0">
            <div class="w-10 h-1 bg-gray-300 rounded-full"></div>
        </div>

        {{-- ── SUCCESS STATE ── --}}
        <div x-show="success" x-cloak class="p-8 text-center flex-1 flex flex-col items-center justify-center">
            <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-5">
                <svg class="w-10 h-10 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <h3 class="font-heading font-bold text-xl text-textprimary mb-2">অর্ডার সফল হয়েছে! 🎉</h3>
            <p class="text-textsecondary text-sm mb-1">অর্ডার নম্বর:</p>
            <p class="font-heading font-bold text-lg text-primary mb-4" x-text="'#' + orderNumber"></p>
            <p class="text-sm text-textsecondary mb-6">আমরা শীঘ্রই আপনার সাথে যোগাযোগ করব।</p>
            <button @click="$dispatch('close-quick-order')" class="btn-primary w-full">বন্ধ করুন</button>
        </div>

        {{-- ── FORM STATE ── --}}
        <template x-if="!success">
            <div class="flex flex-col flex-1 min-h-0">

                {{-- Header --}}
                <div class="flex items-center gap-3 px-5 pt-4 pb-4 border-b border-gray-100 flex-shrink-0">
                    <div class="w-16 h-16 rounded-xl overflow-hidden bg-gray-100 flex-shrink-0">
                        <img :src="product?.image || ''"
                             :alt="product?.name || ''"
                             class="w-full h-full object-cover"
                             x-show="product?.image">
                        <div x-show="!product?.image"
                             class="w-full h-full flex items-center justify-center text-gray-300">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-textprimary text-sm leading-snug line-clamp-2"
                           x-text="product?.name || 'পণ্য'"></p>
                        <div class="flex items-center gap-2 mt-1">
                            <span class="text-accent font-bold text-base"
                                  x-text="'৳' + ((product?.sale_price || product?.price || 0) * form.qty)"></span>
                            <span x-show="product?.sale_price && product?.regular_price && product.sale_price < product.regular_price"
                                  class="text-xs text-gray-400 line-through"
                                  x-text="'৳' + (product?.regular_price * form.qty)"></span>
                        </div>
                    </div>
                    {{-- Close --}}
                    <button @click="$dispatch('close-quick-order')"
                        type="button"
                        class="flex-shrink-0 w-9 h-9 rounded-xl bg-gray-100 hover:bg-gray-200 flex items-center justify-center transition-colors active:scale-95 focus:outline-none">
                        <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                {{-- Scrollable Form Body --}}
                <div class="overflow-y-auto flex-1 px-5 py-4 space-y-4">

                    {{-- General Error --}}
                    <div x-show="errors.general"
                         x-cloak
                         class="bg-red-50 border border-red-200 rounded-xl px-4 py-3 text-sm text-red-600"
                         x-text="errors.general"></div>

                    {{-- Name --}}
                    <div>
                        <label class="block text-xs font-semibold text-textprimary mb-1.5">
                            নাম <span class="text-red-500">*</span>
                        </label>
                        <input type="text"
                               x-model="form.name"
                               placeholder="আপনার পুরো নাম"
                               autocomplete="name"
                               class="input-field"
                               :class="errors.name ? 'border-red-400 ring-1 ring-red-400' : ''">
                        <p x-show="errors.name" x-cloak class="text-xs text-red-500 mt-1" x-text="errors.name"></p>
                    </div>

                    {{-- Phone --}}
                    <div>
                        <label class="block text-xs font-semibold text-textprimary mb-1.5">
                            মোবাইল নম্বর <span class="text-red-500">*</span>
                        </label>
                        <input type="tel"
                               x-model="form.phone"
                               placeholder="01XXXXXXXXX"
                               maxlength="11"
                               autocomplete="tel"
                               inputmode="numeric"
                               class="input-field"
                               :class="errors.phone ? 'border-red-400 ring-1 ring-red-400' : ''">
                        <p x-show="errors.phone" x-cloak class="text-xs text-red-500 mt-1" x-text="errors.phone"></p>
                    </div>

                    {{-- Address --}}
                    <div>
                        <label class="block text-xs font-semibold text-textprimary mb-1.5">
                            ঠিকানা <span class="text-red-500">*</span>
                        </label>
                        <textarea x-model="form.address"
                                  rows="2"
                                  placeholder="জেলা, উপজেলা ও বিস্তারিত ঠিকানা লিখুন"
                                  autocomplete="street-address"
                                  class="input-field resize-none"
                                  :class="errors.address ? 'border-red-400 ring-1 ring-red-400' : ''"></textarea>
                        <p x-show="errors.address" x-cloak class="text-xs text-red-500 mt-1" x-text="errors.address"></p>
                    </div>

                    {{-- Size / Variant (only if product has variants) --}}
                    <div x-show="product?.variants && product.variants.length > 0">
                        <label class="block text-xs font-semibold text-textprimary mb-1.5">
                            সাইজ <span class="text-red-500">*</span>
                        </label>
                        <div class="flex flex-wrap gap-2">
                            <template x-for="variant in (product?.variants || [])" :key="variant.id">
                                <button type="button"
                                    @click="form.variant_id = variant.id; errors.variant_id = ''"
                                    class="px-3 py-1.5 text-sm font-semibold rounded-xl border-2 transition-all duration-150 active:scale-95 focus:outline-none"
                                    :class="form.variant_id === variant.id
                                        ? 'border-primary bg-primary text-white shadow-sm'
                                        : 'border-gray-200 text-textprimary hover:border-primary hover:text-primary'">
                                    <span x-text="variant.size || variant.name"></span>
                                </button>
                            </template>
                        </div>
                        <p x-show="errors.variant_id" x-cloak class="text-xs text-red-500 mt-1" x-text="errors.variant_id"></p>
                    </div>

                    {{-- Quantity --}}
                    <div>
                        <label class="block text-xs font-semibold text-textprimary mb-1.5">পরিমাণ</label>
                        <div class="flex items-center gap-3">
                            <button type="button"
                                @click="form.qty = Math.max(1, form.qty - 1)"
                                class="w-10 h-10 rounded-xl border-2 border-gray-200 flex items-center justify-center text-xl font-bold text-textprimary hover:border-primary hover:text-primary transition-all active:scale-90 focus:outline-none">
                                −
                            </button>
                            <span class="w-10 text-center font-bold text-textprimary text-lg" x-text="form.qty"></span>
                            <button type="button"
                                @click="form.qty = Math.min(20, form.qty + 1)"
                                class="w-10 h-10 rounded-xl border-2 border-gray-200 flex items-center justify-center text-xl font-bold text-textprimary hover:border-primary hover:text-primary transition-all active:scale-90 focus:outline-none">
                                +
                            </button>
                        </div>
                    </div>

                    {{-- Note --}}
                    <div>
                        <label class="block text-xs font-semibold text-textprimary mb-1.5">
                            অর্ডার নোট <span class="text-gray-400 font-normal">(ঐচ্ছিক)</span>
                        </label>
                        <textarea x-model="form.note"
                                  rows="2"
                                  placeholder="কোনো বিশেষ নির্দেশনা থাকলে লিখুন"
                                  class="input-field resize-none"></textarea>
                    </div>

                    {{-- Order Summary --}}
                    <div class="bg-gray-50 rounded-2xl p-4 space-y-2">
                        <p class="text-xs font-bold text-textprimary uppercase tracking-wide mb-3">অর্ডার সারসংক্ষেপ</p>
                        <div class="flex justify-between text-sm">
                            <span class="text-textsecondary">পণ্য মূল্য</span>
                            <span class="font-medium"
                                  x-text="'৳' + ((product?.sale_price || product?.price || 0) * form.qty)"></span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-textsecondary">ডেলিভারি চার্জ</span>
                            <span class="font-medium"
                                  :class="deliveryCharge === 0 ? 'text-green-600 font-semibold' : ''"
                                  x-text="deliveryCharge > 0 ? '৳' + deliveryCharge : 'ফ্রি ✓'"></span>
                        </div>
                        <div class="border-t border-gray-200 pt-2 flex justify-between font-bold">
                            <span class="text-textprimary text-sm">মোট পরিশোধ</span>
                            <span class="text-accent text-base" x-text="'৳' + grandTotal"></span>
                        </div>
                    </div>

                </div>{{-- end scroll area --}}

                {{-- Sticky Submit --}}
                <div class="flex-shrink-0 px-5 py-4 bg-white border-t border-gray-100">
                    <button type="button"
                        @click="submitOrder()"
                        class="w-full py-4 rounded-2xl font-bold text-base text-black transition-all duration-150 active:scale-95 flex items-center justify-center gap-2 focus:outline-none"
                        :class="loading ? 'bg-accent/60 cursor-not-allowed' : 'bg-accent hover:bg-yellow-500 shadow-md'"
                        :disabled="loading">
                        <template x-if="!loading">
                            <span class="flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                </svg>
                                অর্ডার কনফার্ম করুন
                            </span>
                        </template>
                        <template x-if="loading">
                            <span class="flex items-center gap-2">
                                <svg class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                অপেক্ষা করুন...
                            </span>
                        </template>
                    </button>
                </div>

            </div>
        </template>

    </div>
</div>

<script>
function quickOrderForm() {
    return {
        product: null,
        loading: false,
        success: false,
        orderNumber: '',
        form: { name: '', phone: '', address: '', variant_id: null, qty: 1, note: '' },
        errors: {},

        get deliveryCharge() {
            const subtotal = (this.product?.sale_price || this.product?.price || 0) * this.form.qty;
            const freeAbove = {{ $settings['free_shipping_above'] ?? 999 }};
            const charge   = {{ $settings['shipping_charge'] ?? 60 }};
            return subtotal >= freeAbove ? 0 : charge;
        },

        get grandTotal() {
            return (this.product?.sale_price || this.product?.price || 0) * this.form.qty + this.deliveryCharge;
        },

        openModal(product) {
            this.product  = product;
            this.errors   = {};
            this.success  = false;

            const preSelected = product.variants?.find(v => v.selected);
            this.form = {
                name:       '',
                phone:      '',
                address:    '',
                variant_id: preSelected?.id ?? null,
                qty:        product.qty || 1,
                note:       '',
            };
        },

        async submitOrder() {
            this.errors = {};

            if (!this.form.name.trim()) {
                this.errors.name = 'নাম দিন';
                return;
            }
            if (!/^01[0-9]{9}$/.test(this.form.phone)) {
                this.errors.phone = 'সঠিক ১১ সংখ্যার নম্বর দিন (01 দিয়ে শুরু)';
                return;
            }
            if (!this.form.address.trim()) {
                this.errors.address = 'ঠিকানা দিন';
                return;
            }
            if (this.product?.variants?.length > 0 && !this.form.variant_id) {
                this.errors.variant_id = 'সাইজ সিলেক্ট করুন';
                return;
            }

            this.loading = true;
            try {
                const res = await fetch('/order/quick', {
                    method:  'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept':       'application/json',
                    },
                    body: JSON.stringify({
                        product_id: this.product.id,
                        variant_id: this.form.variant_id,
                        qty:        this.form.qty,
                        name:       this.form.name,
                        phone:      this.form.phone,
                        address:    this.form.address,
                        note:       this.form.note,
                    }),
                });

                const data = await res.json();
                this.loading = false;

                if (data.success) {
                    window.location.href = data.redirect;
                } else if (res.status === 422 && data.errors) {
                    // Laravel validation errors
                    const errs = data.errors;
                    if (errs.name)       this.errors.name       = errs.name[0];
                    if (errs.phone)      this.errors.phone      = errs.phone[0];
                    if (errs.address)    this.errors.address    = errs.address[0];
                    if (errs.variant_id) this.errors.variant_id = errs.variant_id[0];
                    if (errs.product_id) this.errors.general    = errs.product_id[0];
                } else {
                    this.errors.general = data.message || 'কিছু একটা সমস্যা হয়েছে। আবার চেষ্টা করুন।';
                }
            } catch (e) {
                this.loading = false;
                this.errors.general = 'নেটওয়ার্ক সমস্যা। আবার চেষ্টা করুন।';
            }
        },
    };
}
</script>
