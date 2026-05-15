<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;
use RuntimeException;

class InventoryService
{
    public function decrementStock(ProductVariant $variant, int $qty): void
    {
        if ($variant->stock < $qty) {
            throw new RuntimeException("Insufficient stock for variant #{$variant->id}. Available: {$variant->stock}, Requested: {$qty}.");
        }
        $variant->decrement('stock', $qty);
    }

    public function incrementStock(ProductVariant $variant, int $qty): void
    {
        $variant->increment('stock', $qty);
    }

    public function getTotalStock(Product $product): int
    {
        return (int) $product->variants()->sum('stock');
    }

    public function getLowStockVariants(int $threshold = 5): Collection
    {
        return ProductVariant::with('product')
            ->where('stock', '<=', $threshold)
            ->where('is_active', true)
            ->orderBy('stock')
            ->get();
    }
}
