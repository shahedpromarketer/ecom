<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Support\Tenancy;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    public function index()
    {
        // Count only this shop's staff on each role (system roles are shared by every shop).
        $roles = Role::withCount(['users' => function ($q) {
            if (Tenancy::active()) {
                $q->where('tenant_id', Tenancy::id());
            }
        }])->orderBy('id')->get();

        return view('admin.roles.index', compact('roles'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'         => ['required', 'string', 'max:50', 'alpha_dash', $this->uniqueName()],
            'display_name' => 'required|string|max:100',
            'permissions'  => 'nullable|array',
        ]);

        // tenant_id is filled in by the BelongsToTenant trait.
        Role::create([
            'name'         => $request->name,
            'display_name' => $request->display_name,
            'permissions'  => $request->permissions ?? [],
            'is_system'    => false,
        ]);

        return back()->with('success', 'Role created successfully.');
    }

    public function update(Request $request, Role $role)
    {
        if ($error = $this->cannotChange($role)) {
            return back()->with('error', $error);
        }

        $request->validate([
            'display_name' => 'required|string|max:100',
            'permissions'  => 'nullable|array',
        ]);

        $data = ['display_name' => $request->display_name, 'permissions' => $request->permissions ?? []];

        if (! $role->is_system) {
            $request->validate(['name' => ['required', 'string', 'max:50', 'alpha_dash', $this->uniqueName($role->id)]]);
            $data['name'] = $request->name;
        }

        $role->update($data);

        return back()->with('success', 'Role updated successfully.');
    }

    public function destroy(Role $role)
    {
        if ($role->is_system) {
            return back()->with('error', 'System roles cannot be deleted.');
        }

        if ($error = $this->cannotChange($role)) {
            return back()->with('error', $error);
        }

        if ($role->users()->exists()) {
            return back()->with('error', 'Cannot delete role with assigned users.');
        }

        $role->delete();

        return back()->with('success', 'Role deleted.');
    }

    /** Shared roles (tenant_id NULL) belong to every shop, so only a super admin may edit them. */
    private function cannotChange(Role $role): ?string
    {
        if ($role->tenant_id === null && ! auth()->user()->is_super_admin) {
            return 'This is a shared system role. Create your own role to customise permissions.';
        }

        return null;
    }

    private function uniqueName(?int $ignoreId = null): \Illuminate\Validation\Rules\Unique
    {
        $rule = Rule::unique('roles', 'name')->where(function ($q) {
            $q->where(fn ($w) => $w->where('tenant_id', Tenancy::id())->orWhereNull('tenant_id'));
        });

        return $ignoreId ? $rule->ignore($ignoreId) : $rule;
    }
}
