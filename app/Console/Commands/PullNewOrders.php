<?php

namespace App\Console\Commands;

use App\Http\Controllers\Admin\IntegrationController;
use App\Models\WordPressSite;
use Illuminate\Console\Command;

class PullNewOrders extends Command
{
    protected $signature   = 'orders:pull-new';
    protected $description = 'Pull new WooCommerce orders every 5 seconds for 60 seconds';

    public function handle(): void
    {
        $controller = app(IntegrationController::class);
        $iterations = 12; // 12 × 5s = 60s (fits within 1-minute cron window)

        for ($i = 0; $i < $iterations; $i++) {
            $sites = WordPressSite::where('is_active', true)
                ->whereNotNull('consumer_key')
                ->whereNotNull('consumer_secret')
                ->get();

            foreach ($sites as $site) {
                // Duplicate checks and SKU matching must stay inside this site's shop.
                $result = \App\Support\Tenancy::as($site->tenant_id, fn () => $controller->pullNewOrders($site));
                $synced = $result['synced'] ?? 0;

                if ($synced > 0) {
                    $this->info("[{$site->name}] {$synced} new order(s) pulled.");
                }
            }

            // Wait 5 seconds before next check (skip sleep on last iteration)
            if ($i < $iterations - 1) {
                sleep(5);
            }
        }
    }
}
