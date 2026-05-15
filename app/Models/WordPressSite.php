<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WordPressSite extends Model
{
    protected $table = 'wordpress_sites';

    protected $fillable = [
        'name', 'site_url', 'api_key', 'is_active', 'last_sync_at', 'total_synced',
    ];

    protected $casts = [
        'is_active'    => 'boolean',
        'last_sync_at' => 'datetime',
    ];

    public function logs()
    {
        return $this->hasMany(SyncLog::class, 'site_id');
    }

    public static function generateApiKey(): string
    {
        do {
            $key = bin2hex(random_bytes(32));
        } while (static::where('api_key', $key)->exists());

        return $key;
    }
}
