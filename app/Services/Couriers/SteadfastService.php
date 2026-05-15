<?php

namespace App\Services\Couriers;

use App\Models\CourierSetting;
use App\Models\Order;
use Illuminate\Support\Facades\Http;

class SteadfastService extends BaseCourierService
{
    private const BASE_URL = 'https://portal.steadfast.com.bd/public/api/v1';

    private string $apiKey;
    private string $secretKey;

    public function __construct()
    {
        $config          = CourierSetting::forCourier('steadfast');
        $this->apiKey    = $config?->api_key ?? '';
        $this->secretKey = $config?->api_secret ?? '';
    }

    public function createShipment(Order $order): array
    {
        $address = collect([
            $order->shipping_district,
            $order->shipping_area,
            $order->shipping_address,
        ])->filter()->implode(', ');

        $response = Http::withHeaders([
            'Api-Key'      => $this->apiKey,
            'Secret-Key'   => $this->secretKey,
            'Content-Type' => 'application/json',
        ])->post(self::BASE_URL . '/create_order', [
            'invoice'           => $order->order_number,
            'recipient_name'    => $order->shipping_name,
            'recipient_phone'   => $order->shipping_phone,
            'recipient_address' => $address,
            'cod_amount'        => $order->total,
            'note'              => '',
        ]);

        return $response->json();
    }

    public function trackShipment(string $trackingCode): array
    {
        $response = Http::withHeaders([
            'Api-Key'    => $this->apiKey,
            'Secret-Key' => $this->secretKey,
        ])->get(self::BASE_URL . '/status_by_trackingcode/' . $trackingCode);

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
