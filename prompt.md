You are working on UHMS — Ultimate Hospital Management System.

Important:
There is currently no docs/UHMS_IMPLEMENTATION_SKILL.md file in this project.
Do not try to read it.
Follow the instructions in this prompt directly.

We completed UHMS Localisation Phase 9 — Active Pages Translation Batch 2.

Phase 9 completed:

* consultations index and enum label cleanup in consultation show/history
* theatre index and enum label cleanup in theatre show/report/calendar/rooms/schedule-form
* lab/investigation enum label cleanup
* medication emergency board and reports
* blood-bank donors page
* EN/FR parity remained clean
* view cache passed
* audit candidates reduced to 19,337

Phase 9 deferred deeper localisation for:

* large consultation detail workflows and inline JavaScript strings in `resources/views/consultations/show.blade.php`
* theatre show/report/calendar and consumables pages beyond enum/status conversion
* lab results/process/print/modal pages beyond enum/result-type conversion
* medication admission board, admission show, MAR chart, and MAR partial content beyond reports/emergency board
* blood bank dashboard, units, storage, requests, donor profile, donations, donation view, and reports beyond donor registry

Now proceed with:

# UHMS Localisation Phase 10 — Active Pages Translation Batch 3

## Goal

Continue active runtime page localisation by completing the deeper clinical/procedure pages deferred from Phase 9.

This phase focuses on:

1. Consultation detail workflows
2. Theatre/procedure detail, calendar, reports, consumables
3. Lab results/process/print/modal deeper text
4. Medication administration admission/MAR screens
5. Blood bank remaining active pages

Do not translate demo/template/sample pages.
Do not translate backup-route-only pages.
Do not translate files only referenced by `routes/web.php.bak`.
Do not change workflows.
Do not change business logic.
Do not create a new localisation system.
Do not introduce a new package.

---

# 1. Source Reports

Use:

```text
docs/LOCALISATION_PHASE_9_ACTIVE_PAGES_BATCH_2_REPORT.md
docs/LOCALISATION_PHASE_8_ACTIVE_PAGES_BATCH_1_REPORT.md
docs/LOCALISATION_PHASE_7_COMPLETE_ACTIVE_PAGE_TRANSLATION_REPORT.md
docs/LOCALISATION_COVERAGE_AUDIT_REPORT.md
```

Continue from the Phase 9 deferred list.

---

# 2. Target Area A — Consultation Detail Workflows

Prioritise:

```text
resources/views/consultations/show.blade.php
resources/views/consultations/history.blade.php
resources/views/consultations/partials/
resources/views/doctor/
```

depending on actual active route/controller usage.

Translate visible UI text in:

* consultation tabs
* patient context panels
* visit context panels
* complaints/history sections
* examination sections
* diagnosis panels
* investigation panels
* prescription panels
* procedure request panels
* treatment plan sections
* follow-up sections
* clinical task sections
* modal titles
* modal body labels
* modal buttons
* table headers
* action buttons
* dropdown actions
* alert messages
* empty states
* inline JavaScript UI strings
* AJAX feedback text

Use or extend:

```text
lang/en/consultations.php
lang/fr/consultations.php
lang/en/messages.php
lang/fr/messages.php
lang/en/common.php
lang/fr/common.php
lang/en/statuses.php
lang/fr/statuses.php
lang/en/lab.php
lang/fr/lab.php
lang/en/theatre.php
lang/fr/theatre.php
```

Do not translate:

* clinical free text
* diagnosis notes
* doctor notes
* patient-entered text
* drug names
* service names entered by users unless system-defined
* investigation/test names entered by users

Do not change clinical workflow logic.

---

# 3. Target Area B — Theatre / Procedure Detail Pages

Translate deeper active theatre pages:

```text
resources/views/theatre/show.blade.php
resources/views/theatre/report.blade.php
resources/views/theatre/calendar.blade.php
resources/views/theatre/consumables/
resources/views/theatre/partials/
resources/views/theatre/rooms/
resources/views/admin/procedures/
resources/views/procedures/
```

