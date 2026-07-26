<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Phase 14R.6 — additive VIEW-ONLY permissions.
 *
 * There is deliberately no mutation permission here: nothing in 14R.6 is
 * clinician-writable. Snapshot viewing requires existing consultation summary
 * access AND the summary permission, so a snapshot can never become a
 * privilege-escalation path into records the user could not otherwise see.
 */
return new class extends Migration
{
    /** @var list<string> */
    private array $permissions = [
        'consultation.maternity_context.summary.view',
        'consultation.maternity_context.readiness.view',
    ];

    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($this->permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        foreach (['Super Admin', 'Admin'] as $roleName) {
            Role::where('name', $roleName)->where('guard_name', 'web')->first()
                ?->givePermissionTo($this->permissions);
        }

        // Clinical roles: only where the role can already see BOTH the
        // consultation and the maternity record behind it.
        foreach (Role::with('permissions')->where('guard_name', 'web')->get() as $role) {
            if (in_array($role->name, ['Super Admin', 'Admin'], true)) {
                continue;
            }

            $held = $role->permissions->pluck('name');

            $canSeeConsultation = $held->contains('consultation.access')
                || $held->contains('consultations.view');
            $canSeeMaternityContext = $held->contains('consultation.maternity_context.view');

            if ($canSeeConsultation && $canSeeMaternityContext) {
                $role->givePermissionTo($this->permissions);
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
