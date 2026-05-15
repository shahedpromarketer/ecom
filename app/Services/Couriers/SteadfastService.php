<?php

namespace App\Services\Couriers;

use App\Models\CourierSetting;
use App\Models\Order;
use Illuminate\Support\Facades\Http;

class SteadfastService extends BaseCourierService
{
    private string $baseUrl;
    private string $apiKey;
    private string $secretKey;

    public function __construct()
    {
        $config = CourierSetting::forCourier('steadfast');
        $this->baseUrl   = $config?->base_url ?? 'https://portal.steadfast.com.bd/public/api/v1';
        $this->apiKey    = $config?->api_key ?? '';
        $this->secretKey = $config?->api_secret ?? '';
    }

    public function createShipment(Order $order): array
    {
        $response = Http::withHeaders([
            'Api-Key'    => $this->apiKey,
            'Secret-Key' => $this->secretKey,
        ])->post("{$this->baseUrl}/create_order", [
            'invoice'          => $order->order_number,
            'recipient_name'   => $order->shipping_name,
            'recipient_phone'  => $order->shipping_phone,
            'recipient_address'=> $order->shipping_address,
            'cod_amount'       => $order->total,
            'note'             => $order->notes ?? '',
        ]);

        return $response->json();
    }

    public function trackShipment(string $trackingCode): array
    {
        $response = Http::withHeaders([
            'Api-Key'    => $this->apiKey,
            'Secret-Key' => $this->secretKey,
        ])->get("{$this->baseUrl}/status-by-trackingcode/{$trackingCode}");

        return $response->json();
    }

    public function cancelShipment(string $consignmentId): array
    {
        return ['message' => 'Cancellation not supported via API. Contact Steadfast support.'];
    }

    public function calculateDeliveryFee(string $district): float
    {
        return 130.0;
    }
}
