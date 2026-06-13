You are working on UHMS — Ultimate Hospital Management System.

Important:
There is currently no `docs/UHMS_IMPLEMENTATION_SKILL.md` file in this project.
Do not try to read it.
Follow this prompt directly.

We are not ready for dashboards or the Full Test Suite yet.

The latest localisation audit still shows active untranslated runtime pages. Phase 15 reduced the active runtime candidates, but 519 active runtime candidates remain. The user has also manually confirmed that consultation pages, auth pages, and several other critical pages still show English text in French mode.

Treat this as a release blocker.

# UHMS Localisation Phase 15B — Critical Runtime Pages Completion Pass

## Goal

Complete localisation of the remaining active runtime pages that are still visible to real users.

Do not work on dormant demo/template views unless they are actually reachable through live routes, controllers, layouts, shared components, or Blade dependencies.

Focus on real active runtime pages.

---

# 1. Required Input Reports

Use these existing reports as your starting point:

```text
docs/LOCALISATION_COVERAGE_AUDIT_REPORT.md
docs/LOCALISATION_PHASE_15_ACTIVE_RUNTIME_CANDIDATE_BURNDOWN_REPORT.md
```

If these docs exist, read them first.

Do not ignore the active runtime worklist.

Do not claim localisation is complete until the active runtime candidate count is significantly reduced and all manually confirmed critical pages are fixed.

---

# 2. Immediate Critical Pages To Fix First

The user has manually confirmed that these are still untranslated:

```text
Consultation pages
Auth pages
```

Therefore start with these areas before touching lower-priority modules.

## 2.1 Consultation Pages

Audit and translate all consultation-related active views and dependencies, including but not limited to:

```text
resources/views/consultations/**/*.blade.php
resources/views/prescriptions/**/*.blade.php
resources/views/partials/patient-card.blade.php
resources/views/partials/patient-visit-header.blade.php
resources/views/claims/partials/clinical-mirror.blade.php
resources/views/vitals/**/*.blade.php
resources/views/lab/**/*.blade.php
resources/views/investigations/**/*.blade.php
```

Also audit related JS inside these Blade files.

Fix:

* consultation page title
* patient summary labels
* visit summary labels
* complaints
* history of presenting complaints
* physical examination
* diagnosis
* investigations
* prescriptions
* treatment plan
* follow-up
* save/update buttons
* clinical task labels
* empty states
* loading text
* confirmation dialogs
* modal titles
* table headers
* badges/statuses
* tabs
* validation field names
* JavaScript messages

Use existing `lang/en/consultations.php` and `lang/fr/consultations.php` where possible.
Add missing keys with EN/FR parity.

Do not translate patient-entered clinical notes.
Do not translate diagnosis text typed by clinicians.
Do not translate medicine names.
Do not translate lab test names entered as catalogue data unless system-defined labels are hardcoded.

---

## 2.2 Auth Pages

Audit and translate all auth-related pages:

```text
resources/views/auth/**/*.blade.php
resources/views/profile/**/*.blade.php
resources/views/settings/profile.blade.php
resources/views/layout/partials/**/*.blade.php
resources/views/components/**/*.blade.php
```

Fix:

* login page
* register page if enabled
* forgot password page
* reset password page
* verify email page if present
* confirm password page if present
* profile page
* account settings page
* logout labels
* remember me
* email/password labels
* placeholders
* validation labels
* submit buttons
* auth error messages
* session messages
* browser title
* layout auth header/footer text

Expand:

```text
lang/en/auth.php
lang/fr/auth.php
```

Do not leave auth with only a few keys if more auth pages exist.

---

# 3. Next High-Priority Runtime Modules

After consultations and auth, process the remaining active runtime worklist in this order:

```text
1. Pharmacy
2. Laboratory / Analyzers / Investigation catalogue
3. Theatre / Procedures
4. Wards / Beds
5. Stock / Store / Product stock
6. Prescriptions
7. Emergency remaining candidates
8. Billing / Invoices remaining candidates
9. Accounting / Payables / Settings
10. Notifications
11. Queue
12. HR
13. Blood bank
14. Settings
15. Dashboards
```

