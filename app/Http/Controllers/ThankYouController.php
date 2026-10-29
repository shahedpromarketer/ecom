<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Setting;
use Illuminate\Http\Request;

class ThankYouController extends Controller
{
    public function index(Request $request)
    {
        $orderNumber = $request->query('order');

        $order = null;
        if ($orderNumber) {
            $order = Order::with('items')
                ->where('order_number', $orderNumber)
                ->first();
        }

        $settings = [
            'store_name'  => Setting::getValue('store_name', 'Clothonic'),
            'store_phone' => Setting::getValue('store_phone', ''),
            'whatsapp_phone' => Setting::getValue('whatsapp_phone', Setting::getValue('store_phone', '')),
            'free_shipping_above' => Setting::getValue('free_shipping_above', 999),
            'shipping_charge'     => Setting::getValue('shipping_charge', 60),
        ];

        return view('pages.thank-you', compact('order', 'settings'));
    }
}
