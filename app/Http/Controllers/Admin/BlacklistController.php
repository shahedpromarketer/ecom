<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlacklistedPhone;
use App\Models\IpBlocklist;
use Illuminate\Http\Request;

class BlacklistController extends Controller
{
    public function index()
    {
        $tenantId = auth()->user()->tenant_id;
        $isSA     = auth()->user()->is_super_admin;

        $phoneQ = BlacklistedPhone::with('blacklistedByUser')->latest();
        if (!$isSA) $phoneQ->where('tenant_id', $tenantId);
        $phones = $phoneQ->paginate(25, ['*'], 'phone_page');

        $ipQ = IpBlocklist::with('blockedByUser')->latest();
        if (!$isSA) $ipQ->where('tenant_id', $tenantId);
        $ips = $ipQ->paginate(25, ['*'], 'ip_page');

        return view('admin.blacklist.index', compact('phones', 'ips'));
    }

    public function store(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $request->validate([
            'phone'  => 'required|string|digits:11|unique:blacklisted_phones,phone,NULL,id,tenant_id,' . $tenantId,
            'reason' => 'nullable|string|max:255',
        ]);

        BlacklistedPhone::create([
            'phone'          => $request->phone,
            'reason'         => $request->reason,
            'blacklisted_by' => auth()->id(),
            'tenant_id'      => $tenantId,
        ]);

        return back()->with('success', 'Phone number blacklisted.');
    }

    public function destroy(BlacklistedPhone $blacklist)
    {
        if (!auth()->user()->is_super_admin && $blacklist->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        $blacklist->delete();

        return back()->with('success', 'Phone removed from blacklist.');
    }

    // ── IP Blocklist ──────────────────────────────────────────────────────────

    public function storeIp(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $request->validate([
            'ip_address' => 'required|ip|unique:ip_blocklist,ip_address,NULL,id,tenant_id,' . $tenantId,
            'reason'     => 'nullable|string|max:255',
        ]);

        IpBlocklist::create([
            'ip_address' => $request->ip_address,
            'reason'     => $request->reason,
            'blocked_by' => auth()->id(),
            'tenant_id'  => $tenantId,
        ]);

        return back()->with('success', 'IP address blocked.');
    }

    public function destroyIp(IpBlocklist $ip)
    {
        if (!auth()->user()->is_super_admin && $ip->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        $ip->delete();

        return back()->with('success', 'IP removed from blocklist.');
    }
}
