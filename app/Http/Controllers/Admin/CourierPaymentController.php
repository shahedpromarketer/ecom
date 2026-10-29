<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\CourierPayment;
use App\Models\CourierSetting;
use App\Models\CourierShipment;
use App\Models\Order;
use App\Services\Couriers\CourierTracker;
use Illuminate\Http\Request;

/**
 * Courier payment matching: upload the courier's payment (COD settlement) file and
 * every line is matched to our parcel — paid, amount differs, or unknown.
 * Fully matched delivered orders move to "completed".
 */
class CourierPaymentController extends Controller
{
    /** Header words → our column. First match wins, so the most specific words come first. */
    private const COLUMNS = [
        'id'        => ['consignment', 'tracking', 'parcel id', 'cid', 'invoice', 'merchant order', 'order id', 'order no', 'reference'],
        'collected' => ['collected', 'cash collection', 'cod amount', 'cod', 'amount'],
        'charge'    => ['delivery charge', 'delivery fee', 'charge', 'fee'],
    ];

    public function index()
    {
        return view('admin.courier-payments.index', [
            'accounts' => CourierSetting::orderBy('courier')->get(),
            'payments' => CourierPayment::with('uploader')->latest()->paginate(20),
            'unpaid'   => CourierShipment::whereIn('status', ['delivered', 'partial'])->whereNull('paid_at')
                ->selectRaw('courier_name, count(*) c, sum(coalesce(collected_amount, cod_amount, 0)) amount')
                ->groupBy('courier_name')->get(),
        ]);
    }

    public function show(CourierPayment $payment)
    {
        return view('admin.courier-payments.show', ['payment' => $payment]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'account_id' => 'required|integer',
            'file'       => 'required|file|max:5120|mimes:csv,txt',
            'reference'  => 'nullable|string|max:100',
        ]);
        $account = CourierSetting::findOrFail($data['account_id']);

        $rows = $this->readCsv($request->file('file')->getRealPath());
        if (count($rows) < 2) {
            return back()->with('error', 'The file is empty.');
        }
        $headers = array_map(fn ($h) => mb_strtolower(trim((string) $h)), array_shift($rows));
        $col = $this->detect($headers);
        if ($col['id'] === null) {
            return back()->with('error', 'Could not find the consignment / tracking / invoice column. Columns found: ' . implode(', ', $headers));
        }

        $report = [];
        $stats = ['matched' => 0, 'mismatched' => 0, 'unknown' => 0, 'collected' => 0.0, 'charge' => 0.0];
        foreach ($rows as $line) {
            $id = trim((string) ($line[$col['id']] ?? ''));
            if ($id === '') {
                continue;
            }
            $collected = $col['collected'] !== null ? $this->money($line[$col['collected']] ?? null) : null;
            $charge = $col['charge'] !== null ? $this->money($line[$col['charge']] ?? null) : null;
            $stats['collected'] += (float) $collected;
            $stats['charge'] += (float) $charge;

            $s = CourierTracker::findShipment($account->courier, ['consignment_id' => $id, 'tracking_code' => $id, 'invoice' => $id]);
            if (! $s) {
                $stats['unknown']++;
                $report[] = ['id' => $id, 'result' => 'unknown', 'collected' => $collected, 'charge' => $charge, 'message' => 'No such parcel in Ecom Pilot'];
                continue;
            }

            $order = Order::withoutGlobalScope('assignee')->find($s->order_id);
            $expected = (float) ($s->cod_amount ?? max(0, (float) $order?->total - (float) $order?->paid_amount));
            $ok = $collected === null || abs($collected - $expected) < 1;
            $s->update(array_filter([
                'collected_amount' => $collected,
                'courier_charge'   => $charge,
                'paid_at'          => now(),
                'payment_ref'      => mb_substr($data['reference'] ?: $request->file('file')->getClientOriginalName(), 0, 100),
            ], fn ($v) => $v !== null));

            if ($ok && $order && $order->status === 'delivered') {
                $order->update(['status' => 'completed']);
            }
            if ($order) {
                ActivityLog::log('order.courier_paid', $order, $account->label() . ' paid ৳' . number_format((float) $collected)
                    . ($ok ? '' : ' — expected ৳' . number_format($expected)));
            }

            $stats[$ok ? 'matched' : 'mismatched']++;
            $report[] = [
                'id' => $id, 'result' => $ok ? 'matched' : 'mismatched', 'order' => $order?->order_number, 'order_id' => $order?->id,
                'collected' => $collected, 'expected' => $expected, 'charge' => $charge,
                'message' => $ok ? 'Paid' : 'Courier paid ৳' . number_format((float) $collected) . ', order COD is ৳' . number_format($expected),
            ];
        }

        $payment = CourierPayment::create([
            'courier_account_id' => $account->id,
            'courier'            => $account->courier,
            'file_name'          => mb_substr($request->file('file')->getClientOriginalName(), 0, 255),
            'reference'          => $data['reference'] ?? null,
            'matched'            => $stats['matched'],
            'mismatched'         => $stats['mismatched'],
            'unknown'            => $stats['unknown'],
            'total_collected'    => $stats['collected'],
            'total_charge'       => $stats['charge'],
            'rows'               => $report,
            'uploaded_by'        => auth()->id(),
        ]);

        return redirect()->route('admin.courier-payments.show', $payment)
            ->with('success', "{$stats['matched']} parcels matched, {$stats['mismatched']} with a different amount, {$stats['unknown']} not found.");
    }

    private function detect(array $headers): array
    {
        $found = ['id' => null, 'collected' => null, 'charge' => null];
        $used = [];
        foreach (self::COLUMNS as $key => $words) {
            foreach ($words as $w) {
                foreach ($headers as $i => $h) {
                    if (! in_array($i, $used, true) && str_contains($h, $w)) {
                        $found[$key] = $i;
                        $used[] = $i;
                        continue 3;
                    }
                }
            }
        }

        return $found;
    }

    private function money($v): ?float
    {
        $v = preg_replace('/[^\d.\-]/', '', (string) $v);

        return $v === '' ? null : (float) $v;
    }

    private function readCsv(string $path): array
    {
        $out = [];
        $h = fopen($path, 'r');
        $first = fgets($h);
        rewind($h);
        $sep = substr_count((string) $first, ';') > substr_count((string) $first, ',') ? ';' : ',';
        while (($row = fgetcsv($h, 0, $sep, '"', '')) !== false) {
            if ($out === [] && isset($row[0])) {
                $row[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $row[0]);   // Excel BOM
            }
            $out[] = $row;
        }
        fclose($h);

        return $out;
    }
}
