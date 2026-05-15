<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class InvoiceSetupController extends Controller
{
    private array $fields = [
        'store_name', 'store_address', 'store_phone', 'store_email',
        'invoice_prefix', 'invoice_start_number', 'invoice_footer_note',
        'invoice_show_logo', 'invoice_show_address', 'invoice_show_social',
        'invoice_accent_color', 'invoice_paper_size',
    ];

    public function index()
    {
        $settings = [];
        foreach ($this->fields as $key) {
            $settings[$key] = Setting::getValue($key);
        }

        return view('admin.invoice-setup.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $data = $request->only($this->fields);

        foreach ($data as $key => $value) {
            Setting::setValue($key, $value, 'invoice');
        }

        if ($request->hasFile('store_logo')) {
            $path = $request->file('store_logo')->store('public/logo');
            Setting::setValue('store_logo', str_replace('public/', 'storage/', $path), 'invoice');
        }

        Cache::store('file')->forget('settings_all');

        return back()->with('success', 'Invoice setup saved.');
    }
}
