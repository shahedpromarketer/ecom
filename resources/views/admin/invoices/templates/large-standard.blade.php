<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Invoice</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family: Arial, sans-serif; font-size: 12px; color:#333; }
.header { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:20px; padding-bottom:14px; border-bottom:2px solid #e5e7eb; }
.shop-name { font-size:24px; font-weight:bold; color:#1B2A4A; }
.shop-info { font-size:10px; color:#888; margin-top:4px; line-height:1.6; }
.invoice-meta { text-align:right; }
.invoice-meta .label { font-size:11px; color:#aaa; letter-spacing:2px; text-transform:uppercase; }
.invoice-meta .number { font-size:22px; font-weight:bold; color:#1B2A4A; }
.invoice-meta .date { font-size:11px; color:#888; }
.customer-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:20px; }
.info-box { background:#f9fafb; padding:12px; border-radius:8px; }
.info-box h4 { font-size:10px; color:#9ca3af; margin-bottom:6px; text-transform:uppercase; letter-spacing:1px; }
.info-box p { font-size:11px; color:#374151; margin-bottom:3px; }
table { width:100%; border-collapse:collapse; margin-bottom:16px; }
thead tr { border-bottom:2px solid #1B2A4A; }
th { padding:8px 10px; text-align:left; font-size:11px; color:#1B2A4A; font-weight:600; }
td { padding:7px 10px; border-bottom:1px solid #f3f4f6; font-size:11px; }
tbody tr:hover { background:#fafafa; }
.totals-wrap { display:flex; justify-content:flex-end; margin-bottom:20px; }
.totals { width:220px; }
.t-row { display:flex; justify-content:space-between; padding:4px 0; font-size:12px; color:#555; }
.grand { font-weight:bold; font-size:15px; color:#1B2A4A; border-top:2px solid #1B2A4A; padding-top:6px; margin-top:6px; }
.due { color:#e53e3e; font-weight:600; }
.terms { background:#f9fafb; padding:14px; border-radius:8px; margin-bottom:20px; }
.terms h4 { font-size:11px; color:#1B2A4A; font-weight:bold; margin-bottom:6px; }
.terms p { font-size:10px; color:#888; line-height:1.6; }
.signature-row { display:grid; grid-template-columns:1fr 1fr; gap:30px; margin-top:30px; }
.sig-box { border-top:1px solid #d1d5db; padding-top:6px; font-size:10px; color:#888; text-align:center; }
.footer { text-align:center; margin-top:20px; padding-top:12px; border-top:1px dashed #ddd; font-size:10px; color:#aaa; line-height:1.8; }
@media print { @page { size: A4; margin: 15mm; } .no-print { display:none !important; } }
@media screen { html { background:#f0f2f5; min-height:100vh; padding:64px 20px 20px; } body { max-width:210mm; margin:0 auto; background:white; padding:15mm; box-shadow:0 4px 24px rgba(0,0,0,.15); border-radius:6px; } }
</style>
</head>
<body>
<div class="header">
    <div>
        <div class="shop-name">{{ $settings['store_name'] ?? 'Ecom Pilot' }}</div>
        <div class="shop-info">
            @if(!empty($settings['store_address'])){{ $settings['store_address'] }}<br>@endif
            @if(!empty($settings['store_phone'])){{ $settings['store_phone'] }}<br>@endif
            @if(!empty($settings['store_email'])){{ $settings['store_email'] }}@endif
        </div>
    </div>
    <div class="invoice-meta">
        <div class="label">Invoice</div>
        <div class="number">#{{ $order->order_number }}</div>
        <div class="date">{{ $order->created_at->format('d M Y') }}</div>
    </div>
</div>
<div class="customer-grid">
    <div class="info-box">
        <h4>Bill To</h4>
        <p style="font-weight:bold;font-size:13px;">{{ $order->shipping_name }}</p>
        <p>{{ $order->shipping_phone }}</p>
        <p>{{ $order->shipping_address }}</p>
        @if($order->shipping_district)<p>{{ $order->shipping_district }}</p>@endif
    </div>
    <div class="info-box">
        <h4>Order Details</h4>
        <p>Order #: {{ $order->order_number }}</p>
        <p>Status: {{ ucfirst($order->status) }}</p>
        <p>Payment: {{ strtoupper($order->payment_method ?? 'COD') }}</p>
        <p>Date: {{ $order->created_at->format('d/m/Y g:ia') }}</p>
    </div>
</div>
<table>
<thead>
    <tr>
        <th width="30">#</th>
        <th>Product</th>
        <th>Variant</th>
        <th style="text-align:right;">Unit Price</th>
        <th style="text-align:center;">Qty</th>
        <th style="text-align:right;">Total</th>
    </tr>
</thead>
<tbody>
@foreach($order->items as $i => $item)
<tr>
    <td>{{ $i+1 }}</td>
    <td>{{ $item->product_name ?? ($item->product->name ?? 'Product') }}</td>
    <td>{{ $item->variant_info ?? '-' }}</td>
    <td style="text-align:right;">৳{{ number_format($item->unit_price, 0) }}</td>
    <td style="text-align:center;">{{ $item->qty }}</td>
    <td style="text-align:right;">৳{{ number_format($item->total, 0) }}</td>
</tr>
@endforeach
</tbody>
</table>
<div class="totals-wrap">
<div class="totals">
    <div class="t-row"><span>Subtotal:</span><span>৳{{ number_format($order->subtotal ?? 0, 0) }}</span></div>
    @if(($order->discount ?? 0) > 0)
    <div class="t-row"><span>Discount:</span><span>-৳{{ number_format($order->discount, 0) }}</span></div>
    @endif
    @if(($order->shipping_charge ?? 0) > 0)
    <div class="t-row"><span>Shipping:</span><span>৳{{ number_format($order->shipping_charge, 0) }}</span></div>
    @endif
    <div class="t-row grand"><span>Grand Total:</span><span>৳{{ number_format($order->total, 0) }}</span></div>
    @if(($order->paid_amount ?? 0) > 0)
    <div class="t-row"><span>Paid:</span><span>৳{{ number_format($order->paid_amount, 0) }}</span></div>
    <div class="t-row due"><span>Due:</span><span>৳{{ number_format($order->total - $order->paid_amount, 0) }}</span></div>
    @endif
</div>
</div>
<div class="terms">
    <h4>Terms & Conditions</h4>
    <p>1. পণ্য গ্রহণের পর ৩ দিনের মধ্যে ত্রুটি জানাতে হবে।<br>
    2. ডেলিভারির পর পণ্য পরিবর্তন বা ফেরত গ্রহণযোগ্য নয়, যদি না পণ্য ত্রুটিপূর্ণ হয়।<br>
    3. সকল বিরোধ {{ $settings['store_name'] ?? 'Ecom Pilot' }}-এর নীতি অনুযায়ী নিষ্পত্তি হবে।</p>
</div>
<div class="signature-row">
    <div class="sig-box">Customer Signature</div>
    <div class="sig-box">Authorized Signature</div>
</div>
<div class="footer">
    {{ $settings['store_name'] ?? 'Ecom Pilot' }}
    @if(!empty($settings['store_phone'])) &bull; {{ $settings['store_phone'] }}@endif
    @if(!empty($settings['store_email'])) &bull; {{ $settings['store_email'] }}@endif
    <br>ধন্যবাদ আপনার ক্রয়ের জন্য! &bull; Thank you for your purchase!
</div>

@if(!empty($preview))
<div class="no-print" style="position:fixed;top:0;left:0;right:0;background:#1B2A4A;color:white;padding:10px 20px;display:flex;align-items:center;justify-content:space-between;font-family:Arial,sans-serif;font-size:13px;z-index:9999;box-shadow:0 2px 8px rgba(0,0,0,.3);">
    <span>&#128065; Preview &mdash; <strong>{{ $template->name ?? '' }}</strong></span>
    <div style="display:flex;gap:8px;">
        <a href="/admin/invoice-templates" style="background:transparent;border:1px solid rgba(255,255,255,.4);color:white;padding:5px 14px;border-radius:6px;text-decoration:none;font-size:12px;">&larr; Back</a>
        <button onclick="window.print()" style="background:#F59E0B;border:none;color:white;padding:6px 16px;border-radius:6px;cursor:pointer;font-weight:bold;font-size:12px;">Print</button>
    </div>
</div>
<div class="no-print" style="height:44px;"></div>
@else
<script>window.onload = () => window.print();</script>
@endif
</body>
</html>
