<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Invoice</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family: Arial, sans-serif; font-size: 11px; }
.header { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:14px; padding-bottom:10px; border-bottom:2px solid #eee; }
.shop-name { font-size:20px; font-weight:bold; color:#1B2A4A; }
.shop-info { font-size:9px; color:#888; margin-top:2px; }
.invoice-meta { text-align:right; }
.invoice-meta .label { font-size:11px; color:#999; letter-spacing:2px; text-transform:uppercase; }
.invoice-meta .number { font-size:15px; font-weight:bold; color:#1B2A4A; }
.invoice-meta .date { font-size:10px; color:#888; }
.customer-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:14px; }
.info-box { background:#f9f9f9; padding:9px; border-radius:6px; }
.info-box h4 { font-size:9px; color:#aaa; margin-bottom:5px; text-transform:uppercase; letter-spacing:1px; }
.info-box p { font-size:10px; color:#333; margin-bottom:2px; }
table { width:100%; border-collapse:collapse; margin-bottom:12px; }
thead tr { border-bottom:2px solid #1B2A4A; }
th { padding:6px 8px; text-align:left; font-size:10px; color:#1B2A4A; }
td { padding:5px 8px; border-bottom:1px solid #f0f0f0; font-size:10px; color:#333; }
tr:last-child td { border-bottom: none; }
.totals { margin-left:auto; width:180px; }
.totals .t-row { display:flex; justify-content:space-between; padding:3px 0; font-size:11px; color:#555; }
.totals .grand { font-weight:bold; font-size:14px; color:#1B2A4A; border-top:2px solid #1B2A4A; padding-top:5px; margin-top:5px; }
.totals .due { color:#e53e3e; }
.footer { text-align:center; margin-top:16px; padding-top:10px; border-top:1px dashed #ddd; font-size:9px; color:#aaa; }
@media print { @page { size: A5; margin: 8mm; } }
</style>
</head>
<body>
<div class="header">
    <div>
        <div class="shop-name">{{ $settings['store_name'] ?? 'Clothonic' }}</div>
        <div class="shop-info">{{ $settings['store_address'] ?? '' }}</div>
        @if(!empty($settings['store_phone']))<div class="shop-info">{{ $settings['store_phone'] }}</div>@endif
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
        <p style="font-weight:bold;">{{ $order->shipping_name }}</p>
        <p>{{ $order->shipping_phone }}</p>
        <p>{{ $order->shipping_address }}</p>
        @if($order->shipping_district)<p>{{ $order->shipping_district }}</p>@endif
    </div>
    <div class="info-box">
        <h4>Order Details</h4>
        <p>Status: {{ ucfirst($order->status) }}</p>
        <p>Payment: {{ strtoupper($order->payment_method ?? 'COD') }}</p>
        <p>Date: {{ $order->created_at->format('d/m/Y') }}</p>
        @if($order->order_number)<p>Ref: {{ $order->order_number }}</p>@endif
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
<div class="footer">{{ $settings['store_name'] ?? 'Clothonic' }} &bull; {{ $settings['store_phone'] ?? '' }} &bull; ধন্যবাদ আপনার ক্রয়ের জন্য!</div>
<script>window.onload = () => window.print();</script>
</body>
</html>
