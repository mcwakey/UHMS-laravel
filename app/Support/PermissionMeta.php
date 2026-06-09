<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Resolves metadata (module, description, risk) for permission names
 * using the rules in `config/permissions.php`.
 *
 * Pure / static — safe to call from anywhere (Inertia share, Blade,
 * Artisan commands, controllers).
 */
class PermissionMeta
{
    /**
     * Return ['name','module','description','risk'] for a permission.
     */
    public static function for(string $permission): array
    {
        return [
            'name'        => $permission,
            'module'      => self::module($permission),
            'description' => self::description($permission),
            'risk'        => self::risk($permission),
        ];
    }

    public static function module(string $permission): string
    {
        $overrides = (array) config('permissions.module_overrides', []);
        if (isset($overrides[$permission])) {
            return $overrides[$permission];
        }
        return Str::before($permission, '.') ?: 'misc';
    }

    public static function risk(string $permission): string
    {
        $overrides = (array) config('permissions.risk_overrides', []);
        if (isset($overrides[$permission])) {
            return $overrides[$permission];
        }

        foreach ((array) config('permissions.risk_rules', []) as $pattern => $level) {
            if (@preg_match($pattern, $permission)) {
                return $level;
            }
        }

        return 'NORMAL';
    }

    public static function description(string $permission): string
    {
        $explicit = (array) config('permissions.descriptions', []);
        if (isset($explicit[$permission])) {
            return $explicit[$permission];
        }

        $parts = explode('.', $permission);
        $action = array_pop($parts) ?: 'access';
        $subject = self::subjectLabel(implode('.', $parts));

        return 'Allows the user to ' . self::actionLabel($action, $subject) . '.';
    }

    private static function actionLabel(string $action, string $subject): string
    {
        $actions = [
            'access' => 'access :subject',
            'acknowledge' => 'acknowledge receipt of :subject',
            'activate' => 'activate :subject',
            'adjust' => 'record adjustments for :subject',
            'administer' => 'administer :subject',
            'admit' => 'admit patients through :subject',
            'apply' => 'apply :subject',
            'approve' => 'approve :subject',
            'assign' => 'assign :subject',
            'bill' => 'create billing records for :subject',
            'broadcast' => 'broadcast :subject',
            'cancel' => 'cancel :subject',
            'cashier' => 'access cashier functions for :subject',
            'complete' => 'complete :subject',
            'confirm_identity' => 'confirm identity for :subject',
            'create' => 'create :subject',
            'create_while_admitted' => 'create :subject while the patient is already admitted',
            'correct' => 'correct :subject',
            'correct_completed' => 'correct completed :subject',
            'deactivate' => 'deactivate :subject',
            'delete' => 'delete :subject',
            'delete_any' => 'delete any user\'s :subject',
            'delete_own' => 'delete only their own :subject',
            'disable' => 'disable :subject',
            'discharge' => 'discharge patients from :subject',
            'dispense' => 'dispense :subject',
            'edit' => 'edit :subject',
            'edit_any' => 'edit any user\'s :subject',
            'edit_own' => 'edit only their own :subject',
            'escalate' => 'escalate :subject',
            'execute' => 'execute :subject',
            'export' => 'export :subject',
            'generate' => 'generate :subject',
            'hold' => 'place :subject on hold',
            'issue' => 'issue :subject',
            'link_departments' => 'link :subject to departments',
            'list' => 'list :subject',
            'manage' => 'manage :subject',
            'manage_preferences' => 'manage preferences for :subject',
            'manage_status' => 'manage status for :subject',
            'mark_deceased' => 'mark :subject as deceased',
            'mark_missed' => 'mark :subject as missed',
            'mark_read' => 'mark :subject as read',
            'override_disabled' => 'bypass disabled-module restrictions for :subject',
            'override_negative' => 'override negative-stock checks for :subject',
            'perform' => 'perform :subject',
            'postpone' => 'postpone :subject',
            'prepare' => 'prepare :subject',
            'preview' => 'preview :subject',
            'print' => 'print :subject',
            'process' => 'process :subject',
            'receive' => 'receive :subject',
            'record' => 'record :subject',
            'record_anaesthesia' => 'record anaesthesia details for :subject',
            'record_postop' => 'record post-operative details for :subject',
            'record_preop' => 'record pre-operative details for :subject',
            'record_surgery' => 'record operative details for :subject',
            'refer' => 'refer patients from :subject',
            'reject' => 'reject :subject',
            'reopen_locked_session' => 'reopen locked :subject',
            'request' => 'request :subject',
            'request_lab' => 'request lab investigations from :subject',
            'request_procedure' => 'request procedures from :subject',
            'reschedule' => 'reschedule :subject',
            'reset_password' => 'reset passwords for :subject',
            'return' => 'record returns for :subject',
            'reverse' => 'reverse :subject',
            'schedule' => 'schedule :subject',
            'search' => 'search :subject',
            'stop' => 'stop :subject',
            'submit' => 'submit :subject',
            'switch' => 'switch :subject',
            'transfer' => 'transfer :subject',
            'transition' => 'change the status of :subject',
            'update' => 'update :subject',
            'use' => 'record use of :subject',
            'validate' => 'validate :subject',
            'verify' => 'verify :subject',
            'view' => 'view :subject',
            'view_all' => 'view all :subject',
            'view_balance' => 'view current balances for :subject',
            'view_overdue' => 'view overdue :subject',
            'view_patient' => 'view patient profile details from :subject',
            'view_report' => 'view reports for :subject',
            'view_reports' => 'view reports for :subject',
            'view_results' => 'view results from :subject',
        ];

        $template = $actions[$action] ?? Str::lower(str_replace('_', ' ', $action)) . ' :subject';

        return str_replace(':subject', $subject, $template);
    }

