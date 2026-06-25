<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IpBlocklist extends Model
{
    protected $table = 'ip_blocklist';

    protected $fillable = ['ip_address', 'reason', 'blocked_by', 'tenant_id'];

    public function blockedByUser()
    {
        return $this->belongsTo(User::class, 'blocked_by');
    }

    public static function isBlocked(string $ip, ?int $tenantId = null): bool
    {
        $q = static::where('ip_address', $ip);
        if ($tenantId) {
            $q->where(function ($q2) use ($tenantId) {
                $q2->where('tenant_id', $tenantId)->orWhereNull('tenant_id');
            });
        }
        return $q->exists();
    }
}
