<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\SyncLog;
use App\Models\WordPressSite;
use Illuminate\Http\Request;

class WordPressSyncController extends Controller
{
    public function receiveOrder(Request $request)
    {
        // Authenticate via API key header
        $apiKey = $request->header('X-API-KEY') ?? $request->header('x-api-key');

        if (!$apiKey) {
            return response()->json(['error' => 'Missing API key'], 401);
        }

        $site = WordPressSite::where('api_key', $apiKey)->where('is_active', true)->first();

        if (!$site) {
            return response()->json(['error' => 'Invalid or inactive API key'], 401);
        }

        // Duplicate check, SKU lookup and the new order all stay inside this site's shop.
        \App\Support\Tenancy::set($site->tenant_id);

        // Validate minimal required fields
        $data = $request->validate([
            'id'              => 'required',
            'billing'         => 'required|array',
            'billing.phone'   => 'required|string',
            'total'           => 'required|numeric',
            'line_items'      => 'nullable|array',
        ]);

        $sourceOrderId = (string) $data['id'];

        // Duplicate check
        $existing = SyncLog::where('site_id', $site->id)
            ->where('source_order_id', $sourceOrderId)
            ->where('status', 'success')
            ->first();

        if ($existing) {
            SyncLog::create([
                'site_id'         => $site->id,
                'order_id'        => $existing->order_id,
                'source_order_id' => $sourceOrderId,
                'status'          => 'duplicate',
                'message'         => 'Order already synced.',
                'payload'         => $request->all(),
            ]);

            return response()->json([
                'success'      => true,
                'duplicate'    => true,
                'message'      => 'Order already exists.',
                'order_number' => $existing->order?->order_number,
            ]);
        }

        try {
            $billing = $data['billing'];

            // Map payment method
            $paymentMethod = $this->mapPaymentMethod($request->input('payment_method', ''));

            // Detect source
            $source = 'wordpress';
            $metaData = collect($request->input('meta_data', []))->keyBy('key');
            foreach ($request->input('meta_data', []) as $meta) {
                if (($meta['key'] ?? '') === '_cartflow_source') {
                    $source = 'cartflow';
                    break;
                }
            }

            // Detect platform from WooCommerce order attribution
            $platform = $metaData->get('_wc_order_attribution_utm_source')['value']
                ?? $metaData->get('_wc_order_attribution_source_type')['value']
                ?? null;
            if ($platform) {
                $platform = strtolower($platform);
            }

            // Create the order
            $order = Order::create([
                'shipping_name'     => trim(($billing['first_name'] ?? '') . ' ' . ($billing['last_name'] ?? '')),
                'shipping_phone'    => $billing['phone'] ?? '',
                'shipping_district' => $billing['state'] ?? $billing['city'] ?? '',
                'shipping_area'     => $billing['city'] ?? '',
                'shipping_address'  => trim(($billing['address_1'] ?? '') . ' ' . ($billing['address_2'] ?? '')),
                'subtotal'          => $request->input('subtotal', $data['total']),
                'discount'          => $request->input('discount_total', 0),
                'shipping_charge'   => $request->input('shipping_total', 0),
                'total'             => $data['total'],
                'payment_method'    => $paymentMethod,
                'payment_status'    => 'unpaid',
                'status'            => 'pending',
                'source'            => $source,
                'source_order_id'   => $sourceOrderId,
                'source_website'    => $site->site_url,
                'platform'          => $platform,
                'notes'             => $billing['note'] ?? $request->input('customer_note'),
                'tenant_id'         => $site->tenant_id,
            ]);

            // Create order items
            foreach ($request->input('line_items', []) as $item) {
                $productId   = null;
                $variantId   = null;
                $productName = $item['name'] ?? 'Unknown Product';
                $unitPrice   = $item['price'] ?? ($item['subtotal'] ? round($item['subtotal'] / max($item['quantity'], 1), 2) : 0);

                // Try to find product by SKU
                $sku = $item['sku'] ?? null;
                if ($sku) {
                    $variant = ProductVariant::where('sku', $sku)->first();
                    if ($variant) {
                        $variantId   = $variant->id;
                        $productId   = $variant->product_id;
                        $productName = $variant->product?->name ?? $productName;
                    } else {
                        $product = Product::where('sku', $sku)->first();
                        if ($product) {
                            $productId   = $product->id;
                            $productName = $product->name;
                        }
                    }
                }

                OrderItem::create([
                    'order_id'     => $order->id,
                    'product_id'   => $productId,
                    'variant_id'   => $variantId,
                    'product_name' => $productName,
                    'variant_info' => $item['variation'] ?? null,
                    'qty'          => $item['quantity'] ?? 1,
                    'unit_price'   => $unitPrice,
                    'total'        => $item['subtotal'] ?? ($unitPrice * ($item['quantity'] ?? 1)),
                ]);
            }

            // Update site stats
            $site->increment('total_synced');
            $site->update(['last_sync_at' => now()]);

            // Log success
            SyncLog::create([
                'site_id'         => $site->id,
                'order_id'        => $order->id,
                'source_order_id' => $sourceOrderId,
                'status'          => 'success',
                'message'         => 'Order synced successfully.',
                'payload'         => $request->all(),
            ]);

            return response()->json([
                'success'      => true,
                'order_number' => $order->order_number,
                'order_id'     => $order->id,
            ]);
        } catch (\Throwable $e) {
            SyncLog::create([
                'site_id'         => $site->id,
                'source_order_id' => $sourceOrderId,
                'status'          => 'failed',
                'message'         => $e->getMessage(),
                'payload'         => $request->all(),
            ]);

            return response()->json(['error' => 'Failed to sync order: ' . $e->getMessage()], 500);
        }
    }

