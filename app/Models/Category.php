<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use \App\Models\Concerns\BelongsToTenant, \App\Models\Concerns\GoesToRecycleBin;

    protected $fillable = [
        'name', 'slug', 'parent_id', 'image', 'description', 'is_active', 'sort_order',
        'tenant_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function products()
    {
        return $this->belongsToMany(Product::class, 'category_product');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    protected static function booted(): void
    {
        // The image file goes only when the category is deleted for good from the Recycle Bin.
        static::forceDeleting(function (Category $category) {
            if ($category->image) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($category->image);
            }
        });
    }

    public function scopeForTenant($query)
    {
        return $query->where('tenant_id', auth()->user()?->tenant_id);
    }
}
