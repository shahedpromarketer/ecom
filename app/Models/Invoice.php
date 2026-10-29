<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use \App\Models\Concerns\BelongsToTenant;

    protected $fillable = [
        'order_id', 'invoice_number', 'subtotal', 'discount',
        'shipping', 'total', 'status', 'notes',
        'tenant_id',
    ];

    protected $casts = [
        'subtotal'  => 'decimal:2',
        'discount'  => 'decimal:2',
        'shipping'  => 'decimal:2',
        'total'     => 'decimal:2',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public static function generateNumber(): string
    {
        $prefix  = Setting::getValue('invoice_prefix', 'CLT-INV');
        $start   = (int) Setting::getValue('invoice_start_number', 1001);
        $last    = static::max('id') ?? 0;
        return $prefix . '-' . ($start + $last);
    }

    public function scopeForTenant($query)
    {
        return $query->where('tenant_id', auth()->user()?->tenant_id);
    }
}
