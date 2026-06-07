<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'user_id', 'order_number', 'status', 'subtotal', 'discount', 'discount_type',
        'shipping_charge', 'courier_charge', 'total', 'paid_amount',
        'payment_method', 'payment_status',
        'coupon_id', 'shipping_name', 'shipping_phone', 'alt_phone', 'email',
        'shipping_division', 'shipping_district', 'shipping_area', 'shipping_address',
        'notes', 'courier_note', 'packing_note',
        'source', 'source_url', 'source_order_id', 'source_website',
        'utm_source', 'utm_medium', 'utm_campaign',
        'order_tag', 'customer_tag', 'print_status', 'hold_reason',
        'assigned_to', 'cod_changed', 'is_duplicate',
        'reference_no', 'skip_followup', 'user_ip',
    ];

    protected $casts = [
        'status'         => 'string',
        'payment_status' => 'string',
        'subtotal'       => 'decimal:2',
        'discount'       => 'decimal:2',
        'shipping_charge'=> 'decimal:2',
        'courier_charge' => 'decimal:2',
        'total'          => 'decimal:2',
        'paid_amount'    => 'decimal:2',
        'cod_changed'    => 'boolean',
        'is_duplicate'   => 'boolean',
        'skip_followup'  => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            if (empty($order->order_number)) {
                $order->order_number = 'CLT' . date('Ymd') . '-' . str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
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
}
