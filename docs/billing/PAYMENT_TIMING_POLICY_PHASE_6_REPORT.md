# Payment Timing Policy — Phase 6 Report

**Visit-Level Policy Materialisation, Financial-Risk Snapshots, and Non-Operational Recommendations**

Date: 2026-07-12 · Branch: `beta-x` · Status: Complete (observational only)

---

## 1. Existing visit-creation architecture audited

- **Creation paths:** `Visit::create` is the single Eloquent entry point for all five creators — `VisitService::create` (direct/walk-in/scheduled), `AppointmentService` (appointment→visit), `EmergencyCaseService`, `Maternity\AntenatalVisitService`, and the maternity test-data service. There is **no** pre-existing visit domain event.
- **Observers are an established convention** (`InvoiceObserver`, `PaymentObserver`, `UserObserver` registered in `AppServiceProvider`).
- **Baseline resolver:** `VisitPaymentTimingResolver::resolve(Visit): VisitPaymentTimingDecision` already returns a never-`inherit` policy + source + reason code + rich `context` (global default, visit-type config, emergency flag, legacy override id/type/scope, integration mode).
- **Financial risk (Phase 5):** `PatientFinancialRiskService::currentFor(Patient)` returns the single active-slot profile; `PatientFinancialRiskProfile` exposes level/status/primary_reason.
- **Snapshot/history patterns:** established immutable event-typed history with JSON old/new + performer + timestamp (Phase 5).

## 2. Materialisation integration point

A new **`VisitObserver::created()`** — the single central hook covering every creation path (and test/fixture paths) without duplicated controller calls. It is gated by `config('visit_payment_policy.auto_materialize')`, wrapped in try/catch (failure logs and is swallowed — never blocks visit creation or emergency care; repairable via backfill), and calls `materialize(..., logActivity: false)` so the clinical hot path stays light (no activity-log noise). Verified: 303 existing visit/payment tests still pass.

## 3. Data-model decision

Two dedicated tables — `visit_payment_policies` (one observational record per visit, `visit_id` unique) and append-only `visit_payment_policy_history`. Baseline (`resolved_*`) and recommendation (`recommended_*`) are **separate columns**. No operational/active flag exists. Short FK names (`vpp_risk_profile_fk`, `vpp_history_policy_fk`) respect MySQL's 64-char limit.

## 4. Files created

Enum `VisitPaymentPolicyEvent`. DTOs `PatientRiskPaymentRecommendation`, `PatientFinancialRiskPolicyRule`, `VisitPaymentPolicyPreview`. Config `config/visit_payment_policy.php`. Migrations (policies, history, permissions). Models `VisitPaymentPolicy`, `VisitPaymentPolicyHistory`. Services `PatientFinancialRiskPolicyConfigurationService`, `PatientRiskPaymentRecommendationService`, `VisitPaymentPolicyMaterializationService`. Observer `VisitObserver`. Commands `VisitPaymentPolicyBackfillCommand`, `VisitPaymentPolicyRefreshCommand`, `VisitPaymentPolicyAuditCommand`. Controller `Admin\Billing\VisitPaymentPolicyController`. Views (`index`, `show`, `report`). Localisation `lang/{en,fr}/visit_payment_policy.php`. Factory `VisitPaymentPolicyFactory`. 7 test files (32 tests).

## 5. Files modified

`Visit.php` (`paymentPolicy`/`paymentPolicyHistory` relations), `AppServiceProvider.php` (observer registration), `RoleSeeder.php` (permissions + grants), `routes/web.php` (worklist/detail/report/refresh routes).

## 6. Visit-policy record fields

