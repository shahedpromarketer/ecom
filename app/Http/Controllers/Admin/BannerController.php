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
        $banners = Banner::orderBy('sort_order')->get();

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
        ]);

        return redirect()->route('admin.banners.index')->with('success', 'Banner created successfully.');
    }

    public function edit(Banner $banner)
    {
        return view('admin.banners.edit', compact('banner'));
    }

    public function update(Request $request, Banner $banner)
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
        Storage::disk('public')->delete($banner->image);
        $banner->delete();

        return back()->with('success', 'Banner deleted.');
    }

    public function toggle(Banner $banner)
    {
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

        foreach ($request->order as $sortOrder => $id) {
            Banner::where('id', $id)->update(['sort_order' => $sortOrder]);
        }

        return response()->json(['success' => true]);
    }
}
