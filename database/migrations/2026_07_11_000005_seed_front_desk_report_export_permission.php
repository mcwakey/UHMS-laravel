<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Phase 18D — seeds the front desk report export permission and grants report
 * viewing to the Receptionist so existing databases pick up the reporting
 * feature without a full role reseed.
 *
 * Role defaults: Super Admin / Admin (view + export), Medical Records Officer
 * (view + export), Receptionist (view only).
 */
return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Permission::firstOrCreate(['name' => 'front_desk.reports.view', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'front_desk.reports.export', 'guard_name' => 'web']);

        foreach (['Super Admin', 'Admin', 'Medical Records Officer'] as $roleName) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
            $role?->givePermissionTo(['front_desk.reports.view', 'front_desk.reports.export']);
        }

        $receptionist = Role::where('name', 'Receptionist')->where('guard_name', 'web')->first();
        $receptionist?->givePermissionTo('front_desk.reports.view');

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (Role::where('guard_name', 'web')->get() as $role) {
            $role->revokePermissionTo('front_desk.reports.export');
        }

        Permission::where('name', 'front_desk.reports.export')->where('guard_name', 'web')->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
