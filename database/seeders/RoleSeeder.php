<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Define permissions grouped by module
        $permissions = [
            // Patient module
            'patients.view',
            'patients.create',
            'patients.edit',
            'patients.delete',

            // Visit module
            'visits.view',
            'visits.create',
            'visits.edit',
            'visits.transition',

            // Queue module
            'queue.view',
            'queue.manage',

            // Consultation / EHR
            'consultations.view',
            'consultations.create',
            'consultations.edit',

            // Vitals
            'vitals.view',
            'vitals.create',

            // Prescriptions
            'prescriptions.view',
            'prescriptions.create',
            'prescriptions.edit',

            // Lab
            'lab.requests.view',
            'lab.requests.create',
            'lab.results.view',
            'lab.results.create',
            'lab.tests.manage',

            // Pharmacy
            'pharmacy.dispensing.view',
            'pharmacy.dispensing.create',
            'pharmacy.drugs.manage',
            'pharmacy.stock.manage',

            // Billing
            'invoices.view',
            'invoices.create',
            'invoices.edit',
            'payments.view',
            'payments.create',
            'services.manage',

            // Users & Roles
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',
            'roles.manage',

            // Departments
            'departments.view',
            'departments.manage',

            // Appointments
            'appointments.view',
            'appointments.create',
            'appointments.edit',
            'appointments.delete',

            // Claims & Insurance
            'claims.view',
            'claims.create',
            'claims.approve',
            'claims.export',

            // Store & Procurement
            'store.purchase.view',
            'store.purchase.create',
            'store.purchase.approve',
            'store.transfer.view',
            'store.transfer.create',

            // Accounts & Finance
            'accounts.manage',
            'accounts.entries.view',
            'accounts.entries.create',
            'accounts.entries.approve',
            'accounts.cashier',

            // Ward & Inpatient
            'ward.view',
            'ward.manage',
            'ward.admit',
            'ward.discharge',
            'beds.view',
            'beds.manage',

            // HR & Payroll
            'hr.employees.view',
            'hr.employees.create',
            'hr.employees.edit',
            'hr.leave.view',
            'hr.leave.create',
            'hr.leave.approve',
            'hr.payroll.view',
            'hr.payroll.process',
            'hr.attendance.view',
            'hr.attendance.manage',

            // Notifications
            'notifications.view',

            // Clinical Coding & Procedures
            'icd.manage',
            'procedures.view',
            'procedures.create',
            'procedures.edit',

            // Reports
            'reports.view',

            // Settings
            'settings.manage',
        ];

        // Create permissions
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles and assign permissions
        $superAdmin = Role::firstOrCreate(['name' => 'Super Admin']);
        $superAdmin->givePermissionTo(Permission::all());

        $admin = Role::firstOrCreate(['name' => 'Admin']);
        $admin->givePermissionTo(Permission::all());

        $doctor = Role::firstOrCreate(['name' => 'Doctor']);
        $doctor->givePermissionTo([
            'patients.view',
            'visits.view', 'visits.transition',
            'consultations.view', 'consultations.create', 'consultations.edit',
            'vitals.view',
            'prescriptions.view', 'prescriptions.create', 'prescriptions.edit',
            'lab.requests.view', 'lab.requests.create',
            'lab.results.view',
            'queue.view',
            'ward.view', 'ward.admit', 'ward.discharge',
            'appointments.view', 'appointments.create', 'appointments.edit',
            'notifications.view',
            'procedures.view', 'procedures.create',
        ]);

        $nurse = Role::firstOrCreate(['name' => 'Nurse']);
        $nurse->givePermissionTo([
            'patients.view',
            'visits.view', 'visits.transition',
            'vitals.view', 'vitals.create',
            'queue.view', 'queue.manage',
            'prescriptions.view',
            'ward.view', 'beds.view',
            'notifications.view',
            'procedures.view',
        ]);

        $receptionist = Role::firstOrCreate(['name' => 'Receptionist']);
        $receptionist->givePermissionTo([
            'patients.view', 'patients.create', 'patients.edit',
            'visits.view', 'visits.create', 'visits.edit', 'visits.transition',
            'queue.view', 'queue.manage',
            'invoices.view',
            'appointments.view', 'appointments.create', 'appointments.edit',
            'notifications.view',
        ]);

        $labTech = Role::firstOrCreate(['name' => 'Lab Technician']);
        $labTech->givePermissionTo([
            'patients.view',
            'visits.view',
            'lab.requests.view',
            'lab.results.view', 'lab.results.create',
            'lab.tests.manage',
            'queue.view',
            'notifications.view',
        ]);

        $pharmacist = Role::firstOrCreate(['name' => 'Pharmacist']);
        $pharmacist->givePermissionTo([
            'patients.view',
            'visits.view', 'visits.transition',
            'prescriptions.view',
            'pharmacy.dispensing.view', 'pharmacy.dispensing.create',
            'pharmacy.drugs.manage', 'pharmacy.stock.manage',
            'store.transfer.view',
            'queue.view',
            'notifications.view',
        ]);

        $accountant = Role::firstOrCreate(['name' => 'Accountant']);
        $accountant->givePermissionTo([
            'patients.view',
            'visits.view',
            'invoices.view', 'invoices.create', 'invoices.edit',
            'payments.view', 'payments.create',
            'services.manage',
            'reports.view',
            'claims.view',
            'accounts.manage',
            'accounts.entries.view', 'accounts.entries.create', 'accounts.entries.approve',
            'accounts.cashier',
            'notifications.view',
        ]);

        $claimsOfficer = Role::firstOrCreate(['name' => 'Claims Officer']);
        $claimsOfficer->givePermissionTo([
            'patients.view',
            'visits.view',
            'invoices.view',
            'claims.view', 'claims.create', 'claims.approve', 'claims.export',
            'reports.view',
            'notifications.view',
        ]);

        $storeKeeper = Role::firstOrCreate(['name' => 'Store Keeper']);
        $storeKeeper->givePermissionTo([
            'patients.view',
            'store.purchase.view', 'store.purchase.create', 'store.purchase.approve',
            'store.transfer.view', 'store.transfer.create',
            'pharmacy.drugs.manage', 'pharmacy.stock.manage',
            'notifications.view',
        ]);

        $hrManager = Role::firstOrCreate(['name' => 'HR Manager']);
        $hrManager->givePermissionTo([
            'hr.employees.view', 'hr.employees.create', 'hr.employees.edit',
            'hr.leave.view', 'hr.leave.create', 'hr.leave.approve',
            'hr.payroll.view', 'hr.payroll.process',
            'hr.attendance.view', 'hr.attendance.manage',
            'reports.view',
            'notifications.view',
        ]);
    }
}
