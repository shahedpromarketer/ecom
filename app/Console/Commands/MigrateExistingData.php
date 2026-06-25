<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class MigrateExistingData extends Command {
    protected $signature = 'tenants:migrate-existing';
    protected $description = 'Migrate existing data to first tenant';

    public function handle(): void {
        $existingUser = User::where('is_super_admin', false)->whereNull('tenant_id')->first();

        if (!$existingUser) {
            $this->info('No unassigned users found. Already migrated?');
            return;
        }

        $tenant = Tenant::firstOrCreate(
            ['email' => $existingUser->email],
            [
                'name'            => 'Default Tenant',
                'status'          => 'active',
                'plan'            => 'enterprise',
                'plan_expires_at' => now()->addYears(10),
                'max_orders'      => 999999,
                'max_users'       => 999,
            ]
        );

        $tenant->update(['owner_id' => $existingUser->id]);

        User::whereNull('tenant_id')->where('is_super_admin', false)
            ->update(['tenant_id' => $tenant->id]);

        $tables = ['orders','products','categories','order_notes','activity_logs','coupons','banners','order_returns','invoices','courier_shipments','marketing_pixels','courier_settings','wordpress_sites','blacklisted_phones','invoice_templates','sync_logs'];

        foreach ($tables as $table) {
            if (DB::getSchemaBuilder()->hasTable($table) && DB::getSchemaBuilder()->hasColumn($table, 'tenant_id')) {
                $count = DB::table($table)->whereNull('tenant_id')->update(['tenant_id' => $tenant->id]);
                $this->info("Updated {$count} rows in {$table}");
            }
        }

        $this->info('Done. Tenant ID: ' . $tenant->id);
    }
}
