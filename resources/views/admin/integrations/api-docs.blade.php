@extends('layouts.admin')

@section('title', 'API docs — ' . $site->name)
@section('page-title', 'API for ' . $site->name)

@section('breadcrumb')
    <span class="text-textsecondary">/</span>
    <a href="{{ route('admin.integrations.wordpress.index') }}" class="text-textsecondary hover:text-textprimary">Integrations</a>
    <span class="text-textsecondary">/</span>
    <span class="text-textprimary font-medium">API docs</span>
@endsection

@php
    $base = rtrim(config('app.url'), '/') . '/api/v1';
    $key  = \App\Support\PluginSignature::connectionKey($site->id, (string) $site->api_key);
    $code = 'block w-full overflow-x-auto p-4 rounded-xl bg-gray-900 text-gray-100 text-xs leading-relaxed font-mono whitespace-pre';
    $h2   = 'text-base font-bold text-textprimary dark:text-white';
    $card = 'p-5 rounded-2xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 space-y-3';
    $orderJson = <<<'JSON'
{
  "external_id": "WEB-10045",
  "order_number": "10045",
  "customer": {
    "name": "Rahim Uddin",
    "phone": "01712345678",
    "address": "House 7, Road 3, Mirpur 10",
    "area": "Mirpur",
    "district": "Dhaka"
  },
  "items": [
    { "name": "Winter Jacket - Black", "sku": "WJ-BLK-L", "variant": "Size: L", "qty": 1, "price": 1350 }
  ],
  "shipping_charge": 90,
  "discount": 0,
  "payment_method": "cod",
  "note": "Call before delivery",
  "ip": "103.120.10.5",
  "utm_source": "facebook"
}
JSON;
@endphp

@section('content')
<div class="max-w-4xl space-y-5" x-data="{ show: false, copied: '' }">

    <div class="{{ $card }}">
        <h2 class="{{ $h2 }}">1. Your key</h2>
        <p class="text-sm text-textsecondary">Send it on every request as <code>Authorization: Bearer &lt;key&gt;</code>. Keep it on your server only — never in browser JavaScript or an app.</p>
        <div class="flex flex-wrap items-center gap-2">
            <code class="flex-1 min-w-0 px-3 py-2 rounded-lg bg-gray-100 dark:bg-gray-700 text-xs font-mono break-all" x-text="show ? @js($key) : '••••••••••••••••••••••••••••••••'"></code>
            <button type="button" @click="show = !show" class="px-3 py-2 text-xs rounded-lg border border-gray-200 dark:border-gray-600" x-text="show ? 'Hide' : 'Show'"></button>
            <button type="button" @click="navigator.clipboard.writeText(@js($key)); copied = 'key'; setTimeout(() => copied = '', 2000)" class="px-3 py-2 text-xs font-semibold rounded-lg bg-primary text-white">
                <span x-show="copied !== 'key'">Copy key</span><span x-show="copied === 'key'" x-cloak>Copied!</span>
            </button>
        </div>
        <p class="text-xs text-textsecondary">Base URL: <code>{{ $base }}</code> · JSON in, JSON out · HTTPS only · up to 300 requests a minute.</p>
    </div>

    <div class="{{ $card }}">
        <h2 class="{{ $h2 }}">2. Send an order — <code class="text-primary">POST /orders</code></h2>
        <p class="text-sm text-textsecondary">
            Call this right after your checkout saves the order. Sending the same <code>external_id</code> again <strong>updates</strong> the order instead of making a duplicate
            (contact details always; items and totals only while the order is still pending in the CRM). Send <code>"status": "cancelled"</code> if the customer cancels on your site.
        </p>
        <pre class="{{ $code }}">curl -X POST {{ $base }}/orders \
  -H "Authorization: Bearer YOUR_KEY" \
  -H "Content-Type: application/json" \
  -d '{{ $orderJson }}'</pre>
        <table class="w-full text-xs">
            <thead><tr class="text-left text-textsecondary"><th class="py-1 pr-3">Field</th><th class="py-1 pr-3">Required</th><th class="py-1">Notes</th></tr></thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                <tr><td class="py-1 pr-3 font-mono">external_id</td><td>yes</td><td>Your own order id. Unique per order.</td></tr>
                <tr><td class="py-1 pr-3 font-mono">customer.name / phone / address</td><td>yes</td><td>Phone: Bangladeshi mobile, any format (01…, +880…).</td></tr>
                <tr><td class="py-1 pr-3 font-mono">customer.district, area, email, alt_phone</td><td>no</td><td></td></tr>
                <tr><td class="py-1 pr-3 font-mono">items[].name, qty, price</td><td>yes</td><td><code>sku</code> links the line to a CRM product; <code>variant</code> is free text (size/colour).</td></tr>
                <tr><td class="py-1 pr-3 font-mono">shipping_charge, discount</td><td>no</td><td><code>total</code> is calculated if you leave it out.</td></tr>
                <tr><td class="py-1 pr-3 font-mono">payment_method</td><td>no</td><td><code>cod</code> (default), <code>bkash</code>, <code>nagad</code>, <code>rocket</code>, <code>card</code>; <code>paid: true</code> if already paid.</td></tr>
                <tr><td class="py-1 pr-3 font-mono">note, ip, utm_*, created_at, order_number</td><td>no</td><td></td></tr>
            </tbody>
        </table>
        <p class="text-xs text-textsecondary">Answer: <code>201</code> created / <code>200</code> updated, with <code>order.order_number</code> (the CRM number) and <code>order.status</code>.</p>
    </div>

    <div class="{{ $card }}">
        <h2 class="{{ $h2 }}">3. Check before accepting an order — <code class="text-primary">POST /checkout/check</code></h2>
        <p class="text-sm text-textsecondary">Optional. Ask before you save the order: blocked numbers / IPs and too many repeat orders (rules from Settings → Order Rules) are refused. If the CRM does not answer within a few seconds, accept the order.</p>
        <pre class="{{ $code }}">curl -X POST {{ $base }}/checkout/check \
  -H "Authorization: Bearer YOUR_KEY" -H "Content-Type: application/json" \
  -d '{"phone": "01712345678", "ip": "103.120.10.5"}'

