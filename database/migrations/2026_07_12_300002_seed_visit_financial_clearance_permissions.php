<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private array $permissions = [
        'visits.financial_clearance.view', 'visits.financial_clearance.assess', 'visits.financial_clearance.close',
        'visits.financial_clearance.history', 'visits.financial_clearance.report',
        'visits.financial_clearance_exception.request', 'visits.financial_clearance_exception.approve',
        'visits.financial_clearance_exception.reject', 'visits.financial_clearance_exception.withdraw',
        'visits.financial_clearance_exception.revoke', 'visits.financial_clearance_exception.history',
        'billing.financial_clearance.settings.view', 'billing.financial_clearance.settings.manage',
        'billing.financial_clearance.settings.activate', 'billing.financial_clearance.settings.rollback',
    ];

    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach ($this->permissions as $name) Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        foreach (['Super Admin', 'Admin'] as $name) Role::where('name', $name)->where('guard_name', 'web')->first()?->givePermissionTo($this->permissions);
        Role::where('name', 'Finance Manager')->first()?->givePermissionTo(array_values(array_diff($this->permissions, [
            'billing.financial_clearance.settings.activate', 'billing.financial_clearance.settings.rollback',
        ])));
        Role::where('name', 'Accountant')->first()?->givePermissionTo([
            'visits.financial_clearance.view', 'visits.financial_clearance.assess', 'visits.financial_clearance.history',
            'visits.financial_clearance.report', 'visits.financial_clearance_exception.request',
            'visits.financial_clearance_exception.withdraw', 'visits.financial_clearance_exception.history',
        ]);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        foreach (Role::all() as $role) $role->revokePermissionTo($this->permissions);
        Permission::whereIn('name', $this->permissions)->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
