You are working on UHMS — Ultimate Hospital Management System.

Important:
There is currently no docs/UHMS_IMPLEMENTATION_SKILL.md file in this project.
Do not try to read it.
Follow the instructions in this prompt directly.

We completed UHMS Localisation Phase 8 — Active Pages Translation Batch 1.

Phase 8 Batch 1 completed:

* appointments create/edit/show/calendar/index
* active admin product views and modals
* service-rendering index/show/reports
* visit Inertia index date-range labels
* EN/FR parity remained clean
* route/cache checks passed
* localisation audit candidate count reduced from 19,639 to 19,434

Phase 8 still lists these active untranslated areas:

* Visit Blade create/edit/show/preview deeper JS and partial sweep
* Billing Inertia pages and invoice/payment/statement Blade print flows
* Product pricing explanatory copy and remaining encoded modal title strings
* Admin service catalogue modal internals beyond the index
* Procedure, lab, radiology, and service catalogue rendering-adjacent pages
* Store, stock, suppliers, requisitions, purchase orders, receipts, returns, transfers, adjustments, and valuation pages
* HR attendance, employees, leave, payroll, and related screens
* Theatre index, show, report, calendar, rooms, and consumables pages
* Blood bank pages beyond the dashboard
* Medication administration pages beyond the admission board
* Reports, accounting, claims, wards, triage, queues, settings, and shared workflow components

Now proceed with:

# UHMS Localisation Phase 9 — Active Pages Translation Batch 2

## Goal

Continue active runtime page localisation from the Phase 7 inventory and Phase 8 backlog.

This batch focuses on clinical and procedure-heavy modules:

1. Consultations
2. Theatre / Procedures
3. Lab / Radiology / Procedure catalogues
4. Medication Administration
5. Blood Bank

Do not translate demo/template/sample pages.
Do not translate backup-route-only pages.
Do not translate files only referenced by `routes/web.php.bak`.
Do not guess based only on folder names.
Use active routes, controllers, and the current route/view inventory.

---

# 1. Source Reports

Use these existing reports as context:

```text
docs/LOCALISATION_PHASE_7_COMPLETE_ACTIVE_PAGE_TRANSLATION_REPORT.md
docs/LOCALISATION_PHASE_8_ACTIVE_PAGES_BATCH_1_REPORT.md
docs/LOCALISATION_COVERAGE_AUDIT_REPORT.md
```

Do not restart the project-wide localisation from scratch.

Continue from the active backlog.

---

# 2. Target Area A — Consultations

Check active consultation route-linked views and partials, especially:

```text
resources/views/consultations/
resources/views/doctor/
resources/views/clinical/
```

depending on actual active route/controller usage.

Prioritise:

```text
resources/views/consultations/show.blade.php
```

Translate:

