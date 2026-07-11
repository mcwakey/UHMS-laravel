<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seeds the Front Desk Operations (Phase 18A) permissions so existing databases
 * pick them up without a full role reseed. Super Admin / Admin receive all;
 * Receptionist and Medical Records Officer receive the operational set.
 */
return new class extends Migration
{
    private array $permissions = [
        'front_desk.view',
        'front_desk.dashboard.view',
        'front_desk.visitors.view',
        'front_desk.visitors.create',
        'front_desk.visitors.update',
        'front_desk.visitors.checkout',
        'front_desk.calls.view',
        'front_desk.calls.create',
        'front_desk.calls.update',
        'front_desk.couriers.view',
        'front_desk.couriers.create',
        'front_desk.couriers.update',
        'front_desk.couriers.deliver',
        'front_desk.reports.view',
    ];

    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($this->permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        foreach (['Super Admin', 'Admin'] as $roleName) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
            $role?->givePermissionTo($this->permissions);
        }

        $operational = [
            'front_desk.view', 'front_desk.dashboard.view',
            'front_desk.visitors.view', 'front_desk.visitors.create', 'front_desk.visitors.update', 'front_desk.visitors.checkout',
            'front_desk.calls.view', 'front_desk.calls.create', 'front_desk.calls.update',
            'front_desk.couriers.view', 'front_desk.couriers.create', 'front_desk.couriers.update', 'front_desk.couriers.deliver',
        ];

        $grants = [
            'Receptionist' => $operational,
            'Medical Records Officer' => array_merge($operational, ['front_desk.reports.view']),
        ];

        foreach ($grants as $roleName => $permissions) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
            $role?->givePermissionTo($permissions);
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
