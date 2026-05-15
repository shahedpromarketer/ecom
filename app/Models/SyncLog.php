<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SyncLog extends Model
{
    protected $fillable = [
        'site_id', 'order_id', 'source_order_id', 'status', 'payload', 'message',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function site()
    {
        return $this->belongsTo(WordPressSite::class, 'site_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
