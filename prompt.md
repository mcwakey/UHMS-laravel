You are working on UHMS — Ultimate Hospital Management System.

We completed several localisation phases, but there are still critical pages showing untranslated English text. Treat this as a release blocker.

# UHMS Localisation Phase 6 — Critical Page Translation Audit & Fix Pass

## Goal

Find and fix all remaining untranslated user-facing text across critical UHMS pages.

Do not assume previous localisation reports are complete.
Do not rely only on EN/FR key parity.
Do not rely only on the presence of `__()` calls.
A page can still be partially untranslated even when language files have parity.

This phase must produce a real audit of remaining untranslated pages and then fix them.

---

# 1. Critical Rule

Before editing, audit the whole project.

Search all user-facing UI areas:

```text
resources/views/**/*.blade.php
resources/js/**/*.js
resources/views/emails/**/*.blade.php
app/Models/**/*.php
app/Http/Controllers/**/*.php
app/Services/**/*.php
app/View/Components/**/*.php
app/Support/**/*.php
```

Look for visible English strings in:

* page titles
* headings
* cards
* tabs
* buttons
* badges
* labels
* placeholders
* helper text
* empty states
* alerts
* modals
* confirmation messages
* table headers
* filter labels
* dropdown options
* print/PDF templates
* email templates
* JavaScript messages
* chart labels
* toast messages
* validation attribute names
* model label methods
* enum/status display methods
* sidebar/menu labels
* breadcrumbs
* dashboard widgets
* action links
* tooltip text

---

# 2. Critical Pages to Verify Manually

At minimum, verify these areas in both English and French:

```text
Dashboard / role dashboards
Department-type dashboards
Patients
Visits
Appointments
Products
Services
Pharmacy
Stock / inventory
Store
Procurement
Suppliers
Billing
Invoices
Payments
Counter sale
Credit notes
Write-offs
Refunds
Sponsors
Insurance providers
Claims
AR aging
AP aging
Accounting
Chart of accounts
Journal entries
Trial balance
General ledger
Cashbook
Profit & loss
Balance sheet
Emergency
Triage
Admissions
Wards
Beds
Theatre / procedures
Consultations
Prescriptions
Investigations
Laboratory
Reports
Print pages
PDF pages
Settings
Users
Roles
Permissions
Activity logs
Notifications
Email templates
Profile page
Login / auth pages
Error pages
```

If a route/page exists, it must be checked.

---

# 3. Do Not Translate These

Do not translate:

* patient names
* staff names
* supplier names
* product names entered by users
* service names entered by users unless system-defined
* diagnosis/free clinical notes
* medicine names
* company/hospital names
* route names
* permission names
* database codes
* audit event codes
* internal enum values unless displayed through a translated label method
* currency symbols
* clinical units such as `mmHg`, `bpm`, `kg`, `°C`, `%`
* technical selectors/classes/data attributes
* example format hints like `GHA-XXXXXXXXX-X`, unless the surrounding label is untranslated

---

# 4. Translation Method

Use the existing Laravel localisation system.

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
__('patients.created_successfully', ['number' => $patient->patient_number])
```

Do not concatenate translated strings with dynamic values.

Bad:

```php
'Patient ' . $patient->name . ' created successfully'
```

Good:

```php
__('patients.created_successfully_for', ['name' => $patient->name])
```

---

# 5. JavaScript Localisation

For inline Blade JavaScript, use the existing i18n bridge pattern:

```blade
@php
$i18n = [
    'loading' => __('common.loading'),
    'no_results' => __('common.no_results'),
];
@endphp

<script>
    const moduleI18n = @json($i18n);
