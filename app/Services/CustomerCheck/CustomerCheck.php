<?php

namespace App\Services\CustomerCheck;

use App\Models\CourierSetting;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * "Courier Success Rate" for a customer's phone: each courier's own history (from the shop's
 * courier logins) and our own data across every shop on Ecom Pilot (counts only, no names).
 * Kept for 24 hours per shop + number so couriers are not asked again and again.
 */
class CustomerCheck
{
    /** Order statuses that mean the parcel reached the customer / came back. */
    public const DELIVERED = ['delivered', 'completed', 'partial_delivered', 'courier_payment_validate', 'hand_delivery_completed'];
    public const RETURNED = ['returned', 'pending_return', 'return_collection'];

    /** Couriers shown, in order. Those without a panel lookup say why. */
    public const COURIERS = [
        'pathao'    => 'Pathao',
        'steadfast' => 'Steadfast',
        'redx'      => 'RedX',
        'carrybee'  => 'CarryBee',
        'paperfly'  => 'Paperfly',
    ];

    public static function for(int $tenantId, string $rawPhone, bool $fresh = false): array
    {
        $phone = User::normalizePhone($rawPhone);
        if (! $phone || strlen($phone) !== 11) {
            return ['phone' => $rawPhone, 'invalid' => true];
        }
        $key = 'customer_check_' . $tenantId . '_' . $phone;
        if ($fresh) {
            Cache::forget($key);
        }

        if ($hit = Cache::get($key)) {
            return $hit;
        }
        $check = self::build($tenantId, $phone);
        // A courier that could not be reached is asked again sooner.
        Cache::put($key, $check, empty($check['retry']) ? now()->addDay() : now()->addMinutes(30));

        return $check;
    }

    private static function build(int $tenantId, string $phone): array
    {
        $accounts = CourierSetting::withoutGlobalScopes()->where('tenant_id', $tenantId)->where('is_active', true)
            ->orderByDesc('is_default')->get()->groupBy('courier');

        $couriers = [];
        $retry = false;
        foreach (self::COURIERS as $key => $label) {
            $row = ['label' => $label, 'total' => null, 'success' => null, 'failed' => null, 'rating' => null, 'note' => null];
            $acc = $accounts[$key][0] ?? null;
            try {
                $row = match ($key) {
                    'pathao'    => $acc ? array_merge($row, CourierPanels::pathao($acc, $phone)) : ['note' => 'Add a Pathao account in Courier Setup'] + $row,
                    'steadfast' => $acc ? array_merge($row, CourierPanels::steadfast($acc, $phone)) : ['note' => 'Add a Steadfast account in Courier Setup'] + $row,
                    'redx'      => ['note' => 'RedX no longer shares this'] + $row,
                    default     => ['note' => $label . ' does not share this'] + $row,
                };
            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                $row['note'] = $label . ' could not be reached right now — press Refresh later.';
                $retry = true;
            } catch (Throwable $e) {
                $row['note'] = $e->getMessage();
                $retry = true;
            }
            $couriers[$key] = $row;
        }

        $counted = collect($couriers)->filter(fn ($c) => $c['total'] !== null);
        $all = [
            'total'   => (int) $counted->sum('total'),
            'success' => (int) $counted->sum('success'),
            'failed'  => (int) $counted->sum('failed'),
        ];

        return [
            'phone'    => $phone,
            'checked'  => now()->toIso8601String(),
            'couriers' => $couriers,
            'own'      => self::own($phone, null),
            'shop'     => self::own($phone, $tenantId),
            'all'      => $all + ['rate' => self::rate($all)],
            'retry'    => $retry,
        ];
    }

    /** Our own history for the number: every shop on Ecom Pilot (null) or one shop. */
    public static function own(string $phone, ?int $tenantId): array
    {
        $last10 = substr($phone, -10);
        $base = DB::table('orders')
            ->whereNull('orders.deleted_at')
            // Numbers are saved as typed (+880 1711-…), so ignore spaces, dashes and +.
            ->whereRaw("REPLACE(REPLACE(REPLACE(orders.shipping_phone, ' ', ''), '-', ''), '+', '') LIKE ?", ['%' . $last10])
            ->when($tenantId, fn ($q) => $q->where('orders.tenant_id', $tenantId));

        $success = (clone $base)->whereIn('orders.status', self::DELIVERED)->count();
        $failed = (clone $base)->where(function ($q) {
            $q->whereIn('orders.status', self::RETURNED)
                ->orWhere(fn ($w) => $w->where('orders.status', 'cancelled')
                    ->whereExists(fn ($s) => $s->select(DB::raw(1))->from('courier_shipments')->whereColumn('courier_shipments.order_id', 'orders.id')));
        })->count();
        $r = ['total' => $success + $failed, 'success' => $success, 'failed' => $failed];

        return $r + ['rate' => self::rate($r)];
    }

    public static function rate(array $r): ?float
    {
        return ($r['total'] ?? 0) > 0 ? round($r['success'] * 100 / $r['total'], 1) : null;
    }

    /** green ≥ 80, yellow ≥ 50, red below, grey when there is no history. */
    public static function tone(?float $rate): string
    {
        return $rate === null ? 'gray' : ($rate >= 80 ? 'green' : ($rate >= 50 ? 'yellow' : 'red'));
    }
}
