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
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>

    {{-- Modal Panel --}}
    <div x-show="quickOrderOpen"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-full md:translate-y-4 md:scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 md:scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0 md:scale-100"
         x-transition:leave-end="opacity-0 translate-y-full md:translate-y-4 md:scale-95"
         class="relative w-full md:max-w-md bg-white rounded-t-3xl md:rounded-2xl shadow-2xl max-h-[92vh] overflow-y-auto z-10"
         x-data="quickOrderForm()"
         @quick-order-open.window="openModal($event.detail)">

        {{-- Drag Handle (mobile) --}}
        <div class="md:hidden flex justify-center pt-3 pb-1">
            <div class="w-10 h-1 bg-gray-300 rounded-full"></div>
        </div>

        {{-- Success State --}}
        <div x-show="success" class="p-8 text-center">
            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <h3 class="font-heading font-bold text-xl text-textprimary mb-2">অর্ডার সফল হয়েছে! 🎉</h3>
            <p class="text-textsecondary text-sm mb-1">অর্ডার নম্বর:</p>
            <p class="font-heading font-bold text-lg text-primary mb-4" x-text="'#' + orderNumber"></p>
            <p class="text-sm text-textsecondary mb-6">আমরা শীঘ্রই আপনার সাথে যোগাযোগ করব।</p>
            <button @click="closeModal()" class="btn-primary w-full">বন্ধ করুন</button>
        </div>

        {{-- Form State --}}
        <div x-show="!success">
            {{-- Header --}}
            <div class="flex items-center gap-3 px-5 pt-4 pb-4 border-b border-gray-100">
                <div class="w-16 h-16 rounded-xl overflow-hidden bg-gray-100 flex-shrink-0">
                    <img :src="product?.image || ''" :alt="product?.name || ''"
                         class="w-full h-full object-cover" x-show="product?.image">
                    <div x-show="!product?.image" class="w-full h-full flex items-center justify-center">
                        <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="font-semibold text-textprimary text-sm leading-snug line-clamp-2" x-text="product?.name || 'পণ্য'"></p>
                    <div class="flex items-center gap-2 mt-1">
                        <span class="text-accent font-bold" x-text="'৳' + (product?.sale_price || product?.price || 0)"></span>
                        <span x-show="product?.sale_price && product?.regular_price"
                              class="text-xs text-gray-400 line-through" x-text="'৳' + product?.regular_price"></span>
                    </div>
                </div>
                <button @click="closeModal()" class="flex-shrink-0 w-8 h-8 rounded-xl bg-gray-100 hover:bg-gray-200 flex items-center justify-center transition-colors">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Form --}}
            <form @submit.prevent="submitOrder()" class="p-5 space-y-4">

                {{-- General Error --}}
                <div x-show="errors.general"
                     class="bg-red-50 border border-red-200 rounded-xl px-4 py-3 text-sm text-red-600"
                     x-text="errors.general"></div>

                {{-- Name --}}
                <div>
                    <label class="block text-xs font-semibold text-textprimary mb-1.5">নাম <span class="text-red-500">*</span></label>
                    <input type="text" x-model="form.name" placeholder="আপনার পুরো নাম"
                           class="input-field" :class="errors.name ? 'border-red-400 ring-1 ring-red-400' : ''">
                    <p x-show="errors.name" class="text-xs text-red-500 mt-1" x-text="errors.name"></p>
                </div>

                {{-- Phone --}}
                <div>
                    <label class="block text-xs font-semibold text-textprimary mb-1.5">মোবাইল নম্বর <span class="text-red-500">*</span></label>
                    <input type="tel" x-model="form.phone" placeholder="01XXXXXXXXX" maxlength="11"
                           class="input-field" :class="errors.phone ? 'border-red-400 ring-1 ring-red-400' : ''">
                    <p x-show="errors.phone" class="text-xs text-red-500 mt-1" x-text="errors.phone"></p>
                </div>

                {{-- Address --}}
                <div>
                    <label class="block text-xs font-semibold text-textprimary mb-1.5">ঠিকানা <span class="text-red-500">*</span></label>
                    <textarea x-model="form.address" rows="2" placeholder="বিস্তারিত ঠিকানা লিখুন"
                              class="input-field resize-none" :class="errors.address ? 'border-red-400 ring-1 ring-red-400' : ''"></textarea>
                    <p x-show="errors.address" class="text-xs text-red-500 mt-1" x-text="errors.address"></p>
                </div>

                {{-- Size (if variants) --}}
                <div x-show="product?.variants && product.variants.length > 0">
                    <label class="block text-xs font-semibold text-textprimary mb-1.5">সাইজ <span class="text-red-500">*</span></label>
                    <div class="flex flex-wrap gap-2">
                        <template x-for="variant in (product?.variants || [])" :key="variant.id">
                            <button type="button"
                                @click="form.variant_id = variant.id"
                                class="px-3 py-1.5 text-sm font-medium rounded-lg border-2 transition-all duration-150"
                                :class="form.variant_id === variant.id
                                    ? 'border-primary bg-primary text-white'
                                    : 'border-gray-200 text-textprimary hover:border-primary'">
                                <span x-text="variant.size || variant.name"></span>
                            </button>
                        </template>
                    </div>
                    <p x-show="errors.variant_id" class="text-xs text-red-500 mt-1" x-text="errors.variant_id"></p>
                </div>

                {{-- Quantity --}}
                <div>
                    <label class="block text-xs font-semibold text-textprimary mb-1.5">পরিমাণ</label>
                    <div class="flex items-center gap-3">
                        <button type="button" @click="form.qty = Math.max(1, form.qty - 1)"
                            class="w-9 h-9 rounded-xl border-2 border-gray-200 flex items-center justify-center text-lg font-bold text-textprimary hover:border-primary hover:text-primary transition-all active:scale-95">−</button>
                        <span class="w-10 text-center font-bold text-textprimary text-lg" x-text="form.qty"></span>
                        <button type="button" @click="form.qty = form.qty + 1"
                            class="w-9 h-9 rounded-xl border-2 border-gray-200 flex items-center justify-center text-lg font-bold text-textprimary hover:border-primary hover:text-primary transition-all active:scale-95">+</button>
                    </div>
                </div>

                {{-- Note --}}
                <div>
                    <label class="block text-xs font-semibold text-textprimary mb-1.5">অর্ডার নোট (ঐচ্ছিক)</label>
                    <textarea x-model="form.note" rows="2" placeholder="কোনো বিশেষ নির্দেশনা থাকলে লিখুন"
                              class="input-field resize-none"></textarea>
                </div>

                {{-- Order Summary --}}
                <div class="bg-gray-50 rounded-2xl p-4 space-y-2">
                    <h4 class="text-xs font-bold text-textprimary uppercase tracking-wide mb-3">অর্ডার সারসংক্ষেপ</h4>
                    <div class="flex justify-between text-sm">
                        <span class="text-textsecondary">পণ্য মূল্য</span>
                        <span class="font-medium" x-text="'৳' + ((product?.sale_price || product?.price || 0) * form.qty)"></span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-textsecondary">ডেলিভারি চার্জ</span>
                        <span class="font-medium" x-text="deliveryCharge > 0 ? '৳' + deliveryCharge : 'ফ্রি'"></span>
                    </div>
                    <div class="border-t border-gray-200 pt-2 flex justify-between text-sm font-bold">
                        <span class="text-textprimary">মোট</span>
                        <span class="text-accent text-base" x-text="'৳' + grandTotal"></span>
                    </div>
                </div>

                {{-- Submit --}}
                <button type="submit"
                    class="btn-accent w-full text-base py-3.5 relative"
                    :disabled="loading"
                    :class="loading ? 'opacity-75 cursor-not-allowed' : ''">
                    <span x-show="!loading">অর্ডার কনফার্ম করুন ✓</span>
                    <span x-show="loading" class="flex items-center gap-2">
                        <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        অপেক্ষা করুন...
                    </span>
                </button>
            </form>
        </div>
    </div>
