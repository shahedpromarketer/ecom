<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A courier payment file (COD settlement) that was matched against our parcels. */
class CourierPayment extends Model
{
    use \App\Models\Concerns\BelongsToTenant;

    protected $fillable = [
        'courier_account_id', 'courier', 'file_name', 'reference', 'matched', 'mismatched', 'unknown',
        'total_collected', 'total_charge', 'rows', 'uploaded_by',
    ];

    protected $casts = ['rows' => 'array'];

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
