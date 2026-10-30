<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A shop's "I paid, please add it to my wallet" request, with the payment screenshot. */
class WalletRechargeRequest extends Model
{
    use \App\Models\Concerns\BelongsToTenant;

    public const METHODS = ['bkash' => 'bKash', 'nagad' => 'Nagad', 'rocket' => 'Rocket', 'bank' => 'Bank transfer', 'other' => 'Other'];

    protected $fillable = [
        'tenant_id', 'amount', 'method', 'transaction_id', 'sender', 'screenshot_path', 'note',
        'status', 'reject_reason', 'requested_by', 'reviewed_by', 'reviewed_at',
    ];

    protected $casts = ['amount' => 'decimal:2', 'reviewed_at' => 'datetime'];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by')->withoutGlobalScopes();
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by')->withoutGlobalScopes();
    }
}
