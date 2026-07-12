<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seeds per-visit payment-arrangement (Payment Timing Policy Phase 7)
 * permissions so existing databases pick them up without a full role reseed.
 * Approval/revocation are finance-manager+ only; self-approval is NOT granted to
 * any role by default. Not granted to clinical roles.
 */
return new class extends Migration
{
    private array $permissions = [
        'visits.payment_arrangement.view',
        'visits.payment_arrangement.request',
        'visits.payment_arrangement.approve',
        'visits.payment_arrangement.reject',
        'visits.payment_arrangement.withdraw',
        'visits.payment_arrangement.revoke',
        'visits.payment_arrangement.history',
        'visits.payment_arrangement.report',
        'visits.payment_arrangement.restore_baseline',
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

        // Finance Manager: full set (approve/reject/revoke/restore).
        Role::where('name', 'Finance Manager')->where('guard_name', 'web')->first()?->givePermissionTo($this->permissions);

        // Accountant: view/request/withdraw/history/report — NO approve/reject/revoke.
        Role::where('name', 'Accountant')->where('guard_name', 'web')->first()?->givePermissionTo([
            'visits.payment_arrangement.view',
            'visits.payment_arrangement.request',
            'visits.payment_arrangement.withdraw',
            'visits.payment_arrangement.history',
            'visits.payment_arrangement.report',
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
