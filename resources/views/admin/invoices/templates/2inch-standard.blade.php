<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Invoice</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family: Arial, sans-serif; font-size: 9px; width: 58mm; }
.header { text-align:center; padding: 6px 3px; background:#1B2A4A; color:white; }
.header h1 { font-size: 12px; font-weight: bold; }
.header p { font-size: 7.5px; opacity:.85; }
.badge { display:inline-block; background:rgba(255,255,255,.2); padding:1px 5px; border-radius:8px; font-size:7px; margin-top:2px; }
.section { padding: 3px 5px; border-bottom: 1px solid #e5e7eb; }
.row { display:flex; justify-content:space-between; margin: 1.5px 0; }
.label { color: #666; }
table { width:100%; border-collapse:collapse; margin: 2px 0; }
th { background:#f8f8f8; padding: 2px 3px; text-align:left; font-size:7.5px; border-bottom:1px solid #ddd; color:#555; }
td { padding: 2px 3px; border-bottom: 1px solid #f0f0f0; font-size:8px; }
.total-section { padding: 3px 5px; }
.total-row { font-weight:bold; font-size:10px; }
.footer { text-align:center; padding: 5px 3px; font-size:8px; color:#777; border-top:1px solid #eee; }
@media print { @page { size: 58mm auto; margin: 0; } html { display:flex; justify-content:center; } body { width:58mm; margin:0 auto; } .no-print { display:none !important; } }
@media screen { html { background:#f0f2f5; min-height:100vh; } body { width:58mm; margin:64px auto 20px; background:white; box-shadow:0 4px 24px rgba(0,0,0,.15); border-radius:4px; } }
</style>
<style>.ep-barcode{max-width:100%;height:auto}</style>
</head>
<body>
<div class="header">
    <h1>{{ $settings['store_name'] ?? 'Ecom Pilot' }}</h1>
    @if(!empty($settings['store_tagline']))<p>{{ $settings['store_tagline'] }}</p>@endif
    <span class="badge">INVOICE</span>
</div>
<div class="section">
    <div class="row"><span class="label">Invoice #</span><span><strong>{{ $order->order_number }}</strong></span></div>
    <div class="ep-barcode-wrap" style="text-align:center;margin:4px 0;line-height:0">{!! \App\Support\Barcode::svg((string) $order->order_number, 34, 1.3) !!}</div>
    <div class="row"><span class="label">Date</span><span>{{ $order->created_at->format('d/m/Y g:ia') }}</span></div>
    @if(!empty($settings['store_phone']))<div class="row"><span class="label">Phone</span><span>{{ $settings['store_phone'] }}</span></div>@endif
</div>
<div class="section">
    <div class="row"><span class="label">Customer</span><span><strong>{{ $order->shipping_name }}</strong></span></div>
    <div class="row"><span class="label">Phone</span><span>{{ $order->shipping_phone }}</span></div>
    <div class="row"><span class="label">Address</span><span style="max-width:36mm;text-align:right;">{{ $order->shipping_address }}</span></div>
</div>
<div class="section">
<table>
<thead><tr><th>Item</th><th style="text-align:center;">Qty</th><th style="text-align:right;">Amt</th></tr></thead>
<tbody>
@foreach($order->items as $item)
<tr>
    <td>{{ Str::limit($item->product_name ?? ($item->product->name ?? 'Product'), 16) }}
    @if(!empty($item->variant_info))<br><span style="color:#888;font-size:7px;">{{ $item->variant_info }}</span>@endif
    </td>
    <td style="text-align:center;">{{ $item->qty }}</td>
    <td style="text-align:right;">৳{{ number_format($item->total, 0) }}</td>
</tr>
@endforeach
</tbody>
</table>
</div>
<div class="total-section">
    @if(($order->discount ?? 0) > 0)
    <div class="row" style="color:#666;margin:1.5px 0;font-size:8px;"><span>Discount</span><span>-৳{{ number_format($order->discount, 0) }}</span></div>
    @endif
    @if(($order->shipping_charge ?? 0) > 0)
    <div class="row" style="color:#666;margin:1.5px 0;font-size:8px;"><span>Shipping</span><span>৳{{ number_format($order->shipping_charge, 0) }}</span></div>
    @endif
    <div class="row total-row" style="padding-top:2px;border-top:1px dashed #333;margin-top:2px;"><span>TOTAL</span><span>৳{{ number_format($order->total, 0) }}</span></div>
    <div class="row" style="margin:1.5px 0;font-size:8px;"><span class="label">Payment</span><span>{{ strtoupper($order->payment_method ?? 'COD') }}</span></div>
    @if(($order->paid_amount ?? 0) > 0)
    <div class="row" style="font-size:8px;"><span class="label">Due</span><span>৳{{ number_format($order->total - $order->paid_amount, 0) }}</span></div>
    @endif
</div>
<div class="footer">ধন্যবাদ আপনার ক্রয়ের জন্য!</div>
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
