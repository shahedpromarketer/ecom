<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourierSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class CourierController extends Controller
{
    private array $couriers = ['pathao', 'steadfast', 'carrybe', 'redx', 'paperfly'];

    public function index()
    {
        $settings = CourierSetting::all()->keyBy('courier');

        foreach ($this->couriers as $courier) {
            if (! isset($settings[$courier])) {
                $settings[$courier] = new CourierSetting([
                    'courier'   => $courier,
                    'is_active' => false,
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
            'is_active'  => 'nullable|boolean',
        ]);

        $data = [
            'api_key'    => $request->api_key,
            'api_secret' => $request->api_secret,
            'webhook_url'=> url("/webhook/{$request->courier}"),
            'is_active'  => $request->boolean('is_active'),
        ];

        if ($request->courier === 'pathao') {
            $existing = CourierSetting::forCourier('pathao');
            $oldExtra = $existing?->extra_config ?? [];
            $extra    = $request->input('extra_config', []);
            $data['extra_config'] = [
                'username' => $extra['username'] ?? $oldExtra['username'] ?? null,
                'password' => filled($extra['password'] ?? null) ? $extra['password'] : ($oldExtra['password'] ?? null),
                'store_id' => $extra['store_id'] ?? $oldExtra['store_id'] ?? null,
            ];
        }

        CourierSetting::updateOrCreate(
            ['courier' => $request->courier],
            $data
        );

        return back()->with('success', ucfirst($request->courier) . ' settings saved.');
    }

    public function testConnection(Request $request)
    {
        $courier = $request->input('courier');
        $config  = CourierSetting::forCourier($courier);

        try {
            switch ($courier) {
                case 'steadfast':
                    $response = Http::withHeaders([
                        'Api-Key'    => $config?->api_key ?? '',
                        'Secret-Key' => $config?->api_secret ?? '',
                    ])->get('https://portal.packzy.com/api/v1/get_balance');
                    $ok = $response->successful();
                    break;

                case 'pathao':
                    $extra    = $config?->extra_config ?? [];
                    $response = Http::post('https://hermes-api.pathao.com/aladdin/api/v1/issue-token', [
                        'client_id'     => $config?->api_key ?? '',
                        'client_secret' => $config?->api_secret ?? '',
                        'username'      => $extra['username'] ?? '',
                        'password'      => $extra['password'] ?? '',
                        'grant_type'    => 'password',
                    ]);
                    $ok = ! empty($response->json('access_token'));
                    break;

                case 'redx':
                    $response = Http::withHeaders([
                        'Authorization' => 'Bearer ' . ($config?->api_key ?? ''),
                    ])->get('https://openapi.redx.com.bd/v1.0.0-beta/parcel/count');
                    $ok = $response->successful();
                    break;

                default:
                    $ok = false;
            }

            return response()->json([
                'success' => $ok,
                'message' => $ok
                    ? ucfirst($courier) . ' connection successful.'
                    : ucfirst($courier) . ' connection failed. Check your credentials.',
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }
}
