<?php

namespace App\Services\Couriers;

use App\Models\CourierSetting;
use App\Models\Order;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class PathaoService extends BaseCourierService
{
    private const BASE_URL = 'https://hermes-api.pathao.com';

    private string $clientId;
    private string $clientSecret;
    private string $username;
    private string $password;
    private string $storeId;

    public function __construct()
    {
        $config              = CourierSetting::forCourier('pathao');
        $extra               = $config?->extra_config ?? [];
        $this->clientId      = $config?->api_key ?? '';
        $this->clientSecret  = $config?->api_secret ?? '';
        $this->username      = $extra['username'] ?? '';
        $this->password      = $extra['password'] ?? '';
        $this->storeId       = $extra['store_id'] ?? '';
    }

    public function createShipment(Order $order): array
    {
        $token   = $this->getAuthToken();
        $address = collect([
            $order->shipping_district,
            $order->shipping_area,
            $order->shipping_address,
        ])->filter()->implode(', ');

        $response = Http::withToken($token)
            ->post(self::BASE_URL . '/aladdin/api/v1/orders/', [
                'store_id'            => $this->storeId,
                'merchant_order_id'   => $order->order_number,
                'recipient_name'      => $order->shipping_name,
                'recipient_phone'     => $order->shipping_phone,
                'recipient_address'   => $address,
                'recipient_city'      => 1,
                'recipient_zone'      => 1,
                'delivery_type'       => 48,
                'item_type'           => 2,
                'special_instruction' => '',
                'item_quantity'       => 1,
                'item_weight'         => 0.5,
                'amount_to_collect'   => $order->total,
                'item_description'    => '',
            ]);

        return $response->json();
    }

    public function trackShipment(string $trackingCode): array
    {
        $token    = $this->getAuthToken();
        $response = Http::withToken($token)
            ->get(self::BASE_URL . '/aladdin/api/v1/orders/' . $trackingCode . '/info');

        return $response->json();
    }

    public function cancelShipment(string $consignmentId): array
    {
        $token    = $this->getAuthToken();
        $response = Http::withToken($token)
            ->post(self::BASE_URL . '/aladdin/api/v1/orders/cancel', [
                'consignment_id' => $consignmentId,
            ]);

        return $response->json();
    }

    public function calculateDeliveryFee(string $district): float
    {
        return 60.0;
    }

    public function getAuthToken(): string
    {
        $cacheKey = 'pathao_token_' . md5($this->clientId);

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $response = Http::post(self::BASE_URL . '/aladdin/api/v1/issue-token', [
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
            'username'      => $this->username,
            'password'      => $this->password,
            'grant_type'    => 'password',
        ]);

        $data      = $response->json();
        $token     = $data['access_token'] ?? '';
        $expiresIn = $data['expires_in'] ?? 3600;

        if ($token) {
            Cache::put($cacheKey, $token, max(60, $expiresIn - 60));
        }

        return $token;
    }
}
