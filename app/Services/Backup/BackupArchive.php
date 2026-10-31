<?php

namespace App\Services\Backup;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use ZipArchive;

/**
 * A full copy of the CRM's data in one zip: every table (one JSON line per row) plus the
 * uploaded files (recharge screenshots, product images…). The code itself lives in GitHub.
 *
 * Restoring works on a fresh install too: deploy the code, run the migrations, restore.
 * Rows are matched to the current columns, so a backup from an older version still restores.
 */
class BackupArchive
{
    /** Runtime tables that are never backed up or restored. */
    public const SKIP = ['migrations', 'sessions', 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs', 'password_reset_tokens'];

    /** Settings that belong to this server's backup itself; a restore keeps the current ones. */
    public const KEEP_SETTINGS = 'backup_%';

    /** Uploaded files that go into the backup (relative to storage/app). */
    public const FILE_DIRS = ['private', 'public'];

    public static function tables(): array
    {
        // Only this app's database (MySQL can list other schemas the user can see).
        $schema = in_array(DB::getDriverName(), ['mysql', 'mariadb'], true) ? DB::getDatabaseName() : null;

        return collect(Schema::getTables($schema))
            ->pluck('name')
            ->reject(fn ($t) => in_array($t, self::SKIP, true) || str_starts_with($t, 'sqlite_'))
            ->values()
            ->all();
    }

    /** Build the zip and return [path, manifest]. */
    public static function create(?string $dir = null): array
    {
        @set_time_limit(0);
        $dir ??= storage_path('app/backups');
        File::ensureDirectoryExists($dir);

        $name = 'ecom-pilot-backup-' . now()->format('Y-m-d-His') . '.zip';
        $path = $dir . DIRECTORY_SEPARATOR . $name;
        $work = $dir . DIRECTORY_SEPARATOR . 'tmp-' . uniqid();
        File::ensureDirectoryExists($work);

        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Could not create the backup file.');
        }

        $counts = [];
        try {
            foreach (self::tables() as $table) {
                $file = $work . DIRECTORY_SEPARATOR . $table . '.jsonl';
                $out = fopen($file, 'w');
                $n = 0;
                foreach (DB::table($table)->cursor() as $row) {
                    fwrite($out, json_encode($row, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) . "\n");
                    $n++;
                }
                fclose($out);
                $zip->addFile($file, 'tables/' . $table . '.jsonl');
                $counts[$table] = $n;
            }

            $files = 0;
            foreach (self::FILE_DIRS as $sub) {
                $root = storage_path('app/' . $sub);
                if (! is_dir($root)) {
                    continue;
                }
                foreach (File::allFiles($root) as $f) {
                    if (str_starts_with($f->getFilename(), '.')) {
                        continue;
                    }
                    $zip->addFile($f->getPathname(), 'files/' . $sub . '/' . str_replace('\\', '/', $f->getRelativePathname()));
                    $files++;
                }
            }

            $manifest = [
                'app'        => 'ecom-pilot',
                'format'     => 1,
                'created_at' => now()->toIso8601String(),
                'url'        => config('app.url'),
                'migration'  => Schema::hasTable('migrations') ? DB::table('migrations')->max('migration') : null,
                'tables'     => $counts,
                'files'      => $files,
            ];
            $zip->addFromString('manifest.json', json_encode($manifest, JSON_PRETTY_PRINT));
            $zip->close();
        } finally {
            File::deleteDirectory($work);
        }

        return [$path, $manifest];
    }

    public static function manifest(string $path): array
    {
        $zip = self::open($path);
        $m = json_decode((string) $zip->getFromName('manifest.json'), true);
        $zip->close();
        if (! is_array($m) || ($m['app'] ?? null) !== 'ecom-pilot') {
            throw new RuntimeException('This is not an Ecom Pilot backup file.');
        }

        return $m;
    }

    /**
     * Put everything in the zip back. Tables in the backup are emptied and refilled;
     * this server's own backup settings are kept. Returns the restored row counts.
     */
    public static function restore(string $path): array
    {
        @set_time_limit(0);
        $manifest = self::manifest($path);
        $zip = self::open($path);

        $keep = Schema::hasTable('settings')
            ? DB::table('settings')->where('key', 'like', self::KEEP_SETTINGS)->get()->map(fn ($r) => (array) $r)->all()
            : [];

        $current = array_flip(self::tables());
        $restored = [];

        Schema::disableForeignKeyConstraints();
        try {
            foreach (array_keys($manifest['tables'] ?? []) as $table) {
                if (! isset($current[$table])) {
                    continue;   // table no longer exists in this version
                }
                $columns = array_flip(Schema::getColumnListing($table));
                DB::table($table)->delete();

                $stream = $zip->getStream('tables/' . $table . '.jsonl');
                if (! $stream) {
                    continue;
                }
                $batch = [];
                $n = 0;
                while (($line = fgets($stream)) !== false) {
                    $line = trim($line);
                    if ($line === '') {
                        continue;
                    }
                    $batch[] = array_intersect_key(json_decode($line, true) ?? [], $columns);
                    if (count($batch) >= 300) {
                        DB::table($table)->insert($batch);
                        $n += count($batch);
                        $batch = [];
                    }
                }
                if ($batch) {
                    DB::table($table)->insert($batch);
                    $n += count($batch);
                }
                fclose($stream);
                $restored[$table] = $n;
            }

            if ($keep && Schema::hasTable('settings')) {
                DB::table('settings')->where('key', 'like', self::KEEP_SETTINGS)->delete();
                DB::table('settings')->insert($keep);
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        // Uploaded files
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entry = $zip->getNameIndex($i);
            if (! str_starts_with($entry, 'files/') || str_ends_with($entry, '/') || str_contains($entry, '..')) {
                continue;
            }
            $target = storage_path('app/' . substr($entry, strlen('files/')));
            File::ensureDirectoryExists(dirname($target));
            file_put_contents($target, $zip->getFromIndex($i));
        }
        $zip->close();

        // Postgres needs its sequences moved past the restored ids (MySQL/SQLite do it themselves).
        if (DB::getDriverName() === 'pgsql') {
            foreach (array_keys($restored) as $table) {
                if (Schema::hasColumn($table, 'id')) {
                    DB::statement("SELECT setval(pg_get_serial_sequence('{$table}', 'id'), COALESCE((SELECT MAX(id) FROM {$table}), 1))");
                }
            }
        }

        \Illuminate\Support\Facades\Cache::flush();

        return $restored;
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
