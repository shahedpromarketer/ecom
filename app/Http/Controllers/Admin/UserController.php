<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    private function tenantUserCount(): int
    {
        return User::where('tenant_id', auth()->user()->tenant_id)
            ->where('is_super_admin', false)
            ->count();
    }

    public function index(Request $request)
    {
        $query = User::withCount('orders')
            ->withSum('orders', 'total');
        if (!auth()->user()->is_super_admin) {
            $query->where('tenant_id', auth()->user()->tenant_id);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%")
                  ->orWhere('phone', 'like', "%{$s}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->status === 'banned') {
            $query->where('is_banned', true);
        } elseif ($request->status === 'active') {
            $query->where('is_banned', false);
        }

        $users  = $query->latest()->paginate(20)->withQueryString();
        $userStatBase = auth()->user()->is_super_admin ? User::query() : User::where('tenant_id', auth()->user()->tenant_id);
        $total  = (clone $userStatBase)->count();
        $banned = (clone $userStatBase)->where('is_banned', true)->count();
        $newThisMonth = (clone $userStatBase)->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)->count();

        $tenant   = auth()->user()->is_super_admin ? null : auth()->user()->tenant;
        $maxUsers = $tenant?->max_users ?? null;

        return view('admin.users.index', compact('users', 'total', 'banned', 'newThisMonth', 'maxUsers'));
    }

    private function assertSameTenant(User $user): void
    {
        if (!auth()->user()->is_super_admin && $user->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }
    }

    public function show(User $user)
    {
        $this->assertSameTenant($user);
        $user->loadCount('orders')->load('orders.items');
        $totalSpent = $user->orders->sum('total');
        return view('admin.users.show', compact('user', 'totalSpent'));
    }

    public function create()
    {
        $tenant   = auth()->user()->tenant;
        $maxUsers = $tenant?->max_users ?? null;
        $current  = $this->tenantUserCount();

        if ($maxUsers && $current >= $maxUsers) {
            return redirect()->route('admin.users.index')
                ->with('error', "User limit reached ({$current}/{$maxUsers}). Upgrade your plan to add more users.");
        }

        $roles = Role::orderBy('display_name')->get();
        return view('admin.users.create', compact('roles', 'maxUsers', 'current'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'phone'    => 'nullable|string|max:20',
            'password' => 'required|string|min:8',
            'role_id'  => 'nullable|exists:roles,id',
        ]);

        $tenant   = auth()->user()->tenant;
        $maxUsers = $tenant?->max_users ?? null;

        if ($maxUsers && $this->tenantUserCount() >= $maxUsers) {
            return back()->with('error', "User limit reached. Your plan allows a maximum of {$maxUsers} users.");
        }

        User::create([
            'name'      => $request->name,
            'email'     => $request->email,
            'phone'     => $request->phone,
            'password'  => Hash::make($request->password),
            'role_id'   => $request->role_id,
            'tenant_id' => auth()->user()->tenant_id,
        ]);

        return redirect()->route('admin.users.index')->with('success', 'User created successfully.');
    }

    public function edit(User $user)
    {
        $this->assertSameTenant($user);
        $roles = Role::orderBy('display_name')->get();
        return view('admin.users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        $this->assertSameTenant($user);

        $request->validate([
            'name'    => 'required|string|max:255',
            'email'   => 'required|email|unique:users,email,' . $user->id,
            'phone'   => 'nullable|string|max:20',
            'role_id' => 'nullable|exists:roles,id',
        ]);

        $user->update($request->only('name', 'email', 'phone', 'role_id'));

        return redirect()->route('admin.users.index')->with('success', 'User updated successfully.');
    }

    public function ban(Request $request, User $user)
    {
        $this->assertSameTenant($user);
        $request->validate(['reason' => 'nullable|string|max:500']);

        $user->update([
            'is_banned'  => true,
            'ban_reason' => $request->reason,
        ]);

        return response()->json(['success' => true, 'message' => 'User banned.']);
    }

    public function unban(User $user)
    {
        $this->assertSameTenant($user);
        $user->update(['is_banned' => false, 'ban_reason' => null]);

        return response()->json(['success' => true, 'message' => 'User unbanned.']);
    }

    public function destroy(User $user)
    {
        $this->assertSameTenant($user);

        if ($user->orders()->exists()) {
            return back()->with('error', 'Cannot delete user with existing orders.');
        }

        $user->delete();

        return back()->with('success', 'User deleted.');
    }
}
