<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $checkin = Permission::firstOrCreate([
            'name' => 'appointments.checkin',
            'guard_name' => 'web',
        ]);
        $create = Permission::query()
            ->where('name', 'appointments.create')
            ->where('guard_name', 'web')
            ->first();

        if ($create) {
            foreach ($create->roles as $role) {
                $role->givePermissionTo($checkin);
            }

            foreach ($create->users as $user) {
                $user->givePermissionTo($checkin);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permission = Permission::query()
            ->where('name', 'appointments.checkin')
            ->where('guard_name', 'web')
            ->first();

        if ($permission) {
            foreach ($permission->roles as $role) {
                $role->revokePermissionTo($permission);
            }

            foreach ($permission->users as $user) {
                $user->revokePermissionTo($permission);
            }

            $permission->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
