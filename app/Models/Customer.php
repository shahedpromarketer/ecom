<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use \App\Models\Concerns\BelongsToTenant, \App\Models\Concerns\GoesToRecycleBin;

    protected $fillable = [
        'tenant_id', 'phone', 'name', 'alt_phone', 'email', 'address', 'district',
        'tag', 'is_blocked', 'assigned_to',
    ];

    protected $casts = [
        'is_blocked'     => 'boolean',
        'total_spent'    => 'decimal:2',
        'first_order_at' => 'datetime',
        'last_order_at'  => 'datetime',
    ];

    public const TAGS = [
        'new'   => 'New',
        'loyal' => 'Loyal',
        'vip'   => 'VIP',
        'fraud' => 'Fraud Risk',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function notes()
    {
        return $this->hasMany(CustomerNote::class)->latest();
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /** Delivered share of finished orders (delivered + cancelled + returned), or null with no history. */
    public function getSuccessRateAttribute(): ?int
    {
        $finished = $this->delivered_orders + $this->cancelled_orders + $this->returned_orders;

        return $finished > 0 ? (int) round($this->delivered_orders * 100 / $finished) : null;
    }

    public function getIsRepeatAttribute(): bool
    {
        return $this->total_orders >= 2;
    }
}
