<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    public function index(Request $request)
    {
        $categorySlug = $request->get('category');
        $currentCategory = null;
        $subCategories = collect();
        $categoryIds = null;

        if ($categorySlug) {
            $currentCategory = Category::where('slug', $categorySlug)
                ->where('is_active', true)
                ->with('children', 'parent')
                ->first();

            if ($currentCategory) {
                $subCategories = $currentCategory->children;
                // Include products from parent and all sub-categories
                $categoryIds = $currentCategory->children->pluck('id')
                    ->push($currentCategory->id)
                    ->toArray();
            }
        }

        $query = Product::where('status', 'active')
            ->with([
                'images'   => fn($q) => $q->where('is_primary', true)->orWhere('sort_order', 1),
                'variants',
            ]);

        if ($categoryIds) {
            $query->whereHas('categories', fn($q) => $q->whereIn('categories.id', $categoryIds));
        }

        // Sub-category filter
        if ($request->filled('sub')) {
            $sub = Category::where('slug', $request->sub)->where('is_active', true)->first();
            if ($sub) {
                $query->whereHas('categories', fn($q) => $q->where('categories.id', $sub->id));
            }
        }

        // Sale filter
        if ($request->boolean('sale')) {
            $query->whereNotNull('sale_price');
        }

        $products = $query->latest()->paginate(16)->withQueryString();

        $settings = [
            'store_name'          => Setting::getValue('store_name', 'Clothonic'),
            'free_shipping_above' => Setting::getValue('free_shipping_above', 999),
            'shipping_charge'     => Setting::getValue('shipping_charge', 60),
        ];

        return view('pages.shop', compact(
            'products',
            'currentCategory',
            'subCategories',
            'settings',
        ));
    }
}
