<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
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
        if (!$site->consumer_key || !$site->consumer_secret) {
            return response()->json(['success' => false, 'message' => 'Consumer key/secret not configured.']);
        }

        $credentials = base64_encode("{$site->consumer_key}:{$site->consumer_secret}");
        $apiUrl = rtrim($site->site_url, '/') . '/wp-json/wc/v3/orders?per_page=100&status=any';

        try {
            $response = Http::withHeaders(['Authorization' => "Basic {$credentials}"])
                ->timeout(30)
                ->get($apiUrl);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Connection error: ' . $e->getMessage()]);
        }

        if (!$response->successful()) {
            return response()->json(['success' => false, 'message' => 'WooCommerce API error (HTTP ' . $response->status() . ')']);
        }

        $wcOrders = $response->json();
        if (!is_array($wcOrders)) {
            return response()->json(['success' => false, 'message' => 'Invalid API response.']);
        }

        $synced = 0;
        $failed = 0;
        $statusMap = [
            'pending'    => 'pending',
            'processing' => 'confirmed',
            'completed'  => 'completed',
            'cancelled'  => 'cancelled',
            'on-hold'    => 'hold',
            'refunded'   => 'returned',
        ];

        foreach ($wcOrders as $wcOrder) {
            $sourceOrderId = (string) ($wcOrder['id'] ?? '');
            if (!$sourceOrderId) {
                continue;
            }

            if (SyncLog::where('site_id', $site->id)
                       ->where('source_order_id', $sourceOrderId)
                       ->where('status', 'success')
                       ->exists()) {
                continue;
            }

            DB::beginTransaction();
            try {
                $billing = $wcOrder['billing'] ?? [];
                $name    = trim(($billing['first_name'] ?? '') . ' ' . ($billing['last_name'] ?? '')) ?: 'Unknown';
                $phone   = $billing['phone'] ?? '';
                $address = implode(', ', array_filter([$billing['address_1'] ?? '', $billing['address_2'] ?? '']));

                $order = Order::create([
                    'source_order_id'   => $sourceOrderId,
                    'status'            => $statusMap[$wcOrder['status'] ?? ''] ?? 'pending',
                    'shipping_name'     => $name,
                    'shipping_phone'    => $phone,
                    'shipping_address'  => $address,
                    'shipping_area'     => $billing['state'] ?? '',
                    'shipping_district' => $billing['city'] ?? '',
                    'total'             => (float) ($wcOrder['total'] ?? 0),
                    'subtotal'          => (float) ($wcOrder['total'] ?? 0),
                    'payment_method'    => $wcOrder['payment_method'] ?? 'cod',
                    'payment_status'    => ($wcOrder['status'] === 'completed') ? 'paid' : 'unpaid',
                    'source'            => 'wordpress',
                    'source_url'        => $site->site_url,
                    'notes'             => "Auto-synced from {$site->name}",
                ]);

                SyncLog::create([
                    'site_id'         => $site->id,
                    'order_id'        => $order->id,
                    'source_order_id' => $sourceOrderId,
                    'status'          => 'success',
                    'message'         => "Synced from {$site->name}",
                ]);

                DB::commit();
                $synced++;
            } catch (\Throwable $e) {
                DB::rollBack();
                SyncLog::create([
                    'site_id'         => $site->id,
                    'source_order_id' => $sourceOrderId,
                    'status'          => 'failed',
                    'message'         => $e->getMessage(),
                ]);
                $failed++;
            }
        }

        $site->update([
            'last_order_sync_at' => now(),
            'last_sync_at'       => now(),
            'total_synced'       => $site->total_synced + $synced,
        ]);

        return response()->json(['success' => true, 'synced' => $synced, 'failed' => $failed]);
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
}
