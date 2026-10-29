<?php

namespace App\Services\Couriers;

use App\Models\CourierSetting;
use App\Models\Order;
use App\Support\Tenancy;
use InvalidArgumentException;

class CourierFactory
{
    /**
     * Build a courier service using the CURRENT tenant's credentials.
     * Prefer forOrder(), which pins the tenant to the order's shop.
     */
    public static function make(string $courier): BaseCourierService
    {
        $config = CourierSetting::forCourier($courier);

        if (! $config || ! $config->is_active) {
            throw new InvalidArgumentException("Courier '{$courier}' is not active or not configured.");
        }

        return match ($courier) {
            'pathao'    => new PathaoService(),
            'steadfast' => new SteadfastService(),
            'carrybe'   => new CarryBeService(),
            'redx'      => new RedxService(),
            'paperfly'  => new PaperFlyService(),
            default     => throw new InvalidArgumentException("Unknown courier: {$courier}"),
        };
    }

    /**
     * Run courier work with the credentials of the shop that owns $order,
     * whoever is logged in (shop staff, super admin, a queued job).
     *
     *   $result = CourierFactory::forOrder($order, 'steadfast', fn ($svc) => $svc->createShipment($order));
     */
    public static function forOrder(Order $order, string $courier, callable $callback): mixed
    {
        return Tenancy::as($order->tenant_id, fn () => $callback(self::make($courier)));
    }

    public static function activeList(): array
    {
        return CourierSetting::where('is_active', true)->pluck('courier')->toArray();
    }
}
