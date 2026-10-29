<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use App\Services\OrderDistributor;
use App\Support\Tenancy;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DistributionController extends Controller
{
    public function index()
    {
        $tenantId = Tenancy::id();
        $settings = OrderDistributor::settings($tenantId);

        $staff = User::inCurrentTenant()
            ->whereIn('role', ['admin', 'staff'])
            ->with('roleModel')
            ->withCount([
                'assignedOrders as open_orders' => fn ($q) => $q->withoutGlobalScopes()->whereIn('status', OrderDistributor::OPEN_STATUSES),
                'assignedOrders as assigned_today' => fn ($q) => $q->withoutGlobalScopes()->whereDate('assigned_at', today()),
            ])
            ->orderBy('name')
            ->get();

        $queued = Order::whereNull('assigned_to')->whereIn('status', OrderDistributor::OPEN_STATUSES)->count();

        return view('admin.distribution.index', compact('settings', 'staff', 'queued'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'mode'      => ['required', Rule::in(array_keys(OrderDistributor::MODES))],
            'max_open'  => 'nullable|integer|min:0|max:10000',
            'sticky'    => 'nullable|boolean',
            'receivers' => 'nullable|array',
            'receivers.*' => [Tenancy::exists('users')],
        ]);

        $tenantId = Tenancy::id();
        Setting::setValue('distribution_mode', $data['mode'], 'distribution', $tenantId);
        Setting::setValue('distribution_max_open', (int) ($data['max_open'] ?? 0), 'distribution', $tenantId);
        Setting::setValue('distribution_sticky', $request->boolean('sticky') ? 1 : 0, 'distribution', $tenantId);

        $receivers = array_map('intval', $data['receivers'] ?? []);
        User::inCurrentTenant()->whereIn('role', ['admin', 'staff'])->update(['receives_orders' => false]);
        if ($receivers) {
            User::inCurrentTenant()->whereIn('id', $receivers)->update(['receives_orders' => true]);
        }

        return back()->with('success', 'Distribution settings saved.');
    }

    /** Hand out queued orders now instead of waiting for the next minute. */
    public function run()
    {
        $count = OrderDistributor::drainQueue(Tenancy::id());

        return back()->with('success', $count ? "Assigned {$count} queued order(s)." : 'Nothing could be assigned (queue empty, distribution off, or everyone is at their limit).');
    }
}
