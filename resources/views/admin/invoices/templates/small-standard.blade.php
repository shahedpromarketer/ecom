<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Invoice</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family: Arial, sans-serif; font-size: 10px; width: 80mm; }
.header { background:#1B2A4A; color:white; text-align:center; padding: 8px 4px; }
.header h1 { font-size: 15px; font-weight: bold; letter-spacing:1px; }
.header p { font-size: 9px; opacity:.8; }
.inv-num { background:#F59E0B; color:white; text-align:center; padding:3px; font-size:10px; font-weight:bold; }
.section { padding: 4px 6px; border-bottom: 1px dashed #ccc; }
.row { display:flex; justify-content:space-between; margin: 2px 0; }
.label { color: #555; }
table { width:100%; border-collapse:collapse; margin: 4px 0; }
th { background:#1B2A4A; color:white; padding: 3px 4px; text-align:left; font-size:9px; }
td { padding: 3px 4px; border-bottom: 1px solid #eee; font-size:9px; }
.total-row { font-weight:bold; font-size:11px; }
.footer { text-align:center; padding: 5px 4px; font-size:9px; color:#777; border-top:1px dashed #ccc; }
.barcode-area { text-align:center; padding:4px; letter-spacing:3px; font-size:8px; font-family:monospace; }
@media print { @page { size: 80mm auto; margin: 0; } body { width:80mm; } }
</style>
</head>
<body>
<div class="header">
    <h1>{{ $settings['store_name'] ?? 'Clothonic' }}</h1>
    @if(!empty($settings['store_phone']))<p>{{ $settings['store_phone'] }}</p>@endif
    @if(!empty($settings['store_tagline']))<p>{{ $settings['store_tagline'] }}</p>@endif
</div>
<div class="inv-num">Invoice: #{{ $order->order_number }}</div>
<div class="section">
    <div class="row"><span class="label">Date:</span><span>{{ $order->created_at->format('d/m/Y g:ia') }}</span></div>
    <div class="row"><span class="label">Payment:</span><span>{{ strtoupper($order->payment_method ?? 'COD') }}</span></div>
</div>
<div class="section">
    <div class="row"><span class="label">Name:</span><span>{{ $order->shipping_name }}</span></div>
    <div class="row"><span class="label">Phone:</span><span>{{ $order->shipping_phone }}</span></div>
    <div class="row"><span class="label">Address:</span><span style="max-width:55mm;text-align:right;">{{ $order->shipping_address }}</span></div>
</div>
<div class="section">
<table>
<thead><tr><th>Item</th><th>Qty</th><th>Total</th></tr></thead>
<tbody>
@foreach($order->items as $item)
<tr>
    <td>{{ Str::limit($item->product_name ?? ($item->product->name ?? 'Product'), 22) }}</td>
    <td style="text-align:center;">{{ $item->qty }}</td>
    <td style="text-align:right;">৳{{ number_format($item->total, 0) }}</td>
</tr>
@endforeach
</tbody>
</table>
</div>
<div class="section">
    @if(($order->discount ?? 0) > 0)
    <div class="row"><span class="label">Discount:</span><span>-৳{{ number_format($order->discount, 0) }}</span></div>
    @endif
    @if(($order->shipping_charge ?? 0) > 0)
    <div class="row"><span class="label">Shipping:</span><span>৳{{ number_format($order->shipping_charge, 0) }}</span></div>
    @endif
    <div class="row total-row"><span>Total:</span><span>৳{{ number_format($order->total, 0) }}</span></div>
    @if(($order->paid_amount ?? 0) > 0)
    <div class="row"><span class="label">Paid:</span><span>৳{{ number_format($order->paid_amount, 0) }}</span></div>
    <div class="row" style="color:red;"><span class="label">Due:</span><span>৳{{ number_format($order->total - $order->paid_amount, 0) }}</span></div>
    @endif
</div>
<div class="barcode-area">|||| {{ $order->order_number }} ||||</div>
<div class="footer">{{ $settings['store_name'] ?? 'Clothonic' }} &bull; ধন্যবাদ!</div>
<script>window.onload = () => window.print();</script>
</body>
</html>