    /**
     * WooCommerce native webhook (WooCommerce > Settings > Advanced > Webhooks).
     *
     * The webhook's "Secret" must be this site's API key from Ecom Pilot.
     * WooCommerce signs the raw body: X-WC-Webhook-Signature = base64(HMAC-SHA256(body, secret)).
     */
    public function receiveWebhook(Request $request)
    {
        $topic         = $request->header('X-WC-Webhook-Topic', '');
        $webhookSource = $request->header('X-WC-Webhook-Source', '');

        // WooCommerce sends a "ping" (webhook_id only) when the webhook is saved.
        if ($request->has('webhook_id') && ! $request->has('id')) {
            return response()->json(['success' => true, 'message' => 'pong']);
        }

        $matchedSite = $this->siteForWebhookSource($webhookSource);

        if (! $matchedSite || ! $this->hasValidWooSignature($request, $matchedSite)) {
            \Log::warning('WooCommerce webhook rejected (unknown site or bad signature)', ['source' => $webhookSource]);

            return response()->json(['error' => 'Invalid webhook signature'], 401);
        }

        // From here on everything (duplicate check, SKU lookup, new order) is inside this shop.
        \App\Support\Tenancy::set($matchedSite->tenant_id);

        // Ignore non-order topics
        if ($topic && !in_array($topic, ['order.created', 'order.updated', ''])) {
            return response()->json(['success' => true, 'message' => 'Topic ignored']);
        }

        $data = $request->all();
        $sourceOrderId = (string) ($data['id'] ?? '');

        if (!$sourceOrderId) {
            return response()->json(['error' => 'Missing order ID'], 422);
        }

        $existing = Order::where('source_order_id', $sourceOrderId)
            ->where('source_website', $webhookSource)
            ->first()
            ?? Order::where('source_order_id', $sourceOrderId)->where('source', 'woocommerce')->first();

        // Skip duplicate creates; allow updates
        if ($existing && $topic !== 'order.updated') {
            return response()->json([
                'success'      => true,
                'duplicate'    => true,
                'order_number' => $existing->order_number,
            ]);
        }

        $billing     = $data['billing'] ?? [];
        $phone       = preg_replace('/[^0-9]/', '', $billing['phone'] ?? '');
        $paymentMethod = $this->mapPaymentMethod($data['payment_method'] ?? 'cod');

        $subtotal = 0;
        foreach ($data['line_items'] ?? [] as $item) {
            $subtotal += (float) ($item['subtotal'] ?? $item['total'] ?? 0);
        }

        $webhookMeta = collect($data['meta_data'] ?? [])->keyBy('key');
        $platform = $webhookMeta->get('_wc_order_attribution_utm_source')['value']
            ?? $webhookMeta->get('_wc_order_attribution_source_type')['value']
            ?? null;
        if ($platform) {
            $platform = strtolower($platform);
        }

        $orderData = [
            'shipping_name'     => trim(($billing['first_name'] ?? '') . ' ' . ($billing['last_name'] ?? '')),
            'shipping_phone'    => $phone,
            'shipping_district' => $billing['state'] ?? $billing['city'] ?? '',
            'shipping_area'     => $billing['city'] ?? '',
            'shipping_address'  => trim(($billing['address_1'] ?? '') . ' ' . ($billing['address_2'] ?? '')),
            'subtotal'          => $subtotal,
            'discount'          => (float) ($data['discount_total'] ?? 0),
            'shipping_charge'   => (float) ($data['shipping_total'] ?? 0),
            'total'             => (float) ($data['total'] ?? $subtotal),
            'payment_method'    => $paymentMethod,
            'payment_status'    => 'unpaid',
            'status'            => 'pending',
            'source'            => 'woocommerce',
            'source_order_id'   => $sourceOrderId,
            'source_website'    => $webhookSource,
            'platform'          => $platform,
            'user_ip'           => $data['customer_ip_address'] ?? '',
            'reference_no'      => (string) ($data['number'] ?? $sourceOrderId),
            'notes'             => $data['customer_note'] ?? '',
        ];

        if ($existing) {
            $existing->update($orderData);
            $order = $existing->fresh();
        } else {
            $order = Order::create($orderData);
        }

        foreach ($data['line_items'] ?? [] as $item) {
            $productId   = null;
            $variantId   = null;
            $productName = $item['name'] ?? 'Unknown Product';
            $qty         = (int) ($item['quantity'] ?? 1);
            $unitPrice   = (float) ($item['price'] ?? ($qty > 0 ? round(($item['subtotal'] ?? 0) / $qty, 2) : 0));

            $sku = $item['sku'] ?? null;
            if ($sku) {
                $variant = ProductVariant::where('sku', $sku)->first();
                if ($variant) {
                    $variantId   = $variant->id;
                    $productId   = $variant->product_id;
                    $productName = $variant->product?->name ?? $productName;
                } else {
                    $product = Product::where('sku', $sku)->first();
                    if ($product) {
                        $productId   = $product->id;
                        $productName = $product->name;
                    }
                }
            }

            OrderItem::updateOrCreate(
                ['order_id' => $order->id, 'product_name' => $productName],
                [
                    'product_id'   => $productId,
                    'variant_id'   => $variantId,
                    'product_name' => $productName,
                    'variant_info' => !empty($item['variation_id']) ? 'Variant #' . $item['variation_id'] : null,
                    'qty'          => $qty,
                    'unit_price'   => $unitPrice,
                    'total'        => (float) ($item['subtotal'] ?? ($unitPrice * $qty)),
                ]
            );
        }

        $matchedSite->increment('total_synced');
        $matchedSite->update(['last_sync_at' => now()]);

        \Log::info('WooCommerce webhook order processed', [
            'order_id'     => $order->id,
            'order_number' => $order->order_number,
            'action'       => $existing ? 'updated' : 'created',
        ]);

        return response()->json([
            'success'      => true,
            'order_number' => $order->order_number,
            'order_id'     => $order->id,
        ]);
    }

