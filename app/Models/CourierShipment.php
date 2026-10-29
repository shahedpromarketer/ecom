<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourierShipment extends Model
{
    use \App\Models\Concerns\BelongsToTenant;

    /** Our own courier statuses, in delivery order (the courier's wording is kept in courier_status). */
    public const STATUSES = [
        'submitted'        => 'Booked',
        'assigned'         => 'Assigned (manual)',
        'picked'           => 'Picked up',
        'in_transit'       => 'In transit',
        'out_for_delivery' => 'Out for delivery',
        'hold'             => 'On hold',
        'delivered'        => 'Delivered',
        'partial'          => 'Partly delivered',
        'returning'        => 'Returning',
        'returned'         => 'Returned to us',
        'cancelled'        => 'Cancelled',
        'unknown'          => 'Unknown',
    ];

    /** Nothing more will happen to these parcels at the courier. */
    public const FINAL = ['delivered', 'partial', 'returned', 'cancelled'];

    protected $fillable = [
        'order_id', 'courier_account_id', 'courier_name', 'consignment_id', 'tracking_code',
        'tracking_link', 'status', 'courier_status', 'courier_message', 'status_updated_at', 'next_sync_at',
        'delivery_fee', 'cod_amount', 'collected_amount', 'courier_charge', 'notes',
        'handed_over_at', 'handed_over_by', 'return_received_at', 'return_received_by', 'paid_at', 'payment_ref',
    ];

    protected $casts = [
        'status_updated_at'  => 'datetime',
        'next_sync_at'       => 'datetime',
        'handed_over_at'     => 'datetime',
        'return_received_at' => 'datetime',
        'paid_at'            => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function account()
    {
        return $this->belongsTo(CourierSetting::class, 'courier_account_id');
    }

    public function events()
    {
        return $this->hasMany(CourierEvent::class)->latest('happened_at');
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst(str_replace('_', ' ', (string) $this->status));
    }

    public function isFinal(): bool
    {
        return in_array($this->status, self::FINAL, true);
    }
}
