<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        $banners = Banner::where('is_active', true)
            ->where('position', 'hero')
            ->orderBy('sort_order')
            ->get();

        $featuredCategories = Category::where('is_active', true)
            ->whereNull('parent_id')
            ->withCount(['products' => fn($q) => $q->where('status', 'active')])
            ->orderBy('sort_order')
            ->take(6)
            ->get();

        $newArrivals = Product::where('status', 'active')
            ->with([
                'images'   => fn($q) => $q->where('is_primary', true)->orWhere('sort_order', 1),
                'variants',
            ])
            ->latest()
            ->take(8)
            ->get();

        $featuredProducts = Product::where('status', 'active')
            ->where('is_featured', true)
            ->with([
                'images'   => fn($q) => $q->where('is_primary', true)->orWhere('sort_order', 1),
                'variants',
            ])
            ->latest()
            ->take(8)
            ->get();

        $saleProducts = Product::where('status', 'active')
            ->whereNotNull('sale_price')
            ->with([
                'images'   => fn($q) => $q->where('is_primary', true)->orWhere('sort_order', 1),
                'variants',
            ])
            ->latest()
            ->take(8)
            ->get();

        $topCategories = Category::where('is_active', true)
            ->whereNull('parent_id')
            ->withCount(['products' => fn($q) => $q->where('status', 'active')])
            ->orderByDesc('products_count')
            ->take(3)
            ->get();

        // Eagerly load products for top categories
        foreach ($topCategories as $cat) {
            $cat->setRelation('topProducts', $cat->products()
                ->where('status', 'active')
                ->with(['images' => fn($q) => $q->where('is_primary', true)->orWhere('sort_order', 1), 'variants'])
                ->latest()
                ->take(4)
                ->get());
        }

        $settings = [
            'store_name'          => Setting::getValue('store_name', 'Clothonic'),
            'free_shipping_above' => Setting::getValue('free_shipping_above', 999),
            'shipping_charge'     => Setting::getValue('shipping_charge', 60),
        ];

        return view('pages.home', compact(
            'banners',
            'featuredCategories',
            'newArrivals',
            'featuredProducts',
            'saleProducts',
            'topCategories',
            'settings'
        ));
    }

    public function search(Request $request)
    {
        $q = $request->get('q', '');

        if (strlen($q) < 2) {
            return response()->json([]);
        }

        $products = Product::where('status', 'active')
            ->where(function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                      ->orWhere('sku', 'like', "%{$q}%");
            })
            ->with(['images' => fn($query) => $query->where('is_primary', true)->orWhere('sort_order', 1)])
            ->take(8)
            ->get()
            ->map(function ($product) {
                $img = $product->images->firstWhere('is_primary', true) ?? $product->images->first();
                return [
                    'id'    => $product->id,
                    'name'  => $product->name,
                    'slug'  => $product->slug,
                    'price' => number_format($product->sale_price ?? $product->price, 0),
                    'image' => $img ? asset('storage/' . $img->image_path) : null,
                    'url'   => route('product.show', $product->slug),
                ];
            });

        return response()->json($products);
    }
}