depending on actual active route/controller usage.

Translate visible:

* show page labels
* workflow timeline labels
* patient/visit/procedure context labels
* schedule form labels
* calendar controls and event labels
* theatre room labels
* report filters
* report table headers
* procedure consumables labels
* procedure billing labels
* pre-op labels
* anaesthesia labels
* surgery labels
* operative note labels
* post-op labels
* cancellation/rejection labels
* modal text
* action buttons
* empty states
* inline JavaScript UI text

Use or extend:

```text
lang/en/theatre.php
lang/fr/theatre.php
lang/en/procedures.php
lang/fr/procedures.php
lang/en/reports.php
lang/fr/reports.php
lang/en/common.php
lang/fr/common.php
lang/en/statuses.php
lang/fr/statuses.php
```

Rules:

* procedures are billable services
* consumables are stock products
* do not hardcode procedure services
* do not change procedure workflow logic
* do not change status transitions

---

# 4. Target Area C — Lab / Investigation Detail Pages

Translate deeper active lab/investigation pages:

```text
resources/views/lab/results.blade.php
resources/views/lab/process.blade.php
resources/views/lab/print.blade.php
resources/views/lab/_result_modal.blade.php
resources/views/lab/_criterion_input.blade.php
resources/views/lab/_consumables.blade.php
resources/views/lab/requests.blade.php
resources/views/investigations/
resources/views/admin/lab/
resources/views/admin/radiology/
```

depending on actual active route/controller usage.

Translate visible:

* request labels
* process labels
* result labels
* verification labels
* rejection labels
* print labels
* modal titles
* modal forms
* criterion labels
* result type labels
* specimen labels
* reference range labels
* consumables labels
* table headers
* filter labels
* action buttons
* empty states
* inline JavaScript UI text

Use or extend:

```text
lang/en/lab.php
lang/fr/lab.php
lang/en/investigations.php
lang/fr/investigations.php
lang/en/radiology.php
lang/fr/radiology.php
lang/en/reports.php
lang/fr/reports.php
lang/en/common.php
lang/fr/common.php
lang/en/statuses.php
lang/fr/statuses.php
```

Do not translate:

* test names entered by users
* result values
* clinical notes
* reference range numeric values
* specimen IDs
* analyzer codes

Do not change lab workflow logic.

---

# 5. Target Area D — Medication Administration / MAR

Translate deeper medication administration pages:

```text
resources/views/medication-administration/admission-board.blade.php
resources/views/medication-administration/admission-show.blade.php
resources/views/medication-administration/mar-chart.blade.php
resources/views/medication-administration/partials/
resources/views/medication-administration/reports.blade.php
resources/views/medication-administration/emergency-board.blade.php
```

Translate visible:

* MAR chart labels
* ward/admission medication labels
* emergency medication labels
* schedule labels
* dose/route/frequency labels
* PRN/SOS labels
* overdue labels
* administered/held/refused/missed labels
* table headers
* filters
* modal titles
* modal form labels
* action buttons
* empty states
* inline JavaScript UI text

Use or extend:

```text
lang/en/medication_administration.php
lang/fr/medication_administration.php
lang/en/statuses.php
lang/fr/statuses.php
lang/en/common.php
lang/fr/common.php
lang/en/messages.php
lang/fr/messages.php
```

Do not translate:

* drug names
* dose values
* clinical notes
* patient/staff names

Do not change MAR workflow logic.

---

# 6. Target Area E — Blood Bank Remaining Pages

Translate remaining active blood-bank pages:

```text
resources/views/blood-bank/dashboard.blade.php
resources/views/blood-bank/units.blade.php
resources/views/blood-bank/storage.blade.php
resources/views/blood-bank/requests.blade.php
resources/views/blood-bank/donor-profile.blade.php
resources/views/blood-bank/donations.blade.php
resources/views/blood-bank/donation-view.blade.php
resources/views/blood-bank/reports.blade.php
resources/views/blood-bank/partials/
```

