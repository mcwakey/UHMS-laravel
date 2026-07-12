# UHMS Implementation Prompt

## Payment Timing Policy — Final Verification and Release Closure

You are working on **UHMS**, a Laravel-based hospital management system.

The Payment Timing Policy implementation batch, covering Phases 1–9, is functionally complete.

This task is a dedicated **verification, hardening, and release-closure exercise**.

It is not Phase 10.

Do not add new payment-policy features unless a defect discovered during verification requires a narrowly scoped correction.

---

# 1. Current Implementation State

The completed implementation includes:

1. Global and visit-type payment-timing policies
2. Legacy/typed policy observation and compatibility
3. Central `PaymentGateService` workflow integration
4. Departmental operation configuration and cutover eligibility
5. Patient financial-risk profiles
6. Visit-level observational policy materialisation
7. Approved per-visit payment arrangements
8. Controlled typed operational cutover
9. Financial clearance, conditional closure, and receivable preservation

The latest Phase 9 implementation provides:

* Visit financial-clearance snapshots
* Immutable clearance history
* Conditional outstanding-balance approval
* Maker-checker exception approval
* Active-mode-only financial closure
* Automatic staleness after invoice, invoice-item, receivable, or payment changes
* Financial worklists, reports, exports, settings, and rollback
* Safe deployment defaults
* English/French localisation
* Diagnostic, backfill, refresh, and expiry commands

Current deployment defaults remain:

```text
Payment timing typed cutover: disabled
Financial clearance enforcement: disabled
PAYMENT_TIMING_FORCE_LEGACY: safe fallback available
VISIT_FINANCIAL_CLEARANCE_FORCE_DISABLED: true
```

---

# 2. Known Verification Gaps

The Phase 9 focused verification passed, but final release verification is incomplete.

Known gaps:

## 2.1 Laravel full suite

The complete Laravel suite was attempted twice.

The execution wrapper timed out before PHPUnit produced a final summary:

* First attempt: approximately 2-minute timeout
* Second attempt: approximately 15-minute timeout

The result is therefore:

```text
inconclusive
```

Do not describe the full Laravel suite as passing or failing until a complete final summary is obtained.

## 2.2 Playwright

The complete Playwright suite was invoked but stopped before test discovery because these environment values were absent:

```text
UHMS_RECEPTION_EMAIL
UHMS_RECEPTION_PASSWORD
```

No credentials were invented.

The browser suite therefore remains unexecuted.

## 2.3 Existing NHIS failures

Three `NhisClaimWorkflowTest` failures were previously reported as pre-existing and were observed to fail identically when the Phase 8 changes were stashed.

This must be verified again against the current Phase 9 branch before final release closure.

---

# 3. Primary Goal

Complete one trustworthy release-verification cycle that answers:

1. Does the complete Laravel suite pass?
2. If not, which failures are caused by the payment-timing batch?
3. Are any failures genuinely pre-existing?
4. Does the complete Playwright suite pass?
5. Does the full payment-timing workflow work through the browser?
6. Are safe deployment defaults preserved?
7. Are rollback controls proven?
8. Can the implementation be declared release-ready?
9. Are there any documented release blockers?

Do not claim release readiness unless the evidence supports it.

---

# 4. Hard Guardrails

Do not:

* Add new payment-policy functionality
* Enable typed cutover by default
* Enable financial-clearance enforcement by default
* Remove environment rollback controls
* Invent test credentials
* Hardcode real user passwords
* weaken assertions merely to make tests pass
* Skip failing tests
* mark tests risky or incomplete to hide failures
* delete regression tests
* change accounting rules without a confirmed defect
* change clinical completion or discharge rules without a confirmed defect
* automatically activate operations after verification
* alter production patient, invoice, payment, receivable, or GL data
* describe an incomplete run as passing
* treat timeout as success
* treat missing credentials as a test pass
* dismiss failures as pre-existing without evidence

---

# 5. Pre-Verification Repository Audit

Before running wide tests, inspect the repository state.

Record:

```text
current branch
current commit
working-tree status
uncommitted files
database driver used for tests
PHP version
Laravel version
Node version
npm/pnpm/yarn version
Playwright version
configured test environment
```

