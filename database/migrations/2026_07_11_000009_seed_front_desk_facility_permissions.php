<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Phase 18E — seeds the shift handover, lost & found and incident desk
 * permissions so existing databases pick them up without a full role reseed.
 *
 * Role defaults: Super Admin / Admin — all; Receptionist — operational set;
 * Medical Records Officer — view-only.
 */
return new class extends Migration
{
    private array $all = [
        'front_desk.handovers.view', 'front_desk.handovers.create', 'front_desk.handovers.update',
        'front_desk.handovers.submit', 'front_desk.handovers.accept', 'front_desk.handovers.cancel',
        'front_desk.lost_found.view', 'front_desk.lost_found.create', 'front_desk.lost_found.update',
        'front_desk.lost_found.claim', 'front_desk.lost_found.release', 'front_desk.lost_found.cancel',
        'front_desk.incidents.view', 'front_desk.incidents.create', 'front_desk.incidents.update',
        'front_desk.incidents.assign', 'front_desk.incidents.escalate', 'front_desk.incidents.resolve',
        'front_desk.incidents.cancel',
    ];

    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($this->all as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        foreach (['Super Admin', 'Admin'] as $roleName) {
            Role::where('name', $roleName)->where('guard_name', 'web')->first()?->givePermissionTo($this->all);
        }

        $receptionist = [
            'front_desk.handovers.view', 'front_desk.handovers.create', 'front_desk.handovers.update',
            'front_desk.handovers.submit', 'front_desk.handovers.accept',
            'front_desk.lost_found.view', 'front_desk.lost_found.create', 'front_desk.lost_found.update',
            'front_desk.lost_found.claim', 'front_desk.lost_found.release',
            'front_desk.incidents.view', 'front_desk.incidents.create', 'front_desk.incidents.update',
        ];
        Role::where('name', 'Receptionist')->where('guard_name', 'web')->first()?->givePermissionTo($receptionist);

        Role::where('name', 'Medical Records Officer')->where('guard_name', 'web')->first()
            ?->givePermissionTo(['front_desk.handovers.view', 'front_desk.lost_found.view', 'front_desk.incidents.view']);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (Role::where('guard_name', 'web')->get() as $role) {
            $role->revokePermissionTo($this->all);
        }

        Permission::whereIn('name', $this->all)->where('guard_name', 'web')->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
