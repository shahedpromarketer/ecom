<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case COD = 'cod';
    case BKASH = 'bkash';
    case NAGAD = 'nagad';

    public function label(): string
    {
        return match($this) {
            self::COD => 'Cash on Delivery',
            self::BKASH => 'bKash',
            self::NAGAD => 'Nagad',
        };
    }
}
