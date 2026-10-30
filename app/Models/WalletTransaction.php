<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** One line of a shop's wallet: a recharge, an order fee or a manual adjustment. */
class WalletTransaction extends Model
{
    use \App\Models\Concerns\BelongsToTenant;

    protected $fillable = ['tenant_id', 'type', 'amount', 'balance_after', 'order_id', 'recharge_request_id', 'note', 'created_by'];

    protected $casts = ['amount' => 'decimal:2', 'balance_after' => 'decimal:2'];

    public function order()
    {
        return $this->belongsTo(Order::class)->withoutGlobalScopes();
    }
}
