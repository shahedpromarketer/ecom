<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::withCount('orders')
            ->withSum('orders', 'total');

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
        $total  = User::count();
        $banned = User::where('is_banned', true)->count();
        $newThisMonth = User::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)->count();

        return view('admin.users.index', compact('users', 'total', 'banned', 'newThisMonth'));
    }

    public function show(User $user)
    {
        $user->loadCount('orders')->load('orders.items');
        $totalSpent = $user->orders->sum('total');
        return view('admin.users.show', compact('user', 'totalSpent'));
    }

    public function create()
    {
        $roles = Role::orderBy('display_name')->get();
        return view('admin.users.create', compact('roles'));
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

        User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'phone'    => $request->phone,
            'password' => Hash::make($request->password),
            'role_id'  => $request->role_id,
        ]);

        return redirect()->route('admin.users.index')->with('success', 'User created successfully.');
    }

    public function edit(User $user)
    {
        $roles = Role::orderBy('display_name')->get();
        return view('admin.users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user)
    {
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
        $request->validate(['reason' => 'nullable|string|max:500']);

        $user->update([
            'is_banned'  => true,
            'ban_reason' => $request->reason,
        ]);

        return response()->json(['success' => true, 'message' => 'User banned.']);
    }

    public function unban(User $user)
    {
        $user->update(['is_banned' => false, 'ban_reason' => null]);

        return response()->json(['success' => true, 'message' => 'User unbanned.']);
    }

    public function destroy(User $user)
    {
        if ($user->orders()->exists()) {
            return back()->with('error', 'Cannot delete user with existing orders.');
        }

        $user->delete();

        return back()->with('success', 'User deleted.');
    }
}
