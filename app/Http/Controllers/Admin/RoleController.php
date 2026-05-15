<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::withCount('users')->orderBy('id')->get();
        return view('admin.roles.index', compact('roles'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'         => 'required|string|max:50|unique:roles,name|alpha_dash',
            'display_name' => 'required|string|max:100',
            'permissions'  => 'nullable|array',
        ]);

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
        $request->validate([
            'display_name' => 'required|string|max:100',
            'permissions'  => 'nullable|array',
        ]);

        $data = ['display_name' => $request->display_name, 'permissions' => $request->permissions ?? []];

        if (!$role->is_system) {
            $request->validate(['name' => 'required|string|max:50|alpha_dash|unique:roles,name,' . $role->id]);
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

        if ($role->users()->exists()) {
            return back()->with('error', 'Cannot delete role with assigned users.');
        }

        $role->delete();

        return back()->with('success', 'Role deleted.');
    }
}
