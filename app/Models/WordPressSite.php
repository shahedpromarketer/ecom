<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class WordPressSite extends Model
{
    protected $table = 'wordpress_sites';

    protected $fillable = [
        'name', 'site_url', 'api_key', 'is_active',
        'type', 'consumer_key', 'consumer_secret',
        'mobile_number', 'invoice_title', 'invoice_address', 'logo',
        'last_sync_at', 'last_product_sync_at', 'last_order_sync_at',
        'total_synced', 'total_products_synced',
    ];

    protected $casts = [
        'is_active'            => 'boolean',
        'last_sync_at'         => 'datetime',
        'last_product_sync_at' => 'datetime',
        'last_order_sync_at'   => 'datetime',
    ];

    public function logs()
    {
        return $this->hasMany(SyncLog::class, 'site_id');
    }

    public function getLogoUrlAttribute(): string
    {
        if ($this->logo && Storage::disk('public')->exists($this->logo)) {
            return asset('storage/' . $this->logo);
        }

        return 'https://ui-avatars.com/api/?name=' . urlencode($this->name)
             . '&size=80&background=1e3a5f&color=ffffff&bold=true&rounded=true';
    }

    public static function generateApiKey(): string
    {
        do {
            $key = bin2hex(random_bytes(32));
        } while (static::where('api_key', $key)->exists());

        return $key;
    }
}
