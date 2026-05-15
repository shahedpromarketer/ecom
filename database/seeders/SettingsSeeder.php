<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            ['key' => 'store_name',            'value' => 'Clothonic',                                       'group' => 'general'],
            ['key' => 'store_tagline',          'value' => "Premium Men's Fashion",                           'group' => 'general'],
            ['key' => 'store_phone',            'value' => '',                                                'group' => 'general'],
            ['key' => 'store_email',            'value' => '',                                                'group' => 'general'],
            ['key' => 'store_address',          'value' => 'Dhaka, Bangladesh',                               'group' => 'general'],
            ['key' => 'currency_symbol',        'value' => '৳',                                              'group' => 'general'],
            ['key' => 'timezone',               'value' => 'Asia/Dhaka',                                      'group' => 'general'],
            ['key' => 'date_format',            'value' => 'd M Y',                                           'group' => 'general'],

            ['key' => 'order_max_amount',       'value' => '5000',                                            'group' => 'order_rules'],
            ['key' => 'order_limit_hours',      'value' => '24',                                              'group' => 'order_rules'],
            ['key' => 'order_limit_count',      'value' => '2',                                               'group' => 'order_rules'],
            ['key' => 'min_order_amount',       'value' => '0',                                               'group' => 'order_rules'],
            ['key' => 'free_shipping_above',    'value' => '999',                                             'group' => 'order_rules'],
            ['key' => 'default_shipping_charge','value' => '60',                                              'group' => 'order_rules'],

            ['key' => 'invoice_prefix',         'value' => 'CLT-INV',                                         'group' => 'invoice'],
            ['key' => 'invoice_start_number',   'value' => '1001',                                            'group' => 'invoice'],
            ['key' => 'invoice_footer_note',    'value' => 'Thank you for shopping with Clothonic!',          'group' => 'invoice'],
            ['key' => 'invoice_show_logo',      'value' => '1',                                               'group' => 'invoice'],
            ['key' => 'invoice_show_address',   'value' => '1',                                               'group' => 'invoice'],
            ['key' => 'invoice_accent_color',   'value' => '#1a3c5e',                                         'group' => 'invoice'],
            ['key' => 'invoice_paper_size',     'value' => 'A4',                                              'group' => 'invoice'],
        ];

        foreach ($defaults as $setting) {
            Setting::firstOrCreate(
                ['key' => $setting['key']],
                ['value' => $setting['value'], 'group' => $setting['group']]
            );
        }

        $this->command->info('Default settings seeded.');
    }
}
