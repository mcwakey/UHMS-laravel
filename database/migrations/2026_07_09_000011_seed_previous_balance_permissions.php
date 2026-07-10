<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seeds the previous-visit outstanding balance & cross-visit payment allocation
 * permissions so existing databases pick them up without a full role reseed.
 */
return new class extends Migration
{
    private array $permissions = [
        'billing.previous_balance.view',
        'billing.previous_balance.amount.view',
        'billing.previous_balance.flag.view',
        'billing.previous_balance.override',
        'billing.payment.allocate_cross_visit',
        'billing.payment.allocate_manual',
        'billing.patient_statement.view',
        'billing.patient_statement.print',
        'billing.patient_statement.export',
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

        $grants = [
            'Cashier' => [
                'billing.previous_balance.view', 'billing.previous_balance.amount.view',
                'billing.previous_balance.flag.view',
                'billing.payment.allocate_cross_visit', 'billing.payment.allocate_manual',
                'billing.patient_statement.view', 'billing.patient_statement.print',
            ],
            'Accountant' => $this->permissions,
            'Finance Manager' => $this->permissions,
            'Receptionist' => ['billing.previous_balance.flag.view'],
            'Doctor' => ['billing.previous_balance.flag.view'],
            'Consultant' => ['billing.previous_balance.flag.view'],
            'Specialist' => ['billing.previous_balance.flag.view'],
            'Physician Assistant' => ['billing.previous_balance.flag.view'],
            'Nurse' => ['billing.previous_balance.flag.view'],
            'Ward Nurse' => ['billing.previous_balance.flag.view'],
            'Emergency Doctor' => ['billing.previous_balance.flag.view'],
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
