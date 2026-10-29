<?php

namespace App\Console\Commands;

use App\Models\WordPressSite;
use App\Services\GoogleSheetImporter;
use Illuminate\Console\Command;

/** Reads every Google Sheet store that has auto-import on and brings in new rows. */
class ImportSheetOrders extends Command
{
    protected $signature   = 'sheets:import';
    protected $description = 'Import new orders from connected Google Sheets';

    public function handle(): void
    {
        $sites = WordPressSite::withoutGlobalScopes()
            ->where('type', 'google_sheet')
            ->where('is_active', true)
            ->get()
            ->filter(fn ($s) => ! empty($s->settings['auto_import']) && ! empty($s->settings['mapping']));

        foreach ($sites as $site) {
            $r = \App\Support\Tenancy::as($site->tenant_id, fn () => GoogleSheetImporter::run($site));
            $this->line("{$site->name}: " . ($r['error'] ?? "{$r['created']} new, {$r['updated']} updated, {$r['invalid']} ignored"));
        }
    }
}
