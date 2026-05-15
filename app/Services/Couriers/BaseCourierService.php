<?php

namespace App\Services\Couriers;

use App\Models\Order;

abstract class BaseCourierService
{
    abstract public function createShipment(Order $order): array;
    abstract public function trackShipment(string $trackingCode): array;
    abstract public function cancelShipment(string $consignmentId): array;
    abstract public function calculateDeliveryFee(string $district): float;
}
