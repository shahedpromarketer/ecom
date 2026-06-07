<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\SyncLog;
use App\Models\WordPressSite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class IntegrationController extends Controller
{
    public function wordpressIndex()
    {
        $sites      = WordPressSite::withCount('logs')->latest()->get();
        $logs       = SyncLog::with(['site', 'order'])->latest()->take(10)->get();
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

    public function syncOrders(WordPressSite $site)
    {
        try {
            $credentials = base64_encode($site->consumer_key . ':' . $site->consumer_secret);

            $response = Http::timeout(30)
                ->withoutVerifying()
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
                    $exists = Order::where('source_order_id', $wcOrder['id'])
                        ->where('source', 'woocommerce')
                        ->exists();

                    if ($exists) continue;

                    $billing = $wcOrder['billing'];
                    $phone   = $billing['phone'] ?? '';

                    $phone = preg_replace('/[^0-9]/', '', $phone);
                    if (strlen($phone) > 11) {
                        $phone = substr($phone, -11);
                    }

                    $order = Order::create([
                        'order_number'      => 'CLT' . date('Ymd') . '-WC' . $wcOrder['id'],
                        'status'            => 'pending',
                        'shipping_name'     => trim($billing['first_name'] . ' ' . $billing['last_name']),
                        'shipping_phone'    => $phone ?: '01000000000',
                        'shipping_division' => $billing['state'] ?? 'Dhaka',
                        'shipping_district' => $billing['city'] ?? 'Dhaka',
                        'shipping_area'     => $billing['city'] ?? '',
                        'shipping_address'  => $billing['address_1'] ?? '',
                        'subtotal'          => $wcOrder['subtotal'] ?? $wcOrder['total'],
                        'discount'          => $wcOrder['discount_total'] ?? 0,
                        'shipping_charge'   => $wcOrder['shipping_total'] ?? 0,
                        'total'             => $wcOrder['total'],
                        'payment_method'    => 'cod',
                        'payment_status'    => $wcOrder['payment_method'] === 'cod' ? 'unpaid' : 'paid',
                        'source'            => 'woocommerce',
                        'source_order_id'   => (string) $wcOrder['id'],
                        'source_url'        => $site->site_url,
                        'print_status'      => 'unprinted',
                    ]);

                    foreach ($wcOrder['line_items'] as $item) {
                        $product = Product::where('name', 'like', '%' . $item['name'] . '%')
                            ->orWhereHas('variants', fn($q) => $q->where('sku', $item['sku']))
                            ->first();

                        OrderItem::create([
                            'order_id'     => $order->id,
                            'product_id'   => $product?->id ?? null,
                            'product_name' => $item['name'],
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
        return response()->json(['success' => true, 'message' => 'Product sync coming soon.']);
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

    public function pullNewOrders(WordPressSite $site): array
    {
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
                ->withoutVerifying()
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

                if (Order::where('source_order_id', (string) $wcOrder['id'])
                    ->where('source', 'woocommerce')->exists()) continue;

                $billing = $wcOrder['billing'] ?? [];
                $phone   = preg_replace('/[^0-9]/', '', $billing['phone'] ?? '');
                if (strlen($phone) > 11) $phone = substr($phone, -11);
                if (empty($phone)) $phone = '01000000000';

                $order = Order::create([
                    'order_number'      => 'CLT' . date('Ymd') . '-WC' . $wcOrder['id'],
                    'status'            => 'pending',
                    'shipping_name'     => trim(($billing['first_name'] ?? '') . ' ' . ($billing['last_name'] ?? '')),
                    'shipping_phone'    => $phone,
                    'shipping_division' => $billing['state'] ?? 'Dhaka',
                    'shipping_district' => $billing['city'] ?? 'Dhaka',
                    'shipping_area'     => $billing['city'] ?? '',
                    'shipping_address'  => $billing['address_1'] ?? '',
                    'subtotal'          => $wcOrder['subtotal'] ?? $wcOrder['total'],
                    'discount'          => $wcOrder['discount_total'] ?? 0,
                    'shipping_charge'   => $wcOrder['shipping_total'] ?? 0,
                    'total'             => $wcOrder['total'],
                    'payment_method'    => 'cod',
                    'payment_status'    => 'unpaid',
                    'source'            => 'woocommerce',
                    'source_order_id'   => (string) $wcOrder['id'],
                    'source_url'        => $site->site_url,
                    'print_status'      => 'unprinted',
                ]);

                foreach ($wcOrder['line_items'] ?? [] as $item) {
                    OrderItem::create([
                        'order_id'     => $order->id,
                        'product_id'   => null,
                        'product_name' => $item['name'] ?? 'Unknown',
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
}
