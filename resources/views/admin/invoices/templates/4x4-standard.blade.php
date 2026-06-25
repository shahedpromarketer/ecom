<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Invoice</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family: Arial, sans-serif; font-size: 9.5px; width: 102mm; height: 102mm; overflow: hidden; }
.wrap { height: 102mm; display:flex; flex-direction:column; }
.header { background:#1B2A4A; color:white; text-align:center; padding: 7px 5px; }
.header h1 { font-size: 14px; font-weight: bold; }
.header p { font-size: 7.5px; opacity:.8; }
.badge { display:inline-block; background:rgba(255,255,255,.2); padding:2px 9px; border-radius:10px; font-size:7.5px; margin-top:2px; letter-spacing:1px; }
.body { padding: 5px 7px; flex:1; display:flex; flex-direction:column; }
.info { display:flex; gap:8px; margin-bottom: 3px; }
.info-col { flex:1; }
.row { display:flex; justify-content:space-between; margin: 2px 0; }
.label { color: #666; }
.divider { border-bottom: 1px solid #e5e7eb; margin: 3px 0; }
table { width:100%; border-collapse:collapse; margin: 2px 0; }
th { font-size: 8px; padding: 2px 3px; border-bottom: 2px solid #1B2A4A; text-align:left; color:#333; }
td { font-size: 9px; padding: 2px 3px; border-bottom: 1px solid #f0f0f0; }
.total { border-top: 2px solid #1B2A4A; padding-top: 3px; display:flex; justify-content:space-between; font-weight:bold; font-size:12px; }
.footer { text-align:center; font-size:8.5px; color:#777; margin-top:auto; padding-top:3px; border-top:1px solid #eee; }
@media print { @page { size: 102mm 102mm; margin: 0; } html { display:flex; justify-content:center; } body { width:102mm; height:102mm; margin:0 auto; overflow:hidden; } .no-print { display:none !important; } }
@media screen { html { background:#f0f2f5; min-height:100vh; } body { width:102mm; height:102mm; margin:64px auto 20px; background:white; box-shadow:0 4px 24px rgba(0,0,0,.15); border-radius:4px; } }
</style>
</head>
<body>
<div class="wrap">
<div class="header">
    <h1>{{ $settings['store_name'] ?? 'Ecom Pilot' }}</h1>
    @if(!empty($settings['store_phone']))<p>{{ $settings['store_phone'] }}</p>@endif
    @if(!empty($settings['store_address']))<p>{{ $settings['store_address'] }}</p>@endif
    <span class="badge">INVOICE</span>
</div>
<div class="body">
<div class="info">
    <div class="info-col">
        <div class="row"><span class="label">Invoice #</span><span><strong>{{ $order->order_number }}</strong></span></div>
        <div class="row"><span class="label">Date</span><span>{{ $order->created_at->format('d/m/Y') }}</span></div>
        <div class="row"><span class="label">Payment</span><span>{{ strtoupper($order->payment_method ?? 'COD') }}</span></div>
    </div>
    <div class="info-col">
        <div class="row"><span class="label">Customer</span><span>{{ Str::limit($order->shipping_name, 14) }}</span></div>
        <div class="row"><span class="label">Phone</span><span>{{ $order->shipping_phone }}</span></div>
        <div class="row"><span class="label">Address</span><span style="max-width:38mm;text-align:right;">{{ Str::limit($order->shipping_address, 22) }}</span></div>
    </div>
</div>
<div class="divider"></div>
<table>
<thead><tr><th>Item</th><th style="text-align:center;">Qty</th><th style="text-align:right;">Rate</th><th style="text-align:right;">Amt</th></tr></thead>
<tbody>
@foreach($order->items->take(5) as $item)
<tr>
    <td>{{ Str::limit($item->product_name ?? ($item->product->name ?? 'Product'), 22) }}</td>
    <td style="text-align:center;">{{ $item->qty }}</td>
    <td style="text-align:right;">৳{{ number_format($item->unit_price, 0) }}</td>
    <td style="text-align:right;">৳{{ number_format($item->total, 0) }}</td>
</tr>
@endforeach
</tbody>
</table>
@if(($order->discount ?? 0) > 0)
<div class="row" style="margin-top:2px;color:#666;font-size:8.5px;"><span>Discount</span><span>-৳{{ number_format($order->discount, 0) }}</span></div>
@endif
@if(($order->shipping_charge ?? 0) > 0)
<div class="row" style="color:#666;font-size:8.5px;"><span>Shipping</span><span>৳{{ number_format($order->shipping_charge, 0) }}</span></div>
@endif
<div class="total" style="margin-top:3px;"><span>TOTAL</span><span>৳{{ number_format($order->total, 0) }}</span></div>
@if(($order->paid_amount ?? 0) > 0)
<div class="row" style="font-size:8.5px;color:#dc2626;font-weight:bold;"><span>Due</span><span>৳{{ number_format($order->total - $order->paid_amount, 0) }}</span></div>
@endif
<div class="footer">ধন্যবাদ আপনার ক্রয়ের জন্য!</div>
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
@else
<script>window.onload = () => window.print();</script>
@endif
</body>
</html>
