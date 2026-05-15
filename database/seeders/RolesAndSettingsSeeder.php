<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Setting;
use Illuminate\Database\Seeder;

class RolesAndSettingsSeeder extends Seeder
{
    public function run(): void
    {
        Role::createDefaults();

        $settings = [
            ['key' => 'order_duplicate_block_minutes', 'value' => '60',  'group' => 'order_rules'],
            ['key' => 'cod_cancel_block_count',        'value' => '3',   'group' => 'order_rules'],
            ['key' => 'max_qty_per_product',           'value' => '10',  'group' => 'order_rules'],
            ['key' => 'min_order_amount',              'value' => '200', 'group' => 'order_rules'],
            ['key' => 'auto_cancel_hours',             'value' => '24',  'group' => 'order_rules'],
            ['key' => 'order_max_amount',              'value' => '5000','group' => 'order_rules'],
        ];

        foreach ($settings as $s) {
            Setting::updateOrCreate(['key' => $s['key']], $s);
        }
    }
}
