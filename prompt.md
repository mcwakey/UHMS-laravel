You are working on UHMS — Ultimate Hospital Management System.

Important:
There is currently no docs/UHMS_IMPLEMENTATION_SKILL.md file in this project.
Do not try to read it.
Follow the instructions in this prompt directly.

We already started UHMS responsiveness and localisation.

Now continue with:

# UHMS Localisation Phase 2 — High-Traffic Screen Translation & UI Text Standardisation

## Goal

Continue translating UHMS from hardcoded visible text into proper Laravel language files.

The goal is to make the main high-traffic screens usable in both English and French, while preserving the existing UI, routes, business logic, permissions, services, and workflows.

Do not restart the localisation system from scratch.
Do not create a parallel translation system.
Do not duplicate existing middleware, routes, helpers, or language files.
Audit first, reuse what exists, then continue.

Use the existing UHMS stack:

* Laravel
* Blade
* Bootstrap 5
* Tabler Icons
* existing localisation infrastructure
* existing layouts and components
* existing permissions and services

---

# 1. Audit First

Before implementing, audit the current localisation work.

Check for:

* existing `lang/en`
* existing `lang/fr`
* existing `lang/en/*.php` files
* existing `lang/fr/*.php` files
* locale middleware
* locale route, especially `POST /locale` or similar
* user locale persistence, especially `users.locale`
* header/topbar language switcher
* sidebar/menu translation hooks
* translated status badge helper/resolver
* translated validation files
* translated auth/pagination/password files
* translated Blade components
* translated dashboard text
* translated table labels
* translated flash messages
* translated JavaScript strings
* responsive/localisation documentation if already present

Do not duplicate anything that already exists.
If an existing structure is present, extend it.

---

# 2. Translation Priority

Focus on high-traffic screens first:

1. Dashboard
2. Patients
3. Visits
4. Appointments
5. Triage
6. Emergency
7. Billing
8. Payments
9. Invoices
10. Sponsors / Insurance
11. Pharmacy
12. Laboratory / Investigations
13. Admissions / Wards
14. Stock / Inventory
15. Users / Roles
16. Settings

Translate visible text only.

Move hardcoded UI text from Blade, controllers, components, and JavaScript into language keys.

---

# 3. What Must Be Translated

Translate:

* page titles
* page subtitles
* breadcrumbs
* menu labels
* sidebar labels
* topbar labels
* buttons
* action dropdowns
* form labels
* placeholders
* help text
* filter labels
* search labels
* table headers
* empty states
* status labels
* badge labels
* modal titles
* modal buttons
* confirmation messages
* success flash messages
* error flash messages
* warning flash messages
* informational flash messages
* validation messages where applicable
* print labels where safe
* dashboard KPI labels
* chart labels where practical
* DataTables labels if DataTables is used
* Select2 placeholders if Select2 is used

---

# 4. What Must Not Be Translated

Do not translate:

* patient names
* staff names
* doctor names
* supplier names
* sponsor names
* insurance provider names
* product names entered by users
* service names entered by users unless system-defined
* clinical notes
* diagnosis free text
* prescription notes
* audit event codes
* route names
* permission names
* database enum/internal codes unless mapped through a display label
* log keys used internally
* API payload keys
* migration names
* model class names

Where internal statuses are displayed to users, map them through a status translation helper or language key.

---

# 5. Language File Structure

Create or update language files cleanly.

Recommended structure:

```text
lang/en/common.php
lang/fr/common.php

lang/en/menu.php
lang/fr/menu.php

lang/en/dashboard.php
lang/fr/dashboard.php

lang/en/patients.php
lang/fr/patients.php

lang/en/visits.php
lang/fr/visits.php

lang/en/appointments.php
lang/fr/appointments.php

lang/en/triage.php
lang/fr/triage.php

lang/en/emergency.php
lang/fr/emergency.php

lang/en/billing.php
lang/fr/billing.php

lang/en/payments.php
lang/fr/payments.php

lang/en/invoices.php
lang/fr/invoices.php

lang/en/insurance.php
lang/fr/insurance.php

lang/en/pharmacy.php
lang/fr/pharmacy.php

lang/en/investigations.php
lang/fr/investigations.php

lang/en/admissions.php
lang/fr/admissions.php

lang/en/stock.php
lang/fr/stock.php

lang/en/users.php
lang/fr/users.php

lang/en/settings.php
lang/fr/settings.php

lang/en/statuses.php
lang/fr/statuses.php

lang/en/actions.php
lang/fr/actions.php

lang/en/messages.php
lang/fr/messages.php

lang/en/validation.php
lang/fr/validation.php
```

