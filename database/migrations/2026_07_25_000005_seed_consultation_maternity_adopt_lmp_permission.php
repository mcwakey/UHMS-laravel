<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Phase 14R.4 — additive permission for the explicit one-way Gynaecology LMP
 * adoption action.
 *
 * Half of the requirement only: the action also demands
 * `maternity.pregnancy.update`. The bridge never grants a maternity operation
 * the user otherwise lacks.
 */
return new class extends Migration
{
    private string $permission = 'consultation.maternity_context.adopt_lmp';

    private string $requiredMaternityPermission = 'maternity.pregnancy.update';

    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Permission::firstOrCreate(['name' => $this->permission, 'guard_name' => 'web']);

        foreach (['Super Admin', 'Admin'] as $roleName) {
            Role::where('name', $roleName)->where('guard_name', 'web')->first()
                ?->givePermissionTo($this->permission);
        }

        // Clinical roles: only where the role already holds both the bridge
        // link ability and the underlying maternity update permission.
        foreach (Role::with('permissions')->where('guard_name', 'web')->get() as $role) {
            if (in_array($role->name, ['Super Admin', 'Admin'], true)) {
                continue;
            }

            $held = $role->permissions->pluck('name');

            if ($held->contains('consultation.maternity_context.link')
                && $held->contains($this->requiredMaternityPermission)) {
                $role->givePermissionTo($this->permission);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (Role::where('guard_name', 'web')->get() as $role) {
            $role->revokePermissionTo($this->permission);
        }

        Permission::where('name', $this->permission)->where('guard_name', 'web')->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
