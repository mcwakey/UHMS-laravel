 You are working on UHMS — Ultimate Hospital Management System.

Important:
There is currently no docs/UHMS_IMPLEMENTATION_SKILL.md file in this project.
Do not try to read it.
Follow the instructions in this prompt directly.

We have completed:

* Localisation Phase 1: base EN/FR infrastructure
* Localisation Phase 2: high-traffic screen translation, reports hub, controller flash messages
* Localisation Phase 3: reports, analytics, export and print translation layer
* Localisation Phase 4: remaining operational screens bulk translation

Current localisation status:

* 23 EN/FR language file pairs exist
* 3,301 EN keys and 3,301 FR keys exist
* EN/FR parity was verified
* 141 Blade views contain translation calls
* All main operational Blade screens are translated
* Bootstrap 5 + Tabler Icons remain the UI standard
* No parallel localisation system was created

Now proceed with:

# UHMS Localisation Phase 5 — Final QA, JavaScript, Emails & Dynamic Label Cleanup

## Goal

Perform the final localisation quality assurance pass before moving to Reports, Analytics, Export & Print implementation.

This phase must close the remaining localisation gaps after Phase 4.

Focus on:

1. JavaScript resource files
2. Controller flash messages
3. Email templates
4. Dynamic enum/model labels
5. Missing translation keys
6. EN/FR parity verification
7. Browser and UI QA
8. Documentation

Do not build new features.
Do not create a new localisation system.
Do not create duplicate middleware.
Do not create duplicate locale routes.
Do not rewrite business logic.
Do not change workflows.
Do not introduce Tailwind.
Do not introduce Vue/React.
Do not introduce a new translation package.

Use existing UHMS stack:

* Laravel
* Blade
* Bootstrap 5
* Tabler Icons
* existing `lang/en` and `lang/fr`
* existing `SetLocale` middleware
* existing locale switcher
* existing translated components
* existing JavaScript i18n bridge pattern

---

# 1. Audit First

Audit the remaining localisation gaps.

Check:

```text
resources/js/
resources/views/emails/
app/Http/Controllers/
app/Models/
app/Enums/
app/Services/
app/Helpers/
resources/views/**/*.blade.php
lang/en/
lang/fr/
```

Look for:

* hardcoded English strings in JavaScript files
* hardcoded English strings in email templates
* hardcoded controller flash messages
* `->with('success', '...')`
* `->with('error', '...')`
* `->with('warning', '...')`
* `->with('info', '...')`
* hardcoded model `label()` methods
* hardcoded enum labels
* hardcoded service-layer display labels
* missing translation keys
* EN/FR parity mismatch
* translation keys that exist in EN but not FR
* translation keys that exist in FR but not EN
* broken translation calls
* duplicate messy translation keys

Do not edit blindly.
Audit first, then fix.

---

# 2. JavaScript Resource Files

Check all files in:

```text
resources/js/
public/js/
```

where applicable.

Find hardcoded user-facing strings in:

* alerts
* confirmations
* toast messages
* loading messages
* error messages
* empty states
* DataTables labels
* Select2 placeholders
* chart labels
* billing calculator labels
* AJAX feedback messages

If strings are used inside Blade files, continue using the existing Blade i18n bridge pattern:

```blade
@php
$i18n = [
    'loading' => __('common.loading'),
    'no_results' => __('common.no_results'),
    'confirm_delete' => __('messages.general.confirm_delete'),
];
@endphp

<script>
const uhmsI18n = @json($i18n);
</script>
```

If strings are inside standalone `resources/js` files, do not create a heavy frontend i18n framework.

Use one of these safe patterns:

1. Read from `window.UHMS_I18N` if already provided globally.
2. Add a small global bridge in the main layout if needed.
3. Keep module-level bridges where the strings are view-specific.

Do not expose sensitive data to JavaScript.
Do not expose patient clinical data in translation objects.

---

# 3. Controller Flash Messages Verification

Verify all controller flash messages use translation keys.

Search for:

```php
->with('success',
->with('error',
->with('warning',
->with('info',
session()->flash(
```

Replace hardcoded messages with:

```php
__('messages.section.key')
```

Use named placeholders for dynamic values:

```php
__('messages.patients.created', ['number' => $patient->patient_number])
```

Do not use string concatenation for translated messages.

Correct:

```php
->with('success', __('messages.invoices.created', ['number' => $invoice->invoice_number]))
```

Wrong:

```php
->with('success', 'Invoice ' . $invoice->invoice_number . ' created successfully.')
```

Do not change controller business logic.
Only translate user-facing messages.

---

# 4. Email Templates

Translate email templates in:

```text
resources/views/emails/
resources/views/mail/
```

if they exist.

Create or extend:

```text
lang/en/emails.php
lang/fr/emails.php
```

Translate:

* email subject labels where applicable
* greetings
* body labels
* button text
* footer text
* notification headings
* appointment reminders
* payment notices
* invoice notices
* admission/discharge notices
* password/user account emails if custom
* system notification emails

Do not translate:

* patient names
* staff names
* facility names
* invoice numbers
* appointment dates
* user-entered notes
* clinical free text
* reset tokens
* URLs

Do not break existing email layout.
Do not change mail delivery logic.

