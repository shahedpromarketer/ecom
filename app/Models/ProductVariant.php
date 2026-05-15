<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id', 'size', 'color', 'color_hex', 'stock', 'sku', 'extra_price', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'extra_price' => 'decimal:2',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
