<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class InvoiceTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\InvoiceTemplate::truncate();

        $templates = [
            ['name' => 'Thermal Minimal',    'size' => 'small',  'style' => 'minimal',      'is_active' => true],
            ['name' => 'Thermal Standard',   'size' => 'small',  'style' => 'standard',     'is_active' => false],
            ['name' => 'A5 Minimal',         'size' => 'medium', 'style' => 'minimal',      'is_active' => false],
            ['name' => 'A5 Standard',        'size' => 'medium', 'style' => 'standard',     'is_active' => false],
            ['name' => 'A4 Standard',        'size' => 'large',  'style' => 'standard',     'is_active' => false],
            ['name' => 'A4 Professional',    'size' => 'large',  'style' => 'professional', 'is_active' => false],
        ];

        foreach ($templates as $t) {
            \App\Models\InvoiceTemplate::create($t);
        }
    }
}
