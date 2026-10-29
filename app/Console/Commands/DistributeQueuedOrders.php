<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\OrderDistributor;
use Illuminate\Console\Command;

/** Hands out orders that were left unassigned because every staff member was at their limit. */
class DistributeQueuedOrders extends Command
{
    protected $signature   = 'orders:distribute';
    protected $description = 'Assign queued (unassigned) orders to staff who have room';

    public function handle(): void
    {
        foreach (Tenant::where('status', 'active')->pluck('id') as $tenantId) {
            $count = \App\Support\Tenancy::as($tenantId, fn () => OrderDistributor::drainQueue($tenantId));
            if ($count) {
                $this->info("Shop {$tenantId}: assigned {$count} queued order(s).");
            }
        }
    }
}
