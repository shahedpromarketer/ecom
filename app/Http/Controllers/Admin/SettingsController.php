<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SettingsController extends Controller
{
    private array $groups = [
        'general'      => ['store_name', 'store_tagline', 'store_phone', 'store_email', 'store_address', 'currency_symbol', 'timezone', 'date_format'],
        'order_rules'  => ['order_max_amount', 'order_limit_count', 'order_limit_hours', 'min_order_amount', 'free_shipping_above', 'default_shipping_charge', 'order_duplicate_block_minutes', 'cod_cancel_block_count', 'max_qty_per_product', 'auto_cancel_hours', 'followup_after_delivery_days'],
        'social'       => ['facebook_url', 'instagram_url', 'youtube_url', 'whatsapp_number', 'support_email'],
        'appearance'   => ['site_logo', 'favicon'],
        'notification' => ['notify_admin_new_order', 'notify_customer_order_placed', 'notify_customer_status_change'],
    ];

    public function index()
    {
        $tenantId = auth()->user()->tenant_id;
        $settings  = Setting::forTenant($tenantId)->pluck('value', 'key');
        return view('admin.settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        $group    = $request->get('group', 'general');
        $keys     = $this->groups[$group] ?? [];

        foreach ($keys as $key) {
            if ($request->has($key)) {
                Setting::setValue($key, $request->input($key), $group, $tenantId);
            }
        }

        if ($group === 'appearance') {
            foreach (['site_logo', 'favicon'] as $file) {
                if ($request->hasFile($file)) {
                    $path = $request->file($file)->store('public/logo');
                    Setting::setValue($file, str_replace('public/', 'storage/', $path), 'appearance', $tenantId);
                }
            }
        }

        Cache::store('file')->forget('settings_all_' . $tenantId);

        return back()->with('success', 'Settings saved successfully.');
    }
}
