# Payment Timing Policy Phase 3 Report

## Outcome

Phase 3 establishes `PaymentGateService` as the single production entry path for every payment-policy check found in the fresh audit. It adds stage-aware context, migrates triage and consultation readiness, explicitly separates laboratory intrinsic settlement from policy permission, centralises the existing pharmacy paid-only release check, and adds a maintained coverage registry/command. Legacy outcomes remain authoritative; typed enforcement is still unavailable.

## Complete payment-check call-site audit

| Location | Classification before Phase 3 | Stage | Phase 3 result |
| --- | --- | --- | --- |
| `TriageController::assertRouteServicesSettled` | Hard enforcement through direct `BillingPolicyService` call | Consultation route completion/routing | Migrated to `PaymentGateService::policyFor` with `consultation.route.complete`. Existing `RuntimeException` and message conversion retained. |
| `ConsultationNextPatientService::paymentReadiness` | Readiness display in preview and hard check in `openNext` | Readiness | Migrated to façade with `consultation.next_patient.readiness`; first blocker and local array shape retained. |
| `LabService::enterResult` | Hard intrinsic settlement check via `LabRequestItem::isBillSettled` | Result entry | Migrated to `policyForLabResultEntry`; existing paid/covered/waived/adjusted and missing-item results retained. |
| `PharmacyBillingSelectionService::isItemSettled` used by `PharmacyService::dispenseItem` | Hard paid-only dispensing check | Dispense | Migrated to `policyForPaidPharmacyItem`; the stricter paid-only behavior, including emergency behavior, remains unchanged. |
| `LabRequestItem::isBillSettled` | Intrinsic settlement calculation | Model helper/display | Retained. No policy service was moved into the model. |
| `resources/views/lab/process.blade.php` | Display/readiness status using intrinsic settlement | Result screen display | Retained as display-only; no new gate added in Blade. |
| `PharmacyService::getDispensingDetails` | Display/queue `is_settled` projection using `InvoiceItem::isPaid` | Dispensing display | Retained as display-only. |
| `resources/views/triage/index.blade.php` | Outstanding-balance display | Triage queue display | Retained as display-only. |
| invoice Blade views | Settlement/status display | Billing display | Retained as display-only. |
| `InvoiceService` / `PaymentService` uses of settlement service | Invoice balance/status calculation | Accounting mutation | Retained; these are calculations, not service permission gates. |
| `PreviousBalanceOverrideService` | Separate previous-debt validation | Registration/billing | Retained independently. |

After migration, the only production call to `BillingPolicyService::getInvoiceItemPolicy` is from `PaymentGateService`.

## Departmental workflow-stage matrix

| Workflow | Existing lifecycle found | Existing payment check | Production status after Phase 3 |
| --- | --- | --- | --- |
| Consultation/triage | service attach/order, pending route, triage route completion, consultation start, route complete/cancel | Triage completion hard gate; next-patient readiness/activation gate | Both checks wired through façade. Consultation-start façade exists but is intentionally unwired. |
| Laboratory | request, accept, sample collect/receive/reject, result entry, result verify, complete/cancel | Intrinsic settlement at result entry | Result entry wired; verification remains ungated as before. |
| Radiology/imaging | Uses investigation/lab request result workflow; skips specimen stages by department result type | Same result-entry check where `LabService` is used | Covered by lab result-entry wiring; no separate new gate. |
| Pharmacy | select/bill prescription quantity, queue/display, dispense/partial dispense, complete | Paid-only check immediately before dispense | Existing hard check wired through façade. |
| Procedures | request, accept, schedule, start, perform, complete/cancel | None found | `procedure.start` registered but not wired. |
| Theatre | accept/reject, schedule, start surgery, complete/cancel | None found | `theatre.perform` registered as missing coverage; no block added. |
| Generic service rendering | create, start, mark rendered/not rendered, cancel | None found | `service.render` façade retained and registered but not wired. |
| Treatments | Consultation treatment records; no independent paid service transition found | None found | `treatment.perform` registered only. |
| Nursing/inpatient | nursing notes/tasks, medication administration, task completion | None found | `nursing.service.render` registered only. |
| Admission/inpatient | request/accept, admit, reserve/assign/transfer/release bed, plan discharge, discharge | No service payment gate; existing running-bill/clearance structures are separate | No gate added. |
| Emergency | case creation, triage, under-care services, medications/procedures, tasks, disposition/session completion | No production service payment gate; legacy engine treats emergency as running bill | No gate added. Stabilisation boundary remains unresolved. |
| Blood bank | request, approve, crossmatch/verify, issue, transfuse/return | None found | `blood_bank.unit.issue` registered only. |
| Ambulance | No dedicated operational ambulance service workflow found | None | Future render operation registered; no production wiring. |
| Visit completion/discharge/financial closure | route/visit completion and admission discharge readiness/clearance | No `PaymentGateService` call | Unchanged and intentionally unwired. |

