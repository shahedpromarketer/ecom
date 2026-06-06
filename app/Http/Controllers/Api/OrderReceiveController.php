<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;

class OrderReceiveController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'customer_phone' => 'required|string',
            'total'          => 'required|numeric|min:0',
            'items'          => 'required|array|min:1',
            'items.*.product_name' => 'required|string',
            'items.*.qty'          => 'required|integer|min:1',
            'items.*.unit_price'   => 'required|numeric|min:0',
        ]);

        $sourceOrderId = (string) $request->input('source_order_id', '');

        // Duplicate check by source_order_id
        if ($sourceOrderId) {
            $existing = Order::where('source_order_id', $sourceOrderId)
                ->where('source', 'wordpress')
                ->first();

            if ($existing) {
                return response()->json([
                    'success'      => true,
                    'duplicate'    => true,
                    'message'      => 'Order already exists.',
                    'order_number' => $existing->order_number,
                    'order_id'     => $existing->id,
                ]);
            }
        }

        $order = Order::create([
            'shipping_name'     => $request->input('customer_name', ''),
            'shipping_phone'    => $request->input('customer_phone'),
            'shipping_district' => $request->input('district', ''),
            'shipping_area'     => $request->input('area', ''),
            'shipping_address'  => $request->input('address', ''),
            'subtotal'          => $request->input('subtotal', $request->input('total')),
            'discount'          => $request->input('discount', 0),
            'shipping_charge'   => $request->input('shipping', 0),
            'total'             => $request->input('total'),
            'payment_method'    => $this->mapPaymentMethod($request->input('payment_method', 'cod')),
            'payment_status'    => 'unpaid',
            'status'            => 'pending',
            'source'            => $request->input('source', 'wordpress'),
            'source_order_id'   => $sourceOrderId ?: null,
            'notes'             => $request->input('note', ''),
        ]);

        foreach ($request->input('items') as $item) {
            $productId = null;
            $variantId = null;
            $productName = $item['product_name'];

            $sku = $item['sku'] ?? null;
            if ($sku) {
                $variant = ProductVariant::where('sku', $sku)->first();
                if ($variant) {
                    $variantId   = $variant->id;
                    $productId   = $variant->product_id;
                    $productName = $variant->product?->name ?? $productName;
                } else {
                    $product = Product::where('sku', $sku)->first();
                    if ($product) {
                        $productId   = $product->id;
                        $productName = $product->name;
                    }
                }
            }

            $qty       = (int) ($item['qty'] ?? 1);
            $unitPrice = (float) ($item['unit_price'] ?? 0);

            OrderItem::create([
                'order_id'     => $order->id,
                'product_id'   => $productId,
                'variant_id'   => $variantId,
                'product_name' => $productName,
                'qty'          => $qty,
                'unit_price'   => $unitPrice,
                'total'        => $item['total'] ?? ($unitPrice * $qty),
            ]);
        }

        return response()->json([
            'success'      => true,
            'order_number' => $order->order_number,
            'order_id'     => $order->id,
        ], 201);
    }

    public function index(Request $request)
    {
        $orders = Order::where('source', 'wordpress')
            ->latest()
            ->paginate(20);

        return response()->json($orders);
    }

    private function mapPaymentMethod(string $method): string
    {
        return match (strtolower($method)) {
            'cod', 'cash_on_delivery' => 'cod',
            'bkash'                   => 'bkash',
            'nagad'                   => 'nagad',
            'stripe', 'paypal', 'card'=> 'card',
            default                   => 'cod',
        };
    }
}
