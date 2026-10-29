<?php

namespace App\Services\Couriers;

use App\Models\CourierSetting;
use App\Models\Order;
use App\Support\Tenancy;
use InvalidArgumentException;

class CourierFactory
{
    public const SERVICES = [
        'steadfast' => SteadfastService::class,
        'pathao'    => PathaoService::class,
        'redx'      => RedxService::class,
        'carrybee'  => CarryBeeService::class,
        'paperfly'  => PaperFlyService::class,
    ];

    public static function for(CourierSetting $account): BaseCourierService
    {
        $class = self::SERVICES[$account->courier] ?? throw new InvalidArgumentException("Unknown courier: {$account->courier}");

        return new $class($account);
    }

    /** @return class-string<BaseCourierService> */
    public static function class(string $courier): string
    {
        return self::SERVICES[$courier === 'carrybe' ? 'carrybee' : $courier] ?? throw new InvalidArgumentException("Unknown courier: {$courier}");
    }

    /** The shop's default active account for a courier, as a service. */
    public static function make(string $courier): BaseCourierService
    {
        $account = CourierSetting::defaultFor($courier);
        if (! $account) {
            throw new InvalidArgumentException(ucfirst($courier) . ' is not set up or not turned on (Settings → Courier).');
        }

        return self::for($account);
    }

    /** Run courier work as the shop that owns the order. */
    public static function forOrder(Order $order, string $courier, callable $callback): mixed
    {
        return Tenancy::as($order->tenant_id, fn () => $callback(self::make($courier)));
    }

    public static function activeList(): array
    {
        return CourierSetting::where('is_active', true)->pluck('courier')->unique()->values()->all();
    }
}
