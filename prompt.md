You are working on UHMS — Ultimate Hospital Management System.

Important:
There is currently no `docs/UHMS_IMPLEMENTATION_SKILL.md` file in this project.
Do not try to read it.
Follow this prompt directly.

Localisation is almost complete, but not yet closed.

Phase 15F reduced active runtime candidates from 192 to 34. All Blade worklist batches were processed. The remaining active-runtime candidates are now mostly JavaScript/manual-review items.

Do not move to dashboards.
Do not move to the Full Test Suite yet.
Do not claim localisation is complete until the remaining 34 active runtime candidates are fixed or formally documented as false positives with evidence.

# UHMS Localisation Phase 15G — Final Runtime JS / Manual-Review Burn-Down

## Goal

Reduce the remaining active runtime localisation candidates from:

```text
Active runtime candidates: 34
```

to:

```text
Active runtime candidates: 0
```

If true zero is not possible, every remaining item must be documented with exact evidence proving it is not user-facing active runtime text.

---

# 1. Required Reports To Read First

Read:

```text
docs/LOCALISATION_COVERAGE_AUDIT_REPORT.md
docs/LOCALISATION_PHASE_15F_CLINICAL_ADMIN_BILLING_BURNDOWN_REPORT.md
```

Use the active runtime worklist in `LOCALISATION_COVERAGE_AUDIT_REPORT.md` as the source of truth.

---

# 2. Remaining Active Runtime Worklist

The latest audit shows only these active runtime candidates remain:

```text
resources/js/script.js — 23 candidates
resources/js/doctors.js — 3 candidates
resources/views/patients/partials/insurance-add-modal-scripts.blade.php — 4 candidates
resources/views/admin/analyzers/index.blade.php — 3 candidates
resources/views/claims/partials/clinical-mirror.blade.php — 1 candidate
```

Process each one carefully.

---

# 3. JavaScript Localisation Rule

Do not wrap plain JavaScript files with Laravel `__()` directly.

For active JavaScript strings, use the existing `window.UHMS_I18N` bridge.

Do not introduce:

```text
i18next
Vue
React
new frontend localisation package
new localisation framework
```

If a JS string is active and user-facing, expose it from the relevant Blade layout/page as translated JSON and consume it in JS.

Example pattern:

```blade
<script>
    window.UHMS_I18N = Object.assign(window.UHMS_I18N || {}, {
        loading: @json(__('common.loading')),
        error: @json(__('common.error')),
        confirm_delete: @json(__('common.confirm_delete')),
    });
</script>
```

Then in JavaScript:

```js
const t = window.UHMS_I18N || {};
alert(t.error || 'Error');
```

Fallback strings may remain in English only as defensive fallback, but the displayed runtime string must come from `window.UHMS_I18N`.

---

# 4. Process `resources/js/script.js`

Review all 23 scanner candidates.

For each candidate, decide one of:

```text
A. Active user-facing string — translate through window.UHMS_I18N
B. Dormant/demo/template string — document as false positive with evidence
C. Internal selector/config key — document as false positive
```

If active:

* add keys to the correct language namespace
* expose translated values through the existing global layout or page-specific Blade
* update `resources/js/script.js` to use the translated values
* preserve existing JS behavior

Do not break:

* modals
* notifications
* DataTables
* Select2
* date pickers
* charts
* forms
* dashboard widgets
* template initialization

If strings are demo examples from unused template widgets, document why they are not active route-linked UI.

---

# 5. Process `resources/js/doctors.js`

Review all 3 scanner candidates.

For each candidate, decide:

```text
A. Active doctor/staff UI string — translate through window.UHMS_I18N
B. Dormant/demo string — document as false positive
C. Internal code/config — document as false positive
```

If active, add the needed translation keys and wire through the Blade/layout i18n bridge.

Do not change doctor workflow or dashboard behavior.

---

# 6. Process `patients/partials/insurance-add-modal-scripts.blade.php`

Fix:

```text
resources/views/patients/partials/insurance-add-modal-scripts.blade.php
```

This is a Blade JS partial, so it may safely use:

```blade
@json(__('patients.some_key'))
```

or a page-level i18n map.

Translate the 4 remaining candidates if they are user-facing.

Do not translate:

* insurance provider names
* sponsor names
* policy numbers
* membership numbers
* patient-entered values
* database values

Do not change insurance/sponsor logic.

---

# 7. Process `admin/analyzers/index.blade.php`

Fix:

```text
resources/views/admin/analyzers/index.blade.php
```

There are 3 residual candidates.

Re-check the current file, because analyzer pages were already processed earlier.

For each remaining candidate:

```text
A. translate if user-facing
B. document as false positive if already translated downstream
C. document as false positive if not displayed
```

Use or extend:

```text
lang/en/analyzers.php
lang/fr/analyzers.php
```

Do not change analyzer connection, mapping, diagnostics, or lab integration logic.

---

# 8. Process `claims/partials/clinical-mirror.blade.php`

Fix or document:

```text
resources/views/claims/partials/clinical-mirror.blade.php
```

This is confidentiality-sensitive.

Review the 1 candidate carefully.

If it is a static UI label, translate it.

If it is clinical data, clinician-entered text, diagnosis text, procedure name, medication name, investigation/test name, or claim content from the database, do not translate it. Document exactly why it remains.

Do not expose any additional clinical data.
Do not change claim generation logic.
Do not change insurance/NHIS behavior.
Remember: NHIS is just another insurance provider. Do not hardcode NHIS.

---

# 9. Class-A Service Candidate Review

The audit still reports:

```text
67 class-A user-facing service-output candidates
```

Review these, but do not blindly translate all of them.

For each class-A candidate, decide:

