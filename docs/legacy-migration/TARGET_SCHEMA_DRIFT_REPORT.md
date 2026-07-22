# Target schema drift report

Generated from the installed non-production target and the current repository. No database changes were made.

## Confirmed migration-ledger drift

- Repository migration files: 306
- Installed migration rows: 305
- Repository migrations not installed: 1
- Installed migrations absent from repository: 0
- Heuristic repository end-state tables missing from target: 0
- Installed tables requiring manual origin verification: 24

### Repository migrations not installed

- `2026_07_20_000001_seed_admission_billing_amount_permission`

### Installed migrations absent from repository

- None.

## Static-analysis candidates (not confirmed drift)

The parser applies common create/alter/drop operations in migration order. Dynamic helpers, package migrations and raw SQL still require repository verification.

### Heuristic repository end-state tables missing from target

- None.

### Installed tables requiring manual origin verification

- `admission_bed_charges`
- `admission_daily_consumable_charges`
- `admission_discharge_clearances`
- `attendance_classes`
- `consultation_action_idempotency_keys`
- `consultation_specialty_entries`
- `consultation_specialty_profiles`
- `consultation_specialty_sections`
- `consultation_specialty_templates`
- `doctor_consultation_preferences`
- `emergency_bay_assignments`
- `emergency_bed_charges`
- `emergency_daily_consumable_charges`
- `emergency_sessions`
- `emergency_session_contributors`
- `migrations`
- `model_has_permissions`
- `model_has_roles`
- `permissions`
- `roles`
- `role_has_permissions`
- `visit_consultation_route_logs`
- `visit_consultation_route_services`
- `visit_sources`

**Manual Phase 1B conclusion:** repository review traced these candidates to package/framework tables, dynamic helpers, or migration constructs the parser does not understand. No installed table is currently confirmed to lack a repository origin.

## Repository schema-dump comparison

- Dump present: yes
- Dump SHA-256: `304fd33d072722ea6712a9fcbc967c96c266e6d1f5c0f698cd811083c4430a39`
- Dump tables: 106
- Dump migration ledger rows: 133
- Dump last migration: `2026_05_13_000001_create_theatre_procedure_tables`
- Installed tables absent from dump: 230
- Dump tables absent from installed target: 1

The dump is a stale historical snapshot and is not the installed target contract. The only dump table absent from the installed target is expected to be the intentionally retired `drug_stock`; later migrations also intentionally remove `visits.assigned_doctor_id`.

## Conditional and raw-DDL checks

- Conditional migrations discovered: 46
- Named raw-DDL indexes expected but absent: 1

- **Confirmed absent installed index:** `invoices.uq_invoices_active_visit` from `2026_05_11_000003_enforce_unique_active_invoice_per_visit`.

The installed `invoices.active_visit_id` generated column is present, but its intended unique index is absent; the non-unique fallback is installed. One-active-invoice-per-visit is therefore service-enforced only.

## Heuristic column comparison

- Candidate expected columns absent: 9.
- Candidate installed columns not represented by the static state: 249.
- **Interpretation:** Inferred/static-analysis candidates, not confirmed drift, until the migration branch and later drops/renames/raw DDL are manually verified.
- Limitation: Static parser covers common Schema blueprint operations and named raw indexes; dynamic PHP, anonymous raw DDL, driver branches, morph macros and complex conditional reversals require manual verification.
- **Manual Phase 1B conclusion:** repository review traced the current missing-column candidates to intentional later removals and the current extra-column candidates to helpers, raw SQL, or dynamic migration constructs outside the static parser. The generated candidate counts above remain evidence, not hard-coded conclusions. No installed column is currently confirmed to lack a repository origin, and no current expected column is confirmed missing.

Complete candidate rows and all installed constraints/indexes are in `TARGET_CONSTRAINT_MANIFEST.json`.

## Service-only invariants

- `one_active_invoice_per_visit`: Migration can fall back to a non-unique index on MariaDB. Evidence: `database/migrations/2026_05_11_000003_enforce_unique_active_invoice_per_visit.php`.
- `one_active_admission_per_patient_and_visit`: Service enforced; no complete database unique constraint. Evidence: `app/Services/AdmissionService.php`.
- `one_medical_record_per_consultation_route`: Model hasOne is not a database unique constraint. Evidence: `app/Models/VisitConsultationRoute.php`.
- `one_visit_per_patient_per_day`: Operational service rule; database uniqueness may not enforce it. Evidence: `app/Services/VisitService.php`.
- `valid_claim_status_transition`: Transition service does not uniformly enforce enum transition rules. Evidence: `app/Services/Claims/ClaimStatusService.php`.
- `balanced_journal_and_open_period`: Validated during operational posting, not fully expressed as database constraints. Evidence: `app/Services/JournalEntryService.php`.
- `route_context_referential_consistency`: Route, patient, visit, department, service and medical-record links include application-only references without complete foreign keys. Evidence: `app/Services/ConsultationRouteService.php`.
- `payment_allocation_validity`: Positive amounts, item ownership, exact allocation and no overpayment are service checks; duplicate payment/item allocations are not database-unique. Evidence: `app/Services/PaymentService.php`.
- `stock_balance_derived_from_movements`: Balance derivation, movement direction and weighted-average valuation are operational service rules. Evidence: `app/Services/ProductStockMovementService.php`.
- `active_bed_occupancy`: Bed availability and active occupancy are checked/mutated operationally, not protected by a complete database uniqueness rule. Evidence: `app/Services/AdmissionService.php`.
- `enum_compatible_strings`: Only two installed columns use native SQL ENUM; most enum-backed varchar values require application/import validation. Evidence: `app/Enums and model casts`.
- `patient_number_sequence_reconciliation`: Uniqueness is database-enforced on patient_number, but current-period sequence state and collision behavior are service-managed. Evidence: `app/Services/PatientIdGeneratorService.php`.
