# Payment Timing Policy — Phase 5 Report

**Patient Financial-Risk Profiles, Restricted Visibility, Review Workflow, History, and Audit**

Date: 2026-07-12 · Branch: `beta-x` · Status: Complete (patient-level administrative data only)

---

## 1. Existing architecture reviewed

- **Patient**: `app/Models/Patient.php` (SoftDeletes, LogsActivity, merge handling, `scopeSearch`, privacy relations). Profile page is a tabbed Blade (`resources/views/patients/show.blade.php`) rendered by `Admin\Patients\PatientController@show`.
- **Privacy/security**: `PatientPrivacyService`, `PatientFieldAuthorizationService`, Spatie permissions seeded in `RoleSeeder`, permission migrations per module (e.g. front-desk pattern). `ActivityLogService` is the single audit wrapper (`activity_log` table) with field masking and async option.
- **Billing**: `PatientOutstandingBalanceService`, `PreviousBalanceOverrideService`, `VisitBillingOverrideService`, `InvoiceReceivable(Service)`. Money is `decimal(10,2)` cast `decimal:2`.
- **Enums**: backed enums with `label()`/`description()` via `__()`, helper factory methods (e.g. `VisitPaymentTimingPolicy`).
- **Status-history**: existing modules use event-typed history rows with JSON old/new + performer + timestamp.

No existing framework was duplicated; the privacy/permission/audit/enum conventions were reused directly.

## 2. Data-model decision

Two dedicated tables (not overloading patient/invoice/visit status):

