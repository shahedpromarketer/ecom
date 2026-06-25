<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BannerController extends Controller
{
    public function index()
    {
        $banQ = Banner::orderBy('sort_order');
        if (!auth()->user()->is_super_admin) { $banQ->where('tenant_id', auth()->user()->tenant_id); }
        $banners = $banQ->get();

        return view('admin.banners.index', compact('banners'));
    }

    public function create()
    {
        return view('admin.banners.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'      => 'required|string|max:255',
            'subtitle'   => 'nullable|string|max:255',
            'link'       => 'nullable|url|max:500',
            'position'   => 'required|in:hero,section,popup',
            'sort_order' => 'nullable|integer|min:0',
            'is_active'  => 'boolean',
            'image'      => 'nullable|image|mimes:jpeg,jpg,png,webp|max:4096',
        ]);

        $path = $request->hasFile('image')
            ? $request->file('image')->store('banners', 'public')
            : null;

        Banner::create([
            'title'      => $request->title,
            'subtitle'   => $request->subtitle,
            'link'       => $request->link,
            'position'   => $request->position,
            'sort_order' => $request->sort_order ?? 0,
            'is_active'  => $request->boolean('is_active', true),
            'image'      => $path,
            'tenant_id'  => auth()->user()->tenant_id,
        ]);

        return redirect()->route('admin.banners.index')->with('success', 'Banner created successfully.');
    }

    private function assertOwns(Banner $banner): void
    {
        if (!auth()->user()->is_super_admin && $banner->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }
    }

    public function edit(Banner $banner)
    {
        $this->assertOwns($banner);
        return view('admin.banners.edit', compact('banner'));
    }

    public function update(Request $request, Banner $banner)
    {
        $this->assertOwns($banner);
        $request->validate([
            'title'      => 'required|string|max:255',
            'subtitle'   => 'nullable|string|max:255',
            'link'       => 'nullable|url|max:500',
            'position'   => 'required|in:hero,section,popup',
            'sort_order' => 'nullable|integer|min:0',
            'is_active'  => 'boolean',
            'image'      => 'nullable|image|mimes:jpeg,jpg,png,webp|max:4096',
        ]);

        $data = [
            'title'      => $request->title,
            'subtitle'   => $request->subtitle,
            'link'       => $request->link,
            'position'   => $request->position,
            'sort_order' => $request->sort_order ?? 0,
            'is_active'  => $request->boolean('is_active'),
        ];

        if ($request->hasFile('image')) {
            Storage::disk('public')->delete($banner->image);
            $data['image'] = $request->file('image')->store('banners', 'public');
        }

        $banner->update($data);

        return redirect()->route('admin.banners.index')->with('success', 'Banner updated successfully.');
    }

    public function destroy(Banner $banner)
    {
        $this->assertOwns($banner);
        Storage::disk('public')->delete($banner->image);
        $banner->delete();

        return back()->with('success', 'Banner deleted.');
    }

    public function toggle(Banner $banner)
    {
        $this->assertOwns($banner);
        $banner->update(['is_active' => !$banner->is_active]);

        return response()->json([
            'success'   => true,
            'is_active' => $banner->is_active,
        ]);
    }

    public function reorder(Request $request)
    {
        $request->validate([
            'order'   => 'required|array',
            'order.*' => 'integer|exists:banners,id',
        ]);

        $tenantId    = auth()->user()->tenant_id;
        $isSuperAdmin = auth()->user()->is_super_admin;

        foreach ($request->order as $sortOrder => $id) {
            $q = Banner::where('id', $id);
            if (!$isSuperAdmin) {
                $q->where('tenant_id', $tenantId);
            }
            $q->update(['sort_order' => $sortOrder]);
        }

        return response()->json(['success' => true]);
    }
}
