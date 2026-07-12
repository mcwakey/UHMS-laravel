<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seeds Patient Financial-Risk (Payment Timing Policy Phase 5) permissions so
 * existing databases pick them up without a full role reseed.
 *
 * This is sensitive administrative finance data — it is deliberately NOT granted
 * to clinical or reception roles. Super Admin / Admin receive all; Finance
 * Manager receives the full set (including clear); Accountant receives the
 * finance-operations subset (no clear).
 */
return new class extends Migration
{
    private array $permissions = [
        'patients.financial_risk.view',
        'patients.financial_risk.manage',
        'patients.financial_risk.review',
        'patients.financial_risk.clear',
        'patients.financial_risk.history',
        'patients.financial_risk.report',
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

        Role::where('name', 'Finance Manager')->where('guard_name', 'web')->first()?->givePermissionTo($this->permissions);

        $accountantSubset = [
            'patients.financial_risk.view',
            'patients.financial_risk.manage',
            'patients.financial_risk.review',
            'patients.financial_risk.history',
            'patients.financial_risk.report',
        ];
        Role::where('name', 'Accountant')->where('guard_name', 'web')->first()?->givePermissionTo($accountantSubset);

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