If the project already has a different clean structure, reuse it instead of forcing this one.

Avoid giant messy files if module-specific files already exist.

---

# 6. Key Naming Rules

Use clear, reusable keys.

Examples:

```php
__('common.save')
__('common.cancel')
__('common.search')
__('common.filter')
__('common.reset')
__('common.export')
__('common.print')
__('common.actions')

__('patients.title')
__('patients.create')
__('patients.edit')
__('patients.fields.first_name')
__('patients.fields.last_name')
__('patients.empty.no_patients_found')

__('visits.title')
__('visits.create')
__('visits.fields.visit_type')
__('visits.statuses.active')
__('visits.statuses.completed')

__('billing.invoices.title')
__('billing.payments.title')
__('billing.messages.invoice_created')
```

Avoid keys like:

```php
__('text1')
__('label2')
__('new_page_title')
__('button_here')
```

Do not duplicate common words in every module if `common.php` already handles them.

---

# 7. Blade Translation Rules

Replace hardcoded visible text in Blade with translation helpers.

Example:

```blade
<h1>{{ __('patients.title') }}</h1>
<button>{{ __('common.save') }}</button>
<label>{{ __('patients.fields.first_name') }}</label>
<input placeholder="{{ __('patients.placeholders.search_patient') }}">
```

Do not translate variable user data.

Correct:

```blade
<td>{{ $patient->full_name }}</td>
<td>{{ __('visits.statuses.' . $visit->status) }}</td>
```

Wrong:

```blade
<td>{{ __($patient->full_name) }}</td>
```

Do not put business logic in Blade while translating.

---

# 8. Controller Flash Messages

Move hardcoded controller messages into language files.

Example:

```php
return redirect()
    ->route('patients.index')
    ->with('success', __('patients.messages.created'));
```

Translate messages in both English and French.

Cover:

* create success
* update success
* delete success
* activation/deactivation success
* validation failure messages where custom
* business rule error messages
* permission/authorization messages where custom

Do not change business behavior while translating.

---

# 9. Validation Translation

Use Laravel validation translation properly.

Update:

```text
lang/en/validation.php
lang/fr/validation.php
```

Where Form Requests have custom messages, move them to translation keys.

Translate attribute names where useful:

```php
'attributes' => [
    'first_name' => 'first name',
    'last_name' => 'last name',
]
```

French example:

```php
'attributes' => [
    'first_name' => 'prénom',
    'last_name' => 'nom',
]
```

Do not weaken validation rules.

---

# 10. JavaScript Translation

Audit JavaScript strings used in:

* confirmation dialogs
* delete confirmations
* DataTables
* Select2
* chart labels
* AJAX loading messages
* empty messages
* search/filter labels

If the project already exposes translations to JavaScript, reuse that approach.

If not, safely expose only needed strings through Blade:

```blade
<script>
window.UHMS_I18N = {
    confirmDelete: @json(__('messages.confirm_delete')),
    loading: @json(__('common.loading')),
    noResults: @json(__('common.no_results')),
};
</script>
```

Do not expose sensitive data to JavaScript.

Do not create a heavy new frontend translation framework.

---

# 11. Status Translation

Standardise status display.

Create or update:

```text
lang/en/statuses.php
lang/fr/statuses.php
```

Translate common statuses:

```text
active
inactive
pending
completed
cancelled
approved
rejected
paid
unpaid
partially_paid
draft
posted
voided
refunded
admitted
discharged
in_progress
verified
dispensed
partially_dispensed
out_of_stock
low_stock
expired
```

Use a helper/resolver if one already exists.

Do not translate database values directly unless safely mapped.

---

# 12. Menu and Permission-Aware Navigation

Translate sidebar/menu labels.

Use:

```php
__('menu.patients')
__('menu.visits')
__('menu.billing')
__('menu.pharmacy')
__('menu.reports')
```

Rules:

* keep existing permission checks
* keep existing module visibility checks
* do not expose hidden modules through translation changes
* do not duplicate menu arrays unnecessarily