→ {"ok": true, "allowed": true}
→ {"ok": true, "allowed": false, "reason": "phone_blocked", "message": "This phone number is not allowed to place orders"}</pre>
    </div>

    <div class="{{ $card }}">
        <h2 class="{{ $h2 }}">4. Save unfinished checkouts — <code class="text-primary">POST /incomplete</code></h2>
        <p class="text-sm text-textsecondary">
            Send what the visitor has typed so far (at least the phone number) when they leave the phone field. If they never finish, the CRM lists them under
            <strong>Failed Orders</strong> so your team can call them. When they do order, it is marked recovered automatically. Use one <code>session_id</code> per visitor/checkout.
        </p>
        <pre class="{{ $code }}">curl -X POST {{ $base }}/incomplete \
  -H "Authorization: Bearer YOUR_KEY" -H "Content-Type: application/json" \
  -d '{"session_id": "a1b2c3", "phone": "01712345678", "name": "Rahim", "address": "Mirpur 10",
       "items": [{"name": "Winter Jacket - Black", "qty": 1, "price": 1350}], "total": 1440,
       "page_url": "https://yoursite.com/checkout", "ip": "103.120.10.5", "user_agent": "Mozilla/5.0 ..."}'</pre>
    </div>

    <div class="{{ $card }}">
        <h2 class="{{ $h2 }}">5. Read an order's status — <code class="text-primary">GET /orders/{external_id}</code></h2>
        <pre class="{{ $code }}">curl {{ $base }}/orders/WEB-10045 -H "Authorization: Bearer YOUR_KEY"

→ {"ok": true, "order": {"external_id": "WEB-10045", "order_number": "EPL20261030-48213",
     "status": "shipped", "courier": "steadfast", "tracking_code": "SF123456", ...}}</pre>
    </div>

    <div class="{{ $card }}">
        <h2 class="{{ $h2 }}">6. Status webhook (optional)</h2>
        <p class="text-sm text-textsecondary">
            @if($site->webhook_url)
                We POST to <code>{{ $site->webhook_url }}</code> whenever an order's status changes in the CRM.
            @else
                Add a webhook URL on the store (Integrations → Edit) and we POST to it whenever an order's status changes in the CRM.
            @endif
            Check the signature before trusting it: <code>X-EP-Signature</code> = HMAC-SHA256 of <code>"{X-EP-Timestamp}.{raw body}"</code> with the part of your key after the dot.
        </p>
        <pre class="{{ $code }}">{"event": "order.status_changed",
 "order": {"external_id": "WEB-10045", "order_number": "EPL20261030-48213", "status": "delivered", ...}}</pre>
    </div>

    <div class="{{ $card }}">
        <h2 class="{{ $h2 }}">PHP example</h2>
        <pre class="{{ $code }}">$key = getenv('ECOM_PILOT_KEY');   // {{ $site->id }}.xxxxxxxx — keep it in your .env

$response = file_get_contents('{{ $base }}/orders', false, stream_context_create(['http' => [
    'method'  => 'POST',
    'header'  => "Authorization: Bearer $key\r\nContent-Type: application/json\r\n",
    'content' => json_encode($order),   // array in the format shown above
    'timeout' => 10,
    'ignore_errors' => true,
]]));
$result = json_decode($response, true);   // $result['order']['order_number']

// Verifying the status webhook
$secret = explode('.', $key, 2)[1];
$raw    = file_get_contents('php://input');
$ok     = hash_equals(
    hash_hmac('sha256', $_SERVER['HTTP_X_EP_TIMESTAMP'] . '.' . $raw, $secret),
    $_SERVER['HTTP_X_EP_SIGNATURE'] ?? ''
) && abs(time() - (int) $_SERVER['HTTP_X_EP_TIMESTAMP']) < 300;</pre>
    </div>

    <div class="{{ $card }}">
        <h2 class="{{ $h2 }}">Node.js example (server side)</h2>
        <pre class="{{ $code }}">const res = await fetch('{{ $base }}/orders', {
  method: 'POST',
  headers: { Authorization: `Bearer ${process.env.ECOM_PILOT_KEY}`, 'Content-Type': 'application/json' },
  body: JSON.stringify(order),
  signal: AbortSignal.timeout(10000),
});
const data = await res.json();   // data.order.order_number</pre>
    </div>

    <div class="{{ $card }}">
        <h2 class="{{ $h2 }}">Errors</h2>
        <table class="w-full text-xs">
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                <tr><td class="py-1 pr-3 font-mono">401 missing_key / invalid_key</td><td>The Authorization header is missing or the key is wrong.</td></tr>
                <tr><td class="py-1 pr-3 font-mono">403 store_inactive</td><td>The store is switched off in Integrations.</td></tr>
                <tr><td class="py-1 pr-3 font-mono">422</td><td>A field is missing or wrong — the response lists which ones under <code>errors</code>.</td></tr>
                <tr><td class="py-1 pr-3 font-mono">429</td><td>Too many requests. Wait a minute and retry.</td></tr>
                <tr><td class="py-1 pr-3 font-mono">5xx / timeout</td><td>Retry later with the same <code>external_id</code> — it never creates a duplicate.</td></tr>
            </tbody>
        </table>
    </div>
</div>
@endsection
