<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlacklistedPhone extends Model
{
    use \App\Models\Concerns\BelongsToTenant;

    protected $fillable = ['phone', 'reason', 'blacklisted_by', 'tenant_id'];

    public function blacklistedByUser()
    {
        return $this->belongsTo(User::class, 'blacklisted_by');
    }

    public static function isBlacklisted(string $phone, ?int $tenantId = null): bool
    {
        $q = static::where('phone', $phone);
        if ($tenantId) {
            $q->where('tenant_id', $tenantId);
        }
        return $q->exists();
    }
}
