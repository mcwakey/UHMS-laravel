# Payment Timing Policy — Phase 7 Report

**Authorised Per-Visit Payment Arrangements, Request/Approval Workflow, and Revocation**

Date: 2026-07-12 · Branch: `beta-x` · Status: Complete (administrative only)

---

## 1. Existing approval/override architecture audited

- **`VisitBillingOverride` / `VisitBillingOverrideService`**: an operational override the Phase 2 resolver reads (types `DEFERRED_OPD_SETTLEMENT`, `CREDIT_APPROVAL`, `PAYMENT_GATE_BYPASS`, `PREVIOUS_BALANCE_OVERRIDE`, …; scopes VISIT/DEPARTMENT/SERVICE/INVOICE_ITEM; statuses ACTIVE/REVOKED/EXPIRED/COMPLETED). Its semantics **differ** from an administrative arrangement (it changes production behaviour), so per spec §4 it was **not** reused.
- **Phase 6 materialisation**: `VisitPaymentPolicy` (`resolved_policy` baseline, `recommended_policy` risk recommendation), `VisitPaymentPolicyMaterializationService`, snapshot-staleness logic, worklist/detail UI, backfill/refresh/audit commands.
- **Phase 5 risk**: `PatientFinancialRiskService::currentFor`, active-slot semantics, restricted permissions, immutable history.
- **Conventions reused**: `ActivityLogService` single audit path, permission-migration + `RoleSeeder` grants, Carbon-cast immutable history tables, `PatientOutstandingBalanceService::getPreviousOutstandingBalance` for balance context.

**Decision:** a dedicated `visit_payment_arrangements` table (one visit → many historical requests; requester/approver traceability; separate from the observational record).

## 2. Data-model decision

`visit_payment_arrangements` + append-only `visit_payment_arrangement_history`, plus three **additive** columns on `visit_payment_policies` (`current_approved_arrangement_id`, `approved_policy_snapshot`, `approved_arrangement_observed_at`). No `is_operational` flag. `requested_policy`/`approved_policy` never store `inherit`. Short FK names (`vpa_policy_fk`, `vpa_replaced_by_fk`, `vpa_history_arrangement_fk`, `vpp_current_arrangement_fk`).

## 3. Files created

Enums `VisitPaymentArrangementStatus/Source/Event`. DTOs `VisitPaymentArrangementData`, `VisitPaymentArrangementApprovalData`, `VisitPaymentArrangementApprovalRequirement`. Config `config/visit_payment_arrangement.php`. Migrations (arrangements, history, additive VPP columns, permissions). Models `VisitPaymentArrangement`, `VisitPaymentArrangementHistory`. Exception `VisitPaymentArrangementException`. Services `VisitPaymentArrangementApprovalPolicyService`, `VisitPaymentArrangementService`. 7 FormRequests. Controller `Admin\Billing\VisitPaymentArrangementController`. Commands `VisitPaymentArrangementExpireCommand`, `VisitPaymentArrangementAuditCommand`. Views (worklist `index`, `show`, `report`, `_reason-action`). Localisation `lang/{en,fr}/visit_payment_arrangement.php`. Factory. 6 test files (28 tests).

## 4. Files modified

`Visit.php` (arrangement relations), `VisitPaymentPolicy.php` (link fields + relations), `VisitPaymentPolicyController@show` + its Blade (4-card context + request/restore actions + emergency notice), `RoleSeeder.php`, `routes/web.php`, `routes/console.php`.

## 5. Status / source / event vocabulary

- Status: `pending`, `approved`, `rejected`, `withdrawn`, `revoked`, `expired`, `replaced` (terminal = all except pending/approved).
- Source: 8 administrative provenances (manual_request … baseline_restoration).
- Event: `requested`, `approved`, `rejected`, `withdrawn`, `revoked`, `expired`, `replaced`, `updated_before_decision`, `baseline_restored`.

## 6. Request lifecycle

Request snapshots the request-time baseline, recommendation, risk level/status and finance-review flag (never free-text risk details or contact/clinical data). Pending requests can be updated (recomputing requirements) while pending; one pending request per visit is enforced under a row lock. Approval → one current approved arrangement; a new approval marks the prior one `replaced` (with `replaced_by_arrangement_id`). Reject/withdraw are pending-only; revoke/expire are approved-only; restore-baseline removes the current arrangement.

## 7. Approval requirement rules

`VisitPaymentArrangementApprovalPolicyService` (administrative, no gate decision): prepayment requests require a decision but no separate approver; deferral requests (`pay_after_all_services`/`running_bill`) require a **separate finance-manager** approver when the baseline is prepay, the risk snapshot is high-risk/blocked-credit, a prepay recommendation exists, there is a material previous balance (config threshold), or running-bill is requested on an outpatient visit whose baseline isn't already running-bill. Watchlist raises finance-review without forcing a separate approver.

## 8. Self-approval prevention

Enforced in the **service** (not just the UI): `requested_by === actor` throws `self_approval` unless the caller holds the dedicated `visits.payment_arrangement.self_approve` permission (granted to **no role** by default). Verified by `test_requester_cannot_approve_own_request` and the permission test.

## 9. Risk-based approval requirements

Restrictive snapshots drive separate-approver/finance-manager requirements (above). At approval, `riskIsStale()` compares the request-time snapshot against the patient's current risk state; if it differs, approval is blocked (`stale_risk`) unless `confirm_stale_risk` is passed — never a silent approval on outdated data. The patient's risk profile itself is never modified.

## 10. Previous-balance context handling

