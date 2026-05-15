<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 13px; color: #1f2937; background: #fff; }
        .invoice-wrapper { max-width: 800px; margin: 0 auto; padding: 40px; }

        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 32px; padding-bottom: 24px; border-bottom: 2px solid #e5e7eb; }
        .store-name { font-size: 28px; font-weight: 800; color: #1a3c5e; letter-spacing: -0.5px; }
        .store-sub { font-size: 12px; color: #6b7280; margin-top: 2px; }
        .invoice-meta { text-align: right; }
        .invoice-number { font-family: monospace; font-size: 18px; font-weight: 700; color: #1f2937; }
        .invoice-label { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; color: #9ca3af; }
        .invoice-date { font-size: 12px; color: #6b7280; margin-top: 4px; }

        .bill-to { margin-bottom: 24px; }
        .section-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 1.5px; color: #9ca3af; margin-bottom: 8px; }
        .bill-name { font-size: 15px; font-weight: 600; color: #1f2937; }
        .bill-detail { font-size: 12px; color: #6b7280; margin-top: 2px; }

        table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        thead tr { border-bottom: 2px solid #1a3c5e; }
        thead th { padding: 10px 8px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #374151; text-align: left; }
        thead th:last-child, thead th:nth-child(3), thead th:nth-child(4) { text-align: right; }
        thead th:nth-child(3) { text-align: center; }
        tbody tr { border-bottom: 1px solid #f3f4f6; }
        tbody td { padding: 10px 8px; font-size: 13px; color: #374151; }
        tbody td:last-child, tbody td:nth-child(4) { text-align: right; }
        tbody td:nth-child(3) { text-align: center; }

        .totals { display: flex; justify-content: flex-end; margin-bottom: 32px; }
        .totals-box { width: 240px; }
        .total-row { display: flex; justify-content: space-between; padding: 4px 0; font-size: 13px; color: #4b5563; }
        .total-row.grand { border-top: 2px solid #1a3c5e; margin-top: 8px; padding-top: 10px; font-size: 16px; font-weight: 700; color: #1f2937; }

        .footer { border-top: 1px solid #e5e7eb; padding-top: 16px; text-align: center; }
        .footer p { font-size: 12px; color: #6b7280; }
        .thank-you { font-size: 14px; font-weight: 600; color: #1a3c5e; margin-bottom: 6px; }

        .accent-bar { height: 4px; background: linear-gradient(90deg, #1a3c5e, #f59e0b); border-radius: 2px; margin-bottom: 32px; }

        @media print {
            body { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
            .no-print { display: none; }
            @page { margin: 1cm; size: A4; }
        }
    </style>
</head>
<body>

{{-- Print button (hidden on print) --}}
<div class="no-print" style="position:fixed;top:16px;right:16px;z-index:100;">
    <button onclick="window.print()" style="padding:8px 16px;background:#1a3c5e;color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;">🖨️ Print</button>
    <button onclick="window.close()" style="padding:8px 16px;background:#6b7280;color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;margin-left:8px;">Close</button>
</div>

<div class="invoice-wrapper">
    <div class="accent-bar"></div>

    <div class="header">
        <div>
            @php $storeName = \App\Models\Setting::getValue('store_name', 'Clothonic'); @endphp
            <div class="store-name">{{ $storeName }}</div>
            <div class="store-sub">{{ \App\Models\Setting::getValue('store_address', 'Dhaka, Bangladesh') }}</div>
            <div class="store-sub">{{ \App\Models\Setting::getValue('store_phone', '') }}</div>
            <div class="store-sub">{{ \App\Models\Setting::getValue('store_email', '') }}</div>
        </div>
        <div class="invoice-meta">
            <div class="invoice-label">Invoice</div>
            <div class="invoice-number">{{ $invoice->invoice_number }}</div>
            <div class="invoice-date">Date: {{ $invoice->created_at->format('d M Y') }}</div>
            <div class="invoice-date">Order: {{ $invoice->order?->order_number }}</div>
        </div>
    </div>

    <div class="bill-to">
        <div class="section-label">Bill To</div>
        <div class="bill-name">{{ $invoice->order?->shipping_name }}</div>
        <div class="bill-detail">{{ $invoice->order?->shipping_phone }}</div>
        <div class="bill-detail">{{ $invoice->order?->shipping_address }}</div>
        <div class="bill-detail">{{ $invoice->order?->shipping_area }}, {{ $invoice->order?->shipping_district }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Product</th>
                <th>Qty</th>
                <th>Unit Price</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->order?->items ?? [] as $i => $item)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>
                    {{ $item->product?->name }}
                    @if($item->variant)
                    <span style="font-size:11px;color:#9ca3af;">({{ $item->variant->size }}/{{ $item->variant->color }})</span>
                    @endif
                </td>
                <td>{{ $item->qty }}</td>
                <td>৳{{ number_format($item->unit_price, 0) }}</td>
                <td>৳{{ number_format($item->total, 0) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <div class="totals-box">
            <div class="total-row"><span>Subtotal</span><span>৳{{ number_format($invoice->subtotal, 0) }}</span></div>
            @if($invoice->discount > 0)
            <div class="total-row" style="color:#16a34a;"><span>Discount</span><span>-৳{{ number_format($invoice->discount, 0) }}</span></div>
            @endif
            <div class="total-row"><span>Shipping</span><span>৳{{ number_format($invoice->shipping, 0) }}</span></div>
            <div class="total-row grand"><span>Grand Total</span><span>৳{{ number_format($invoice->total, 0) }}</span></div>
        </div>
    </div>

    <div class="footer">
        <p class="thank-you">{{ \App\Models\Setting::getValue('invoice_footer_note', 'Thank you for shopping with Clothonic!') }}</p>
        <p>Payment Method: {{ strtoupper($invoice->order?->payment_method ?? 'COD') }}</p>
    </div>
</div>

<script>
    window.addEventListener('load', function() {
        // Small delay to ensure styles are applied before printing
        setTimeout(() => window.print(), 500);
    });
</script>
</body>
</html>
