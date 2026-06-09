<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\InvoiceTemplate;
use App\Models\Order;
use App\Models\Setting;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $query = Invoice::with('order')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhereHas('order', fn($o) => $o->where('order_number', 'like', "%{$search}%"));
            });
        }

        $invoices = $query->paginate(20)->withQueryString();

        return view('admin.invoices.index', compact('invoices'));
    }

    public function show(Invoice $invoice)
    {
        $invoice->load('order.items.product', 'order.items.variant', 'order.user');
        return view('admin.invoices.show', compact('invoice'));
    }

    public function generateFromOrder(Order $order)
    {
        $invoice = Invoice::where('order_id', $order->id)->first();

        if (!$invoice) {
            $invoice = Invoice::create([
                'invoice_number' => Invoice::generateNumber(),
                'order_id'       => $order->id,
                'subtotal'       => $order->subtotal,
                'discount'       => $order->discount,
                'shipping'       => $order->shipping_charge,
                'total'          => $order->total,
                'status'         => 'draft',
            ]);
        }

        return redirect()->route('admin.invoices.show', $invoice)->with('success', 'Invoice generated.');
    }

    public function generatePrint(Order $order)
    {
        $invoice = Invoice::where('order_id', $order->id)->first();

        if (!$invoice) {
            $invoice = Invoice::create([
                'invoice_number' => Invoice::generateNumber(),
                'order_id'       => $order->id,
                'subtotal'       => $order->subtotal,
                'discount'       => $order->discount,
                'shipping'       => $order->shipping_charge,
                'total'          => $order->total,
                'status'         => 'draft',
            ]);
        }

        return redirect()->route('admin.invoices.print', $invoice);
    }

    public function updateStatus(Request $request, Invoice $invoice)
    {
        $request->validate(['status' => 'required|in:draft,sent,paid,cancelled']);
        $invoice->update(['status' => $request->status]);
        return back()->with('success', 'Invoice status updated.');
    }

    public function printView(Invoice $invoice)
    {
        $invoice->load('order.items.product', 'order.items.variant');
        $order    = $invoice->order;
        $settings = Setting::pluck('value', 'key')->toArray();
        $active   = InvoiceTemplate::where('is_active', true)->first();
        $view     = $active ? 'admin.invoices.templates.' . $active->size . '-' . $active->style
                            : 'admin.invoices.templates.medium-standard';
        return view($view, compact('order', 'settings', 'invoice'));
    }

    public function preview($templateId)
    {
        $template = InvoiceTemplate::findOrFail($templateId);
        $order    = Order::with(['items.product', 'items.variant'])->whereHas('items')->latest()->firstOrFail();
        $settings = Setting::pluck('value', 'key')->toArray();
        $invoice  = Invoice::where('order_id', $order->id)->first();
        $view     = 'admin.invoices.templates.' . $template->size . '-' . $template->style;
        return view($view, compact('order', 'settings', 'invoice', 'template'));
    }

    public function downloadCsv(Request $request)
    {
        $invoices = Invoice::with('order')->latest()->get()->map(fn($i) => [
            'Invoice #'    => $i->invoice_number,
            'Order #'      => $i->order?->order_number,
            'Customer'     => $i->order?->shipping_name,
            'Subtotal'     => $i->subtotal,
            'Discount'     => $i->discount,
            'Shipping'     => $i->shipping,
            'Total'        => $i->total,
            'Status'       => ucfirst($i->status),
            'Date'         => $i->created_at->format('d M Y'),
        ]);

        $handle = fopen('php://temp', 'r+');
        fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

        if ($invoices->isNotEmpty()) {
            fputcsv($handle, array_keys($invoices->first()));
            foreach ($invoices as $row) {
                fputcsv($handle, $row);
            }
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="invoices-' . now()->toDateString() . '.csv"',
        ]);
    }

    public function bulkStatusUpdate(Request $request)
    {
        $request->validate([
            'ids'    => 'required|array',
            'status' => 'required|in:draft,sent,paid,cancelled',
        ]);

        Invoice::whereIn('id', $request->ids)->update(['status' => $request->status]);

        return back()->with('success', count($request->ids) . ' invoices updated.');
    }
}