## Stage and context design

`PaymentGateStage` contains only stages used by current façade operations or the maintained coverage registry: `start`, `perform`, `result`, `complete`, `dispense`, `issue`, `render`, and `readiness`. Labels are localised; stored/logged values remain machine identifiers.

`PaymentGateContext` carries stage, stable operation code, optional department ID/type, service type, nullable emergency-stabilisation marker, and bounded metadata. Named constructors cover triage completion, consultation readiness, lab result entry, and pharmacy dispensing. Observation output intentionally emits only bounded stage/operation/type/presence fields and never emits arbitrary metadata or clinical/patient content.

UHMS cannot currently reliably distinguish emergency stabilisation from post-stabilisation routine care. `emergencyStabilisation` therefore remains null unless a future reliable workflow marker can populate it. Current emergency running-bill behavior is unchanged.

## Façade consolidation

`evaluateInvoiceItem` is the common normal-policy evaluator. Existing `policyFor`, `can*`, and `assert*` contracts delegate through it while retaining their old names, arguments, return values, `BillingGateException`, messages, and fail-open resolution behavior.

Two explicit compatibility policies live in `BillingPolicyService` and are accessible only through the façade:

- Laboratory intrinsic settlement permits exactly paid, fully covered, waived, or fully adjusted items. It still blocks cancelled/unpaid/partial items and ignores visit timing overrides, matching the old lab helper.
- Pharmacy paid-only release uses the existing `InvoiceItem::isPaid` rule. It does not newly allow emergency, inpatient, waiver, credit, deferred, or bypass cases that pharmacy previously blocked.

These compatibility paths also support typed observation but never substitute typed policy for the result.

## Missing invoice compatibility matrix

| Workflow | Missing invoice/item behavior preserved | Diagnostic reason |
| --- | --- | --- |
| Triage route, priced billable service | Blocks with “has not been billed” message before calling policy | Existing controller branch |
| Triage route, unpriced/non-billable service | Skips payment check | Existing controller branch |
| Consultation next-patient readiness | Route service with no invoice item is skipped | Existing readiness behavior |
| Laboratory result entry | Missing `invoice_item_id` or missing row is allowed | `INVOICE_ITEM_MISSING_ALLOWED` |
| Pharmacy dispensing | No outstanding billed selection blocks as “not billed”; selection without invoice item fails paid check | Existing pharmacy messages |
| Generic façade resolution | Unresolved invoice item remains allowed by existing `can*`/`assert*` wrappers | Existing fail-open behavior |
| Normal policy with item but no visit | Legacy `NO_VISIT_CONTEXT` allowed result | Existing policy behavior |

No inconsistent behavior was normalised in this phase.

## Operation registry and coverage

`PaymentGateOperationRegistry` describes 13 stable operations with stage, façade method, hard-gate status, production caller, and legacy/missing-item behavior. It makes no decisions.

Production wired operations (4):

- `consultation.route.complete`
- `consultation.next_patient.readiness`
- `laboratory.result.enter`
- `pharmacy.item.dispense`

Intentionally unwired operations (9): consultation start, investigation perform, procedure start, generic service render, theatre perform, treatment perform, nursing service render, blood-bank issue, and ambulance render. Their absence is informational and does not enable blocking.

`php artisan billing:payment-gate-coverage` reports the registry in table or JSON form, performs no writes, creates no ActivityLog rows, and exits successfully when wiring is incomplete. Local output reported 13 registered, 4 wired, and 9 unwired operations.

## Observation context

Phase 2 comparison logs now include payment-gate stage, operation code, department type, service type, invoice-item presence, and the nullable emergency-stabilisation marker. They continue to exclude patient names/contact data, diagnosis, clinical notes, insurance identifiers, and free-text reasons. Log/cache failures remain non-blocking.

## Performance and query impact

