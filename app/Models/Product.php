<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use \App\Models\Concerns\BelongsToTenant, \App\Models\Concerns\GoesToRecycleBin;

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

    protected static function booted(): void
    {
        // Image files are only removed when the product is deleted for good from the Recycle Bin.
        static::forceDeleting(function (Product $product) {
            foreach ($product->images as $image) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($image->image_path);
            }
        });
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
        // Use the images already loaded with the list (no extra query per product).
        if ($this->relationLoaded('images')) {
            return $this->images->firstWhere('is_primary', true) ?? $this->images->first();
        }

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
