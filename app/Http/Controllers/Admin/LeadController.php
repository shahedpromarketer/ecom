<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\User;
use App\Support\Phone;
use App\Support\Tenancy;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LeadController extends Controller
{
    public const TABS = [
        'pending'   => 'Pending',
        'unread'    => 'New (unread)',
        'confirmed' => 'Confirmed',
        'canceled'  => 'Canceled',
        'all'       => 'All',
    ];

    public function index(Request $request)
    {
        $user = auth()->user();
        $tab  = array_key_exists($request->tab, self::TABS) ? $request->tab : 'pending';
        $base = fn () => Lead::query()->visibleTo($user);

        $query = $base()->with(['customer', 'assignee', 'order']);
        match ($tab) {
            'pending'   => $query->where('status', 'pending'),
            'unread'    => $query->where('status', 'pending')->whereNull('read_at'),
            'confirmed' => $query->where('status', 'confirmed'),
            'canceled'  => $query->where('status', 'canceled'),
            'all'       => null,
        };

        if ($request->filled('search')) {
            $s = trim($request->search);
            $frag = Phone::searchFragment($s);
            $query->whereHas('customer', fn ($q) => $q->where('name', 'like', "%{$s}%")->orWhere('phone', 'like', '%' . ($frag ?: $s) . '%'));
        }
        if ($request->filled('source')) {
            $query->where('source', $request->source);
        }
        if ($request->boolean('star')) {
            $query->where('is_star', true);
        }
        if ($request->filled('assigned_to') && $user->can('view_all_leads')) {
            $request->assigned_to === 'none' ? $query->whereNull('assigned_to') : $query->where('assigned_to', (int) $request->assigned_to);
        }

        $leads = $query->orderByDesc('is_star')->latest()->paginate(30)->withQueryString();

        $counts = [
            'pending' => $base()->where('status', 'pending')->count(),
            'unread'  => $base()->where('status', 'pending')->whereNull('read_at')->count(),
        ];

        $staff = $this->staff();

        return view('admin.leads.index', compact('leads', 'tab', 'counts', 'staff'));
    }

    /** One lead, typed in by staff (e.g. from a Messenger chat). */
    public function store(Request $request)
    {
        $data = $request->validate([
            'phone'       => ['required', 'string', 'max:20', function ($a, $v, $fail) {
                if (! Phone::isValidBdMobile($v)) {
                    $fail('Enter a valid Bangladeshi mobile number (01XXXXXXXXX).');
                }
            }],
            'name'        => 'nullable|string|max:255',
            'address'     => 'nullable|string|max:1000',
            'source'      => ['nullable', Rule::in(array_keys(Lead::SOURCES))],
            'interest'    => 'nullable|string|max:1000',
            'note'        => 'nullable|string|max:2000',
            'assigned_to' => ['nullable', Tenancy::exists('users')],
        ]);

        $assignee = auth()->user()->can('view_all_leads') ? ($data['assigned_to'] ?? auth()->id()) : auth()->id();

        [$lead, $created] = $this->createLead($data['phone'], $data['name'] ?? null, $assignee, [
            'source'   => $data['source'] ?? null,
            'interest' => $data['interest'] ?? null,
            'note'     => $data['note'] ?? null,
            'address'  => $data['address'] ?? null,
        ]);

        return back()->with($created ? 'success' : 'permission_error', $created
            ? 'Lead added.'
            : 'This number already has an open lead (assigned to ' . ($lead->assignee?->name ?? 'nobody') . ').');
    }

    /**
     * Many numbers at once: pasted lines ("01712345678 Rahim") and/or a CSV file.
     * New leads are shared out equally between the chosen staff.
     */
    public function import(Request $request)
    {
        abort_unless(auth()->user()->can('view_all_leads'), 403, 'Only managers can import leads.');

        $request->validate([
            'numbers'     => 'nullable|string|max:200000',
            'file'        => 'nullable|file|mimes:csv,txt|max:5120',
            'source'      => ['nullable', Rule::in(array_keys(Lead::SOURCES))],
            'note'        => 'nullable|string|max:1000',
            'staff'       => 'nullable|array',
            'staff.*'     => [Tenancy::exists('users')],
        ]);

        $rows = $this->parseRows((string) $request->numbers);
        if ($request->hasFile('file')) {
            $rows = array_merge($rows, $this->parseRows(file_get_contents($request->file('file')->getRealPath())));
        }

        if (! $rows) {
            return back()->withErrors(['numbers' => 'No valid mobile numbers found.']);
        }

        $staffIds = array_values(array_map('intval', $request->input('staff', [])));
        $added = $skipped = 0;
        $i = 0;

        foreach ($rows as $phone => $name) {
            $assignee = $staffIds ? $staffIds[$i % count($staffIds)] : null;
            [, $created] = $this->createLead($phone, $name, $assignee, [
                'source' => $request->source ?? 'import',
                'note'   => $request->note,
            ]);
            if ($created) {
                $added++;
                $i++;
            } else {
                $skipped++;
            }
        }

        return back()->with('success', "Imported {$added} lead(s)." . ($skipped ? " {$skipped} skipped (already had an open lead)." : ''));
    }

    public function update(Request $request, Lead $lead)
    {
        $this->authorizeLead($lead);

        $data = $request->validate([
            'action'        => ['required', Rule::in(['read', 'star', 'cancel', 'confirm', 'reopen', 'note'])],
            'cancel_reason' => 'nullable|string|max:255',
            'note'          => 'nullable|string|max:2000',
        ]);

        match ($data['action']) {
            'read'    => $lead->update(['read_at' => now()]),
            'star'    => $lead->update(['is_star' => ! $lead->is_star]),
            'cancel'  => $lead->update(['status' => 'canceled', 'cancel_reason' => $data['cancel_reason'] ?? null, 'read_at' => $lead->read_at ?? now()]),
            'confirm' => $lead->confirmWith(null),   // confirmed without an order in the CRM
            'reopen'  => $lead->update(['status' => 'pending', 'cancel_reason' => null, 'confirmed_at' => null]),
            'note'    => $lead->update(['note' => trim(($lead->note ? $lead->note . "\n" : '') . now()->format('d M H:i') . ' ' . auth()->user()->name . ': ' . ($data['note'] ?? ''))]),
        };

        return back()->with('success', 'Lead updated.');
    }

    public function bulk(Request $request)
    {
        $data = $request->validate([
            'ids'         => 'required|array|min:1',
            'ids.*'       => 'integer',
            'action'      => ['required', Rule::in(['assign', 'cancel', 'star', 'read', 'delete'])],
            'assigned_to' => ['required_if:action,assign', 'nullable', Tenancy::exists('users')],
        ]);

        if (in_array($data['action'], ['assign', 'delete'], true) && ! auth()->user()->can('view_all_leads')) {
            return back()->with('permission_error', 'Only managers can move or delete leads.');
        }

        $query = Lead::query()->visibleTo(auth()->user())->whereIn('id', $data['ids']);

        $count = match ($data['action']) {
            'assign' => $query->update(['assigned_to' => $data['assigned_to']]),
            'cancel' => $query->where('status', 'pending')->update(['status' => 'canceled']),
            'star'   => $query->update(['is_star' => true]),
            'read'   => $query->whereNull('read_at')->update(['read_at' => now()]),
            'delete' => $query->delete(),
        };

        return back()->with('success', "{$count} lead(s) updated.");
    }

    /**
     * @return array{0: Lead, 1: bool} the lead and whether it is new
     */
    private function createLead(string $phone, ?string $name, ?int $assignee, array $extra): array
    {
        $phone = Phone::normalize($phone);

        $customer = Customer::firstOrCreate(['phone' => $phone], array_filter([
            'name'    => $name,
            'address' => $extra['address'] ?? null,
        ]));
        if ($name && ! $customer->name) {
            $customer->update(['name' => $name]);
        }

        $open = Lead::where('customer_id', $customer->id)->where('status', 'pending')->first();
        if ($open) {
            return [$open, false];
        }

        $lead = Lead::create([
            'customer_id' => $customer->id,
            'status'      => 'pending',
            'source'      => $extra['source'] ?? null,
            'interest'    => $extra['interest'] ?? null,
            'note'        => $extra['note'] ?? null,
            'assigned_to' => $assignee ?? $customer->assigned_to,
            'created_by'  => auth()->id(),
        ]);

        return [$lead, true];
    }

    /** "01712345678, Rahim" / "Rahim 01712-345678" / CSV rows → [phone => name]. */
    private function parseRows(string $text): array
    {
        $rows = [];
        foreach (preg_split('/\r\n|\r|\n/', $text) as $line) {
            if (! preg_match('/(\+?88)?0?1[3-9][\d\s-]{8,12}/', $line, $m)) {
                continue;
            }
            $phone = Phone::normalize($m[0]);
            if (! Phone::isValidBdMobile($phone) || isset($rows[$phone])) {
                continue;
            }
            $name = trim(preg_replace('/[",;]+/', ' ', str_replace($m[0], '', $line)));
            $rows[$phone] = $name !== '' ? mb_substr($name, 0, 255) : null;
        }

        return $rows;
    }

    private function authorizeLead(Lead $lead): void
    {
        $user = auth()->user();
        if (! $user->can('view_all_leads') && $lead->assigned_to !== $user->id) {
            abort(403, 'This lead is assigned to someone else.');
        }
    }

    private function staff()
    {
        return User::inCurrentTenant()->whereIn('role', ['admin', 'staff'])->orderBy('name')->get(['id', 'name']);
    }
}