If no email templates exist, document that clearly.

---

# 5. Dynamic Enum and Model Labels

Audit model and enum label methods.

Check for methods like:

```php
label()
getLabelAttribute()
statusLabel()
typeLabel()
displayName()
humanName()
```

in:

```text
app/Models/
app/Enums/
app/Services/
app/Helpers/
```

If they return hardcoded English visible to users, add translation support.

Preferred approach:

* keep existing `label()` if changing it would be risky
* add `translatedLabel()` where needed
* or update label resolver to use `__('statuses.domain.key')` if already standardised

Example:

```php
public function translatedLabel(): string
{
    return __('statuses.visits.' . $this->status);
}
```

Do not translate internal codes.
Do not break database values.
Do not change enum stored values.
Do not change business rules.

---

# 6. Validation Attributes and Messages

Verify:

```text
lang/en/validation.php
lang/fr/validation.php
```

Check:

* validation syntax
* translated attribute names
* French attribute names
* custom validation messages
* missing attributes for high-traffic forms

Add missing attributes for:

* patients
* visits
* billing
* invoices
* payments
* admissions
* emergency
* triage
* lab
* stock
* users
* settings

Do not change validation rules.

---

# 7. Missing Translation Key Scan

Search Blade/PHP files for translation keys and verify they exist.

Check:

* `__('...')`
* `@lang('...')`
* `trans('...')`

Find:

* missing keys
* typo keys
* EN-only keys
* FR-only keys
* duplicate keys with different meanings
* keys placed in wrong files

Fix cleanly.

Do not remove existing keys unless obviously unused and safe.

---

# 8. EN/FR Parity Verification

Run or create a small parity verification script to confirm every lang file pair has matching top-level and nested keys.

Check all modules:

```text
admissions
auth
billing
common
dashboards
emergency
investigations
invoices
lab
menu
messages
patients
payments
pharmacy
reports
roles
settings
statuses
stock
triage
users
validation
visits
```

If new files are added, include them in the parity check.

Required result:

* 0 missing EN keys
* 0 missing FR keys
* no syntax errors in lang files

---

# 9. UI QA Pass

Manually check the system in English and French.

Verify:

1. Login page
2. Dashboard
3. Sidebar/menu
4. Patients
5. Visits
6. Billing
7. Invoices
8. Payments
9. Admissions
10. Emergency
11. Triage
12. Lab/investigations
13. Pharmacy
14. Stock/store
15. Reports hub
16. Settings
17. Users/roles
18. Print views
19. PDF views
20. Emails if previewable

Check for:

* missing translation keys
* raw key text displayed to user
* untranslated English text
* broken layout due to long French labels
* mobile overflow caused by translated text
* buttons wrapping badly
* tables overflowing without wrappers
* modals breaking on mobile
* print layout problems

Use Bootstrap 5 utilities only for responsive fixes.

---

# 10. Architecture Rules

Follow these UHMS rules:

* Do not create parallel systems.
* Do not duplicate localisation infrastructure.
* Do not create duplicate middleware.
* Do not create duplicate locale routes.
* Do not create duplicate layouts.
* Do not move business logic into Blade.
* Do not create controller-heavy logic.
* Do not bypass ActivityLogService.
* Do not break audit logging.
* Do not hardcode NHIS.
* Do not hardcode sponsors.
* Do not hardcode insurance providers.
* Do not hardcode emergency services.
* Do not expose unauthorized clinical data.
* Do not expose unauthorized financial values.
* Do not expose unauthorized stock cost.
* Products are physical stock items.
* Services are billable activities.
* Use Bootstrap 5 and Tabler Icons only.
* Do not introduce Tailwind.
* Do not introduce new packages.

---

# 11. Verification Commands

Run:

```bash
php artisan view:clear
php artisan config:clear
php artisan cache:clear
php artisan route:list
php artisan test
```

Also run syntax checks:

```bash
for f in lang/en/*.php lang/fr/*.php; do php -l "$f"; done
```

Run or create a parity check for nested translation keys.

If full tests are too broad, run relevant tests and document what was checked manually.

---

# 12. Documentation

Create:

```text
docs/LOCALISATION_PHASE_5_FINAL_QA_JS_EMAILS_DYNAMIC_LABELS_REPORT.md
```

Include:

* audit findings
* JavaScript files checked
* JavaScript strings translated
* controller flash messages verified/fixed
* email templates translated or marked not present
* enum/model labels fixed
* validation attributes updated
* missing keys fixed
* parity verification result
* syntax check result
* UI QA result
* responsive fixes made
* remaining TODOs

---

# 13. Deliverables

At the end, provide:

1. Summary of audit findings.
2. List of JavaScript files checked and changed.
3. List of controller flash messages fixed, if any.
4. List of email templates translated, if any.
5. List of enum/model label methods updated, if any.
6. List of validation attributes added or fixed.
7. List of missing keys fixed.
8. EN/FR parity result.
9. Syntax check result for language files.
10. UI QA result.
11. List of files changed.
12. Remaining TODOs, if any.
13. Confirmation that no duplicate localisation system was created.
14. Confirmation that no business logic was moved into Blade.
15. Confirmation that existing workflows still work.

Proceed with UHMS Localisation Phase 5 now.
