<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Services\OrderRestrictionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CartController extends Controller
{
    public function checkout(Request $request, OrderRestrictionService $restriction)
    {
        $request->validate([
            'items'         => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.variant_id' => 'nullable|integer|exists:product_variants,id',
            'items.*.qty'        => 'required|integer|min:1|max:20',
            'name'          => 'required|string|max:100',
            'phone'         => ['required', 'string', 'regex:/^01[0-9]{9}$/'],
            'address'       => 'required|string|max:500',
            'note'          => 'nullable|string|max:500',
        ], [
            'phone.regex'        => 'সঠিক ১১ সংখ্যার মোবাইল নম্বর দিন।',
            'items.required'     => 'কার্ট খালি।',
            'name.required'      => 'নাম দিন।',
            'address.required'   => 'ঠিকানা দিন।',
        ]);

        $freeAbove      = (float) Setting::getValue('free_shipping_above', 999);
        $shippingCharge = (float) Setting::getValue('shipping_charge', 60);

        $subtotal   = 0;
        $lineItems  = [];

        foreach ($request->items as $item) {
            $product   = Product::findOrFail($item['product_id']);
            $unitPrice = (float) ($product->sale_price ?? $product->price);
            $qty       = (int) $item['qty'];
            $lineTotal = $unitPrice * $qty;
            $subtotal += $lineTotal;

            $variantInfo = null;
            if (!empty($item['variant_id'])) {
                $variant = ProductVariant::find($item['variant_id']);
                if ($variant) {
                    $variantInfo = $variant->name ?? $variant->size ?? null;
                }
            }

            $lineItems[] = [
                'product'      => $product,
                'variant_id'   => $item['variant_id'] ?? null,
                'variant_info' => $variantInfo,
                'qty'          => $qty,
                'unit_price'   => $unitPrice,
                'total'        => $lineTotal,
            ];
        }

        $shipping = $subtotal >= $freeAbove ? 0 : $shippingCharge;
        $total    = $subtotal + $shipping;
        $phone    = $request->phone;

        $checks = [
            $restriction->checkPhoneFormat($phone),
            $restriction->checkBlacklist($phone),
            $restriction->checkOrderLimit($phone),
            $restriction->checkOrderAmount($total),
            $restriction->checkProductQty($request->items),
            $restriction->checkDuplicateOrder($phone, array_column($request->items, 'product_id'), $request->address),
        ];

        foreach ($checks as $check) {
            if (!$check['allowed']) {
                return response()->json(['success' => false, 'message' => $check['message']], 422);
            }
        }

        $order = DB::transaction(function () use ($request, $lineItems, $subtotal, $shipping, $total) {
            $order = Order::create([
                'shipping_name'    => $request->name,
                'shipping_phone'   => $request->phone,
                'shipping_address' => $request->address,
                'notes'            => $request->note,
                'subtotal'         => $subtotal,
                'shipping_charge'  => $shipping,
                'total'            => $total,
                'payment_method'   => 'cod',
                'payment_status'   => 'unpaid',
                'status'           => 'pending',
                'source'           => 'website',
                'source_url'       => url()->previous(),
            ]);

            foreach ($lineItems as $li) {
                OrderItem::create([
                    'order_id'     => $order->id,
                    'product_id'   => $li['product']->id,
                    'variant_id'   => $li['variant_id'],
                    'product_name' => $li['product']->name,
                    'variant_info' => $li['variant_info'],
                    'qty'          => $li['qty'],
                    'unit_price'   => $li['unit_price'],
                    'discount'     => 0,
                    'total'        => $li['total'],
                ]);
            }

            return $order;
        });

        return response()->json([
            'success'      => true,
            'order_number' => $order->order_number,
            'message'      => 'অর্ডার সফলভাবে সম্পন্ন হয়েছে!',
            'redirect'     => route('order.thank-you', ['order' => $order->order_number]),
        ]);
    }
}
