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
            ['name' => '2 Inch Minimal',     'size' => '2inch',  'style' => 'minimal',      'is_active' => false],
            ['name' => '2 Inch Standard',    'size' => '2inch',  'style' => 'standard',     'is_active' => false],
            ['name' => '3 Inch Minimal',     'size' => '3inch',  'style' => 'minimal',      'is_active' => false],
            ['name' => '3 Inch Standard',    'size' => '3inch',  'style' => 'standard',     'is_active' => false],
            ['name' => '3x2 Minimal',        'size' => '3x2',    'style' => 'minimal',      'is_active' => false],
            ['name' => '3x2 Standard',       'size' => '3x2',    'style' => 'standard',     'is_active' => false],
            ['name' => '3x3 Minimal',        'size' => '3x3',    'style' => 'minimal',      'is_active' => false],
            ['name' => '3x3 Standard',       'size' => '3x3',    'style' => 'standard',     'is_active' => false],
            ['name' => '3x4 Minimal',        'size' => '3x4',    'style' => 'minimal',      'is_active' => false],
            ['name' => '3x4 Standard',       'size' => '3x4',    'style' => 'standard',     'is_active' => false],
            ['name' => '4x4 Minimal',        'size' => '4x4',    'style' => 'minimal',      'is_active' => false],
            ['name' => '4x4 Standard',       'size' => '4x4',    'style' => 'standard',     'is_active' => false],
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