    /** Active site whose URL has exactly the same host as the webhook source. */
    private function siteForWebhookSource(string $source): ?WordPressSite
    {
        $host = strtolower((string) parse_url($source, PHP_URL_HOST));
        if ($host === '') {
            return null;
        }

        $host = preg_replace('/^www\./', '', $host);

        return WordPressSite::withoutGlobalScope('tenant')
            ->where('is_active', true)
            ->get()
            ->first(function (WordPressSite $site) use ($host) {
                $siteHost = strtolower((string) parse_url($site->site_url, PHP_URL_HOST));

                return preg_replace('/^www\./', '', $siteHost) === $host;
            });
    }

    private function hasValidWooSignature(Request $request, WordPressSite $site): bool
    {
        $signature = (string) $request->header('X-WC-Webhook-Signature', '');
        if ($signature === '' || empty($site->api_key)) {
            return false;
        }

        $expected = base64_encode(hash_hmac('sha256', $request->getContent(), $site->api_key, true));

        return hash_equals($expected, $signature);
    }

    private function mapPaymentMethod(string $wpMethod): string
    {
        return match (strtolower($wpMethod)) {
            'cod', 'cash_on_delivery'    => 'cod',
            'bkash'                      => 'bkash',
            'nagad'                      => 'nagad',
            'stripe', 'paypal', 'card'   => 'card',
            default                      => 'cod',
        };
    }
}
