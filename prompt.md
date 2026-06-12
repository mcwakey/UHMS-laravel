You are working on UHMS — Ultimate Hospital Management System.

Important:
There is currently no docs/UHMS_IMPLEMENTATION_SKILL.md file in this project.
Do not try to read it.
Follow the instructions in this prompt directly.

We already completed:

* Localisation Phase 1: base localisation infrastructure
* Localisation Phase 2: admissions, emergency, pharmacy drug catalogue, reports hub, operational reports, and controller flash messages
* Localisation Phase 3: reports, analytics, export, and print translation keys/views

Now continue with:

# UHMS Localisation Phase 4 — Remaining Operational Screens Bulk Translation

## Goal

Complete the remaining high-traffic operational screen translations that were deferred after Phase 2 and Phase 3.

This phase must focus on remaining hardcoded UI text in existing screens.

Do not create a new localisation system.
Do not create duplicate middleware.
Do not create duplicate locale routes.
Do not rewrite business logic.
Do not change workflows.
Do not introduce Tailwind.
Do not introduce a new frontend framework.
Do not introduce a new translation package.

Use existing UHMS stack:

* Laravel
* Blade
* Bootstrap 5
* Tabler Icons
* existing `lang/en` and `lang/fr`
* existing `SetLocale` middleware
* existing `POST /locale` route
* existing user locale persistence
* existing language switcher
* existing translated components
* existing status badge translation resolver

---

# 1. Audit First

Before editing, audit the current codebase and confirm what remains hardcoded.

Prioritise these known TODO areas:

```text
resources/views/patients/
resources/views/visits/
resources/views/billing/
resources/views/invoices/
resources/views/payments/
resources/views/investigations/
resources/views/stock/
resources/views/users/
resources/views/settings/
resources/views/triage/
resources/views/emergency/show.blade.php
resources/views/admissions/show.blade.php
resources/views/prints/
resources/views/pdf/
resources/views/components/
resources/views/partials/
resources/js/
```

Also check:

```text
lang/en/patients.php
lang/fr/patients.php
lang/en/visits.php
lang/fr/visits.php
lang/en/billing.php
lang/fr/billing.php
lang/en/invoices.php
lang/fr/invoices.php
lang/en/payments.php
lang/fr/payments.php
lang/en/investigations.php
lang/fr/investigations.php
lang/en/stock.php
lang/fr/stock.php
lang/en/users.php
lang/fr/users.php
lang/en/settings.php
lang/fr/settings.php
lang/en/triage.php
lang/fr/triage.php
lang/en/validation.php
lang/fr/validation.php
```

If a language file exists, extend it.
If it does not exist and the module needs it, create both EN and FR versions.

Do not duplicate keys unnecessarily.

---

# 2. Translation Scope

Translate visible UI text in:

1. Patients
2. Visits
3. Billing
4. Invoices
5. Payments
6. Investigations / Laboratory
7. Stock / Inventory
8. Users
9. Settings
10. Triage
11. Emergency show page deep content
12. Admissions show page deep content
13. Inline JavaScript strings
14. Invoice/receipt print templates
15. Validation attribute names

Translate:

* page titles
* section titles
* breadcrumbs
* buttons
* action menus
* form labels
* placeholders
* help text
* filters
* search fields
* table headers
* empty states
* status labels
* modal titles
* modal buttons
* confirmation messages
* alert messages
* validation attribute names
* print labels
* JavaScript UI labels

Do not translate:

* patient names
* staff names
* doctor names
* supplier names
* sponsor names
* insurance provider names
* product names entered by users
* service names entered by users unless system-defined
* diagnosis free text
* clinical notes
* prescription notes
* audit event codes
* permission names
* route names
* database/internal codes unless mapped through display labels

---

# 3. Patients Module

Translate remaining hardcoded text in:

```text
resources/views/patients/
```

Create or extend:

```text
lang/en/patients.php
lang/fr/patients.php
```

Cover:

* patient list
* patient create/edit forms
* patient profile/show page
* patient search
* patient cards
* patient documents
* patient timeline labels
* patient visit links
* patient billing links
* patient status labels
* patient contact fields
* next of kin fields
* emergency contact fields
* insurance/sponsor section labels

