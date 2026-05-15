<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentsController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with(['user', 'items'])
            ->latest();

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }
        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('order_number', 'like', "%{$s}%")
                  ->orWhere('shipping_phone', 'like', "%{$s}%")
                  ->orWhere('shipping_name', 'like', "%{$s}%");
            });
        }

        $summary = Order::selectRaw('
            payment_method,
            payment_status,
            count(*) as order_count,
            sum(total) as total_amount
        ')
        ->groupBy('payment_method', 'payment_status')
        ->get()
        ->groupBy('payment_method');

        $unpaidTotal  = Order::where('payment_status', 'unpaid')->sum('total');
        $paidTotal    = Order::where('payment_status', 'paid')->sum('total');
        $unpaidCount  = Order::where('payment_status', 'unpaid')->count();
        $codPending   = Order::where('payment_method', 'cod')->where('payment_status', 'unpaid')->count();

        $orders = $query->paginate(25)->withQueryString();

        return view('admin.payments.index', compact(
            'orders', 'summary', 'unpaidTotal', 'paidTotal', 'unpaidCount', 'codPending'
        ));
    }

    public function markPaid(Request $request, Order $order)
    {
        $order->update(['payment_status' => 'paid']);
        return back()->with('success', 'Order #' . $order->order_number . ' marked as paid.');
    }

    public function markUnpaid(Request $request, Order $order)
    {
        $order->update(['payment_status' => 'unpaid']);
        return back()->with('success', 'Order #' . $order->order_number . ' marked as unpaid.');
    }

    public function bulkMarkPaid(Request $request)
    {
        $request->validate(['ids' => 'required|array', 'ids.*' => 'integer']);
        Order::whereIn('id', $request->ids)->update(['payment_status' => 'paid']);
        return back()->with('success', count($request->ids) . ' orders marked as paid.');
    }
}
