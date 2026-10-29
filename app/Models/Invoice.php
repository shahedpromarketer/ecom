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

    /**
     * Next invoice number for the current shop: PREFIX-1001, PREFIX-1002, …
     * Each shop counts on its own (the old version used the global max id,
     * so numbers jumped whenever another shop created an invoice).
     */
    public static function generateNumber(): string
    {
        $tenantId = \App\Support\Tenancy::active() ? \App\Support\Tenancy::id() : null;

        $prefix = Setting::getValue('invoice_prefix', 'INV', $tenantId);
        $start  = (int) Setting::getValue('invoice_start_number', 1001, $tenantId);
        $next   = $start + static::count();   // scoped to the current shop

        while (static::where('invoice_number', $prefix . '-' . $next)->exists()) {
            $next++;
        }

        return $prefix . '-' . $next;
    }

    public function scopeForTenant($query)
    {
        return $query->where('tenant_id', auth()->user()?->tenant_id);
    }
}
