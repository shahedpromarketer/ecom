<?php

namespace App\Services\Couriers;

use App\Models\CourierSetting;
use App\Models\Order;
use Illuminate\Support\Facades\Http;

class SteadfastService extends BaseCourierService
{
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
        $setting = CourierSetting::where('courier', 'steadfast')
            ->where('is_active', true)
            ->first();

        if (! $setting) {
            throw new \Exception('Steadfast is not configured or not active.');
        }

        $response = Http::withHeaders([
            'Api-Key'      => $setting->api_key,
            'Secret-Key'   => $setting->api_secret,
            'Content-Type' => 'application/json',
        ])->post('https://portal.steadfast.com.bd/public/api/v1/create_order', [
            'invoice'           => $order->order_number,
            'recipient_name'    => $order->shipping_name,
            'recipient_phone'   => $order->shipping_phone,
            'recipient_address' => $order->shipping_district . ', ' . $order->shipping_area . ', ' . $order->shipping_address,
            'cod_amount'        => $order->total,
            'note'              => '',
        ]);

        if ($response->failed()) {
            throw new \Exception('Steadfast API error: ' . $response->body());
        }

        $data = $response->json();

        if (! isset($data['consignment'])) {
            throw new \Exception('Steadfast response invalid: ' . json_encode($data));
        }

        return [
            'tracking_code'  => $data['consignment']['tracking_code'] ?? null,
            'consignment_id' => $data['consignment']['id'] ?? null,
            'status'         => 'submitted',
        ];
    }

    public function trackShipment(string $trackingCode): array
    {
        $response = Http::withHeaders([
            'Api-Key'    => $this->apiKey,
            'Secret-Key' => $this->secretKey,
        ])->get('https://portal.steadfast.com.bd/public/api/v1/status_by_trackingcode/' . $trackingCode);

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
