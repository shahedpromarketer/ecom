<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    protected $fillable = [
        'user_id', 'label', 'recipient_name', 'phone',
        'division', 'district', 'area', 'address_line', 'is_default',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
