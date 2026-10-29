<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\BlacklistedPhone;
use App\Models\Customer;
use App\Models\CustomerNote;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = Customer::query()->withCount('notes');

        if ($request->filled('search')) {
            $s = trim($request->search);
            $phone = \App\Support\Phone::searchFragment($s);
            $query->where(function ($q) use ($s, $phone) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('phone', 'like', '%' . ($phone ?: $s) . '%')
                  ->orWhere('alt_phone', 'like', "%{$s}%");
            });
        }

        if ($request->filled('tag')) {
            $request->tag === 'none' ? $query->whereNull('tag') : $query->where('tag', $request->tag);
        }

        match ($request->type) {
            'repeat'  => $query->where('total_orders', '>=', 2),
            'new'     => $query->where('total_orders', '<=', 1),
            'blocked' => $query->where('is_blocked', true),
            'never_delivered' => $query->where('total_orders', '>', 0)->where('delivered_orders', 0),
            default   => null,
        };

        // Success rate = delivered / (delivered + cancelled + returned)
        $finished = '(delivered_orders + cancelled_orders + returned_orders)';
        match ($request->success) {
            'high' => $query->whereRaw("$finished > 0 AND delivered_orders * 100 >= 80 * $finished"),
            'mid'  => $query->whereRaw("$finished > 0 AND delivered_orders * 100 >= 50 * $finished AND delivered_orders * 100 < 80 * $finished"),
            'low'  => $query->whereRaw("$finished > 0 AND delivered_orders * 100 < 50 * $finished"),
            'none' => $query->whereRaw("$finished = 0"),
            default => null,
        };

        if ($request->filled('district')) {
            $query->where('district', $request->district);
        }

        match ($request->sort) {
            'orders' => $query->orderByDesc('total_orders'),
            'spent'  => $query->orderByDesc('total_spent'),
            'name'   => $query->orderBy('name'),
            default  => $query->orderByDesc('last_order_at'),
        };

        $customers = $query->paginate($request->integer('per_page', 25) ?: 25)->withQueryString();

        $stats = [
            'total'   => Customer::count(),
            'repeat'  => Customer::where('total_orders', '>=', 2)->count(),
            'blocked' => Customer::where('is_blocked', true)->count(),
            'new_30d' => Customer::where('first_order_at', '>=', now()->subDays(30))->count(),
        ];

        $districts = Customer::whereNotNull('district')->where('district', '!=', '')
            ->distinct()->orderBy('district')->pluck('district');

        return view('admin.customers.index', compact('customers', 'stats', 'districts'));
    }

    public function show(Customer $customer)
    {
        $orders = $customer->orders()->with('items')->latest()->paginate(15);
        $customer->load(['notes.user', 'assignedTo']);
        $followups = \App\Models\Followup::where('customer_id', $customer->id)
            ->with(['assignee', 'doneByUser'])
            ->orderByRaw("status = 'listed' DESC")
            ->latest('scheduled_for')
            ->limit(20)
            ->get();
        $staff = \App\Models\User::inCurrentTenant()->whereIn('role', ['admin', 'staff'])->orderBy('name')->get(['id', 'name']);

        return view('admin.customers.show', compact('customer', 'orders', 'staff', 'followups'));
    }

    public function update(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'name'        => 'nullable|string|max:255',
            'alt_phone'   => 'nullable|string|max:20',
            'email'       => 'nullable|email|max:255',
            'address'     => 'nullable|string|max:1000',
            'district'    => 'nullable|string|max:100',
            'tag'         => ['nullable', Rule::in(array_keys(Customer::TAGS))],
            'assigned_to' => ['nullable', \App\Support\Tenancy::exists('users')],
            'is_blocked'  => 'nullable|boolean',
            'block_reason'=> 'nullable|string|max:255',
        ]);

        $wasBlocked = $customer->is_blocked;
        $customer->fill(collect($data)->except(['block_reason', 'is_blocked'])->all());

        // Blocking needs its own permission (same as the blacklist page).
        if (auth()->user()->can('ban_customers')) {
            $customer->is_blocked = $request->boolean('is_blocked');
        }
        $customer->save();

        // Blocking a customer also blocks the number at WordPress/plugin checkout.
        if ($customer->is_blocked && ! $wasBlocked) {
            BlacklistedPhone::firstOrCreate(
                ['phone' => $customer->phone],
                ['reason' => $request->input('block_reason') ?: 'Blocked from customer profile', 'blacklisted_by' => auth()->id()]
            );
            ActivityLog::log('customer.blocked', $customer, 'Customer ' . $customer->phone . ' blocked by ' . auth()->user()->name);
        } elseif (! $customer->is_blocked && $wasBlocked) {
            BlacklistedPhone::where('phone', $customer->phone)->delete();
            ActivityLog::log('customer.unblocked', $customer, 'Customer ' . $customer->phone . ' unblocked by ' . auth()->user()->name);
        }

        return back()->with('success', 'Customer updated.');
    }

    public function storeNote(Request $request, Customer $customer)
    {
        $request->validate(['note' => 'required|string|max:2000']);

        $customer->notes()->create([
            'user_id' => auth()->id(),
            'note'    => $request->note,
        ]);

        return back()->with('success', 'Note added.');
    }

    public function destroyNote(CustomerNote $note)
    {
        // Scoped through its customer, so only this shop's notes resolve.
        $note->delete();

        return back()->with('success', 'Note deleted.');
    }
}
