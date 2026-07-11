<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Phase 18B — seeds the visitor-pass print permission so existing databases pick
 * it up without a full role reseed. Granted to Super Admin / Admin plus the
 * front-desk operational roles (Receptionist, Medical Records Officer).
 */
return new class extends Migration
{
    private string $permission = 'front_desk.visitors.print_pass';

    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Permission::firstOrCreate(['name' => $this->permission, 'guard_name' => 'web']);

        foreach (['Super Admin', 'Admin', 'Receptionist', 'Medical Records Officer'] as $roleName) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
            $role?->givePermissionTo($this->permission);
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