Run:

```bash
git status --short
git diff --check
php -v
php artisan --version
node --version
npm --version
```

Use the project’s actual package manager where different.

Do not discard legitimate uncommitted implementation work.

---

# 6. Configuration Safety Audit

Confirm the safe runtime defaults before testing.

Verify:

```text
payment timing master cutover mode defaults to disabled
typed operation settings are not active by default
environment force-legacy behaviour works
financial-clearance mode defaults to disabled
VISIT_FINANCIAL_CLEARANCE_FORCE_DISABLED defaults safely
no operation seeder selects typed mode
no financial-clearance seeder activates enforcement
```

Run the existing commands:

```bash
php artisan billing:payment-timing-cutover-status
php artisan billing:payment-timing-cutover-audit
php artisan billing:payment-gate-coverage
php artisan billing:payment-gate-policy-audit
php artisan billing:visit-payment-policy-audit
php artisan billing:visit-payment-arrangement-audit
php artisan billing:visit-financial-clearance-status
php artisan billing:visit-financial-clearance-audit
php artisan billing:financial-risk-audit
```

Record:

```text
configured mode
effective mode
environment override
typed operations
wired operations
unwired operations
financial-clearance mode
audit findings
```

Do not mutate configuration while running read-only diagnostics.

---

# 7. Database and Migration Verification

Run migration verification against the actual test database configuration.

Required checks:

```bash
php artisan migrate:status
php artisan migrate --force
```

Where safe and supported, also verify a clean database:

```bash
php artisan migrate:fresh --seed --env=testing
```

Only use `migrate:fresh` on a disposable test database.

Never run it against production or a shared development database.

Verify:

* All Phase 1–9 migrations apply cleanly
* Permission migrations are idempotent
* Settings seeders remain idempotent
* No duplicate settings are created
* No operation becomes typed through seeding
* No patient is automatically classified as financially risky
* No visit arrangement is automatically approved
* No financial clearance is automatically activated
* MySQL foreign-key identifier lengths remain safe
* SQLite testing compatibility remains intact where used

Record the final migration and seed results.

---

# 8. Syntax, Static, Route, View, and Localisation Checks

Run:

```bash
php -l <all new and modified PHP files>
php artisan route:list
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan view:cache
```

Run the project’s existing:

```text
localisation parity check
localisation audit
permission audit
activity-log integrity audit
route-permission audit
git diff --check
```

Confirm:

* EN/FR parity
* No missing route permissions
* No unprotected mutation route
* No new real activity-log gap
* No invalid Blade compilation
* No unresolved syntax error
* No whitespace or merge-marker issue

Do not fix unrelated legacy localisation candidates unless they block verification or were introduced by this batch.

---

# 9. Focused Payment-Timing Verification

Before running the complete suite, run the full focused payment-timing family.

Include all tests matching:

```text
PaymentTiming
PaymentGate
BillingPaymentPolicy
PreviousBalance
PatientFinancialRisk
VisitPaymentPolicy
VisitPaymentArrangement
VisitFinancialClearance
TriagePayment
ConsultationPaymentReadiness
LaboratoryPaymentGate
Pharmacy
LanguageParity
```

Suggested command strategy:

```bash
php artisan test \
  --filter='PaymentTiming|PaymentGate|BillingPaymentPolicy|PreviousBalance|PatientFinancialRisk|VisitPaymentPolicy|VisitPaymentArrangement|VisitFinancialClearance|TriagePayment|ConsultationPaymentReadiness|LaboratoryPaymentGate|Pharmacy|LanguageParity'
```

Adjust to PHPUnit support and repository naming.

Record:

```text
tests
assertions
passed
failed
skipped
duration
```

All payment-timing failures introduced by Phases 1–9 must be fixed before proceeding.

---

# 10. Partitioned Laravel Verification

The full suite previously exceeded the execution wrapper limit.

Run the Laravel suite in stable partitions first.

Use the repository’s actual test directory structure.

Suggested partitions:

```text
Unit
Authentication and permissions
Patients and visits
Consultation
Emergency and admission
Laboratory and investigations
Pharmacy and stock
Billing and payments
Insurance and NHIS
Reports and analytics
Commands and scheduling
Other feature modules
```

