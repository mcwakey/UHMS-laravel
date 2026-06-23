<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Services\RolePermissionAuditService;
use App\Support\PermissionMeta;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function __construct(private RolePermissionAuditService $audit) {}

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

        $role = Role::create($validated);
        $this->audit->roleCreated($role);

        return redirect()->route('admin.roles.index')
            ->with('success', __('messages.roles.created'));
    }

    public function update(Request $request, Role $role)
    {
        if (in_array($role->name, ['Super Admin', 'Admin'])) {
            return redirect()->back()->with('error', __('messages.roles.cannot_rename_system'));
        }

        $validated = $request->validate([
            'name' => "required|string|max:255|unique:roles,name,{$role->id}",
        ]);

        $old = ['name' => $role->name, 'guard_name' => $role->guard_name];
        $role->update($validated);
        $this->audit->roleUpdated($role, $old, ['name' => $role->name, 'guard_name' => $role->guard_name]);

        return redirect()->route('admin.roles.index')
            ->with('success', __('messages.roles.updated'));
    }

    public function destroy(Role $role)
    {
        if (in_array($role->name, ['Super Admin', 'Admin'])) {
            return redirect()->back()->with('error', __('messages.roles.cannot_delete_system'));
        }

        if ($role->users()->exists()) {
            return redirect()->back()->with('error', __('messages.roles.cannot_delete_assigned'));
        }

        $role->delete();
        $this->audit->roleDeleted($role);

        return redirect()->route('admin.roles.index')
            ->with('success', __('messages.roles.deleted'));
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

        $requested = $validated['permissions'] ?? [];
        $before = $role->permissions->pluck('name')->all();
        $this->authorizeCriticalPermissionChange($request, $before, $requested);

        $role->syncPermissions($requested);
        $this->audit->rolePermissionsUpdated($role, $before, $role->fresh()->permissions->pluck('name')->all());

        return redirect()->route('admin.roles.permissions', $role)
            ->with('success', __('messages.roles.permissions_updated'));
    }

    private function authorizeCriticalPermissionChange(Request $request, array $current, array $requested): void
    {
        $criticalCurrent = $this->criticalPermissionNames($current);
        $criticalRequested = $this->criticalPermissionNames($requested);

        sort($criticalCurrent);
        sort($criticalRequested);

        if ($criticalCurrent !== $criticalRequested) {
            abort_unless($request->user()?->can('permissions.assign_critical'), 403);
        }
    }

    /**
     * @param  array<int, string>  $permissions
     * @return array<int, string>
     */
    private function criticalPermissionNames(array $permissions): array
    {
        return array_values(array_filter(
            array_unique($permissions),
            fn (string $permission) => PermissionMeta::risk($permission) === 'CRITICAL'
        ));
    }
}
