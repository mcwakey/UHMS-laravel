You are working on UHMS — Ultimate Hospital Management System.

Important:
There is currently no docs/UHMS_IMPLEMENTATION_SKILL.md file in this project.
Do not try to read it.
Follow the instructions in this prompt directly.

We ran a full Localisation Coverage Audit.

Audit results:

* 1,248 files scanned
* 551 files with possible hardcoded strings
* 19,758 hardcoded candidates found
* Many candidates are false positives from demo/template/sample pages, comments, CSS/JS selectors, or internal strings
* But real remaining user-facing strings still exist in controllers, services, consultations, theatre, blood bank, medication administration, reports/accounting labels, and some live Blade pages

Now proceed with:

# UHMS Localisation Coverage Cleanup — Real Pages Only

## Goal

Clean up the real remaining untranslated UHMS user-facing strings found by the localisation coverage audit.

This phase must separate real application pages from template/demo/sample pages.

Do not blindly translate every candidate.
Do not translate vendor/template demo pages unless they are actually used by UHMS routes.
Do not waste time on commented-out code.
Do not translate CSS classes, JS selectors, route names, permission names, config keys, or internal codes.
Do not claim 100% until real application files are clean.

---

# 1. Start From The Audit Report

Open and use:

```text
docs/LOCALISATION_COVERAGE_AUDIT_REPORT.md
```

Use it as the source list for cleanup.

First classify findings into:

1. Real UHMS application files — fix now
2. Template/demo/sample files — ignore or move to TODO
3. False positives — document and ignore
4. Internal/non-visible strings — ignore
5. Risky strings needing manual review — document

---

# 2. High Priority Real Application Areas

Prioritise these real UHMS areas first:

```text
resources/views/consultations/
resources/views/theatre/
resources/views/blood-bank/
resources/views/medication-administration/
resources/views/hr/
resources/views/store/
resources/views/admin/
resources/views/layout/partials/sidebar.blade.php
app/Http/Controllers/
app/Services/
app/Models/
app/Enums/
app/Helpers/
```

Also review:

```text
resources/views/emails/
resources/views/mail/
resources/js/
public/js/
```

Do not prioritise obvious template/demo files such as:

```text
resources/views/widgets.blade.php
resources/views/ui-dropdowns.blade.php
resources/views/tables-basic.blade.php
resources/views/ui-modals.blade.php
resources/views/social-feed.blade.php
resources/views/form-select2.blade.php
resources/views/layout-dark.blade.php
resources/views/layout-full-width.blade.php
resources/views/layout-hidden.blade.php
resources/views/layout-hover-view.blade.php
resources/views/layout-mini.blade.php
resources/views/layout-rtl.blade.php
```

Unless any of those are actively linked by UHMS routes/menu and used in production.

---

# 3. Controllers Cleanup

Fix real user-facing hardcoded messages in controllers.

Search for:

```php
->with('success',
->with('error',
->with('warning',
->with('info',
session()->flash(
return response()->json(['message' =>
'description' => '...
'title' => '...
'label' => '...
```

Examples from the audit include:

* Appointment check-in success message
* Medication administration recorded messages
* Patient insurance added/updated messages
* Service catalog validation and price entry removed messages
* Visit failure message
* Billing report payer/status labels
* Consultation prescription/procedure JSON messages
* Activity log export description

Use existing `lang/en/messages.php` and `lang/fr/messages.php` where appropriate.

If a message belongs to a module, place it cleanly:

```php
__('messages.appointments.checked_in_visit_created')
__('messages.medication_administration.recorded')
__('messages.patient_insurance.added')
__('messages.service_catalog.price_entry_removed')
__('messages.visits.create_failed')
```

Use named placeholders for dynamic values.

Do not change controller business logic.

---

# 4. Services Cleanup

Fix real user-facing labels/messages in services where those strings are displayed in UI, logs, notifications, timeline entries, reports, task titles, or API responses.

Examples from the audit include:

* ARAgingService / APAgingService labels
* FinancialReportService labels
* PatientMergePreviewService labels
* ProcedureReportService stage labels
* ProcedureWorkflowService titles
* ProcedureRequestService titles
* ProcedureScheduleService titles
* LabService titles
* InvestigationRequestService titles
* EmergencyCaseService titles
* EmergencyBedBillingService titles
* AdmissionService titles
* AdmissionBedBillingService titles
* BloodDonationService titles
* BloodIssueService titles
* BloodCrossmatchService titles
* QueueService titles
* PharmacyService titles
* ServiceRenderingService titles
* JournalEntryService reversal labels

Use clean language keys.

Preferred files:

```text
lang/en/messages.php
lang/fr/messages.php
lang/en/statuses.php
lang/fr/statuses.php
lang/en/reports.php
lang/fr/reports.php
lang/en/common.php
lang/fr/common.php
```

Create a new module file only if necessary, for example:

```text
lang/en/clinical.php
lang/fr/clinical.php
lang/en/theatre.php
lang/fr/theatre.php
lang/en/blood_bank.php
lang/fr/blood_bank.php
```

Maintain EN/FR parity.

