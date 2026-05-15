<?php

namespace App\Services\Couriers;

use App\Models\CourierSetting;
use App\Models\Order;
use Illuminate\Support\Facades\Http;

class PaperFlyService extends BaseCourierService
{
    private string $baseUrl;
    private string $apiKey;
    private string $apiSecret;

    public function __construct()
    {
        $config = CourierSetting::forCourier('paperfly');
        $this->baseUrl   = $config?->base_url ?? 'https://api.paperfly.com.bd/api';
        $this->apiKey    = $config?->api_key ?? '';
        $this->apiSecret = $config?->api_secret ?? '';
    }

    public function createShipment(Order $order): array
    {
        $response = Http::withHeaders([
            'X-AUTH-KEY'    => $this->apiKey,
            'X-AUTH-SECRET' => $this->apiSecret,
        ])->post("{$this->baseUrl}/parcel/create", [
            'merchant_invoice' => $order->order_number,
            'customer_name'    => $order->shipping_name,
            'customer_phone'   => $order->shipping_phone,
            'customer_address' => $order->shipping_address,
            'cod_amount'       => $order->total,
        ]);

        return $response->json();
    }

    public function trackShipment(string $trackingCode): array
    {
        $response = Http::withHeaders([
            'X-AUTH-KEY'    => $this->apiKey,
            'X-AUTH-SECRET' => $this->apiSecret,
        ])->get("{$this->baseUrl}/parcel/track/{$trackingCode}");

        return $response->json();
    }

    public function cancelShipment(string $consignmentId): array
    {
        return ['message' => 'Contact Paperfly support to cancel: ' . $consignmentId];
    }

    public function calculateDeliveryFee(string $district): float
    {
        return 100.0;
    }
}
