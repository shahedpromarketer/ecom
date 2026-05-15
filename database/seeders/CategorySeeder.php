<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $parents = [
            'T-Shirts' => ['Polo', 'Round Neck', 'V-Neck'],
            'Shirts' => ['Casual', 'Formal', 'Denim'],
            'Pants' => [],
            'Shorts' => [],
            'Jackets' => [],
            'Accessories' => [],
        ];

        foreach ($parents as $parentName => $children) {
            $parent = Category::create([
                'name' => $parentName,
                'slug' => Str::slug($parentName),
            ]);

            foreach ($children as $childName) {
                Category::create([
                    'name' => $childName,
                    'slug' => Str::slug($parentName . '-' . $childName),
                    'parent_id' => $parent->id,
                ]);
            }
        }
    }
}
