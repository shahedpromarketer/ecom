<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = ['name', 'display_name', 'permissions', 'is_system'];

    protected $casts = [
        'permissions' => 'array',
        'is_system'   => 'boolean',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public static function createDefaults(): void
    {
        $defaults = [
            [
                'name'         => 'admin',
                'display_name' => 'Administrator',
                'permissions'  => ['view_orders','manage_orders','export_orders','view_products','manage_products','view_customers','manage_customers','ban_customers','manage_marketing','view_reports','export_reports','manage_settings'],
                'is_system'    => true,
            ],
            [
                'name'         => 'manager',
                'display_name' => 'Manager',
                'permissions'  => ['view_orders','manage_orders','export_orders','view_products','manage_products','view_customers','manage_customers','view_reports','export_reports'],
                'is_system'    => true,
            ],
            [
                'name'         => 'support',
                'display_name' => 'Support',
                'permissions'  => ['view_orders','view_customers'],
                'is_system'    => true,
            ],
            [
                'name'         => 'customer',
                'display_name' => 'Customer',
                'permissions'  => [],
                'is_system'    => false,
            ],
        ];

        foreach ($defaults as $role) {
            static::updateOrCreate(['name' => $role['name']], $role);
        }
    }
}
