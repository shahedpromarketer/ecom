<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\CourierSetting;
use App\Models\CourierShipment;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderNote;
use App\Models\Product;
use App\Services\Couriers\CourierFactory;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    const ALL_STATUSES = [
        'pending', 'confirmed', 'failed', 'processing', 'hold', 'hold_followup',
        'shipped', 'delivered', 'completed', 'cancelled', 'pending_return',
        'returned', 'damage', 'hand_delivery', 'hand_delivery_completed',
        'others', 'return_collection', 'courier_payment_validate',
    ];

    public function index(Request $request)
    {
        // Auto-pull new orders from active WooCommerce sites silently
        try {
            $sites = \App\Models\WordPressSite::where('is_active', true)
                ->whereIn('type', ['woocommerce', 'wordpress'])
                ->whereNotNull('consumer_key')
                ->whereNotNull('consumer_secret')
                ->get();

            foreach ($sites as $site) {
                if (true) {
                    app(IntegrationController::class)->pullNewOrders($site);
                }
            }
        } catch (\Exception $e) {
            // Silent fail — never break the orders page
        }

        $perPage = in_array((int) $request->get('per_page', 10), [10,25,50,100,150,200,250,300,500,1000,1500,2000])
            ? (int) $request->get('per_page', 10) : 10;

        $query = Order::with(['items.product.images', 'assignedTo', 'orderNotes', 'shipment'])->latest();

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Flag filters
        if ($request->filled('cod_changed')) {
            $query->where('cod_changed', true);
        }
        if ($request->filled('duplicates')) {
            $query->where('is_duplicate', true);
        }

        // Search
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('order_number', 'like', "%{$s}%")
                  ->orWhere('shipping_phone', 'like', "%{$s}%")
                  ->orWhere('shipping_name', 'like', "%{$s}%");
            });
        }

        // Dropdown filters
        if ($request->filled('source'))          $query->where('source', $request->source);
        if ($request->filled('payment_status'))  $query->where('payment_status', $request->payment_status);
        if ($request->filled('payment_method'))  $query->where('payment_method', $request->payment_method);
        if ($request->filled('order_tag'))       $query->where('order_tag', 'like', "%{$request->order_tag}%");
        if ($request->filled('utm_source'))      $query->where('utm_source', $request->utm_source);
        if ($request->filled('district'))        $query->where('shipping_district', 'like', "%{$request->district}%");
        if ($request->filled('print_status'))    $query->where('print_status', $request->print_status);
        if ($request->filled('amount_min'))      $query->where('total', '>=', $request->amount_min);
        if ($request->filled('amount_max'))      $query->where('total', '<=', $request->amount_max);

        // Category filter
        if ($request->filled('category')) {
            $query->whereHas('items.product.categories', fn ($q) => $q->where('categories.id', $request->category));
        }

        // Product filter
        if ($request->filled('product')) {
            $query->whereHas('items', fn ($q) => $q->where('product_id', $request->product));
        }

        // Courier filter
        if ($request->filled('courier')) {
            $query->whereHas('shipment', fn ($q) => $q->where('courier_name', $request->courier));
        }

        // Courier status filter
        if ($request->filled('courier_status')) {
            $query->whereHas('shipment', fn ($q) => $q->where('status', $request->courier_status));
        }

        // Date range — Order Created At
        if ($request->filled('created_range') && $request->created_range !== 'custom') {
            $this->applyDateFilter($query, 'created_at', $request->created_range);
        } elseif ($request->filled('created_from') || $request->filled('created_to')) {
            if ($request->filled('created_from')) $query->whereDate('created_at', '>=', $request->created_from);
            if ($request->filled('created_to'))   $query->whereDate('created_at', '<=', $request->created_to);
        }

        // Date range — Courier Submitted At
        if ($request->filled('courier_range') && $request->courier_range !== 'custom') {
            $query->whereHas('shipment', function ($q) use ($request) {
                $this->applyDateFilter($q, 'created_at', $request->courier_range);
            });
        }

        // Date range — Status Added At (approximated via orders.updated_at)
        if ($request->filled('status_range')) {
            $this->applyDateFilter($query, 'updated_at', $request->status_range);
        }

        // Date range — Note Added At
        if ($request->filled('note_range')) {
            $query->whereHas('orderNotes', function ($q) use ($request) {
                $this->applyDateFilter($q, 'created_at', $request->note_range);
            });
        }

        $orders = $query->paginate($perPage)->withQueryString();

        $categories = Category::where('is_active', true)->orderBy('name')->get();
        $districts  = Order::whereNotNull('shipping_district')
            ->distinct()->pluck('shipping_district')->filter()->sort()->values();
        $sources    = Order::whereNotNull('source')
            ->distinct()->pluck('source')->filter()->sort()->values();

        return view('admin.orders.index', compact(
            'orders', 'categories', 'districts', 'sources', 'perPage'
        ));
    }

    public function addNote(Request $request, Order $order)
    {
        $request->validate([
            'note_text'           => 'required|string|max:2000',
            'is_customer_visible' => 'boolean',
        ]);

        $note = $order->orderNotes()->create([
            'user_id'              => auth()->id(),
            'note_text'            => $request->note_text,
            'is_customer_visible'  => $request->boolean('is_customer_visible'),
        ]);
        $note->load('user');

        ActivityLog::log('order.note_added', $order, 'Note added by ' . auth()->user()->name);

        return response()->json([
            'success' => true,
            'note'    => [
                'id'                   => $note->id,
                'note_text'            => $note->note_text,
                'is_customer_visible'  => $note->is_customer_visible,
                'user_name'            => $note->user->name,
                'user_initial'         => strtoupper(substr($note->user->name, 0, 1)),
                'time_ago'             => $note->created_at->diffForHumans(),
            ],
        ]);
    }

    public function deleteNote(OrderNote $note)
    {
        $note->delete();
        return response()->json(['success' => true]);
    }

    public function create()
    {
        $admins    = \App\Models\User::orderBy('name')->get();
        $districts = [
            'Bagerhat','Bandarban','Barguna','Barishal','Bhola','Bogura','Brahmanbaria',
            'Chandpur','Chapai Nawabganj','Chattogram','Chuadanga','Cox\'s Bazar','Cumilla',
            'Dhaka','Dinajpur','Faridpur','Feni','Gaibandha','Gazipur','Gopalganj',
            'Habiganj','Jamalpur','Jashore','Jhalokati','Jhenaidah','Joypurhat',
            'Khagrachari','Khulna','Kishoreganj','Kurigram','Kushtia','Lakshmipur',
            'Lalmonirhat','Madaripur','Magura','Manikganj','Meherpur','Moulvibazar',
            'Munshiganj','Mymensingh','Naogaon','Narail','Narayanganj','Narsingdi',
            'Natore','Netrokona','Nilphamari','Noakhali','Pabna','Panchagarh',
            'Patuakhali','Pirojpur','Rajbari','Rajshahi','Rangamati','Rangpur',
            'Satkhira','Shariatpur','Sherpur','Sirajganj','Sunamganj','Sylhet',
            'Tangail','Thakurgaon',
        ];
        return view('admin.orders.create', compact('admins', 'districts'));
    }

    public function store(Request $request)
    {
        $shippingName  = $request->full_name ?? $request->shipping_name;
        $shippingPhone = $request->primary_mobile ?? $request->shipping_phone;

        $request->merge([
            'shipping_name'  => $shippingName,
            'shipping_phone' => $shippingPhone,
        ]);

        $request->validate([
            'shipping_name'      => 'required|string|max:255',
            'shipping_phone'     => 'required|string|max:20',
            'payment_method'     => 'required|in:cod,bkash,nagad,rocket,card',
            'items'              => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity'   => 'nullable|integer|min:1',
            'items.*.qty'        => 'nullable|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        $subtotal      = collect($request->items)->sum(fn($i) => ($i['unit_price'] ?? 0) * (($i['qty'] ?? $i['quantity']) ?? 1));
        $discountType  = $request->discount_type ?? 'fixed';
        $discountValue = (float)($request->discount_value ?? $request->discount ?? 0);
        $discount      = $discountType === 'percent' ? ($subtotal * $discountValue / 100) : $discountValue;
        $shipping      = (float)($request->shipping_charge ?? 0);
        $total         = max(0, $subtotal - $discount + $shipping);

        $order = Order::create([
            'shipping_name'     => $shippingName,
            'shipping_phone'    => $shippingPhone,
            'alt_phone'         => $request->alternative_mobile ?? $request->alt_phone,
            'email'             => $request->email,
            'shipping_division' => $request->shipping_division,
            'shipping_district' => $request->district ?? $request->shipping_district,
            'shipping_area'     => $request->shipping_area,
            'shipping_address'  => $request->address ?? $request->shipping_address,
            'subtotal'          => $subtotal,
            'discount'          => $discount,
            'discount_type'     => $discountType,
            'shipping_charge'   => $shipping,
            'courier_charge'    => (float)($request->courier_charge ?? 0),
            'total'             => $total,
            'paid_amount'       => (float)($request->paid_amount ?? 0),
            'payment_method'    => $request->payment_method,
            'payment_status'    => $request->payment_status ?? 'unpaid',
            'status'            => $request->order_status ?? 'pending',
            'source'            => $request->source_website ?? $request->source ?? 'manual',
            'source_website'    => $request->source_website,
            'notes'             => $request->notes,
            'courier_note'      => $request->courier_note,
            'packing_note'      => $request->packing_note,
            'order_tag'         => $request->order_tag,
            'customer_tag'      => $request->customer_tag,
            'reference_no'      => $request->reference_no,
            'skip_followup'     => $request->boolean('skip_followup'),
            'assigned_to'       => $request->assigned_to ?? auth()->id(),
        ]);

        foreach ($request->items as $item) {
            $product  = Product::find($item['product_id']);
            $qty      = (int)($item['qty'] ?? $item['quantity'] ?? 1);
            $price    = (float)($item['unit_price'] ?? 0);
            $rowDisc  = (float)($item['discount'] ?? 0);
            OrderItem::create([
                'order_id'     => $order->id,
                'product_id'   => $item['product_id'],
                'variant_id'   => $item['variant_id'] ?? null,
                'product_name' => $product?->name ?? ($item['product_name'] ?? ''),
                'variant_info' => $item['variant'] ?? $item['variant_info'] ?? null,
                'qty'          => $qty,
                'unit_price'   => $price,
                'discount'     => $rowDisc,
                'total'        => ($price * $qty) - $rowDisc,
            ]);
        }

        // Auto-generate invoice
        try {
            \App\Models\Invoice::create([
                'order_id'       => $order->id,
                'invoice_number' => \App\Models\Invoice::generateNumber(),
                'subtotal'       => $subtotal,
                'discount'       => $discount,
                'shipping'       => $shipping,
                'total'          => $total,
                'status'         => 'draft',
            ]);
        } catch (\Exception $e) {
            \Log::error('Auto-invoice creation failed for order ' . $order->id . ': ' . $e->getMessage());
        }

        ActivityLog::log('order.created', $order, 'Manual order created by ' . auth()->user()->name);

        return redirect()->route('admin.orders.show', $order)->with('success', 'Order created successfully.');
    }

    public function incomplete(Request $request)
    {
        $query = Order::with(['user', 'items', 'shipment'])
            ->whereIn('status', ['pending', 'confirmed', 'processing'])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('order_number', 'like', "%{$s}%")
                  ->orWhere('shipping_phone', 'like', "%{$s}%");
            });
        }

        $orders = $query->paginate(20)->withQueryString();

        return view('admin.orders.incomplete', compact('orders'));
    }

    public function show(Order $order)
    {
        $order->load([
            'items.product.images',
            'items.product.variants',
            'items.variant',
            'user',
            'coupon',
            'shipment',
            'orderNotes.user',
            'activityLogs.user',
            'orderReturn',
            'assignedEmployee',
        ]);

        $activeCouriers = CourierSetting::where('is_active', true)->get();
        $statusSteps    = ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'completed'];
        $currentStep    = array_search($order->status, $statusSteps);
        if ($currentStep === false) $currentStep = -1;

        $admins    = \App\Models\User::orderBy('name')->get();
        $districts = [
            'Bagerhat','Bandarban','Barguna','Barishal','Bhola','Bogura','Brahmanbaria',
            'Chandpur','Chapai Nawabganj','Chattogram','Chuadanga','Cox\'s Bazar','Cumilla',
            'Dhaka','Dinajpur','Faridpur','Feni','Gaibandha','Gazipur','Gopalganj',
            'Habiganj','Jamalpur','Jashore','Jhalokati','Jhenaidah','Joypurhat',
            'Khagrachhari','Khulna','Kishoreganj','Kurigram','Kushtia','Lakshmipur',
            'Lalmonirhat','Madaripur','Magura','Manikganj','Meherpur','Moulvibazar',
            'Munshiganj','Mymensingh','Naogaon','Narail','Narayanganj','Narsingdi',
            'Natore','Netrokona','Nilphamari','Noakhali','Pabna','Panchagarh',
            'Patuakhali','Pirojpur','Rajbari','Rajshahi','Rangamati','Rangpur',
            'Satkhira','Shariatpur','Sherpur','Sirajganj','Sunamganj','Sylhet',
            'Tangail','Thakurgaon',
        ];

        // Auto-fill reference_no from WooCommerce order ID if not set
        if (empty($order->reference_no) && ! empty($order->source_order_id)) {
            $order->reference_no = $order->source_order_id;
        }

        // Normalize shipping_district to canonical casing so the select auto-selects
        if (! empty($order->shipping_district)) {
            foreach ($districts as $d) {
                if (strtolower(trim($order->shipping_district)) === strtolower($d)) {
                    $order->shipping_district = $d;
                    break;
                }
            }
        }

        return view('admin.orders.show', compact(
            'order', 'activeCouriers', 'statusSteps', 'currentStep',
            'admins', 'districts'
        ));
    }

    public function updateStatus(Request $request, Order $order)
    {
        $request->validate([
            'status' => 'required|in:' . implode(',', self::ALL_STATUSES),
        ]);

        $old = $order->status;
        $order->update(['status' => $request->status]);

        ActivityLog::log(
            'order.status_changed',
            $order,
            "Status changed from {$old} to {$request->status}",
            ['old' => $old, 'new' => $request->status]
        );

        return back()->with('success', 'Status updated to ' . ucwords(str_replace('_', ' ', $request->status)));
    }

    public function addItem(Request $request, Order $order)
    {
        $request->validate([
            'product_id'   => 'required|exists:products,id',
            'product_name' => 'required|string|max:255',
            'qty'          => 'required|integer|min:1',
            'unit_price'   => 'required|numeric|min:0',
            'total'        => 'required|numeric|min:0',
        ]);

        OrderItem::create([
            'order_id'     => $order->id,
            'product_id'   => $request->product_id,
            'variant_id'   => $request->variant_id ?? null,
            'product_name' => $request->product_name,
            'qty'          => $request->qty,
            'unit_price'   => $request->unit_price,
            'total'        => $request->total,
        ]);

        $newSubtotal = $order->items()->sum('total');
        $order->update(['subtotal' => $newSubtotal, 'total' => $newSubtotal - $order->discount + $order->shipping_charge]);

        return response()->json(['success' => true]);
    }

    public function removeItem(OrderItem $item)
    {
        $order = $item->order;
        $item->delete();
        $newSubtotal = $order->items()->sum('total');
        $order->update(['subtotal' => $newSubtotal, 'total' => $newSubtotal - $order->discount + $order->shipping_charge]);

        return response()->json(['success' => true]);
    }

    public function update(Request $request, Order $order)
    {
        $request->validate([
            'full_name'         => 'required|string|max:255',
            'primary_mobile'    => 'required|string|max:20',
            'alternative_mobile'=> 'nullable|string|max:20',
            'email'             => 'nullable|email|max:255',
            'address'           => 'nullable|string|max:500',
            'district'          => 'nullable|string|max:100',
            'order_status'      => 'required|in:' . implode(',', self::ALL_STATUSES),
            'payment_method'    => 'nullable|string|max:50',
            'discount_value'    => 'nullable|numeric|min:0',
            'discount_type'     => 'nullable|in:fixed,percent',
            'shipping_charge'   => 'nullable|numeric|min:0',
            'paid_amount'       => 'nullable|numeric|min:0',
            'courier_charge'    => 'nullable|numeric|min:0',
            'courier_note'      => 'nullable|string|max:500',
            'packing_note'      => 'nullable|string|max:500',
            'order_tag'         => 'nullable|string|max:50',
            'customer_tag'      => 'nullable|string|max:50',
            'assigned_to'       => 'nullable|exists:users,id',
            'reference_no'      => 'nullable|string|max:100',
            'source_website'    => 'nullable|string|max:255',
            'skip_followup'     => 'nullable|boolean',
            'items'             => 'nullable|array',
        ]);

        $oldData = $order->toArray();

        // Recalculate totals
        $subtotal      = (float) ($request->subtotal ?? $order->subtotal);
        $discountType  = $request->discount_type ?? 'fixed';
        $discountVal   = (float) ($request->discount_value ?? 0);
        $discountAmt   = $discountType === 'percent'
            ? round($subtotal * $discountVal / 100, 2)
            : $discountVal;
        $shipping      = (float) ($request->shipping_charge ?? $order->shipping_charge);
        $grandTotal    = max(0, $subtotal - $discountAmt + $shipping);
        $paidAmount    = (float) ($request->paid_amount ?? $order->paid_amount ?? 0);

        // Update order items if submitted
        if ($request->has('items') && is_array($request->items)) {
            foreach ($request->items as $itemData) {
                if (!empty($itemData['id'])) {
                    $item = OrderItem::find($itemData['id']);
                    if ($item && $item->order_id === $order->id) {
                        $qty   = (int) ($itemData['quantity'] ?? $item->qty);
                        $price = (float) ($itemData['unit_price'] ?? $item->unit_price);
                        $disc  = (float) ($itemData['discount'] ?? 0);
                        $item->update([
                            'qty'          => $qty,
                            'unit_price'   => $price,
                            'discount'     => $disc,
                            'total'        => ($qty * $price) - $disc,
                            'variant_info' => $itemData['variant'] ?? $item->variant_info,
                        ]);
                    }
                } elseif (!empty($itemData['product_id'])) {
                    $qty   = (int) ($itemData['quantity'] ?? 1);
                    $price = (float) ($itemData['unit_price'] ?? 0);
                    $disc  = (float) ($itemData['discount'] ?? 0);
                    $product = Product::find($itemData['product_id']);
                    OrderItem::create([
                        'order_id'     => $order->id,
                        'product_id'   => $itemData['product_id'],
                        'product_name' => $product?->name ?? 'Product',
                        'variant_info' => $itemData['variant'] ?? null,
                        'qty'          => $qty,
                        'unit_price'   => $price,
                        'discount'     => $disc,
                        'total'        => ($qty * $price) - $disc,
                    ]);
                }
            }
            $subtotal = $order->items()->sum('total');
            $discountAmt = $discountType === 'percent'
                ? round($subtotal * $discountVal / 100, 2)
                : $discountVal;
            $grandTotal = max(0, $subtotal - $discountAmt + $shipping);
        }

        $order->update([
            'shipping_name'     => $request->full_name,
            'shipping_phone'    => $request->primary_mobile,
            'alt_phone'         => $request->alternative_mobile,
            'email'             => $request->email,
            'shipping_address'  => $request->address,
            'shipping_district' => $request->district ?? $order->shipping_district,
            'status'            => $request->order_status,
            'payment_method'    => $request->payment_method ?? $order->payment_method,
            'discount'          => $discountAmt,
            'discount_type'     => $discountType,
            'shipping_charge'   => $shipping,
            'subtotal'          => $subtotal,
            'total'             => $grandTotal,
            'paid_amount'       => $paidAmount,
            'courier_charge'    => (float) ($request->courier_charge ?? 0),
            'courier_note'      => $request->courier_note,
            'packing_note'      => $request->packing_note,
            'order_tag'         => $request->order_tag,
            'customer_tag'      => $request->customer_tag,
            'assigned_to'       => $request->assigned_to,
            'reference_no'      => $request->reference_no,
            'source_website'    => $request->source_website,
            'skip_followup'     => (bool) $request->skip_followup,
        ]);

        ActivityLog::log(
            'order_updated',
            $order,
            'Order updated by ' . auth()->user()->name,
            [],
            $oldData,
            $order->fresh()->toArray()
        );

        Cache::forget('sidebar_order_counts');

        return back()->with('success', 'Order updated successfully.');
    }

    public function submitCourier(Request $request, Order $order)
    {
        $request->validate([
            'courier'       => 'required|in:pathao,steadfast,carrybe,redx,paperfly,manual',
            'delivery_fee'  => 'nullable|numeric|min:0',
            'notes'         => 'nullable|string',
            'tracking_code' => 'nullable|string',
        ]);

        if ($request->courier === 'manual') {
            $order->shipment()->updateOrCreate(
                ['order_id' => $order->id],
                [
                    'courier_name'  => 'manual',
                    'tracking_code' => $request->tracking_code,
                    'delivery_fee'  => $request->delivery_fee ?? 0,
                    'status'        => 'assigned',
                    'notes'         => $request->notes,
                ]
            );
            $order->update(['status' => 'shipped']);
            ActivityLog::log('order.courier_assigned', $order, 'Manual courier assigned by ' . auth()->user()->name);
            return back()->with('success', 'Courier assigned manually.');
        }

        try {
            $service = CourierFactory::make($request->courier);
            $result  = $service->createShipment($order);

            $order->shipment()->updateOrCreate(
                ['order_id' => $order->id],
                [
                    'courier_name'   => $request->courier,
                    'consignment_id' => $result['consignment_id'] ?? null,
                    'tracking_code'  => $result['tracking_code'] ?? $result['tracking_id'] ?? null,
                    'delivery_fee'   => $request->delivery_fee ?? 0,
                    'status'         => 'submitted',
                    'notes'          => $request->notes,
                ]
            );

            $order->update(['status' => 'shipped']);
            ActivityLog::log('order.courier_submitted', $order,
                ucfirst($request->courier) . ' shipment created. Tracking: ' . ($result['tracking_code'] ?? 'N/A'),
                $result
            );

            return back()->with('success', 'Shipment created on ' . ucfirst($request->courier) . '.');
        } catch (\Exception $e) {
            return back()->with('error', 'Courier API error: ' . $e->getMessage());
        }
    }

    public function productSearch(Request $request, $id)
    {
        $q        = $request->get('q', '');
        $products = Product::where('status', 'active')
            ->with(['variants', 'images'])
            ->when($q, fn($query) => $query->where('name', 'like', "%{$q}%")
                ->orWhere('sku', 'like', "%{$q}%"))
            ->limit(20)
            ->get()
            ->map(fn($p) => [
                'id'        => $p->id,
                'name'      => $p->name,
                'image_url' => $p->images->where('is_primary', true)->first()
                    ? asset('storage/' . $p->images->where('is_primary', true)->first()->image_path)
                    : ($p->images->first()
                        ? asset('storage/' . $p->images->first()->image_path)
                        : asset('images/no-image.png')),
                'stock'     => $p->variants->sum('stock') ?? ($p->stock ?? 0),
                'variants'  => $p->variants->map(fn($v) => [
                    'id'    => $v->id,
                    'name'  => trim(($v->size ?? '') . ' ' . ($v->color ?? '')),
                    'price' => $v->price ?? $p->sale_price ?? $p->price,
                    'stock' => $v->stock ?? 0,
                ]),
            ]);

        return response()->json($products);
    }

    public function searchProducts(Request $request)
    {
        $q = $request->get('q', '');
        $products = Product::where('status', 'active')
            ->with(['variants', 'images'])
            ->where(function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                      ->orWhere('sku', 'like', "%{$q}%");
            })
            ->limit(10)
            ->get()
            ->map(fn($p) => [
                'id'            => $p->id,
                'name'          => $p->name,
                'price'         => $p->price,
                'sale_price'    => $p->sale_price,
                'current_price' => $p->sale_price ?? $p->price,
                'primary_image' => $p->images->where('is_primary', true)->first()?->image_path
                                   ?? $p->images->first()?->image_path,
                'variants'      => $p->variants->map(fn($v) => [
                    'id'    => $v->id,
                    'label' => trim(($v->size ?? '') . ' ' . ($v->color ?? '')),
                    'stock' => $v->stock ?? 0,
                    'price' => $v->price ?? $p->sale_price ?? $p->price,
                ]),
            ]);

        return response()->json($products);
    }

    public function searchCustomer(Request $request)
    {
        $phone = $request->get('phone', '');
        $order = Order::where('shipping_phone', $phone)
            ->with('user')
            ->latest()
            ->first();

        if (!$order) {
            return response()->json(['found' => false]);
        }

        return response()->json([
            'found'            => true,
            'name'             => $order->shipping_name,
            'division'         => $order->shipping_division ?? '',
            'district'         => $order->shipping_district ?? '',
            'area'             => $order->shipping_area ?? '',
            'address'          => $order->shipping_address ?? '',
            'total_orders'     => Order::where('shipping_phone', $phone)->count(),
        ]);
    }

    public function bulkStatus(Request $request)
    {
        $request->validate(['ids' => 'required|array', 'status' => 'required|string']);
        Order::whereIn('id', $request->ids)->update(['status' => $request->status]);
        Cache::forget('sidebar_order_counts');
        return response()->json(['success' => true]);
    }

    public function bulkCourier(Request $request)
    {
        $request->validate(['ids' => 'required|array', 'courier' => 'required|string']);
        foreach ($request->ids as $id) {
            $order = Order::find($id);
            if ($order) {
                CourierShipment::updateOrCreate(
                    ['order_id' => $order->id],
                    ['courier_name' => $request->courier, 'status' => 'assigned']
                );
                $order->update(['status' => 'shipped']);
            }
        }
        return response()->json(['success' => true]);
    }

    public function bulkTag(Request $request)
    {
        $request->validate(['ids' => 'required|array', 'tag' => 'required|string']);
        Order::whereIn('id', $request->ids)->update(['order_tag' => $request->tag]);
        return response()->json(['success' => true]);
    }

    public function exportCsv(Request $request)
    {
        $ids    = $request->input('ids', []);
        $orders = Order::with(['items.product'])->whereIn('id', $ids)->get();

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="orders-' . date('Y-m-d') . '.csv"',
        ];

        $callback = function () use ($orders) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Order#', 'Customer', 'Phone', 'Address', 'Items', 'Total', 'Payment', 'Status', 'Date']);
            foreach ($orders as $order) {
                fputcsv($file, [
                    $order->order_number,
                    $order->shipping_name,
                    $order->shipping_phone,
                    $order->shipping_district . ', ' . $order->shipping_area,
                    $order->items->count() . ' items',
                    $order->total,
                    $order->payment_method,
                    $order->status,
                    $order->created_at->format('Y-m-d H:i'),
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function bulkPrint(Request $request)
    {
        $ids    = $request->input('ids', []);
        $orders = Order::with(['items.product', 'items.variant'])->whereIn('id', $ids)->get();
        return view('admin.orders.bulk-print', compact('orders'));
    }

    public function reOrder($id)
    {
        $original = Order::with('items')->findOrFail($id);

        $newOrder = $original->replicate();
        $newOrder->status         = 'pending';
        $newOrder->payment_status = 'unpaid';
        $newOrder->paid_amount    = 0;
        $newOrder->reference_no   = null;
        $newOrder->source_order_id = null;
        $newOrder->order_number   = null; // boot() will generate a fresh one
        $newOrder->created_at     = now();
        $newOrder->updated_at     = now();
        $newOrder->save();

        foreach ($original->items as $item) {
            $newItem = $item->replicate();
            $newItem->order_id = $newOrder->id;
            $newItem->save();
        }

        return response()->json([
            'success'      => true,
            'redirect_url' => route('admin.orders.show', $newOrder->id),
        ]);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function applyDateFilter($query, string $column, string $range): void
    {
        [$start, $end] = $this->parseDateRange($range);
        $query->whereDate($column, '>=', $start)->whereDate($column, '<=', $end);
    }

    private function parseDateRange(string $range): array
    {
        return match ($range) {
            'today'      => [today()->toDateString(), today()->toDateString()],
            'yesterday'  => [today()->subDay()->toDateString(), today()->subDay()->toDateString()],
            'last_7'     => [today()->subDays(6)->toDateString(), today()->toDateString()],
            'last_30'    => [today()->subDays(29)->toDateString(), today()->toDateString()],
            'this_month' => [today()->startOfMonth()->toDateString(), today()->toDateString()],
            'last_month' => [
                today()->subMonthNoOverflow()->startOfMonth()->toDateString(),
                today()->subMonthNoOverflow()->endOfMonth()->toDateString(),
            ],
            'this_year'  => [today()->startOfYear()->toDateString(), today()->toDateString()],
            default      => [today()->subDays(29)->toDateString(), today()->toDateString()],
        };
    }
}
