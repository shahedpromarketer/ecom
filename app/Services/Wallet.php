<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Tenant;
use App\Models\WalletRechargeRequest;
use App\Models\WalletTransaction;
use App\Support\Tenancy;
use Illuminate\Support\Facades\DB;

/**
 * Prepaid wallet. Every new order costs the shop's order_charge (default ৳0.45), once.
 * Orders are never refused: at zero balance they still arrive, and order work is locked
 * until a recharge is approved.
 */
class Wallet
{
    /** Take the fee for a new order (safe to call twice: one charge per order, ever). */
    public static function chargeOrder(Order $order): void
    {
        if (! $order->tenant_id) {
            return;
        }

        DB::transaction(function () use ($order) {
            $tenant = Tenant::whereKey($order->tenant_id)->lockForUpdate()->first();
            $fee = (float) ($tenant?->order_charge ?? 0);
            if (! $tenant || $fee <= 0 || WalletTransaction::withoutGlobalScopes()->where('order_id', $order->id)->exists()) {
                return;
            }

            $balance = round((float) $tenant->wallet_balance - $fee, 2);
            $tenant->forceFill(['wallet_balance' => $balance])->save();
            Tenancy::as($tenant->id, fn () => WalletTransaction::create([
                'tenant_id' => $tenant->id, 'type' => 'order', 'amount' => -$fee, 'balance_after' => $balance,
                'order_id' => $order->id, 'note' => 'Order #' . $order->order_number,
            ]));
        });
    }

    /** Add (or, with a negative amount, take) money: recharges and manual adjustments. */
    public static function credit(Tenant $tenant, float $amount, string $type, ?string $note = null, ?int $requestId = null, ?int $by = null): WalletTransaction
    {
        return DB::transaction(function () use ($tenant, $amount, $type, $note, $requestId, $by) {
            $locked = Tenant::whereKey($tenant->id)->lockForUpdate()->first();
            $balance = round((float) $locked->wallet_balance + $amount, 2);
            $locked->forceFill(['wallet_balance' => $balance])->save();

            return Tenancy::as($locked->id, fn () => WalletTransaction::create([
                'tenant_id' => $locked->id, 'type' => $type, 'amount' => round($amount, 2), 'balance_after' => $balance,
                'recharge_request_id' => $requestId, 'note' => $note, 'created_by' => $by,
            ]));
        });
    }

    /** Super admin approves a recharge request: the money goes into the shop's wallet once. */
    public static function approve(WalletRechargeRequest $request, int $by, ?float $amount = null): void
    {
        DB::transaction(function () use ($request, $by, $amount) {
            $r = WalletRechargeRequest::withoutGlobalScopes()->whereKey($request->id)->lockForUpdate()->first();
            if ($r->status !== 'pending') {
                throw new \RuntimeException('This request was already ' . $r->status . '.');
            }
            $amount ??= (float) $r->amount;
            $r->forceFill(['status' => 'approved', 'amount' => $amount, 'reviewed_by' => $by, 'reviewed_at' => now()])->save();
            self::credit(Tenant::findOrFail($r->tenant_id), $amount, 'recharge',
                'Recharge via ' . (WalletRechargeRequest::METHODS[$r->method] ?? $r->method) . ' (' . $r->transaction_id . ')', $r->id, $by);
        });
    }
}
