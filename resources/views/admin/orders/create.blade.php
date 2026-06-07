@extends('layouts.admin')

@section('title', 'Create New Order')
@section('page-title', 'Create Order')

@section('content')

{{-- ALERTS --}}
@if(session('success'))
<div class="mb-4 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-2xl text-green-700 dark:text-green-400 text-sm">
    {{ session('success') }}
</div>
@endif
@if(session('error'))
<div class="mb-4 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-2xl text-red-700 dark:text-red-400 text-sm">
    {{ session('error') }}
</div>
@endif
@if($errors->any())
<div class="mb-4 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-2xl">
    <ul class="text-sm text-red-700 dark:text-red-400 space-y-1">
        @foreach($errors->all() as $error)
            <li>• {{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

{{-- MAIN FORM --}}
<form action="{{ route('admin.orders.store') }}" method="POST" enctype="multipart/form-data" id="orderForm">
@csrf

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6" x-data="orderCalc()" x-init="init()">

{{-- ══════════════ LEFT COLUMN (2/3) ══════════════ --}}
<div class="lg:col-span-2 space-y-6">

{{-- CARD: SHIPPING ADDRESS --}}
<div class="bg-white dark:bg-gray-900 rounded-2xl shadow p-6">
    <h3 class="text-base font-semibold text-[#1B2A4A] dark:text-white mb-4 flex items-center gap-2">
        <span class="w-8 h-8 bg-[#1B2A4A]/10 rounded-lg flex items-center justify-center text-[#1B2A4A] dark:bg-[#1B2A4A]/30 dark:text-white text-xs">📦</span>
        Shipping Address
    </h3>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
        {{-- Mobile Number --}}
        <div class="sm:col-span-1">
            <label class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1 block">Mobile Number*</label>
            <input type="text" name="primary_mobile" id="mobileInput" required maxlength="11"
                oninput="if(this.value.length===11) lookupCustomer(this.value)"
                placeholder="01XXXXXXXXX"
                class="w-full rounded-xl border border-gray-200 dark:border-gray-700 px-3 py-2 text-sm bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#1B2A4A]">
            <p id="lookupStatus" class="text-xs mt-1 hidden"></p>
        </div>

        {{-- Alternative Number --}}
        <div>
            <label class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1 block">Alternative Number</label>
            <input type="text" name="alternative_mobile" id="altMobileInput" maxlength="11"
                class="w-full rounded-xl border border-gray-200 dark:border-gray-700 px-3 py-2 text-sm bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#1B2A4A]">
        </div>

        {{-- Full Name --}}
        <div>
            <label class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1 block">Full Name*</label>
            <input type="text" name="full_name" id="fullNameInput" required
                class="w-full rounded-xl border border-gray-200 dark:border-gray-700 px-3 py-2 text-sm bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#1B2A4A]">
        </div>

        {{-- Email --}}
        <div>
            <label class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1 block">Email</label>
            <input type="email" name="email" id="emailInput"
                class="w-full rounded-xl border border-gray-200 dark:border-gray-700 px-3 py-2 text-sm bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#1B2A4A]">
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
        {{-- Address --}}
        <div>
            <label class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1 block">Address</label>
            <textarea name="address" id="addressInput" rows="2"
                class="w-full rounded-xl border border-gray-200 dark:border-gray-700 px-3 py-2 text-sm bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#1B2A4A] resize-none"></textarea>
        </div>

        <div class="space-y-3">
            {{-- District --}}
            <div>
                <label class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1 block">District</label>
                <select name="district" id="districtInput"
                    class="w-full rounded-xl border border-gray-200 dark:border-gray-700 px-3 py-2 text-sm bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#1B2A4A]">
                    <option value="">Select District</option>
                    @foreach($districts as $d)
                    <option value="{{ $d }}">{{ $d }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    {{-- Customer Insight inline row --}}
    <div class="flex flex-wrap items-center gap-4 mt-4 pt-4 border-t dark:border-gray-700">
        <div class="flex items-center gap-1 text-sm" id="prevOrdersBlock" style="display:none">
            <span class="text-gray-500 dark:text-gray-400 text-xs">Total Orders:</span>
            <span class="font-bold text-[#1B2A4A] dark:text-white" id="prevOrderCount">0</span>
            <a id="prevOrderLink" href="#" class="text-xs text-[#F59E0B] hover:underline ml-1" target="_blank">View All ↗</a>
        </div>
        <div class="flex items-center gap-1 text-sm" id="prevCompletedBlock" style="display:none">
            <span class="text-gray-500 dark:text-gray-400 text-xs">Completed:</span>
            <span class="font-bold text-green-600" id="prevCompletedCount">0</span>
        </div>
        <div class="flex items-center gap-2 ml-auto">
            <select name="order_tag" class="rounded-lg border border-gray-200 dark:border-gray-700 px-2 py-1 text-xs bg-white dark:bg-gray-800 dark:text-white">
                <option value="">Order Tag</option>
                <option value="vip">VIP</option>
                <option value="regular">Regular</option>
                <option value="problematic">Problematic</option>
            </select>
            <select name="customer_tag" class="rounded-lg border border-gray-200 dark:border-gray-700 px-2 py-1 text-xs bg-white dark:bg-gray-800 dark:text-white">
                <option value="">Customer Tag</option>
                <option value="loyal">Loyal</option>
                <option value="new">New</option>
                <option value="fraud">Fraud Risk</option>
            </select>
        </div>
    </div>
</div>

{{-- CARD: ORDER PRODUCTS --}}
<div class="bg-white dark:bg-gray-900 rounded-2xl shadow p-6">
    <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
        <h3 class="text-base font-semibold text-[#1B2A4A] dark:text-white flex items-center gap-2">🛒 Order Products</h3>
        <button type="button" onclick="openProductModal()"
            class="bg-[#F59E0B] text-white px-4 py-2 rounded-xl text-xs font-medium hover:bg-yellow-500 transition">
            + Add Item
        </button>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm" id="productsTable">
            <thead>
                <tr class="text-xs text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-800 rounded-xl">
                    <th class="text-left px-3 py-2 rounded-l-xl">Product</th>
                    <th class="text-center px-3 py-2">Unit Price</th>
                    <th class="text-center px-3 py-2">Qty</th>
                    <th class="text-center px-3 py-2">Stock</th>
                    <th class="text-center px-3 py-2">Discount</th>
                    <th class="text-center px-3 py-2">Subtotal</th>
                    <th class="text-center px-3 py-2 rounded-r-xl">Action</th>
                </tr>
            </thead>
            <tbody id="productRows">
                <tr id="emptyRow">
                    <td colspan="7" class="text-center py-8 text-gray-400 text-sm">No items yet. Click "+ Add Item" to search and add products.</td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- Order Logistics --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-6 pt-4 border-t dark:border-gray-700">
        <div>
            <label class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1 block">Order Status*</label>
            <select name="order_status" required
                class="w-full rounded-xl border border-gray-200 dark:border-gray-700 px-3 py-2 text-sm bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#1B2A4A]">
                @foreach(['pending'=>'Pending','confirmed'=>'Confirmed','processing'=>'Processing','shipped'=>'Shipped','delivered'=>'Delivered','cancelled'=>'Cancelled','hold'=>'Hold','failed'=>'Failed','returned'=>'Returned','damage'=>'Damage','hand_delivery'=>'Hand Delivery','completed'=>'Completed'] as $s => $l)
                <option value="{{ $s }}" {{ $s === 'pending' ? 'selected' : '' }}>{{ $l }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1 block">Payment Method</label>
            <select name="payment_method"
                class="w-full rounded-xl border border-gray-200 dark:border-gray-700 px-3 py-2 text-sm bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#1B2A4A]">
                @foreach(['cod'=>'Cash on Delivery','bkash'=>'bKash','nagad'=>'Nagad','rocket'=>'Rocket','card'=>'Bank Transfer'] as $v=>$l)
                <option value="{{ $v }}">{{ $l }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1 block">Courier Note</label>
            <textarea name="courier_note" rows="2"
                class="w-full rounded-xl border border-gray-200 dark:border-gray-700 px-3 py-2 text-sm bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#1B2A4A] resize-none"></textarea>
        </div>
        <div>
            <label class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1 block">Packing Note</label>
            <textarea name="packing_note" rows="2"
                class="w-full rounded-xl border border-gray-200 dark:border-gray-700 px-3 py-2 text-sm bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#1B2A4A] resize-none"></textarea>
        </div>
        <div class="sm:col-span-2">
            <label class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1 block">Attachments</label>
            <input type="file" name="attachments[]" multiple accept="image/*,.pdf"
                class="w-full rounded-xl border border-gray-200 dark:border-gray-700 px-3 py-2 text-sm bg-white dark:bg-gray-800 dark:text-white">
        </div>
    </div>
</div>

</div>{{-- END LEFT COLUMN --}}

{{-- ══════════════ RIGHT COLUMN: SUMMARY SIDEBAR ══════════════ --}}
<div class="lg:col-span-1">
<div class="bg-white dark:bg-gray-900 rounded-2xl shadow p-6 lg:sticky lg:top-4 space-y-4">

    {{-- Summary Header --}}
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-bold text-gray-800 dark:text-white">Summary</h3>
    </div>

    {{-- Assigned To --}}
    <div class="mb-4">
        <label class="text-sm font-bold text-gray-800 dark:text-white block mb-1">Assigned To</label>
        <select name="assigned_to" class="w-full rounded-lg border border-gray-200 dark:border-gray-700 px-2 py-1.5 text-sm bg-white dark:bg-gray-800 dark:text-white">
            <option value="">Unassigned</option>
            @foreach($admins as $admin)
            <option value="{{ $admin->id }}" {{ $admin->id === auth()->id() ? 'selected' : '' }}>{{ $admin->name }}</option>
            @endforeach
        </select>
    </div>

    {{-- Reference No --}}
    <div class="mb-1">
        <label class="text-sm font-bold text-gray-800 dark:text-white block mb-1">Reference No</label>
        <input type="text" name="reference_no"
            class="w-full rounded-lg border border-gray-200 dark:border-gray-700 px-3 py-1.5 text-sm bg-white dark:bg-gray-800 dark:text-white">
    </div>

    {{-- Source Website --}}
    <div class="mb-4">
        <label class="text-sm font-bold text-gray-800 dark:text-white block mb-1">Source Website</label>
        <select name="source_website"
            class="w-full rounded-lg border border-gray-200 dark:border-gray-700 px-2 py-1.5 text-sm bg-white dark:bg-gray-800 dark:text-white">
            <option value="">Select Source</option>
            <option value="manual" selected>Manual Entry</option>
            <option value="facebook">Facebook</option>
            <option value="instagram">Instagram</option>
            <option value="whatsapp">WhatsApp</option>
            <option value="phone">Phone Call</option>
            <option value="website">Website</option>
        </select>
    </div>

    {{-- Financial Table --}}
    <table class="w-full text-sm mb-4 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
        <tbody>
            <tr class="border-b border-gray-200 dark:border-gray-700">
                <td class="px-3 py-2 font-bold text-gray-800 dark:text-white bg-gray-50 dark:bg-gray-800">Subtotal:</td>
                <td class="px-3 py-2 text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-900" x-text="'৳' + subtotal.toFixed(2) + ' Tk'">৳0.00 Tk</td>
            </tr>
            <input type="hidden" name="subtotal" :value="subtotal">
            <tr class="border-b border-gray-200 dark:border-gray-700">
                <td class="px-3 py-2 font-bold text-gray-800 dark:text-white bg-gray-50 dark:bg-gray-800">
                    Discount:
                    <button type="button" @click="discountType = discountType === 'fixed' ? 'percent' : 'fixed'"
                        class="ml-1 px-1.5 py-0.5 rounded border border-gray-300 dark:border-gray-600 text-xs dark:text-white"
                        x-text="discountType === 'fixed' ? '৳' : '%'">৳</button>
                </td>
                <td class="px-3 py-2 bg-white dark:bg-gray-900">
                    <input type="number" name="discount_value" x-model="discountValue" step="0.01" min="0"
                        class="w-full rounded border border-gray-200 dark:border-gray-700 px-2 py-1 text-sm bg-white dark:bg-gray-800 dark:text-white text-right">
                    <input type="hidden" name="discount_type" :value="discountType">
                </td>
            </tr>
            <tr class="border-b border-gray-200 dark:border-gray-700">
                <td class="px-3 py-2 font-bold text-gray-800 dark:text-white bg-gray-50 dark:bg-gray-800">Shipping Charge:</td>
                <td class="px-3 py-2 bg-white dark:bg-gray-900">
                    <input type="number" name="shipping_charge" x-model="shipping" step="0.01" min="0"
                        class="w-full rounded border border-gray-200 dark:border-gray-700 px-2 py-1 text-sm bg-white dark:bg-gray-800 dark:text-white text-right">
                </td>
            </tr>
            <tr class="border-b border-gray-200 dark:border-gray-700">
                <td class="px-3 py-2 font-bold text-gray-800 dark:text-white bg-gray-50 dark:bg-gray-800">Grand Total:</td>
                <td class="px-3 py-2 font-bold text-gray-800 dark:text-white bg-white dark:bg-gray-900" x-text="'৳' + grandTotal.toFixed(2) + ' Tk'"></td>
                <input type="hidden" name="grand_total" :value="grandTotal">
            </tr>
            <tr class="border-b border-gray-200 dark:border-gray-700">
                <td class="px-3 py-2 font-bold text-gray-800 dark:text-white bg-gray-50 dark:bg-gray-800">Paid Amount:</td>
                <td class="px-3 py-2 bg-white dark:bg-gray-900">
                    <input type="number" name="paid_amount" x-model="paid" step="0.01" min="0"
                        class="w-full rounded border border-gray-200 dark:border-gray-700 px-2 py-1 text-sm bg-white dark:bg-gray-800 dark:text-white text-right mb-1">
                </td>
            </tr>
            <tr class="border-b border-gray-200 dark:border-gray-700">
                <td class="px-3 py-2 font-bold text-gray-800 dark:text-white bg-gray-50 dark:bg-gray-800">Due:</td>
                <td class="px-3 py-2 font-bold bg-white dark:bg-gray-900" :class="due > 0 ? 'text-red-500' : 'text-green-500'" x-text="'৳' + due.toFixed(2) + ' Tk'"></td>
                <input type="hidden" name="due" :value="due">
            </tr>
            <tr>
                <td class="px-3 py-2 font-bold text-gray-800 dark:text-white bg-gray-50 dark:bg-gray-800">Courier Charge:</td>
                <td class="px-3 py-2 bg-white dark:bg-gray-900">
                    <input type="number" name="courier_charge" x-model="courierCharge" step="0.01" min="0"
                        class="w-full rounded border border-gray-200 dark:border-gray-700 px-2 py-1 text-sm bg-white dark:bg-gray-800 dark:text-white text-right">
                </td>
            </tr>
        </tbody>
    </table>

    {{-- Notes --}}
    <div class="mb-4">
        <label class="text-sm font-bold text-gray-800 dark:text-white block mb-1">Order Notes</label>
        <textarea name="notes" rows="2" placeholder="Internal notes..."
            class="w-full rounded-lg border border-gray-200 dark:border-gray-700 px-3 py-1.5 text-sm bg-white dark:bg-gray-800 dark:text-white resize-none focus:outline-none focus:ring-2 focus:ring-[#1B2A4A]"></textarea>
    </div>

    {{-- Skip Followup --}}
    <label class="flex items-center gap-2 cursor-pointer mb-4">
        <input type="checkbox" name="skip_followup" value="1"
            class="rounded border-gray-300 text-[#1B2A4A]">
        <span class="text-sm text-gray-600 dark:text-gray-400">Skip/Done Followup</span>
    </label>

    {{-- Buttons --}}
    <button type="submit" onclick="return validateOrder()"
        class="w-full bg-[#1B2A4A] text-white py-3 rounded-xl text-sm font-semibold hover:bg-[#243a63] transition mb-2">
        ✔ Create Order
    </button>
    <p class="text-xs text-gray-400 mb-2">NB: (*) marked are required field.</p>
    <a href="{{ route('admin.orders.index') }}"
        class="w-full block text-center border border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-400 py-2.5 rounded-xl text-sm hover:bg-gray-50 dark:hover:bg-gray-800 transition">
        Cancel
    </a>

</div>
</div>

</div>{{-- END MAIN GRID --}}
</form>

{{-- PRODUCT SEARCH MODAL --}}
<div id="productModal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4">
    <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-xl w-full max-w-lg p-6">
        <div class="flex items-center justify-between mb-4">
            <h4 class="font-semibold text-[#1B2A4A] dark:text-white">Add Product</h4>
            <button type="button" onclick="closeProductModal()" class="text-gray-400 hover:text-gray-600 text-xl">×</button>
        </div>
        <input type="text" id="productSearchInput" placeholder="Search product name or SKU..."
            class="w-full rounded-xl border border-gray-200 dark:border-gray-700 px-4 py-2 text-sm mb-4 bg-white dark:bg-gray-800 dark:text-white focus:outline-none"
            oninput="searchProducts(this.value)">
        <div id="productSearchResults" class="max-h-64 overflow-y-auto space-y-2">
            <p class="text-center text-gray-400 text-sm py-4">Loading products...</p>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
const SEARCH_URL = '/admin/orders/search-products';
const CUSTOMER_URL = '/admin/orders/search-customer';
const CSRF = document.querySelector('meta[name="csrf-token"]').content;

// ─── Alpine.js Calculator ─────────────────────────────────────────────────────
function orderCalc() {
    return {
        subtotal:      0,
        discountType:  'fixed',
        discountValue: 0,
        shipping:      60,
        paid:          0,
        courierCharge: 0,

        get discount() {
            return this.discountType === 'percent'
                ? (this.subtotal * this.discountValue / 100)
                : (parseFloat(this.discountValue) || 0);
        },
        get grandTotal() {
            return Math.max(0, this.subtotal - this.discount + (parseFloat(this.shipping) || 0));
        },
        get due() {
            return Math.max(0, this.grandTotal - (parseFloat(this.paid) || 0));
        },
        init() {
            window.updateSubtotal = (val) => { this.subtotal = val; };
        }
    };
}

// ─── Customer Lookup ──────────────────────────────────────────────────────────
function lookupCustomer(phone) {
    const status = document.getElementById('lookupStatus');
    status.className = 'text-xs mt-1 text-gray-400';
    status.textContent = 'Looking up...';
    status.classList.remove('hidden');

    fetch(CUSTOMER_URL + '?phone=' + encodeURIComponent(phone), {
        headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'}
    })
    .then(r => r.json())
    .then(data => {
        if (data.found) {
            document.getElementById('fullNameInput').value  = data.name || '';
            document.getElementById('addressInput').value   = data.address || '';

            const districtSel = document.getElementById('districtInput');
            if (data.district) {
                for (let opt of districtSel.options) {
                    if (opt.value === data.district) { opt.selected = true; break; }
                }
            }

            status.className = 'text-xs mt-1 text-green-600 dark:text-green-400';
            status.textContent = '✓ Existing customer — address filled';

            const prevBlock = document.getElementById('prevOrdersBlock');
            const prevCompleted = document.getElementById('prevCompletedBlock');
            if (data.total_orders > 0) {
                prevBlock.style.display = '';
                prevCompleted.style.display = '';
                document.getElementById('prevOrderCount').textContent = data.total_orders;
                document.getElementById('prevOrderLink').href = '/admin/orders?search=' + encodeURIComponent(phone);
            }
        } else {
            status.className = 'text-xs mt-1 text-blue-500';
            status.textContent = '+ New customer';
        }
    })
    .catch(() => {
        status.className = 'text-xs mt-1 text-red-500';
        status.textContent = 'Lookup failed';
    });
}

// ─── Product Row Calculations ─────────────────────────────────────────────────
function recalcRow(el) {
    const row = el.closest('tr');
    const price    = parseFloat(row.querySelector('.item-price').value) || 0;
    const qty      = parseInt(row.querySelector('.item-qty').value) || 1;
    const discount = parseFloat(row.querySelector('.item-discount').value) || 0;
    const sub      = (price * qty) - discount;
    row.querySelector('.item-subtotal').textContent = '৳' + sub.toFixed(2);
    recalcAllSubtotals();
}

function recalcAllSubtotals() {
    let total = 0;
    document.querySelectorAll('#productRows .product-row').forEach(row => {
        const text = row.querySelector('.item-subtotal').textContent.replace('৳','').replace(/,/g,'');
        total += parseFloat(text) || 0;
    });
    if (window.updateSubtotal) window.updateSubtotal(total);
}

function removeProductRow(btn) {
    btn.closest('tr').remove();
    const rows = document.querySelectorAll('#productRows .product-row');
    if (rows.length === 0) {
        document.getElementById('productRows').innerHTML = `
            <tr id="emptyRow">
                <td colspan="7" class="text-center py-8 text-gray-400 text-sm">No items yet. Click "+ Add Item" to search and add products.</td>
            </tr>`;
    }
    recalcAllSubtotals();
}

// ─── Product Search Modal ─────────────────────────────────────────────────────
function openProductModal() {
    const m = document.getElementById('productModal');
    m.classList.remove('hidden'); m.classList.add('flex');
    const input = document.getElementById('productSearchInput');
    input.value = '';
    input.focus();
    loadProducts('');
}

function closeProductModal() {
    const m = document.getElementById('productModal');
    m.classList.add('hidden'); m.classList.remove('flex');
}

let searchTimeout;
function searchProducts(q) {
    clearTimeout(searchTimeout);
    if (q === '') { loadProducts(''); return; }
    if (q.length < 1) return;
    searchTimeout = setTimeout(() => loadProducts(q), 300);
}

function loadProducts(q) {
    fetch(SEARCH_URL + '?q=' + encodeURIComponent(q), {
        headers: {'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json'}
    })
    .then(r => r.json())
    .then(products => {
        const c = document.getElementById('productSearchResults');
        if (!products.length) {
            c.innerHTML = '<p class="text-center text-gray-400 text-sm py-4">No products found.</p>';
            return;
        }
        c.innerHTML = products.map(p => {
            const imgSrc = p.primary_image ? '/storage/' + p.primary_image : '/images/no-image.png';
            const stock  = (p.variants || []).reduce((s, v) => s + (parseInt(v.stock) || 0), 0) || 0;
            return `<div class="flex items-center gap-3 p-3 border dark:border-gray-700 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer" onclick='addProductRow(${JSON.stringify(p)})'>
                <img src="${imgSrc}" class="w-10 h-10 object-cover rounded-lg" onerror="this.src='/images/no-image.png'">
                <div class="flex-1">
                    <div class="text-sm font-medium dark:text-white">${p.name}</div>
                    <div class="text-xs text-gray-400">৳${parseFloat(p.current_price || p.price || 0).toFixed(2)} | ${(p.variants||[]).length} variants</div>
                </div>
            </div>`;
        }).join('');
    })
    .catch(err => {
        document.getElementById('productSearchResults').innerHTML = '<p class="text-center text-red-400 text-sm py-4">Error loading products.</p>';
        console.error(err);
    });
}

let rowIndex = 0;
function addProductRow(product) {
    const emptyRow = document.getElementById('emptyRow');
    if (emptyRow) emptyRow.remove();

    const variants  = product.variants || [];
    const firstVar  = variants[0] || {};
    const basePrice = parseFloat(firstVar.price || product.current_price || product.sale_price || product.price || 0);
    const stock     = parseInt(product.stock || 0);

    const row = document.createElement('tr');
    row.className = 'product-row border-b dark:border-gray-800';
    row.setAttribute('data-row', rowIndex);

    const variantOpts = variants.map(v => `<option value="${v.label || v.name || ''}">${v.label || v.name || ''}</option>`).join('');

    row.innerHTML = `
        <td class="px-3 py-3">
            <div class="flex items-center gap-3">
                <img src="${product.image_url || (product.primary_image ? '/storage/'+product.primary_image : '/images/no-image.png')}"
                    class="w-12 h-12 object-cover rounded-lg border dark:border-gray-700" onerror="this.src='/images/no-image.png'">
                <div>
                    <div class="font-medium text-gray-800 dark:text-gray-200 text-xs leading-tight">${product.name}</div>
                    <select name="items[${rowIndex}][variant]" class="mt-1 text-xs border border-gray-200 dark:border-gray-700 rounded-lg px-2 py-1 bg-white dark:bg-gray-800 dark:text-white">
                        <option value="">No variant</option>
                        ${variantOpts}
                    </select>
                    <input type="hidden" name="items[${rowIndex}][product_id]" value="${product.id}">
                    <input type="hidden" name="items[${rowIndex}][id]" value="">
                </div>
            </div>
        </td>
        <td class="px-3 py-3 text-center">
            <input type="number" name="items[${rowIndex}][unit_price]" value="${basePrice}"
                class="w-24 text-center rounded-lg border border-gray-200 dark:border-gray-700 px-2 py-1 text-sm bg-white dark:bg-gray-800 dark:text-white item-price"
                step="0.01" onchange="recalcRow(this)">
        </td>
        <td class="px-3 py-3 text-center">
            <input type="number" name="items[${rowIndex}][quantity]" value="1"
                class="w-16 text-center rounded-lg border border-gray-200 dark:border-gray-700 px-2 py-1 text-sm bg-white dark:bg-gray-800 dark:text-white item-qty"
                min="1" onchange="recalcRow(this)">
        </td>
        <td class="px-3 py-3 text-center">
            <span class="${stock <= 0 ? 'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400' : 'bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400'} px-2 py-1 rounded-lg text-xs font-bold">${stock}</span>
        </td>
        <td class="px-3 py-3 text-center">
            <input type="number" name="items[${rowIndex}][discount]" value="0"
                class="w-20 text-center rounded-lg border border-gray-200 dark:border-gray-700 px-2 py-1 text-sm bg-white dark:bg-gray-800 dark:text-white item-discount"
                min="0" onchange="recalcRow(this)">
        </td>
        <td class="px-3 py-3 text-center">
            <span class="item-subtotal font-bold text-[#1B2A4A] dark:text-white text-sm">৳${basePrice.toFixed(2)}</span>
        </td>
        <td class="px-3 py-3 text-center">
            <button type="button" onclick="removeProductRow(this)"
                class="w-8 h-8 flex items-center justify-center bg-red-100 dark:bg-red-900/30 text-red-500 rounded-lg hover:bg-red-200 mx-auto">✕</button>
        </td>
    `;

    document.getElementById('productRows').appendChild(row);
    rowIndex++;
    recalcAllSubtotals();
    closeProductModal();
}

// ─── Validation ───────────────────────────────────────────────────────────────
function validateOrder() {
    const rows = document.querySelectorAll('#productRows .product-row');
    if (rows.length === 0) {
        showToast('Please add at least one product.', 'error');
        return false;
    }
    return true;
}

// ─── Toast ────────────────────────────────────────────────────────────────────
function showToast(msg, type = 'success') {
    const t = document.createElement('div');
    t.className = `fixed top-4 right-4 z-[9999] px-4 py-3 rounded-xl text-white text-sm font-medium shadow-lg ${type === 'error' ? 'bg-red-500' : 'bg-green-500'}`;
    t.textContent = msg;
    document.body.appendChild(t);
    setTimeout(() => t.remove(), 3000);
}
</script>
@endpush