Baseline (`resolved_policy`/`resolution_source`/`resolution_reason_code`); recommendation (`recommended_policy`/`recommendation_source`/`recommendation_reason_code`/`requires_finance_review`); policy-context snapshots (`global_default_snapshot`, `visit_type_policy_snapshot`, `visit_type_snapshot`, `emergency_protection_snapshot`); compatible-override snapshots (type/scope/id); risk snapshots (`patient_financial_risk_profile_id`, level/status/reason enums, `patient_risk_observed_at`); `resolution_version`, `materialized_at`, `last_refreshed_at`. Enum-cast; indexed on resolved policy, source, finance-review flag, risk level, materialisation date.

## 7. History design

Append-only `visit_payment_policy_history` with `VisitPaymentPolicyEvent` (materialized/refreshed/…), JSON material old/new (policy + risk-snapshot fields only), reason code, performer, timestamp. Rendered as a localised, human-readable table; no edit/delete surface; no raw JSON.

## 8. Risk recommendation rules

Per §9 of the spec: no active profile / normal → none; watchlist → review only (no policy); high_risk / blocked_credit → recommend `pay_before_service` + review; suspended/cleared/expired → no active recommendation; under_review → review only, no forced policy. Reason codes are stable machine strings on the DTO (never translated). Every recommendation is `approvedForResolution = false`.

## 9. Risk-rule approval configuration

