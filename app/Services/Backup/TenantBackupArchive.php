<?php

namespace App\Services\Backup;

use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use ZipArchive;

/**
 * One shop's own data in a zip, for the shop's own Google Drive: orders, products & stock,
 * customers, courier parcels… Not included: the wallet (the CRM's money), staff accounts,
 * and secrets (courier keys, website/API keys) — those stay as they are on a restore.
 *
 * Restore only ever touches rows of that shop, and refuses a backup of another shop.
 */
class TenantBackupArchive
{
    /** Tables with tenant_id that belong in a shop backup. */
    public const TABLES = [
        'categories', 'products', 'customers', 'coupons', 'orders', 'order_notes', 'order_returns',
        'invoices', 'invoice_templates', 'courier_shipments', 'courier_events', 'courier_payments',
        'followups', 'leads', 'incomplete_orders', 'blacklisted_phones', 'ip_blocklist',
        'marketing_pixels', 'saved_filters', 'courier_scores', 'roles', 'settings',
    ];

    /** Tables without tenant_id, reached through a parent: table => [column, parent table]. Order matters. */
    public const CHILDREN = [
        'product_variants' => ['product_id', 'products'],
        'product_images'   => ['product_id', 'products'],
        'category_product' => ['product_id', 'products'],
        'order_items'      => ['order_id', 'orders'],   // after variants: items point at them
        'customer_notes'   => ['customer_id', 'customers'],
        'stock_logs'       => ['variant_id', 'product_variants'],
    ];

    public static function create(Tenant $tenant, string $dir): array
    {
        @set_time_limit(0);
        File::ensureDirectoryExists($dir);
        $slug = \Illuminate\Support\Str::slug($tenant->name) ?: 'shop';
        $path = $dir . DIRECTORY_SEPARATOR . 'backup-' . $slug . '-' . now()->format('Y-m-d-His') . '.zip';

        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Could not create the backup file.');
        }

        $counts = [];
        $tmp = $dir . DIRECTORY_SEPARATOR . 'tmp-' . uniqid();
        File::ensureDirectoryExists($tmp);
        $plan = self::plan();
        try {
            foreach ($plan as $table => $how) {
                $file = $tmp . DIRECTORY_SEPARATOR . $table . '.jsonl';
                $out = fopen($file, 'w');
                $n = 0;
                foreach (self::scoped($table, $plan, $tenant->id)->cursor() as $row) {
                    fwrite($out, json_encode($row, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) . "\n");
                    $n++;
                }
                fclose($out);
                $zip->addFile($file, 'tables/' . $table . '.jsonl');
                $counts[$table] = $n;
            }

        $manifest = [
            'app' => 'ecom-pilot', 'kind' => 'shop', 'format' => 1,
            'tenant_id' => $tenant->id, 'shop' => $tenant->name,
            'created_at' => now()->toIso8601String(), 'tables' => $counts,
        ];
            $zip->addFromString('manifest.json', json_encode($manifest, JSON_PRETTY_PRINT));
            $zip->close();
        } finally {
            File::deleteDirectory($tmp);
        }