- `patient_financial_risk_profiles` — one row per classification; the current classification occupies the single "active slot".
- `patient_financial_risk_history` — append-only, immutable transitions (shortened FK name `pfr_history_profile_fk` for MySQL's 64-char limit).

`credit_limit` is `decimal(10,2)`, informational only. Free-text fields are length-bounded (`reason_details`/`clearance_reason` 1000, `reference` 191). Indexed on patient, status, risk level, review date, expiry date.

## 3. Files created

Enums: `PatientFinancialRiskLevel`, `PatientFinancialRiskReason`, `PatientFinancialRiskStatus`, `PatientFinancialRiskEvent`.
Data: `Data/Billing/PatientFinancialRiskData`.
Models: `PatientFinancialRiskProfile`, `PatientFinancialRiskHistory`.
Service: `Services/Billing/PatientFinancialRiskService`; Exception `InvalidFinancialRiskTransitionException`.
Migrations: profiles, history, permissions seed (3).
Requests: `Store`/`Update`/`Review`/`Suspend`/`Reactivate`/`Clear` PatientFinancialRiskRequest.
Controllers: `Admin\Patients\PatientFinancialRiskController`, `Admin\Billing\FinancialRiskController`.
Commands: `FinancialRiskExpireCommand` (`billing:financial-risk-expire`), `FinancialRiskAuditCommand` (`billing:financial-risk-audit`).
Views: `patients/partials/_financial-risk*.blade.php` (section, action, form), `admin/billing/financial-risk/{index,report}.blade.php`.
Localisation: `lang/{en,fr}/patient_financial_risk.php`.
Factory: `PatientFinancialRiskProfileFactory`.
Tests: 6 files (25 tests).

## 4. Files modified

`Patient.php` (relations/scopes), `PatientController@show` (permission-gated data loading), `RoleSeeder.php` (permissions + Accountant/Finance Manager grants), `routes/web.php` (patient-scoped + admin worklist routes), `routes/console.php` (daily expiry schedule), `patients/show.blade.php` (guarded tab + pane).

## 5. Enum vocabulary

- Level: `normal`, `watchlist`, `high_risk`, `blocked_credit` (with `isRestrictive()`, colour).
- Reason: 8 structured reasons; `other` and `management_decision` require details/reference.
- Status: `active`, `under_review`, `suspended`, `cleared`, `expired` (with `occupiesActiveSlot()`, `isTerminal()`, `allowedTransitions()`).
- Event: 8 material events mapping to `PATIENT_FINANCIAL_RISK_*` activity actions.

No enum makes a payment decision.

## 6. Active-profile uniqueness strategy

Only one profile per patient may be `active` or `under_review`. Enforced in the service inside a `DB::transaction` with `lockForUpdate()` on the patient's active-slot rows: `createOrClassify` mutates the current active profile instead of creating a duplicate; clearing then reclassifying creates a new, historically-traceable profile. Verified by `test_only_one_active_restrictive_profile_can_exist` and `test_clearing_then_reclassifying_creates_new_traceable_profile`.

## 7. State-transition rules

`active↔under_review`, `active/under_review→suspended`, `suspended→active/under_review`, any non-terminal `→cleared/→expired`. `cleared` and `expired` are terminal. Reactivation, suspension and clearance each require a reason. Invalid transitions throw `InvalidFinancialRiskTransitionException` (HTTP → flash error). No transition touches a visit or payment gate.

## 8. Review and expiry workflow

Optional `review_due_at` (advisory badge only) and `expires_at`. `billing:financial-risk-expire` transitions due active/under-review/suspended profiles to `expired`, transactionally, appending history + one activity log; idempotent (re-checks under lock; skips terminal). Scheduled daily 01:15 with `withoutOverlapping()->onOneServer()`. A read-only review-due view is provided via the worklist "Due for review" filter.

## 9. Permissions and initial role assignment

`patients.financial_risk.{view,manage,review,clear,history,report}`. Super Admin/Admin: all (via `Permission::all()` sync + migration). Finance Manager: all (incl. clear). Accountant: view/manage/review/history/report (no clear). Deliberately **not** granted to clinical or reception roles. Enforced on routes (`can:` middleware), FormRequests (`authorize`), and controllers.

## 10. Privacy and restricted-visibility behaviour

Financial-risk data lives in separate tables/relations and is **never** part of patient JSON, search, props or exports by default. `PatientController@show` loads it **only** when the viewer has `patients.financial_risk.view` (history only with `...history`); the Blade tab/pane is `@can`-gated. Confirmed by `test_unauthorised_user_never_receives_financial_risk_data_in_page_source` (asserts neither the confidential marker nor the pane leak into the page source).

## 11. Patient profile UI

A restricted "Financial Risk" tab shows current level/status/reason/dates/credit-limit/actors, a neutral empty state for normal patients, permission-scoped actions (classify/edit/submit-review/complete-review/suspend/reactivate/clear via modals), and the localised history table. No inline JS (Bootstrap data attributes).

## 12. Financial-risk worklist

`admin/billing/financial-risk` — search (privacy-aware patient search), filters (level, status, review-due, expired, active restriction), sortable columns, pagination, permission-safe links to patient profiles.

## 13. History design

Immutable append-only rows with event, JSON material old/new values (level/status/dates/reason only — never patient snapshots), performer, timestamp. Rendered as a localised, human-readable table; no edit/delete surface; no raw JSON dumped.

## 14. Activity-log behaviour

Single authoritative mutation path: the **service** appends both the immutable history row and one `ActivityLog` entry per material event, inside the same transaction (history is the in-transaction authoritative audit; if it fails the mutation rolls back). No observers duplicate logging. Reads create no activity log (verified). Audit metadata excludes the free-text `reference` and uses the module `BILLING`.

## 15. Balance-context integration

Existing balance services remain the source of truth and are **not** copied into the profile; no snapshot is persisted. Risk level is never auto-derived — a user must confirm the classification. Verified that outstanding balance is unaffected by classification.

## 16. Reporting and export scope

Aggregated report (active watchlist/high-risk/blocked-credit, due-for-review, expiring-soon, cleared/created this month) gated by `...report`. CSV export limited to necessary fields (patient number/name, level, status, reason, dates, credit limit) — no clinical/insurance/contact data or raw audit JSON.

## 17. Expiry command and scheduler behaviour

See §8. Idempotent, transactional, bounded, overlap-protected; changes no payment gate, visit or invoice.

## 18. Diagnostic command results

`billing:financial-risk-audit` (read-only, no writes, no activity logs, no sensitive free-text; findings never fail exit code) detects: multiple active profiles, active-beyond-expiry, date-ordering errors, missing setter, missing required details, cleared-without-reason, cleared-timestamp inconsistency, review-overdue. Verified by `test_audit_command_reports_anomalies_as_valid_json_without_writes` and the clean-run test.

## 19. Seeder and existing-data decisions

Only permission seeding (migration + `RoleSeeder`). No production patient is classified; existing patients simply have no active profile. A test-only factory exists; no backfill infers risk from old debt.

## 20. Performance and query impact

Financial-risk data is loaded only when authorised and requested (the normal clinical workflow incurs no risk query). History/worklist use eager loading (`setter/reviewer/...`, `patient` select-columns) and pagination; indexed status/date columns.

## 21. Focused tests and results

All executed and passing:

| Suite | Tests |
|---|---|
| `PatientFinancialRiskEnumTest` | 5 |
| `PatientFinancialRiskServiceTest` (model/workflow/expiry/uniqueness/audit) | 8 |
| `PatientFinancialRiskPermissionTest` (permission/confidentiality/UI) | 5 |
| `PatientFinancialRiskCommandTest` (expiry + diagnostic) | 3 |
| `PatientFinancialRiskNonEnforcementTest` | 3 |
| `PatientFinancialRiskLocalizationTest` | 1 |
| **Total** | **25 passed** |

Regression (unchanged): `PaymentTiming | PaymentGateService | BillingPaymentPolicy | PreviousBalance | LaboratoryPaymentGate | Pharmacy | ConsultationPaymentReadiness | TriagePayment` → **96 passed**. Project `LanguageParityTest` → **2 passed**. Migrations run clean; blade compiles; `git diff --check` clean. Full Laravel/Playwright suites were **not** run, per spec.

## 22. Confirmation: payment gates and visit policies unchanged

`VisitPaymentTimingResolver`, `PaymentGateService`, `BillingPolicyService`, previous-balance behaviour, laboratory/pharmacy/emergency/inpatient behaviour, invoices, receivables, allocation, GL, visit completion, discharge and financial closure are all untouched. The service never calls those subsystems; `PatientFinancialRiskNonEnforcementTest` proves a `blocked_credit` classification creates no visit/invoice/override and leaves the advisory billing outcome and outstanding balance unchanged.

## 23. Deferred work for Phase 6

- Risk-based visit-policy resolution (currently none): let `high_risk`/`blocked_credit` inform a future typed payment-timing decision — behind explicit approval, mirroring Phase 4's non-operational-until-approved model.
- Optional non-sensitive "financial review required" indicator for specific finance workflows (method surface prepared conceptually; not exposed to clinical workspaces this phase).
- Optional assessment snapshot (clearly historical) if the hospital wants a point-in-time balance capture.
- Notification automation for due reviews/expiry (only with an approved existing framework and scope).
