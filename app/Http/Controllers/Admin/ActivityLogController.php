<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $query = ActivityLog::with('user')->latest();
        if (!auth()->user()->is_super_admin) {
            $query->where('tenant_id', auth()->user()->tenant_id);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where('description', 'like', "%{$s}%");
        }
        if ($request->filled('action_type')) {
            $query->where('action', 'like', $request->action_type . '.%');
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs  = $query->paginate(50)->withQueryString();
        $adminsQ = User::where('role', 'admin');
        if (!auth()->user()->is_super_admin) { $adminsQ->where('tenant_id', auth()->user()->tenant_id); }
        $admins = $adminsQ->get();

        return view('admin.activity-log.index', compact('logs', 'admins'));
    }
}
