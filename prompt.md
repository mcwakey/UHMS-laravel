You are working on UHMS — Ultimate Hospital Management System.

Important:
There is currently no `docs/UHMS_IMPLEMENTATION_SKILL.md` file in this project.
Do not try to read it.
Follow this prompt directly.

Active-runtime localisation burn-down is complete.

The latest localisation audit shows:

```text
Active runtime candidates: 0
```

Phase 15G confirms that active runtime candidates went from 34 to 0, with remaining JavaScript/manual-review items either translated or proven as false positives/non-active with evidence.

Do not restart broad translation work.
Do not touch dormant demo/template files.
Do not re-open already completed phases unless a test proves a regression.

# UHMS Localisation Phase 16 — QA Gates, Regression Tests, and Localisation Lock

## Goal

Lock the completed localisation work with automated and manual QA gates so future development cannot reintroduce untranslated active-runtime UI strings.

This phase must prepare the system for the Full Test Suite by adding localisation-specific tests, audit commands, documentation, and verification gates.

---

# 1. Required Reports To Read First

Read:

```text
docs/LOCALISATION_COVERAGE_AUDIT_REPORT.md
docs/LOCALISATION_PHASE_15G_FINAL_RUNTIME_JS_MANUAL_REVIEW_REPORT.md
```

Use these as the current source of truth:

```text
Active runtime candidates: 0
```

---

# 2. Do Not Re-Translate Completed Runtime Pages

Do not make broad Blade translation changes unless a failing test identifies a real active-runtime untranslated string.

Do not edit dormant template/demo pages unless they become route-linked.

Do not translate:

* database values
* patient names
* doctor names
* medicine names
* product names
* service names from database
* clinical notes
* diagnosis text
* lab test names from database
* insurance provider names
* sponsor names
* role slugs
* permission slugs
* route names
* database column names
* protocol names such as HL7 v2.x and ASTM E1394
* clinical units
* currency symbols
* UHMS acronym

---

# 3. Add Localisation Regression Tests

Create or update automated tests for localisation safety.

Add tests under an appropriate namespace, for example:

```text
tests/Feature/Localization/
```

Recommended test files:

```text
tests/Feature/Localization/LanguageParityTest.php
tests/Feature/Localization/ActiveRuntimeLocalizationAuditTest.php
tests/Feature/Localization/FrenchRouteSmokeTest.php
tests/Feature/Localization/ValidationLocalizationTest.php
tests/Feature/Localization/JavaScriptLocalizationBridgeTest.php
```

---

# 4. Language Parity Test

Create a test that recursively flattens all keys in:

```text
lang/en
lang/fr
```

The test must fail if:

* an English key is missing in French
* a French key is missing in English
* nested keys differ
* a language file exists in one locale but not the other

The test must print clear output showing:

```text
missing key
locale
file
```

Do not compare translated values.
Only compare key structure.

---

# 5. Active Runtime Audit Test

Create a test or command wrapper that runs:

```bash
php scripts/localisation-audit.php
```

The test must fail if:

```text
Active runtime candidates > 0
```

The test must not fail for:

```text
demo_template_candidates
known_false_positives
service_title_manual_review candidates
language-file candidates
```

But the test must display their counts so developers remain aware of them.

Expected current baseline:

```text
Active runtime candidates: 0
```

---

# 6. French Route Smoke Test

Create a route smoke test that checks critical GET pages in French mode.

It should not attempt to crawl everything blindly if authentication/permissions make that unstable. Instead, build a curated list of critical active-runtime routes across major modules.

Include routes for:

```text
auth/login
dashboard
patients
visits
consultations
appointments
billing/invoices
payments
pharmacy
lab
investigations
emergency
wards
theatre
stock
store
accounting
reports
settings
notifications
queue
HR
blood bank
```

For each route:

* authenticate as a user with appropriate permissions
* set locale to French
* request the page
* assert HTTP 200 or expected redirect if permission-gated
* assert the page does not contain obvious untranslated UI markers from the old audit where possible

Do not assert against patient-entered/database content.

---

# 7. Validation Localisation Test

Add tests for French validation messages.

Cover at least:

