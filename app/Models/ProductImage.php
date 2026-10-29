<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductImage extends Model
{
    use \App\Models\Concerns\BelongsToTenantThroughParent;

    public function tenantParentRelation(): string
    {
        return 'product';
    }

    protected $fillable = [
        'product_id', 'image_path', 'is_primary', 'sort_order',
    ];

    // Returns the correct URL whether image_path is an external URL or a local storage path
    public function getUrlAttribute(): string
    {
        if (str_starts_with($this->image_path, 'http://') || str_starts_with($this->image_path, 'https://')) {
            return $this->image_path;
        }
        return asset('storage/' . $this->image_path);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
