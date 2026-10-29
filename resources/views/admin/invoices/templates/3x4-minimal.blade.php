<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Invoice</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family: Arial, sans-serif; font-size: 9px; width: 76mm; height: 102mm; overflow: hidden; }
.wrap { padding: 5px 6px; height: 102mm; display:flex; flex-direction:column; }
.header { text-align:center; border-bottom: 1px dashed #000; padding-bottom: 4px; margin-bottom: 4px; }
.header h1 { font-size: 13px; font-weight: bold; }
.header p { font-size: 8px; color: #555; }
.row { display:flex; justify-content:space-between; margin: 2px 0; }
.label { color: #666; }
.divider { border-bottom: 1px dashed #ccc; margin: 3px 0; }
table { width:100%; border-collapse:collapse; margin: 2px 0; }
th { font-size: 8px; padding: 2px 3px; border-bottom: 1px solid #ccc; text-align:left; }
td { font-size: 8.5px; padding: 2px 3px; border-bottom: 1px solid #f0f0f0; }
.total-row { font-weight:bold; font-size:11px; border-top: 1px dashed #000 !important; }
.footer { text-align:center; font-size:8px; color:#777; margin-top:auto; padding-top:3px; border-top:1px dashed #ccc; }
@media print { @page { size: 76mm 102mm; margin: 0; } html { display:flex; justify-content:center; } body { width:76mm; height:102mm; margin:0 auto; overflow:hidden; } .no-print { display:none !important; } }
@media screen { html { background:#f0f2f5; min-height:100vh; } body { width:76mm; height:102mm; margin:64px auto 20px; background:white; box-shadow:0 4px 24px rgba(0,0,0,.15); border-radius:4px; } }
</style>
<style>.ep-barcode{max-width:100%;height:auto}</style>
</head>
<body>
<div class="wrap">
<div class="header">
    <h1>{{ $settings['store_name'] ?? 'Ecom Pilot' }}</h1>
    @if(!empty($settings['store_phone']))<p>{{ $settings['store_phone'] }}</p>@endif
    @if(!empty($settings['store_address']))<p>{{ $settings['store_address'] }}</p>@endif
</div>
<div class="row"><span class="label">Invoice:</span><span>#{{ $order->order_number }}</span></div>
<div class="ep-barcode-wrap" style="text-align:center;margin:4px 0;line-height:0">{!! \App\Support\Barcode::svg((string) $order->order_number, 34, 1.3) !!}</div>
<div class="row"><span class="label">Date:</span><span>{{ $order->created_at->format('d/m/Y g:ia') }}</span></div>
<div class="divider"></div>
<div class="row"><span class="label">Name:</span><span>{{ $order->shipping_name }}</span></div>
<div class="row"><span class="label">Phone:</span><span>{{ $order->shipping_phone }}</span></div>
<div class="row"><span class="label">Address:</span><span style="max-width:48mm;text-align:right;">{{ Str::limit($order->shipping_address, 30) }}</span></div>
<div class="divider"></div>
<table>
<thead><tr><th>Item</th><th style="text-align:center;">Qty</th><th style="text-align:right;">Total</th></tr></thead>
<tbody>
@foreach($order->items->take(5) as $item)
<tr>
    <td>{{ Str::limit($item->product_name ?? ($item->product->name ?? 'Product'), 20) }}</td>
    <td style="text-align:center;">{{ $item->qty }}</td>
    <td style="text-align:right;">৳{{ number_format($item->total, 0) }}</td>
</tr>
@endforeach
</tbody>
</table>
@if(($order->discount ?? 0) > 0)
<div class="row" style="margin-top:2px;color:#666;font-size:8px;"><span>Discount:</span><span>-৳{{ number_format($order->discount, 0) }}</span></div>
@endif
<div class="row total-row" style="margin-top:2px;padding-top:2px;border-top:1px dashed #000;"><span>Total:</span><span>৳{{ number_format($order->total, 0) }}</span></div>
<div class="row" style="margin-top:2px;font-size:8px;"><span class="label">Payment:</span><span>{{ strtoupper($order->payment_method ?? 'COD') }}</span></div>
<div class="footer">ধন্যবাদ আপনার ক্রয়ের জন্য!</div>
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
