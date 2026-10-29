@extends('layouts.admin')

@section('title', 'Roles & Permissions')
@section('page-title', 'Roles & Permissions')

@section('breadcrumb')
    <span class="text-textsecondary">/</span>
    <span class="text-textprimary font-medium">Roles</span>
@endsection

@section('content')

@if(session('success'))
    <div class="mb-5 flex items-center gap-3 p-3.5 rounded-xl bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800">
        <svg class="w-4 h-4 text-green-600 dark:text-green-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/></svg>
        <p class="text-sm text-green-700 dark:text-green-400">{{ session('success') }}</p>
    </div>
@endif
@if(session('error'))
    <div class="mb-5 flex items-center gap-3 p-3.5 rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800">
        <svg class="w-4 h-4 text-red-600 dark:text-red-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd"/></svg>
        <p class="text-sm text-red-700 dark:text-red-400">{{ session('error') }}</p>
    </div>
@endif

@php
$permissionGroups = \App\Support\Permissions::GROUPS;
@endphp

{{-- Edit Modal --}}
<div x-data="{
    editModal: false,
    editRole: {},
    openEdit(role) {
        this.editRole = JSON.parse(JSON.stringify(role));
        if (!Array.isArray(this.editRole.permissions)) this.editRole.permissions = [];
        this.editModal = true;
    },
    hasPermission(perm) { return this.editRole.permissions && this.editRole.permissions.includes(perm); },
    togglePermission(perm) {
        if (!this.editRole.permissions) this.editRole.permissions = [];
        const idx = this.editRole.permissions.indexOf(perm);
        if (idx === -1) this.editRole.permissions.push(perm);
        else this.editRole.permissions.splice(idx, 1);
    }
}">
<div x-show="editModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 overflow-y-auto">
    <div @click.away="editModal = false" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-xl w-full max-w-2xl my-4">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="font-heading font-semibold text-textprimary dark:text-gray-100">Edit Role</h3>
            <button @click="editModal = false" class="text-textsecondary hover:text-textprimary dark:hover:text-gray-200">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form method="POST" :action="'/admin/roles/' + editRole.id" class="p-6 space-y-5">
            @csrf
            <input type="hidden" name="_method" value="PUT">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-textsecondary dark:text-gray-400 mb-1.5">Display Name</label>
                    <input type="text" name="display_name" :value="editRole.display_name" required
                           class="w-full px-4 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700/50 text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary dark:focus:border-primary transition-all duration-200">
                </div>
                <div>
                    <label class="block text-xs font-medium text-textsecondary dark:text-gray-400 mb-1.5">Role Name (slug)</label>
                    <input type="text" name="name" :value="editRole.name" :readonly="editRole.is_system"
                           :class="editRole.is_system ? 'bg-gray-50 dark:bg-gray-700 text-textsecondary cursor-not-allowed' : ''"
                           class="w-full px-4 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700/50 text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary dark:focus:border-primary transition-all duration-200">
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-textsecondary dark:text-gray-400 mb-3">Permissions</label>
                <div class="space-y-4">
                    @foreach($permissionGroups as $group => $perms)
                    <div>
                        <p class="text-xs font-semibold text-textprimary dark:text-gray-200 mb-2">{{ $group }}</p>
                        <div class="flex flex-wrap gap-2">
                            @foreach($perms as $perm)
                            <label class="flex items-center gap-2 cursor-pointer px-3 py-1.5 rounded-xl border border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                                   :class="hasPermission('{{ $perm }}') ? 'bg-primary/10 border-primary/30 dark:bg-primary/20' : ''">
                                <input type="checkbox" name="permissions[]" value="{{ $perm }}"
                                       :checked="hasPermission('{{ $perm }}')"
                                       @change="togglePermission('{{ $perm }}')"
                                       class="w-3.5 h-3.5 rounded text-primary focus:ring-primary/30">
                                <span class="text-xs font-medium text-textprimary dark:text-gray-100">{{ str_replace('_', ' ', $perm) }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="flex-1 py-2.5 font-semibold bg-accent hover:bg-yellow-500 text-black rounded-xl transition-colors active:scale-95">Save Role</button>
                <button type="button" @click="editModal = false" class="flex-1 py-2.5 font-semibold border border-gray-200 dark:border-gray-600 text-textsecondary dark:text-gray-400 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">Cancel</button>
            </div>
        </form>
    </div>
</div>

{{-- Roles Table --}}
<x-admin.card>
    <x-slot:header>
        <h3 class="font-heading font-semibold text-textprimary dark:text-gray-100">Roles</h3>
    </x-slot:header>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                <tr>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Role</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Name (slug)</th>
                    <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Users</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Permissions</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($roles as $role)
                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors">
                    <td class="px-6 py-3">
                        <div class="flex items-center gap-2">
                            <span class="font-semibold text-textprimary dark:text-gray-100">{{ $role->display_name }}</span>
                            @if($role->is_system)
                                <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">System</span>
                            @endif
                        </div>
                    </td>
                    <td class="px-4 py-3 font-mono text-sm text-textsecondary dark:text-gray-400">{{ $role->name }}</td>
                    <td class="px-4 py-3 text-center">
                        <a href="{{ route('admin.users.index', ['role' => $role->name]) }}"
                           class="font-semibold text-primary hover:underline">{{ $role->users_count }}</a>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex flex-wrap gap-1 max-w-xs">
                            @foreach(array_slice($role->permissions ?? [], 0, 4) as $perm)
                                <span class="px-1.5 py-0.5 text-xs rounded bg-gray-100 dark:bg-gray-700 text-textsecondary dark:text-gray-400">{{ str_replace('_', ' ', $perm) }}</span>
                            @endforeach
                            @if(count($role->permissions ?? []) > 4)
                                <span class="px-1.5 py-0.5 text-xs rounded bg-gray-100 dark:bg-gray-700 text-textsecondary dark:text-gray-400">+{{ count($role->permissions) - 4 }} more</span>
                            @endif
                            @if(empty($role->permissions))
                                <span class="text-xs text-textsecondary dark:text-gray-500">None</span>
                            @endif
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-1.5">
                            @if($role->tenant_id !== null || auth()->user()->is_super_admin)
                            <button @click="openEdit({{ json_encode(['id' => $role->id, 'name' => $role->name, 'display_name' => $role->display_name, 'permissions' => $role->permissions ?? [], 'is_system' => $role->is_system]) }})"
                                    type="button"
                                    class="px-2.5 py-1 text-xs font-medium bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors">Edit</button>
                            @else
                            <span class="px-2.5 py-1 text-xs text-textsecondary dark:text-gray-400" title="Shared starter role — create your own role to customise">Built-in</span>
                            @endif
                            @if(!$role->is_system)
                            <form method="POST" action="{{ route('admin.roles.destroy', $role) }}" onsubmit="return confirm('Delete role {{ $role->display_name }}?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="px-2.5 py-1 text-xs font-medium text-danger hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors">Delete</button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-6 py-12 text-center text-textsecondary dark:text-gray-400">No roles found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-admin.card>

{{-- Create Role --}}
<div class="mt-6">
<x-admin.card>
    <x-slot:header>
        <h3 class="font-heading font-semibold text-textprimary dark:text-gray-100">Create New Role</h3>
    </x-slot:header>
    <form method="POST" action="{{ route('admin.roles.store') }}"
          x-data="{ displayName: '', name: '' }" class="p-6 space-y-5">
        @csrf

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-medium text-textsecondary dark:text-gray-400 mb-1.5">Display Name <span class="text-danger">*</span></label>
                <input type="text" name="display_name" x-model="displayName"
                       @input="name = displayName.toLowerCase().replace(/\s+/g, '_').replace(/[^a-z0-9_]/g, '')"
                       placeholder="e.g. Store Manager" required
                       class="w-full px-4 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700/50 text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary dark:focus:border-primary transition-all duration-200">
            </div>
            <div>
                <label class="block text-xs font-medium text-textsecondary dark:text-gray-400 mb-1.5">Role Slug <span class="text-danger">*</span></label>
                <input type="text" name="name" x-model="name" placeholder="e.g. store_manager" required
                       class="w-full px-4 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700/50 text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary dark:focus:border-primary transition-all duration-200 font-mono">
            </div>
        </div>

        <div>
            <label class="block text-xs font-medium text-textsecondary dark:text-gray-400 mb-3">Permissions</label>
            <div class="space-y-4">
                @foreach($permissionGroups as $group => $perms)
                <div>
                    <p class="text-xs font-semibold text-textprimary dark:text-gray-200 mb-2">{{ $group }}</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach($perms as $perm)
                        <label class="flex items-center gap-2 cursor-pointer px-3 py-1.5 rounded-xl border border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            <input type="checkbox" name="permissions[]" value="{{ $perm }}"
                                   class="w-3.5 h-3.5 rounded text-primary focus:ring-primary/30">
                            <span class="text-xs font-medium text-textprimary dark:text-gray-100">{{ str_replace('_', ' ', $perm) }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <button type="submit" class="px-6 py-2.5 font-semibold bg-accent hover:bg-yellow-500 text-black rounded-xl transition-colors active:scale-95">Create Role</button>
    </form>
</x-admin.card>
</div>

</div>{{-- end x-data editModal --}}

@endsection
