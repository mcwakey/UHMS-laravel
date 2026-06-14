You are working on UHMS — Ultimate Hospital Management System.

Important:
There is currently no `docs/UHMS_IMPLEMENTATION_SKILL.md` file in this project.
Do not try to read it.
Follow this prompt directly.

# UHMS Investigation Module — Configurable Overall Result Type

## Goal

In the investigation module, the **overall result field** must be configurable per investigation/test type.

Currently, investigation results are too generic. Some investigations need a free-text conclusion, some need a numeric value, some need true/false, and others need positive/negative outcomes. This must be configurable from the investigation catalogue/test configuration, then respected when entering results and when generating reports/statistics.

---

## 1. Add Overall Result Type Configuration

Add a configurable field to the investigation/test catalogue.

Each investigation type/test must support one of these overall result formats:

```text
free_text
numeric
boolean
positive_negative
```

Recommended labels:

```text
Free text
Numeric value
True / False
Positive / Negative
```

The configuration should be set per investigation/test type, not globally.

Example:

```text
Malaria RDT       → positive_negative
Pregnancy Test    → positive_negative
Blood Sugar       → numeric
HIV Screening     → positive_negative
X-Ray Chest       → free_text
Consent-related test → boolean
```

---

## 2. Database Changes

Add the needed columns to the investigation catalogue/test table.

Use the existing model/table names in the project. Do not create a parallel investigation catalogue.

Recommended fields:

```text
overall_result_type
overall_result_unit
overall_result_min_value
overall_result_max_value
overall_result_positive_label
overall_result_negative_label
overall_result_true_label
overall_result_false_label
```

Only add fields that make sense based on the current schema.

At minimum, add:

```text
overall_result_type
```

Optional but useful:

```text
overall_result_unit
```

For numeric result reporting, the unit can be used in display and reports.

Use safe defaults:

```text
overall_result_type = free_text
```

Make the migration compatible with MariaDB/MySQL.

Do not use database enum if the project normally avoids enums for compatibility. A string column with validation is acceptable.

---

## 3. Model Constants / Helper Methods

In the investigation catalogue/test model, add constants or helper methods for supported result types.

Example:

```php
public const OVERALL_RESULT_FREE_TEXT = 'free_text';
public const OVERALL_RESULT_NUMERIC = 'numeric';
public const OVERALL_RESULT_BOOLEAN = 'boolean';
public const OVERALL_RESULT_POSITIVE_NEGATIVE = 'positive_negative';
```

Add helper methods such as:

```php
public static function overallResultTypes(): array
public function overallResultTypeLabel(): string
public function usesNumericOverallResult(): bool
public function usesBooleanOverallResult(): bool
public function usesPositiveNegativeOverallResult(): bool
public function usesFreeTextOverallResult(): bool
```

Make labels localised with `__()`.

---

## 4. Admin Configuration UI

Update the investigation/test catalogue create/edit/configure screens.

Add a field:

```text
Overall Result Type
```

Input type:

```text
select dropdown
```

Options:

```text
Free text
Numeric value
True / False
Positive / Negative
```

If `numeric` is selected, optionally show:

```text
Unit
Minimum normal value
Maximum normal value
```

If `positive_negative` is selected, optionally show labels:

```text
Positive label
Negative label
```

If `boolean` is selected, optionally show labels:

```text
True label
False label
```

These optional custom labels should default to translated standard labels if not set.

Use Bootstrap 5 only.
Do not introduce a new frontend library.

---

## 5. Result Entry UI

When entering or verifying investigation results, the overall result input must change based on the configured type.

Rules:

### free_text

Show a textarea or text input.

Store as text.

### numeric

Show a number input.

Allow decimal values.

Store numeric value separately if the schema supports it, or validate/cast carefully if stored in the existing result field.

Show the configured unit if available.

### boolean

Show a select/radio:

```text
True
False
```

Store canonical value:

```text
true
false
```

Do not store translated labels as database values.

### positive_negative

Show a select/radio:

```text
Positive
Negative
```

Store canonical value:

```text
positive
negative
```

Do not store translated labels as database values.

---

## 6. Storage Rules

The stored overall result must be reportable.

Preferred structure, if safe with current schema:

```text
overall_result_type
overall_result_text
overall_result_numeric
overall_result_boolean
overall_result_outcome
overall_result_unit
```

Where:

```text
overall_result_text      → free text values
overall_result_numeric   → numeric values
overall_result_boolean   → true/false
overall_result_outcome   → positive/negative
overall_result_unit      → copied/displayed unit if needed
```

If the current system already has one `overall_result` column, keep it for backward compatibility but add typed columns if necessary.

Do not break existing results.

Existing free-text results must continue to display correctly.

Backwards compatibility rule:

