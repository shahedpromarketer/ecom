<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    use \App\Models\Concerns\BelongsToTenantThroughParent;

    public function tenantParentRelation(): string
    {
        return 'product';
    }

    protected $fillable = [
        'product_id', 'size', 'color', 'color_hex', 'stock', 'sku', 'extra_price', 'is_active', 'wp_variation_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'extra_price' => 'decimal:2',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /** The one size-less variant a simple store product gets. */
    public function isDefaultSlot(): bool
    {
        return ! $this->wp_variation_id && blank($this->size) && blank($this->color);
    }

    /**
     * A size / colour added in the CRM to a product the store sells as simple
     * (e.g. CartFlows landing pages). Its stock lives in the CRM only.
     */
    public function isCrmOnly(): bool
    {
        return ! $this->wp_variation_id && ! $this->isDefaultSlot();
    }

    public function scopeDefaultSlot($q)
    {
        return $q->whereNull('wp_variation_id')
            ->where(fn ($w) => $w->whereNull('size')->orWhere('size', ''))
            ->where(fn ($w) => $w->whereNull('color')->orWhere('color', ''));
    }
}
