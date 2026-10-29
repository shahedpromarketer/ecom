<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Invoice</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family: Arial, sans-serif; font-size: 10px; width: 76mm; }
.header { text-align:center; padding: 8px 4px; background:#1B2A4A; color:white; }
.header h1 { font-size: 15px; font-weight: bold; }
.header p { font-size: 8.5px; opacity:.85; }
.badge { display:inline-block; background:rgba(255,255,255,.2); padding:2px 8px; border-radius:10px; font-size:8px; margin-top:3px; letter-spacing:1px; }
.section { padding: 4px 6px; border-bottom: 1px solid #e5e7eb; }
.row { display:flex; justify-content:space-between; margin: 2px 0; }
.label { color: #666; }
table { width:100%; border-collapse:collapse; margin: 3px 0; }
th { background:#f8f8f8; padding: 3px 4px; text-align:left; font-size:8.5px; border-bottom:2px solid #1B2A4A; color:#333; }
td { padding: 3px 4px; border-bottom: 1px solid #f0f0f0; font-size:9px; }
.total-section { padding: 4px 6px; }
.total-row { font-weight:bold; font-size:12px; }
.footer { text-align:center; padding: 6px 4px; font-size:9px; color:#777; border-top:1px solid #eee; }
@media print { @page { size: 76mm auto; margin: 0; } html { display:flex; justify-content:center; } body { width:76mm; margin:0 auto; } .no-print { display:none !important; } }
@media screen { html { background:#f0f2f5; min-height:100vh; } body { width:76mm; margin:64px auto 20px; background:white; box-shadow:0 4px 24px rgba(0,0,0,.15); border-radius:4px; } }
</style>
<style>.ep-barcode{max-width:100%;height:auto}</style>
</head>
<body>
<div class="header">
    <h1>{{ $settings['store_name'] ?? 'Ecom Pilot' }}</h1>
    @if(!empty($settings['store_tagline']))<p>{{ $settings['store_tagline'] }}</p>@endif
    @if(!empty($settings['store_phone']))<p>{{ $settings['store_phone'] }}</p>@endif
    <span class="badge">INVOICE</span>
</div>
<div class="section">
    <div class="row"><span class="label">Invoice #</span><span><strong>{{ $order->order_number }}</strong></span></div>
    <div class="ep-barcode-wrap" style="text-align:center;margin:4px 0;line-height:0">{!! \App\Support\Barcode::svg((string) $order->order_number, 34, 1.3) !!}</div>
    <div class="row"><span class="label">Date</span><span>{{ $order->created_at->format('d M Y, g:ia') }}</span></div>
    @if(!empty($settings['store_address']))<div class="row"><span class="label">Address</span><span style="max-width:42mm;text-align:right;">{{ $settings['store_address'] }}</span></div>@endif
</div>
<div class="section">
    <div class="row"><span class="label">Customer</span><span><strong>{{ $order->shipping_name }}</strong></span></div>
    <div class="row"><span class="label">Phone</span><span>{{ $order->shipping_phone }}</span></div>
    <div class="row"><span class="label">Address</span><span style="max-width:48mm;text-align:right;">{{ $order->shipping_address }}</span></div>
</div>
<div class="section">
<table>
<thead><tr><th>Item</th><th style="text-align:center;">Qty</th><th style="text-align:right;">Rate</th><th style="text-align:right;">Amt</th></tr></thead>
<tbody>
@foreach($order->items as $item)
<tr>
    <td>{{ Str::limit($item->product_name ?? ($item->product->name ?? 'Product'), 18) }}
    @if(!empty($item->variant_info))<br><span style="color:#999;font-size:7.5px;">{{ $item->variant_info }}</span>@endif
    </td>
    <td style="text-align:center;">{{ $item->qty }}</td>
    <td style="text-align:right;">৳{{ number_format($item->unit_price, 0) }}</td>
    <td style="text-align:right;">৳{{ number_format($item->total, 0) }}</td>
</tr>
@endforeach
</tbody>
</table>
</div>
<div class="total-section">
    @if(($order->subtotal ?? 0) > 0 && $order->subtotal != $order->total)
    <div class="row" style="color:#666;margin:2px 0;"><span>Subtotal</span><span>৳{{ number_format($order->subtotal, 0) }}</span></div>
    @endif
    @if(($order->discount ?? 0) > 0)
    <div class="row" style="color:#666;margin:2px 0;"><span>Discount</span><span>-৳{{ number_format($order->discount, 0) }}</span></div>
    @endif
    @if(($order->shipping_charge ?? 0) > 0)
    <div class="row" style="color:#666;margin:2px 0;"><span>Shipping</span><span>৳{{ number_format($order->shipping_charge, 0) }}</span></div>
    @endif
    <div class="row total-row" style="padding-top:3px;border-top:2px solid #1B2A4A;margin-top:3px;"><span>TOTAL</span><span>৳{{ number_format($order->total, 0) }}</span></div>
    <div class="row" style="margin:2px 0;"><span class="label">Payment Method</span><span>{{ strtoupper($order->payment_method ?? 'COD') }}</span></div>
    @if(($order->paid_amount ?? 0) > 0)
    <div class="row" style="margin:2px 0;"><span class="label">Paid</span><span>৳{{ number_format($order->paid_amount, 0) }}</span></div>
    <div class="row" style="margin:2px 0;color:#dc2626;font-weight:bold;"><span>Due</span><span>৳{{ number_format($order->total - $order->paid_amount, 0) }}</span></div>
    @endif
</div>
<div class="footer">
    @if(!empty($settings['store_email']))<p>{{ $settings['store_email'] }}</p>@endif
    <p>ধন্যবাদ আপনার ক্রয়ের জন্য!</p>
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
