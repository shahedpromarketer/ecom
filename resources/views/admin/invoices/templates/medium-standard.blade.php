<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Invoice</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family: Arial, sans-serif; font-size: 11px; }
.header { background:#1B2A4A; color:white; padding:14px 16px; display:flex; justify-content:space-between; align-items:center; margin-bottom:14px; border-radius:6px; }
.shop-name { font-size:20px; font-weight:bold; }
.shop-sub { font-size:9px; opacity:.7; margin-top:2px; }
.invoice-meta { text-align:right; }
.invoice-meta .inv-label { font-size:9px; opacity:.6; letter-spacing:2px; text-transform:uppercase; }
.invoice-meta .inv-num { font-size:16px; font-weight:bold; }
.invoice-meta .inv-date { font-size:9px; opacity:.7; }
.customer-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:14px; }
.info-box { background:#f9f9f9; padding:9px; border-radius:6px; border-left:3px solid #1B2A4A; }
.info-box h4 { font-size:9px; color:#1B2A4A; margin-bottom:5px; text-transform:uppercase; font-weight:bold; }
.info-box p { font-size:10px; color:#333; margin-bottom:2px; }
table { width:100%; border-collapse:collapse; margin-bottom:12px; }
thead tr { background:#1B2A4A; }
th { padding:7px 8px; text-align:left; font-size:10px; color:white; }
td { padding:5px 8px; border-bottom:1px solid #f0f0f0; font-size:10px; }
tbody tr:nth-child(even) { background:#fafafa; }
.totals { margin-left:auto; width:190px; }
.t-row { display:flex; justify-content:space-between; padding:3px 0; font-size:11px; color:#555; }
.grand { font-weight:bold; font-size:14px; color:#1B2A4A; border-top:2px solid #1B2A4A; padding-top:5px; margin-top:5px; }
.due { color:#e53e3e; }
.footer { text-align:center; margin-top:16px; padding-top:10px; border-top:1px dashed #ddd; font-size:9px; color:#aaa; }
@media print { @page { size: A5; margin: 6mm; } .no-print { display:none !important; } }
@media screen { html { background:#f0f2f5; min-height:100vh; padding:64px 20px 20px; } body { max-width:148mm; margin:0 auto; background:white; padding:14mm; box-shadow:0 4px 24px rgba(0,0,0,.15); border-radius:6px; } }
</style>
<style>.ep-barcode{max-width:100%;height:auto}</style>
</head>
<body>
<div class="header">
    <div>
        <div class="shop-name">{{ $settings['store_name'] ?? 'Ecom Pilot' }}</div>
        <div class="shop-sub">{{ $settings['store_tagline'] ?? '' }}</div>
        @if(!empty($settings['store_address']))<div class="shop-sub">{{ $settings['store_address'] }}</div>@endif
    </div>
    <div class="invoice-meta">
        <div class="inv-label">Invoice</div>
        <div class="inv-num">#{{ $order->order_number }}</div>
        <div class="ep-barcode-wrap" style="text-align:center;margin:4px 0;line-height:0">{!! \App\Support\Barcode::svg((string) $order->order_number, 34, 1.3) !!}</div>
        <div class="inv-date">{{ $order->created_at->format('d M Y') }}</div>
    </div>
</div>
<div class="customer-grid">
    <div class="info-box">
        <h4>Bill To</h4>
        <p style="font-weight:bold;">{{ $order->shipping_name }}</p>
        <p>{{ $order->shipping_phone }}</p>
        <p>{{ $order->shipping_address }}</p>
        @if($order->shipping_district)<p>{{ $order->shipping_district }}</p>@endif
    </div>
    <div class="info-box">
        <h4>Order Info</h4>
        <p>Status: {{ ucfirst($order->status) }}</p>
        <p>Payment: {{ strtoupper($order->payment_method ?? 'COD') }}</p>
        <p>Date: {{ $order->created_at->format('d/m/Y g:ia') }}</p>
    </div>
</div>
<table>
<thead><tr><th>#</th><th>Product</th><th>Variant</th><th style="text-align:right;">Price</th><th style="text-align:center;">Qty</th><th style="text-align:right;">Total</th></tr></thead>
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
<div class="footer">{{ $settings['store_name'] ?? 'Ecom Pilot' }} &bull; {{ $settings['store_phone'] ?? '' }} &bull; ধন্যবাদ আপনার ক্রয়ের জন্য!</div>

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