        return [$path, $manifest];
    }

    /** A readable list of the shop's orders, for opening in Drive / Excel. */
    public static function ordersCsv(Tenant $tenant, string $dir): string
    {
        File::ensureDirectoryExists($dir);
        $path = $dir . DIRECTORY_SEPARATOR . 'orders-' . (\Illuminate\Support\Str::slug($tenant->name) ?: 'shop') . '-' . now()->format('Y-m-d') . '.csv';
        $out = fopen($path, 'w');
        fwrite($out, "\xEF\xBB\xBF");   // so Excel shows Bangla correctly
        fputcsv($out, ['Order', 'Date', 'Status', 'Name', 'Phone', 'Address', 'District', 'Items', 'Total', 'Paid', 'Courier', 'Tracking', 'Source'], ',', '"', '');

        DB::table('orders')->where('tenant_id', $tenant->id)->orderByDesc('id')
            ->chunkById(500, function ($orders) use ($out) {
                $items = DB::table('order_items')->whereIn('order_id', $orders->pluck('id'))->get()->groupBy('order_id');
                $ships = DB::table('courier_shipments')->whereIn('order_id', $orders->pluck('id'))->get()->keyBy('order_id');
                foreach ($orders as $o) {
                    $list = ($items[$o->id] ?? collect())->map(fn ($i) => $i->product_name . ($i->variant_info ? ' (' . $i->variant_info . ')' : '') . ' x' . $i->qty)->implode('; ');
                    $s = $ships[$o->id] ?? null;
                    fputcsv($out, [$o->order_number, $o->created_at, $o->status, $o->shipping_name, $o->shipping_phone, $o->shipping_address,
                        $o->shipping_district, $list, $o->total, $o->paid_amount, $s->courier_name ?? '', $s->tracking_code ?? '', $o->source], ',', '"', '');
                }
            });
        fclose($out);

        return $path;
    }

    public static function manifest(string $path): array
    {
        $zip = self::open($path);
        $m = json_decode((string) $zip->getFromName('manifest.json'), true);
        $zip->close();
        if (! is_array($m) || ($m['app'] ?? null) !== 'ecom-pilot' || ($m['kind'] ?? null) !== 'shop') {
            throw new RuntimeException('This is not a shop backup file.');
        }

        return $m;
    }

    /** Replace this shop's data with the backup's. Other shops are never touched. */
    public static function restore(Tenant $tenant, string $path): array
    {
        @set_time_limit(0);
        $m = self::manifest($path);
        if ((int) $m['tenant_id'] !== (int) $tenant->id) {
            throw new RuntimeException('This backup belongs to another shop.');
        }
        $zip = self::open($path);
        $plan = self::plan();


        $restored = [];
        Schema::disableForeignKeyConstraints();
        DB::beginTransaction();
        try {
            // Empty the shop (children first)
            foreach (array_reverse($plan, true) as $table => $how) {
                // Children go first, while their parents still exist for the sub-select to find them.
                if (! Schema::hasColumn($table, 'id')) {
                    self::scoped($table, $plan, $tenant->id)->delete();   // e.g. category_product
                    continue;
                }
                foreach (array_chunk(self::scoped($table, $plan, $tenant->id)->pluck('id')->all(), 1000) as $chunk) {
                    DB::table($table)->whereIn('id', $chunk)->delete();
                }
            }

            // Refill from the backup (parents first). Rows are forced onto this shop.
            $ids = [];   // table => set of restored ids
            foreach ($plan as $table => $how) {
                $columns = array_flip(Schema::getColumnListing($table));
                $raw = (string) $zip->getFromName('tables/' . $table . '.jsonl');
                $rows = [];
                foreach (preg_split('/\R/', $raw) as $line) {
                    if (trim($line) === '') {
                        continue;
                    }
                    $row = array_intersect_key(json_decode($line, true) ?? [], $columns);
                    if ($how === 'tenant') {
                        if ($table === 'settings' && str_starts_with((string) ($row['key'] ?? ''), 'backup_')) {
                            continue;
                        }
                        $row['tenant_id'] = $tenant->id;
                    } elseif (! isset($ids[$how[1]][(string) ($row[$how[0]] ?? '')])) {
                        continue;   // child of something not in this backup
                    }
                    $rows[] = $row;
                }
                $hasId = isset($columns['id']);
                $done = [];
                foreach (array_chunk($rows, 300) as $chunk) {
                    if ($hasId) {
                        // This shop's rows are gone now, so an id that still exists belongs to another shop: skip it, never overwrite.
                        $taken = array_flip(array_map('strval', DB::table($table)->whereIn('id', array_column($chunk, 'id'))->pluck('id')->all()));
                        $chunk = array_values(array_filter($chunk, fn ($r) => ! isset($taken[(string) $r['id']])));
                        if ($chunk) {
                            DB::table($table)->insert($chunk);
                        }
                    } else {
                        DB::table($table)->insertOrIgnore($chunk);
                    }
                    array_push($done, ...$chunk);
                }
                $ids[$table] = $hasId ? array_flip(array_map('strval', array_column($done, 'id'))) : [];
                $restored[$table] = count($done);
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        } finally {
            Schema::enableForeignKeyConstraints();
            $zip->close();
        }

        \Illuminate\Support\Facades\Cache::flush();

        return $restored;
    }

    /** This shop's rows of a table; children through a sub-select on their parent (works for any size). */
    private static function scoped(string $table, array $plan, int $tenantId)
    {
        $how = $plan[$table];
        $q = DB::table($table);
        if ($how === 'tenant') {
            $q->where('tenant_id', $tenantId);
            if ($table === 'settings') {
                $q->where('key', 'not like', BackupArchive::KEEP_SETTINGS);
            }

            return $q;
        }

        return $q->whereIn($how[0], self::scoped($how[1], $plan, $tenantId)->select($how[1] . '.id'));
    }

    /** table => 'tenant' | [column, parent], only tables that exist here. */
    private static function plan(): array
    {
        $plan = [];
        foreach (self::TABLES as $t) {
            if (Schema::hasTable($t)) {
                $plan[$t] = 'tenant';
            }
        }
        foreach (self::CHILDREN as $t => $how) {
            if (Schema::hasTable($t) && isset($plan[$how[1]])) {
                $plan[$t] = $how;
            }
        }

        return $plan;
    }

    private static function open(string $path): ZipArchive
    {
        $zip = new ZipArchive();
        if (! is_file($path) || $zip->open($path) !== true) {
            throw new RuntimeException('The backup file could not be opened.');
        }

        return $zip;
    }
}
