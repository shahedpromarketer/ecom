<?php

namespace App\Services;

use App\Http\Controllers\Api\PublicStoreApiController;
use App\Models\SyncLog;
use App\Models\WordPressSite;
use App\Support\Phone;
use App\Support\Tenancy;
use Illuminate\Support\Facades\Http;

/**
 * Turns rows of a Google Sheet into orders.
 *
 * The sheet must be shared "Anyone with the link → Viewer" (we read its CSV export).
 * settings: sheet_url, gid, mapping {field => column header}, start_row, last_result.
 * Every row (or every group of rows sharing an order-id column) becomes one order;
 * re-reading the sheet never duplicates — rows are keyed by their order id or content.
 */
class GoogleSheetImporter
{
    /** CRM field => what the shop owner sees in the mapping form. */
    public const FIELDS = [
        'order_id'   => 'Order ID (optional — rows with the same id become one order)',
        'name'       => 'Customer name *',
        'phone'      => 'Phone *',
        'address'    => 'Address *',
        'district'   => 'District',
        'product'    => 'Product *',
        'variant'    => 'Size / colour',
        'qty'        => 'Quantity',
        'price'      => 'Unit price',
        'total'      => 'Order total',
        'shipping'   => 'Delivery charge',
        'note'       => 'Note',
        'date'       => 'Order date',
    ];

    public const REQUIRED = ['name', 'phone', 'address', 'product'];

    public static function csvUrl(string $sheetUrl, ?string $gid = null): ?string
    {
        if (! preg_match('~/spreadsheets/d/([a-zA-Z0-9_-]{20,})~', $sheetUrl, $m)) {
            return null;
        }
        if ($gid === null && preg_match('~[#&?]gid=(\d+)~', $sheetUrl, $g)) {
            $gid = $g[1];
        }

        return 'https://docs.google.com/spreadsheets/d/' . $m[1] . '/export?format=csv&gid=' . ($gid ?: '0');
    }

    /** @return array{ok: bool, rows: array<int, array<string,string>>, headers: array<int,string>, error: ?string} */
    public static function fetch(string $sheetUrl, ?string $gid = null): array
    {
        $url = self::csvUrl($sheetUrl, $gid);
        if (! $url) {
            return ['ok' => false, 'rows' => [], 'headers' => [], 'error' => 'This does not look like a Google Sheets link.'];
        }

        try {
            $res = Http::timeout(20)->withOptions(['allow_redirects' => true])->get($url);
        } catch (\Throwable $e) {
            return ['ok' => false, 'rows' => [], 'headers' => [], 'error' => 'Could not reach Google: ' . $e->getMessage()];
        }

        $body = $res->body();
        if (! $res->successful() || str_contains(substr($body, 0, 500), '<html')) {
            return ['ok' => false, 'rows' => [], 'headers' => [], 'error' => 'Google refused to share the sheet. Set Share → General access → "Anyone with the link" (Viewer).'];
        }

        $lines = self::parseCsv($body);
        $headers = array_map(fn ($h) => trim((string) $h), array_shift($lines) ?? []);
        $rows = [];
        foreach ($lines as $line) {
            if (count(array_filter($line, fn ($v) => trim((string) $v) !== '')) === 0) {
                $rows[] = [];   // keep row numbers stable
                continue;
            }
            $row = [];
            foreach ($headers as $i => $h) {
                if ($h !== '') {
                    $row[$h] = trim((string) ($line[$i] ?? ''));
                }
            }
            $rows[] = $row;
        }

        return ['ok' => true, 'rows' => $rows, 'headers' => array_values(array_filter($headers, fn ($h) => $h !== '')), 'error' => null];
    }

    /** @return array{created:int, updated:int, skipped:int, invalid:int, error:?string} */
    public static function run(WordPressSite $site): array
    {
        $s = (array) ($site->settings ?? []);
        $result = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'invalid' => 0, 'error' => null];

        $map = (array) ($s['mapping'] ?? []);
        foreach (self::REQUIRED as $f) {
            if (empty($map[$f])) {
                $result['error'] = 'Column mapping is not finished.';
                return self::finish($site, $result);
            }
        }

        $data = self::fetch((string) ($s['sheet_url'] ?? ''), $s['gid'] ?? null);
        if (! $data['ok']) {
            $result['error'] = $data['error'];
            return self::finish($site, $result);
        }