```text
If old result has only overall_result text, treat it as free_text unless the investigation type is configured otherwise.
```

---

## 7. Validation Rules

Add validation based on selected result type.

For result entry:

```text
free_text           → nullable|string
numeric             → nullable|numeric
boolean             → nullable|in:true,false,1,0
positive_negative   → nullable|in:positive,negative
```

For catalogue configuration:

```text
overall_result_type → required|in:free_text,numeric,boolean,positive_negative
```

If numeric min/max are added:

```text
min/max must be numeric
max must be >= min
```

---

## 8. Reporting / Tally Logic

Add report/statistics support for tallying overall results.

For positive/negative investigations, reports should be able to count:

```text
Positive count
Negative count
Total tested
Positive rate
Negative rate
```

For boolean investigations, reports should be able to count:

```text
True count
False count
Total tested
True rate
False rate
```

For numeric investigations, reports should support:

```text
count
average
minimum
maximum
normal / abnormal count if min/max configured
```

For free-text investigations, reports should support:

```text
count completed
latest result summaries where appropriate
```

Do not mix numeric aggregation with text results.

Add this logic in a service class, not directly in controllers or Blade.

Example service name if no better existing service exists:

```text
InvestigationResultSummaryService
```

Reuse existing report services if already present.

---

## 9. Display Rules

Wherever an overall result is displayed, format it based on type.

Examples:

```text
free_text           → "No acute abnormality detected"
numeric             → "5.6 mmol/L"
boolean true        → "True" / translated label
boolean false       → "False" / translated label
positive            → "Positive" / translated label
negative            → "Negative" / translated label
```

Database values remain canonical English/internal values.

Displayed values must be translated using language files.

---

## 10. Localisation

Add EN/FR translation keys for all new labels.

Use or extend:

```text
lang/en/investigations.php
lang/fr/investigations.php
lang/en/lab.php
lang/fr/lab.php
lang/en/reports.php
lang/fr/reports.php
```

Required keys include:

```text
overall_result_type
free_text
numeric_value
true_false
positive_negative
overall_result_unit
minimum_normal_value
maximum_normal_value
positive
negative
true
false
positive_count
negative_count
true_count
false_count
positive_rate
negative_rate
average_value
minimum_value
maximum_value
normal_count
abnormal_count
completed_count
```

Maintain EN/FR parity.

Run the localisation tests after changes.

---

## 11. Permissions / Security

Do not weaken permissions.

Preserve existing access control for:

```text
investigation catalogue configuration
result entry
result verification
result viewing
reports
```

Do not expose clinical results to users without permission.

Do not move business logic into Blade.

---

## 12. Backward Compatibility

Existing investigation records must continue to work.

Do not delete old result data.

Do not rename existing columns without migration safety.

If new typed columns are added, existing data should still display through the old free-text fallback.

Add compatibility helpers if needed.

---

## 13. Tests

Add or update tests for:

```text
catalogue can configure free_text result type
catalogue can configure numeric result type
catalogue can configure boolean result type
catalogue can configure positive_negative result type
result entry validates based on configured type
numeric result can be stored and displayed with unit
boolean result stores canonical value and displays translated value
positive_negative result stores canonical value and displays translated value
old free-text results still display
report tally counts positive/negative correctly
report tally counts true/false correctly
report summary calculates numeric average/min/max
unauthorized users cannot configure result types
unauthorized users cannot view restricted results
```

Also run existing localisation tests:

```bash
php artisan test tests/Feature/Localization
php scripts/localisation-audit.php
```

Active runtime candidates must remain:

```text
0
```

---

## 14. Verification Commands

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
php artisan test tests/Feature/Localization
php scripts/localisation-audit.php
php artisan test
```

If frontend assets are touched and dependencies are available:

```bash
npm run build
```

Run:

```bash
git diff --check
```

---

## 15. Documentation

Create:

```text
docs/INVESTIGATION_CONFIGURABLE_OVERALL_RESULT_TYPE_REPORT.md
```

Include:

* summary
* database changes
* model changes
* UI changes
* result entry behavior
* storage strategy
* backward compatibility notes
* reporting/tally logic
* permissions/security notes
* tests added
* commands run
* localisation audit result
* remaining risks
* next recommended phase

---

## 16. Acceptance Criteria

This implementation is complete only when:

* each investigation/test type can configure its overall result type
* result entry UI changes based on configured type
* values are stored canonically and safely
* old free-text results still display
* positive/negative results can be tallied
* true/false results can be tallied
* numeric results can be aggregated
* free-text results remain supported
* EN/FR localisation parity passes
* active runtime localisation candidates remain 0
* route list works
* view cache compiles
* tests pass or failures are documented
* no permissions are weakened
* no clinical data exposure is introduced
* documentation report is created

Proceed with the configurable investigation overall result implementation now.