Possible command pattern:

```bash
php artisan test tests/Unit
php artisan test tests/Feature/Auth
php artisan test tests/Feature/Patients
php artisan test tests/Feature/Visits
php artisan test tests/Feature/Consultation
php artisan test tests/Feature/Emergency
php artisan test tests/Feature/Admission
php artisan test tests/Feature/Lab
php artisan test tests/Feature/Pharmacy
php artisan test tests/Feature/Billing
php artisan test tests/Feature/Nhis
```

Use actual paths, not invented paths.

Where the repository does not group tests by module, generate a stable test-class inventory and execute bounded groups.

For every partition, record:

```text
command
tests
assertions
passed
failed
skipped
duration
peak memory where available
```

Do not stop after the first unrelated failure unless it prevents the remaining partitions from executing.

---

# 11. NHIS Baseline Verification

The known `NhisClaimWorkflowTest` failures require evidence-based classification.

Perform this workflow:

1. Run the failing NHIS test class on the current branch.
2. Record exact failing methods, exception messages, stack traces, and assertions.
3. Identify files changed by Phases 1–9 that could influence those tests.
4. Compare against a clean pre-payment-policy baseline where available.
5. Where safe, use:

   * a temporary worktree,
   * a known prior commit,
   * or a carefully controlled stash comparison.
6. Run the same NHIS tests on the baseline.
7. Compare failures exactly.

Classify each failure as:

```text
introduced by payment-timing batch
pre-existing and identical
pre-existing but changed
environment-dependent
inconclusive
```

Do not rely only on memory or an earlier statement.

If a failure is caused by this batch, fix it and rerun both the NHIS tests and affected payment regressions.

If genuinely pre-existing, document:

```text
baseline commit
test name
current error
baseline error
evidence that errors match
```

Do not silently exclude the tests from release reporting.

---

# 12. Full Laravel Suite

After all partitions complete and payment-related failures are fixed, run the complete suite once.

Use an execution method that allows sufficient time and preserves output.

Preferred approaches:

```bash
php artisan test
```

or, where the project supports it:

```bash
php artisan test --parallel
```

Use parallel execution only if:

* The suite is designed for it
* Database isolation is reliable
* Scheduler/cache/file tests are compatible
* Results are reproducible

Capture output to a file while preserving the exit code.

Example:

```bash
set -o pipefail
php artisan test 2>&1 | tee storage/logs/payment-timing-final-suite.log
```

On Windows PowerShell, use an equivalent mechanism that preserves the command exit code.

The test runner must be allowed enough time to finish.

Record:

```text
total tests
total assertions
passed
failed
skipped
risky
duration
exit code
```

If it still fails to complete:

* identify the last completed test
* identify long-running or hanging classes
* execute those classes separately
* inspect locks, network calls, scheduler behaviour, database transactions, and subprocesses
* fix payment-batch causes
* rerun the complete suite

Do not declare closure while the full-suite result remains inconclusive unless there is a clearly documented infrastructure limitation accepted as a release blocker.

---

# 13. Full-Suite Failure Triage

For every full-suite failure:

1. Rerun the individual test.
2. Rerun its containing class.
3. Rerun its module partition.
4. Check order dependency.
5. Check database leakage.
6. Check cached settings leakage.
7. Check environment variable leakage.
8. Check singleton or memoisation leakage.
9. Check observers and after-commit jobs.
10. Check payment cutover and clearance settings are reset between tests.

Pay particular attention to new singleton services introduced across Phases 1–9.

Ensure tests reset or isolate:

```text
payment timing cutover mode
operation modes
compatibility acknowledgements
approved arrangements
financial-risk profiles
visit payment-policy snapshots
financial-clearance settings
environment force switches
cached Setting values
request-scoped memoisation
```

Fix any batch-created order dependency.

---

# 14. Playwright Environment Preparation

Do not invent credentials.

Create or reuse a dedicated deterministic E2E reception account through the existing fixture/seeder framework.

Preferred approach:

