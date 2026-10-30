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

    protected static function booted(): void
    {
        // The CRM owns stock: any change is sent to the product's WooCommerce store.
        static::saved(function (self $v) {
            if ($v->wasRecentlyCreated || $v->wasChanged(['stock', 'is_active', 'size', 'color'])) {
                \App\Services\StoreStockPusher::queue($v->product_id);
            }
        });
        static::deleted(fn (self $v) => \App\Services\StoreStockPusher::queue($v->product_id));
    }

    /** The one size-less variant a simple store product gets (its stock mirrors the store). */
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
