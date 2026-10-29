<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use \App\Models\Concerns\BelongsToTenant;

    protected $fillable = [
        'code', 'type', 'value', 'min_order_amount', 'max_discount',
        'usage_limit', 'used_count', 'expires_at', 'is_active',
        'tenant_id',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function scopeForTenant($query)
    {
        return $query->where('tenant_id', auth()->user()?->tenant_id);
    }
}
