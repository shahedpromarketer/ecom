<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Invoice</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family: Arial, sans-serif; font-size: 12px; color:#333; }
.header { background:#1B2A4A; color:white; padding:20px 24px; display:flex; justify-content:space-between; align-items:center; margin-bottom:0; }
.header-left .shop-name { font-size:26px; font-weight:bold; letter-spacing:1px; }
.header-left .shop-sub { font-size:10px; opacity:.65; margin-top:3px; line-height:1.7; }
.header-right { text-align:right; }
.header-right .inv-label { font-size:10px; opacity:.6; letter-spacing:3px; text-transform:uppercase; }
.header-right .inv-num { font-size:20px; font-weight:bold; }
.header-right .inv-date { font-size:10px; opacity:.7; }
.accent-bar { background:#F59E0B; height:4px; margin-bottom:20px; }
.customer-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:20px; }
.info-box { padding:12px 14px; border-radius:8px; border:1px solid #e5e7eb; }
.info-box h4 { font-size:10px; color:#F59E0B; margin-bottom:7px; text-transform:uppercase; font-weight:bold; letter-spacing:1px; }
.info-box p { font-size:11px; color:#374151; margin-bottom:3px; }
table { width:100%; border-collapse:collapse; margin-bottom:16px; }
thead tr { background:#F59E0B; }
th { padding:9px 10px; text-align:left; font-size:11px; color:white; font-weight:600; }
td { padding:7px 10px; border-bottom:1px solid #f3f4f6; font-size:11px; }
tbody tr:nth-child(even) { background:#fffbf0; }
.totals-section { display:flex; justify-content:flex-end; margin-bottom:20px; }
.totals { width:230px; background:#f9fafb; padding:12px; border-radius:8px; border:1px solid #e5e7eb; }
.t-row { display:flex; justify-content:space-between; padding:4px 0; font-size:12px; color:#555; }
.grand { font-weight:bold; font-size:16px; color:#1B2A4A; border-top:2px solid #F59E0B; padding-top:7px; margin-top:7px; }
.due { color:#e53e3e; font-weight:600; }
.terms { background:#fffbf0; padding:14px; border-radius:8px; border:1px solid #fde68a; margin-bottom:20px; }
.terms h4 { font-size:11px; color:#92400e; font-weight:bold; margin-bottom:6px; }
.terms p { font-size:10px; color:#78350f; line-height:1.6; }
.signature-row { display:grid; grid-template-columns:1fr 1fr; gap:30px; margin-bottom:20px; }
.sig-box { border-top:2px solid #1B2A4A; padding-top:6px; font-size:10px; color:#888; text-align:center; }
.footer { background:#1B2A4A; color:white; padding:12px 24px; text-align:center; font-size:10px; border-radius:6px; }
.footer .main { font-size:12px; font-weight:bold; margin-bottom:4px; }
.footer .sub { opacity:.65; line-height:1.8; }
@media print { @page { size: A4; margin: 12mm; } .no-print { display:none !important; } }
@media screen { html { background:#f0f2f5; min-height:100vh; padding:64px 20px 20px; } body { max-width:210mm; margin:0 auto; background:white; padding:15mm; box-shadow:0 4px 24px rgba(0,0,0,.15); border-radius:6px; } }
</style>
<style>.ep-barcode{max-width:100%;height:auto}</style>
</head>
<body>
<div class="header">
    <div class="header-left">
        <div class="shop-name">{{ $settings['store_name'] ?? 'Ecom Pilot' }}</div>
        <div class="shop-sub">
            @if(!empty($settings['store_tagline'])){{ $settings['store_tagline'] }}<br>@endif
            @if(!empty($settings['store_address'])){{ $settings['store_address'] }}<br>@endif
            @if(!empty($settings['store_phone'])){{ $settings['store_phone'] }}@endif
            @if(!empty($settings['store_email'])) &bull; {{ $settings['store_email'] }}@endif
        </div>
    </div>
    <div class="header-right">
        <div class="inv-label">Invoice</div>
        <div class="inv-num">#{{ $order->order_number }}</div>
        <div class="ep-barcode-wrap" style="text-align:center;margin:4px 0;line-height:0">{!! \App\Support\Barcode::svg((string) $order->order_number, 34, 1.3) !!}</div>
        <div class="inv-date">{{ $order->created_at->format('d M Y') }}</div>
    </div>
</div>
<div class="accent-bar"></div>
<div class="customer-grid">
    <div class="info-box">
        <h4>Bill To</h4>
        <p style="font-weight:bold;font-size:13px;">{{ $order->shipping_name }}</p>
        <p>{{ $order->shipping_phone }}</p>
        <p>{{ $order->shipping_address }}</p>
        @if($order->shipping_district)<p>{{ $order->shipping_district }}</p>@endif
        @if($order->shipping_area)<p>{{ $order->shipping_area }}</p>@endif
    </div>
    <div class="info-box">
        <h4>Order Details</h4>
        <p><strong>Order #:</strong> {{ $order->order_number }}</p>
        <p><strong>Status:</strong> {{ ucfirst($order->status) }}</p>
        <p><strong>Payment:</strong> {{ strtoupper($order->payment_method ?? 'COD') }}</p>
        <p><strong>Date:</strong> {{ $order->created_at->format('d/m/Y g:ia') }}</p>
    </div>
</div>
<table>
<thead>
    <tr>
        <th width="30">#</th>
        <th>Product Name</th>
        <th>Variant</th>
        <th style="text-align:right;">Unit Price</th>
        <th style="text-align:center;">Qty</th>
        <th style="text-align:right;">Discount</th>
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
    <td style="text-align:right;">{{ ($item->discount ?? 0) > 0 ? '-৳'.number_format($item->discount,0) : '-' }}</td>
    <td style="text-align:right;font-weight:600;">৳{{ number_format($item->total, 0) }}</td>
</tr>
@endforeach
</tbody>
</table>
<div class="totals-section">
<div class="totals">
    <div class="t-row"><span>Subtotal:</span><span>৳{{ number_format($order->subtotal ?? 0, 0) }}</span></div>
    @if(($order->discount ?? 0) > 0)
    <div class="t-row" style="color:#16a34a;"><span>Discount:</span><span>-৳{{ number_format($order->discount, 0) }}</span></div>
    @endif
    @if(($order->shipping_charge ?? 0) > 0)
    <div class="t-row"><span>Shipping:</span><span>৳{{ number_format($order->shipping_charge, 0) }}</span></div>
    @endif
    <div class="t-row grand"><span>Grand Total:</span><span>৳{{ number_format($order->total, 0) }}</span></div>
    @if(($order->paid_amount ?? 0) > 0)
    <div class="t-row"><span>Paid:</span><span>৳{{ number_format($order->paid_amount, 0) }}</span></div>
    <div class="t-row due"><span>Balance Due:</span><span>৳{{ number_format($order->total - $order->paid_amount, 0) }}</span></div>
    @endif
</div>
</div>
<div class="terms">
    <h4>Terms & Conditions</h4>
    <p>1. পণ্য গ্রহণের পর ৩ দিনের মধ্যে ত্রুটি জানাতে হবে। &bull; 2. ডেলিভারির পর পণ্য পরিবর্তন বা ফেরত গ্রহণযোগ্য নয়, যদি না পণ্য ত্রুটিপূর্ণ হয়।<br>
    3. সকল বিরোধ {{ $settings['store_name'] ?? 'Ecom Pilot' }}-এর নীতি অনুযায়ী নিষ্পত্তি হবে। &bull; 4. এই ইনভয়েস একটি বৈধ দলিল।</p>
</div>
<div class="signature-row">
    <div class="sig-box">Customer Signature</div>
    <div class="sig-box">Authorized Signature</div>
</div>
<div class="footer">
    <div class="main">{{ $settings['store_name'] ?? 'Ecom Pilot' }}</div>
    <div class="sub">
        @if(!empty($settings['store_phone'])){{ $settings['store_phone'] }}@endif
        @if(!empty($settings['store_email'])) &bull; {{ $settings['store_email'] }}@endif
        @if(!empty($settings['store_address'])) &bull; {{ $settings['store_address'] }}@endif
        <br>ধন্যবাদ আপনার ক্রয়ের জন্য! &bull; Thank you for your purchase!
    </div>
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