Preserve existing patient workflows.

---

# 4. Visits Module

Translate remaining hardcoded text in:

```text
resources/views/visits/
```

Create or extend:

```text
lang/en/visits.php
lang/fr/visits.php
```

Cover:

* visit list
* visit create/edit
* visit type labels
* visit status labels
* consultation/session labels
* doctor assignment labels
* department labels
* queue labels
* emergency visit labels
* admission-related visit labels
* billing preview labels

Rules:

* emergency visits must remain part of Visit workflow
* do not create a separate emergency patient lifecycle
* do not hardcode emergency consultation services

---

# 5. Billing, Invoices and Payments

Translate remaining hardcoded text in:

```text
resources/views/billing/
resources/views/invoices/
resources/views/payments/
```

Create or extend:

```text
lang/en/billing.php
lang/fr/billing.php
lang/en/invoices.php
lang/fr/invoices.php
lang/en/payments.php
lang/fr/payments.php
```

Cover:

* invoice list
* invoice show
* invoice create/edit if present
* payment screens
* billing queue
* billing calculator labels
* discount labels
* credit note labels
* write-off labels
* refund labels
* sponsor/insurance billing labels
* payment method labels
* payer type labels
* balance labels
* cashier labels
* receipt labels

Rules:

* payment is not revenue
* discount, credit note, write-off and refund wording must stay distinct
* do not hardcode NHIS
* do not hardcode sponsors
* do not hardcode insurance providers
* do not move accounting logic into Blade

---

# 6. Investigations / Laboratory

Translate remaining hardcoded text in:

```text
resources/views/investigations/
resources/views/lab/
resources/views/laboratory/
```

depending on the existing project structure.

Create or extend:

```text
lang/en/investigations.php
lang/fr/investigations.php
```

Cover:

* investigation requests
* pending results
* result entry
* verification labels
* rejected/cancelled labels
* urgent labels
* lab report labels
* requested by
* verified by
* specimen/sample labels
* test/service labels

Do not translate clinical free text or result values entered by users.

---

# 7. Stock / Inventory

Translate remaining hardcoded text in:

```text
resources/views/stock/
resources/views/inventory/
resources/views/procurement/
```

depending on the existing project structure.

Create or extend:

```text
lang/en/stock.php
lang/fr/stock.php
```

Cover:

* stock list
* stock movement
* stock adjustment
* low stock
* out of stock
* expired stock
* stock transfer
* procurement labels
* purchase order labels
* goods received labels
* supplier labels
* batch labels
* expiry labels
* quantity labels

Rules:

* products are physical stock items
* services are billable activities
* do not expose stock cost without permission
* do not duplicate stock calculations

---

# 8. Users, Roles and Settings

Translate remaining hardcoded text in:

```text
resources/views/users/
resources/views/roles/
resources/views/settings/
```

Create or extend:

```text
lang/en/users.php
lang/fr/users.php
lang/en/roles.php
lang/fr/roles.php
lang/en/settings.php
lang/fr/settings.php
```

Cover:

* user list
* user create/edit
* roles
* permissions UI labels
* profile settings
* facility settings
* module settings
* billing/accounting settings labels
* localisation settings
* status labels

Do not translate permission names internally.
Only translate user-facing labels.

---

# 9. Triage

Translate remaining hardcoded text in:

```text
resources/views/triage/
```

Create or extend:

```text
lang/en/triage.php
lang/fr/triage.php
```

Cover:

* triage queue
* triage assessment
* vitals labels
* priority labels
* emergency triage labels
* nurse notes labels
* clinical warning labels

Do not translate clinical free text entered by users.

---

# 10. Emergency and Admissions Deep Pages

Continue from Phase 2 deferred items.

Translate deeper hardcoded content inside:

```text
resources/views/emergency/show.blade.php
resources/views/admissions/show.blade.php
```

Cover:

* inner cards
* modal forms
* tab labels
* ward round labels
* medication administration labels
* discharge clearance labels
* emergency disposition labels
* billing/admission action labels

Do not change emergency/admission workflows.

---

# 11. Invoice, Receipt and Operational Print Templates

