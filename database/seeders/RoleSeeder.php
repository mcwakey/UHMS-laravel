<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // ------------------------------------------------------------------
        // PERMISSION DEFINITIONS
        // Grouped by module for readability. All permissions use dot-notation.
        // ------------------------------------------------------------------
        $permissions = [
            // ── Patients ─────────────────────────────────────────────────
            'patients.view',
            'patients.create',
            'patients.edit',
            'patients.delete',

            // ── Visits ────────────────────────────────────────────────────
            'visits.view',
            'visits.create',
            'visits.edit',
            'visits.transition',
            'visits.preview',

            // ── Queue ─────────────────────────────────────────────────────
            'queue.view',
            'queue.manage',

            // ── Consultation / Clinical EHR ───────────────────────────────
            'consultations.view',
            'consultations.create',
            'consultations.edit',
            'consultation.routes.view',
            'consultation.routes.create',
            'consultation.routes.activate',
            'consultation.routes.complete',
            'consultation.routes.cancel',
            'consultation.sessions.switch',
            'consultation.sessions.view_all',
            'consultation.entries.create',
            'consultation.entries.edit_own',
            'consultation.entries.delete_own',
            'consultation.entries.edit_any',
            'consultation.entries.delete_any',
            'consultation.entries.view_all',
            'consultation.entries.correct_completed',
            'consultation.hopc.create',
            'consultation.hopc.view',
            'consultation.hopc.edit',
            'consultation.examination.create',
            'consultation.examination.view',
            'consultation.tasks.create',
            'consultation.tasks.view',
            'consultation.tasks.update',
            'medical_patterns.view',
            'medical_patterns.create',
            'medical_patterns.update',
            'medical_patterns.apply',
            // Consultation workflow (for Doctors, Consultants, etc.)
            'consultation.access',          // can enter consultation area
            'consultation.dashboard',       // consultation summary dashboard
            'consultation.queue',           // see & pick up consultation queue
            'consultation.history',         // view patient consultation history
            'consultation.create',          // write a new consultation note
            'consultation.complete',        // mark consultation as complete
            'consultation.refer',           // refer patient to another dept
            'consultation.prescribe',       // prescribe from consultation
            'consultation.request_lab',     // request investigations
            'consultation.request_procedure', // request procedures
            'consultation.view_results',    // view lab/procedure results
            'consultation.view_patient',    // view full patient profile

            // ── Vitals ────────────────────────────────────────────────────
            'vitals.view',
            'vitals.create',

            // ── Prescriptions ─────────────────────────────────────────────
            'prescriptions.view',
            'prescriptions.create',
            'prescriptions.edit',

            // ── Lab / Investigations ──────────────────────────────────────
            'lab.requests.view',
            'lab.requests.create',
            'lab.results.view',
            'lab.results.create',
            'lab.results.verify',
            'lab.tests.manage',

            // ── Pharmacy ──────────────────────────────────────────────────
            'pharmacy.dispensing.view',
            'pharmacy.dispensing.create',
            'pharmacy.drugs.manage',
            'pharmacy.stock.manage',

            // ── Billing & Finance ─────────────────────────────────────────
            'invoices.view',
            'invoices.create',
            'invoices.edit',
            'invoices.void',
            'payments.view',
            'payments.create',
            'payments.void',
            'services.manage',

            // ── Users & Roles ─────────────────────────────────────────────
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',
            'roles.manage',

            // ── Departments ───────────────────────────────────────────────
            'departments.view',
            'departments.manage',
            'departments.create',
            'departments.edit',
            'departments.delete',

            // ── Appointments ──────────────────────────────────────────────
            'appointments.view',
            'appointments.create',
            'appointments.edit',
            'appointments.delete',

            // ── Claims & Insurance ────────────────────────────────────────
            'claims.view',
            'claims.create',
            'claims.approve',
            'claims.export',
            'claims.dashboard.view',
            'claims.eligible.view',
            'claims.prepare',
            'claims.validate',
            'claims.submit',
            'claims.payment.record',
            'claims.cancel',
            'claims.report.view',
            'claims.nhia.view',
            'claims.nhia.prepare',
            'claims.nhia.submit',
            'claims.nhia.export',

            // ── Store & Procurement ───────────────────────────────────────
            'store.purchase.view',          // view suppliers, purchase orders
            'store.purchase.create',        // create/edit purchase orders and supplier records
            'store.purchase.approve',       // approve purchase orders
            'store.purchase.receive',       // receive goods against a purchase order
            'store.transfer.view',          // view stock transfers
            'store.transfer.create',        // create / complete stock transfers

            // ── Purchase Returns ──────────────────────────────────────────
            'store.return.view',            // view purchase returns list and details
            'store.return.create',          // create, edit, post and cancel purchase returns
            'store.return.approve',         // approve purchase returns

            // ── Stock Requisitions ────────────────────────────────────────
            'store.requisition.view',       // view requisitions list and details
            'store.requisition.create',     // create / submit / cancel a requisition
            'store.requisition.approve',    // approve requisition and set approved quantities
            'store.requisition.issue',      // issue stock from main store against a requisition
            'store.requisition.acknowledge', // acknowledge receipt of issued items in department

            // ── Products (master catalogue — Store/Admin only creates) ─────
            'product.view',
            'product.create',
            'product.edit',
            'product.link_departments',
            'product.link_department',      // alias used by some routes
            'product.pricing.manage',       // update base_price, is_billable, insurance prices

            // ── Stock Management ──────────────────────────────────────────
            'stock.location.manage',        // create/edit stock locations
            'stock_location.manage',        // legacy alias kept for old routes
            'stock.view',                   // view stock balances and ledger
            'stock.view_balance',           // view current stock balance summary
            'stock.transfer',               // perform ad-hoc stock transfers
            'stock.adjust',                 // record stock adjustments
            'stock.receive',                // receive stock (direct, not via PO)
            'stock.return',                 // record ad-hoc stock returns to supplier
            'stock.override_negative',      // allow dispensing below zero

            // ── Supplier Ledger ───────────────────────────────────────────
            'supplier.manage',              // create/edit/deactivate suppliers
            'supplier.ledger.view',         // view supplier ledger entries
            'supplier.payment.create',      // record manual payments to suppliers
            'supplier.return.create',       // record manual credit notes / returns

            // ── Accounts & Finance ────────────────────────────────────────
            'accounts.manage',
            'accounts.entries.view',
            'accounts.entries.create',
            'accounts.entries.approve',
            'accounts.cashier',

            // ── Ward & Inpatient ──────────────────────────────────────────
            'ward.view',
            'ward.manage',
            'ward.admit',
            'ward.discharge',
            'ward.consumable.use',          // record ward consumable usage
            'medication_orders.view',
            'medication_orders.manage',
            'medication_orders.stop',
            'medication_orders.hold',
            'medication_administration.view',
            'medication_administration.administer',
            'medication_administration.hold',
            'medication_administration.mark_missed',
            'medication_administration.correct',
            'medication_administration.view_reports',
            'mar_chart.view',
            'mar_chart.print',
            'admission.mar_chart.view',
            'emergency.mar_chart.view',
            'clinical_tasks.view',
            'clinical_tasks.manage',
            'clinical_tasks.complete',
            'clinical_tasks.escalate',
            'clinical_tasks.view_overdue',
            'admission.medication_board.view',
            'emergency.medication_board.view',
            'beds.view',
            'beds.manage',

            // ── HR & Payroll ──────────────────────────────────────────────
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

            // ── Notifications ─────────────────────────────────────────────
            'notifications.view',

            // ── Clinical Coding ───────────────────────────────────────────
            'icd.view',
            'icd.manage',

            // ── Procedure / Theatre Workflow ──────────────────────────────
            'procedures.view',
            'procedures.create',
            'procedures.edit',
            'procedure.request',
            'procedure.view',
            'procedure.accept',
            'procedure.reject',
            'procedure.bill',
            'procedure.schedule',
            'procedure.reschedule',
            'procedure.record_preop',
            'procedure.record_anaesthesia',
            'procedure.record_surgery',
            'procedure.record_postop',
            'procedure.complete',
            'procedure.cancel',
            'procedure.print',
            'procedure.view_report',

            // ── Procedure Catalogue ───────────────────────────────────────
            'procedure.catalogue.view',     // dotted canonical
            'procedure.catalogue.manage',
            'procedure_catalogue.view',     // legacy underscore alias
            'procedure_catalogue.manage',
            'procedure_template.manage',
            'service_consumable.manage',

            // ── Investigation Catalogue ────────────────────────────────────
            'investigation.catalogue.view',
            'investigation.catalogue.manage',

            // ── Consumable Usage ──────────────────────────────────────────
            'consumable_usage.record',      // legacy alias
            'consumable.use',               // canonical

            // ── Analyzer Integration ──────────────────────────────────────
            'analyzer.manage',

            // ── Reports ───────────────────────────────────────────────────
            'reports.view',
            'reports.generate',

            // ── Settings ─────────────────────────────────────────────────
            'settings.view',
            'settings.manage',

            // ── Modules ───────────────────────────────────────────────────
            'modules.manage',
        ];

        // Create all permissions
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // ------------------------------------------------------------------
        // ROLE DEFINITIONS
        // ------------------------------------------------------------------

        // ── Super Admin & Admin ───────────────────────────────────────────
        $superAdmin = Role::firstOrCreate(['name' => 'Super Admin']);
        $superAdmin->syncPermissions(Permission::all());

        $admin = Role::firstOrCreate(['name' => 'Admin']);
        $admin->syncPermissions(Permission::all());

        // ── Doctor ────────────────────────────────────────────────────────
        // Full clinical access including consultation workflow
        $doctorPerms = [
            'patients.view',
            'visits.view', 'visits.transition', 'visits.preview',
            'consultations.view', 'consultations.create', 'consultations.edit',
            'consultation.access', 'consultation.dashboard', 'consultation.queue',
            'consultation.history', 'consultation.create', 'consultation.complete',
            'consultation.refer', 'consultation.prescribe',
            'consultation.request_lab', 'consultation.request_procedure',
            'consultation.view_results', 'consultation.view_patient',
            'consultation.entries.create', 'consultation.entries.edit_own',
            'consultation.entries.delete_own', 'consultation.entries.view_all',
            'consultation.hopc.create', 'consultation.hopc.view', 'consultation.hopc.edit',
            'consultation.examination.create', 'consultation.examination.view',
            'consultation.tasks.create', 'consultation.tasks.view', 'consultation.tasks.update',
            'medical_patterns.view', 'medical_patterns.create', 'medical_patterns.update', 'medical_patterns.apply',
            'vitals.view',
            'prescriptions.view', 'prescriptions.create', 'prescriptions.edit',
            'medication_orders.view', 'medication_orders.manage', 'medication_orders.stop', 'medication_orders.hold',
            'medication_administration.view', 'medication_administration.view_reports',
            'mar_chart.view', 'mar_chart.print',
            'admission.mar_chart.view', 'emergency.mar_chart.view',
            'clinical_tasks.view', 'clinical_tasks.view_overdue',
            'admission.medication_board.view', 'emergency.medication_board.view',
            'lab.requests.view', 'lab.requests.create',
            'lab.results.view',
            'queue.view',
            'ward.view', 'ward.admit', 'ward.discharge',
            'appointments.view', 'appointments.create', 'appointments.edit',
            'notifications.view',
            'procedures.view', 'procedures.create',
            'procedure.request', 'procedure.view', 'procedure.view_report',
            'procedure.record_anaesthesia', 'procedure.record_surgery',
            'procedure.catalogue.view', 'procedure_catalogue.view',
            'consumable_usage.record', 'consumable.use',
            'investigation.catalogue.view',
            'icd.view',
            'product.view',
            'reports.view',
        ];

        $doctor = Role::firstOrCreate(['name' => 'Doctor']);

        // ── Consultant ────────────────────────────────────────────────────
        // Same clinical depth as Doctor; specialised outpatient consultant
        $consultant = Role::firstOrCreate(['name' => 'Consultant']);
        $consultant->syncPermissions($doctorPerms);

        // ── Specialist ────────────────────────────────────────────────────
        $specialist = Role::firstOrCreate(['name' => 'Specialist']);
        $specialist->syncPermissions($doctorPerms);

        // ── Physician Assistant ───────────────────────────────────────────
        // Slightly reduced: no admit/discharge, no procedure recording
        $physicianAssistant = Role::firstOrCreate(['name' => 'Physician Assistant']);
        $physicianAssistant->syncPermissions([
            'patients.view',
            'visits.view', 'visits.transition', 'visits.preview',
            'consultation.access', 'consultation.dashboard', 'consultation.queue',
            'consultation.history', 'consultation.create', 'consultation.complete',
            'consultation.refer', 'consultation.prescribe',
            'consultation.request_lab', 'consultation.request_procedure',
            'consultation.view_results', 'consultation.view_patient',
            'vitals.view',
            'prescriptions.view', 'prescriptions.create',
            'lab.requests.view', 'lab.requests.create',
            'lab.results.view',
            'queue.view',
            'ward.view',
            'appointments.view', 'appointments.create',
            'notifications.view',
            'procedures.view', 'procedure.request', 'procedure.view',
            'procedure.catalogue.view', 'procedure_catalogue.view',
            'investigation.catalogue.view',
            'icd.view',
            'product.view',
        ]);

        // ── Nurse ─────────────────────────────────────────────────────────
        $nurse = Role::firstOrCreate(['name' => 'Nurse']);
        $nurse->syncPermissions([
            'patients.view',
            'visits.view', 'visits.transition', 'visits.preview',
            'vitals.view', 'vitals.create',
            'queue.view', 'queue.manage',
            'prescriptions.view',
            'ward.view', 'beds.view',
            'medication_orders.view',
            'medication_administration.view', 'medication_administration.administer',
            'medication_administration.hold', 'medication_administration.mark_missed',
            'mar_chart.view', 'mar_chart.print',
            'admission.mar_chart.view', 'emergency.mar_chart.view',
            'clinical_tasks.view', 'clinical_tasks.complete', 'clinical_tasks.view_overdue',
            'admission.medication_board.view', 'emergency.medication_board.view',
            'notifications.view',
            'procedures.view',
            'procedure.view', 'procedure.record_preop', 'procedure.record_postop',
            'procedure.catalogue.view', 'procedure_catalogue.view',
            'consumable_usage.record', 'consumable.use',
            'product.view', 'stock.view_balance',
            // Requisitions: nurses can request stock and acknowledge receipt
            'store.requisition.view', 'store.requisition.create', 'store.requisition.acknowledge',
        ]);

        // ── Ward Nurse ────────────────────────────────────────────────────
        // Nurse with expanded inpatient and ward consumable access
        $wardNurse = Role::firstOrCreate(['name' => 'Ward Nurse']);
        $wardNurse->syncPermissions([
            'patients.view',
            'visits.view', 'visits.transition', 'visits.preview',
            'vitals.view', 'vitals.create',
            'queue.view', 'queue.manage',
            'prescriptions.view',
            'ward.view', 'ward.admit', 'ward.discharge', 'ward.manage',
            'beds.view', 'beds.manage',
            'medication_orders.view', 'medication_orders.hold',
            'medication_administration.view', 'medication_administration.administer',
            'medication_administration.hold', 'medication_administration.mark_missed',
            'medication_administration.view_reports',
            'mar_chart.view', 'mar_chart.print',
            'admission.mar_chart.view', 'emergency.mar_chart.view',
            'clinical_tasks.view', 'clinical_tasks.complete', 'clinical_tasks.escalate', 'clinical_tasks.view_overdue',
            'admission.medication_board.view', 'emergency.medication_board.view',
            'notifications.view',
            'procedures.view',
            'procedure.view', 'procedure.record_preop', 'procedure.record_postop',
            'procedure.catalogue.view', 'procedure_catalogue.view',
            'consumable_usage.record', 'consumable.use', 'ward.consumable.use',
            'product.view', 'stock.view_balance',
            // Requisitions: ward nurses can request stock and acknowledge receipt
            'store.requisition.view', 'store.requisition.create', 'store.requisition.acknowledge',
        ]);

        // ── Theatre Nurse ─────────────────────────────────────────────────
        // Scrub/circulating nurse: full procedure workflow + consumable recording
        $theatreNurse = Role::firstOrCreate(['name' => 'Theatre Nurse']);
        $theatreNurse->syncPermissions([
            'patients.view',
            'visits.view',
            'vitals.view', 'vitals.create',
            'queue.view',
            'prescriptions.view',
            'procedures.view',
            'procedure.view', 'procedure.accept',
            'procedure.record_preop', 'procedure.record_postop',
            'procedure.schedule', 'procedure.complete', 'procedure.print',
            'procedure.catalogue.view', 'procedure_catalogue.view',
            'consumable_usage.record', 'consumable.use',
            'product.view', 'stock.view', 'stock.view_balance',
            'notifications.view',
        ]);

        // ── Anaesthetist ──────────────────────────────────────────────────
        $anaesthetist = Role::firstOrCreate(['name' => 'Anaesthetist']);
        $anaesthetist->syncPermissions([
            'patients.view',
            'visits.view',
            'vitals.view', 'vitals.create',
            'queue.view',
            'procedures.view',
            'procedure.view', 'procedure.record_preop',
            'procedure.record_anaesthesia', 'procedure.record_surgery',
            'procedure.record_postop', 'procedure.view_report',
            'procedure.catalogue.view', 'procedure_catalogue.view',
            'icd.view',
            'consumable_usage.record', 'consumable.use',
            'product.view', 'stock.view_balance',
            'notifications.view',
        ]);

        // ── Receptionist ──────────────────────────────────────────────────
        $receptionist = Role::firstOrCreate(['name' => 'Receptionist']);
        $receptionist->syncPermissions([
            'patients.view', 'patients.create', 'patients.edit',
            'visits.view', 'visits.create', 'visits.edit', 'visits.transition', 'visits.preview',
            'queue.view', 'queue.manage',
            'invoices.view',
            'appointments.view', 'appointments.create', 'appointments.edit',
            'notifications.view',
        ]);

        // ── Cashier ───────────────────────────────────────────────────────
        // Payment collection only; cannot modify invoices
        $cashier = Role::firstOrCreate(['name' => 'Cashier']);
        $cashier->syncPermissions([
            'patients.view',
            'visits.view', 'visits.preview',
            'invoices.view',
            'payments.view', 'payments.create',
            'accounts.cashier',
            'notifications.view',
        ]);

        // ── Lab Technician ────────────────────────────────────────────────
        $labTech = Role::firstOrCreate(['name' => 'Lab Technician']);
        $labTech->syncPermissions([
            'patients.view',
            'visits.view',
            'lab.requests.view',
            'lab.results.view', 'lab.results.create', 'lab.results.verify',
            'lab.tests.manage',
            'investigation.catalogue.view',
            'analyzer.manage',
            'product.view', 'stock.view', 'stock.view_balance',
            'consumable_usage.record', 'consumable.use',
            'queue.view',
            'icd.view',
            'notifications.view',
        ]);

        // ── Lab Manager ───────────────────────────────────────────────────
        // Lab Tech + catalogue management + stock adjustments for lab
        $labManager = Role::firstOrCreate(['name' => 'Lab Manager']);
        $labManager->syncPermissions([
            'patients.view',
            'visits.view',
            'lab.requests.view',
            'lab.results.view', 'lab.results.create', 'lab.results.verify',
            'lab.tests.manage',
            'investigation.catalogue.view', 'investigation.catalogue.manage',
            'analyzer.manage',
            'product.view', 'stock.view', 'stock.view_balance',
            'stock.adjust', 'stock.return',
            'consumable_usage.record', 'consumable.use',
            'queue.view',
            'icd.view',
            'reports.view',
            'notifications.view',
        ]);

        // ── Radiologist ───────────────────────────────────────────────────
        // Reads imaging requests, records findings, can request procedures
        $radiologist = Role::firstOrCreate(['name' => 'Radiologist']);
        $radiologist->syncPermissions([
            'patients.view',
            'visits.view',
            'lab.requests.view',
            'lab.results.view', 'lab.results.create', 'lab.results.verify',
            'procedures.view',
            'procedure.view', 'procedure.record_surgery', 'procedure.view_report',
            'procedure.catalogue.view', 'procedure_catalogue.view',
            'investigation.catalogue.view',
            'icd.view',
            'product.view', 'stock.view_balance',
            'consumable_usage.record', 'consumable.use',
            'queue.view',
            'notifications.view',
        ]);

        // ── Pharmacist ────────────────────────────────────────────────────
        $pharmacist = Role::firstOrCreate(['name' => 'Pharmacist']);
        $pharmacist->syncPermissions([
            'patients.view',
            'visits.view', 'visits.transition',
            'prescriptions.view',
            'pharmacy.dispensing.view', 'pharmacy.dispensing.create',
            'pharmacy.drugs.manage', 'pharmacy.stock.manage',
            'medication_orders.view', 'medication_administration.view',
            'mar_chart.view', 'mar_chart.print',
            'admission.mar_chart.view', 'emergency.mar_chart.view',
            'store.transfer.view',
            'product.view', 'stock.view', 'stock.view_balance', 'stock.return',
            'consumable_usage.record', 'consumable.use',
            'queue.view',
            // Requisitions: pharmacists can view, create requests, and acknowledge receipt
            'store.requisition.view', 'store.requisition.create', 'store.requisition.acknowledge',
            'notifications.view',
        ]);

        // ── Accountant ────────────────────────────────────────────────────
        $accountant = Role::firstOrCreate(['name' => 'Accountant']);
        $accountant->syncPermissions([
            'patients.view',
            'visits.view',
            'invoices.view', 'invoices.create', 'invoices.edit', 'invoices.void',
            'payments.view', 'payments.create', 'payments.void',
            'services.manage',
            'reports.view', 'reports.generate',
            'claims.view',
            'accounts.manage',
            'accounts.entries.view', 'accounts.entries.create', 'accounts.entries.approve',
            'accounts.cashier',
            'supplier.ledger.view',
            'notifications.view',
        ]);

        // ── Claims Officer ────────────────────────────────────────────────
        $claimsOfficer = Role::firstOrCreate(['name' => 'Claims Officer']);
        $claimsOfficer->syncPermissions([
            'patients.view',
            'visits.view', 'visits.preview',
            'invoices.view',
            'claims.view', 'claims.create', 'claims.approve', 'claims.export',
            'claims.eligible.view', 'claims.prepare', 'claims.validate', 'claims.submit',
            'claims.payment.record', 'claims.nhia.view', 'claims.nhia.prepare',
            'claims.nhia.submit', 'claims.nhia.export',
            'reports.view',
            'notifications.view',
        ]);

        // ── Store Keeper ──────────────────────────────────────────────────
        // Only role (outside Admin) that can CREATE products and manage stock
        $storeKeeper = Role::firstOrCreate(['name' => 'Store Keeper']);
        $storeKeeper->syncPermissions([
            'patients.view',
            // Suppliers & Purchase Orders
            'store.purchase.view', 'store.purchase.create', 'store.purchase.approve',
            'store.purchase.receive',
            // Purchase Returns
            'store.return.view', 'store.return.create', 'store.return.approve',
            // Stock Transfers
            'store.transfer.view', 'store.transfer.create',
            // Stock Requisitions (store keeper approves, issues, and views all)
            'store.requisition.view', 'store.requisition.approve', 'store.requisition.issue',
            // Pharmacy stock management
            'pharmacy.drugs.manage', 'pharmacy.stock.manage',
            // Products
            'product.view', 'product.create', 'product.edit',
            'product.link_departments', 'product.link_department',
            'product.pricing.manage',
            // Stock locations & balances
            'stock.location.manage', 'stock_location.manage',
            'stock.view', 'stock.view_balance',
            'stock.transfer', 'stock.adjust', 'stock.receive', 'stock.return',
            'stock.override_negative',
            // Supplier ledger
            'supplier.manage', 'supplier.ledger.view',
            'supplier.payment.create', 'supplier.return.create',
            'reports.view',
            'notifications.view',
        ]);

        // ── HR Manager ────────────────────────────────────────────────────
        $hrManager = Role::firstOrCreate(['name' => 'HR Manager']);
        $hrManager->syncPermissions([
            'hr.employees.view', 'hr.employees.create', 'hr.employees.edit',
            'hr.leave.view', 'hr.leave.create', 'hr.leave.approve',
            'hr.payroll.view', 'hr.payroll.process',
            'hr.attendance.view', 'hr.attendance.manage',
            'reports.view',
            'notifications.view',
        ]);
    }
}
