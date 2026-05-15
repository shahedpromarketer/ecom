<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'user_id', 'order_number', 'status', 'subtotal', 'discount',
        'shipping_charge', 'total', 'payment_method', 'payment_status',
        'coupon_id', 'shipping_name', 'shipping_phone', 'shipping_division',
        'shipping_district', 'shipping_area', 'shipping_address', 'notes',
        'source', 'source_url', 'source_order_id',
        'utm_source', 'utm_medium', 'utm_campaign',
        'order_tag', 'print_status', 'hold_reason',
        'assigned_to', 'cod_changed', 'is_duplicate',
    ];

    protected $casts = [
        'status'         => 'string',
        'payment_status' => 'string',
        'subtotal'       => 'decimal:2',
        'discount'       => 'decimal:2',
        'shipping_charge'=> 'decimal:2',
        'total'          => 'decimal:2',
        'cod_changed'    => 'boolean',
        'is_duplicate'   => 'boolean',
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

    public function orderNotes()
    {
        return $this->hasMany(OrderNote::class)->latest();
    }

    public function orderReturn()
    {
        return $this->hasOne(OrderReturn::class, 'order_id');
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
