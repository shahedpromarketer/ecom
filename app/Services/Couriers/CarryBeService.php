<?php

namespace App\Services\Couriers;

use App\Models\Order;

class CarryBeService extends BaseCourierService
{
    public function createShipment(Order $order): array
    {
        // TODO: Implement CarryBe API integration
        // Endpoint: https://api.carrybe.com/api/orders (TBC)
        return ['message' => 'CarryBe integration pending', 'order' => $order->order_number];
    }

    public function trackShipment(string $trackingCode): array
    {
        // TODO: Implement CarryBe tracking API
        return ['tracking_code' => $trackingCode, 'status' => 'unknown'];
    }

    public function cancelShipment(string $consignmentId): array
    {
        // TODO: Implement CarryBe cancellation API
        return ['message' => 'CarryBe cancellation pending'];
    }

    public function calculateDeliveryFee(string $district): float
    {
        // TODO: Implement CarryBe delivery fee calculation
        return 100.0;
    }
}
