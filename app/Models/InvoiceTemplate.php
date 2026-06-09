<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceTemplate extends Model
{
    protected $fillable = ['name', 'size', 'style', 'is_active', 'preview_image'];

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }
}
