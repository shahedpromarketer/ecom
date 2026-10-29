<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** One step in a parcel's journey, from a courier webhook, a status sync or staff. */
class CourierEvent extends Model
{
    use \App\Models\Concerns\BelongsToTenant;

    protected $fillable = ['courier_shipment_id', 'status', 'courier_status', 'message', 'source', 'payload', 'happened_at'];

    protected $casts = ['payload' => 'array', 'happened_at' => 'datetime'];

    public function shipment()
    {
        return $this->belongsTo(CourierShipment::class, 'courier_shipment_id');
    }
}
