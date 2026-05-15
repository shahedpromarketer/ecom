<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Order;
use App\Models\OrderReturn;
use Illuminate\Http\Request;

class ReturnController extends Controller
{
    public function index(Request $request)
    {
        $query = OrderReturn::with(['order', 'resolvedBy'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('requested_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('requested_at', '<=', $request->date_to);
        }

        $returns = $query->paginate(25)->withQueryString();

        $stats = [
            'total'     => OrderReturn::count(),
            'pending'   => OrderReturn::where('status', 'requested')->count(),
            'approved'  => OrderReturn::where('status', 'approved')->count(),
            'completed' => OrderReturn::where('status', 'completed')->count(),
        ];

        return view('admin.returns.index', compact('returns', 'stats'));
    }

    public function store(Request $request, Order $order)
    {
        $request->validate([
            'reason'        => 'required|string|max:2000',
            'refund_amount' => 'nullable|numeric|min:0',
            'refund_method' => 'nullable|in:cod_adjustment,bkash,nagad,manual',
        ]);

        $return = OrderReturn::create([
            'order_id'      => $order->id,
            'reason'        => $request->reason,
            'refund_amount' => $request->refund_amount ?? 0,
            'refund_method' => $request->refund_method,
            'status'        => 'requested',
            'requested_at'  => now(),
        ]);

        $order->update(['status' => 'pending_return']);

        ActivityLog::log(
            'return.requested',
            $return,
            "Return requested for Order #{$order->order_number}",
            ['reason' => $request->reason]
        );

        return back()->with('success', 'Return request submitted.');
    }

    public function update(Request $request, OrderReturn $return)
    {
        $request->validate([
            'action'        => 'required|in:approve,reject,complete',
            'admin_note'    => 'nullable|string|max:1000',
            'refund_amount' => 'nullable|numeric|min:0',
            'refund_method' => 'nullable|in:cod_adjustment,bkash,nagad,manual',
        ]);

        $statusMap = [
            'approve'  => 'approved',
            'reject'   => 'rejected',
            'complete' => 'completed',
        ];

        $return->update([
            'status'        => $statusMap[$request->action],
            'admin_note'    => $request->admin_note,
            'refund_amount' => $request->refund_amount ?? $return->refund_amount,
            'refund_method' => $request->refund_method,
            'resolved_by'   => auth()->id(),
            'resolved_at'   => now(),
        ]);

        ActivityLog::log(
            'return.' . $request->action . 'd',
            $return,
            'Return ' . $request->action . 'd by ' . auth()->user()->name
        );

        return back()->with('success', 'Return ' . $request->action . 'd successfully.');
    }
}
