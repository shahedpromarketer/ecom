<?php

namespace App\Services\Couriers;

use App\Models\CourierSetting;
use App\Models\Order;
use Illuminate\Support\Facades\Http;

class RedxService extends BaseCourierService
{
    private string $baseUrl;
    private string $apiKey;

    public function __construct()
    {
        $config = CourierSetting::forCourier('redx');
        $this->baseUrl = $config?->base_url ?? 'https://openapi.redx.com.bd/v1.0.0-beta';
        $this->apiKey  = $config?->api_key ?? '';
    }

    public function createShipment(Order $order): array
    {
        $response = Http::withToken($this->apiKey)
            ->post("{$this->baseUrl}/parcel", [
                'name'             => $order->shipping_name,
                'phone'            => $order->shipping_phone,
                'address'          => $order->shipping_address,
                'merchant_invoice_id' => $order->order_number,
                'cash_collection_amount' => (int) $order->total,
                'parcel_weight'    => 500,
            ]);

        return $response->json();
    }

    public function trackShipment(string $trackingCode): array
    {
        $response = Http::withToken($this->apiKey)
            ->get("{$this->baseUrl}/parcel/track/{$trackingCode}");

        return $response->json();
    }

    public function cancelShipment(string $consignmentId): array
    {
        $response = Http::withToken($this->apiKey)
            ->delete("{$this->baseUrl}/parcel/{$consignmentId}");

        return $response->json();
    }

    public function calculateDeliveryFee(string $district): float
    {
        return 60.0;
    }

    public function getAuthToken(): string
    {
        $response = Http::post("{$this->baseUrl}/auth/token", [
            'api_key' => $this->apiKey,
        ]);

        return $response->json('token', '');
    }
}
