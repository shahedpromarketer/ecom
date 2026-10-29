<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\SyncLog;
use App\Models\WordPressSite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class IntegrationController extends Controller
{
    public function wordpressIndex()
    {
        $tenantId   = auth()->user()->tenant_id;
        $isSA       = auth()->user()->is_super_admin;
        $siteQ      = WordPressSite::withCount('logs')->latest();
        if (!$isSA) $siteQ->where('tenant_id', $tenantId);
        $sites      = $siteQ->get();

        $logQ = SyncLog::with(['site', 'order'])->latest()->take(10);
        if (!$isSA) $logQ->whereHas('site', fn($q) => $q->where('tenant_id', $tenantId));
        $logs       = $logQ->get();
        $webhookUrl = url('/api/wordpress/sync');

        return view('admin.integrations.wordpress.index', compact('sites', 'logs', 'webhookUrl'));
    }

    public function wordpressStore(Request $request)
    {
        $request->validate([
            'site_name'       => 'required|string|max:255',
            'site_url'        => 'required|url|max:255',
            'type'            => 'required|in:wordpress,woocommerce,cartflow,landing_page',
            'consumer_key'    => 'nullable|string|max:255',
            'consumer_secret' => 'nullable|string|max:255',
            'mobile_number'   => 'nullable|string|max:20',
            'invoice_title'   => 'nullable|string|max:255',
            'invoice_address' => 'nullable|string|max:1000',
            'logo'            => 'nullable|image|max:1024',
        ]);

        $logoPath = null;
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('site-logos', 'public');
        }

        $site = WordPressSite::create([
            'name'            => $request->site_name,
            'site_url'        => rtrim($request->site_url, '/'),
            'type'            => $request->type,
            'consumer_key'    => $request->consumer_key,
            'consumer_secret' => $request->consumer_secret,
            'mobile_number'   => $request->mobile_number,
            'invoice_title'   => $request->invoice_title,
            'invoice_address' => $request->invoice_address,
            'logo'            => $logoPath,
            'api_key'         => WordPressSite::generateApiKey(),
            'tenant_id'       => auth()->user()->tenant_id,
        ]);

        return response()->json(['success' => true, 'site' => $site]);
    }

    public function wordpressUpdate(Request $request, WordPressSite $site)
    {
        $request->validate([
            'site_name'       => 'required|string|max:255',
            'site_url'        => 'required|url|max:255',
            'type'            => 'required|in:wordpress,woocommerce,cartflow,landing_page',
            'consumer_key'    => 'nullable|string|max:255',
            'consumer_secret' => 'nullable|string|max:255',
            'mobile_number'   => 'nullable|string|max:20',
            'invoice_title'   => 'nullable|string|max:255',
            'invoice_address' => 'nullable|string|max:1000',
            'logo'            => 'nullable|image|max:1024',
        ]);

        $data = [
            'name'            => $request->site_name,
            'site_url'        => rtrim($request->site_url, '/'),
            'type'            => $request->type,
            'consumer_key'    => $request->consumer_key,
            'consumer_secret' => $request->consumer_secret,
            'mobile_number'   => $request->mobile_number,
            'invoice_title'   => $request->invoice_title,
            'invoice_address' => $request->invoice_address,
        ];

        if ($request->hasFile('logo')) {
            if ($site->logo) {
                Storage::disk('public')->delete($site->logo);
            }
            $data['logo'] = $request->file('logo')->store('site-logos', 'public');
        }

        $site->update($data);

        return response()->json(['success' => true, 'site' => $site->fresh()]);
    }

    public function wordpressDestroy(WordPressSite $site)
    {
        if ($site->logo) {
            Storage::disk('public')->delete($site->logo);
        }
        $site->delete();

        return response()->json(['success' => true]);
    }

    public function toggleActive(WordPressSite $site)
    {
        $site->update(['is_active' => !$site->is_active]);

        return response()->json(['success' => true, 'is_active' => $site->is_active]);
    }

    public function forceConnect(WordPressSite $site)
    {
        $now = $site->plugin_connected ? null : now();
        $site->update([
            'plugin_connected'    => !$site->plugin_connected,
            'plugin_connected_at' => $now,
            'plugin_version'      => $site->plugin_connected ? null : 'manual',
        ]);

        return response()->json([
            'success'          => true,
            'plugin_connected' => $site->plugin_connected,
        ]);
    }

    public function syncOrders(WordPressSite $site)
    {
        if (!$site->plugin_connected) {
            return response()->json([
                'success' => false,
                'message' => 'Ecom Pilot Netpilot plugin is not connected. Please install and connect the plugin on your WordPress site first.',
            ], 403);
        }

        try {
            $credentials = base64_encode($site->consumer_key . ':' . $site->consumer_secret);

            $response = Http::timeout(30)
                ->withOptions(['verify' => (bool) config('services.woocommerce.verify_ssl', true)])
                ->withHeaders([
                    'Authorization' => 'Basic ' . $credentials,
                ])
                ->get($site->site_url . '/wp-json/wc/v3/orders', [
                    'per_page' => 100,
                    'status'   => 'any',
                    'orderby'  => 'date',
                    'order'    => 'desc',
                ]);

            if ($response->failed()) {
                return response()->json([
                    'success' => false,
                    'message' => 'API Error: ' . $response->status() . ' - ' . $response->body()
                ]);
            }

            $orders = $response->json();
            $synced = 0;
            $failed = 0;

            foreach ($orders as $wcOrder) {
                try {
                    $existing = Order::where('source_order_id', $wcOrder['id'])
                        ->where('source', 'woocommerce')
                        ->first();

                    if ($existing) {
                        // Backfill packing_note if empty
                        if (empty($existing->packing_note)) {
                            $note = $this->extractPackingNote($wcOrder);
                            if ($note) $existing->update(['packing_note' => $note]);
                        }
                        continue;
                    }

                    $billing = $wcOrder['billing'];
                    $phone   = $billing['phone'] ?? '';

                    $phone = preg_replace('/[^0-9]/', '', $phone);
                    if (strlen($phone) > 11) {
                        $phone = substr($phone, -11);
                    }

                    $order = new Order([
                        'order_number'      => 'EPL' . \Carbon\Carbon::parse($wcOrder['date_created'] ?? now())->format('Ymd') . '-WC' . $wcOrder['id'],
                        'status'            => 'pending',
                        'shipping_name'     => trim($billing['first_name'] . ' ' . $billing['last_name']),
                        'shipping_phone'    => $phone ?: '01000000000',
                        'shipping_division' => $billing['state'] ?? 'Dhaka',
                        'shipping_district' => $billing['city'] ?? 'Dhaka',
                        'shipping_area'     => $billing['city'] ?? '',
                        'shipping_address'  => $billing['address_1'] ?? '',
                        'subtotal'          => collect($wcOrder['line_items'] ?? [])->sum('subtotal'),
                        'discount'          => $wcOrder['discount_total'] ?? 0,
                        'shipping_charge'   => $wcOrder['shipping_total'] ?? 0,
                        'total'             => $wcOrder['total'],
                        'payment_method'    => 'cod',
                        'payment_status'    => $wcOrder['payment_method'] === 'cod' ? 'unpaid' : 'paid',
                        'source'            => 'woocommerce',
                        'source_order_id'   => (string) $wcOrder['id'],
                        'source_url'        => $site->site_url,
                        'platform'          => $this->extractPlatform($wcOrder),
                        'print_status'      => 'unprinted',
                        'packing_note'      => $this->extractPackingNote($wcOrder),
                        'tenant_id'         => $site->tenant_id,
                    ]);
                    $order->created_at = \Carbon\Carbon::parse($wcOrder['date_created'] ?? now());
                    $order->save();

                    $billingSize = $this->extractBillingSize($wcOrder);

                    foreach ($wcOrder['line_items'] as $item) {
                        $product = $this->matchProduct($item, $billingSize);
                        $variant = $this->matchVariant($product, $billingSize);

                        $variantInfo = collect($item['meta_data'] ?? [])
                            ->filter(fn($m) => !str_starts_with($m['key'], '_') && !empty($m['display_value']))
                            ->map(fn($m) => $m['display_value'])
                            ->implode(' / ');

                        // Use billing_size as variant_info if no line-item meta
                        if (!$variantInfo && $billingSize) {
                            $variantInfo = $billingSize;
                        }

                        OrderItem::create([
                            'order_id'     => $order->id,
                            'product_id'   => $product?->id ?? null,
                            'variant_id'   => $variant?->id ?? null,
                            'product_name' => $item['name'],
                            'variant_info' => $variantInfo ?: null,
                            'qty'          => $item['quantity'],
                            'unit_price'   => $item['price'],
                            'total'        => $item['total'],
                        ]);
                    }

                    SyncLog::create([
                        'site_id'         => $site->id,
                        'order_id'        => $order->id,
                        'source_order_id' => (string) $wcOrder['id'],
                        'status'          => 'success',
                        'message'         => 'Order synced successfully',
                    ]);

                    $synced++;

                } catch (\Exception $e) {
                    SyncLog::create([
                        'site_id'         => $site->id,
                        'source_order_id' => (string) ($wcOrder['id'] ?? ''),
                        'status'          => 'failed',
                        'message'         => substr($e->getMessage(), 0, 500),
                    ]);
                    $failed++;
                }
            }

            $site->update([
                'last_order_sync_at' => now(),
                'total_synced'       => $site->total_synced + $synced,
            ]);

            return response()->json([
                'success' => true,
                'synced'  => $synced,
                'failed'  => $failed,
                'message' => "$synced orders synced, $failed failed"
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Connection error: ' . $e->getMessage()
            ]);
        }
    }

    public function syncProducts(WordPressSite $site)
    {
        if (!$site->plugin_connected) {
            return response()->json([
                'success' => false,
                'message' => 'Ecom Pilot Netpilot plugin is not connected. Please install and connect the plugin on your WordPress site first.',
            ], 403);
        }

        // Ecom Pilot Connector v2+: pull through the plugin (no WooCommerce API keys needed).
        if ($site->rest_url) {
            $r = \App\Services\StoreProductSync::pull($site);

            return response()->json([
                'success' => $r['error'] === null,
                'synced'  => $r['created'] + $r['updated'],
                'message' => $r['error'] ?? "{$r['created']} new, {$r['updated']} updated" . ($r['failed'] ? ", {$r['failed']} failed" : ''),
            ], $r['error'] ? 422 : 200);
        }

        try {
            $credentials = base64_encode($site->consumer_key . ':' . $site->consumer_secret);
            $page    = 1;
            $synced  = 0;
            $failed  = 0;

            do {
                $response = Http::timeout(30)
                    ->withOptions(['verify' => (bool) config('services.woocommerce.verify_ssl', true)])
                    ->withHeaders(['Authorization' => 'Basic ' . $credentials])
                    ->get($site->site_url . '/wp-json/wc/v3/products', [
                        'per_page' => 50,
                        'page'     => $page,
                        'status'   => 'publish',
                    ]);

                if ($response->failed()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'API Error: ' . $response->status() . ' - ' . $response->body(),
                    ]);
                }

                $products = $response->json();
                if (empty($products)) break;

                foreach ($products as $wpProduct) {
                    try {
                        // Resolve price from multiple fields (out-of-stock products may have empty 'price')
                        if (! empty($wpProduct['price'])) {
                            $price = (float) $wpProduct['price'];
                        } elseif (! empty($wpProduct['sale_price'])) {
                            $price = (float) $wpProduct['sale_price'];
                        } elseif (! empty($wpProduct['regular_price'])) {
                            $price = (float) $wpProduct['regular_price'];
                        } else {
                            $price = 0;
                        }

                        $regularPrice = ! empty($wpProduct['regular_price']) ? (float) $wpProduct['regular_price'] : $price;
                        $salePrice    = ! empty($wpProduct['sale_price'])    ? (float) $wpProduct['sale_price']    : null;

                        // Stock: prefer stock_quantity, fall back to stock_status flag
                        if (isset($wpProduct['stock_quantity']) && $wpProduct['stock_quantity'] !== null) {
                            $stock = (int) $wpProduct['stock_quantity'];
                        } elseif (($wpProduct['stock_status'] ?? '') === 'instock') {
                            $stock = 1;
                        } else {
                            $stock = 0;
                        }

                        $existing = Product::where('wp_product_id', $wpProduct['id'])
                            ->where('tenant_id', $site->tenant_id)
                            ->first();

                        $slug = $wpProduct['slug'] ?? Str::slug($wpProduct['name']);
                        if (! $existing) {
                            $base  = $slug;
                            $count = 1;
                            while (Product::where('slug', $slug)->where('tenant_id', $site->tenant_id)->exists()) {
                                $slug = $base . '-' . $count++;
                            }
                        } else {
                            $slug = $existing->slug;
                        }

                        $product = Product::updateOrCreate(
                            ['wp_product_id' => $wpProduct['id'], 'tenant_id' => $site->tenant_id],
                            [
                                'name'              => $wpProduct['name'],
                                'slug'              => $slug,
                                'description'       => $wpProduct['description'] ?? '',
                                'short_description' => $wpProduct['short_description'] ?? '',
                                'price'             => $price,
                                'regular_price'     => $regularPrice,
                                'sale_price'        => $salePrice,
                                'sku'               => $wpProduct['sku'] ?: null,
                                'status'            => $wpProduct['status'] === 'publish' ? 'active' : 'inactive',
                            ]
                        );

                        // Sync images — store WooCommerce URLs directly in image_path
                        if (!empty($wpProduct['images'])) {
                            $product->images()->delete();
                            foreach ($wpProduct['images'] as $idx => $img) {
                                if (!empty($img['src'])) {
                                    $product->images()->create([
                                        'image_path' => $img['src'],
                                        'is_primary' => $idx === 0,
                                        'sort_order' => $idx,
                                    ]);
                                }
                            }
                        }

                        $synced++;
                    } catch (\Exception $e) {
                        $failed++;
                        \Log::warning('Product sync failed', [
                            'site_id'    => $site->id,
                            'wp_id'      => $wpProduct['id'] ?? null,
                            'name'       => $wpProduct['name'] ?? null,
                            'error'      => $e->getMessage(),
                        ]);
                    }
                }

                $page++;
            } while (count($products) === 50);

            $site->update([
                'last_product_sync_at'  => now(),
                'total_products_synced' => ($site->total_products_synced ?? 0) + $synced,
            ]);

            return response()->json([
                'success' => true,
                'synced'  => $synced,
                'failed'  => $failed,
                'message' => "$synced products synced, $failed failed",
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Connection error: ' . $e->getMessage(),
            ]);
        }
    }

    public function testConnection(WordPressSite $site)
    {
        if (!$site->consumer_key || !$site->consumer_secret) {
            return response()->json(['success' => false, 'connected' => false, 'message' => 'Consumer key/secret not configured.']);
        }

        $credentials = base64_encode("{$site->consumer_key}:{$site->consumer_secret}");
        $apiUrl      = rtrim($site->site_url, '/') . '/wp-json/wc/v3/system_status';

        try {
            $response = Http::withHeaders(['Authorization' => "Basic {$credentials}"])
                ->timeout(15)
                ->get($apiUrl);

            if ($response->successful()) {
                return response()->json(['success' => true, 'connected' => true]);
            }

            return response()->json([
                'success'   => false,
                'connected' => false,
                'message'   => 'HTTP ' . $response->status() . ': Invalid credentials or site not reachable.',
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'connected' => false, 'message' => $e->getMessage()]);
        }
    }

    public function regenerateKey(WordPressSite $site)
    {
        $newKey = WordPressSite::generateApiKey();
        $site->update(['api_key' => $newKey]);

        return response()->json(['success' => true, 'api_key' => $newKey]);
    }

    public function importRecentOrders(WordPressSite $site, Request $request)
    {
        if (!$site->plugin_connected) {
            return response()->json([
                'success' => false,
                'message' => 'Ecom Pilot Netpilot plugin is not connected. Please install and connect the plugin on your WordPress site first.',
            ], 403);
        }

        $days = in_array((int) $request->input('days'), [1, 2]) ? (int) $request->input('days') : 2;

        try {
            $credentials = base64_encode($site->consumer_key . ':' . $site->consumer_secret);

            $response = Http::timeout(30)
                ->withOptions(['verify' => (bool) config('services.woocommerce.verify_ssl', true)])
                ->withHeaders([
                    'Authorization' => 'Basic ' . $credentials,
                ])
                ->get($site->site_url . '/wp-json/wc/v3/orders', [
                    'per_page' => 100,
                    'status'   => 'any',
                    'orderby'  => 'date',
                    'order'    => 'desc',
                    'after'    => now()->subDays($days - 1)->startOfDay()->toIso8601String(),
                ]);

            if ($response->failed()) {
                return response()->json([
                    'success' => false,
                    'message' => 'API Error: ' . $response->status() . ' - ' . $response->body()
                ]);
            }

            $orders   = $response->json();
            $imported = 0;
            $skipped  = 0;

            foreach ($orders as $wcOrder) {
                try {
                    $existing = Order::where('source_order_id', $wcOrder['id'])
                        ->where('source', 'woocommerce')
                        ->first();

                    if ($existing) {
                        $skipped++;
                        continue;
                    }

                    $billing = $wcOrder['billing'];
                    $phone   = preg_replace('/[^0-9]/', '', $billing['phone'] ?? '');
                    if (strlen($phone) > 11) {
                        $phone = substr($phone, -11);
                    }

                    $order = new Order([
                        'order_number'      => 'EPL' . \Carbon\Carbon::parse($wcOrder['date_created'] ?? now())->format('Ymd') . '-WC' . $wcOrder['id'],
                        'status'            => 'pending',
                        'shipping_name'     => trim($billing['first_name'] . ' ' . $billing['last_name']),
                        'shipping_phone'    => $phone ?: '01000000000',
                        'shipping_division' => $billing['state'] ?? 'Dhaka',
                        'shipping_district' => $billing['city'] ?? 'Dhaka',
                        'shipping_area'     => $billing['city'] ?? '',
                        'shipping_address'  => $billing['address_1'] ?? '',
                        'subtotal'          => collect($wcOrder['line_items'] ?? [])->sum('subtotal'),
                        'discount'          => $wcOrder['discount_total'] ?? 0,
                        'shipping_charge'   => $wcOrder['shipping_total'] ?? 0,
                        'total'             => $wcOrder['total'],
                        'payment_method'    => 'cod',
                        'payment_status'    => $wcOrder['payment_method'] === 'cod' ? 'unpaid' : 'paid',
                        'source'            => 'woocommerce',
                        'source_order_id'   => (string) $wcOrder['id'],
                        'source_url'        => $site->site_url,
                        'platform'          => $this->extractPlatform($wcOrder),
                        'print_status'      => 'unprinted',
                        'packing_note'      => $this->extractPackingNote($wcOrder),
                        'tenant_id'         => $site->tenant_id,
                    ]);
                    $order->created_at = \Carbon\Carbon::parse($wcOrder['date_created'] ?? now());
                    $order->save();

                    $billingSize = $this->extractBillingSize($wcOrder);

                    foreach ($wcOrder['line_items'] as $item) {
                        $product     = $this->matchProduct($item, $billingSize);
                        $variant     = $this->matchVariant($product, $billingSize);
                        $variantInfo = collect($item['meta_data'] ?? [])
                            ->filter(fn($m) => !str_starts_with($m['key'], '_') && !empty($m['display_value']))
                            ->map(fn($m) => $m['display_value'])
                            ->implode(' / ');

                        if (!$variantInfo && $billingSize) {
                            $variantInfo = $billingSize;
                        }

                        OrderItem::create([
                            'order_id'     => $order->id,
                            'product_id'   => $product?->id ?? null,
                            'variant_id'   => $variant?->id ?? null,
                            'product_name' => $item['name'],
                            'variant_info' => $variantInfo ?: null,
                            'qty'          => $item['quantity'],
                            'unit_price'   => $item['price'],
                            'total'        => $item['total'],
                        ]);
                    }

                    SyncLog::create([
                        'site_id'         => $site->id,
                        'order_id'        => $order->id,
                        'source_order_id' => (string) $wcOrder['id'],
                        'status'          => 'success',
                        'message'         => "Imported via last {$days} day(s) pull",
                    ]);

                    $imported++;

                } catch (\Exception $e) {
                    SyncLog::create([
                        'site_id'         => $site->id,
                        'source_order_id' => (string) ($wcOrder['id'] ?? ''),
                        'status'          => 'failed',
                        'message'         => substr($e->getMessage(), 0, 500),
                    ]);
                }
            }

            if ($imported > 0) {
                $site->update([
                    'last_order_sync_at' => now(),
                    'total_synced'       => $site->total_synced + $imported,
                ]);
            }

            return response()->json([
                'success'  => true,
                'imported' => $imported,
                'skipped'  => $skipped,
                'message'  => "$imported orders imported, $skipped already existed",
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Connection error: ' . $e->getMessage()
            ]);
        }
    }

    public function pullNewOrders(WordPressSite $site): array
    {
        if (!$site->plugin_connected) {
            return ['synced' => 0, 'skipped' => 'plugin_not_connected'];
        }

        $credentials = base64_encode($site->consumer_key . ':' . $site->consumer_secret);

        $params = [
            'per_page' => 100,
            'orderby'  => 'date',
            'order'    => 'desc',
        ];

        if ($site->last_order_sync_at) {
            $params['after'] = $site->last_order_sync_at->toIso8601String();
        }

        try {
            $response = Http::timeout(10)
                ->withOptions(['verify' => (bool) config('services.woocommerce.verify_ssl', true)])
                ->withHeaders(['Authorization' => 'Basic ' . $credentials])
                ->get(rtrim($site->site_url, '/') . '/wp-json/wc/v3/orders', $params);

            if ($response->failed()) {
                return ['synced' => 0];
            }

            $orders = $response->json();
            if (!is_array($orders)) {
                return ['synced' => 0];
            }

            $synced = 0;

            foreach ($orders as $wcOrder) {
                if (!is_array($wcOrder) || empty($wcOrder['id'])) continue;

                $existingOrder = Order::where('source_order_id', (string) $wcOrder['id'])
                    ->where('source', 'woocommerce')->first();
                if ($existingOrder) {
                    if (empty($existingOrder->packing_note)) {
                        $note = $this->extractPackingNote($wcOrder);
                        if ($note) $existingOrder->update(['packing_note' => $note]);
                    }
                    continue;
                }

                $billing = $wcOrder['billing'] ?? [];
                $phone   = preg_replace('/[^0-9]/', '', $billing['phone'] ?? '');
                if (strlen($phone) > 11) $phone = substr($phone, -11);
                if (empty($phone)) $phone = '01000000000';

                $order = new Order([
                    'order_number'      => 'EPL' . \Carbon\Carbon::parse($wcOrder['date_created'] ?? now())->format('Ymd') . '-WC' . $wcOrder['id'],
                    'status'            => 'pending',
                    'shipping_name'     => trim(($billing['first_name'] ?? '') . ' ' . ($billing['last_name'] ?? '')),
                    'shipping_phone'    => $phone,
                    'shipping_division' => $billing['state'] ?? 'Dhaka',
                    'shipping_district' => $billing['city'] ?? 'Dhaka',
                    'shipping_area'     => $billing['city'] ?? '',
                    'shipping_address'  => $billing['address_1'] ?? '',
                    'subtotal'          => collect($wcOrder['line_items'] ?? [])->sum('subtotal'),
                    'discount'          => $wcOrder['discount_total'] ?? 0,
                    'shipping_charge'   => $wcOrder['shipping_total'] ?? 0,
                    'total'             => $wcOrder['total'],
                    'payment_method'    => 'cod',
                    'payment_status'    => 'unpaid',
                    'source'            => 'woocommerce',
                    'source_order_id'   => (string) $wcOrder['id'],
                    'source_url'        => $site->site_url,
                    'platform'          => $this->extractPlatform($wcOrder),
                    'print_status'      => 'unprinted',
                    'packing_note'      => $this->extractPackingNote($wcOrder),
                    'tenant_id'         => $site->tenant_id,
                ]);
                $order->created_at = \Carbon\Carbon::parse($wcOrder['date_created'] ?? now());
                $order->save();

                $billingSize = $this->extractBillingSize($wcOrder);

                foreach ($wcOrder['line_items'] ?? [] as $item) {
                    $product = $this->matchProduct($item, $billingSize);
                    $variant = $this->matchVariant($product, $billingSize);

                    $variantInfo = collect($item['meta_data'] ?? [])
                        ->filter(fn($m) => !str_starts_with($m['key'], '_') && !empty($m['display_value']))
                        ->map(fn($m) => $m['display_value'])
                        ->implode(' / ');

                    if (!$variantInfo && $billingSize) {
                        $variantInfo = $billingSize;
                    }

                    OrderItem::create([
                        'order_id'     => $order->id,
                        'product_id'   => $product?->id ?? null,
                        'variant_id'   => $variant?->id ?? null,
                        'product_name' => $item['name'] ?? 'Unknown',
                        'variant_info' => $variantInfo ?: null,
                        'qty'          => $item['quantity'] ?? 1,
                        'unit_price'   => $item['price'] ?? 0,
                        'total'        => $item['total'] ?? 0,
                    ]);
                }

                $synced++;
            }

            if ($synced > 0) {
                $site->update([
                    'last_order_sync_at' => now(),
                    'total_synced'       => $site->total_synced + $synced,
                ]);
            } else {
                // Update timestamp even when no new orders to avoid hammering every second
                $site->update(['last_order_sync_at' => now()]);
            }

            return ['synced' => $synced];

        } catch (\Exception $e) {
            return ['synced' => 0, 'error' => $e->getMessage()];
        }
    }

    private function matchProduct(array $item, ?string $billingSize): ?Product
    {
        $name = $item['name'] ?? '';
        $sku  = $item['sku']  ?? '';

        // 1. Name + size combined match (most specific)
        if ($billingSize) {
            $product = Product::where('name', 'like', '%' . $name . '%')
                ->where('name', 'like', '%' . $billingSize . '%')
                ->first();
            if ($product) return $product;
        }

        // 2. Exact name match
        $product = Product::where('name', $name)->first();
        if ($product) return $product;

        // 3. CRM product name contained in WC product name
        $product = Product::whereRaw('? LIKE CONCAT(\'%\', name, \'%\')', [$name])->first();
        if ($product) return $product;

        // 4. WC product name contained in CRM product name
        $product = Product::where('name', 'like', '%' . $name . '%')->first();
        if ($product) return $product;

        // 5. SKU match
        if ($sku) {
            $product = Product::whereHas('variants', fn($q) => $q->where('sku', $sku))->first();
            if ($product) return $product;
        }

        return null;
    }

    private function matchVariant(?Product $product, ?string $billingSize): ?ProductVariant
    {
        if (!$product || !$billingSize) return null;

        return $product->variants()
            ->where(fn($q) => $q->whereRaw('LOWER(size) = LOWER(?)', [$billingSize])
                ->orWhereRaw('LOWER(size) = LOWER(?)', [trim($billingSize)]))
            ->where('is_active', true)
            ->first();
    }

    private function extractBillingSize(array $wcOrder): ?string
    {
        $meta = collect($wcOrder['meta_data'] ?? [])->keyBy('key');

        return $meta->get('billing_size')['value']
            ?? $meta->get('_billing_size')['value']
            ?? null;
    }

    private function extractPackingNote(array $wcOrder): ?string
    {
        $size = $this->extractBillingSize($wcOrder);
        if (empty($size)) return null;

        return 'Size: ' . $size;
    }

    private function extractPlatform(array $wcOrder): ?string
    {
        $meta = collect($wcOrder['meta_data'] ?? [])->keyBy('key');

        $utmSource = $meta->get('_wc_order_attribution_utm_source')['value'] ?? null;
        if ($utmSource) {
            return strtolower($utmSource);
        }

        $sourceType = $meta->get('_wc_order_attribution_source_type')['value'] ?? null;
        if ($sourceType) {
            return strtolower($sourceType);
        }

        return null;
    }
}
