<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourierShipment extends Model
{
    protected $fillable = [
        'order_id', 'courier_name', 'consignment_id', 'tracking_code',
        'tracking_link', 'status', 'delivery_fee', 'notes',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