Translate remaining hardcoded labels in invoice/receipt print templates.

Check:

```text
resources/views/prints/
resources/views/pdf/
resources/views/billing/
resources/views/invoices/
resources/views/receipts/
```

Translate system labels:

* Invoice
* Receipt
* Patient
* Date
* Doctor
* Service
* Product
* Quantity
* Unit price
* Discount
* Tax
* Total
* Paid
* Balance
* Payment method
* Generated by
* Printed at
* Signature

Do not translate:

* patient names
* service names entered by users
* product names entered by users
* clinical notes
* free text

Do not break print layout.

---

# 12. JavaScript Inline Strings

Translate inline JavaScript UI strings where practical.

Check:

* billing calculator labels
* confirmation dialogs
* delete confirmations
* AJAX loading messages
* chart labels
* DataTables labels
* Select2 placeholders
* empty result messages

If the project already exposes translations to JavaScript, reuse that approach.

If needed, safely expose only UI strings through Blade:

```blade
<script>
window.UHMS_I18N = {
    loading: @json(__('common.loading')),
    noResults: @json(__('common.no_results')),
    confirmDelete: @json(__('messages.general.confirm_delete')),
};
</script>
```

Do not expose sensitive data.
Do not add a heavy frontend i18n framework.

---

# 13. Validation Attributes

Populate missing validation attributes, especially in French.

Update:

```text
lang/en/validation.php
lang/fr/validation.php
```

Add user-facing attribute names for common fields:

```text
first_name
last_name
middle_name
gender
date_of_birth
phone
email
address
patient_id
visit_id
department_id
doctor_id
service_id
product_id
quantity
price
amount
payment_method
insurance_provider_id
sponsor_id
supplier_id
admission_id
ward_id
bed_id
diagnosis
notes
status
```

Do not change validation rules.
Only improve translated field names/messages.

---

# 14. Responsive Cleanup While Translating

While touching Blade files, fix obvious responsiveness problems.

Check:

* horizontal overflow
* table wrappers
* mobile filter layout
* action button wrapping
* modal sizing
* tab scrolling
* card stacking
* print hidden elements

Use Bootstrap 5 utilities only.

Do not introduce Tailwind.
Do not create duplicate layouts.

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

---

# 16. Verification

After implementation, verify:

1. English locale loads.
2. French locale loads.
3. Locale switcher still works.
4. User locale persistence still works.
5. Patient screens translate.
6. Visit screens translate.
7. Billing screens translate.
8. Invoice screens translate.
9. Payment screens translate.
10. Investigation/lab screens translate.
11. Stock screens translate.
12. User/role screens translate.
13. Settings screens translate.
14. Triage screens translate.
15. Emergency show deep content translates.
16. Admissions show deep content translates.
17. Invoice/receipt print labels translate.
18. Inline JavaScript labels translate where touched.
19. French validation attribute names display correctly.
20. No missing translation key errors.
21. No broken Blade syntax.
22. No mobile responsiveness regression.
23. No permission regression.
24. No workflow regression.

Run:

```bash
php artisan view:clear
php artisan config:clear
php artisan route:list
php artisan test
```

If full tests are too broad, run relevant tests and document manual checks.

---

# 17. Documentation

Create or update:

```text
docs/LOCALISATION_PHASE_4_REMAINING_OPERATIONAL_SCREENS_REPORT.md
```

Include:

* audit findings
* language files added/updated
* modules translated
* views/components touched
* JavaScript strings translated
* print templates translated
* validation attributes added
* responsive fixes made
* verification performed
* remaining TODOs

---

# 18. Deliverables

At the end, provide:

1. Summary of what remained from Phase 2/3.
2. Summary of what was translated in Phase 4.
3. List of files changed.
4. List of language files added or updated.
5. List of views/components touched.
6. List of JavaScript strings translated.
7. List of print templates touched.
8. List of validation attributes added.
9. List of responsive fixes made.
10. Confirmation that no duplicate localisation system was created.
11. Confirmation that no business logic was moved into Blade.
12. Confirmation that existing workflows still work.
13. Remaining untranslated areas, if any.

Proceed with UHMS Localisation Phase 4 now.