---

# 13. Print and PDF Translation

Review print/PDF templates where safe:

* invoice print
* receipt print
* prescription print
* lab result print
* patient card print
* visit summary print
* billing report print
* stock print views if any

Translate system labels:

* Invoice
* Receipt
* Date
* Patient
* Doctor
* Quantity
* Amount
* Total
* Paid
* Balance
* Generated by
* Printed at

Do not translate:

* patient names
* service names unless system-defined
* drug names
* diagnosis free text
* clinical notes

Do not break existing print layout.

---

# 14. Responsive Check While Translating

While touching Blade files, also fix obvious responsive issues.

Check:

* mobile horizontal overflow
* table wrapping
* filter bars on mobile
* action buttons on small screens
* modals on small screens
* tabs on mobile
* cards stacking
* print hidden elements

Use Bootstrap 5 utilities only.

Do not introduce Tailwind.
Do not create a second UI system.

---

# 15. Architecture Rules

Follow these UHMS rules:

* Do not create parallel systems.
* Do not duplicate existing localisation infrastructure.
* Do not create duplicate middleware.
* Do not create duplicate locale routes.
* Do not create duplicate layouts.
* Do not move business logic into Blade.
* Do not create controller-heavy logic.
* Use services where business logic is needed.
* Do not bypass ActivityLogService.
* Do not break audit logging.
* Do not hardcode NHIS.
* Do not hardcode sponsors.
* Do not hardcode insurance providers.
* Do not hardcode emergency services.
* Products are physical stock items.
* Services are billable activities.
* Use Bootstrap 5 and Tabler Icons only.

---

# 16. Files to Prioritise

Prioritise likely files in these areas:

```text
resources/views/layouts/
resources/views/components/
resources/views/partials/
resources/views/dashboard/
resources/views/patients/
resources/views/visits/
resources/views/appointments/
resources/views/triage/
resources/views/emergency/
resources/views/billing/
resources/views/payments/
resources/views/invoices/
resources/views/pharmacy/
resources/views/investigations/
resources/views/admissions/
resources/views/stock/
resources/views/users/
resources/views/settings/

app/Http/Controllers/
app/Http/Requests/
app/View/Components/
resources/js/
routes/web.php
lang/en/
lang/fr/
```

Do not blindly edit every file.
Start with high-traffic screens and shared components.

---

# 17. Verification

After implementation, verify:

1. English locale loads.
2. French locale loads.
3. Locale switcher works.
4. User locale persists if implemented.
5. Dashboard labels translate.
6. Sidebar/menu labels translate.
7. Patient screens translate.
8. Visit screens translate.
9. Billing screens translate.
10. Pharmacy screens translate.
11. Investigation screens translate.
12. Emergency screens translate.
13. Admission screens translate if present.
14. Common buttons translate.
15. Status badges translate.
16. Flash messages translate.
17. Validation messages translate.
18. Print labels translate where touched.
19. No obvious hardcoded text remains on targeted screens.
20. No broken Blade syntax.
21. No missing translation key errors.
22. No mobile layout regression.
23. No permission regression.
24. Existing workflows still work.

Run available checks such as:

```bash
php artisan test
php artisan route:list
php artisan view:clear
php artisan config:clear
```

If full tests are too broad, run relevant tests and document what was checked manually.

---

# 18. Documentation

Create or update:

```text
docs/LOCALISATION_RESPONSIVENESS_PHASE_2_REPORT.md
```

Include:

* audit findings
* files changed
* language files added/updated
* modules translated
* controllers/messages translated
* validation files updated
* JavaScript strings translated
* print views touched
* responsive fixes made
* remaining untranslated areas
* manual verification results
* known TODOs

---

# 19. Deliverables

At the end, provide:

1. Summary of what existed before changes.
2. Summary of what was added or improved.
3. List of files changed.
4. List of language files added or updated.
5. List of main modules translated.
6. List of responsive fixes made.
7. List of remaining untranslated areas.
8. Confirmation that no duplicate localisation system was created.
9. Confirmation that Bootstrap 5 + Tabler Icons remain the UI standard.
10. Confirmation that business logic was not moved into Blade views.
11. Confirmation that existing workflows still work.

Proceed with UHMS Localisation Phase 2 now.
