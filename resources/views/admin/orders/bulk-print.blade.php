<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk Print — {{ count($orders) }} Orders</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 12px; color: #111; }
        .order-page {
            page-break-after: always;
            padding: 24px;
            max-width: 680px;
            margin: 0 auto;
        }
        .order-page:last-child { page-break-after: avoid; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #111; padding-bottom: 12px; margin-bottom: 16px; }
        .order-num { font-size: 18px; font-weight: bold; }
        .status-badge { display: inline-block; padding: 4px 10px; border: 1px solid #111; border-radius: 4px; font-size: 11px; font-weight: bold; text-transform: uppercase; }
        .section { margin-bottom: 14px; }
        .section-title { font-size: 10px; font-weight: bold; text-transform: uppercase; color: #666; letter-spacing: 0.05em; margin-bottom: 4px; }
        .customer-name { font-size: 14px; font-weight: bold; }
        .phone { font-size: 13px; margin-top: 2px; }
        .address { margin-top: 2px; color: #333; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th { background: #f3f3f3; text-align: left; padding: 6px 8px; font-size: 11px; border: 1px solid #ddd; }
        td { padding: 6px 8px; border: 1px solid #ddd; vertical-align: top; }
        .totals { margin-top: 12px; text-align: right; }
        .totals tr td { border: none; padding: 2px 0; }
        .totals .grand-total { font-size: 15px; font-weight: bold; border-top: 1px solid #111; padding-top: 6px; margin-top: 4px; }
        .footer { margin-top: 16px; padding-top: 10px; border-top: 1px dashed #ccc; font-size: 10px; color: #999; display: flex; justify-content: space-between; }
        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>

@foreach($orders as $order)
<div class="order-page">

    <div class="header">
        <div>
            <div class="order-num">#{{ $order->order_number }}</div>
            <div style="font-size:11px; color:#666; margin-top:3px;">{{ $order->created_at->format('d M Y, H:i') }}</div>
        </div>
        <div class="status-badge">{{ ucwords(str_replace('_', ' ', $order->status)) }}</div>
    </div>

    <div class="section">
        <div class="section-title">Customer</div>
        <div class="customer-name">{{ $order->shipping_name }}</div>
        <div class="phone">{{ $order->shipping_phone }}</div>
        <div class="address">
            {{ collect([$order->shipping_address, $order->shipping_area, $order->shipping_district])->filter()->implode(', ') }}
        </div>
    </div>

    <div class="section">
        <div class="section-title">Items ({{ $order->items->count() }})</div>
        <table>
            <thead>
                <tr>
                    <th>Product</th>
                    <th style="width:60px; text-align:center;">Qty</th>
                    <th style="width:80px; text-align:right;">Price</th>
                    <th style="width:90px; text-align:right;">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                <tr>
                    <td>
                        {{ $item->product_name }}
                        @if($item->variant_info)
                            <br><span style="font-size:10px; color:#666;">{{ $item->variant_info }}</span>
                        @endif
                    </td>
                    <td style="text-align:center;">{{ $item->qty }}</td>
                    <td style="text-align:right;">৳{{ number_format($item->unit_price) }}</td>
                    <td style="text-align:right;">৳{{ number_format($item->qty * $item->unit_price) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="totals">
        <table style="width:240px; margin-left:auto;">
            <tr>
                <td style="color:#666;">Subtotal</td>
                <td style="text-align:right;">৳{{ number_format($order->subtotal) }}</td>
            </tr>
            @if($order->discount > 0)
            <tr>
                <td style="color:#666;">Discount</td>
                <td style="text-align:right; color:#16a34a;">-৳{{ number_format($order->discount) }}</td>
            </tr>
            @endif
            @if($order->shipping_charge > 0)
            <tr>
                <td style="color:#666;">Shipping</td>
                <td style="text-align:right;">৳{{ number_format($order->shipping_charge) }}</td>
            </tr>
            @endif
            <tr class="grand-total">
                <td><strong>Total</strong></td>
                <td style="text-align:right;"><strong>৳{{ number_format($order->total) }}</strong></td>
            </tr>
        </table>
    </div>

    <div class="footer">
        <span>Payment: {{ strtoupper($order->payment_method) }} — {{ ucfirst($order->payment_status) }}</span>
        <span>Printed: {{ now()->format('d M Y, H:i') }}</span>
    </div>

</div>
@endforeach

<script>window.print();</script>
</body>
</html>
