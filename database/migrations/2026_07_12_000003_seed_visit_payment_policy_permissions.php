<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seeds Visit Payment Policy (Payment Timing Policy Phase 6) permissions so
 * existing databases pick them up without a full role reseed. These expose an
 * OBSERVATIONAL record only — `refresh` never means approve/activate. Not
 * granted to clinical or reception roles.
 */
return new class extends Migration
{
    private array $permissions = [
        'visits.payment_policy.view',
        'visits.payment_policy.history',
        'visits.payment_policy.report',
        'visits.payment_policy.refresh',
    ];

    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($this->permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        foreach (['Super Admin', 'Admin'] as $roleName) {
            Role::where('name', $roleName)->where('guard_name', 'web')->first()?->givePermissionTo($this->permissions);
        }

        // Finance Manager: view/history/report/refresh.
        Role::where('name', 'Finance Manager')->where('guard_name', 'web')->first()?->givePermissionTo($this->permissions);

        // Accountant: view/history/report (no refresh).
        Role::where('name', 'Accountant')->where('guard_name', 'web')->first()?->givePermissionTo([
            'visits.payment_policy.view',
            'visits.payment_policy.history',
            'visits.payment_policy.report',
        ]);

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