- Triage and consultation pass their already-loaded invoice items into the façade; no additional invoice resolution was introduced.
- Lab result entry reuses the already-loaded `LabRequest` and invoice-item relation where present. Missing invoice items return without a query; this has a focused zero-query assertion.
- Pharmacy reuses invoice items eager-loaded by the existing billing-selection query. The paid-only compatibility decision does not add settlement or invoice queries.
- Legacy mode still performs no typed resolution queries, as covered by Phase 2 tests.
- Observe-mode active override lookup remains memoised once per visit/resolver instance; a focused SQL-listener assertion verifies one override query across two resolutions.
- Display-only model/Blade checks remain untouched, avoiding new hidden N+1 policy reads.

## Files created

- `app/Enums/PaymentGateStage.php`
- `app/Data/Billing/PaymentGateContext.php`
- `app/Services/Billing/PaymentGateOperationRegistry.php`
- `app/Console/Commands/PaymentGateCoverageCommand.php`
- `tests/Unit/PaymentGateStageTest.php`
- `tests/Feature/PaymentGateServiceTest.php`
- `tests/Feature/TriagePaymentRegressionTest.php`
- `tests/Feature/ConsultationPaymentReadinessTest.php`
- `tests/Feature/LaboratoryPaymentGateTest.php`
- `tests/Feature/PaymentGateCoverageTest.php`
- `docs/billing/PAYMENT_TIMING_POLICY_PHASE_3_REPORT.md`

## Files modified

- `app/Services/Billing/BillingPolicyService.php`
- `app/Services/Billing/PaymentGateService.php`
- `app/Services/Billing/PaymentTimingPolicyComparisonService.php`
- `app/Http/Controllers/Admin/Patients/TriageController.php`
- `app/Services/ConsultationNextPatientService.php`
- `app/Services/LabService.php`
- `app/Services/PharmacyBillingSelectionService.php`
- `lang/en/payment_timing.php`
- `lang/fr/payment_timing.php`
- `tests/Feature/PaymentTimingIntegrationTest.php`
- `tests/Feature/PaymentTimingObservationTest.php`
- `tests/Feature/PaymentTimingSettingsTest.php`
- `tests/Feature/VisitPaymentTimingResolverTest.php`

No migration, setting, permission, admin screen, or ActivityLog mutation was added.

## Focused verification

Executed successfully:

- Phase 3 named tests: 19 tests, 116 assertions.
- Phase 1-3 `PaymentTiming` tests: 30 tests, 106 assertions.
- Existing `BillingPaymentPolicy`: 17 tests, 45 assertions.
- Existing `PreviousBalancePolicy`: 16 tests, 50 assertions.
- Existing pharmacy workflow: 9 tests, 34 assertions.
- Existing billing enhancements/lab settlement: 16 tests, 62 assertions.
- Existing investigation logging: 4 tests, 12 assertions.
- Existing sample management: 8 tests, 26 assertions.
- Existing consultation next-patient workflow: 10 tests, 60 assertions.
- Existing workflow JSON/triage behavior was rerun through the Phase 3 triage regression class.
- PHP syntax checks passed for all new/touched PHP files.
- Configuration and compiled views cleared; Blade view cache compiled.
- EN/FR localisation parity passed.
- Permission audit found zero missing route permissions and zero unprotected admin mutation routes.
- Logging integrity audit found zero real missing logs; its classified legacy backlog is unrelated.
- `billing:payment-timing-audit --limit=25`: 6 inspected, 6 matches, no mismatches/unavailable context.
- `billing:payment-gate-coverage`: 13 registered, 4 wired, 9 informationally unwired.
- `git diff --check` passed.

The full Laravel and Playwright suites were intentionally not run.

## Operational compatibility confirmation

Paid remains allowed, unpaid remains blocked where it was previously blocked, insurer-covered/waived/adjusted lab items retain their intrinsic behavior, missing items retain workflow-specific behavior, pharmacy remains paid-only even for emergency visits, legacy overrides retain their existing scopes, and no new department begins enforcing payment. Typed decisions remain observation-only.

## Phase 4 recommendations

Phase 4 should not simply wire all nine uncovered operations. It should first define approved enforcement stages per department, especially whether consultation starts, investigation performance versus result release, procedure start, theatre start, service rendering, and blood issue should ever block. Each cutover needs explicit emergency/inpatient treatment, missing-item semantics, service-scoped override rules, and rollback configuration.

Before any emergency stage-specific policy, UHMS needs a reliable existing marker for stabilisation versus post-stabilisation care. Phase 4 should also decide whether the lab/pharmacy compatibility policies remain intentional hospital policy or should later converge on the typed visit policy under an explicit enforcement rollout mode.
