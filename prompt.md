You are working on UHMS — Ultimate Hospital Management System.

Important:
There is currently no `docs/UHMS_IMPLEMENTATION_SKILL.md` file in this project.
Do not try to read it.
Follow this prompt directly.

Localisation active-runtime work is complete and locked.

Current confirmed status:

```text
Active runtime candidates: 0
```

Phase 16 added localisation QA gates under:

```text
tests/Feature/Localization/
```

The localisation test suite passes:

```text
12 tests / 60 assertions
```

Do not restart localisation work.
Do not re-open translation phases unless a test proves a real regression.

# UHMS Phase 17 — Full Test Suite & System-Wide Regression Stabilisation

## Goal

Run and stabilise the full UHMS automated test suite after the major localisation/responsiveness work.

The goal is not to add new features.

The goal is to make the whole application testable, stable, and safe after the recent large UI/localisation changes.

---

# 1. Required Context

Before changing anything, review the latest reports:

```text
docs/LOCALISATION_COVERAGE_AUDIT_REPORT.md
docs/LOCALISATION_PHASE_16_QA_GATES_AND_REGRESSION_LOCK_REPORT.md
```

Important baseline:

```text
Active runtime candidates: 0
Localisation tests: passing
```

Do not break this baseline.

---

# 2. Run the Full Test Suite

Run:

```bash
php artisan test
```

If the full suite is too large or crashes early, run grouped suites:

```bash
php artisan test tests/Feature
php artisan test tests/Unit
php artisan test tests/Feature/Localization
```

Also run:

```bash
php artisan route:list
php artisan view:cache
php artisan view:clear
```

Then record:

* total tests
* passed tests
* failed tests
* skipped tests
* errors
* first failing file
* first failing test
* failure categories

---

# 3. Do Not Fix Blindly

For every failure, classify it first.

Use these categories:

```text
A. Real application bug
B. Test expectation outdated after intended change
C. Seeder/factory/test-data issue
D. Permission/role setup issue
E. Route/name/view path changed
F. Localisation assertion issue
G. Database migration/schema issue
H. Environment-only issue
I. Flaky/time-dependent issue
```

Do not change production code if the problem is clearly a bad test.

Do not change tests to hide a real bug.

---

# 4. Preserve Localisation Lock

Before and after fixes, run:

```bash
php artisan test tests/Feature/Localization
php scripts/localisation-audit.php
```

The following must remain true:

```text
Active runtime candidates: 0
EN/FR parity: pass
French route smoke: pass
Validation localisation: pass
JS localisation bridge: pass
```

If any localisation test fails, fix it immediately before continuing.

---

# 5. Fix Test Infrastructure First

If failures are caused by missing baseline data, fix factories/seeders/test setup before touching business logic.

Pay attention to:

* roles
* permissions
* departments
* services
* products
* stock locations
* payment methods
* insurance providers
* sponsors
* consultation services
* emergency service mappings
* accounting chart of accounts
* fiscal periods
* users with proper roles

Prefer reusable test helpers or seeders over copy-pasting setup into every test.

---

# 6. Role and Permission Test Stability

Many UHMS pages are permission-aware.

Ensure tests create users with appropriate roles/permissions.

Do not bypass permissions in production code.

For tests, use one of these approaches:

```php
$user = User::factory()->create();
$user->assignRole('Super Admin');
$this->actingAs($user);
```

or a reusable helper:

```php
$this->actingAsSuperAdmin();
```

If roles do not exist in the test database, seed them in the test setup.

Do not remove `@can`, policies, gates, middleware, or permission checks.

---

# 7. Database / Migration Stability

If tests fail due to schema issues:

* verify migrations run cleanly on a fresh test database
* avoid destructive migrations unless absolutely required
* ensure MariaDB 10.1 compatibility
* avoid JSON column assumptions if the project must support older MariaDB
* avoid unsupported indexes or generated columns if not already used safely

Run:

```bash
php artisan migrate:fresh --env=testing
php artisan test
```

Only if the test environment supports it.

---

# 8. Factory and Seeder Stabilisation

Fix factories for core models where needed.

Important UHMS entities likely needed in tests:

```text
User
Role
Permission
Patient
Visit
Department
Service
Product
StockLocation
Invoice
Payment
InsuranceProvider
PatientInsurance
Sponsor
Appointment
Consultation
Prescription
LabRequest
EmergencyCase
Ward
Bed
TheatreRoom
PurchaseOrder
Supplier
Account
JournalEntry
```

Factories should create valid minimal records.

Avoid creating huge fixture data.

---

# 9. Billing / Accounting / Stock Safety

When fixing failures in billing, accounting, or stock tests, preserve these rules:

```text
Products = physical stock items.
Services = billable activities.
Operational records stay operational.
Accounting records are journal entries.
```

Do not mix product and service logic.

Do not bypass accounting services.

Do not change invoice totals just to satisfy a test.

Do not weaken stock-cost permissions.

Do not expose financial data to unauthorised users.

---

# 10. Emergency Workflow Safety

When fixing emergency-related tests, preserve the intended workflow:

* emergency visit type should create/flag Emergency/Casualty context correctly
* emergency cases should map to configured emergency consultation/service mappings
* no hardcoded emergency service IDs
* no hardcoded consultation service IDs
* emergency workflow must still bill using configured services
* emergency clinical/financial visibility remains permission-aware

Do not hardcode Emergency/Casualty service names as IDs.

Use configuration or database mappings.

---

# 11. Insurance / Sponsor / NHIS Safety

NHIS is just another insurance provider.

Do not create NHIS-only architecture.

Do not hardcode NHIS into generic billing, claims, or insurance workflows.

When fixing tests:

* use generic insurance providers
* use generic sponsor entities
* keep patient insurance logic provider-agnostic
* keep claim logic provider-aware but not provider-hardcoded

---

# 12. Activity Log Safety

Do not bypass:

```text
ActivityLogService
```

If tests fail because logs are expected, update tests or seed context properly.

Do not remove audit events just to pass tests.

If logging causes unstable assertions, assert the event type/key rather than fragile full text where possible.

---

# 13. View / Blade / Route Failures

If tests fail because a page no longer renders:

* check missing language keys
* check undefined variables
* check permission-gated buttons
* check route names
* check partial includes
* check `@json()` usage
* check view cache parse errors

Run:

```bash
php artisan view:cache
```

Do not suppress Blade errors.

Fix root causes.

---

# 14. Frontend Asset Safety

If tests or builds touch frontend assets:

Run:

```bash
npm run build
```

Only if Node dependencies are installed.

Do not introduce new frontend frameworks.

Do not introduce Tailwind.

Use existing Bootstrap 5 + Tabler Icons.

Do not break the `window.UHMS_I18N` localisation bridge.

---

# 15. Fix Order

Use this order:

```text
1. Environment/test setup failures
2. Migration/schema failures
3. Seeder/factory failures
4. Auth/role/permission failures
5. Route/view/cache failures
6. Localisation regression failures
7. Core domain logic failures
8. Billing/accounting/stock failures
9. Emergency/clinical workflow failures
10. Flaky/time-sensitive tests
```

Commit mentally by category; keep diffs reviewable.

---

# 16. Reporting Requirements

Create:

```text
docs/PHASE_17_FULL_TEST_SUITE_REGRESSION_STABILISATION_REPORT.md
```

Include:

* summary
* test commands run
* initial full-suite result
* final full-suite result
* number of failures fixed
* failure categories
* files changed
* production code changed
* test code changed
* seeders/factories changed
* migrations changed, if any
* localisation gate result
* active runtime candidate count
* route list result
* view cache result
* npm build result, if run
* remaining failing tests, if any
* known risks
* next recommended phase

---

# 17. Required Final Verification

At the end, run:

```bash
php artisan test tests/Feature/Localization
php scripts/localisation-audit.php
php artisan route:list
php artisan view:cache
php artisan view:clear
php artisan test
```

If available and relevant:

```bash
npm run build
```

Also run:

```bash
git diff --check
```

---

# 18. Acceptance Criteria

Phase 17 is complete only when:

* localisation tests still pass
* active runtime candidates remain 0
* route list works
* view cache compiles
* full test suite is run
* all failures are fixed or clearly documented
* no permission checks are weakened
* no clinical/financial data exposure is introduced
* no business logic is moved into Blade
* no NHIS-only logic is introduced
* no stock/product/service rules are broken
* documentation report is created

Proceed with UHMS Phase 17 now.