* consultation page headings
* tabs
* patient/visit context labels
* complaints section labels
* history labels
* examination labels
* diagnosis labels
* investigation labels
* prescription labels
* procedure request labels
* treatment plan labels
* follow-up labels
* action buttons
* modal titles
* modal body text
* form labels
* placeholders
* table headers
* empty states
* AJAX messages
* inline JavaScript UI text

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
```

Do not translate:

* clinical free text
* diagnosis notes
* doctor notes
* patient-entered text
* drug names
* investigation names entered by users
* service names entered by users unless system-defined

Do not change consultation workflow logic.

---

# 3. Target Area B — Theatre / Procedures

Check active theatre/procedure route-linked views and partials:

```text
resources/views/theatre/
resources/views/procedures/
resources/views/admin/procedures/
```

depending on actual active route/controller usage.

Translate:

* theatre dashboard/index
* procedure request pages
* procedure show pages
* procedure calendar
* procedure room pages
* procedure consumables pages
* procedure reports
* procedure catalogue pages
* acceptance/rejection labels
* billing labels
* scheduling labels
* pre-op labels
* anaesthesia labels
* surgery labels
* operative note labels
* post-op labels
* completion/cancellation labels
* table headers
* filters
* action buttons
* modal labels
* empty states
* inline JavaScript UI text

Use or extend:

```text
lang/en/theatre.php
lang/fr/theatre.php
lang/en/procedures.php
lang/fr/procedures.php
lang/en/statuses.php
lang/fr/statuses.php
lang/en/messages.php
lang/fr/messages.php
lang/en/common.php
lang/fr/common.php
```

Rules:

* do not change procedure workflow logic
* do not change procedure status transitions
* do not hardcode procedure services
* procedure services remain billable services
* consumables remain stock products

---

# 4. Target Area C — Lab / Radiology / Catalogues

Check active route-linked views and partials for:

```text
resources/views/lab/
resources/views/investigations/
resources/views/radiology/
resources/views/admin/lab/
resources/views/admin/radiology/
resources/views/admin/procedures/
```

Translate:

* lab catalogue pages
* investigation request pages
* lab process/result pages not already fully handled
* radiology catalogue pages
* procedure/lab/radiology service catalogue pages
* filters
* table headers
* create/edit forms
* modal labels
* result labels
* verification labels
* rejection/cancellation labels
* print labels
* AJAX messages
* inline JavaScript UI text

Use or extend:

```text
lang/en/lab.php
lang/fr/lab.php
lang/en/investigations.php
lang/fr/investigations.php
lang/en/radiology.php
lang/fr/radiology.php
lang/en/services.php
lang/fr/services.php
lang/en/statuses.php
lang/fr/statuses.php
lang/en/common.php
lang/fr/common.php
```

Do not translate:

* test names entered by users
* service names entered by users unless system-defined
* clinical result values
* clinical notes
* specimen IDs
* reference range numeric constants

---

# 5. Target Area D — Medication Administration

Continue active medication-administration localisation beyond admission board.

Check:

```text
resources/views/medication-administration/
```

Translate:

* emergency medication board
* MAR chart
* reports
* partials
* modals
* filters
* dose/schedule/route labels
* PRN/SOS labels
* overdue labels
* administered/held/refused/missed labels
* table headers
* action buttons
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
* staff/patient names

Do not change MAR workflow logic.

---

# 6. Target Area E — Blood Bank

Continue active blood-bank localisation beyond dashboard.

Check:

```text
resources/views/blood-bank/
```

Translate:

* donors
* donations
* units
* storage
* requests
* crossmatch
* issue/release
* transfusion reaction
* reports
* forms
* filters
* table headers
* action buttons
* modal labels
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

* blood group codes like A+, B-, O+
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

or an existing shared component such as:

```blade
<x-status-badge>
```

Do not change stored enum values.
Do not change enum constants.
Do not change workflow/status transition logic.

---

# 8. JavaScript / Frontend Strings

Translate visible JavaScript UI strings in targeted Blade and frontend files.

Examples:

* alerts
* confirmations
* loading text
* empty messages
* Select2 placeholders
* AJAX success/error messages
* modal dynamic row labels
* calendar labels
* chart labels

Use the existing `window.UHMS_I18N`, `useTrans()`, or module-level Blade i18n bridge already present in the project.

Do not introduce a new frontend localisation package.
Do not expose sensitive clinical data to JavaScript.

---

# 9. Language File Rules

Add EN and FR keys together.

Use existing files where possible.

Create new paired EN/FR files only if the module is active and needs them.

Maintain nested EN/FR parity.

Avoid vague keys like:

```text
label1
text2
button_new
```

Prefer clear grouped keys.

---

# 10. Responsive Cleanup While Translating

While touching these views, fix obvious responsive issues:

```text
table overflow
filter wrapping
action button overflow
modal sizing
tab overflow
cards not stacking
long French labels breaking layout
```

Use Bootstrap 5 utilities only.
Do not introduce Tailwind.

---

# 11. Verification

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

* consultation pages cleaned
* theatre/procedure pages cleaned
* lab/radiology/catalogue pages cleaned
* medication-administration pages cleaned
* blood-bank pages cleaned
* remaining active pages after this batch

---

# 12. Documentation

Create:

```text
docs/LOCALISATION_PHASE_9_ACTIVE_PAGES_BATCH_2_REPORT.md
```

Include:

* active routes/views checked in this batch
* consultation files translated
* theatre/procedure files translated
* lab/radiology/catalogue files translated
* medication-administration files translated
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

# 13. Acceptance Criteria

This phase is complete when:

* consultation active pages are checked and translated or documented
* theatre/procedure active pages are checked and translated or documented
* lab/radiology/catalogue active pages are checked and translated or documented
* medication-administration active pages are checked and translated or documented
* blood-bank active pages are checked and translated or documented
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

Proceed with UHMS Localisation Phase 9 — Active Pages Translation Batch 2 now.