Translate visible:

* dashboard labels not already done
* unit labels
* storage labels
* request labels
* donor profile labels
* donation labels
* donation detail labels
* crossmatch labels
* issue/release labels
* transfusion reaction labels
* report filters
* table headers
* modal labels
* action buttons
* empty states
* inline JavaScript UI text

Use or extend:

```text
lang/en/blood_bank.php
lang/fr/blood_bank.php
lang/en/statuses.php
lang/fr/statuses.php
lang/en/messages.php
lang/fr/messages.php
lang/en/common.php
lang/fr/common.php
```

Do not translate:

* blood group codes such as A+, B-, O+
* donor names
* patient names
* unit numbers
* clinical notes

Do not change blood bank workflow logic.

---

# 7. Dynamic Labels

Search target files for:

```php
->label()
->statusLabel()
->typeLabel()
getLabelAttribute()
displayName()
humanName()
```

Where displayed to users and safe, replace with:

```php
->translatedLabel()
```

or an existing shared component:

```blade
<x-status-badge>
```

Do not change stored enum values.
Do not change enum constants.
Do not change workflow/status transition logic.

---

# 8. JavaScript / Frontend Strings

Translate visible JavaScript UI strings in targeted Blade/frontend files:

* alerts
* confirmations
* loading text
* empty messages
* Select2 placeholders
* AJAX success/error messages
* dynamic row labels
* calendar labels
* chart labels

Use existing `window.UHMS_I18N`, `useTrans()`, or module-level Blade i18n bridge.

Do not introduce a new frontend localisation package.
Do not expose sensitive data to JavaScript.

---

# 9. Responsive Cleanup While Translating

Fix obvious responsive issues while touching these pages:

* table overflow
* filters wrapping badly
* action button overflow
* modal sizing
* tab overflow
* long French labels breaking layout

Use Bootstrap 5 utilities only.
Do not introduce Tailwind.

---

# 10. Verification

Run:

```bash
php artisan view:clear
php artisan config:clear
php artisan cache:clear
php artisan route:list
php artisan view:cache
php artisan view:clear
```

Run language lint:

```bash
for f in lang/en/*.php lang/fr/*.php; do php -l "$f"; done
```

Run nested EN/FR parity verification.

Run localisation audit again:

```bash
php scripts/localisation-audit.php
```

The raw candidate count may remain high because demo/template files are still included.

But the report must specifically state:

* consultation detail pages cleaned
* theatre/procedure detail pages cleaned
* lab/investigation detail pages cleaned
* medication administration/MAR pages cleaned
* blood bank remaining pages cleaned
* remaining active pages after this batch

---

# 11. Documentation

Create:

```text
docs/LOCALISATION_PHASE_10_ACTIVE_PAGES_BATCH_3_REPORT.md
```

Include:

* active routes/views checked in this batch
* consultation files translated
* theatre/procedure files translated
* lab/investigation files translated
* medication administration/MAR files translated
* blood-bank files translated
* language files added/updated
* JavaScript strings translated
* dynamic labels updated
* responsive fixes made
* EN/FR parity result
* PHP lint result
* cache/route/view-cache verification result
* localisation audit result
* remaining active untranslated pages

---

# 12. Acceptance Criteria

This phase is complete when:

* consultation detail active pages are checked and translated or documented
* theatre/procedure detail active pages are checked and translated or documented
* lab/investigation detail active pages are checked and translated or documented
* medication administration/MAR active pages are checked and translated or documented
* blood-bank remaining active pages are checked and translated or documented
* active frontend strings in these areas are checked and translated or documented
* EN/FR parity remains clean
* touched files pass lint
* caches clear
* route list works
* view cache works
* no business logic changed
* no workflows changed
* no duplicate localisation system created
* no new packages introduced

Proceed with UHMS Localisation Phase 10 — Active Pages Translation Batch 3 now.
