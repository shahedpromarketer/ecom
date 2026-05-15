<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlacklistedPhone extends Model
{
    protected $fillable = ['phone', 'reason', 'blacklisted_by'];

    public function blacklistedByUser()
    {
        return $this->belongsTo(User::class, 'blacklisted_by');
    }

    public static function isBlacklisted(string $phone): bool
    {
        return static::where('phone', $phone)->exists();
    }
}
