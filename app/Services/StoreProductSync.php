<?php

namespace App\Services;

use App\Models\SyncLog;
use App\Models\WordPressSite;
use App\Support\PluginSignature;
use App\Support\Tenancy;
use Illuminate\Support\Facades\Http;

/** Pulls the whole catalogue from a store through the Ecom Pilot plugin, page by page. */
class StoreProductSync
{
    public const PER_PAGE = 25;

    /** @return array{created: int, updated: int, failed: int, error: ?string} */
    public static function pull(WordPressSite $site, int $maxPages = 200): array
    {
        $result = ['created' => 0, 'updated' => 0, 'failed' => 0, 'error' => null];
        @set_time_limit(600);

        Tenancy::as($site->tenant_id, function () use ($site, $maxPages, &$result) {
            for ($page = 1; $page <= $maxPages; $page++) {
                $res = self::call($site, 'products', ['page' => $page, 'per_page' => self::PER_PAGE]);
                if (! $res['ok']) {
                    $result['error'] = $res['error'];
                    break;
                }

                foreach ($res['data']['products'] ?? [] as $p) {
                    try {
                        [, $what] = WooProductImporter::upsert($site, $p);
                        $result[$what]++;
                    } catch (\Throwable $e) {
                        report($e);
                        $result['failed']++;
                    }
                }

                if ($page >= (int) ($res['data']['total_pages'] ?? 1)) {
                    break;
                }
            }
        });

        $site->forceFill([
            'last_product_sync_at'  => now(),
            'total_products_synced' => $result['created'] + $result['updated'],
        ])->saveQuietly();

        SyncLog::create([
            'site_id' => $site->id,
            'status'  => $result['error'] ? 'failed' : 'success',
            'message' => $result['error']
                ? 'Product sync stopped: ' . $result['error']
                : "Products synced: {$result['created']} new, {$result['updated']} updated" . ($result['failed'] ? ", {$result['failed']} failed" : ''),
        ]);

        return $result;
    }

    /** Signed POST to the plugin's REST API. */
    public static function call(WordPressSite $site, string $route, array $data, int $timeout = 30): array
    {
        $base = rtrim($site->rest_url ?: rtrim($site->site_url, '/') . '/wp-json', '/');
        $body = json_encode($data);
        $sig = PluginSignature::sign((string) $site->api_key, $body);

        try {
            $response = Http::timeout($timeout)
                ->withOptions(['verify' => (bool) config('services.woocommerce.verify_ssl', true)])
                ->withHeaders([
                    'X-EP-Site'      => (string) $site->id,
                    'X-EP-Timestamp' => (string) $sig['timestamp'],
                    'X-EP-Signature' => $sig['signature'],
                    'Accept'         => 'application/json',
                ])
                ->withBody($body, 'application/json')
                ->post($base . '/ecom-pilot/v1/' . $route);
        } catch (\Throwable $e) {
            return ['ok' => false, 'data' => null, 'error' => $e->getMessage()];
        }

        if ($response->status() === 404) {
            return ['ok' => false, 'data' => null, 'error' => 'The store runs an older Ecom Pilot plugin. Update it to version 2.1 or newer.'];
        }

        return [
            'ok'    => $response->successful(),
            'data'  => $response->json(),
            'error' => $response->successful() ? null : 'HTTP ' . $response->status() . ' ' . mb_substr($response->body(), 0, 200),
        ];
    }
}
