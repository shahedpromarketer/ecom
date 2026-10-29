<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Setting;

class ProductController extends Controller
{
    public function show(string $slug)
    {
        $product = Product::where('slug', $slug)
            ->where('status', 'active')
            ->with([
                'images'     => fn($q) => $q->orderByDesc('is_primary')->orderBy('sort_order'),
                'variants'   => fn($q) => $q->where('is_active', true)->orderBy('id'),
                'categories' => fn($q) => $q->with('parent'),
            ])
            ->firstOrFail();

        // Related products — same categories, excluding this product
        $categoryIds = $product->categories->pluck('id')->toArray();
        $related = Product::where('status', 'active')
            ->where('id', '!=', $product->id)
            ->whereHas('categories', fn($q) => $q->whereIn('categories.id', $categoryIds))
            ->with([
                'images'   => fn($q) => $q->where('is_primary', true)->orWhere('sort_order', 1),
                'variants',
            ])
            ->latest()
            ->take(4)
            ->get();

        $settings = [
            'store_name'          => Setting::getValue('store_name', 'Clothonic'),
            'free_shipping_above' => Setting::getValue('free_shipping_above', 999),
            'shipping_charge'     => Setting::getValue('shipping_charge', 60),
            'whatsapp_phone'      => Setting::getValue('whatsapp_phone', ''),
        ];

        return view('pages.product', compact('product', 'related', 'settings'));
    }
}
