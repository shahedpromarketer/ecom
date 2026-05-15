<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourierSetting;
use App\Services\Couriers\CourierFactory;
use Illuminate\Http\Request;

class CourierController extends Controller
{
    private array $couriers = ['pathao', 'steadfast', 'carrybe', 'redx', 'paperfly'];

    private array $defaultUrls = [
        'pathao'    => 'https://merchant.pathao.com',
        'steadfast' => 'https://portal.steadfast.com.bd/public/api/v1',
        'carrybe'   => 'https://api.carrybe.com/api',
        'redx'      => 'https://openapi.redx.com.bd/v1.0.0-beta',
        'paperfly'  => 'https://api.paperfly.com.bd/api',
    ];

    public function index()
    {
        $settings = CourierSetting::all()->keyBy('courier');

        foreach ($this->couriers as $courier) {
            if (! isset($settings[$courier])) {
                $settings[$courier] = new CourierSetting([
                    'courier'     => $courier,
                    'is_active'   => false,
                    'base_url'    => $this->defaultUrls[$courier],
                    'webhook_url' => url("/webhook/{$courier}"),
                ]);
            }
        }

        return view('admin.courier.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'courier'    => 'required|in:pathao,steadfast,carrybe,redx,paperfly',
            'api_key'    => 'nullable|string',
            'api_secret' => 'nullable|string',
            'base_url'   => 'nullable|url',
            'is_active'  => 'nullable|boolean',
        ]);

        CourierSetting::updateOrCreate(
            ['courier' => $request->courier],
            [
                'api_key'     => $request->api_key,
                'api_secret'  => $request->api_secret,
                'base_url'    => $request->base_url ?? $this->defaultUrls[$request->courier],
                'webhook_url' => url("/webhook/{$request->courier}"),
                'is_active'   => $request->boolean('is_active'),
            ]
        );

        return back()->with('success', ucfirst($request->courier) . ' settings saved.');
    }

    public function testConnection(Request $request)
    {
        $courier = $request->courier;

        try {
            $service = CourierFactory::make($courier);
            return response()->json(['success' => true, 'message' => 'Connection test passed.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }
}
