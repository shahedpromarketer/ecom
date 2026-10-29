<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\CourierSetting;
use App\Models\CourierShipment;
use App\Models\Order;
use App\Services\Couriers\CourierTracker;
use Illuminate\Http\Request;

/**
 * Warehouse scanning: pack orders, hand parcels to the courier (booking any that
 * are not booked yet) and receive returned parcels. Works with a USB barcode
 * scanner or the phone camera; codes are order numbers or courier tracking codes.
 */
class DispatchController extends Controller
{
    private const PACKABLE = ['confirmed', 'processing', 'hold'];
    private const HANDOVER = ['confirmed', 'processing', 'hold', 'shipped'];
    private const RETURNS  = ['pending_return', 'return_collection', 'shipped', 'hold'];

    public function index()
    {
        return view('admin.dispatch.index', [
            'accounts' => CourierSetting::where('is_active', true)->orderBy('courier')->orderByDesc('is_default')->get(),
            'today'    => [
                'packed'   => Order::whereDate('packed_at', today())->count(),
                'handed'   => CourierShipment::whereDate('handed_over_at', today())->count(),
                'received' => CourierShipment::whereDate('return_received_at', today())->count(),
                'waiting'  => Order::whereIn('status', ['pending_return', 'return_collection'])->count(),
            ],
        ]);
    }

    /** POST {code, mode: pack|handover|returns} → the order, and whether it can go on. */
    public function lookup(Request $request)
    {
        $data = $request->validate(['code' => 'required|string|max:100', 'mode' => 'required|in:pack,handover,returns']);
        $order = $this->find(trim($data['code']));
        if (! $order) {
            return response()->json(['ok' => false, 'message' => 'No order found for "' . $data['code'] . '".'], 404);
        }

        $view = $this->view($order);
        [$ok, $message] = match ($data['mode']) {
            'pack'     => $this->canPack($order),
            'handover' => $this->canHandOver($order),
            'returns'  => $this->canReceive($order),
        };

        return response()->json(['ok' => $ok, 'message' => $message, 'order' => $view]);
    }

    public function pack(Request $request)
    {
        $order = Order::findOrFail($request->validate(['order_id' => 'required|integer'])['order_id']);
        [$ok, $message] = $this->canPack($order);
        if (! $ok) {
            return response()->json(['ok' => false, 'message' => $message], 422);
        }

        $order->update(['packed_at' => now(), 'packed_by' => auth()->id(), 'status' => $order->status === 'confirmed' ? 'processing' : $order->status]);
        ActivityLog::log('order.packed', $order, 'Packed by ' . auth()->user()->name);

        return response()->json(['ok' => true, 'message' => '#' . $order->order_number . ' packed.', 'order' => $this->view($order->fresh())]);
    }

    /** POST {account_id, order_ids[]} — book what is not booked yet, then mark all handed over. */
    public function handover(Request $request)
    {
        $data = $request->validate(['account_id' => 'nullable|integer', 'order_ids' => 'required|array|min:1|max:300', 'order_ids.*' => 'integer']);
        $account = $data['account_id'] ? CourierSetting::where('is_active', true)->find($data['account_id']) : null;

        $results = [];
        foreach (Order::with('shipment')->whereIn('id', $data['order_ids'])->get() as $order) {
            [$ok, $message] = $this->canHandOver($order);
            if (! $ok) {
                $results[] = ['id' => $order->id, 'number' => $order->order_number, 'ok' => false, 'message' => $message];
                continue;
            }
            try {
                $s = $order->shipment;
                if (! $s || ! $s->consignment_id && $s->courier_name !== 'manual' || $s->status === 'cancelled') {
                    if (! $account) {
                        throw new \RuntimeException('Not booked yet: choose a courier account to book it.');
                    }
                    $s = CourierTracker::book($order, $account);
                }
                $s->update(['handed_over_at' => now(), 'handed_over_by' => auth()->id()]);
                $s->events()->create(['status' => $s->status, 'courier_status' => 'handed_over', 'source' => 'manual',
                    'message' => 'Handed to the courier by ' . auth()->user()->name, 'happened_at' => now()]);
                $results[] = ['id' => $order->id, 'number' => $order->order_number, 'ok' => true,
                    'message' => ucfirst($s->courier_name) . ' ' . $s->tracking_code];
            } catch (\Throwable $e) {
                $results[] = ['id' => $order->id, 'number' => $order->order_number, 'ok' => false, 'message' => $e->getMessage()];
            }
        }

        return response()->json([
            'ok'      => true,
            'done'    => collect($results)->where('ok', true)->count(),
            'results' => $results,
        ]);
    }

