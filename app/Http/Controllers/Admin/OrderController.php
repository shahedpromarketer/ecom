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
        'pending', 'confirmed', 'processing', 'hold', 'hold_followup',
        'shipped', 'delivered', 'completed', 'cancelled', 'pending_return',
        'returned', 'damage', 'hand_delivery', 'hand_delivery_completed',
        'others', 'return_collection', 'courier_payment_validate',
    ];

    public function index(Request $request)
    {
        // New store orders arrive by themselves (plugin push, and the orders:pull-new job every
        // few seconds), so opening this page never waits on a store's server.

        $perPage = in_array((int) $request->get('per_page', 10), [10,25,50,100,150,200,250,300,500,1000,1500,2000])
            ? (int) $request->get('per_page', 10) : 10;

        $tenantId = auth()->user()->tenant_id;
        $isSuperAdmin = auth()->user()->is_super_admin;

        $query = Order::with([
            'items.product' => fn($q) => $q->with(['images' => fn($q) => $q->where('is_primary', true)->limit(1)]),
            'assignedTo',
            'shipment',
            'latestOrderNote',
        ])->withCount('orderNotes');
        if (!$isSuperAdmin) {
            $query->where('tenant_id', $tenantId);
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
            // For shipped orders sort by updated_at so recently submitted orders appear first
            $query->latest($request->status === 'shipped' ? 'updated_at' : 'created_at');
        } else {
            $query->latest();
        }

        // Flag filters
        if ($request->filled('cod_changed')) {
            $query->where('cod_changed', true);
        }
        if ($request->filled('duplicates')) {
            $query->where('is_duplicate', true);
        }

        // ID-only search
        if ($request->filled('id_search')) {
            $query->where('id', $request->id_search);
        }

        // Search
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('id', 'like', "%{$s}%")
                  ->orWhere('order_number', 'like', "%{$s}%")
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

        // Responsible staff
        if ($request->filled('assigned_to')) {
            $request->assigned_to === 'none'
                ? $query->whereNull('assigned_to')
                : $query->where('assigned_to', (int) $request->assigned_to);
        }

        // Exclude: everything except these statuses / sources
        $excludeStatus = array_filter((array) $request->input('exclude_status', []));
        if ($excludeStatus) {
            $query->whereNotIn('status', $excludeStatus);
        }
        $excludeSource = array_filter((array) $request->input('exclude_source', []));
        if ($excludeSource) {
            $query->where(fn ($q) => $q->whereNull('source')->orWhereNotIn('source', $excludeSource));
        }

        // Customer: first-time vs repeat, and their delivery success rate (from the customers table)
        if ($request->filled('customer_type')) {
            $query->whereHas('customer', fn ($q) => $request->customer_type === 'repeat'
                ? $q->where('total_orders', '>=', 2)
                : $q->where('total_orders', '<=', 1));
        }
        if ($request->filled('customer_success')) {
            $finished = '(delivered_orders + cancelled_orders + returned_orders)';
            $query->whereHas('customer', fn ($q) => match ($request->customer_success) {
                'high' => $q->whereRaw("$finished > 0 AND delivered_orders * 100 >= 80 * $finished"),
                'mid'  => $q->whereRaw("$finished > 0 AND delivered_orders * 100 >= 50 * $finished AND delivered_orders * 100 < 80 * $finished"),
                'low'  => $q->whereRaw("$finished > 0 AND delivered_orders * 100 < 50 * $finished"),
                'none' => $q->whereRaw("$finished = 0"),
                default => $q,
            });
        }

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

        // Batch-calculate customer stats per phone to avoid N+1 queries in the view
        $phones = $orders->pluck('shipping_phone')->filter()->unique()->values()->toArray();
        if ($phones) {
            $statsBase     = $isSuperAdmin ? Order::query() : Order::where('tenant_id', $tenantId);
            $phoneTotals   = (clone $statsBase)->whereIn('shipping_phone', $phones)
                ->select('shipping_phone', DB::raw('COUNT(*) as cnt'))
                ->groupBy('shipping_phone')
                ->pluck('cnt', 'shipping_phone');
            $phoneSuccess  = (clone $statsBase)->whereIn('shipping_phone', $phones)
                ->whereIn('status', ['delivered', 'completed'])
                ->select('shipping_phone', DB::raw('COUNT(*) as cnt'))
                ->groupBy('shipping_phone')
                ->pluck('cnt', 'shipping_phone');
            $phoneFailed   = (clone $statsBase)->whereIn('shipping_phone', $phones)
                ->whereIn('status', ['cancelled', 'failed', 'returned'])
                ->select('shipping_phone', DB::raw('COUNT(*) as cnt'))
                ->groupBy('shipping_phone')
                ->pluck('cnt', 'shipping_phone');
        } else {
            $phoneTotals  = collect();
            $phoneSuccess = collect();
            $phoneFailed  = collect();
        }

        $categories     = Category::where('is_active', true)->orderBy('name')->get();
        $baseQuery      = $isSuperAdmin ? Order::query() : Order::where('tenant_id', $tenantId);
        $districts      = (clone $baseQuery)->whereNotNull('shipping_district')
            ->distinct()->pluck('shipping_district')->filter()->sort()->values();
        $sources        = (clone $baseQuery)->whereNotNull('source')
            ->distinct()->pluck('source')->filter()->sort()->values();
        $activeCouriers = CourierSetting::all();
        $staffList      = \App\Models\User::inCurrentTenant()->whereIn('role', ['admin', 'staff'])->orderBy('name')->get(['id', 'name']);
        $allStatuses    = self::ALL_STATUSES;

        return view('admin.orders.index', compact(
            'orders', 'categories', 'districts', 'sources', 'perPage', 'activeCouriers',
            'phoneTotals', 'phoneSuccess', 'phoneFailed', 'staffList', 'allStatuses'
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
        $admins    = \App\Models\User::inCurrentTenant()->orderBy('name')->get();
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
            'items.*.product_id' => ['required', \App\Support\Tenancy::exists('products')],
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

        if (in_array($request->order_status, Order::COURIER_STAGE, true)) {
            return back()->withInput()->with('error', 'A new order can\'t go straight to the courier: save it as confirmed first, then submit it to the courier.');
        }

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
            'tenant_id'         => auth()->user()->tenant_id,
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
                'tenant_id'      => auth()->user()->tenant_id,
            ]);
        } catch (\Exception $e) {
            \Log::error('Auto-invoice creation failed for order ' . $order->id . ': ' . $e->getMessage());
        }

        ActivityLog::log('order.created', $order, 'Manual order created by ' . auth()->user()->name);

        return redirect()->route('admin.orders.show', $order)->with('success', 'Order created successfully.');
    }

    public function incomplete(Request $request)
    {
        $incTenantId = auth()->user()->tenant_id;
        $incIsSuperAdmin = auth()->user()->is_super_admin;
        $query = Order::with(['user', 'items', 'shipment'])
            ->whereIn('status', ['pending', 'confirmed', 'processing'])
            ->latest();
        if (!$incIsSuperAdmin) {
            $query->where('tenant_id', $incTenantId);
        }

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

        $courierAccounts = CourierSetting::orderByDesc('is_active')->orderBy('courier')->orderByDesc('is_default')->get();
        $allCourierKeys  = array_keys(CourierSetting::COURIERS);
        $courierSettings = CourierSetting::whereIn('courier', $allCourierKeys)->get()->keyBy('courier');
        $activeCouriers  = collect($allCourierKeys)->map(fn($key) => (object)[
            'courier'   => $key,
            'is_active' => $courierSettings->get($key)?->is_active ?? false,
        ]);
        $statusSteps    = ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'completed'];
        $currentStep    = array_search($order->status, $statusSteps);
        if ($currentStep === false) $currentStep = -1;

        $admins    = \App\Models\User::inCurrentTenant()->orderBy('name')->get();
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
            'order', 'activeCouriers', 'courierAccounts', 'statusSteps', 'currentStep',
            'admins', 'districts'
        ));
    }

    /** Moves the order to the Recycle Bin (it can be restored from there). */
    public function destroy(Order $order)
    {
        $order->delete();
        ActivityLog::log('order.deleted', $order, 'Order #' . $order->order_number . ' moved to the Recycle Bin by ' . auth()->user()->name);

        return redirect()->route('admin.orders.index')->with('success', 'Order #' . $order->order_number . ' moved to the Recycle Bin.');
    }

    public function updateStatus(Request $request, Order $order)
    {
        $request->validate([
            'status' => 'required|in:' . implode(',', self::ALL_STATUSES),
        ]);

        if ($why = $order->courierBlockReason($request->status)) {
            return back()->with('error', $why);
        }

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
            'product_id'   => ['required', \App\Support\Tenancy::exists('products')],
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
            'discount'     => 0,
            'total'        => $request->unit_price * $request->qty,
        ]);

        $newSubtotal = $order->items()->sum(DB::raw('(unit_price * qty) - COALESCE(discount, 0)'));
        $order->update(['subtotal' => $newSubtotal, 'total' => max(0, $newSubtotal - $order->discount + $order->shipping_charge)]);

        return response()->json(['success' => true]);
    }

    public function removeItem(OrderItem $item)
    {
        $order = $item->order;
        $item->delete();
        $newSubtotal = $order->items()->sum(DB::raw('(unit_price * qty) - COALESCE(discount, 0)'));
        $order->update(['subtotal' => $newSubtotal, 'total' => max(0, $newSubtotal - $order->discount + $order->shipping_charge)]);

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
            'assigned_to'       => ['nullable', \App\Support\Tenancy::exists('users')],
            'reference_no'      => 'nullable|string|max:100',
            'source_website'    => 'nullable|string|max:255',
            'skip_followup'     => 'nullable|boolean',
            'items'             => 'nullable|array',
        ]);

        if ($request->order_status !== $order->status && ($why = $order->courierBlockReason($request->order_status))) {
            return back()->withInput()->with('error', $why);
        }

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

        $this->clearSidebarCache();

        return back()->with('success', 'Order updated successfully.');
    }

    /** "a12" = courier account 12; a plain courier name = that courier's default account. */
    private function courierAccount(string $value): ?CourierSetting
    {
        if (preg_match('/^a(\d+)$/', $value, $m)) {
            return CourierSetting::where('is_active', true)->find((int) $m[1]);
        }

        return CourierSetting::defaultFor($value);
    }

    public function submitCourier(Request $request, Order $order)
    {
        $request->validate([
            'courier'      => 'required|string|max:30',
            'delivery_fee' => 'nullable|numeric|min:0',
            'notes'        => 'nullable|string|max:500',
        ]);

        if ($why = $order->courierBlockReason('shipped')) {
            return back()->with('error', $why);
        }

        if ($request->courier === 'manual') {
            $order->shipment()->updateOrCreate(
                ['order_id' => $order->id],
                [
                    'courier_account_id' => null,
                    'courier_name'   => 'manual',
                    'tracking_code'  => $request->tracking_code,
                    'consignment_id' => $request->consignment_id,
                    'delivery_fee'   => $request->delivery_fee ?? 0,
                    'status'         => 'assigned',
                    'notes'          => $request->notes,
                ]
            );
            $order->update(['status' => 'shipped']);
            ActivityLog::log('order.courier_assigned', $order, 'Manual courier assigned by ' . auth()->user()->name);
            return back()->with('success', 'Courier assigned manually.');
        }

        $account = $this->courierAccount($request->courier);
        if (! $account) {
            return back()->with('error', 'This courier account is not set up or is turned off (Settings → Courier).');
        }

        try {
            $s = \App\Services\Couriers\CourierTracker::book($order, $account, $request->filled('delivery_fee') ? (float) $request->delivery_fee : null, $request->notes);

            return back()->with('success', 'Booked with ' . $account->label() . '. Tracking: ' . $s->tracking_code);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /** The customer's delivery history: each courier's own (shop's logins) + all shops on Ecom Pilot. */
    public function customerCheck(Request $request, Order $order)
    {
        $tenantId = (int) ($order->tenant_id ?? auth()->user()->tenant_id);
        $check = \App\Services\CustomerCheck\CustomerCheck::for($tenantId, (string) $order->shipping_phone, $request->boolean('fresh'));

        return view('admin.orders._customer-check', ['check' => $check]);
    }

    public function trackCourier(Order $order)
    {
        $shipment = $order->shipment;
        if (! $shipment || ! $shipment->consignment_id) {
            return response()->json(['error' => 'This order has no courier booking.'], 404);
        }

        // Ask the courier now (quietly keep what we have if it does not answer).
        if ($shipment->account && $shipment->account->is_active && ! $shipment->isFinal()) {
            try {
                if ($u = \App\Services\Couriers\CourierFactory::for($shipment->account)->track($shipment)) {
                    \App\Services\Couriers\CourierTracker::apply($shipment, $u, 'sync');
                    $shipment->refresh();
                }
            } catch (\Throwable $e) {
                // shown from our own history below
            }
        }

        return response()->json([
            'courier'        => $shipment->account?->label() ?? ucfirst($shipment->courier_name),
            'tracking_code'  => $shipment->tracking_code,
            'consignment_id' => $shipment->consignment_id,
            'status'         => $shipment->statusLabel(),
            'events'         => $shipment->events()->get()->map(fn ($e) => [
                'date'         => $e->happened_at?->format('d M Y, h:i A'),
                'note'         => trim((\App\Models\CourierShipment::STATUSES[$e->status] ?? $e->status) . ($e->message ? ' — ' . $e->message : '') . ($e->courier_status && $e->courier_status !== $e->status ? " ({$e->courier_status})" : '')),
                'delivery_man' => $e->source,
            ])->all(),
        ]);
    }
    public function updateTracking(Request $request, Order $order)
    {
        $request->validate([
            'tracking_code'  => 'required|string',
            'consignment_id' => 'nullable|string',
        ]);

        if (!$order->shipment) {
            return response()->json(['error' => 'No shipment found.'], 404);
        }

        $order->shipment->update([
            'tracking_code'  => $request->tracking_code,
            'consignment_id' => $request->consignment_id ?? $order->shipment->consignment_id,
        ]);

        return response()->json(['success' => true, 'tracking_code' => $request->tracking_code]);
    }

    public function productSearch(Request $request, $id)
    {
        $q        = $request->get('q', '');
        $products = Product::where('status', 'active')
            ->with(['variants', 'images'])
            ->when(!auth()->user()->is_super_admin, fn($q2) => $q2->where('tenant_id', auth()->user()->tenant_id))
            ->when($q, fn($query) => $query->where('name', 'like', "%{$q}%")
                ->orWhere('sku', 'like', "%{$q}%"))
            ->limit(20)
            ->get()
            ->map(fn($p) => [
                'id'        => $p->id,
                'name'      => $p->name,
                'price'     => (float) ($p->sale_price ?? $p->price ?? 0),
                'image_url' => $p->images->where('is_primary', true)->first()
                    ? asset('storage/' . $p->images->where('is_primary', true)->first()->image_path)
                    : ($p->images->first()
                        ? asset('storage/' . $p->images->first()->image_path)
                        : asset('images/no-image.png')),
                'stock'     => $p->variants->sum('stock') ?? 0,
                'variants'  => $p->variants->map(fn($v) => [
                    'id'    => $v->id,
                    'name'  => trim(($v->size ?? '') . ' ' . ($v->color ?? '')),
                    'price' => (float) ($v->price ?? $p->sale_price ?? $p->price ?? 0),
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
            ->when(!auth()->user()->is_super_admin, fn($q2) => $q2->where('tenant_id', auth()->user()->tenant_id))
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
                'primary_image' => $p->images->where('is_primary', true)->first()?->url
                                   ?? $p->images->first()?->url,
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
        $phone    = $request->get('phone', '');
        $custBase = Order::where('shipping_phone', $phone);
        if (!auth()->user()->is_super_admin) { $custBase->where('tenant_id', auth()->user()->tenant_id); }
        $order = (clone $custBase)->with('user')->latest()->first();

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
            'total_orders'     => $custBase->count(),
        ]);
    }

    public function quickView(Order $order): \Illuminate\Http\JsonResponse
    {
        if (!auth()->user()->is_super_admin && $order->tenant_id !== auth()->user()->tenant_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $order->load(['items.product.images', 'orderNotes.user', 'shipment', 'assignedTo']);

        $phone = $order->shipping_phone;
        $baseQ = Order::where('shipping_phone', $phone);
        $customerTotal     = $baseQ->count();
        $customerCompleted = (clone $baseQ)->whereIn('status', ['delivered', 'completed'])->count();

        $admins = \App\Models\User::inCurrentTenant()->orderBy('name')->get(['id', 'name']);

        $allNotes = $order->orderNotes->sortByDesc('created_at');

        return response()->json([
            'id'                => $order->id,
            'order_number'      => $order->order_number ?? $order->id,
            'status'            => $order->status,
            'shipping_name'     => $order->shipping_name,
            'shipping_phone'    => $order->shipping_phone,
            'alt_phone'         => $order->alt_phone,
            'email'             => $order->email,
            'shipping_address'  => $order->shipping_address,
            'shipping_district' => $order->shipping_district,
            'payment_method'    => $order->payment_method,
            'subtotal'          => (float) $order->subtotal,
            'discount'          => (float) $order->discount,
            'shipping_charge'   => (float) $order->shipping_charge,
            'total'             => (float) $order->total,
            'paid_amount'       => (float) $order->paid_amount,
            'due'               => max(0, (float) $order->total - (float) $order->paid_amount),
            'order_tag'         => $order->order_tag,
            'customer_tag'      => $order->customer_tag,
            'courier_note'      => $order->courier_note,
            'packing_note'      => $order->packing_note,
            'assigned_to'       => $order->assigned_to,
            'reference_no'      => $order->reference_no,
            'source'            => $order->source ?? 'Direct',
            'created_at'        => $order->created_at->format('d/m/Y h:ia'),
            'updated_at'        => $order->updated_at->format('d/m/Y h:ia'),
            'customer_total'    => $customerTotal,
            'customer_completed'=> $customerCompleted,
            'admins'            => $admins->map(fn($a) => ['id' => $a->id, 'name' => $a->name])->values(),
            'items'             => $order->items->map(fn($i) => [
                'id'          => $i->id,
                'name'        => $i->product_name ?? ($i->product?->name ?? 'Product'),
                'variant'     => $i->variant_info,
                'qty'         => (int) $i->qty,
                'unit_price'  => (float) $i->unit_price,
                'discount'    => (float) ($i->discount ?? 0),
                'subtotal'    => (float) (($i->unit_price * $i->qty) - ($i->discount ?? 0)),
                'image'       => $i->product?->images?->first()
                    ? (str_starts_with($i->product->images->first()->image_path, 'http')
                        ? $i->product->images->first()->image_path
                        : asset('storage/' . $i->product->images->first()->image_path))
                    : null,
            ]),
            'order_notes'    => $allNotes->filter(fn($n) => ($n->type ?? 'order') === 'order')->map(fn($n) => [
                'id' => $n->id, 'text' => $n->note_text,
                'user' => $n->user?->name ?? 'System',
                'created_at' => $n->created_at->format('d M Y, H:i'),
            ])->values(),
            'customer_notes' => $allNotes->filter(fn($n) => $n->type === 'customer')->map(fn($n) => [
                'id' => $n->id, 'text' => $n->note_text,
                'user' => $n->user?->name ?? 'System',
                'created_at' => $n->created_at->format('d M Y, H:i'),
            ])->values(),
            'shipment' => $order->shipment ? [
                'courier'       => ucfirst($order->shipment->courier_name),
                'status'        => $order->shipment->status,
                'tracking'      => $order->shipment->tracking_code,
                'tracking_link' => $order->shipment->tracking_link,
                'consignment'   => $order->shipment->consignment_id,
                'delivery_fee'  => (float) $order->shipment->delivery_fee,
                'submitted_at'  => $order->shipment->created_at?->format('d/m/Y h:ia'),
            ] : null,
        ]);
    }

    public function quickField(Request $request, Order $order): \Illuminate\Http\JsonResponse
    {
        if (!auth()->user()->is_super_admin && $order->tenant_id !== auth()->user()->tenant_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $field = $request->input('field');
        $value = $request->input('value');

        // Item-level field update
        if ($field === 'item_field') {
            $data   = is_array($value) ? $value : json_decode($value, true);
            $item   = OrderItem::findOrFail($data['item_id']);
            if ($item->order_id !== $order->id) abort(403);

            if ($data['field'] === 'qty')      $item->qty        = max(1, (int) $data['value']);
            if ($data['field'] === 'price')    $item->unit_price = max(0, (float) $data['value']);
            if ($data['field'] === 'discount') $item->discount   = max(0, (float) $data['value']);
            $item->total = ($item->unit_price * $item->qty) - ($item->discount ?? 0);
            $item->save();

            $subtotal = $order->items()->sum(DB::raw('(unit_price * qty) - COALESCE(discount, 0)'));
            $order->subtotal = $subtotal;
            $order->total    = max(0, $subtotal - $order->discount + $order->shipping_charge);
            $order->save();

            return response()->json([
                'success'  => true,
                'subtotal' => (float) $order->subtotal,
                'total'    => (float) $order->total,
                'due'      => max(0, (float) $order->total - (float) $order->paid_amount),
                'item_subtotal' => (float) $item->total,
            ]);
        }

        $allowed = [
            'status', 'shipping_name', 'shipping_phone', 'alt_phone', 'email',
            'shipping_address', 'shipping_district', 'payment_method', 'paid_amount',
            'shipping_charge', 'discount', 'order_tag', 'customer_tag', 'courier_note',
            'packing_note', 'assigned_to', 'reference_no',
        ];

        if (!in_array($field, $allowed)) {
            return response()->json(['error' => 'Field not allowed'], 422);
        }

        $updateData = [$field => $value];

        if (in_array($field, ['shipping_charge', 'discount', 'paid_amount'])) {
            $shipping = $field === 'shipping_charge' ? (float) $value : (float) $order->shipping_charge;
            $discount = $field === 'discount'        ? (float) $value : (float) $order->discount;
            $paid     = $field === 'paid_amount'     ? (float) $value : (float) $order->paid_amount;
            $total    = max(0, (float) $order->subtotal - $discount + $shipping);
            $updateData['total']       = $total;
            $updateData['paid_amount'] = $paid;
        }

        $order->update($updateData);

        if ($field === 'status') {
            $this->clearSidebarCache();
            ActivityLog::log('order.status_changed', $order, "Status changed to {$value}", ['new' => $value]);
        }

        $order->refresh();

        return response()->json([
            'success'  => true,
            'subtotal' => (float) $order->subtotal,
            'total'    => (float) $order->total,
            'due'      => max(0, (float) $order->total - (float) $order->paid_amount),
        ]);
    }

    public function sidebarCounts(): \Illuminate\Http\JsonResponse
    {
        $user = auth()->user();
        $query = $user->is_super_admin
            ? Order::query()
            : Order::where('tenant_id', $user->tenant_id);

        $statuses = $query->select('status', DB::raw('COUNT(*) as cnt'))
            ->groupBy('status')
            ->pluck('cnt', 'status');

        return response()->json([
            'all'            => $statuses->sum(),
            'pending'        => $statuses['pending']        ?? 0,
            'confirmed'      => $statuses['confirmed']      ?? 0,
            'processing'     => $statuses['processing']     ?? 0,
            'hold'           => $statuses['hold']           ?? 0,
            'shipped'        => $statuses['shipped']        ?? 0,
            'delivered'      => $statuses['delivered']      ?? 0,
            'completed'      => $statuses['completed']      ?? 0,
            'cancelled'      => $statuses['cancelled']      ?? 0,
            'pending_return' => $statuses['pending_return'] ?? 0,
            'returned'       => $statuses['returned']       ?? 0,
        ]);
    }

    public function bulkStatus(Request $request)
    {
        $request->validate(['ids' => 'required|array|max:500', 'status' => ['required', 'string', \Illuminate\Validation\Rule::in(self::ALL_STATUSES)]]);
        $q = Order::whereIn('id', $request->ids);
        if (!auth()->user()->is_super_admin) { $q->where('tenant_id', auth()->user()->tenant_id); }
        // One by one, so each order gets what a status change brings: wallet fee on confirming,
        // stock at the courier / back on return, customer counts and the store's status.
        $errors = [];
        foreach ($q->get() as $order) {
            if ($why = $order->courierBlockReason($request->status)) {
                $errors[] = $why;   // pending orders go to the courier only after confirming
                continue;
            }
            if ($order->status !== $request->status) {
                $order->update(['status' => $request->status]);
            }
        }
        $this->clearSidebarCache();
        return response()->json(['success' => true, 'errors' => $errors]);
    }

    public function bulkCourier(Request $request)
    {
        $request->validate(['ids' => 'required|array|max:200', 'courier' => 'required|string|max:30']);

        $account = $request->courier === 'manual' ? null : $this->courierAccount($request->courier);
        if ($request->courier !== 'manual' && ! $account) {
            return response()->json(['success' => false, 'errors' => ['This courier account is not set up or is turned off.']], 422);
        }

        $errors = [];
        $booked = 0;
        foreach (Order::whereIn('id', $request->ids)->get() as $order) {
            if ($why = $order->courierBlockReason('shipped')) {
                $errors[] = $why;
                continue;
            }
            if (! $account) {
                CourierShipment::updateOrCreate(['order_id' => $order->id], [
                    'courier_account_id' => null, 'courier_name' => 'manual',
                    'delivery_fee' => $order->shipping_charge ?? 0, 'status' => 'assigned',
                ]);
                $order->update(['status' => 'shipped']);
                $booked++;
                continue;
            }

            try {
                \App\Services\Couriers\CourierTracker::book($order, $account);
                $booked++;
            } catch (\Throwable $e) {
                // Not booked: the order stays as it was (marking it shipped would also take stock).
                $errors[] = 'Order #' . $order->order_number . ': ' . $e->getMessage();
            }
        }

        return response()->json(['success' => true, 'booked' => $booked, 'errors' => $errors]);
    }
    public function bulkTag(Request $request)
    {
        $request->validate(['ids' => 'required|array', 'tag' => 'required|string']);
        $q = Order::whereIn('id', $request->ids);
        if (!auth()->user()->is_super_admin) { $q->where('tenant_id', auth()->user()->tenant_id); }
        $q->update(['order_tag' => $request->tag]);
        return response()->json(['success' => true]);
    }

    public function exportCsv(Request $request)
    {
        $ids    = $request->input('ids', []);
        $expQ   = Order::with(['items.product', 'items.variant'])->whereIn('id', $ids);
        if (!auth()->user()->is_super_admin) { $expQ->where('tenant_id', auth()->user()->tenant_id); }
        $orders = $expQ->get();

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="orders-' . date('Y-m-d') . '.csv"',
        ];

        $callback = function () use ($orders) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM for Excel
            fputcsv($file, ['Order#', 'Customer', 'Phone', 'Alt Phone', 'Address', 'Area', 'District', 'Division', 'Products', 'Total', 'Payment', 'Status', 'Date']);
            foreach ($orders as $order) {
                $itemLines = $order->items->map(function ($item) {
                    $name    = $item->product_name ?? ($item->product->name ?? '');
                    $variant = trim($item->variant_info ?? '');
                    if (!$variant && $item->variant) {
                        $variant = trim(($item->variant->size ?? '') . ' ' . ($item->variant->color ?? ''));
                    }
                    $label = $variant ? "{$name} ({$variant})" : $name;
                    return "x{$item->qty} {$label}";
                })->implode(' | ');

                fputcsv($file, [
                    $order->order_number,
                    $order->shipping_name,
                    $order->shipping_phone,
                    $order->alt_phone ?? '',
                    $order->shipping_address ?? '',
                    $order->shipping_area ?? '',
                    $order->shipping_district ?? '',
                    $order->shipping_division ?? '',
                    $itemLines,
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
        $printQ = Order::with(['items.product', 'items.variant'])->whereIn('id', $ids);
        if (!auth()->user()->is_super_admin) { $printQ->where('tenant_id', auth()->user()->tenant_id); }
        $orders = $printQ->get();
        return view('admin.orders.bulk-print', compact('orders'));
    }

    public function searchLive(Request $request)
    {
        $q = trim($request->get('q', ''));
        if (strlen($q) < 2) {
            return response()->json([]);
        }

        $liveQ = Order::where(function ($query) use ($q) {
                $query->where('id', 'like', "%{$q}%")
                      ->orWhere('order_number', 'like', "%{$q}%")
                      ->orWhere('shipping_phone', 'like', "%{$q}%")
                      ->orWhere('shipping_name', 'like', "%{$q}%");
            });
        if (!auth()->user()->is_super_admin) { $liveQ->where('tenant_id', auth()->user()->tenant_id); }
        $orders = $liveQ->latest()
            ->limit(8)
            ->get()
            ->map(fn ($o) => [
                'id'            => $o->id,
                'customer_name' => $o->shipping_name ?? 'N/A',
                'phone'         => $o->shipping_phone ?? '',
                'status'        => $o->status,
            ]);

        return response()->json($orders);
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

    private function clearSidebarCache(): void
    {
        \App\Support\SidebarCounts::forget(auth()->user()->tenant_id);
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
