@php
    $cartFreeAbove      = $settings['free_shipping_above'] ?? 999;
    $cartShippingCharge = $settings['shipping_charge'] ?? 60;
@endphp

{{-- Backdrop --}}
<div x-show="cartOpen"
     x-cloak
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     @click="cartOpen = false; document.body.style.overflow = ''"
     class="fixed inset-0 bg-black/50 z-[55]"></div>

{{-- Drawer Panel --}}
<div x-show="cartOpen"
     x-cloak
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="translate-x-full"
     x-transition:enter-end="translate-x-0"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="translate-x-0"
     x-transition:leave-end="translate-x-full"
     class="fixed top-0 right-0 h-full w-full max-w-sm bg-white z-[56] flex flex-col shadow-2xl"
     x-data="{
         checkoutOpen: false,
         form: { name: '', phone: '', address: '', note: '' },
         errors: {},
         submitting: false,
         async submitCart() {
             this.errors = {};
             this.submitting = true;
             let cartItems = [];
             try {
                 const raw = JSON.parse(localStorage.getItem('ecom_cart') || '[]');
                 cartItems = raw.map(i => ({ product_id: i.id, variant_id: i.variant_id ?? null, qty: i.qty }));
             } catch(e) { cartItems = []; }
             if (!cartItems.length) {
                 this.errors._general = 'কার্ট খালি।';
                 this.submitting = false;
                 return;
             }
             try {
                 const r = await fetch('{{ route('cart.checkout') }}', {
                     method: 'POST',
                     headers: {
                         'Content-Type': 'application/json',
                         'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                         'Accept': 'application/json',
                     },
                     body: JSON.stringify({ ...this.form, items: cartItems }),
                 });
                 const data = await r.json();
                 if (r.ok && data.success) {
                     localStorage.removeItem('ecom_cart');
                     window.location.href = data.redirect;
                 } else if (r.status === 422) {
                     this.errors = data.errors ?? {};
                     if (data.message && !Object.keys(data.errors ?? {}).length) {
                         this.errors._general = data.message;
                     }
                 } else {
                     this.errors._general = data.message ?? 'সমস্যা হয়েছে, আবার চেষ্টা করুন।';
                 }
             } catch(e) {
                 this.errors._general = 'সংযোগ সমস্যা। আবার চেষ্টা করুন।';
             }
             this.submitting = false;
         }
     }">

    {{-- Header --}}
    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 flex-shrink-0">
        <div class="flex items-center gap-2">
            <svg class="w-5 h-5 text-textprimary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            <h2 class="font-heading font-bold text-base text-textprimary">Shopping Cart</h2>
            <span class="px-2 py-0.5 bg-primary text-white text-xs font-bold rounded-full" x-text="cartCount"></span>
        </div>
        <button @click="cartOpen = false; document.body.style.overflow = ''; checkoutOpen = false"
                class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100 transition-colors">
            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>

    {{-- Scrollable Body --}}
    <div class="flex-1 overflow-y-auto">

        {{-- Empty State --}}
        <div x-show="cart.length === 0"
             class="flex flex-col items-center justify-center h-full gap-4 px-6 py-16">
            <svg class="w-16 h-16 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            <p class="text-sm text-textsecondary text-center">কার্ট খালি আছে।<br>পণ্য যোগ করুন।</p>
            <button @click="cartOpen = false; document.body.style.overflow = ''"
                    class="px-5 py-2.5 bg-primary text-white text-sm font-semibold rounded-xl hover:bg-opacity-90 transition-all">
                কেনাকাটা শুরু করুন
            </button>
        </div>

        {{-- Cart Items --}}
        <div x-show="cart.length > 0 && !checkoutOpen" class="px-4 py-4 space-y-3">
            <template x-for="item in cart" :key="item.key">
                <div class="flex items-start gap-3 bg-gray-50 rounded-xl p-3">
                    <img :src="item.image || ''" :alt="item.name"
                         class="w-14 rounded-lg object-cover bg-gray-200 flex-shrink-0"
                         style="height:72px; width:56px;"
                         onerror="this.style.display='none'">
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-semibold text-textprimary leading-snug line-clamp-2" x-text="item.name"></p>
                        <p x-show="item.variant_name" class="text-[11px] text-textsecondary mt-0.5" x-text="'Size: ' + item.variant_name"></p>
                        <p class="text-sm font-bold text-accent mt-1" x-text="'৳' + (item.price * item.qty).toLocaleString()"></p>
                        <div class="flex items-center mt-2">
                            <button @click="updateCartItemQty(item.key, item.qty - 1)"
                                    class="w-7 h-7 border border-gray-300 flex items-center justify-center text-sm font-bold text-textprimary hover:bg-gray-100 transition-colors">−</button>
                            <div class="w-8 h-7 border-t border-b border-gray-300 flex items-center justify-center text-xs font-semibold" x-text="item.qty"></div>
                            <button @click="updateCartItemQty(item.key, item.qty + 1)"
                                    class="w-7 h-7 border border-gray-300 flex items-center justify-center text-sm font-bold text-textprimary hover:bg-gray-100 transition-colors">+</button>
                        </div>
                    </div>
                    <button @click="removeFromCart(item.key)"
                            class="w-6 h-6 flex items-center justify-center text-gray-400 hover:text-red-500 transition-colors flex-shrink-0 mt-0.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </template>
        </div>

        {{-- Checkout Form --}}
        <div x-show="cart.length > 0 && checkoutOpen" class="px-4 py-4">
            <button @click="checkoutOpen = false"
                    class="flex items-center gap-1.5 text-xs text-primary font-semibold mb-4 hover:underline">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/>
                </svg>
                কার্টে ফিরুন
            </button>

            <div x-show="errors._general"
                 class="mb-3 px-4 py-3 bg-red-50 border border-red-200 rounded-xl text-sm text-red-600"
                 x-text="errors._general"></div>

            <div class="space-y-3">
                <div>
                    <label class="block text-xs font-semibold text-textprimary mb-1">আপনার নাম *</label>
                    <input type="text" x-model="form.name" placeholder="পূর্ণ নাম লিখুন"
                           class="w-full px-3 py-2.5 text-sm border rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all"
                           :class="errors.name ? 'border-red-400' : 'border-gray-300'">
                    <p x-show="errors.name" class="text-xs text-red-500 mt-1" x-text="(errors.name || [])[0]"></p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-textprimary mb-1">মোবাইল নম্বর *</label>
                    <input type="tel" x-model="form.phone" placeholder="01XXXXXXXXX"
                           class="w-full px-3 py-2.5 text-sm border rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all"
                           :class="errors.phone ? 'border-red-400' : 'border-gray-300'">
                    <p x-show="errors.phone" class="text-xs text-red-500 mt-1" x-text="(errors.phone || [])[0]"></p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-textprimary mb-1">ডেলিভারি ঠিকানা *</label>
                    <textarea x-model="form.address" rows="3" placeholder="বাড়ি, রাস্তা, এলাকা, জেলা..."
                              class="w-full px-3 py-2.5 text-sm border rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all resize-none"
                              :class="errors.address ? 'border-red-400' : 'border-gray-300'"></textarea>
                    <p x-show="errors.address" class="text-xs text-red-500 mt-1" x-text="(errors.address || [])[0]"></p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-textprimary mb-1">নোট (ঐচ্ছিক)</label>
                    <input type="text" x-model="form.note" placeholder="বিশেষ নির্দেশনা থাকলে লিখুন..."
                           class="w-full px-3 py-2.5 text-sm border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all">
                </div>
            </div>
        </div>

    </div>

    {{-- Footer (always visible when cart has items) --}}
    <div x-show="cart.length > 0" class="border-t border-gray-100 px-5 py-4 space-y-3 flex-shrink-0">

        {{-- Order Summary --}}
        <div x-show="!checkoutOpen" class="space-y-1.5 text-sm">
            <div class="flex justify-between text-textsecondary">
                <span>মোট পণ্য</span>
                <span x-text="'৳' + cartSubtotal.toLocaleString()"></span>
            </div>
            <div class="flex justify-between text-textsecondary">
                <span>ডেলিভারি চার্জ</span>
                <span x-text="cartSubtotal >= {{ (int) $cartFreeAbove }} ? 'ফ্রি ✓' : '৳{{ (int) $cartShippingCharge }}'"></span>
            </div>
            <div class="flex justify-between font-bold text-textprimary pt-2 border-t border-gray-100">
                <span>সর্বমোট</span>
                <span x-text="'৳' + (cartSubtotal >= {{ (int) $cartFreeAbove }} ? cartSubtotal : cartSubtotal + {{ (int) $cartShippingCharge }}).toLocaleString()"></span>
            </div>
        </div>

        {{-- Checkout button --}}
        <div x-show="!checkoutOpen">
            <button @click="checkoutOpen = true"
                    class="w-full py-3.5 bg-accent hover:bg-yellow-500 active:scale-[0.98] text-white font-bold text-sm rounded-xl transition-all">
                Checkout করুন →
            </button>
        </div>

        {{-- Place Order button --}}
        <div x-show="checkoutOpen">
            <button @click="submitCart()"
                    :disabled="submitting"
                    class="w-full py-3.5 bg-accent hover:bg-yellow-500 active:scale-[0.98] text-white font-bold text-sm rounded-xl transition-all disabled:opacity-60 flex items-center justify-center gap-2">
                <svg x-show="submitting" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <span x-text="submitting ? 'অর্ডার হচ্ছে...' : 'অর্ডার নিশ্চিত করুন'"></span>
            </button>
        </div>

    </div>

</div>