    public function receive(Request $request)
    {
        $data = $request->validate(['order_id' => 'required|integer', 'note' => 'nullable|string|max:300']);
        $order = Order::with('shipment')->findOrFail($data['order_id']);
        [$ok, $message] = $this->canReceive($order);
        if (! $ok) {
            return response()->json(['ok' => false, 'message' => $message], 422);
        }

        $s = $order->shipment;
        if ($s) {
            $s->update(['return_received_at' => now(), 'return_received_by' => auth()->id(), 'status' => 'returned']);
            $s->events()->create(['status' => 'returned', 'courier_status' => 'received', 'source' => 'manual',
                'message' => 'Return received by ' . auth()->user()->name . ($data['note'] ? ' — ' . $data['note'] : ''), 'happened_at' => now()]);
        }
        $order->update(['status' => 'returned']);   // puts the stock back
        ActivityLog::log('order.return_received', $order, 'Returned parcel received by ' . auth()->user()->name . ($data['note'] ? ': ' . $data['note'] : ''));

        return response()->json(['ok' => true, 'message' => '#' . $order->order_number . ' received, stock is back.', 'order' => $this->view($order->fresh('shipment'))]);
    }

    // ── Rules ──────────────────────────────────────────────────────

    private function canPack(Order $order): array
    {
        if ($order->packed_at) {
            return [false, 'Already packed ' . $order->packed_at->diffForHumans() . '.'];
        }
        if (! in_array($order->status, self::PACKABLE, true)) {
            return [false, 'This order is "' . str_replace('_', ' ', $order->status) . '". Only confirmed orders are packed.'];
        }

        return [true, 'Check the items, then press Packed.'];
    }

    private function canHandOver(Order $order): array
    {
        $s = $order->shipment;
        if ($s?->handed_over_at) {
            return [false, 'Already handed to ' . ucfirst($s->courier_name) . ' ' . $s->handed_over_at->diffForHumans() . '.'];
        }
        if (! in_array($order->status, self::HANDOVER, true)) {
            return [false, 'This order is "' . str_replace('_', ' ', $order->status) . '" — it cannot go to the courier.'];
        }

        return [true, $s && $s->consignment_id && $s->status !== 'cancelled'
            ? 'Booked with ' . ucfirst($s->courier_name) . ' (' . $s->tracking_code . ').'
            : 'Not booked yet — it will be booked with the chosen account.' . ($order->packed_at ? '' : ' (Not packed yet.)')];
    }

    private function canReceive(Order $order): array
    {
        $s = $order->shipment;
        if ($s?->return_received_at || $order->status === 'returned') {
            return [false, 'Already received as a return.'];
        }
        $courierSaysReturn = $s && in_array($s->status, ['returning', 'returned', 'cancelled', 'hold'], true);
        if (! in_array($order->status, ['pending_return', 'return_collection'], true) && ! $courierSaysReturn) {
            return [false, 'This parcel is not marked as a return (order is "' . str_replace('_', ' ', $order->status) . '").'];
        }
        if (! in_array($order->status, self::RETURNS, true)) {
            return [false, 'This order is "' . str_replace('_', ' ', $order->status) . '".'];
        }

        return [true, 'Check the parcel is complete, then press Received.'];
    }

    private function find(string $code): ?Order
    {
        $code = ltrim($code, '#');
        $order = Order::with(['items', 'shipment'])->where('order_number', $code)->first();
        if ($order) {
            return $order;
        }
        $s = CourierShipment::where('tracking_code', $code)->orWhere('consignment_id', $code)->latest('id')->first();

        return $s ? Order::with(['items', 'shipment'])->find($s->order_id) : null;
    }

    private function view(Order $order): array
    {
        $order->loadMissing(['items', 'shipment']);

        return [
            'id'      => $order->id,
            'number'  => $order->order_number,
            'status'  => $order->status,
            'name'    => $order->shipping_name,
            'phone'   => $order->shipping_phone,
            'address' => trim($order->shipping_address . ', ' . $order->shipping_district, ', '),
            'total'   => (float) $order->total,
            'cod'     => max(0, (float) $order->total - (float) $order->paid_amount),
            'note'    => $order->packing_note,
            'packed'  => $order->packed_at?->format('d M, h:i A'),
            'items'   => $order->items->map(fn ($i) => ['name' => $i->product_name, 'variant' => $i->variant_info, 'qty' => (int) $i->qty])->values(),
            'courier' => $order->shipment ? [
                'name'     => $order->shipment->account?->label() ?? ucfirst($order->shipment->courier_name),
                'tracking' => $order->shipment->tracking_code,
                'status'   => $order->shipment->statusLabel(),
            ] : null,
        ];
    }
}
