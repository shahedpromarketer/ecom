<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\BlacklistedPhone;
use App\Models\Customer;
use App\Models\IncompleteOrder;
use App\Models\IpBlocklist;
use App\Models\Lead;
use App\Models\WordPressSite;
use App\Support\Phone;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** "Failed orders" — checkouts that were started but never finished. */
class FailedOrderController extends Controller
{
    public const TABS = ['pending' => 'To call', 'recovered' => 'Recovered', 'deleted' => 'Deleted', 'all' => 'All'];

    public function index(Request $request)
    {
        $tab = array_key_exists($request->tab, self::TABS) ? $request->tab : 'pending';

        $query = IncompleteOrder::with(['store', 'order', 'assignee']);
        if ($tab !== 'all') {
            $query->where('status', $tab);
        }
        if ($request->filled('search')) {
            $s = trim($request->search);
            $frag = Phone::searchFragment($s);
            $query->where(fn ($q) => $q->where('name', 'like', "%{$s}%")->orWhere('phone', 'like', '%' . ($frag ?: $s) . '%'));
        }
        if ($request->filled('store')) {
            $query->where('wordpress_site_id', (int) $request->store);
        }
        match ($request->period) {
            'today' => $query->whereDate('last_activity_at', today()),
            '7d'    => $query->where('last_activity_at', '>=', now()->subDays(7)),
            '30d'   => $query->where('last_activity_at', '>=', now()->subDays(30)),
            default => null,
        };

        $items = $query->latest('last_activity_at')->paginate(30)->withQueryString();

        // Past orders per phone on this page, to judge who is a real buyer.
        $history = Customer::whereIn('phone', $items->pluck('phone')->unique())->get()->keyBy('phone');

        $since = now()->subDays(30);
        $stats = [
            'pending'   => IncompleteOrder::where('status', 'pending')->count(),
            'today'     => IncompleteOrder::whereDate('created_at', today())->count(),
            'recovered' => IncompleteOrder::where('status', 'recovered')->where('created_at', '>=', $since)->count(),
            'total30'   => IncompleteOrder::where('created_at', '>=', $since)->count(),
        ];
        $stats['rate'] = $stats['total30'] ? round($stats['recovered'] * 100 / $stats['total30']) : null;

        $stores = WordPressSite::orderBy('name')->get(['id', 'name']);

        return view('admin.failed-orders.index', compact('items', 'tab', 'stats', 'history', 'stores'));
    }

    public function update(Request $request, IncompleteOrder $failed)
    {
        $data = $request->validate([
            'action'        => ['required', Rule::in(['note', 'delete', 'restore', 'lead', 'block'])],
            'note'          => 'nullable|string|max:2000',
            'delete_reason' => 'nullable|string|max:255',
        ]);

        switch ($data['action']) {
            case 'note':
                $failed->update(['note' => trim(($failed->note ? $failed->note . "\n" : '') . now()->format('d M H:i') . ' ' . auth()->user()->name . ': ' . $data['note'])]);
                break;
            case 'delete':
                $failed->update(['status' => 'deleted', 'delete_reason' => $data['delete_reason'] ?: 'Other']);
                break;
            case 'restore':
                $failed->update(['status' => 'pending', 'delete_reason' => null]);
                break;
            case 'lead':
                $this->toLead($failed);
                return back()->with('success', 'Moved to Leads.');
            case 'block':
                $this->block($failed);
                return back()->with('success', 'Number' . ($failed->ip ? ' and IP' : '') . ' blocked; entry deleted.');
        }

        return back()->with('success', 'Updated.');
    }

    public function bulk(Request $request)
    {
        $data = $request->validate([
            'ids'           => 'required|array|min:1',
            'ids.*'         => 'integer',
            'action'        => ['required', Rule::in(['delete', 'block', 'lead'])],
            'delete_reason' => 'nullable|string|max:255',
        ]);

        $rows = IncompleteOrder::whereIn('id', $data['ids'])->where('status', 'pending')->get();
        foreach ($rows as $row) {
            match ($data['action']) {
                'delete' => $row->update(['status' => 'deleted', 'delete_reason' => $data['delete_reason'] ?: 'Other']),
                'block'  => $this->block($row),
                'lead'   => $this->toLead($row),
            };
        }

        return back()->with('success', $rows->count() . ' entr' . ($rows->count() === 1 ? 'y' : 'ies') . ' updated.');
    }

    private function toLead(IncompleteOrder $f): void
    {
        $customer = Customer::firstOrCreate(['phone' => $f->phone], array_filter(['name' => $f->name, 'address' => $f->address]));
        if (! Lead::where('customer_id', $customer->id)->where('status', 'pending')->exists()) {
            Lead::create([
                'customer_id' => $customer->id,
                'status'      => 'pending',
                'source'      => 'website',
                'interest'    => $f->itemsSummary() ?: null,
                'note'        => 'From an unfinished checkout' . ($f->total > 0 ? ' (৳' . number_format($f->total) . ')' : '') . '.' . ($f->note ? "\n" . $f->note : ''),
                'assigned_to' => $f->assigned_to ?? auth()->id(),
                'created_by'  => auth()->id(),
            ]);
        }
        $f->update(['status' => 'deleted', 'delete_reason' => 'Moved to leads']);
    }

    private function block(IncompleteOrder $f): void
    {
        BlacklistedPhone::firstOrCreate(['phone' => $f->phone], ['reason' => 'Blocked from failed orders', 'blacklisted_by' => auth()->id()]);
        if ($f->ip) {
            IpBlocklist::firstOrCreate(['ip_address' => $f->ip], ['reason' => 'Blocked from failed orders', 'blocked_by' => auth()->id()]);
        }
        $f->update(['status' => 'deleted', 'delete_reason' => 'Blocked']);
        ActivityLog::log('failed_order.blocked', $f, 'Blocked ' . $f->phone . ($f->ip ? ' / ' . $f->ip : '') . ' by ' . auth()->user()->name);
    }
}