```text
auth/profile fields
patient fields
visit fields
consultation fields
billing/payment fields
stock/store fields
emergency fields
```

The test should confirm that validation errors use French field attributes where available.

Do not require every possible validation message to be manually listed; test representative coverage.

---

# 8. JavaScript I18N Bridge Test

Add a test or static check for JavaScript localisation bridges.

Confirm:

* active Blade-embedded JS uses `@json(__('...'))`
* active JS pages expose needed values through `window.UHMS_I18N` or page-level I18N maps
* `resources/js/script.js` and `resources/js/doctors.js` remain documented as dormant/demo false positives unless they become route-linked

If those JS files later become active, the test or documentation must force them to be wired through `window.UHMS_I18N`.

---

# 9. Protect False Positive Rules

The scanner was updated in Phase 15G to reclassify specific known false positives.

Add comments/tests to ensure these remain narrow and safe:

```text
HL7 v2.x
ASTM E1394
resources/js/script.js demo-widget fragments
resources/js/doctors.js demo-widget fragments
```

Do not create broad rules that hide real untranslated active UI strings.

If any false-positive rule is widened, require a test or report note explaining why.

---

# 10. Optional Service Output Localisation Review

The audit still has a `service_title_manual_review` bucket.

This is not active-runtime UI debt, but review the safe display-only candidates if time allows.

Priority optional candidates:

```text
app/Services/StatisticsService.php
app/Services/PatientMergePreviewService.php
```

Rules:

* translate only confirmed user-facing display labels
* do not translate stored event titles
* do not translate audit records
* do not translate journal descriptions
* do not translate SQL/internal expressions
* do not alter canonical workflow values

If touched, add EN/FR keys and update tests.

If not touched, document as deferred non-blocking debt.

---

# 11. Full Verification Commands

Run:

```bash
php artisan view:clear
php artisan config:clear
php artisan cache:clear
php artisan route:list
php artisan view:cache
php artisan view:clear
```

Run:

```bash
for f in lang/en/*.php lang/fr/*.php; do php -l "$f"; done
```

Run:

```bash
php -l scripts/localisation-audit.php
php scripts/localisation-audit.php
```

Run:

```bash
php artisan test
```

If the full test suite is too large or currently unstable, run the new localisation tests first:

```bash
php artisan test tests/Feature/Localization
```

Run:

```bash
git diff --check
```

If compiled Blade cache exists, lint compiled views:

```bash
find storage/framework/views -type f -name "*.php" -print0 | xargs -0 -n1 php -l
```

---

# 12. Manual French QA Checklist

Switch app to French and manually verify a small release-critical sample:

```text
login
dashboard
patients
visits/create
consultation show
pharmacy dispense
billing invoice show
payment page
emergency show
wards/beds
theatre show
stock balances
purchase orders
accounting settings
reports hub
settings/profile
notifications
queue board
```

Check:

* page title
* breadcrumbs
* headings
* forms
* placeholders
* buttons
* modals
* alerts
* validation errors
* JavaScript confirms/alerts
* empty states
* print/PDF labels where relevant
* no clinical/financial data exposure
* no permission regression

---

# 13. Documentation Required

Create:

```text
docs/LOCALISATION_PHASE_16_QA_GATES_AND_REGRESSION_LOCK_REPORT.md
```

Include:

* summary
* confirmation of starting active runtime count: 0
* tests added
* commands run
* language parity result
* audit result
* French route smoke result
* validation localisation result
* JavaScript bridge result
* false-positive protection notes
* optional service-output review result
* manual QA checklist
* known non-blocking localisation debt
* recommendation for next phase

---

# 14. Acceptance Criteria

Phase 16 is complete only when:

* active runtime candidates remain 0
* EN/FR parity test passes
* localisation audit test passes
* French route smoke tests pass or document permission-gated redirects
* validation localisation tests pass
* JavaScript localisation bridge checks pass
* false-positive rules are documented and narrow
* PHP lint passes
* view cache compiles
* no permission checks are weakened
* no clinical/financial data exposure is introduced
* no business logic is moved into Blade
* documentation report is created

Proceed with UHMS Localisation Phase 16 now.