</div>

<script>
function quickOrderForm() {
    return {
        product: null,
        loading: false,
        success: false,
        orderNumber: '',
        form: {
            name: '',
            phone: '',
            address: '',
            variant_id: null,
            qty: 1,
            note: ''
        },
        errors: {},

        get deliveryCharge() {
            const price = (this.product?.sale_price || this.product?.price || 0) * this.form.qty;
            const freeAbove = {{ $settings['free_shipping_above'] ?? 999 }};
            const charge = {{ $settings['shipping_charge'] ?? 60 }};
            return price >= freeAbove ? 0 : charge;
        },

        get grandTotal() {
            const price = (this.product?.sale_price || this.product?.price || 0) * this.form.qty;
            return price + this.deliveryCharge;
        },

        openModal(product) {
            this.product = product;
            this.form = { name: '', phone: '', address: '', variant_id: null, qty: 1, note: '' };
            this.errors = {};
            this.success = false;
        },

        closeModal() {
            this.$root.dispatchEvent(new Event('close-quick-order'));
            // Use parent scope
            if (window.Alpine) {
                const parent = document.querySelector('[x-data]');
            }
            this.$dispatch('close-quick-order');
        },

        async submitOrder() {
            this.errors = {};

            if (!this.form.name.trim()) { this.errors.name = 'নাম দিন'; return; }
            if (!this.form.phone || !/^01[0-9]{9}$/.test(this.form.phone)) {
                this.errors.phone = 'সঠিক ১১ সংখ্যার নম্বর দিন (01 দিয়ে শুরু)';
                return;
            }
            if (!this.form.address.trim()) { this.errors.address = 'ঠিকানা দিন'; return; }
            if (this.product?.variants?.length > 0 && !this.form.variant_id) {
                this.errors.variant_id = 'সাইজ সিলেক্ট করুন';
                return;
            }

            this.loading = true;

            try {
                const response = await fetch('/order/quick', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        product_id: this.product.id,
                        variant_id: this.form.variant_id,
                        qty: this.form.qty,
                        name: this.form.name,
                        phone: this.form.phone,
                        address: this.form.address,
                        note: this.form.note
                    })
                });

                const data = await response.json();
                this.loading = false;

                if (data.success) {
                    this.success = true;
                    this.orderNumber = data.order_number;
                } else {
                    this.errors.general = data.message || 'কিছু একটা সমস্যা হয়েছে। আবার চেষ্টা করুন।';
                }
            } catch (e) {
                this.loading = false;
                this.errors.general = 'নেটওয়ার্ক সমস্যা। আবার চেষ্টা করুন।';
            }
        }
    };
}
</script>
