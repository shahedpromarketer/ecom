<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Tenant extends Model {
    protected $fillable = ['name','email','phone','logo','status','plan','plan_expires_at','owner_id','max_orders','max_users','settings'];
    protected $casts = ['settings' => 'array', 'plan_expires_at' => 'datetime'];

    public function owner() { return $this->belongsTo(User::class, 'owner_id'); }
    public function users() { return $this->hasMany(User::class, 'tenant_id'); }
    public function orders() { return $this->hasMany(Order::class, 'tenant_id'); }

    public function isActive(): bool {
        return $this->status === 'active' && (!$this->plan_expires_at || $this->plan_expires_at->isFuture());
    }
}
