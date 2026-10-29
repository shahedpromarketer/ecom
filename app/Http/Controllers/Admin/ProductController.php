<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with([
            'categories',
            'images' => fn ($q) => $q->orderByDesc('is_primary'),
        ])
            ->withCount('variants')
            ->withSum('variants', 'stock')
            ->withMin('variants', 'stock');

        if (!auth()->user()->is_super_admin) {
            $query->where('tenant_id', auth()->user()->tenant_id);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn ($q) => $q->where('name', 'like', "%{$s}%")->orWhere('sku', 'like', "%{$s}%"));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('category')) {
            $query->whereHas('categories', fn ($q) => $q->where('categories.id', $request->category));
        }

        $products   = $query->latest()->paginate(15)->withQueryString();
        $catQuery   = Category::whereNull('parent_id')->with('children')->orderBy('name');
        if (!auth()->user()->is_super_admin) { $catQuery->where('tenant_id', auth()->user()->tenant_id); }
        $categories = $catQuery->get();

        return view('admin.products.index', compact('products', 'categories'));
    }

    public function create()
    {
        $catQ = Category::whereNull('parent_id')->with('children')->orderBy('name');
        if (!auth()->user()->is_super_admin) { $catQ->where('tenant_id', auth()->user()->tenant_id); }
        $categories = $catQ->get();

        return view('admin.products.create', compact('categories'));
    }

    public function store(ProductRequest $request)
    {
        $data = $request->validated();
        $data['is_featured'] = $request->boolean('is_featured');

        DB::transaction(function () use ($data, $request) {
            $product = Product::create([
                'name'              => $data['name'],
                'slug'              => $data['slug'],
                'description'       => $data['description'] ?? null,
                'short_description' => $data['short_description'] ?? null,
                'price'             => $data['price'],
                'sale_price'        => $data['sale_price'] ?? null,
                'sku'               => $data['sku'] ?? null,
                'status'            => $data['status'],
                'is_featured'       => $data['is_featured'],
                'meta_title'        => $data['meta_title'] ?? null,
                'meta_description'  => $data['meta_description'] ?? null,
                'tenant_id'         => auth()->user()->tenant_id,
            ]);

            $product->categories()->sync($this->resolveCategories($data['categories'] ?? []));

            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $idx => $file) {
                    $path = $file->store('products', 'public');
                    $product->images()->create([
                        'image_path' => $path,
                        'is_primary' => $idx === 0,
                        'sort_order' => $idx,
                    ]);
                }
            }

            if (!empty($data['variants'])) {
                foreach ($data['variants'] as $v) {
                    if (!empty($v['_delete'])) continue;
                    $product->variants()->create([
                        'size'        => $v['size'] ?? null,
                        'color'       => $v['color'] ?? null,
                        'color_hex'   => $v['color_hex'] ?? null,
                        'stock'       => (int) ($v['stock'] ?? 0),
                        'sku'         => $v['sku'] ?? null,
                        'extra_price' => $v['extra_price'] ?: 0,
                        'is_active'   => true,
                    ]);
                }
            }
        });

        return redirect()->route('admin.products.index')->with('success', 'Product created successfully.');
    }

    public function edit(Product $product)
    {
        $product->load([
            'categories',
            'images' => fn ($q) => $q->orderByDesc('is_primary')->orderBy('sort_order'),
            'variants',
        ]);

        $catQ = Category::whereNull('parent_id')->with('children')->orderBy('name');
        if (!auth()->user()->is_super_admin) { $catQ->where('tenant_id', auth()->user()->tenant_id); }
        $categories = $catQ->get();

        $variantsData = collect(old('variants'))
            ? collect(old('variants'))->values()->map(fn ($v, $i) => [
                'key'         => $i,
                'id'          => isset($v['id']) && $v['id'] ? (int) $v['id'] : null,
                'size'        => $v['size'] ?? '',
                'color'       => $v['color'] ?? '',
                'color_hex'   => $v['color_hex'] ?? '#000000',
                'stock'       => (int) ($v['stock'] ?? 0),
                'sku'         => $v['sku'] ?? '',
                'extra_price' => $v['extra_price'] ?? '',
                '_delete'     => ($v['_delete'] ?? '0') === '1',
            ])->all()
            : $product->variants->map(fn ($v, $i) => [
                'key'         => $i,
                'id'          => $v->id,
                'size'        => $v->size ?? '',
                'color'       => $v->color ?? '',
                'color_hex'   => $v->color_hex ?? '#000000',
                'stock'       => $v->stock,
                'sku'         => $v->sku ?? '',
                'extra_price' => $v->extra_price ?? '',
                '_delete'     => false,
            ])->values()->all();

        return view('admin.products.edit', compact('product', 'categories', 'variantsData'));
    }

    public function update(ProductRequest $request, Product $product)
    {
        $data = $request->validated();
        $data['is_featured'] = $request->boolean('is_featured');

        DB::transaction(function () use ($data, $request, $product) {
            $product->update([
                'name'              => $data['name'],
                'slug'              => $data['slug'],
                'description'       => $data['description'] ?? null,
                'short_description' => $data['short_description'] ?? null,
                'price'             => $data['price'],
                'sale_price'        => $data['sale_price'] ?? null,
                'sku'               => $data['sku'] ?? null,
                'status'            => $data['status'],
                'is_featured'       => $data['is_featured'],
                'meta_title'        => $data['meta_title'] ?? null,
                'meta_description'  => $data['meta_description'] ?? null,
            ]);

            $product->categories()->sync($this->resolveCategories($data['categories'] ?? []));

            if ($request->hasFile('images')) {
                $existingCount = $product->images()->count();
                foreach ($request->file('images') as $idx => $file) {
                    $path = $file->store('products', 'public');
                    $product->images()->create([
                        'image_path' => $path,
                        'is_primary' => $existingCount === 0 && $idx === 0,
                        'sort_order' => $existingCount + $idx,
                    ]);
                }
            }

            foreach ($data['variants'] ?? [] as $v) {
                if (!empty($v['_delete']) && $v['_delete'] === '1') {
                    if (!empty($v['id'])) {
                        ProductVariant::find($v['id'])?->delete();
                    }
                    continue;
                }

                if (!empty($v['id'])) {
                    ProductVariant::find($v['id'])?->update([
                        'size'        => $v['size'] ?? null,
                        'color'       => $v['color'] ?? null,
                        'color_hex'   => $v['color_hex'] ?? null,
                        'stock'       => (int) ($v['stock'] ?? 0),
                        'sku'         => $v['sku'] ?? null,
                        'extra_price' => $v['extra_price'] ?: 0,
                    ]);
                } else {
                    $product->variants()->create([
                        'size'        => $v['size'] ?? null,
                        'color'       => $v['color'] ?? null,
                        'color_hex'   => $v['color_hex'] ?? null,
                        'stock'       => (int) ($v['stock'] ?? 0),
                        'sku'         => $v['sku'] ?? null,
                        'extra_price' => $v['extra_price'] ?: 0,
                        'is_active'   => true,
                    ]);
                }
            }
        });

        return redirect()->route('admin.products.index')->with('success', 'Product updated successfully.');
    }

    public function destroy(Product $product)
    {
        $product->delete();   // to the Recycle Bin; images stay until deleted for good

        return back()->with('success', 'Product moved to the Recycle Bin.');
    }

    public function setPrimaryImage(Request $request, Product $product)
    {
        $request->validate(['image_id' => 'required|integer']);
        $product->images()->update(['is_primary' => false]);
        $product->images()->where('id', $request->image_id)->update(['is_primary' => true]);

        return response()->json(['success' => true]);
    }

    public function deleteImage(ProductImage $image)
    {
        Storage::disk('public')->delete($image->image_path);
        $wasPrimary = $image->is_primary;
        $productId  = $image->product_id;
        $image->delete();

        if ($wasPrimary) {
            ProductImage::where('product_id', $productId)->oldest()->first()?->update(['is_primary' => true]);
        }

        return response()->json(['success' => true]);
    }

    public function updateStock(Request $request, ProductVariant $variant)
    {
        $request->validate(['stock' => 'required|integer|min:0']);
        $variant->update(['stock' => $request->stock]);

        return response()->json(['success' => true, 'stock' => $variant->stock]);
    }

    public function bulkAction(Request $request)
    {
        $ids    = array_filter((array) $request->input('ids', []));
        $action = $request->input('action');

        if (empty($ids)) {
            return back()->with('error', 'No products selected.');
        }

        if ($action === 'delete') {
            $delQ = Product::whereIn('id', $ids)->with('images');
            if (!auth()->user()->is_super_admin) { $delQ->where('tenant_id', auth()->user()->tenant_id); }
            $products = $delQ->get();
            foreach ($products as $p) {
                $p->delete();   // to the Recycle Bin
            }
            return back()->with('success', count($ids) . ' product(s) moved to the Recycle Bin.');
        }

        if (in_array($action, ['active', 'inactive', 'draft'], true)) {
            $statusQ = Product::whereIn('id', $ids);
            if (!auth()->user()->is_super_admin) { $statusQ->where('tenant_id', auth()->user()->tenant_id); }
            $statusQ->update(['status' => $action]);
            return back()->with('success', count($ids) . ' product(s) set to ' . $action . '.');
        }

        return back()->with('error', 'Invalid action selected.');
    }

    public function clone(Product $product)
    {
        $product->load(['categories', 'variants', 'images']);

        $new = $product->replicate();
        $new->name   = $product->name . ' (Copy)';
        $new->status = 'draft';

        $baseSlug  = $product->slug . '-copy';
        $slug      = $baseSlug;
        $i         = 1;
        while (Product::where('slug', $slug)->where('tenant_id', $product->tenant_id)->exists()) {
            $slug = $baseSlug . '-' . $i++;
        }
        $new->slug = $slug;
        $new->is_featured = false;
        $new->save();

        $new->categories()->sync($product->categories->pluck('id'));

        foreach ($product->variants as $variant) {
            $cloned              = $variant->replicate();
            $cloned->product_id  = $new->id;
            $cloned->stock       = 0;
            $cloned->save();
        }

        foreach ($product->images as $image) {
            $oldPath     = $image->image_path;
            $newFilename = 'clone_' . time() . '_' . basename($oldPath);
            $newPath     = 'products/' . $newFilename;
            if (Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->copy($oldPath, $newPath);
            }
            ProductImage::create([
                'product_id'  => $new->id,
                'image_path'  => $newPath,
                'is_primary'  => $image->is_primary,
                'sort_order'  => $image->sort_order ?? 0,
            ]);
        }

        \App\Models\ActivityLog::log(
            'product.cloned',
            $new,
            "Cloned from: {$product->name}",
            ['original_id' => $product->id]
        );

        return redirect()
            ->route('admin.products.edit', $new)
            ->with('success', 'Product cloned. Review and publish when ready.');
    }

    private function resolveCategories(array $categories): array
    {
        if (!empty($categories)) {
            return $categories;
        }

        $tenantId = auth()->user()->tenant_id;

        $unknown = Category::firstOrCreate(
            ['name' => 'Unknown', 'tenant_id' => $tenantId],
            [
                'slug'       => 'unknown-' . $tenantId,
                'is_active'  => true,
                'sort_order' => 0,
            ]
        );

        return [$unknown->id];
    }
}