```text
A. Confirmed user-facing label/output — translate
B. Stored canonical event title — leave unchanged
C. Audit/accounting/journal description — leave unchanged
D. SQL/internal expression — mark false positive
E. Already translated downstream — document
```

Priority services to review:

```text
app/Services/ConsultationNextPatientService.php
app/Services/FinancialReportService.php
app/Services/PatientMergePreviewService.php
app/Services/ProcedureReportService.php
app/Services/StatisticsService.php
app/Services/ReportService.php
```

Safe examples:

```php
'label' => __('accounting.revenue')
```

```php
'message' => __('consultations.payment_ready')
```

Unsafe examples:

```text
stored event titles
audit descriptions
journal entry descriptions
SQL expressions
canonical workflow titles
historical records
```

Do not alter stored semantics.

---

# 10. Translation Rules

Use Laravel localisation only.

Use:

```php
__('module.key')
```

or:

```blade
{{ __('module.key') }}
```

For placeholders:

```php
__('patients.insurance_added_for_patient', ['patient' => $patient->name])
```

Do not concatenate translated fragments.

Bad:

```php
'Patient: ' . $patient->name
```

Good:

```php
__('patients.patient_name', ['name' => $patient->name])
```

---

# 11. Language File Rules

Use existing namespaces where possible.

For JS/global strings, prefer:

```text
common.php
messages.php
patients.php
analyzers.php
claims.php
consultations.php
accounting.php
reports.php
statistics.php
```

Do not dump domain-specific strings into `common.php`.

Use `common.php` only for genuinely generic UI strings:

```text
save
cancel
close
search
filter
clear
actions
status
active
inactive
view
edit
delete
yes
no
loading
error
success
confirm
warning
```

Every English key must exist in French.
Every French key must exist in English.

---

# 12. Do Not Translate These

Do not translate:

* patient names
* staff names
* doctor names
* supplier names
* medicine names from database
* product names from database
* service names from database unless system-defined hardcoded labels
* diagnosis text typed by clinicians
* clinical notes typed by clinicians
* lab test names from catalogue/database
* insurance provider names
* sponsor names
* permission slugs
* role slugs
* route names
* database column names
* internal enum values
* CSS classes
* JS selectors
* data attributes
* clinical units such as mmHg, bpm, kg, cm, °C, %, SpO2
* currency symbols
* UHMS acronym

---

# 13. Security Rules

Do not weaken permissions.

Preserve:

```text
@can
@cannot
Gate
policies
middleware
role checks
permission checks
financial visibility checks
stock-cost visibility checks
clinical confidentiality checks
```

Do not expose:

```text
restricted clinical data
financial data
accounting data
stock cost
insurance financial details
sponsor financial details
audit logs
user permissions
```

No business logic should be moved into Blade.

---

# 14. Scanner Burn-Down

Run the scanner after each mini-section:

```bash
php scripts/localisation-audit.php
```

Track before/after for:

```text
script.js
doctors.js
insurance-add-modal-scripts
admin/analyzers/index
claims clinical mirror
class-A services
```

Target:

```text
Active runtime candidates: 0
```

If any remain, document exact reason and proof.

---

# 15. Required Documentation

Create:

```text
docs/LOCALISATION_PHASE_15G_FINAL_RUNTIME_JS_MANUAL_REVIEW_REPORT.md
```

Include:

* summary
* starting active runtime candidate count: 34
* ending active runtime candidate count
* per-file before/after counts
* JS strings translated
* JS strings documented as false positives
* `window.UHMS_I18N` wiring added/used
* insurance modal script result
* analyzer residual result
* clinical mirror decision
* class-A service review result
* service candidates translated
* service candidates deferred with reasons
* language files changed
* namespaces created
* keys added
* EN/FR parity result
* PHP lint result
* view cache result
* scanner result
* security/permissions confirmation
* manual French verification checklist
* recommendation for next phase

Do not claim localisation complete unless active runtime candidates are zero or every remaining candidate is proven false-positive/non-user-facing.

---

# 16. Verification Commands

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
git diff --check
```

If compiled Blade cache exists, lint compiled views:

```bash
find storage/framework/views -type f -name "*.php" -print0 | xargs -0 -n1 php -l
```

---

# 17. Manual French Verification

Switch the app to French and verify:

```text
global JS behaviours from script.js
doctor JS behaviours from doctors.js
patient insurance add modal
analyzer index
claims clinical mirror area
consultation next-patient/payment-ready output if touched
financial reports if service labels touched
patient merge preview if service labels touched
procedure reports if service labels touched
statistics/report labels if touched
```

Check:

* alerts
* confirm dialogs
* modal messages
* dynamic generated HTML
* placeholders
* empty states
* buttons
* status text
* service-generated labels
* no regression in JS behaviour
* no clinical/financial data exposure

---

# 18. Acceptance Criteria

Phase 15G is complete only when:

* all 34 remaining active runtime candidates are fixed or documented with proof
* active runtime candidate count is zero or every remaining candidate is proven non-user-facing/false-positive
* JS active strings use `window.UHMS_I18N`
* insurance modal script is translated or documented
* analyzer residual strings are fixed or documented
* clinical mirror candidate is fixed or documented safely
* class-A service outputs are reviewed
* confirmed user-facing service labels are translated
* stored/audit/journal/SQL semantics remain unchanged
* EN/FR parity passes
* PHP lint passes
* view cache compiles
* localisation scanner runs
* permissions are unchanged
* clinical confidentiality remains protected
* financial visibility remains protected
* no business logic is moved into Blade
* no new localisation framework is introduced
* no new frontend package is introduced
* documentation report is created

Proceed with UHMS Localisation Phase 15G now.
