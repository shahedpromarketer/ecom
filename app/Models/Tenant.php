<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Tenant extends Model {
    protected $fillable = ['name','email','phone','logo','status','plan','plan_expires_at','owner_id','max_orders','max_users','settings','order_charge','charge_on'];
    protected $casts = ['settings' => 'array', 'plan_expires_at' => 'datetime', 'wallet_balance' => 'decimal:2', 'order_charge' => 'decimal:2'];

    protected static function booted(): void {
        // New shops pay the standard per-order fee unless one is given.
        static::creating(function (self $t) {
            if ($t->order_charge === null) {
                $t->order_charge = config('services.wallet.order_charge', 0.45);
            }
        });
    }

    public function owner() { return $this->belongsTo(User::class, 'owner_id'); }
    public function users() { return $this->hasMany(User::class, 'tenant_id'); }
    public function orders() { return $this->hasMany(Order::class, 'tenant_id'); }

    public function isActive(): bool {
        return $this->status === 'active' && (!$this->plan_expires_at || $this->plan_expires_at->isFuture());
    }

    /** Paying shop with nothing left in the wallet: orders still arrive, order work waits for a recharge. */
    public function walletLocked(): bool {
        return (float) $this->order_charge > 0 && (float) $this->wallet_balance <= 0;
    }

    /** About how many more orders the balance covers (null for a free shop). */
    public function ordersLeft(): ?int {
        return (float) $this->order_charge > 0 ? max(0, (int) floor((float) $this->wallet_balance / (float) $this->order_charge)) : null;
    }
}