Do not skip route-linked files.

Use the active runtime worklist from the audit report as the source of truth.

---

# 4. Required Files From Current Audit To Prioritise

At minimum, fix these files from the active runtime worklist if they still contain candidates:

```text
resources/views/consultations/show.blade.php
resources/views/consultations/history.blade.php
resources/views/auth/**/*.blade.php
resources/views/pharmacy/dispense.blade.php
resources/views/pharmacy/drug-history.blade.php
resources/views/pharmacy/history.blade.php
resources/views/admin/analyzers/diagnostics.blade.php
resources/views/admin/analyzers/index.blade.php
resources/views/admin/analyzers/show.blade.php
resources/views/theatre/rooms/index.blade.php
resources/views/admin/procedures/index.blade.php
resources/views/admin/procedures/schedule.blade.php
resources/views/prescriptions/show.blade.php
resources/views/prescriptions/index.blade.php
resources/views/wards/index.blade.php
resources/views/wards/beds.blade.php
resources/views/wards/bed-map.blade.php
resources/views/admin/product-stock/ledger.blade.php
resources/views/admin/product-stock/balances.blade.php
resources/views/admin/product-stock/receive.blade.php
resources/views/admin/product-stock/transfer.blade.php
resources/views/admin/product-stock/adjust.blade.php
resources/views/admin/product-stock/return.blade.php
resources/views/store/purchase-orders/index.blade.php
resources/views/store/purchase-orders/create.blade.php
resources/views/store/purchase-orders/show.blade.php
resources/views/store/purchase-returns/index.blade.php
resources/views/store/purchase-returns/create.blade.php
resources/views/store/purchase-returns/show.blade.php
resources/views/store/supplier-ledger.blade.php
resources/views/store/suppliers.blade.php
resources/views/billing/invoices/show.blade.php
resources/views/accounting/payable/payables.blade.php
resources/views/accounting/settings/index.blade.php
resources/views/notifications/index.blade.php
resources/views/queue/manage.blade.php
resources/views/queue/board.blade.php
```

If some files do not exist, document them as not found.

---

# 5. Translation Rules

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
__('consultations.saved_for_patient', ['patient' => $patient->name])
```

Do not concatenate translated fragments.

Bad:

```php
'Consultation for ' . $patient->name
```

Good:

```php
__('consultations.consultation_for_patient', ['patient' => $patient->name])
```

---

# 6. JavaScript Translation

For inline Blade JavaScript, use page-level JSON maps:

```blade
@php
$consultationI18n = [
    'loading' => __('common.loading'),
    'save_success' => __('consultations.save_success'),
    'confirm_delete' => __('consultations.confirm_delete'),
];
@endphp

<script>
    window.UHMS_CONSULTATION_I18N = @json($consultationI18n);
