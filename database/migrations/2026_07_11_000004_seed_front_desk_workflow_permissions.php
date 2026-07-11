<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Phase 18C — seeds the call follow-up queue & courier workflow permissions so
 * existing databases pick them up without a full role reseed. Super Admin /
 * Admin receive all; Receptionist and Medical Records Officer receive the
 * operational set.
 */
return new class extends Migration
{
    private array $permissions = [
        'front_desk.calls.followups.view',
        'front_desk.calls.followups.assign',
        'front_desk.calls.followups.complete',
        'front_desk.calls.transfer',
        'front_desk.couriers.workflow.view',
        'front_desk.couriers.dispatch',
        'front_desk.couriers.handover',
        'front_desk.couriers.return',
    ];

    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($this->permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        foreach (['Super Admin', 'Admin', 'Receptionist', 'Medical Records Officer'] as $roleName) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
            $role?->givePermissionTo($this->permissions);
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
