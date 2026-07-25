<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Phase 14R.3 — additive bridge ACTION permissions.
 *
 * Each of these is only half of the requirement: the action also demands the
 * underlying maternity permission (maternity.pregnancy.create /
 * maternity.anc.record / maternity.labor.start). The bridge must never grant
 * access to a maternity operation the user otherwise lacks.
 *
 * Existing 14R.2 permissions (view/link/unlink) are retained untouched.
 */
return new class extends Migration
{
    /** @var array<string, string> bridge permission => required maternity permission */
    private array $permissions = [
        'consultation.maternity_context.create_profile' => 'maternity.pregnancy.create',
        'consultation.maternity_context.record_anc' => 'maternity.anc.record',
        'consultation.maternity_context.start_labor' => 'maternity.labor.start',
    ];

    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (array_keys($this->permissions) as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        foreach (['Super Admin', 'Admin'] as $roleName) {
            Role::where('name', $roleName)->where('guard_name', 'web')->first()
                ?->givePermissionTo(array_keys($this->permissions));
        }

        // Clinical roles: grant a bridge action ONLY to roles that already hold
        // both the maternity permission it wraps and the bridge link ability.
        foreach (Role::with('permissions')->where('guard_name', 'web')->get() as $role) {
            if (in_array($role->name, ['Super Admin', 'Admin'], true)) {
                continue;
            }

            $held = $role->permissions->pluck('name');

            if (! $held->contains('consultation.maternity_context.link')) {
                continue;
            }

            foreach ($this->permissions as $bridge => $required) {
                if ($held->contains($required)) {
                    $role->givePermissionTo($bridge);
                }
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (Role::where('guard_name', 'web')->get() as $role) {
            $role->revokePermissionTo(array_keys($this->permissions));
        }

        Permission::whereIn('name', array_keys($this->permissions))
            ->where('guard_name', 'web')->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
