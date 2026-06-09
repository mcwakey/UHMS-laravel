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
            'patients.mark_deceased',
            'patients.merge.view',
            'patients.merge.request',
            'patients.merge.approve',
            'patients.merge.execute',
            'patients.merge.confirm_identity',

            // ── Visits ────────────────────────────────────────────────────
            'visits.view',
            'visits.create',
            'visits.edit',
            'visits.transition',
            'visits.preview',
            'visits.create_while_admitted',
            'visits.reopen_locked_session',

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
            'complaints.view',
            'complaints.create',
            'complaints.edit_own',
            'complaints.edit_any',
            'complaints.delete_own',
            'complaints.delete_any',
            'complaints.catalogue.view',
            'complaints.catalogue.create',
            'complaints.catalogue.update',
            'complaints.catalogue.deactivate',
            'consultation.hopc.create',
            'consultation.hopc.view',
            'consultation.hopc.edit',
            'consultation.examination.create',
            'consultation.examination.view',
            'consultation.tasks.create',
            'consultation.tasks.view',
            'consultation.tasks.update',
            'consultation.followup.create',
            'consultation.followup.update',
            'consultation.followup.cancel',
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
            'payments.refund',
            // ── Billing payment policy / gate (context-aware) ──
            'billing.policy.view',
            'billing.view_payment_gate_status',
            'billing.view_running_balance',
            'billing.payment_gate.override',
            'billing.override_opd_payment_gate',
            'billing.override_opd_full_visit_settlement',
            'billing.revoke_opd_deferred_settlement',
            'billing.approve_credit',
            'billing.approve_credit_balance',
            'billing.waive_invoice_item',
            'billing.waive_invoice',
            'billing.discount.view',
            'billing.discount.apply',
            'billing.discount.approve',
            'billing.discount.remove',
            'billing.discount.override_limit',
            'billing.discount.report',
            'billing.complete_visit_with_balance',
            'billing.discharge_clearance.override',
            'credit_notes.view',
            'credit_notes.create',
            'credit_notes.write_off',
            'sponsors.manage',
            'services.manage',

            // Service rendering / fulfilment
            'service_rendering.view',
            'service_rendering.view_all',
            'service_rendering.start',
            'service_rendering.mark_rendered',
            'service_rendering.mark_not_rendered',
            'service_rendering.cancel',
            'service_rendering.edit_notes',
            'service_rendering.reports',
            'service_rendering.correct_completed',

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
            'appointments.update',
            'appointments.cancel',
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
            'product.pricing.manage',       // update base_price, is_billable, insurance prices

            // ── Stock Management ──────────────────────────────────────────
            'stock.location.manage',        // create/edit stock locations
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
            'accounting.dashboard.view',
            'accounting.accounts.view',
            'accounting.accounts.create',
            'accounting.accounts.edit',
            'accounting.accounts.disable',
            'accounting.journals.view',
            'accounting.journals.create',
            'accounting.journals.edit',
            'accounting.journals.post',
            'accounting.journals.reverse',
            'accounting.journals.cancel',
            'accounting.reports.trial_balance',
            'accounting.reports.general_ledger',
            'accounting.periods.view',
            'accounting.periods.manage',
            'accounting.fiscal_years.view',
            'accounting.fiscal_years.manage',
            'accounting.settings.view',
            'accounting.settings.manage',

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
            'emergency.board.view',
            'emergency.case.create',
            'emergency.case.view',
            'emergency.case.update',
            'emergency.case.cancel',
            'emergency.triage.perform',
            'emergency.bay.assign',
            'emergency.notes.create',
            'emergency.notes.edit_own',
            'emergency.notes.edit_any',
            'emergency.vitals.record',
            'emergency.medication.administer',
            'emergency.investigation.request',
            'emergency.procedure.request',
            'emergency.consumables.use',
            'emergency.billing.view',
            'emergency.tasks.manage',
            'emergency.session.manage',
            'emergency.disposition.manage',
            'emergency.transfer.admit',
            'emergency.transfer.opd',
            'emergency.transfer.theatre',
            'emergency.refer',
            'emergency.death.record',
            'emergency.reports.view',
            'emergency.settings.manage',
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
            'notifications.mark_read',
            'notifications.delete',
            'notifications.manage',
            'notifications.broadcast',
            'notifications.manage_preferences',

            // ── Activity / Audit Logs ─────────────────────────────────────
            'logs.view',
            'logs.view_patient',
            'logs.view_clinical',
            'logs.view_financial',
            'logs.view_stock',
            'logs.view_security',
            'logs.view_details',
            'logs.view_sensitive',
            'logs.export',
            'logs.delete',
            'logs.manage_retention',

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

            'theatre.rooms.view',
            'theatre.rooms.create',
            'theatre.rooms.update',
            'theatre.rooms.deactivate',
            'theatre.rooms.manage_status',
            'theatre.board.view',
            'theatre.cases.view',
            'theatre.cases.accept',
            'theatre.cases.schedule',
            'theatre.cases.reschedule',
            'theatre.cases.cancel',
            'theatre.cases.postpone',
            'theatre.cases.complete',
            'theatre.team.assign',
            'theatre.preop.manage',
            'theatre.anaesthesia.create',
            'theatre.anaesthesia.edit_own',
            'theatre.anaesthesia.edit_any',
            'theatre.operative_note.create',
            'theatre.operative_note.edit_own',
            'theatre.operative_note.edit_any',
            'theatre.recovery_note.create',
            'theatre.recovery_note.edit_own',
            'theatre.recovery_note.edit_any',
            'theatre.consumables.use',
            'theatre.billing.view',
            'theatre.billing.manage',
            'theatre.schedule.override',
            'theatre.reports.view',

            // ── Procedure Catalogue ───────────────────────────────────────
            'procedure_catalogue.view',     // canonical (used by routes)
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
            'reports.export',
            'reports.clinical',
            'reports.consultation',
            'reports.consultations',
            'reports.diagnosis',
            'reports.diagnoses',
            'reports.complaints',
            'reports.pharmacy',
            'reports.investigations',
            'reports.procedures',
            'reports.theatre',
            'reports.emergency',
            'reports.admission',
            'reports.mar',
            'reports.billing',
            'reports.claims',
            'reports.stock',
            'reports.blood_bank',

            // ── Statistical Reports / Analytics ───────────────────────────
            'statistics.view',
            'statistics.dashboard.view',
            'statistics.activity.view',
            'statistics.clinical.view',
            'statistics.diagnosis.view',
            'statistics.complaints.view',
            'statistics.consultation.view',
            'statistics.pharmacy.view',
            'statistics.investigations.view',
            'statistics.procedures.view',
            'statistics.theatre.view',
            'statistics.emergency.view',
            'statistics.admission.view',
            'statistics.mar.view',
            'statistics.billing.view',
            'statistics.claims.view',
            'statistics.stock.view',
            'statistics.blood_bank.view',
            'statistics.staff_performance.view',
            'statistics.export',

            'blood_bank.view',
            'blood_bank.donors.manage',
            'blood_bank.donations.record',
            'blood_bank.screening.manage',
            'blood_bank.units.view',
            'blood_bank.units.discard',
            'blood_bank.requests.view',
            'blood_bank.requests.create',
            'blood_bank.requests.approve',
            'blood_bank.crossmatch.perform',
            'blood_bank.units.issue',
            'blood_bank.transfusions.record',
            'blood_bank.reports.view',
            'blood_bank.settings.manage',
            // WHO screening, compatibility, recipient & emergency release
            'blood_bank.screening.view',
            'blood_bank.screening.perform',
            'blood_bank.screening.verify',
            'blood_bank.donor.defer',
            'blood_bank.donor.override_eligibility',
            'blood_bank.compatibility.view',
            'blood_bank.crossmatch.verify',
            'blood_bank.compatibility.override',
            'blood_bank.emergency_release',
            'blood_bank.recipient_details.manage',
            'blood_bank.transfusion.reaction_record',

            // ── Settings ─────────────────────────────────────────────────
            'settings.view',
            'settings.manage',

            // ── Modules ───────────────────────────────────────────────────
            'modules.manage',
            'modules.enable',
            'modules.disable',
            'modules.override_disabled',

            // ── Roles & permissions admin ────────────────────────────────
            'roles.view',
            'roles.create',
            'roles.update',
            'roles.delete',
            'permissions.view',
            'permissions.assign',
            'permissions.assign_critical',

            // ── User account admin (extra) ───────────────────────────────
            'users.disable',
            'users.reset_password',
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
            'complaints.view', 'complaints.create', 'complaints.edit_own', 'complaints.delete_own',
            'consultation.hopc.create', 'consultation.hopc.view', 'consultation.hopc.edit',
            'consultation.examination.create', 'consultation.examination.view',
            'consultation.tasks.create', 'consultation.tasks.view', 'consultation.tasks.update',
            'consultation.followup.create', 'consultation.followup.update', 'consultation.followup.cancel',
            'medical_patterns.view', 'medical_patterns.create', 'medical_patterns.update', 'medical_patterns.apply',
            'vitals.view',
            'prescriptions.view', 'prescriptions.create', 'prescriptions.edit',
            'medication_orders.view', 'medication_orders.manage', 'medication_orders.stop', 'medication_orders.hold',
            'medication_administration.view', 'medication_administration.view_reports',
            'mar_chart.view', 'mar_chart.print',
            'admission.mar_chart.view', 'emergency.mar_chart.view',
            'clinical_tasks.view', 'clinical_tasks.view_overdue',
            'admission.medication_board.view', 'emergency.medication_board.view',
            'emergency.board.view', 'emergency.case.create', 'emergency.case.view', 'emergency.case.update',
            'emergency.notes.create', 'emergency.vitals.record', 'emergency.medication.administer',
            'emergency.investigation.request', 'emergency.procedure.request', 'emergency.disposition.manage',
            'emergency.consumables.use', 'emergency.billing.view', 'emergency.tasks.manage', 'emergency.session.manage',
            'emergency.transfer.admit', 'emergency.transfer.opd', 'emergency.transfer.theatre',
            'emergency.refer', 'emergency.death.record', 'emergency.reports.view',
            'lab.requests.view', 'lab.requests.create',
            'lab.results.view',
            'queue.view',
            'ward.view', 'ward.admit', 'ward.discharge',
            'appointments.view', 'appointments.create', 'appointments.edit', 'appointments.update', 'appointments.cancel',
            'notifications.view',
            'procedures.view', 'procedures.create',
            'procedure.request', 'procedure.view', 'procedure.view_report',
            'procedure.record_anaesthesia', 'procedure.record_surgery',
            'procedure_catalogue.view',
            'consumable_usage.record', 'consumable.use',
            'service_rendering.view', 'service_rendering.start',
            'service_rendering.mark_rendered', 'service_rendering.mark_not_rendered',
            'service_rendering.edit_notes', 'service_rendering.reports',
            'investigation.catalogue.view',
            'blood_bank.view', 'blood_bank.requests.create',
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
            'consultation.followup.create',
            'vitals.view',
            'prescriptions.view', 'prescriptions.create',
            'lab.requests.view', 'lab.requests.create',
            'lab.results.view',
            'queue.view',
            'ward.view',
            'appointments.view', 'appointments.create',
            'notifications.view',
            'procedures.view', 'procedure.request', 'procedure.view',
            'procedure_catalogue.view',
            'investigation.catalogue.view',
            'icd.view',
            'product.view',
        ]);

        // ── Nurse ─────────────────────────────────────────────────────────
        $nurse = Role::firstOrCreate(['name' => 'Nurse']);
        $nurse->syncPermissions([
            'patients.view',
            'visits.view', 'visits.transition', 'visits.preview',
            'complaints.view', 'complaints.create', 'complaints.edit_own', 'complaints.delete_own',
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
            'emergency.board.view', 'emergency.case.view', 'emergency.triage.perform',
            'emergency.bay.assign', 'emergency.notes.create', 'emergency.vitals.record',
            'emergency.medication.administer', 'emergency.consumables.use', 'emergency.tasks.manage', 'emergency.reports.view',
            'notifications.view',
            'procedures.view',
            'procedure.view', 'procedure.record_preop', 'procedure.record_postop',
            'procedure_catalogue.view',
            'consumable_usage.record', 'consumable.use',
            'service_rendering.view', 'service_rendering.start',
            'service_rendering.mark_rendered', 'service_rendering.mark_not_rendered',
            'service_rendering.edit_notes',
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
            'complaints.view', 'complaints.create', 'complaints.edit_own', 'complaints.delete_own',
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
            'emergency.board.view', 'emergency.case.create', 'emergency.case.view', 'emergency.case.update',
            'emergency.triage.perform', 'emergency.bay.assign', 'emergency.notes.create',
            'emergency.vitals.record', 'emergency.medication.administer', 'emergency.consumables.use', 'emergency.tasks.manage', 'emergency.reports.view',
            'notifications.view',
            'procedures.view',
            'procedure.view', 'procedure.record_preop', 'procedure.record_postop',
            'procedure_catalogue.view',
            'consumable_usage.record', 'consumable.use', 'ward.consumable.use',
            'service_rendering.view', 'service_rendering.start',
            'service_rendering.mark_rendered', 'service_rendering.mark_not_rendered',
            'service_rendering.edit_notes', 'service_rendering.reports',
            'product.view', 'stock.view_balance',
            // Requisitions: ward nurses can request stock and acknowledge receipt
            'store.requisition.view', 'store.requisition.create', 'store.requisition.acknowledge',
        ]);

        // ── Theatre Nurse ─────────────────────────────────────────────────
        // Scrub/circulating nurse: full procedure workflow + consumable recording
        $emergencyDoctor = Role::firstOrCreate(['name' => 'Emergency Doctor']);
        $emergencyDoctor->syncPermissions(array_values(array_unique(array_merge($doctorPerms, [
            'emergency.board.view', 'emergency.case.create', 'emergency.case.view', 'emergency.case.update',
            'emergency.notes.create', 'emergency.vitals.record', 'emergency.medication.administer',
            'emergency.investigation.request', 'emergency.procedure.request', 'emergency.disposition.manage',
            'emergency.consumables.use', 'emergency.billing.view', 'emergency.tasks.manage', 'emergency.session.manage',
            'emergency.transfer.admit', 'emergency.transfer.opd', 'emergency.transfer.theatre',
            'emergency.refer', 'emergency.death.record', 'emergency.reports.view',
        ]))));

        $emergencyNurse = Role::firstOrCreate(['name' => 'Emergency Nurse']);
        $emergencyNurse->syncPermissions([
            'patients.view',
            'visits.view', 'visits.preview',
            'complaints.view', 'complaints.create', 'complaints.edit_own', 'complaints.delete_own',
            'vitals.view', 'vitals.create',
            'medication_orders.view',
            'medication_administration.view', 'medication_administration.administer',
            'medication_administration.hold', 'medication_administration.mark_missed',
            'mar_chart.view', 'mar_chart.print', 'emergency.mar_chart.view',
            'clinical_tasks.view', 'clinical_tasks.complete', 'clinical_tasks.escalate', 'clinical_tasks.view_overdue',
            'emergency.medication_board.view',
            'emergency.board.view', 'emergency.case.create', 'emergency.case.view', 'emergency.case.update',
            'emergency.triage.perform', 'emergency.bay.assign', 'emergency.notes.create',
            'emergency.vitals.record', 'emergency.medication.administer', 'emergency.consumables.use',
            'emergency.billing.view', 'emergency.tasks.manage', 'emergency.session.manage', 'emergency.reports.view',
            'service_rendering.view', 'service_rendering.start',
            'service_rendering.mark_rendered', 'service_rendering.mark_not_rendered',
            'service_rendering.edit_notes',
            'notifications.view',
            'product.view', 'stock.view_balance',
        ]);

        $triageNurse = Role::firstOrCreate(['name' => 'Triage Nurse']);
        $triageNurse->syncPermissions([
            'patients.view',
            'visits.view', 'visits.preview',
            'complaints.view', 'complaints.create', 'complaints.edit_own', 'complaints.delete_own',
            'vitals.view', 'vitals.create',
            'queue.view',
            'emergency.board.view', 'emergency.case.create', 'emergency.case.view',
            'emergency.triage.perform', 'emergency.notes.create', 'emergency.vitals.record',
            'notifications.view',
        ]);

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
            'theatre.board.view', 'theatre.cases.view', 'theatre.cases.accept',
            'theatre.cases.schedule', 'theatre.cases.complete',
            'theatre.rooms.view', 'theatre.rooms.update', 'theatre.rooms.manage_status',
            'theatre.preop.manage', 'theatre.recovery_note.create',
            'theatre.consumables.use', 'theatre.billing.view',
            'procedure_catalogue.view',
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
            'theatre.board.view', 'theatre.cases.view',
            'theatre.anaesthesia.create', 'theatre.anaesthesia.edit_own',
            'theatre.operative_note.create', 'theatre.recovery_note.create',
            'theatre.reports.view',
            'procedure_catalogue.view',
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
            'appointments.view', 'appointments.create', 'appointments.edit', 'appointments.update', 'appointments.cancel',
            'notifications.view',
        ]);

        // ── Cashier ───────────────────────────────────────────────────────
        // Payment collection only; cannot modify invoices
        $cashier = Role::firstOrCreate(['name' => 'Cashier']);
        $cashier->syncPermissions([
            'patients.view',
            'visits.view', 'visits.preview',
            'invoices.view',
            'billing.discount.view', 'billing.discount.apply',
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
            'procedure_catalogue.view',
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
            'billing.discount.view', 'billing.discount.apply', 'billing.discount.approve', 'billing.discount.report',
            'payments.view', 'payments.create', 'payments.void', 'payments.refund',
            'credit_notes.view', 'credit_notes.create', 'credit_notes.write_off',
            'sponsors.manage',
            'services.manage',
            'reports.view', 'reports.generate',
            'statistics.view', 'statistics.dashboard.view', 'statistics.billing.view', 'statistics.claims.view', 'statistics.export',
            'claims.view',
            'accounts.manage',
            'accounts.entries.view', 'accounts.entries.create', 'accounts.entries.approve',
            'accounts.cashier',
            'accounting.dashboard.view',
            'accounting.accounts.view', 'accounting.accounts.create', 'accounting.accounts.edit',
            'accounting.journals.view', 'accounting.journals.create', 'accounting.journals.edit',
            'accounting.journals.post', 'accounting.journals.reverse', 'accounting.journals.cancel',
            'accounting.reports.trial_balance', 'accounting.reports.general_ledger',
            'accounting.periods.view', 'accounting.fiscal_years.view',
            'accounting.settings.view',
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
            'complaints.view',
            'claims.eligible.view', 'claims.prepare', 'claims.validate', 'claims.submit',
            'claims.payment.record', 'claims.nhia.view', 'claims.nhia.prepare',
            'claims.nhia.submit', 'claims.nhia.export',
            'reports.view',
            'notifications.view',
        ]);

        // ── Store Keeper ──────────────────────────────────────────────────
        // Only role (outside Admin) that can CREATE products and manage stock
        $bloodBankOfficer = Role::firstOrCreate(['name' => 'Blood Bank Officer']);
        $bloodBankOfficer->syncPermissions([
            'patients.view',
            'visits.view', 'visits.preview',
            'blood_bank.view',
            'blood_bank.donors.manage',
            'blood_bank.donations.record',
            'blood_bank.screening.manage',
            'blood_bank.units.view',
            'blood_bank.units.discard',
            'blood_bank.requests.view',
            'blood_bank.requests.create',
            'blood_bank.requests.approve',
            'blood_bank.crossmatch.perform',
            'blood_bank.units.issue',
            'blood_bank.transfusions.record',
            'blood_bank.reports.view',
            // WHO screening, compatibility, recipient & emergency release
            'blood_bank.screening.view',
            'blood_bank.screening.perform',
            'blood_bank.screening.verify',
            'blood_bank.donor.defer',
            'blood_bank.donor.override_eligibility',
            'blood_bank.compatibility.view',
            'blood_bank.crossmatch.verify',
            'blood_bank.compatibility.override',
            'blood_bank.emergency_release',
            'blood_bank.recipient_details.manage',
            'blood_bank.transfusion.reaction_record',
            'reports.view',
            'reports.blood_bank',
            'notifications.view',
        ]);

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
            'product.link_departments',
            'product.pricing.manage',
            // Stock locations & balances
            'stock.location.manage',
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