        $startRow = max(2, (int) ($s['start_row'] ?? 2));   // sheet row numbers; row 1 = headers
        $orders = [];
        $seen = [];

        foreach ($data['rows'] as $i => $row) {
            $sheetRow = $i + 2;
            if ($sheetRow < $startRow || ! $row) {
                continue;
            }
            $get = fn (string $f) => isset($map[$f]) && $map[$f] !== '' ? ($row[$map[$f]] ?? '') : '';

            $phone = Phone::normalize($get('phone'));
            if (! Phone::isValidBdMobile($phone) || $get('product') === '' || $get('name') === '') {
                $result['invalid']++;
                continue;
            }

            // Rows sharing an order id are one order; otherwise each row is its own order,
            // keyed by its content (so adding rows above it later does not import it twice).
            if ($get('order_id') !== '') {
                $key = 'id:' . $get('order_id');
            } else {
                $base = 'row:' . substr(sha1($phone . '|' . $get('name') . '|' . $get('product') . '|' . $get('variant') . '|' . $get('date') . '|' . $get('qty')), 0, 32);
                $seen[$base] = ($seen[$base] ?? 0) + 1;
                $key = $seen[$base] > 1 ? $base . '-' . $seen[$base] : $base;
            }
            $qty = max(1, (int) preg_replace('/\D/', '', $get('qty') ?: '1'));
            $price = (float) preg_replace('/[^\d.]/', '', $get('price'));

            $orders[$key] ??= [
                'external_id' => mb_substr($key, 0, 100),
                'order_number'=> $get('order_id') ?: 'Sheet row ' . $sheetRow,
                'customer'    => ['name' => $get('name'), 'phone' => $phone, 'address' => $get('address'), 'district' => $get('district') ?: null],
                'items'       => [],
                'note'        => $get('note') ?: null,
                'shipping_charge' => (float) preg_replace('/[^\d.]/', '', $get('shipping')),
                'total'       => $get('total') !== '' ? (float) preg_replace('/[^\d.]/', '', $get('total')) : null,
                'created_at'  => self::date($get('date')),
            ];
            $orders[$key]['items'][] = ['name' => $get('product'), 'variant' => $get('variant') ?: null, 'qty' => $qty, 'price' => $price];
        }

        Tenancy::as($site->tenant_id, function () use ($site, $orders, &$result) {
            foreach ($orders as $o) {
                if ($o['total'] === null) {
                    unset($o['total']);
                }
                try {
                    [, $what] = WooOrderImporter::import($site, PublicStoreApiController::toImporterPayload($o));
                    $result[$what === 'created' ? 'created' : ($what === 'updated' ? 'updated' : 'skipped')]++;
                } catch (\Throwable $e) {
                    report($e);
                    $result['invalid']++;
                }
            }
        });

        return self::finish($site, $result);
    }

    private static function finish(WordPressSite $site, array $result): array
    {
        $s = (array) ($site->settings ?? []);
        $s['last_result'] = $result + ['at' => now()->toIso8601String()];
        $site->forceFill(['settings' => $s, 'last_sync_at' => now(), 'last_order_sync_at' => now()])->saveQuietly();
        if ($result['created']) {
            $site->increment('total_synced', $result['created']);
        }

        if ($result['error'] || $result['created'] || $result['invalid']) {
            SyncLog::create([
                'site_id' => $site->id,
                'status'  => $result['error'] ? 'failed' : 'success',
                'message' => $result['error'] ?? "Sheet: {$result['created']} new orders, {$result['updated']} updated, {$result['invalid']} rows skipped (missing phone/name/product)",
            ]);
        }

        return $result;
    }

    private static function date(string $v): ?string
    {
        if ($v === '') {
            return null;
        }
        try {
            return \Illuminate\Support\Carbon::parse(str_replace('/', '-', $v))->toIso8601String();
        } catch (\Throwable $e) {
            return null;
        }
    }

    private static function parseCsv(string $csv): array
    {
        $out = [];
        $h = fopen('php://temp', 'r+');
        fwrite($h, $csv);
        rewind($h);
        while (($row = fgetcsv($h, 0, ',', '"', '')) !== false) {
            $out[] = $row;
        }
        fclose($h);

        return $out;
    }
}
