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

class QuickOrderController extends Controller
{
    public function store(Request $request, OrderRestrictionService $restriction)
    {
        $validated = $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'variant_id' => 'nullable|integer|exists:product_variants,id',
            'qty'        => 'nullable|integer|min:1|max:20',
            'name'       => 'required|string|max:100',
            'phone'      => ['required', 'string', 'regex:/^01[0-9]{9}$/'],
            'address'    => 'required|string|max:500',
            'note'       => 'nullable|string|max:500',
        ], [
            'phone.regex'       => 'সঠিক ১১ সংখ্যার মোবাইল নম্বর দিন।',
            'product_id.exists' => 'পণ্যটি পাওয়া যায়নি।',
            'name.required'     => 'নাম দিন।',
            'address.required'  => 'ঠিকানা দিন।',
        ]);

        $product = Product::findOrFail($validated['product_id']);
        $qty     = (int) ($validated['qty'] ?? 1);

        // Determine unit price
        $unitPrice = (float) ($product->sale_price ?? $product->price);

        // Shipping
        $freeAbove     = (float) Setting::getValue('free_shipping_above', 999);
        $shippingCharge = (float) Setting::getValue('shipping_charge', 60);
        $subtotal       = $unitPrice * $qty;
        $shipping       = $subtotal >= $freeAbove ? 0 : $shippingCharge;
        $total          = $subtotal + $shipping;

        $phone = $validated['phone'];

        // Run restriction checks
        $checks = [
            $restriction->checkPhoneFormat($phone),
            $restriction->checkBlacklist($phone),
            $restriction->checkOrderLimit($phone),
            $restriction->checkOrderAmount($total),
            $restriction->checkProductQty([['qty' => $qty]]),
            $restriction->checkDuplicateOrder($phone, [$product->id], $validated['address']),
        ];

        foreach ($checks as $check) {
            if (!$check['allowed']) {
                return response()->json(['success' => false, 'message' => $check['message']], 422);
            }
        }

        // Variant info
        $variantInfo = null;
        if ($validated['variant_id']) {
            $variant = ProductVariant::find($validated['variant_id']);
            if ($variant) {
                $variantInfo = $variant->name ?? $variant->size ?? null;
            }
        }

        // Create order
        $order = DB::transaction(function () use ($product, $validated, $qty, $unitPrice, $subtotal, $shipping, $total, $variantInfo) {
            $order = Order::create([
                'shipping_name'    => $validated['name'],
                'shipping_phone'   => $validated['phone'],
                'shipping_address' => $validated['address'],
                'notes'            => $validated['note'] ?? null,
                'subtotal'         => $subtotal,
                'shipping_charge'  => $shipping,
                'total'            => $total,
                'payment_method'   => 'cod',
                'payment_status'   => 'pending',
                'status'           => 'pending',
                'source'           => 'website',
                'source_url'       => url()->previous(),
            ]);

            OrderItem::create([
                'order_id'     => $order->id,
                'product_id'   => $product->id,
                'variant_id'   => $validated['variant_id'] ?? null,
                'product_name' => $product->name,
                'variant_info' => $variantInfo,
                'qty'          => $qty,
                'unit_price'   => $unitPrice,
                'discount'     => 0,
                'total'        => $unitPrice * $qty,
            ]);

            return $order;
        });

        return response()->json([
            'success'      => true,
            'order_number' => $order->order_number,
            'message'      => 'অর্ডার সফলভাবে সম্পন্ন হয়েছে!',
        ]);
    }
}
