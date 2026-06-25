<?php

namespace App\Services;

use App\Models\BlacklistedPhone;
use App\Models\IpBlocklist;
use App\Models\Order;
use App\Models\Setting;

class OrderRestrictionService
{
    public function checkPhoneFormat(string $phone): array
    {
        $valid = strlen($phone) === 11 && str_starts_with($phone, '01') && ctype_digit($phone);
        return [
            'allowed' => $valid,
            'message' => 'Please enter a valid 11-digit Bangladeshi phone number',
        ];
    }

    public function checkBlacklist(string $phone): array
    {
        $blocked = BlacklistedPhone::isBlacklisted($phone);
        return [
            'allowed' => !$blocked,
            'message' => 'This phone number is not allowed to place orders',
        ];
    }

    public function checkOrderLimit(string $phone): array
    {
        $minutes = (int) Setting::getValue('order_duplicate_block_minutes', 60);
        $limit   = (int) Setting::getValue('order_limit_count', 2);
        $hours   = (int) Setting::getValue('order_limit_hours', 24);

        $count = Order::where('shipping_phone', $phone)
            ->where('created_at', '>=', now()->subHours($hours))
            ->whereNotIn('status', ['cancelled', 'returned'])
            ->count();

        return [
            'allowed' => $count < $limit,
            'message' => "You have reached the order limit. Please try again after {$hours} hours",
        ];
    }

    public function checkDuplicateOrder(string $phone, array $productIds = [], string $address = ''): array
    {
        $minutes = (int) Setting::getValue('order_duplicate_block_minutes', 60);

        $recentOrder = Order::where('shipping_phone', $phone)
            ->where('created_at', '>=', now()->subMinutes($minutes))
            ->whereNotIn('status', ['cancelled', 'returned'])
            ->exists();

        return [
            'allowed' => !$recentOrder,
            'message' => 'এই নম্বর থেকে সম্প্রতি একটি অর্ডার করা হয়েছে। অনুগ্রহ করে কিছুক্ষণ পর আবার চেষ্টা করুন।',
        ];
    }

    public function checkIpBlock(string $ip, ?int $tenantId = null): array
    {
        if (!$ip || $ip === '127.0.0.1') {
            return ['allowed' => true, 'message' => ''];
        }

        $blocked = IpBlocklist::isBlocked($ip, $tenantId);
        return [
            'allowed' => !$blocked,
            'message' => 'Orders are not accepted from your location.',
        ];
    }

    public function check24hLimit(string $phone, ?int $tenantId = null): array
    {
        $q = Order::where('shipping_phone', $phone)
            ->where('created_at', '>=', now()->subHours(24))
            ->whereNotIn('status', ['cancelled', 'returned']);
        if ($tenantId) {
            $q->where('tenant_id', $tenantId);
        }
        $exists = $q->exists();

        return [
            'allowed' => !$exists,
            'message' => 'আপনি ইতিমধ্যে ২৪ ঘণ্টার মধ্যে একটি অর্ডার করেছেন। অনুগ্রহ করে পরে আবার চেষ্টা করুন।',
        ];
    }

    public function checkOrderAmount(float $total): array
    {
        $max = (float) Setting::getValue('order_max_amount', 5000);
        $min = (float) Setting::getValue('min_order_amount', 200);

        if ($total > $max) {
            return ['allowed' => false, 'message' => 'Maximum order amount is ৳' . number_format($max, 0) . '. Please reduce your order'];
        }

        if ($total < $min) {
            return ['allowed' => false, 'message' => 'Minimum order amount is ৳' . number_format($min, 0)];
        }

        return ['allowed' => true, 'message' => ''];
    }

    public function checkCODCancelHistory(string $phone): array
    {
        $limit = (int) Setting::getValue('cod_cancel_block_count', 3);

        $cancelCount = Order::where('shipping_phone', $phone)
            ->where('status', 'cancelled')
            ->count();

        if ($cancelCount >= $limit) {
            return [
                'allowed'     => false,
                'cod_blocked' => true,
                'message'     => "COD is not available due to {$cancelCount} previous cancellation(s). Please use online payment.",
            ];
        }

        return ['allowed' => true, 'cod_blocked' => false, 'message' => ''];
    }

    public function checkProductQty(array $items): array
    {
        $max = (int) Setting::getValue('max_qty_per_product', 10);

        foreach ($items as $item) {
            $qty = (int) ($item['qty'] ?? $item['quantity'] ?? 0);
            if ($qty > $max) {
                return ['allowed' => false, 'message' => "Maximum {$max} quantity allowed per product"];
            }
        }

        return ['allowed' => true, 'message' => ''];
    }

    public function validateAll(array $data): array
    {
        $phone      = $data['phone'] ?? '';
        $total      = (float) ($data['total'] ?? 0);
        $productIds = $data['product_ids'] ?? [];
        $address    = $data['address'] ?? '';
        $items      = $data['items'] ?? [];

        $checks = [
            $this->checkPhoneFormat($phone),
            $this->checkBlacklist($phone),
            $this->checkOrderLimit($phone),
            $this->checkOrderAmount($total),
            $this->checkProductQty($items),
            $this->checkDuplicateOrder($phone, $productIds, $address),
        ];

        foreach ($checks as $result) {
            if (!$result['allowed']) {
                return $result;
            }
        }

        return ['allowed' => true, 'message' => ''];
    }

    public function validatePhone(string $phone): bool
    {
        return $this->checkPhoneFormat($phone)['allowed'];
    }
}
