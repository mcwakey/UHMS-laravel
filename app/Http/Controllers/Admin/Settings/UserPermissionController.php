<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\RolePermissionAuditService;
use App\Support\PermissionMeta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Per-user direct permission overrides (Spatie `givePermissionTo` /
 * `revokePermissionTo`). These are *additive* on top of the user's roles —
 * they never remove a role-granted permission.
 *
 * Gated by the `permissions.assign` permission (CRITICAL).
 */
class UserPermissionController extends Controller
{
    public function __construct(private RolePermissionAuditService $audit) {}

    public function edit(User $user)
    {
        Gate::authorize('permissions.assign');

        $user->load('roles', 'permissions');

        // Permissions inherited via role assignments — not editable here.
        $inherited = $user->getPermissionsViaRoles()->pluck('name')->all();

        // Direct permissions — editable.
        $direct = $user->permissions->pluck('name')->all();

        $catalogue = Permission::orderBy('name')->get()
            ->map(function ($p) use ($inherited, $direct) {
                return [
                    'id'          => $p->id,
                    'name'        => $p->name,
                    'module'      => PermissionMeta::module($p->name),
                    'description' => PermissionMeta::description($p->name),
                    'risk'        => PermissionMeta::risk($p->name),
                    'inherited'   => in_array($p->name, $inherited, true),
                    'direct'      => in_array($p->name, $direct, true),
                ];
            })
            ->groupBy('module')
            ->sortKeys();

        $riskLevels = config('permissions.risk_levels', []);

        return view('admin.users.permissions', compact('user', 'catalogue', 'riskLevels'));
    }

    public function update(Request $request, User $user)
    {
        Gate::authorize('permissions.assign');

        $validated = $request->validate([
            'permissions'   => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
            'reason'        => 'required|string|max:500',
        ]);

        $names = $validated['permissions'] ?? [];
        $before = $user->permissions()->pluck('name')->all();
        $this->authorizeCriticalPermissionChange($request, $before, $names);

        // Sync only direct permissions (NOT role-inherited ones).
        $user->syncPermissions($names);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Security audit trail via the central funnel (replaces the old ad-hoc
        // activity('user-permissions') call): structured ROLES/PERMISSIONS event
        // with diff + CRITICAL-permission detection + the mandatory reason.
        $this->audit->userPermissionsUpdated(
            $user,
            $before,
            $user->fresh()->permissions->pluck('name')->all(),
            $validated['reason'],
        );

        return redirect()->route('admin.users.permissions.edit', $user)
            ->with('success', __('messages.user_permissions.updated'));
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
