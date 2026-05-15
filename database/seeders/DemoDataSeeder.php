<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\CourierShipment;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderNote;
use App\Models\OrderReturn;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    private int $invoiceSeq = 0;

    public function run(): void
    {
        $admin = User::where('role', 'admin')->first();

        $customers = $this->seedCustomers();
        $products  = $this->seedProducts();
        $this->seedBanners();
        $orders = $this->seedOrders($customers, $products, $admin);
        $this->seedReturns($orders);
        $this->seedActivityLogs($orders, $products, $admin);
        $this->seedCoupons();
    }

    // ── Customers ──────────────────────────────────────────────────────────────

    private function seedCustomers(): array
    {
        $rows = [
            ['name' => 'Mohammad Rahim', 'phone' => '01712345678', 'email' => 'rahim@example.com'],
            ['name' => 'Karim Hossain',  'phone' => '01812345678', 'email' => 'karim@example.com'],
            ['name' => 'Jamal Uddin',    'phone' => '01912345678', 'email' => 'jamal@example.com'],
            ['name' => 'Arif Khan',      'phone' => '01612345678', 'email' => 'arif@example.com'],
            ['name' => 'Sohel Rana',     'phone' => '01512345678', 'email' => 'sohel@example.com'],
        ];

        return array_map(fn($r) => User::create([
            'name'     => $r['name'],
            'email'    => $r['email'],
            'phone'    => $r['phone'],
            'password' => Hash::make('password'),
            'role'     => 'customer',
        ]), $rows);
    }

    // ── Products ───────────────────────────────────────────────────────────────

    private function seedProducts(): array
    {
        $defs = [
            [
                'name'        => 'Classic White Polo Shirt',
                'price'       => 850,
                'sale_price'  => 699,
                'sku'         => 'POLO-WHT',
                'is_featured' => true,
                'cats'        => ['T-Shirts', 'Polo'],
                'variants'    => [
                    ['S',  'White', '#FFFFFF', 10],
                    ['M',  'White', '#FFFFFF', 15],
                    ['L',  'White', '#FFFFFF', 8],
                    ['XL', 'White', '#FFFFFF', 5],
                ],
            ],
            [
                'name'        => 'Navy Blue Formal Shirt',
                'price'       => 1200,
                'sale_price'  => null,
                'sku'         => 'SHIRT-NVY',
                'is_featured' => true,
                'cats'        => ['Shirts', 'Formal'],
                'variants'    => [
                    ['M',   'Navy', '#001F5B', 12],
                    ['L',   'Navy', '#001F5B', 10],
                    ['XL',  'Navy', '#001F5B', 6],
                    ['XXL', 'Navy', '#001F5B', 3],
                ],
            ],
            [
                'name'        => 'Black Slim Fit Jeans',
                'price'       => 1800,
                'sale_price'  => 1499,
                'sku'         => 'JEANS-BLK',
                'is_featured' => false,
                'cats'        => ['Pants'],
                'variants'    => [
                    ['30', 'Black', '#111111', 8],
                    ['32', 'Black', '#111111', 14],
                    ['34', 'Black', '#111111', 10],
                    ['36', 'Black', '#111111', 4],
                ],
            ],
            [
                'name'        => 'Olive Cargo Shorts',
                'price'       => 950,
                'sale_price'  => 799,
                'sku'         => 'SHORTS-OLV',
                'is_featured' => true,
                'cats'        => ['Shorts'],
                'variants'    => [
                    ['M',  'Olive', '#6B7C4A', 20],
                    ['L',  'Olive', '#6B7C4A', 15],
                    ['XL', 'Olive', '#6B7C4A', 7],
                ],
            ],
            [
                'name'        => 'Dark Grey Hoodie Jacket',
                'price'       => 2200,
                'sale_price'  => 1899,
                'sku'         => 'JACKET-GRY',
                'is_featured' => true,
                'cats'        => ['Jackets'],
                'variants'    => [
                    ['S',   'Grey', '#555555', 5],
                    ['M',   'Grey', '#555555', 8],
                    ['L',   'Grey', '#555555', 10],
                    ['XL',  'Grey', '#555555', 6],
                    ['XXL', 'Grey', '#555555', 2],
                ],
            ],
        ];

        $products = [];
        foreach ($defs as $d) {
            $product = Product::create([
                'name'              => $d['name'],
                'slug'              => Str::slug($d['name']),
                'price'             => $d['price'],
                'sale_price'        => $d['sale_price'],
                'sku'               => $d['sku'],
                'status'            => 'active',
                'is_featured'       => $d['is_featured'],
                'short_description' => 'Premium quality men\'s ' . strtolower($d['name']) . '.',
            ]);

            $catIds = Category::whereIn('name', $d['cats'])->pluck('id');
            $product->categories()->sync($catIds);

            ProductImage::create([
                'product_id' => $product->id,
                'image_path' => 'products/placeholder.jpg',
                'is_primary' => true,
                'sort_order' => 0,
            ]);

            foreach ($d['variants'] as [$size, $color, $hex, $stock]) {
                ProductVariant::create([
                    'product_id' => $product->id,
                    'size'       => $size,
                    'color'      => $color,
                    'color_hex'  => $hex,
                    'stock'      => $stock,
                    'sku'        => $d['sku'] . '-' . $size,
                    'is_active'  => true,
                ]);
            }

            $products[] = $product->load('variants');
        }

        return $products;
    }

    // ── Banners ────────────────────────────────────────────────────────────────

    private function seedBanners(): void
    {
        Banner::create([
            'title'      => 'Summer Sale - Up to 50% Off',
            'subtitle'   => 'Limited time offer on selected items',
            'image'      => 'banners/placeholder.jpg',
            'position'   => 'hero',
            'is_active'  => true,
            'sort_order' => 1,
        ]);

        Banner::create([
            'title'      => 'New Collection 2025',
            'subtitle'   => 'Fresh styles for the modern man',
            'image'      => 'banners/placeholder.jpg',
            'position'   => 'section',
            'is_active'  => true,
            'sort_order' => 2,
        ]);
    }

    // ── Orders ─────────────────────────────────────────────────────────────────

    private function seedOrders(array $customers, array $products, ?User $admin): array
    {
        $statuses = [
            'pending', 'confirmed', 'processing', 'hold', 'shipped',
            'delivered', 'completed', 'cancelled', 'pending_return', 'returned',
        ];

        $addresses = [
            ['Dhaka',      'Dhaka',      'Mirpur',       'House 12, Road 5, Mirpur-10, Dhaka'],
            ['Chattogram', 'Chattogram', 'Agrabad',      'Flat 3B, Agrabad Commercial Area'],
            ['Sylhet',     'Sylhet',     'Zindabazar',   'Tower Block, Zindabazar Road'],
            ['Rajshahi',   'Rajshahi',   'Shaheb Bazar', '15 Shaheb Bazar Road, Rajshahi'],
            ['Khulna',     'Khulna',     'KDA Avenue',   'Plot 22, KDA Avenue, Khulna'],
        ];

        $paymentPool  = ['cod','cod','cod','cod','cod','cod','bkash','bkash','bkash','nagad'];
        $sourcePool   = ['website','website','website','website','wordpress','wordpress','wordpress','manual','manual','whatsapp'];
        $notesPool    = [
            'Customer called and confirmed the order',
            'Address verified via phone',
            'Requested faster delivery',
            'Product color confirmed with customer',
            'Customer is a regular buyer',
        ];

        $allOrders  = [];
        $shipOrders = [];
        $orderSeq   = 1;

        foreach ($statuses as $status) {
            for ($i = 0; $i < 5; $i++) {
                $customer      = $customers[($orderSeq - 1) % count($customers)];
                [$div, $dist, $area, $addr] = $addresses[($orderSeq - 1) % count($addresses)];
                $payMethod     = $paymentPool[array_rand($paymentPool)];
                $source        = $sourcePool[array_rand($sourcePool)];
                $payStatus     = in_array($status, ['completed', 'delivered']) ? 'paid' : 'unpaid';
                $orderDate     = Carbon::now()->subDays(rand(0, 30));

                // Pick 1-3 products
                $shuffled = $products;
                shuffle($shuffled);
                $pickedProducts = array_slice($shuffled, 0, rand(1, 3));

                $subtotal = 0;
                $itemRows = [];
                foreach ($pickedProducts as $p) {
                    $variant  = $p->variants->first();
                    $price    = (float) ($p->sale_price ?? $p->price);
                    $qty      = rand(1, 3);
                    $subtotal += $price * $qty;
                    $itemRows[] = [
                        'product'      => $p,
                        'variant'      => $variant,
                        'qty'          => $qty,
                        'unit_price'   => $price,
                    ];
                }

                $shipping = 60;
                $discount = 0;
                $total    = $subtotal + $shipping - $discount;

                $orderNumber = 'CLT' . $orderDate->format('Ymd') . '-' . str_pad($orderSeq, 4, '0', STR_PAD_LEFT);

                $order = Order::create([
                    'user_id'            => $customer->id,
                    'order_number'       => $orderNumber,
                    'status'             => $status,
                    'subtotal'           => $subtotal,
                    'discount'           => $discount,
                    'shipping_charge'    => $shipping,
                    'total'              => $total,
                    'payment_method'     => $payMethod,
                    'payment_status'     => $payStatus,
                    'shipping_name'      => $customer->name,
                    'shipping_phone'     => $customer->phone,
                    'shipping_division'  => $div,
                    'shipping_district'  => $dist,
                    'shipping_area'      => $area,
                    'shipping_address'   => $addr,
                    'source'             => $source,
                    'print_status'       => 'unprinted',
                    'assigned_to'        => $admin?->id,
                    'created_at'         => $orderDate,
                    'updated_at'         => $orderDate,
                ]);

                foreach ($itemRows as $row) {
                    OrderItem::create([
                        'order_id'     => $order->id,
                        'product_id'   => $row['product']->id,
                        'variant_id'   => $row['variant']?->id,
                        'product_name' => $row['product']->name,
                        'variant_info' => $row['variant'] ? ($row['variant']->size . ' / ' . $row['variant']->color) : null,
                        'qty'          => $row['qty'],
                        'unit_price'   => $row['unit_price'],
                        'total'        => $row['unit_price'] * $row['qty'],
                    ]);
                }

                // Invoice
                $this->invoiceSeq++;
                Invoice::create([
                    'order_id'       => $order->id,
                    'invoice_number' => 'CLT-INV-' . (1000 + $this->invoiceSeq),
                    'subtotal'       => $subtotal,
                    'discount'       => $discount,
                    'shipping'       => $shipping,
                    'total'          => $total,
                    'status'         => in_array($status, ['completed', 'delivered', 'paid']) ? 'paid' : 'draft',
                    'created_at'     => $orderDate,
                    'updated_at'     => $orderDate,
                ]);

                // Notes (1-2 per order)
                $noteCount = rand(1, 2);
                $shuffledNotes = $notesPool;
                shuffle($shuffledNotes);
                foreach (array_slice($shuffledNotes, 0, $noteCount) as $noteText) {
                    if ($admin) {
                        OrderNote::create([
                            'order_id'             => $order->id,
                            'user_id'              => $admin->id,
                            'note'                 => $noteText,
                            'is_customer_visible'  => false,
                            'created_at'           => $orderDate->copy()->addMinutes(rand(10, 60)),
                        ]);
                    }
                }

                // Courier for shipped/delivered/completed
                if (in_array($status, ['shipped', 'delivered', 'completed'])) {
                    $couriers = ['pathao', 'steadfast', 'manual'];
                    CourierShipment::create([
                        'order_id'      => $order->id,
                        'courier_name'  => $couriers[array_rand($couriers)],
                        'tracking_code' => 'TRK' . str_pad(rand(0, 99999999), 8, '0', STR_PAD_LEFT),
                        'status'        => 'submitted',
                        'delivery_fee'  => [60, 80, 100][array_rand([60, 80, 100])],
                        'created_at'    => $orderDate->copy()->addHours(rand(2, 12)),
                    ]);
                }

                if ($status === 'returned') {
                    $shipOrders[] = $order;
                }

                $allOrders[] = $order;
                $orderSeq++;
            }
        }

        return ['all' => $allOrders, 'returned' => $shipOrders];
    }

    // ── Returns ────────────────────────────────────────────────────────────────

    private function seedReturns(array $orders): void
    {
        $reasons = [
            'Product quality not as expected',
            'Wrong size delivered',
            'Product damaged during delivery',
            'Customer changed mind',
            'Product not matching description',
        ];

        $statuses = ['requested', 'requested', 'approved', 'approved', 'completed'];
        $returnOrders = $orders['returned'] ?? [];

        foreach ($returnOrders as $i => $order) {
            $status = $statuses[$i] ?? 'requested';
            OrderReturn::create([
                'order_id'      => $order->id,
                'reason'        => $reasons[$i % count($reasons)],
                'status'        => $status,
                'refund_amount' => (float) $order->total,
                'refund_method' => in_array($status, ['approved', 'completed']) ? 'bkash' : null,
                'resolved_by'   => in_array($status, ['approved', 'completed']) ? User::where('role', 'admin')->value('id') : null,
                'resolved_at'   => in_array($status, ['approved', 'completed']) ? now()->subDays(rand(1, 5)) : null,
                'requested_at'  => $order->created_at->copy()->addDays(1),
            ]);
        }
    }

    // ── Activity Logs ──────────────────────────────────────────────────────────

    private function seedActivityLogs(array $orderBag, array $products, ?User $admin): void
    {
        if (!$admin) return;

        $allOrders = $orderBag['all'];
        $adminId   = $admin->id;

        $entries = [
            ['order.status_changed', $allOrders[0],  "Order #{$allOrders[0]->order_number} status changed to confirmed"],
            ['order.status_changed', $allOrders[1],  "Order #{$allOrders[1]->order_number} status changed to processing"],
            ['order.note_added',     $allOrders[2],  "Note added to Order #{$allOrders[2]->order_number}"],
            ['order.courier_assigned',$allOrders[4], "Order #{$allOrders[4]->order_number} courier assigned to Pathao"],
            ['order.status_changed', $allOrders[6],  "Order #{$allOrders[6]->order_number} status changed to delivered"],
            ['order.status_changed', $allOrders[10], "Order #{$allOrders[10]->order_number} status changed to completed"],
            ['order.status_changed', $allOrders[20], "Order #{$allOrders[20]->order_number} status changed to cancelled"],
            ['product.created',      $products[0],   "Product '{$products[0]->name}' was created"],
            ['product.updated',      $products[1],   "Product '{$products[1]->name}' price updated"],
            ['product.cloned',       $products[2],   "Product '{$products[2]->name}' was cloned"],
            ['product.created',      $products[3],   "Product '{$products[3]->name}' was created"],
            ['product.created',      $products[4],   "Product '{$products[4]->name}' was created"],
            ['return.requested',     $allOrders[45], "Return requested for Order #{$allOrders[45]->order_number}"],
            ['return.approved',      $allOrders[46], "Return approved for Order #{$allOrders[46]->order_number}"],
            ['return.completed',     $allOrders[47], "Return completed for Order #{$allOrders[47]->order_number}"],
            ['order.courier_submitted', $allOrders[5], "Steadfast shipment created for Order #{$allOrders[5]->order_number}"],
            ['order.courier_submitted', $allOrders[15], "Manual courier assigned for Order #{$allOrders[15]->order_number}"],
            ['settings.updated',     $admin,         "Store settings updated by {$admin->name}"],
            ['order.note_added',     $allOrders[30], "Note added to Order #{$allOrders[30]->order_number}"],
            ['product.updated',      $products[0],   "Product '{$products[0]->name}' stock updated"],
        ];

        foreach ($entries as $idx => [$action, $subject, $description]) {
            ActivityLog::create([
                'user_id'      => $adminId,
                'action'       => $action,
                'subject_type' => get_class($subject),
                'subject_id'   => $subject->id,
                'description'  => $description,
                'properties'   => [],
                'ip_address'   => '127.0.0.1',
                'user_agent'   => 'DemoSeeder/1.0',
                'created_at'   => Carbon::now()->subDays(rand(0, 30)),
                'updated_at'   => Carbon::now()->subDays(rand(0, 30)),
            ]);
        }
    }

    // ── Coupons ────────────────────────────────────────────────────────────────

    private function seedCoupons(): void
    {
        Coupon::create([
            'code'             => 'SAVE10',
            'type'             => 'percent',
            'value'            => 10,
            'min_order_amount' => 500,
            'is_active'        => true,
        ]);

        Coupon::create([
            'code'             => 'FLAT100',
            'type'             => 'fixed',
            'value'            => 100,
            'min_order_amount' => 800,
            'is_active'        => true,
        ]);

        Coupon::create([
            'code'             => 'WELCOME50',
            'type'             => 'percent',
            'value'            => 50,
            'min_order_amount' => 0,
            'max_discount'     => 200,
            'is_active'        => false,
        ]);
    }
}