1. Audit existing Playwright authentication fixtures.
2. Audit existing E2E test-data seeders.
3. Add or extend a dedicated test-only fixture command if necessary.
4. Generate or configure:

   ```text
   UHMS_RECEPTION_EMAIL
   UHMS_RECEPTION_PASSWORD
   ```
5. Keep credentials test-only.
6. Do not commit real production credentials.
7. Do not print passwords in reports or logs.
8. Ensure the account has only the permissions required by the suite.

Where the existing test harness already supports a shared fixture account, reuse it.

Document the setup command and required environment variables.

---

# 15. Payment-Timing Playwright Fixture

Prepare deterministic browser-test data containing:

```text
finance user who can assess and close
separate finance-manager approver
reception user
outpatient visit
inpatient visit
emergency visit
pay-after approved arrangement
pay-before approved arrangement
running-bill arrangement
unpaid invoice item
partially paid invoice
fully paid invoice
open patient receivable
conditional-clearance request
approved conditional-clearance exception
financially closed visit
stale financially closed visit
```

The fixture must be:

* Idempotent
* Discoverable
* Test-only
* Safe to rerun
* Separate from default production launch data
* Free from real patient information

Include stable identifiers in fixture metadata.

---

# 16. Focused Playwright Coverage

Add or enable focused browser tests for the completed payment-timing workflow.

Required browser flow:

## 16.1 Pay-after service workflow

1. Finance opens a visit with an approved `pay_after_all_services` arrangement.
2. Typed cutover is explicitly active for an eligible test operation.
3. An unpaid eligible service proceeds.
4. The invoice item remains unpaid.
5. The receivable remains open.

## 16.2 Pay-before workflow

1. Finance opens a visit with an approved `pay_before_service` arrangement.
2. The unpaid eligible service is blocked.
3. The user sees the correct localised payment-required message.
4. Payment is recorded through the existing payment workflow.
5. The service becomes eligible.

## 16.3 Conditional financial clearance

1. Finance assesses an unpaid pay-after visit.
2. Clearance displays `pending`.
3. A conditional-clearance exception is requested.
4. The requester cannot approve their own request.
5. A separate Finance Manager approves it.
6. Clearance becomes `conditionally_cleared`.
7. Finance financially closes the visit.
8. The invoice remains unpaid or partially unpaid.
9. The receivable remains open and collectible.

## 16.4 New activity after financial close

1. A new billable item is added after financial close.
2. The financial-clearance record becomes stale or reopened.
3. Clinical work remains allowed.
4. The invoice and receivable include the new charge.

## 16.5 Clinical independence

Verify:

* Consultation completion remains possible without financial closure.
* Same-day outpatient reopening follows existing rules.
* Inpatient discharge remains possible without financial closure.
* Emergency disposition remains unchanged.
* Re-admission remains unchanged.

## 16.6 Rollback

1. Admin activates typed cutover in the fixture environment.
2. Admin activates financial-clearance enforcement.
3. Admin performs rollback.
4. Legacy authority resumes.
5. Approved arrangements remain recorded.
6. Clearance and exception history remain intact.

---

# 17. Complete Playwright Suite

After focused browser coverage passes, run the complete existing Playwright suite.

Use the repository’s standard command, such as:

```bash
npx playwright test
```

or the actual project script.

Record:

```text
total tests
passed
failed
skipped
flaky
duration
browser projects
workers
retries
```

On failure, retain:

```text
trace
screenshot
video where configured
console output
network error details
```

Do not delete failure artifacts before triage.

Rerun failed tests individually and then within the full suite.

Fix all regressions introduced by Phases 1–9.

Document genuine pre-existing browser failures with evidence.

---

# 18. Manual Release Smoke Test

Perform a bounded manual smoke test after automated suites.

Verify:

## Configuration

* Typed cutover defaults disabled
* Financial-clearance enforcement defaults disabled
* Environment kill switches are visible
* Operation modes display correctly
* Rollback buttons are permission-protected

## Patient risk

* Authorised finance user can view a risk profile
* Clinical user cannot view sensitive risk information

## Arrangement

* Requester cannot self-approve
* Approved arrangement remains visible
* Revocation preserves history

## Typed service gate

* Pay-before blocks
* Pay-after allows
* Running bill allows
* Emergency remains legacy

