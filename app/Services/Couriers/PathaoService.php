<?php

namespace App\Services\Couriers;

use App\Models\CourierSetting;
use App\Models\Order;
use Illuminate\Support\Facades\Http;

class PathaoService extends BaseCourierService
{
    private string $baseUrl;
    private string $clientId;
    private string $clientSecret;

    public function __construct()
    {
        $config = CourierSetting::forCourier('pathao');
        $this->baseUrl      = $config?->base_url ?? 'https://merchant.pathao.com';
        $this->clientId     = $config?->api_key ?? '';
        $this->clientSecret = $config?->api_secret ?? '';
    }

    public function createShipment(Order $order): array
    {
        $token = $this->getAuthToken();

        $response = Http::withToken($token)
            ->post("{$this->baseUrl}/aladdin/api/v1/orders/", [
                'store_id'               => $this->clientId,
                'merchant_order_id'      => $order->order_number,
                'recipient_name'         => $order->shipping_name,
                'recipient_phone'        => $order->shipping_phone,
                'recipient_address'      => $order->shipping_address,
                'recipient_city'         => 1,
                'recipient_zone'         => 1,
                'delivery_type'          => 48,
                'item_type'              => 2,
                'special_instruction'    => $order->notes ?? '',
                'item_quantity'          => $order->items->sum('qty'),
                'item_weight'            => 0.5,
                'amount_to_collect'      => $order->total,
                'item_description'       => 'Clothing items',
            ]);

        return $response->json();
    }

    public function trackShipment(string $trackingCode): array
    {
        $token = $this->getAuthToken();

        $response = Http::withToken($token)
            ->get("{$this->baseUrl}/aladdin/api/v1/orders/{$trackingCode}/info");

        return $response->json();
    }

    public function cancelShipment(string $consignmentId): array
    {
        $token = $this->getAuthToken();

        $response = Http::withToken($token)
            ->post("{$this->baseUrl}/aladdin/api/v1/orders/cancel", [
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
        $response = Http::post("{$this->baseUrl}/aladdin/api/v1/issue-token/", [
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
            'grant_type'    => 'password',
            'username'      => '',
            'password'      => '',
        ]);

        return $response->json('access_token', '');
    }
}