Do not translate internal database values.
Do not change stored enum values.
Do not change accounting logic.
Do not change workflow logic.
Do not bypass ActivityLogService.

---

# 5. Sidebar/Menu Cleanup

The audit flagged `app/Services/SidebarMenuBuilder.php`.

This service may already have a `translateLabel()` hook.

Do not blindly wrap every menu label with `__()` if the builder already translates labels later.

Instead:

1. Inspect the menu builder.
2. If labels are intentionally raw and translated by `translateLabel()`, document as false positive.
3. If some labels are not passing through translation, fix those.
4. Ignore commented-out menu blocks unless they are active.

Ensure active menu labels are translated through:

```php
__('menu.key')
```

or the existing `translateLabel()` mechanism.

Do not break permissions or module visibility.

---

# 6. Real Blade Views Cleanup

Translate remaining real Blade files.

Focus on:

```text
resources/views/consultations/
resources/views/theatre/
resources/views/blood-bank/
resources/views/medication-administration/
resources/views/hr/
resources/views/store/
resources/views/admin/
resources/views/emails/
resources/views/mail/
```

Translate:

* headings
* buttons
* tabs
* modal labels
* form labels
* placeholders
* table headers
* empty states
* action labels
* alert text
* print labels
* email labels

Do not translate:

* clinical notes
* diagnosis text
* product names
* service names entered by users
* patient/staff names
* supplier names
* route names
* permission names
* internal codes

---

# 7. Template/Demo Files

For obvious template/demo files, do not translate unless used in production.

Create a section in the cleanup report listing ignored template/demo files, for example:

```text
resources/views/widgets.blade.php
resources/views/ui-dropdowns.blade.php
resources/views/tables-basic.blade.php
resources/views/ui-modals.blade.php
resources/views/social-feed.blade.php
resources/views/form-select2.blade.php
resources/views/layout-dark.blade.php
resources/views/layout-full-width.blade.php
resources/views/layout-hidden.blade.php
resources/views/layout-hover-view.blade.php
resources/views/layout-mini.blade.php
resources/views/layout-rtl.blade.php
```

For each ignored file, state:

* why it was ignored
* whether it is linked by any route/menu
* whether it should be deleted later, archived, or left as template reference

Do not delete files in this phase unless clearly safe.

---

# 8. Translation Key Rules

Add EN and FR keys together.

Use existing files when practical:

```text
lang/en/common.php
lang/fr/common.php
lang/en/messages.php
lang/fr/messages.php
lang/en/menu.php
lang/fr/menu.php
lang/en/statuses.php
lang/fr/statuses.php
lang/en/reports.php
lang/fr/reports.php
lang/en/lab.php
lang/fr/lab.php
lang/en/emergency.php
lang/fr/emergency.php
lang/en/admissions.php
lang/fr/admissions.php
lang/en/billing.php
lang/fr/billing.php
lang/en/payments.php
lang/fr/payments.php
lang/en/invoices.php
lang/fr/invoices.php
lang/en/stock.php
lang/fr/stock.php
```

Create new lang files only if the module is active and has enough unique text:

```text
lang/en/theatre.php
lang/fr/theatre.php
lang/en/blood_bank.php
lang/fr/blood_bank.php
lang/en/hr.php
lang/fr/hr.php
lang/en/consultations.php
lang/fr/consultations.php
```

Avoid messy generic keys.
Avoid duplicate keys with the same meaning.

---

# 9. Repeat Audit After Cleanup

After fixes, run the localisation audit again.

The new report should show:

* fewer real application candidates
* template/demo candidates separated
* remaining false positives documented
* no hardcoded controller flash literals
* no obvious untranslated real Blade text in active modules

The total candidate count may still be high because of template/demo files. That is acceptable if real UHMS files are clean and documented.

---

# 10. Verification

Run:

```bash
php artisan view:clear
php artisan config:clear
php artisan cache:clear
php artisan route:list
```

Run PHP syntax checks:

```bash
find app database routes config -name "*.php" -print0 | xargs -0 -n1 php -l
for f in lang/en/*.php lang/fr/*.php; do php -l "$f"; done
```

Run nested EN/FR parity verification.

Required:

* 0 missing EN keys
* 0 missing FR keys
* PHP lint passes
* caches clear

---

# 11. Documentation

Create:

```text
docs/LOCALISATION_COVERAGE_CLEANUP_REPORT.md
```

Include:

* audit summary before cleanup
* files classified as real application files
* files classified as template/demo files
* false positives ignored
* files fixed
* language files changed
* remaining candidates after re-audit
* EN/FR parity result
* PHP lint result
* route/list/cache verification result
* remaining TODOs

---

# 12. Acceptance Criteria

This phase is complete when:

* real active UHMS pages from the audit are cleaned
* real controller flash/API messages are translated
* real service-generated display labels are translated
* menu/sidebar active labels are translated or confirmed handled by `translateLabel()`
* template/demo files are documented separately
* false positives are documented
* EN/FR parity remains clean
* PHP lint passes
* caches clear
* no business logic was changed
* no duplicate localisation system was created
* no Tailwind or new packages introduced

Proceed with UHMS Localisation Coverage Cleanup — Real Pages Only now.