## Financial clearance

* Fully settled clears
* Unpaid remains pending
* Conditional approval clears administratively
* Financial close preserves debt
* New charge marks clearance stale
* Clinical completion and discharge remain independent

Record the test user roles and fixture identifiers, but do not record passwords.

---

# 19. Security and Privacy Verification

Verify that unauthorised users cannot receive:

```text
patient financial-risk level or reason
visit risk snapshots
approved-arrangement details
conditional-clearance details
financial-clearance history
audit metadata
free-text approval reasons
```

Check:

* Blade page source
* JSON responses
* Inertia props
* API resources
* CSV exports
* browser network responses
* logs
* command JSON output

Confirm patient masking remains active.

Run permission and route audits again after all fixes.

---

# 20. Accounting Integrity Verification

Create focused assertions or manual verification proving:

* Pay-after does not mark invoice items paid
* Running bill does not mark invoice items paid
* Conditional clearance does not mark invoice paid
* Financial close does not mark invoice paid
* Outstanding receivable remains open
* Payment posting still updates the existing ledger correctly
* Payment reversal marks clearance stale
* Waiver and adjustment behaviour remains unchanged
* No duplicate payment allocation occurs
* No duplicate journal entry occurs
* No previous debt is moved into the current visit
* No approved arrangement creates a billing override
* No conditional clearance creates a payment or waiver

Where existing general-ledger tests exist, rerun the applicable billing/accounting suites.

---

# 21. Performance Verification

Measure or assert:

```text
disabled typed cutover adds zero arrangement/policy queries
disabled financial-clearance mode adds no clinical workflow query
payment-gate loops avoid N+1 arrangement lookups
finance worklists paginate
history is not loaded in list views
financial summaries reuse current ledger services
automatic staleness is after-commit and failure-safe
```

Review query logs for:

* consultation route completion
* next-patient readiness
* laboratory result entry
* pharmacy dispensing
* visit financial-clearance worklist
* visit financial-clearance detail

Fix batch-created N+1 or repeated settings queries.

Do not perform speculative optimisation unrelated to the batch.

---

# 22. Scheduler and Command Verification

Run or test all relevant scheduled commands:

```text
billing:financial-risk-expire
billing:visit-payment-arrangement-expire
billing:visit-financial-clearance-exception-expire
```

Confirm:

* Dry-run defaults where specified
* Commit is idempotent
* `withoutOverlapping`
* `onOneServer` where configured
* Repeated execution does not duplicate history or activity logs
* Expiry does not alter receivables
* Failures are visible but do not corrupt workflow state

Run all read-only audit commands and confirm they create no writes or activity logs.

---

# 23. Release Readiness Checklist

Create a checklist covering:

## Database

* Migrations reviewed
* Backups required before deployment
* Migration order valid
* Rollback limitations documented
* No destructive migration

## Configuration

* New environment variables documented
* Safe defaults confirmed
* Config cache instructions documented
* Kill switches documented

## Permissions

* Permission migration applied
* Role assignments reviewed
* Activation permissions restricted
* Clinical roles receive no sensitive finance permissions

## Operations

* Typed operations default legacy
* Financial-clearance mode disabled
* Emergency remains legacy
* Nine unwired operations remain unwired

## Monitoring

* Diagnostic log events documented
* Audit commands documented
* Failure fallback documented
* Support team knows rollback procedure

## Accounting

* Receivable preservation verified
* No fake settlement
* No GL mutation from closure
* Payment reversal behaviour verified

## Clinical safety

* Completion independent
* Discharge independent
* Emergency unaffected
* Reopening unaffected

---

# 24. Deployment Plan

Document a safe deployment sequence.

Suggested sequence:

1. Create database backup.
2. Deploy code with all enforcement disabled.
3. Run migrations.
4. Run idempotent seeders.
5. Clear and rebuild caches.
6. Run permission audit.
7. Run configuration status commands.
8. Run payment-gate and clearance audit commands.
9. Verify all operation modes remain legacy/disabled.
10. Verify financial-clearance mode remains disabled.
11. Perform finance/admin UI smoke tests.
12. Monitor logs.
13. Do not activate typed cutover during the deployment itself.
14. Schedule a separate operational activation decision.