`PatientFinancialRiskPolicyConfigurationService` reads typed rules from `config/visit_payment_policy.php` (not admin-editable in Phase 6). It **forces** `approved_for_resolution = false` for every level regardless of config, and falls back to safe defaults for invalid values — no stored/config value can make a recommendation operational (mirrors Phase 4's safety model).

## 10. Baseline-vs-recommendation separation

`resolved_policy` always comes from `VisitPaymentTimingResolver`; the recommendation lives in `recommended_policy`. The materialisation service computes them independently and never substitutes one for the other — proven by `test_risk_recommendation_is_stored_separately_from_resolved_policy` (a high-risk patient's inpatient visit stores `resolved=running_bill` from the baseline while `recommended=pay_before_service`). The audit command flags `recommendation_treated_as_resolved` if a baseline is ever sourced from `patient_risk`.

## 11. Risk snapshot design

Captures only profile id, level, status, structured reason, and observation timestamp. `test_snapshot_captures_bounded_fields_only` asserts free-text `reason_details`/`reference` never appear in the stored payload. No contact info, clinical data, or history is captured.

## 12. Snapshot staleness rules

`snapshotIsStale()` (diagnostic only; never mutates policy) returns true when: the patient now has a different active profile; the snapshotted profile later left the active slot or changed level/status; the profile was updated after observation; or there is no snapshot despite a current restrictive profile. Verified across four snapshot tests.

## 13. Materialisation and refresh behaviour

`materialize()` is idempotent (creates once, never overwrites an existing snapshot). `refresh()` is the only controlled way an existing snapshot changes: it recomputes and, on material change, updates the record + appends one `REFRESHED` history entry + one activity log; an unchanged refresh writes nothing. Phase 6 performs **no** automatic refresh (no scheduler entry).

## 14. Visit-creation coverage

The observer covers outpatient, inpatient, emergency, appointment-generated and direct/walk-in visits (all funnel through `Visit::create`). Materialisation tests assert correct baselines for outpatient (global default), inpatient (running_bill/visit_type) and emergency (running_bill/emergency_policy + emergency_protection flag).

## 15. Existing-visit backfill behaviour

`billing:visit-payment-policy-backfill` — dry-run by default, `--commit` writes, chunked, bounded, `--missing-only` semantics (skips existing), idempotent, creates no invoices/payments/overrides and changes no visit status. Verified by four backfill/command tests.

## 16. Permissions and restricted visibility

`visits.payment_policy.{view,history,report,refresh}`. Super Admin/Admin: all; Finance Manager: all (incl. refresh); Accountant: view/history/report (no refresh); clinical/reception: none. `refresh` never means approve/activate. Enforced on routes (`can:` middleware) and controllers; history loads only with the history permission. Confirmed by the permission test.

## 17. Visit-level UI and worklist

A restricted detail page (observed policy, source, reason, snapshots, recommendation, finance-review flag, freshness, timestamps) prominently labelled *"This record is observational and does not currently control service access or payment enforcement,"* distinguishing Observed / Recommended / (legacy gate). A finance worklist (`admin/billing/visit-payment-policies`) with filters (resolved/recommended policy, source, risk level, visit type, finance-review, active-only, date range), a stale indicator column, and pagination. No approval/override action exists.

## 18. Activity-log behaviour

Explicit materialise/refresh via service/command writes one bounded `VISIT_PAYMENT_POLICY_*` event (visit id, policy id, old/new resolved + recommendation, source, risk level, finance-review flag — no patient name/contact/clinical/free-text). The auto observer path writes **no** activity log (kept light). Reads/previews never log. Single logging path (service) — no observer/service duplication.

## 19. Diagnostic command results

`billing:visit-payment-policy-audit` (read-only, no writes/logs, no sensitive text, findings never fail the exit code) detects: missing records, resolved `inherit`, unknown source, recommendation/source mismatches, `recommendation_treated_as_resolved`, restrictive-snapshot-without-recommendation, missing version, invalid timestamps, invalid recommended policy, and stale snapshots. Verified read-only.

## 20. Query and performance impact

Materialisation reuses the already-loaded visit/patient, cached payment-timing settings, one bounded active-risk-profile query and the resolver's bounded override query — no invoice/receivable/payment/balance queries. Lists eager-load visit+patient, paginate, and compute staleness only for the visible page. **Payment-gate evaluation never queries `visit_payment_policies`.**

## 21. Focused tests and results

All executed and passing — **32 Phase 6 tests** (`PatientRiskPaymentRecommendationTest` 8, `VisitPaymentPolicyMaterializationTest` 8, `VisitPaymentPolicySnapshotTest` 4, `VisitPaymentPolicyPermissionTest` 4, `VisitPaymentPolicyBackfillTest` 4, `VisitPaymentPolicyNonEnforcementTest` 3, `VisitPaymentPolicyLocalizationTest` 1). Regression + visit suite: **303 passed** (`PatientFinancialRisk | PaymentTiming | PaymentGateService | BillingPaymentPolicy | PreviousBalance | TriagePayment | ConsultationPaymentReadiness | LaboratoryPaymentGate | Pharmacy | LanguageParity | Visit*`). Migrations clean; blade compiles; `git diff --check` clean; diagnostics run read-only. Full Laravel/Playwright suites **not** run, per spec.

## 22. Confirmation: legacy payment decisions remain authoritative

`VisitPaymentTimingResolver`, `BillingPolicyService`, `PaymentGateService`, previous-balance, laboratory/pharmacy/emergency/inpatient/triage/consultation behaviour, invoices, receivables, allocation, GL, completion, discharge and closure are all unchanged. `test_resolver_remains_risk_unaware` proves classifying a blocked-credit patient does not change the resolver output; `test_billing_policy_advisory_outcome_unchanged_by_recommendation` proves the billing advisory outcome is untouched.

## 23. Confirmation: risk recommendations remain non-operational

Every rule/recommendation is `approved_for_resolution = false` (unit-tested for all levels). No production caller reads `visit_payment_policies`; the record has no operational flag; materialisation creates no invoice/override/visit-state change (`test_materialisation_creates_no_invoice_override_or_visit_state_change`).

## 24. Deferred requirements for Phase 7

- An **explicit, authorised approval workflow** that could promote a recommendation to an approved visit-specific policy (gated, audited, still separate from the legacy gate) — the natural next step once `approved_for_resolution` may become true per level.
- Admin editability of the risk-rule configuration (currently typed config only).
- Optional controlled auto-refresh triggers (e.g. on risk-profile change) with history — deliberately excluded in Phase 6.
- Wiring an approved visit-specific policy into `PaymentGateService`/resolver precedence — only after approval + cutover controls exist.
