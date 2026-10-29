<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Followup;
use App\Models\Order;
use App\Models\User;
use App\Support\Tenancy;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FollowupController extends Controller
{
    public const TABS = [
        'today'    => 'Today',
        'overdue'  => 'Overdue',
        'upcoming' => 'Upcoming',
        'done'     => 'Done',
        'negative' => 'Negative',
    ];

    public function index(Request $request)
    {
        $user = auth()->user();
        $tab  = array_key_exists($request->tab, self::TABS) ? $request->tab : 'today';

        $base = fn () => Followup::query()->visibleTo($user);

        $query = $base()->with(['customer', 'assignee', 'order']);
        match ($tab) {
            'today'    => $query->open()->whereDate('scheduled_for', today()),
            'overdue'  => $query->open()->whereDate('scheduled_for', '<', today()),
            'upcoming' => $query->open()->whereDate('scheduled_for', '>', today()),
            'done'     => $query->where('status', 'done'),
            'negative' => $query->where('status', 'negative'),
        };

        if ($request->filled('tag')) {
            $query->where('tag', $request->tag);
        }
        if ($request->filled('assigned_to') && $user->can('view_all_followups')) {
            $request->assigned_to === 'none'
                ? $query->whereNull('assigned_to')
                : $query->where('assigned_to', (int) $request->assigned_to);
        }
        if ($request->filled('search')) {
            $s = trim($request->search);
            $frag = \App\Support\Phone::searchFragment($s);
            $query->whereHas('customer', fn ($q) => $q->where('name', 'like', "%{$s}%")
                ->orWhere('phone', 'like', '%' . ($frag ?: $s) . '%'));
        }

        in_array($tab, ['done', 'negative'], true)
            ? $query->latest('done_at')
            : $query->orderBy('scheduled_for')->orderBy('id');

        $followups = $query->paginate(30)->withQueryString();

        $counts = [
            'today'    => $base()->open()->whereDate('scheduled_for', today())->count(),
            'overdue'  => $base()->open()->whereDate('scheduled_for', '<', today())->count(),
            'upcoming' => $base()->open()->whereDate('scheduled_for', '>', today())->count(),
        ];

        $staff = $this->staff();

        return view('admin.followups.index', compact('followups', 'tab', 'counts', 'staff'));
    }

    /** Create from a customer profile or an order page. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_id'   => ['required', Tenancy::exists('customers')],
            'order_id'      => ['nullable', Tenancy::exists('orders')],
            'scheduled_for' => 'required|date|after_or_equal:today',
            'assigned_to'   => ['nullable', Tenancy::exists('users')],
            'tag'           => ['nullable', Rule::in(array_keys(Followup::TAGS))],
            'note'          => 'nullable|string|max:2000',
        ]);

        // Staff who only see their own list can only create follow-ups for themselves.
        if (! auth()->user()->can('view_all_followups')) {
            $data['assigned_to'] = auth()->id();
        }

        Followup::create($data + [
            'created_by'  => auth()->id(),
            'assigned_to' => $data['assigned_to'] ?? auth()->id(),
            'status'      => 'listed',
        ]);

        return back()->with('success', 'Follow-up scheduled for ' . \Illuminate\Support\Carbon::parse($data['scheduled_for'])->format('d M Y') . '.');
    }

    /** Record the call: done / negative, what happened, and optionally book the next one. */
    public function complete(Request $request, Followup $followup)
    {
        $this->authorizeFollowup($followup);

        $data = $request->validate([
            'status'        => ['required', Rule::in(['done', 'negative'])],
            'result_note'   => 'nullable|string|max:2000',
            'next_date'     => 'nullable|date|after:today',
            'next_note'     => 'nullable|string|max:2000',
        ]);

        $followup->update([
            'status'      => $data['status'],
            'result_note' => $data['result_note'] ?? null,
            'done_at'     => now(),
            'done_by'     => auth()->id(),
        ]);

        if (! empty($data['next_date'])) {
            Followup::create([
                'customer_id'   => $followup->customer_id,
                'order_id'      => $followup->order_id,
                'assigned_to'   => $followup->assigned_to ?? auth()->id(),
                'created_by'    => auth()->id(),
                'scheduled_for' => $data['next_date'],
                'tag'           => $followup->tag,
                'note'          => $data['next_note'] ?? null,
                'status'        => 'listed',
            ]);
        }

        if ($data['result_note'] ?? null) {
            $followup->customer?->notes()->create([
                'user_id' => auth()->id(),
                'note'    => 'Follow-up (' . Followup::STATUSES[$data['status']] . '): ' . $data['result_note'],
            ]);
        }

        return back()->with('success', 'Follow-up marked ' . strtolower(Followup::STATUSES[$data['status']]) . '.');
    }

    /** Re-open a finished follow-up (e.g. marked done by mistake). */
    public function reopen(Followup $followup)
    {
        $this->authorizeFollowup($followup);
        $followup->update(['status' => 'listed', 'done_at' => null, 'done_by' => null]);

        return back()->with('success', 'Follow-up re-opened.');
    }

    public function bulk(Request $request)
    {
        $data = $request->validate([
            'ids'           => 'required|array|min:1',
            'ids.*'         => 'integer',
            'action'        => ['required', Rule::in(['reschedule', 'assign', 'done'])],
            'scheduled_for' => 'required_if:action,reschedule|nullable|date|after_or_equal:today',
            'assigned_to'   => ['required_if:action,assign', 'nullable', Tenancy::exists('users')],
        ]);

        if ($data['action'] === 'assign' && ! auth()->user()->can('view_all_followups')) {
            return back()->with('permission_error', 'Only managers can move follow-ups to other staff.');
        }

        $query = Followup::query()->visibleTo(auth()->user())->whereIn('id', $data['ids']);

        $count = match ($data['action']) {
            'reschedule' => $query->open()->update(['scheduled_for' => $data['scheduled_for']]),
            'assign'     => $query->update(['assigned_to' => $data['assigned_to']]),
            'done'       => $query->open()->update(['status' => 'done', 'done_at' => now(), 'done_by' => auth()->id()]),
        };

        return back()->with('success', "{$count} follow-up(s) updated.");
    }

    public function destroy(Followup $followup)
    {
        $this->authorizeFollowup($followup);
        $followup->delete();

        return back()->with('success', 'Follow-up deleted.');
    }

    private function authorizeFollowup(Followup $followup): void
    {
        $user = auth()->user();
        if (! $user->can('view_all_followups') && $followup->assigned_to !== $user->id) {
            abort(403, 'This follow-up is assigned to someone else.');
        }
    }

    private function staff()
    {
        return User::inCurrentTenant()->whereIn('role', ['admin', 'staff'])->orderBy('name')->get(['id', 'name']);
    }
}
