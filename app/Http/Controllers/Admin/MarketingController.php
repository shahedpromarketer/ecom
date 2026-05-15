<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MarketingPixel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class MarketingController extends Controller
{
    private const PLATFORMS = ['facebook', 'google_analytics', 'tiktok', 'google_tag_manager', 'microsoft_clarity'];

    public function index()
    {
        $pixels = collect(self::PLATFORMS)->mapWithKeys(function ($platform) {
            $pixel = MarketingPixel::firstOrCreate(
                ['platform' => $platform],
                ['is_active' => false]
            );
            return [$platform => $pixel];
        });

        return view('admin.marketing.index', compact('pixels'));
    }

    public function update(Request $request, string $platform)
    {
        abort_unless(in_array($platform, self::PLATFORMS), 404);

        $request->validate([
            'pixel_id'   => 'nullable|string|max:255',
            'api_secret' => 'nullable|string|max:500',
            'test_code'  => 'nullable|string|max:255',
        ]);

        MarketingPixel::updateOrCreate(
            ['platform' => $platform],
            [
                'pixel_id'   => $request->filled('pixel_id') ? $request->pixel_id : null,
                'api_secret' => $request->filled('api_secret') ? $request->api_secret : null,
                'test_code'  => $request->filled('test_code') ? $request->test_code : null,
            ]
        );

        Cache::store('file')->forget('active_pixels');

        return response()->json(['success' => true, 'message' => 'Saved successfully.']);
    }

    public function toggle(string $platform)
    {
        abort_unless(in_array($platform, self::PLATFORMS), 404);

        $pixel = MarketingPixel::firstOrCreate(
            ['platform' => $platform],
            ['is_active' => false]
        );
        $pixel->update(['is_active' => !$pixel->is_active]);

        Cache::store('file')->forget('active_pixels');

        return response()->json(['success' => true, 'is_active' => (bool) $pixel->is_active]);
    }
}
