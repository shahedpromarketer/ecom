<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use \App\Models\Concerns\BelongsToTenant;

    protected $fillable = [
        'wp_product_id', 'name', 'slug', 'description', 'short_description',
        'price', 'regular_price', 'sale_price',
        'sku', 'status', 'is_featured', 'meta_title', 'meta_description',
        'tenant_id',
    ];

    protected $casts = [
        'is_featured'   => 'boolean',
        'price'         => 'decimal:2',
        'regular_price' => 'decimal:2',
        'sale_price'    => 'decimal:2',
    ];

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'category_product');
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function getCurrentPriceAttribute()
    {
        return $this->sale_price ?? $this->price;
    }

    public function getPrimaryImageAttribute()
    {
        return $this->images()->where('is_primary', true)->first()
            ?? $this->images()->first();
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeForTenant($query)
    {
        return $query->where('tenant_id', auth()->user()?->tenant_id);
    }
}
