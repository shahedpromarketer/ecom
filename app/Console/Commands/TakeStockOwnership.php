<?php

namespace App\Console\Commands;

use App\Services\StockOwnership;
use Illuminate\Console\Command;

/**
 * Shows / applies the one-time correction a shop gets when it starts keeping stock in the CRM
 * (also run automatically when "Manage stock in Ecom Pilot" is switched on).
 *
 *   php artisan stock:take-ownership 1 --dry
 */
class TakeStockOwnership extends Command
{
    protected $signature   = 'stock:take-ownership {tenant : shop id} {--dry : only show what would change}';
    protected $description = 'Put back store orders not shipped yet, for a shop starting to keep stock in the CRM';

    public function handle(): void
    {
        $rows = StockOwnership::takeOver((int) $this->argument('tenant'), (bool) $this->option('dry'));
        $this->table(['Product', 'Size / colour', 'Stock now', 'Add back'], array_map(fn ($r) => [$r['product'], $r['variant'], $r['before'], '+' . $r['add']], $rows));
        $this->line($this->option('dry') ? 'Nothing changed (--dry).' : count($rows) . ' variants corrected.');
    }
}
