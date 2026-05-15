@extends('layouts.admin')

@section('title', 'Create New Order')
@section('page-title', 'Create Order')

@section('breadcrumb')
    <span class="text-textsecondary">/</span>
    <a href="{{ route('admin.orders.index') }}" class="hover:text-primary transition-colors">Orders</a>
    <span class="text-textsecondary">/</span>
    <span class="text-textprimary font-medium">Create New</span>
@endsection

@section('content')

@if($errors->any())
<div class="mb-4 p-4 rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800">
    <ul class="text-sm text-red-700 dark:text-red-400 space-y-1">
        @foreach($errors->all() as $error)
            <li>• {{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<form method="POST" action="{{ route('admin.orders.store') }}"
      x-data="orderCreate()"
      @submit.prevent="submitOrder">
    @csrf

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

        {{-- LEFT --}}
        <div class="lg:col-span-2 space-y-5">

            {{-- Customer Info --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-border dark:border-gray-700 shadow-sm p-5">
                <h3 class="font-heading font-semibold text-sm text-textprimary dark:text-gray-100 mb-4">Customer Information</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-textsecondary dark:text-gray-400 mb-1.5">Phone Number *</label>
                        <div class="flex gap-2">
                            <input type="text" name="shipping_phone" x-model="phone" placeholder="01XXXXXXXXX"
                                   class="flex-1 px-3 py-2 text-sm rounded-xl border border-border dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary" required>
                            <button type="button" @click="searchCustomer"
                                    class="px-3 py-2 text-xs font-semibold bg-primary text-white rounded-xl hover:bg-primary/90 transition-colors whitespace-nowrap">Search</button>
                        </div>
                        <p x-show="customerFound" class="text-[11px] text-green-600 dark:text-green-400 mt-1">
                            <svg class="w-3 h-3 inline" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/></svg>
                            Existing customer — address filled
                        </p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-textsecondary dark:text-gray-400 mb-1.5">Customer Name *</label>
                        <input type="text" name="shipping_name" x-model="customerName" placeholder="Full name"
                               class="w-full px-3 py-2 text-sm rounded-xl border border-border dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary" required>
                    </div>
                </div>
            </div>

            {{-- Shipping Address --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-border dark:border-gray-700 shadow-sm p-5">
                <h3 class="font-heading font-semibold text-sm text-textprimary dark:text-gray-100 mb-4">Shipping Address</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-textsecondary dark:text-gray-400 mb-1.5">Division</label>
                        <select name="shipping_division" x-model="division"
                                class="w-full px-3 py-2 text-sm rounded-xl border border-border dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30">
                            <option value="">Select Division</option>
                            @foreach(['Dhaka','Chattogram','Rajshahi','Khulna','Barishal','Sylhet','Rangpur','Mymensingh'] as $div)
                                <option value="{{ $div }}">{{ $div }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-textsecondary dark:text-gray-400 mb-1.5">District</label>
                        <input type="text" name="shipping_district" x-model="district" placeholder="e.g. Dhaka"
                               class="w-full px-3 py-2 text-sm rounded-xl border border-border dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-textsecondary dark:text-gray-400 mb-1.5">Area / Thana</label>
                        <input type="text" name="shipping_area" x-model="area" placeholder="e.g. Mirpur-10"
                               class="w-full px-3 py-2 text-sm rounded-xl border border-border dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-textsecondary dark:text-gray-400 mb-1.5">Full Address</label>
                        <input type="text" name="shipping_address" x-model="address" placeholder="House, Road, Block…"
                               class="w-full px-3 py-2 text-sm rounded-xl border border-border dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30">
                    </div>
                </div>
            </div>

            {{-- Products --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-border dark:border-gray-700 shadow-sm p-5">
                <h3 class="font-heading font-semibold text-sm text-textprimary dark:text-gray-100 mb-4">Order Items</h3>

                {{-- Product search --}}
                <div class="relative mb-4" x-data="{ focused: false }">
                    <input type="text" x-model="productSearch"
                           @input.debounce.300ms="searchProducts"
                           @focus="focused = true"
                           @blur.window="setTimeout(() => focused = false, 200)"
                           placeholder="Search product by name or SKU…"
                           class="w-full px-3 py-2 text-sm rounded-xl border border-border dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30">
                    <div x-show="focused && productResults.length > 0"
                         class="absolute z-20 mt-1 w-full bg-white dark:bg-gray-800 border border-border dark:border-gray-700 rounded-xl shadow-lg max-h-64 overflow-y-auto">
                        <template x-for="p in productResults" :key="p.id">
                            <div @click="addProduct(p)"
                                 class="flex items-center gap-3 px-3 py-2.5 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer">
                                <div class="w-8 h-8 rounded-lg bg-gray-100 dark:bg-gray-700 flex-shrink-0 overflow-hidden">
                                    <img x-show="p.primary_image" :src="'/storage/' + p.primary_image" class="w-full h-full object-cover">
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium text-textprimary dark:text-gray-100 truncate" x-text="p.name"></p>
                                    <p class="text-xs text-textsecondary dark:text-gray-400">৳<span x-text="p.current_price"></span></p>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Items table --}}
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="border-b border-border dark:border-gray-700">
                            <tr>
                                <th class="pb-2 text-left text-xs font-semibold text-gray-500 dark:text-gray-400">Product</th>
                                <th class="pb-2 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 w-32">Variant</th>
                                <th class="pb-2 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 w-24">Qty</th>
                                <th class="pb-2 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 w-24">Price</th>
                                <th class="pb-2 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 w-24">Total</th>
                                <th class="pb-2 w-8"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border dark:divide-gray-700">
                            <template x-for="(item, i) in items" :key="i">
                                <tr>
                                    <td class="py-2.5 pr-2">
                                        <input type="hidden" :name="'items[' + i + '][product_id]'" :value="item.product_id">
                                        <input type="hidden" :name="'items[' + i + '][product_name]'" :value="item.product_name">
                                        <input type="hidden" :name="'items[' + i + '][variant_info]'" :value="item.variant_info">
                                        <p class="text-sm font-medium text-textprimary dark:text-gray-100" x-text="item.product_name"></p>
                                    </td>
                                    <td class="py-2.5 px-1">
                                        <input type="hidden" :name="'items[' + i + '][variant_id]'" :value="item.variant_id">
                                        <select x-show="item.variants.length > 0"
                                                @change="selectVariant(i, $event.target.value)"
                                                class="w-full text-xs px-2 py-1 rounded-lg border border-border dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 text-textprimary dark:text-gray-100 focus:outline-none">
                                            <option value="">No variant</option>
                                            <template x-for="v in item.variants" :key="v.id">
                                                <option :value="v.id" x-text="v.label"></option>
                                            </template>
                                        </select>
                                        <span x-show="item.variants.length === 0" class="text-xs text-textsecondary dark:text-gray-400">—</span>
                                    </td>
                                    <td class="py-2.5 px-1">
                                        <input type="hidden" :name="'items[' + i + '][qty]'" :value="item.qty">
                                        <div class="flex items-center gap-1 justify-center">
                                            <button type="button" @click="item.qty = Math.max(1, item.qty - 1); calcTotals()"
                                                    class="w-6 h-6 rounded-lg bg-gray-100 dark:bg-gray-700 text-textprimary dark:text-gray-200 flex items-center justify-center text-sm font-bold hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">−</button>
                                            <span class="text-sm font-semibold text-textprimary dark:text-gray-100 w-6 text-center" x-text="item.qty"></span>
                                            <button type="button" @click="item.qty++; calcTotals()"
                                                    class="w-6 h-6 rounded-lg bg-gray-100 dark:bg-gray-700 text-textprimary dark:text-gray-200 flex items-center justify-center text-sm font-bold hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">+</button>
                                        </div>
                                    </td>
                                    <td class="py-2.5 px-1">
                                        <input type="hidden" :name="'items[' + i + '][unit_price]'" :value="item.unit_price">
                                        <input type="number" x-model="item.unit_price" @input="calcTotals" min="0"
                                               class="w-full text-xs px-2 py-1 rounded-lg border border-border dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 text-textprimary dark:text-gray-100 text-right focus:outline-none">
                                    </td>
                                    <td class="py-2.5 pl-1 text-right text-sm font-semibold text-textprimary dark:text-gray-100">
                                        ৳<span x-text="(item.unit_price * item.qty).toLocaleString()"></span>
                                    </td>
                                    <td class="py-2.5 pl-1">
                                        <button type="button" @click="removeItem(i)"
                                                class="text-gray-300 hover:text-red-400 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                            <tr x-show="items.length === 0">
                                <td colspan="6" class="py-6 text-center text-sm text-textsecondary dark:text-gray-400">Search and add products above</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Notes & Source --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-border dark:border-gray-700 shadow-sm p-5">
                <h3 class="font-heading font-semibold text-sm text-textprimary dark:text-gray-100 mb-4">Additional Info</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-textsecondary dark:text-gray-400 mb-1.5">Order Source</label>
                        <select name="source"
                                class="w-full px-3 py-2 text-sm rounded-xl border border-border dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30">
                            <option value="manual">Manual Entry</option>
                            <option value="phone">Phone Call</option>
                            <option value="whatsapp">WhatsApp</option>
                            <option value="facebook">Facebook</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-textsecondary dark:text-gray-400 mb-1.5">Order Tag</label>
                        <input type="text" name="order_tag" placeholder="e.g. vip, reseller"
                               class="w-full px-3 py-2 text-sm rounded-xl border border-border dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-medium text-textsecondary dark:text-gray-400 mb-1.5">Order Notes</label>
                        <textarea name="notes" rows="3" placeholder="Internal order notes…"
                                  class="w-full px-3 py-2 text-sm rounded-xl border border-border dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30 resize-none"></textarea>
                    </div>
                </div>
            </div>

        </div>

        {{-- RIGHT --}}
        <div class="space-y-5">

            {{-- Order Summary --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-border dark:border-gray-700 shadow-sm p-5">
                <h3 class="font-heading font-semibold text-sm text-textprimary dark:text-gray-100 mb-4">Order Summary</h3>
                <div class="space-y-3">
                    {{-- Hidden fields for calculated values --}}
                    <input type="hidden" name="subtotal" :value="subtotal">
                    <input type="hidden" name="total" :value="grandTotal">
                    <input type="hidden" name="discount" :value="discount">

                    <div class="flex justify-between text-sm">
                        <span class="text-textsecondary dark:text-gray-400">Subtotal</span>
                        <span class="font-semibold text-textprimary dark:text-gray-100">৳<span x-text="subtotal.toLocaleString()"></span></span>
                    </div>
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-textsecondary dark:text-gray-400">Discount</span>
                            <span class="text-green-600 dark:text-green-400" x-show="discount > 0">-৳<span x-text="discount.toLocaleString()"></span></span>
                            <span class="text-textsecondary dark:text-gray-400" x-show="discount === 0">—</span>
                        </div>
                        <div class="flex gap-2">
                            <input type="number" x-model.number="discount" @input="calcTotals" min="0" placeholder="0"
                                   class="flex-1 px-2 py-1.5 text-xs rounded-lg border border-border dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 text-textprimary dark:text-gray-100 focus:outline-none">
                        </div>
                    </div>
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-textsecondary dark:text-gray-400">Shipping</span>
                            <span class="text-textprimary dark:text-gray-100">৳<span x-text="shipping.toLocaleString()"></span></span>
                        </div>
                        <input type="number" name="shipping_charge" x-model.number="shipping" @input="calcTotals" min="0" placeholder="0"
                               class="w-full px-2 py-1.5 text-xs rounded-lg border border-border dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 text-textprimary dark:text-gray-100 focus:outline-none">
                    </div>
                    <div class="border-t border-border dark:border-gray-700 pt-3 flex justify-between">
                        <span class="text-sm font-bold text-textprimary dark:text-gray-100">Total</span>
                        <span class="text-lg font-bold text-primary dark:text-blue-400">৳<span x-text="grandTotal.toLocaleString()"></span></span>
                    </div>
                </div>
            </div>

            {{-- Payment --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-border dark:border-gray-700 shadow-sm p-5">
                <h3 class="font-heading font-semibold text-sm text-textprimary dark:text-gray-100 mb-4">Payment</h3>
                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-medium text-textsecondary dark:text-gray-400 mb-1.5">Payment Method</label>
                        <select name="payment_method"
                                class="w-full px-3 py-2 text-sm rounded-xl border border-border dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30">
                            <option value="cod">COD (Cash on Delivery)</option>
                            <option value="bkash">bKash</option>
                            <option value="nagad">Nagad</option>
                            <option value="card">Card</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-textsecondary dark:text-gray-400 mb-1.5">Payment Status</label>
                        <select name="payment_status"
                                class="w-full px-3 py-2 text-sm rounded-xl border border-border dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30">
                            <option value="unpaid">Unpaid</option>
                            <option value="paid">Paid</option>
                        </select>
                    </div>
                </div>
            </div>

            {{-- Actions --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-border dark:border-gray-700 shadow-sm p-5 space-y-3">
                <button type="submit"
                        class="w-full py-2.5 text-sm font-semibold bg-primary text-white rounded-xl hover:bg-primary/90 transition-colors">
                    Confirm Order
                </button>
                <a href="{{ route('admin.orders.index') }}"
                   class="w-full py-2.5 text-sm font-medium border border-border dark:border-gray-600 text-textsecondary dark:text-gray-400 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors text-center block">
                    Cancel
                </a>
            </div>

        </div>

    </div>

</form>

@endsection

@push('scripts')
<script>
function orderCreate() {
    return {
        phone: '',
        customerName: '',
        customerFound: false,
        division: '',
        district: '',
        area: '',
        address: '',
        productSearch: '',
        productResults: [],
        items: [],
        subtotal: 0,
        discount: 0,
        shipping: 60,
        grandTotal: 60,

        async searchCustomer() {
            if (this.phone.length < 10) return;
            try {
                const r = await fetch(`/admin/orders/search-customer?phone=${encodeURIComponent(this.phone)}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await r.json();
                if (data.found) {
                    this.customerName  = data.name;
                    this.division      = data.division;
                    this.district      = data.district;
                    this.area          = data.area;
                    this.address       = data.address;
                    this.customerFound = true;
                }
            } catch (e) { console.error(e); }
        },

        async searchProducts() {
            if (!this.productSearch) { this.productResults = []; return; }
            try {
                const r = await fetch(`/admin/orders/search-products?q=${encodeURIComponent(this.productSearch)}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                this.productResults = await r.json();
            } catch (e) { this.productResults = []; }
        },

        addProduct(p) {
            const price = parseFloat(p.current_price || p.sale_price || p.price || 0);
            this.items.push({
                product_id:   p.id,
                product_name: p.name,
                variants:     p.variants || [],
                variant_id:   '',
                variant_info: '',
                qty:          1,
                unit_price:   price,
            });
            this.productSearch  = '';
            this.productResults = [];
            this.calcTotals();
        },

        selectVariant(i, variantId) {
            const v = this.items[i].variants.find(v => String(v.id) === String(variantId));
            if (v) {
                this.items[i].variant_id   = v.id;
                this.items[i].variant_info = v.label;
                if (v.price) this.items[i].unit_price = parseFloat(v.price);
                this.calcTotals();
            }
        },

        removeItem(i) {
            this.items.splice(i, 1);
            this.calcTotals();
        },

        calcTotals() {
            this.subtotal   = this.items.reduce((s, i) => s + (parseFloat(i.unit_price) * parseInt(i.qty)), 0);
            this.grandTotal = Math.max(0, this.subtotal - parseFloat(this.discount || 0) + parseFloat(this.shipping || 0));
        },

        submitOrder() {
            if (this.items.length === 0) {
                alert('Please add at least one product.');
                return;
            }
            this.$el.submit();
        },
    };
}
</script>
@endpush
