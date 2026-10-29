<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use \App\Models\Concerns\BelongsToTenant;

    protected $fillable = [
        'user_id', 'customer_id', 'order_number', 'status', 'subtotal', 'discount', 'discount_type',
        'shipping_charge', 'courier_charge', 'total', 'paid_amount',
        'payment_method', 'payment_status',
        'coupon_id', 'shipping_name', 'shipping_phone', 'alt_phone', 'email',
        'shipping_division', 'shipping_district', 'shipping_area', 'shipping_address',
        'notes', 'courier_note', 'packing_note',
        'source', 'source_url', 'source_order_id', 'source_website', 'platform',
        'utm_source', 'utm_medium', 'utm_campaign',
        'order_tag', 'customer_tag', 'print_status', 'hold_reason',
        'assigned_to', 'assigned_at', 'cod_changed', 'is_duplicate',
        'reference_no', 'skip_followup', 'user_ip',
        'tenant_id', 'stock_deducted',
    ];

    protected $casts = [
        'status'          => 'string',
        'payment_status'  => 'string',
        'subtotal'        => 'decimal:2',
        'discount'        => 'decimal:2',
        'shipping_charge' => 'decimal:2',
        'courier_charge'  => 'decimal:2',
        'total'           => 'decimal:2',
        'paid_amount'     => 'decimal:2',
        'cod_changed'     => 'boolean',
        'is_duplicate'    => 'boolean',
        'skip_followup'   => 'boolean',
        'stock_deducted'  => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        // Runs after BelongsToTenant has stamped tenant_id.
        static::creating(function ($order) {
            if (empty($order->order_number)) {
                $order->order_number = static::nextOrderNumber($order->tenant_id);
            }
        });

        // Staff without view_all_orders only ever see the orders assigned to them —
        // in lists, search, exports, dashboards and any /orders/{id} link.
        static::addGlobalScope('assignee', function (\Illuminate\Database\Eloquent\Builder $query) {
            $user = auth()->user();
            if ($user && \App\Support\Tenancy::active() && ! $user->hasFullAccess() && ! $user->hasPermission('view_all_orders')) {
                $query->where($query->getModel()->qualifyColumn('assigned_to'), $user->id);
            }
        });
    }

    /**
     * EPL20261029-48213 style, unique inside the shop. The old 4-digit random
     * number collided easily, and a collision made the insert fail (lost order).
     */
    public static function nextOrderNumber(?int $tenantId): string
    {
        $prefix = 'EPL' . now()->format('Ymd') . '-';

        for ($i = 0; $i < 10; $i++) {
            $number = $prefix . random_int(10000, 99999);

            $taken = static::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('order_number', $number)
                ->exists();

            if (! $taken) {
                return $number;
            }
        }

        return $prefix . now()->format('His') . random_int(100, 999);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function shipment()
    {
        return $this->hasOne(CourierShipment::class);
    }

    public function assignedEmployee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function orderNotes()
    {
        return $this->hasMany(OrderNote::class)->latest();
    }

    public function latestOrderNote()
    {
        return $this->hasOne(OrderNote::class)->latestOfMany();
    }

    public function orderReturn()
    {
        return $this->hasOne(OrderReturn::class, 'order_id');
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class, 'subject_id')
            ->where('subject_type', static::class)
            ->latest();
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeBySource($query, string $source)
    {
        return $query->where('source', $source);
    }

    public function scopeForTenant($query)
    {
        return $query->where('tenant_id', auth()->user()?->tenant_id);
    }

}
