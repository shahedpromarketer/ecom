<?php

namespace App\Services\Couriers;

use App\Models\CourierSetting;
use App\Models\Order;
use Illuminate\Support\Facades\Http;

class RedxService extends BaseCourierService
{
    private const BASE_URL = 'https://openapi.redx.com.bd/v1.0.0-beta';

    private string $apiKey;

    public function __construct()
    {
        $config        = CourierSetting::forCourier('redx');
        $this->apiKey  = $config?->api_key ?? '';
    }

    public function createShipment(Order $order): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type'  => 'application/json',
        ])->post(self::BASE_URL . '/parcel', [
            'customer_name'          => $order->shipping_name,
            'customer_phone'         => $order->shipping_phone,
            'delivery_area'          => $order->shipping_district,
            'delivery_area_id'       => 1,
            'customer_address'       => $order->shipping_address,
            'merchant_invoice_id'    => $order->order_number,
            'cash_collection_amount' => (float) $order->total,
            'parcel_weight'          => 500,
            'instruction'            => '',
            'value'                  => (float) $order->total,
        ]);

        $data = $response->json();

        if (empty($data['tracking_id'])) {
            throw new \Exception('RedX API error: ' . json_encode($data));
        }

        return [
            'tracking_code'  => $data['tracking_id'],
            'consignment_id' => (string) ($data['parcel_id'] ?? $data['tracking_id']),
            'status'         => 'submitted',
        ];
    }

    public function trackShipment(string $trackingCode): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
        ])->get(self::BASE_URL . '/parcel/track/' . $trackingCode);

        return $response->json();
    }

    public function cancelShipment(string $consignmentId): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
        ])->delete(self::BASE_URL . '/parcel/' . $consignmentId);

        return $response->json();
    }

    public function calculateDeliveryFee(string $district): float
    {
        return 60.0;
    }
}
