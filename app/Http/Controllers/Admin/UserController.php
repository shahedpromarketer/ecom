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

    /** Role must be one of this shop's roles or a shared system role. */
    private function roleRule(): \Illuminate\Validation\Rules\Exists
    {
        return \Illuminate\Validation\Rule::exists('roles', 'id')->where(function ($q) {
            if (\App\Support\Tenancy::active()) {
                $q->where(fn ($w) => $w->where('tenant_id', \App\Support\Tenancy::id())->orWhereNull('tenant_id'));
            }
        });
    }

    /** The "Administrator" system role makes a user a full-access admin; any other role makes them staff. */
    private function accessLevelFor(?int $roleId): string
    {
        $role = $roleId ? Role::find($roleId) : null;

        return ($role && $role->tenant_id === null && $role->name === 'admin') ? 'admin' : 'staff';
    }

    /** Nobody locks themselves out, and the shop owner cannot be removed by their staff. */
    private function guardProtectedUser(User $user): ?string
    {
        if ($user->id === auth()->id()) {
            return 'You cannot do this to your own account.';
        }

        if ($user->tenant && $user->tenant->owner_id === $user->id && ! auth()->user()->is_super_admin) {
            return 'The shop owner account cannot be changed here.';
        }

        return null;
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
        $request->merge(['phone' => User::normalizePhone($request->phone), 'email' => $request->filled('email') ? trim($request->email) : null]);
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'nullable|required_without:phone|email|max:150|unique:users,email',
            'phone'    => 'nullable|required_without:email|string|min:10|max:20|unique:users,phone',
            'password' => 'required|string|min:8',
            'role_id'  => ['required', $this->roleRule()],
        ], ['email.required_without' => 'Give an email or a mobile number — staff sign in with either.', 'phone.unique' => 'This mobile number is already used by another account.']);

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
            'role'      => $this->accessLevelFor((int) $request->role_id),
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

        $request->merge(['phone' => User::normalizePhone($request->phone), 'email' => $request->filled('email') ? trim($request->email) : null]);
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'nullable|required_without:phone|email|max:150|unique:users,email,' . $user->id,
            'phone'    => 'nullable|required_without:email|string|min:10|max:20|unique:users,phone,' . $user->id,
            'role_id'  => ['required', $this->roleRule()],
            'password' => 'nullable|string|min:8|max:100',
        ], ['email.required_without' => 'Give an email or a mobile number — staff sign in with either.', 'phone.unique' => 'This mobile number is already used by another account.']);

        $data = $request->only('name', 'email', 'phone');

        // New password for a staff member who forgot theirs (no email here). Not your own (use Profile), not the owner's.
        if ($request->filled('password')) {
            if ($error = $this->guardProtectedUser($user)) {
                return back()->withInput()->with('error', $error === 'You cannot do this to your own account.' ? 'Change your own password from your Profile.' : $error);
            }
            $data['password'] = Hash::make($request->password);
            $data['remember_token'] = \Illuminate\Support\Str::random(60);
        }

        // Changing someone's role: not your own (no self-promotion/lock-out), not the owner's.
        if ((int) $request->role_id !== (int) $user->role_id || $this->accessLevelFor((int) $request->role_id) !== $user->role) {
            if ($error = $this->guardProtectedUser($user)) {
                return back()->with('error', $error);
            }
            $data['role_id'] = $request->role_id;
            $data['role']    = $this->accessLevelFor((int) $request->role_id);
        }

        $user->forceFill($data)->save();
        if (isset($data['password'])) {
            rescue(fn () => \Illuminate\Support\Facades\DB::table('sessions')->where('user_id', $user->id)->delete(), null, false);   // sign out old sessions
        }

        return redirect()->route('admin.users.index')->with('success', isset($data['password'])
            ? 'User updated. New password set — share it with ' . $user->name . '.'
            : 'User updated successfully.');
    }

    public function ban(Request $request, User $user)
    {
        $this->assertSameTenant($user);
        if ($error = $this->guardProtectedUser($user)) {
            return response()->json(['success' => false, 'message' => $error], 422);
        }
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

        if ($error = $this->guardProtectedUser($user)) {
            return back()->with('error', $error);
        }

        if ($user->orders()->exists()) {
            return back()->with('error', 'Cannot delete user with existing orders.');
        }

        $user->delete();

        return back()->with('success', 'User deleted.');
    }
}
