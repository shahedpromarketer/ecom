<?php

namespace App\Console\Commands;

use App\Models\CourierSetting;
use App\Models\CourierShipment;
use App\Services\Couriers\CourierFactory;
use App\Services\Couriers\CourierTracker;
use App\Support\Tenancy;
use Illuminate\Console\Command;

/**
 * Asks couriers about parcels that are still on the way and whose check is due,
 * so statuses stay right even when a webhook was missed or never set up.
 */
class SyncCourierStatuses extends Command
{
    protected $signature   = 'couriers:sync {--limit=200}';
    protected $description = 'Refresh the status of parcels still on the way';

    public function handle(): void
    {
        $due = CourierShipment::withoutGlobalScopes()
            ->whereNotNull('courier_account_id')
            ->whereNotNull('consignment_id')
            ->whereNotIn('status', CourierShipment::FINAL)
            ->where('next_sync_at', '<=', now())
            ->orderBy('next_sync_at')
            ->limit((int) $this->option('limit'))
            ->get();

        $accounts = CourierSetting::withoutGlobalScopes()->whereIn('id', $due->pluck('courier_account_id')->unique())->get()->keyBy('id');
        $changed = 0;

        foreach ($due as $s) {
            $account = $accounts[$s->courier_account_id] ?? null;
            if (! $account || ! $account->is_active) {
                $s->forceFill(['next_sync_at' => now()->addDay()])->saveQuietly();
                continue;
            }

            Tenancy::as($s->tenant_id, function () use ($s, $account, &$changed) {
                try {
                    $u = CourierFactory::for($account)->track($s);
                    if ($u && str_starts_with(CourierTracker::apply($s, $u, 'sync'), 'moved')) {
                        $changed++;
                    } elseif (! $u) {
                        $s->forceFill(['next_sync_at' => now()->addHours(3)])->saveQuietly();
                    }
                } catch (\Throwable $e) {
                    $s->forceFill(['next_sync_at' => now()->addHours(6)])->saveQuietly();
                    $this->warn("#{$s->id} {$s->courier_name}: " . mb_substr($e->getMessage(), 0, 150));
                }
            });
        }

        $this->info("Checked {$due->count()} parcels, {$changed} moved.");
    }
}
