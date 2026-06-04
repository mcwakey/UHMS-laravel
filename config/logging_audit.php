<?php

/**
 * Governance config for `php artisan logs:audit`.
 *
 * The audit is a heuristic that scans controllers for mutating actions and asks
 * "does this action reach the activity log?". Because almost all of UHMS logs at
 * a SERVICE FUNNEL (not in the controller), the raw heuristic over-reports. This
 * config teaches the audit about the funnels we actually wired, and lets us
 * classify the residue honestly:
 *
 *   SERVICE_FUNNEL_COVERED  controller delegates to a service/observer that logs
 *   NEEDS_REVIEW            delegates to a service we have NOT verified logs
 *   KNOWN_BACKLOG           a real gap we have consciously deferred (documented)
 *   INTENTIONALLY_SKIPPED   non-auditable / noisy action, excluded on purpose
 *   MISSING_LOG             mutating action with no funnel anywhere — a real gap
 *
 * The command also computes the transitive closure of "logging services": a
 * service counts as logging if it calls ActivityLogService directly OR delegates
 * to another service that does (e.g. EmergencyMedicationService →
 * MedicationAdministrationLogService). So you usually do NOT need to list a
 * funnel here — only add one when transitive detection can't see the link
 * (dynamic resolution, façade indirection, observers).
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Covered service funnels (verified during the logging burn-down)
    |--------------------------------------------------------------------------
    | Services confirmed to write activity logs. Seeds the "logging services"
    | set so transitive detection has explicit anchors even if a marker scan
    | misses them. Class base-names (no namespace).
    */
    'covered_service_funnels' => [
        // Consultation / clinical entries
        'MedicalRecordEntryLogService',
        // Investigations / lab
        'LabService',
        'InvestigationRequestService',
        // MAR / medication administration
        'MedicationAdministrationLogService',
        'MedicationAdministrationService',
        // Pharmacy
        'PharmacyService',
        'PharmacyBillingSelectionService',
        // Procedures / theatre
        'ProcedureWorkflowService',
        // Billing / claims
        'BillingService',
        'VisitBillingOverrideService',
        'CreditNoteService',
        'RefundService',
        'ClaimService',
        'ClaimStatusService',
        // Emergency / admission
        'EmergencyTriageService',
        'EmergencyDispositionService',
        'EmergencyCaseService',
        'EmergencyConsumableService',
        'AdmissionService',
        // Stock / procurement / supplier / admin
        'StockTransferService',
        'StockRequisitionService',
        'ProcurementService',
        'SupplierLedgerService',
        'ModuleService',
        'ConsumableUsageService',
        'StockMovementService',
        // Cross-cutting
        'ServiceRenderingService',
        'PatientMergeService',
        // Blood bank lifecycle
        'BloodRequestService',
        'BloodIssueService',
        'BloodCrossmatchService',
    ],

    /*
    |--------------------------------------------------------------------------
    | Covered observers (model → observer that logs)
    |--------------------------------------------------------------------------
    | A controller is funnel-covered if it touches a model whose observer logs,
    | even when it never references a logging service. Match is on the model
    | base-name appearing in the controller source.
    */
    'covered_observers' => [
        'Invoice' => 'InvoiceObserver',
        'Payment' => 'PaymentObserver',
        'User' => 'UserObserver',
    ],

    /*
    |--------------------------------------------------------------------------
    | Known false-positive controllers (force SERVICE_FUNNEL_COVERED)
    |--------------------------------------------------------------------------
    | Controllers verified by hand to be covered when the heuristic can't see
    | the link. Key = controller path relative to app root; value = reason.
    */
    'known_false_positive_controllers' => [
        'app/Http/Controllers/Admin/UserController.php' =>
            'User create/update audited by UserObserver (USER_CREATED / USER_FIELD_CHANGED).',
    ],

    /*
    |--------------------------------------------------------------------------
    | Intentionally skipped actions (INTENTIONALLY_SKIPPED)
    |--------------------------------------------------------------------------
    | Non-auditable or noisy actions excluded on purpose. Entries may be a
    | controller path (whole controller) or "ControllerPath@method".
    */
    'intentionally_skipped_actions' => [
        // Per-user preference toggles — not a security/clinical event.
        'app/Http/Controllers/Admin/NotificationPreferenceController.php@update',
    ],

    /*
    |--------------------------------------------------------------------------
    | Known backlog (KNOWN_BACKLOG) — real gaps consciously deferred
    |--------------------------------------------------------------------------
    | Lower-priority admin/catalogue/HR/notification polish. Documented, not
    | hidden: they still appear in the audit under "Backlog" and feed the
    | promotion checklist. NEVER put a CRITICAL/HIGH security or financial gap
    | here — those must stay visible as MISSING_LOG. Entries are controller
    | paths or "ControllerPath@method".
    */
    'backlog_controllers' => [
        // Catalogue / reference-data CRUD
        'app/Http/Controllers/Admin/AccountCategoryController.php',
        'app/Http/Controllers/Admin/ComplaintCatalogueController.php',
        'app/Http/Controllers/Admin/DepartmentController.php',
        'app/Http/Controllers/Admin/DesignationController.php',
        'app/Http/Controllers/Admin/IcdCodeController.php',
        'app/Http/Controllers/Admin/InsuranceProviderController.php',
        'app/Http/Controllers/Admin/InsuranceTierController.php',
        'app/Http/Controllers/Admin/InvestigationItemController.php',
        'app/Http/Controllers/Admin/ProcedureController.php',
        'app/Http/Controllers/Admin/ProductController.php',
        'app/Http/Controllers/Admin/ServiceCatalogController.php',
        'app/Http/Controllers/Admin/SpecialtyController.php',
        'app/Http/Controllers/Admin/StockLocationController.php',
        'app/Http/Controllers/Admin/SupplierController.php',
        'app/Http/Controllers/Admin/AnalyzerController.php',
        'app/Http/Controllers/Admin/WardController.php',
        'app/Http/Controllers/Admin/EmergencyBayController.php',
        'app/Http/Controllers/Billing/SponsorController.php',
        // Patient demographic sub-records
        'app/Http/Controllers/Admin/PatientInsuranceController.php',
        'app/Http/Controllers/Admin/EmergencyContactController.php',
        'app/Http/Controllers/Admin/ProfileController.php',
        // HR (non-financial reference + attendance). Payroll/leave APPROVALS are
        // financial and deliberately left to surface as NEEDS_REVIEW, not backlog.
        'app/Http/Controllers/Admin/AttendanceController.php',
        'app/Http/Controllers/Admin/EmployeeController.php',
        // Notifications
        'app/Http/Controllers/Admin/NotificationController.php',
        'app/Http/Controllers/Admin/NotificationBroadcastController.php',
        // Stock adjustments (raw ledger; global-log only, no patient ctx). Purchase
        // RETURNS are left to surface (they move supplier ledger + stock) — not here.
        'app/Http/Controllers/Admin/ProductStockController.php',
        // Emergency ancillary records not yet on a logging funnel
        'app/Http/Controllers/Admin/EmergencyVitalsController.php',
        'app/Http/Controllers/Admin/EmergencyNoteController.php',
        'app/Http/Controllers/Admin/EmergencyInvestigationController.php',
        'app/Http/Controllers/Admin/EmergencyProcedureController.php',
        'app/Http/Controllers/Admin/EmergencyPatientIdentityController.php',
        // Front-desk / queue workflow deferred (non-financial)
        'app/Http/Controllers/Admin/AppointmentController.php',
        'app/Http/Controllers/Admin/QueueController.php',
        'app/Http/Controllers/Admin/TriageController.php',
        'app/Http/Controllers/Admin/VitalController.php',
        // Blood bank intake (lifecycle issue/crossmatch/request already covered)
        'app/Http/Controllers/Admin/BloodDonationController.php',
        'app/Http/Controllers/Admin/BloodDonorController.php',
    ],

    /*
    |--------------------------------------------------------------------------
    | Severity tiers by action (highest matching tier wins for a finding)
    |--------------------------------------------------------------------------
    | Severity is independent of classification — it expresses the risk of the
    | action if it were genuinely unlogged.
    */
    'severity_tiers' => [
        'CRITICAL' => ['destroy', 'delete', 'merge', 'reverse', 'refund', 'override', 'waive'],
        'HIGH' => ['approve', 'reject', 'verify', 'pay', 'dispense', 'administer', 'discharge', 'admit', 'cancel', 'transfer', 'release', 'submit'],
        'MEDIUM' => ['store', 'update', 'issue', 'record', 'complete', 'assign', 'restore', 'toggle'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Baseline file
    |--------------------------------------------------------------------------
    | Accepted findings snapshot. Anything not in the baseline is a NEW finding
    | (used by `--fail --strict`). Regenerate with `logs:audit --write-baseline`.
    */
    'baseline_path' => storage_path('app/logs-audit-baseline.json'),
];