</script>
```

Then use the translated values in JavaScript.

For global JS, use the existing `window.UHMS_I18N` mechanism if already present.
Do not create a second localisation framework.
Do not introduce i18next, Vue, React, or any new frontend package.

---

# 7. Dynamic Labels

Audit visible calls like:

```php
label()
typeLabel()
statusLabel()
paymentStatusLabel()
visitTypeLabel()
consultationModeLabel()
priorityLabel()
```

If they return hardcoded English and are displayed in active pages, add or use translated methods such as:

```php
translatedLabel()
translatedStatusLabel()
translatedTypeLabel()
```

Only convert displays after confirming the method is for UI output.
Do not change stored canonical values or database enum values.

---

# 8. Auth Validation Attributes

Update validation attributes for auth/profile fields in:

```text
lang/en/validation.php
lang/fr/validation.php
```

Add field labels for:

```text
name
first_name
last_name
email
password
password_confirmation
current_password
new_password
remember
locale
phone
avatar
profile_photo
```

Make sure French validation errors show French field names.

---

# 9. Consultation Validation Attributes

Also add/verify validation attributes for consultation fields:

```text
complaint
complaints
history
history_of_presenting_complaint
physical_examination
diagnosis
diagnoses
investigations
prescriptions
treatment
treatment_plan
follow_up_date
clinical_notes
vitals
temperature
blood_pressure
pulse
respiratory_rate
spo2
weight
height
bmi
```

---

# 10. Do Not Translate These

Do not translate:

* patient names
* doctor names
* staff names
* diagnosis text typed by clinicians
* clinical notes typed by clinicians
* medicine/product names from database
* service names from database unless they are system-defined hardcoded labels
* lab test names from database unless hardcoded
* supplier names
* insurance provider names
* sponsor names
* permission names
* route names
* database column names
* internal enum values
* CSS classes
* JS selectors
* data attributes
* clinical units: mmHg, bpm, kg, cm, °C, %, SpO2
* currency symbols
* UHMS acronym

---

# 11. Permissions Must Stay Intact

While translating, do not weaken security.

Preserve:

* `@can`
* `@cannot`
* `Gate`
* policies
* middleware
* role checks
* permission checks
* financial visibility checks
* stock-cost visibility checks
* clinical confidentiality checks

Do not expose restricted clinical, financial, accounting, insurance, sponsor, or stock-cost data.

---

# 12. Scanner And Burn-Down

Run the localisation scanner after changes:

```bash
php scripts/localisation-audit.php
```

or if available:

```bash
php artisan uhms:localisation-audit
```

Compare before/after active runtime candidates.

The goal of this phase is to burn down the remaining 519 active runtime candidates, starting with consultation and auth.

Document:

* before count
* after count
* files fixed
* keys added
* files deferred
* reason for each deferred file

---

# 13. Language File Parity

After all changes, verify recursive EN/FR parity.

Every key added in English must exist in French.
Every key added in French must exist in English.

Run PHP lint:

```bash
for f in lang/en/*.php lang/fr/*.php; do php -l "$f"; done
```

---

# 14. Required Manual Verification

Manually switch to French and verify:

## Auth

* login
* forgot password
* reset password
* profile
* account/settings
* logout menu/session messages

## Consultations

* consultation list/history
* consultation show page
* new consultation flow if present
* complaints
* history
* examination
* diagnosis
* investigations
* prescriptions
* treatment
* follow-up
* modals
* JavaScript buttons/messages
* validation errors

## Remaining Modules

Verify every file fixed from the active runtime list.

Do not declare complete based only on automated checks.

---

# 15. Required Documentation

Create:

```text
docs/LOCALISATION_PHASE_15B_CRITICAL_RUNTIME_COMPLETION_REPORT.md
```

Include:

* summary
* manually confirmed problem pages
* active runtime candidate count before
* active runtime candidate count after
* consultation pages fixed
* auth pages fixed
* other modules fixed
* files changed
* language files changed
* keys added
* dynamic labels converted
* validation attributes added
* scanner result
* parity result
* manual French verification checklist
* remaining candidates, if any
* reason for every deferred candidate
* recommendation for next phase

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

If project tests are stable, run relevant localisation or feature tests.
Do not run the full test suite yet if there are still active runtime localisation candidates.

---

# 17. Acceptance Criteria

This phase is complete only when:

* consultation pages no longer show English in French mode
* auth pages no longer show English in French mode
* active runtime candidate count is reduced from the current 519
* all high-priority route-linked clinical pages are translated or documented
* all added language keys have EN/FR parity
* validation attributes for auth and consultations are translated
* inline JS strings for fixed pages are translated
* dynamic visible labels are translated where safe
* permissions are unchanged
* no new localisation framework is introduced
* no business logic is moved into Blade
* documentation report is created
* manual French verification confirms the fixed pages

Proceed with UHMS Localisation Phase 15B now.
