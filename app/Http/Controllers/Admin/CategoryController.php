<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CategoryRequest;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = Category::withCount(['children', 'products'])
            ->with('parent');

        if (!auth()->user()->is_super_admin) {
            $query->where('tenant_id', auth()->user()->tenant_id);
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('parent')) {
            if ($request->parent === 'root') {
                $query->whereNull('parent_id');
            } else {
                $query->where('parent_id', $request->parent);
            }
        }

        $categories = $query->orderBy('sort_order')->orderBy('name')->paginate(20)->withQueryString();
        $parentsQ   = Category::whereNull('parent_id')->orderBy('name');
        if (!auth()->user()->is_super_admin) { $parentsQ->where('tenant_id', auth()->user()->tenant_id); }
        $parents    = $parentsQ->get();

        return view('admin.categories.index', compact('categories', 'parents'));
    }

    public function create()
    {
        $parentsQ = Category::whereNull('parent_id')->orderBy('name');
        if (!auth()->user()->is_super_admin) {
            $parentsQ->where('tenant_id', auth()->user()->tenant_id);
        }
        $parents = $parentsQ->get();

        return view('admin.categories.create', compact('parents'));
    }

    public function store(CategoryRequest $request)
    {
        $data = $request->validated();
        $data['is_active']   = $request->boolean('is_active', true);
        $data['sort_order']  = $data['sort_order'] ?? 0;
        $data['tenant_id']   = auth()->user()->tenant_id;

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('categories', 'public');
        }

        Category::create($data);

        return redirect()->route('admin.categories.index')
            ->with('success', 'Category created successfully.');
    }

    public function edit(Category $category)
    {
        if (!auth()->user()->is_super_admin && $category->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        $parentsQ = Category::whereNull('parent_id')->where('id', '!=', $category->id)->orderBy('name');
        if (!auth()->user()->is_super_admin) {
            $parentsQ->where('tenant_id', auth()->user()->tenant_id);
        }
        $parents = $parentsQ->get();

        return view('admin.categories.edit', compact('category', 'parents'));
    }

    public function update(CategoryRequest $request, Category $category)
    {
        if (!auth()->user()->is_super_admin && $category->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }
        $data = $request->validated();
        $data['is_active']  = $request->boolean('is_active', true);
        $data['sort_order'] = $data['sort_order'] ?? 0;

        if ($request->hasFile('image')) {
            if ($category->image) {
                \Storage::disk('public')->delete($category->image);
            }
            $data['image'] = $request->file('image')->store('categories', 'public');
        }

        $category->update($data);

        return redirect()->route('admin.categories.index')
            ->with('success', 'Category updated successfully.');
    }

    public function destroy(Category $category)
    {
        if (!auth()->user()->is_super_admin && $category->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        if ($category->children()->exists()) {
            return back()->with('error', 'Cannot delete a category that has sub-categories.');
        }

        if ($category->image) {
            \Storage::disk('public')->delete($category->image);
        }

        $category->delete();

        return back()->with('success', 'Category deleted successfully.');
    }

    public function toggleActive(Category $category)
    {
        $category->update(['is_active' => !$category->is_active]);

        return response()->json([
            'is_active' => $category->is_active,
        ]);
    }
}
