<?php

namespace App\Services\Couriers;

use App\Models\CourierSetting;
use InvalidArgumentException;

class CourierFactory
{
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

    public static function activeList(): array
    {
        return CourierSetting::where('is_active', true)->pluck('courier')->toArray();
    }
}