`PatientOutstandingBalanceService::getPreviousOutstandingBalance` informs the approval requirement only (threshold in config). No full invoice lists are copied, no parallel balance calc, and approvals create **no** previous-balance override — those remain a separate prior-debt exception.

## 11. Approved-arrangement linkage to materialised visit policy

On approval the service sets `current_approved_arrangement_id` + `approved_policy_snapshot` + `approved_arrangement_observed_at` on the visit-policy record, **leaving `resolved_policy` and `recommended_policy` untouched** (asserted by test). Revoke/expire/restore clear the link. The Phase 6 detail page now shows four distinct cards: Observed baseline / Risk recommendation / Approved administrative arrangement / Operational legacy gate.

## 12. Replacement, revocation, and expiry behaviour

Replacement preserves the old record's approval data and links it forward. Revocation (reason required) clears the current link, preserves baseline/recommendation, creates no new arrangement. Expiry via `billing:visit-payment-arrangement-expire` (dry-run default, `--commit`, scheduled daily 01:20 `withoutOverlapping`+`onOneServer`) transitions due approved arrangements to `expired`, idempotently, touching no terminal records.

## 13. Permissions and role assignments

`visits.payment_arrangement.{view,request,approve,reject,withdraw,revoke,history,report,restore_baseline}` (+ ungranted `self_approve`). Super Admin/Admin: all; Finance Manager: all incl. approve/reject/revoke/restore; Accountant: view/request/withdraw/history/report (no approval); clinical/reception: none. Enforced on routes (`can:` middleware), FormRequests, and service.

## 14. Privacy and restricted visibility

Arrangement data lives in dedicated tables loaded only for authorised finance/admin pages (never in patient JSON/props). History requires its own permission. Snapshots store enums/ids only — no free-text risk details, contact or clinical data. Worklist masks patient identity to authorised finance users and shows no free-text reasons in the broad list.

## 15. Worklist and detail UI

Worklist (`admin/billing/visit-payment-arrangements`) with status/policy/source/risk-level/visit-type/finance-review/expiring-soon filters, pagination, permission-safe links. Detail page shows the four separated cards, state-dependent approve/reject/withdraw/revoke forms, an emergency-safety notice, a stale-context banner + explicit confirmation, and a human-readable history table. Prominent notice: *"Approval records an administrative arrangement only. It does not yet change live service-access enforcement."*

## 16. History and activity-log behaviour

Every mutation appends one immutable history row and writes exactly one bounded `VISIT_PAYMENT_ARRANGEMENT_*` (or `VISIT_PAYMENT_BASELINE_RESTORED`) activity event with visit/arrangement ids, requested/approved policy, statuses, requester/approver ids, risk level and finance-review flag — no patient name/contact/clinical/free-text risk details. Reads create no logs; a single service logging path avoids observer/service duplication.

## 17. Audit and expiry command results

`billing:visit-payment-arrangement-audit` (read-only, no writes/logs/sensitive text; findings never fail the exit code) detects multiple pending/approved, approved-without-policy, pending-with-approved-policy, requester=approver, expired-still-current, inherit policy, expiry-before-effective, terminal-missing-timestamp, stale-risk, and missing visit-policy link. Verified by `test_audit_command_detects_anomalies_read_only`. Expiry command verified dry-run→commit→idempotent.

## 18. Concurrency protection

All mutations run in a `DB::transaction` with `lockForUpdate` on the visit's pending/approved rows and a status recheck inside the lock; duplicate-pending is rejected under lock; replacement locks and marks prior approved rows atomically. Verified by duplicate-pending, replacement (one current approved remains), terminal-reuse and stale-risk tests.

## 19. Query and performance impact

Normal clinical workflows never query arrangements. Only restricted finance pages load pending/approved/history, eager-loaded (`visit`, `patient`, `requester`, `approver`) and paginated. `BillingPolicyService` and `PaymentGateService` do **not** query arrangement tables.

## 20. Focused tests and results

All executed and passing — **28 Phase 7 tests** (`VisitPaymentArrangementServiceTest` 11, `…ApprovalPolicyTest` 6, `…PermissionTest` 5, `…CommandTest` 2, `…NonEnforcementTest` 3, `…LocalizationTest` 1). Regression: **147 passed** (`VisitPaymentPolicy | PatientFinancialRisk | PaymentTiming | PaymentGateService | BillingPaymentPolicy | PreviousBalance | TriagePayment | ConsultationPaymentReadiness | LaboratoryPaymentGate | Pharmacy | LanguageParity`). Migrations clean; blade compiles; `git diff --check` clean; all diagnostic commands read-only. Full Laravel/Playwright suites **not** run, per spec.

## 21. Confirmation: approved arrangements remain non-operational

`VisitPaymentTimingResolver`, `BillingPolicyService`, `PaymentGateService`, invoices, receivables, overrides, laboratory/pharmacy/emergency/inpatient/triage/consultation/previous-balance behaviour are all unchanged. `VisitPaymentArrangementNonEnforcementTest` proves an approved `pay_after_all_services`/`pay_before_service` arrangement leaves the resolver output and billing advisory outcome unchanged and creates no invoice/override; the resolver never reads arrangements; no unwired operation became wired.

## 22. Deferred requirements for Phase 8

- **Operational cutover controls**: a gated, audited mechanism by which an approved arrangement (once explicitly approved for resolution) could influence the gate/resolver precedence — behind cutover safety controls mirroring Phase 4.
- Optional management-approval tier and policy-specific permissions (`force_pre_service`/`allow_post_service`/…) if the hospital wants finer control.
- Optional reception request-only access per hospital workflow.
- Visit-lifecycle refinements (post-completion correction permission, cancelled-visit auto-withdrawal) if required by operational policy.
