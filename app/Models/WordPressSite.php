<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class WordPressSite extends Model
{
    use \App\Models\Concerns\BelongsToTenant;

    protected $table = 'wordpress_sites';

    protected $fillable = [
        'name', 'site_url', 'api_key', 'is_active',
        'type', 'consumer_key', 'consumer_secret',
        'mobile_number', 'invoice_title', 'invoice_address', 'logo',
        'last_sync_at', 'last_product_sync_at', 'last_order_sync_at',
        'total_synced', 'total_products_synced', 'tenant_id',
        'plugin_connected', 'plugin_connected_at', 'plugin_version',
        'status_map', 'rest_url', 'last_seen_at', 'webhook_url', 'settings',
    ];

    protected $hidden = ['api_key', 'consumer_key', 'consumer_secret'];

    /** Store types. "custom" = any website using the REST API; "google_sheet" = rows from a sheet. */
    public const TYPES = [
        'woocommerce'  => 'WooCommerce (plugin)',
        'wordpress'    => 'WordPress',
        'custom'       => 'Custom website / API',
        'google_sheet' => 'Google Sheet',
        'landing_page' => 'Landing page',
        'cartflow'     => 'CartFlow',
    ];

    /** What new orders from this store are labelled with (orders.source). */
    public function orderSource(): string
    {
        return match ($this->type) {
            'custom', 'landing_page' => 'website',
            'google_sheet'           => 'google_sheet',
            default                  => 'woocommerce',
        };
    }

    protected $casts = [
        'is_active'            => 'boolean',
        'plugin_connected'     => 'boolean',
        'status_map'           => 'array',
        'settings'             => 'array',
        'last_seen_at'         => 'datetime',
        'last_sync_at'         => 'datetime',
        'last_product_sync_at' => 'datetime',
        'last_order_sync_at'   => 'datetime',
        'plugin_connected_at'  => 'datetime',
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