Do not combine deployment with enforcement activation.

---

# 25. Rollback Plan

Document two rollback levels.

## 25.1 Operational rollback

Use:

```text
PAYMENT_TIMING_FORCE_LEGACY
VISIT_FINANCIAL_CLEARANCE_FORCE_DISABLED
```

and admin rollback controls.

Operational rollback must:

* Restore legacy gate authority
* Disable financial-close enforcement
* Preserve approved arrangements
* Preserve clearances and histories
* Preserve invoices, payments, and receivables

## 25.2 Code rollback

Document:

* Commit or release tag
* Migration compatibility
* Whether additive tables may remain safely after code rollback
* Cache clearing steps
* Worker restart steps
* Scheduler restart steps

Do not recommend destructive down migrations as the first rollback method.

---

# 26. Release Closure Report

Create:

```text
docs/billing/PAYMENT_TIMING_POLICY_RELEASE_CLOSURE_REPORT.md
```

The report must include:

1. Executive implementation summary
2. Phases 1–9 closure inventory
3. Repository and environment details
4. Safe deployment defaults
5. Migration results
6. Seeder idempotence results
7. Syntax, route, view, localisation, permission, and audit results
8. Focused payment-timing test totals
9. Laravel partition results
10. Full Laravel-suite totals
11. NHIS baseline investigation
12. Playwright fixture setup
13. Focused Playwright results
14. Full Playwright-suite totals
15. Screenshots/traces for browser failures
16. Manual smoke-test results
17. Accounting-integrity verification
18. Security/privacy verification
19. Query/performance findings
20. Scheduler and command verification
21. Known pre-existing failures
22. New failures fixed
23. Remaining blockers
24. Deployment checklist
25. Rollback procedure
26. Environment variable reference
27. Release readiness verdict
28. Final implementation-batch status

Use one of these final verdicts:

```text
READY FOR DEPLOYMENT WITH ENFORCEMENT DISABLED
READY FOR CONTROLLED PILOT
BLOCKED — VERIFICATION FAILURE
BLOCKED — ENVIRONMENT REQUIREMENT
```

Do not use a stronger verdict than the evidence supports.

---

# 27. Final Acceptance Criteria

Release verification is complete only when:

* Repository state is documented.
* Safe defaults are confirmed.
* All migrations apply cleanly.
* Seeders are idempotent.
* Syntax checks pass.
* Routes compile.
* Blade views compile.
* English/French parity passes.
* Permission audit reports no new gap.
* Activity-log audit reports no new real gap.
* Focused payment-timing tests pass.
* Laravel module partitions complete.
* Known NHIS failures are evidence-classified.
* The complete Laravel suite produces a final summary.
* Playwright credentials are supplied through a safe test fixture or environment configuration.
* Focused payment-timing browser tests pass.
* The complete Playwright suite produces a final summary.
* Regressions introduced by Phases 1–9 are fixed.
* Outstanding receivables remain collectible.
* No financial closure falsely settles an invoice.
* Clinical completion remains independent.
* Inpatient discharge remains independent.
* Emergency behaviour remains unchanged.
* Typed cutover remains disabled after testing.
* Financial-clearance enforcement remains disabled after testing.
* Rollback controls are proven.
* Deployment and rollback plans are documented.
* The release-closure report contains exact evidence.
* A justified release-readiness verdict is issued.

Proceed with **Payment Timing Policy Final Verification and Release Closure only**.

Do not implement additional payment-policy functionality.

After completion, provide:

1. Verification summary
2. Files modified to fix regressions
3. Repository and environment details
4. Focused test totals
5. Laravel partition totals
6. Full Laravel-suite result
7. NHIS baseline findings
8. Playwright environment setup
9. Focused Playwright result
10. Full Playwright-suite result
11. Manual smoke-test findings
12. Accounting-integrity findings
13. Security/privacy findings
14. Query/performance findings
15. Scheduler and command results
16. Known pre-existing failures
17. Remaining blockers
18. Deployment checklist
19. Rollback procedure
20. Release-readiness verdict
21. Release-closure report path

Then stop.