</script>
```

For `resources/js/**/*.js`, do not hardcode English text.

If the JS file cannot access Laravel translations directly, expose a safe global object from the layout or page:

```blade
<script>
window.UHMS_I18N = {
    common: {
        loading: @json(__('common.loading')),
        noResults: @json(__('common.no_results')),
        error: @json(__('common.error')),
        success: @json(__('common.success')),
        confirm: @json(__('common.confirm')),
    }
};
</script>
```

Then reference:

```javascript
window.UHMS_I18N.common.loading
```

Do not introduce Vue, React, i18next, or any new frontend i18n package.

---

# 6. Dynamic Labels / Enum Labels

Audit model methods like:

```php
label()
statusLabel()
typeLabel()
paymentStatusLabel()
visitTypeLabel()
```

If they return hardcoded English, add translated equivalents without breaking existing callers.

Preferred pattern:

```php
public function translatedStatusLabel(): string
{
    return __('statuses.invoice.' . $this->status);
}
```

If safe, update UI views to use translated label methods.

Do not break existing business logic.

---

# 7. Validation Attribute Names

Update:

```text
lang/en/validation.php
lang/fr/validation.php
```

Add or complete the `attributes` array for critical forms:

* patients
* visits
* appointments
* products
* services
* billing
* invoices
* payments
* insurance
* sponsors
* claims
* emergency
* triage
* admissions
* pharmacy
* stock
* suppliers
* procurement
* accounting
* users
* settings

Validation messages must show translated field names in French.

---

# 8. Appointments and Products Are Critical

Specifically audit and fix:

```text
resources/views/appointments/**/*.blade.php
resources/views/products/**/*.blade.php
resources/views/pharmacy/products/**/*.blade.php
resources/views/stock/**/*.blade.php
resources/views/store/**/*.blade.php
resources/views/inventory/**/*.blade.php
```

If products are implemented under another path, find the correct path and translate it.

Remember UHMS rule:

```text
Products = physical stock items.
Services = billable activities.
```

Do not mix the two.

---

# 9. Print / PDF / Email Pages

Audit and translate:

```text
resources/views/**/*print*.blade.php
resources/views/**/*pdf*.blade.php
resources/views/emails/**/*.blade.php
```

Print/PDF/email labels must be translated.

Do not translate patient names, product names, service names, free text, or clinical notes.

---

# 10. Scanner Scripts

Create or update a localisation scanner command or script.

Preferred:

```text
php artisan uhms:localisation-audit
```

If an Artisan command is too heavy, create:

```text
scripts/localisation-audit.php
```

The scanner should report likely hardcoded user-facing strings in:

```text
resources/views
resources/js
resources/views/emails
app/Http/Controllers
app/Models
app/Services
app/View/Components
```

The scanner should ignore obvious non-user-facing items:

* class names
* route names
* permission names
* CSS classes
* JS selectors
* array keys
* database column names
* HTML attributes like `id`, `class`, `data-*`
* translation keys
* clinical units
* currency symbols

The scanner does not need to be perfect, but it must help find remaining untranslated text.

---

# 11. Language File Parity

After adding keys, verify EN/FR parity for every language file.

All keys in `lang/en/*.php` must exist in `lang/fr/*.php`.
All keys in `lang/fr/*.php` must exist in `lang/en/*.php`.

Add a parity checker if not already present.

---

# 12. Permissions and Security

Do not expose unauthorized data while translating.

Maintain all existing permission checks.

Especially protect:

* clinical sensitive data
* financial values
* accounting data
* sponsor/insurance financial details
* stock cost
* audit logs
* user/role/permission management

Do not remove `@can`, `Gate`, policy, middleware, or service permission checks.

---

# 13. Responsiveness Must Not Regress

While touching critical pages, fix obvious responsiveness issues only when directly encountered:

* tables must be wrapped in `.table-responsive`
* forms must stack properly on mobile
* action buttons must wrap on small screens
* modals must be usable on mobile
* filter bars must not overflow
* print pages must remain print-safe

Use Bootstrap 5 only.
Do not introduce Tailwind.
Do not introduce a new UI library.

---

# 14. Required Documentation

Create:

```text
docs/LOCALISATION_PHASE_6_CRITICAL_PAGE_AUDIT_REPORT.md
```

Include:

* audit method used
* scanner command/script added
* pages/routes audited
* files changed
* language keys added
* critical pages fixed
* remaining untranslated items, if any
* intentionally untranslated categories
* EN/FR parity result
* manual verification result
* screenshots checklist if possible
* known limitations

Do not claim “zero untranslated strings” unless the scanner and manual audit support it.

---

# 15. Verification Commands

Run:

```bash
php artisan route:list
php artisan view:clear
php artisan config:clear
php artisan cache:clear
```

Run syntax checks:

```bash
for f in lang/en/*.php lang/fr/*.php; do php -l "$f"; done
```

Run Blade/PHP syntax checks where practical.

Run the localisation scanner:

```bash
php artisan uhms:localisation-audit
```

or:

```bash
php scripts/localisation-audit.php
```

Verify French mode manually:

1. Switch to French.
2. Open every critical page listed above.
3. Confirm no page title/header/card/button/table/filter/modal/empty state still shows English.
4. Submit validation errors on key forms.
5. Confirm validation field names are translated.
6. Trigger success/error flash messages.
7. Confirm flash messages are translated.
8. Open print/PDF pages.
9. Confirm print/PDF labels are translated.
10. Open pages using JavaScript interactions.
11. Confirm JavaScript messages are translated.
12. Confirm unauthorized users still cannot see restricted clinical/financial/stock-cost data.

---

# 16. Acceptance Criteria

This phase is complete only when:

* all critical pages have been audited
* appointments pages are translated
* products/stock/product-related pages are translated
* remaining dashboard pages are translated
* remaining billing/accounting/claims pages are translated
* remaining print/PDF/email templates are translated
* JavaScript user-facing strings are translated
* validation attribute names are translated
* dynamic enum/model labels are translated where displayed
* EN/FR language files have full parity
* scanner output is clean or documented with justified exceptions
* manual French verification is documented
* no permissions are weakened
* no business logic is moved into Blade
* no parallel localisation system is created
* no new UI/i18n package is introduced
* documentation is created

---

# 17. Important UHMS Rules

Do not introduce Tailwind.
Do not introduce Vue/React/i18next.
Do not create a parallel localisation system.
Do not duplicate language files unnecessarily.
Do not remove existing routes.
Do not break existing workflows.
Do not hardcode NHIS.
Do not hardcode sponsors.
Do not hardcode insurance providers.
Do not hardcode emergency services.
Do not expose unauthorized clinical data.
Do not expose unauthorized financial data.
Do not expose stock cost to unauthorized users.
Do not move business logic into Blade.
Do not bypass existing services.
Do not bypass ActivityLogService.
Use Bootstrap 5 and Tabler Icons only.

Proceed with UHMS Localisation Phase 6 — Critical Page Translation Audit & Fix Pass now.