    private static function subjectLabel(string $subject): string
    {
        if ($subject === '') {
            return 'this area';
        }

        $labels = [
            'accounts' => 'accounting records',
            'accounts.entries' => 'journal entries',
            'accounting' => 'double-entry accounting',
            'accounting.accounts' => 'chart of accounts',
            'accounting.dashboard' => 'accounting dashboard',
            'accounting.fiscal_years' => 'fiscal years',
            'accounting.journals' => 'manual journal entries',
            'accounting.periods' => 'accounting periods',
            'accounting.posting' => 'automated accounting postings',
            'accounting.reports' => 'accounting reports',
            'accounting.settings' => 'accounting settings',
            'admission.mar_chart' => 'admission medication administration charts',
            'admission.medication_board' => 'admission medication boards',
            'analyzer' => 'lab analyzer integration',
            'appointments' => 'appointments',
            'beds' => 'beds and bed allocation',
            'billing.credit_note' => 'billing credit notes',
            'billing.discount' => 'billing discounts',
            'billing.refund' => 'billing refunds',
            'billing.write_off' => 'billing write-offs',
            'claims' => 'insurance claims',
            'claims.dashboard' => 'claims dashboard',
            'claims.eligible' => 'claim eligibility checks',
            'claims.nhia' => 'NHIA claim batches',
            'claims.payment' => 'claim payment records',
            'claims.report' => 'claim reports',
            'clinical_tasks' => 'clinical tasks',
            'complaints' => 'patient complaints',
            'complaints.catalogue' => 'complaint catalogue',
            'consultation' => 'consultation workspace',
            'consultation.entries' => 'consultation entries',
            'consultation.examination' => 'clinical examination notes',
            'consultation.followup' => 'consultation follow-up appointments',
            'consultation.hopc' => 'history of presenting complaint notes',
            'consultation.routes' => 'consultation route workflows',
            'consultation.sessions' => 'consultation sessions',
            'consultation.tasks' => 'consultation tasks',
            'consultations' => 'consultation records',
            'consumable' => 'consumables',
            'consumable_usage' => 'consumable usage records',
            'corporate_clients' => 'corporate client accounts',
            'departments' => 'departments',
            'emergency' => 'emergency department workspace',
            'emergency.bay' => 'emergency bays',
            'emergency.billing' => 'emergency billing',
            'emergency.board' => 'emergency department boards',
            'emergency.case' => 'emergency cases',
            'emergency.consumables' => 'emergency consumables',
            'emergency.death' => 'emergency death records',
            'emergency.disposition' => 'emergency dispositions',
            'emergency.investigation' => 'emergency investigations',
            'emergency.mar_chart' => 'emergency medication administration charts',
            'emergency.medication' => 'emergency medications',
            'emergency.medication_board' => 'emergency medication boards',
            'emergency.notes' => 'emergency notes',
            'emergency.procedure' => 'emergency procedures',
            'emergency.reports' => 'emergency reports',
            'emergency.session' => 'emergency sessions',
            'emergency.settings' => 'emergency settings',
            'emergency.tasks' => 'emergency tasks',
            'emergency.transfer' => 'emergency transfers',
            'emergency.triage' => 'emergency triage',
            'emergency.vitals' => 'emergency vitals',
            'hr.attendance' => 'HR attendance records',
            'hr.employees' => 'HR employee records',
            'hr.leave' => 'HR leave requests',
            'hr.payroll' => 'HR payroll',
            'icd' => 'ICD clinical coding catalogue',
            'investigation.catalogue' => 'investigation catalogue',
            'invoices' => 'invoices',
            'lab.requests' => 'lab requests',
            'lab.results' => 'lab results',
            'lab.tests' => 'lab test catalogue',
            'logs' => 'audit logs',
            'mar_chart' => 'medication administration charts',
            'medical_patterns' => 'clinical templates and medical patterns',
            'medication_administration' => 'medication administration records',
            'medication_orders' => 'medication orders',
            'modules' => 'optional modules',
            'notifications' => 'notifications',
            'patients' => 'patient records',
            'patients.merge' => 'patient folder merges',
            'payments' => 'payments',
            'receivables' => 'payer receivables',
            'reports.ar_aging' => 'accounts receivable aging reports',
            'pharmacy.dispensing' => 'pharmacy dispensing',
            'pharmacy.drugs' => 'pharmacy drug catalogue',
            'pharmacy.stock' => 'pharmacy stock',
            'prescriptions' => 'prescriptions',
            'procedure' => 'procedure requests and cases',
            'procedure_catalogue' => 'procedure catalogue',
            'procedure_template' => 'procedure templates',
            'procedures' => 'procedure records',
            'product' => 'product catalogue items',
            'product.pricing' => 'product pricing',
            'queue' => 'work queues',
            'reports' => 'reports',
            'roles' => 'roles',
            'service_consumable' => 'service consumable mappings',
            'services' => 'billable services',
            'settings' => 'system settings',
            'sponsors' => 'billing sponsors',
            'stock' => 'stock records',
            'stock.location' => 'stock locations',
            'store.purchase' => 'purchase orders',
            'store.requisition' => 'stock requisitions',
            'store.return' => 'purchase returns',
            'store.transfer' => 'stock transfers',
            'supplier' => 'suppliers',
            'supplier.ledger' => 'supplier ledgers',
            'supplier.payment' => 'supplier payments',
            'supplier.return' => 'supplier returns and credit notes',
            'theatre.anaesthesia' => 'theatre anaesthesia notes',
            'theatre.billing' => 'theatre billing',
            'theatre.board' => 'theatre board',
            'theatre.cases' => 'theatre cases',
            'theatre.consumables' => 'theatre consumables',
            'theatre.operative_note' => 'operative notes',
            'theatre.preop' => 'theatre pre-op checklist',
            'theatre.recovery_note' => 'recovery notes',
            'theatre.reports' => 'theatre reports',
            'theatre.rooms' => 'theatre rooms',
            'theatre.schedule' => 'theatre schedule',
            'theatre.team' => 'theatre teams',
            'users' => 'user accounts',
            'visits' => 'patient visits',
            'vitals' => 'patient vitals',
            'ward' => 'ward and inpatient records',
            'ward.consumable' => 'ward consumables',
        ];

        if (isset($labels[$subject])) {
            return $labels[$subject];
        }

        return str_replace('_', ' ', $subject);
    }
}
