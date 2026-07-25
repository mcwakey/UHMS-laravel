<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Phase 14R.2 — additive bridge permissions.
 *
 * These gate the LINK itself. They never escalate access to maternity data:
 * a future UI action must require both the bridge permission and the
 * underlying maternity permission (e.g. maternity.anc.record).
 *
 * create_profile / record_anc / start_labor are intentionally NOT added here —
 * they belong to the workspace phase that uses them (14R.3).
 */
return new class extends Migration
{
    /** @var list<string> */
    private array $permissions = [
        'consultation.maternity_context.view',
        'consultation.maternity_context.link',
        'consultation.maternity_context.unlink',
    ];

    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($this->permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Admins receive all bridge permissions.
        foreach (['Super Admin', 'Admin'] as $roleName) {
            Role::where('name', $roleName)->where('guard_name', 'web')->first()
                ?->givePermissionTo($this->permissions);
        }

        // Clinical roles: only grant to roles that can already see both sides.
        // Viewing requires consultation access; linking additionally requires
        // the ability to view maternity records.
        foreach (Role::with('permissions')->where('guard_name', 'web')->get() as $role) {
            if (in_array($role->name, ['Super Admin', 'Admin'], true)) {
                continue;
            }

            // Read the role's own permission names — hasPermissionTo() throws
            // when a permission name does not exist in this installation.
            $held = $role->permissions->pluck('name');

            $canSeeConsultation = $held->contains('consultation.access')
                || $held->contains('consultations.view');
            $canSeeMaternity = $held->contains('maternity.view')
                || $held->contains('maternity.pregnancy.view');

            if (! $canSeeConsultation || ! $canSeeMaternity) {
                continue;
            }

            $role->givePermissionTo('consultation.maternity_context.view');

            // Link/unlink follow the existing maternity write convention.
            if ($held->contains('maternity.pregnancy.update')) {
                $role->givePermissionTo([
                    'consultation.maternity_context.link',
                    'consultation.maternity_context.unlink',
                ]);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (Role::where('guard_name', 'web')->get() as $role) {
            $role->revokePermissionTo($this->permissions);
        }

        Permission::whereIn('name', $this->permissions)->where('guard_name', 'web')->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
