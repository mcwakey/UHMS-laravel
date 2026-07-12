<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seeds Payment Timing operational-cutover (Phase 8) permissions so existing
 * databases pick them up without a full reseed.
 *
 * Settings DEFAULTS are handled by PaymentTimingSettingsSeeder (idempotent) and
 * by the config service's safe fallback (absent = disabled) — deliberately NOT
 * seeded here, so a fresh migration leaves the payment_timing settings group
 * untouched (disabled/legacy-equivalent at runtime).
 */
return new class extends Migration
{
    private array $permissions = [
        'billing.payment_timing.cutover.view',
        'billing.payment_timing.cutover.manage',
        'billing.payment_timing.cutover.activate',
        'billing.payment_timing.cutover.rollback',
    ];

    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($this->permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Super Admin / Admin: all.
        foreach (['Super Admin', 'Admin'] as $roleName) {
            Role::where('name', $roleName)->where('guard_name', 'web')->first()?->givePermissionTo($this->permissions);
        }

        // Finance Manager: view + manage only (NOT activate/rollback by default).
        Role::where('name', 'Finance Manager')->where('guard_name', 'web')->first()?->givePermissionTo([
            'billing.payment_timing.cutover.view',
            'billing.payment_timing.cutover.manage',
        ]);

        // Accountant: view only.
        Role::where('name', 'Accountant')->where('guard_name', 'web')->first()?->givePermissionTo([
            'billing.payment_timing.cutover.view',
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
