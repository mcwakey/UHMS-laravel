<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\PermissionMeta;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::withCount('permissions', 'users')->get();

        return view('roles.index', compact('roles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
        ]);

        Role::create($validated);

        return redirect()->route('admin.roles.index')
            ->with('success', 'Role created successfully.');
    }

    public function update(Request $request, Role $role)
    {
        if (in_array($role->name, ['Super Admin', 'Admin'])) {
            return redirect()->back()->with('error', 'Cannot rename system roles.');
        }

        $validated = $request->validate([
            'name' => "required|string|max:255|unique:roles,name,{$role->id}",
        ]);

        $role->update($validated);

        return redirect()->route('admin.roles.index')
            ->with('success', 'Role updated successfully.');
    }

    public function destroy(Role $role)
    {
        if (in_array($role->name, ['Super Admin', 'Admin'])) {
            return redirect()->back()->with('error', 'Cannot delete system roles.');
        }

        if ($role->users()->exists()) {
            return redirect()->back()->with('error', 'Cannot delete role with assigned users.');
        }

        $role->delete();

        return redirect()->route('admin.roles.index')
            ->with('success', 'Role deleted successfully.');
    }

    public function permissions(Role $role)
    {
        // Group by *resolved* module (PermissionMeta) so overrides are honoured.
        $permissions = Permission::orderBy('name')->get()->groupBy(function ($permission) {
            return PermissionMeta::module($permission->name);
        })->sortKeys();

        // Decorate with description + risk so the view doesn't need to read config.
        $permissions = $permissions->map(function ($group) {
            return $group->map(function ($p) {
                $p->meta_description = PermissionMeta::description($p->name);
                $p->meta_risk        = PermissionMeta::risk($p->name);
                return $p;
            });
        });

        $rolePermissions = $role->permissions->pluck('name')->toArray();
        $riskLevels      = config('permissions.risk_levels', []);

        return view('roles.permissions', compact('role', 'permissions', 'rolePermissions', 'riskLevels'));
    }

    public function updatePermissions(Request $request, Role $role)
    {
        $validated = $request->validate([
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        $role->syncPermissions($validated['permissions'] ?? []);

        return redirect()->route('admin.roles.permissions', $role)
            ->with('success', 'Permissions updated successfully.');
    }
}
