You are working on UHMS — Ultimate Hospital Management System.

Important:
There is currently no docs/UHMS_IMPLEMENTATION_SKILL.md file in this project.
Do not try to read it.
Follow the instructions in this prompt directly.

We completed UHMS Localisation Phase 12 — Active Pages Translation Batch 5.

Phase 12 completed a high-value pass across:

* billing invoices
* payments
* receipts
* statements
* aging print/PDF surfaces
* claims list/create/detail/review enum and form-label cleanup
* insurance provider enum labels and verification driver labels
* cash/account entry list/create dynamic labels
* queue and ward/bed management enum labels

Phase 12 verification passed:

* route list: 714 routes
* view cache passed
* language file lint passed
* EN/FR nested parity passed with PARITY_OK
* localisation audit passed

Audit result after Phase 12:

* files scanned: 1268
* files with candidates: 518
* hardcoded candidates: 18,751

Phase 12 deferred these active residual areas:

* deeper claim show/review visible copy
* remaining insurance provider form/help text
* cashier handover full-page copy
* accounts daily collection/reconciliation remaining table and modal literals
* accounting report pages, journals, fiscal years, periods, accounts payable, and accounting settings
* billing Inertia pages for sponsors, credit notes, dashboard, statements, aging, and discounts
* reports hub and operational/report print pages
* settings/users/roles/departments/modules pages
* wider shared layout/component audit allowlist cleanup

Now proceed with:

# UHMS Localisation Phase 13 — Active Pages Translation Batch 6

## Goal

Complete the next active localisation cleanup batch for the residual financial, reporting, accounting, admin, settings, and shared UI surfaces deferred from Phase 12.

This batch focuses on:

1. Claims deeper show/review copy
2. Insurance provider forms/help text
3. Cashier handover and cash office pages
4. Accounts daily collection/reconciliation remaining literals
5. Accounting journals, reports, periods, fiscal years, AP, settings
6. Billing Inertia pages
7. Reports hub and operational/report print pages
8. Settings, users, roles, departments, modules
9. Shared layouts/components and audit allowlist cleanup

Do not translate demo/template/sample pages.
Do not translate backup-route-only pages.
Do not translate files only referenced by `routes/web.php.bak`.
Do not change billing logic.
Do not change accounting logic.
Do not change claims logic.
Do not change payment logic.
Do not change permissions.
Do not expose restricted financial, clinical, stock-cost, payroll, or accounting data.
Do not create a new localisation system.
Do not introduce new packages.
Do not introduce Tailwind.

---

# 1. Source Reports

Use these reports:

```text id="dsqosz"
docs/LOCALISATION_PHASE_7_COMPLETE_ACTIVE_PAGE_TRANSLATION_REPORT.md
docs/LOCALISATION_PHASE_8_ACTIVE_PAGES_BATCH_1_REPORT.md
docs/LOCALISATION_PHASE_9_ACTIVE_PAGES_BATCH_2_REPORT.md
docs/LOCALISATION_PHASE_11_ACTIVE_PAGES_BATCH_4_REPORT.md
docs/LOCALISATION_PHASE_12_ACTIVE_PAGES_BATCH_5_REPORT.md
docs/LOCALISATION_COVERAGE_AUDIT_REPORT.md
```

Continue from the Phase 12 deferred list.

Do not restart localisation from scratch.

---

# 2. Target Area A — Claims / Insurance Deeper Copy

Check active route-linked views and partials:

```text id="6hy2nn"
resources/views/claims/
resources/views/insurance/
resources/views/billing/
```

Translate remaining visible copy in:

* claim show page
* claim review page
* claim status panels
* claim reconciliation sections
* claim submission/appeal/payment panels
* claim item details
* insurance provider create/edit/show forms
* verification provider help text
* verification config labels
* insurance coverage labels
* insurance tier labels
* action buttons
* modal labels
* empty states
* inline JavaScript strings

Use or extend:

```text id="xkht2v"
lang/en/claims.php
lang/fr/claims.php
lang/en/billing.php
lang/fr/billing.php
lang/en/common.php
lang/fr/common.php
lang/en/statuses.php
lang/fr/statuses.php
lang/en/messages.php
lang/fr/messages.php
```

Rules:

* NHIS is just one insurance provider.
* Do not create hardcoded NHIS-only strings.
* Do not hardcode sponsors.
* Do not hardcode insurance providers.
* Do not change claims workflow.
* Do not change claim submission/review/payment logic.
* Do not expose financial values without permission.

---

# 3. Target Area B — Cashier / Cash Office / Daily Collection / Reconciliation

Check active route-linked views:

```text id="r0oec0"
resources/views/accounts/
resources/views/cashier/
resources/views/billing/
resources/views/payments/
```

Translate remaining visible copy in:

* cashier handover pages
* cashier shift pages
* daily collection page
* reconciliation page
* payment reconciliation modals
* cash/account entry tables
* approval panels
* collection summaries
* deposit/bank reconciliation labels
* filters
* table headers
* modal labels
* action buttons
* print/export labels
* inline JavaScript strings

Use or extend:

```text id="c3efva"
lang/en/accounting.php
lang/fr/accounting.php
lang/en/payments.php
lang/fr/payments.php
lang/en/billing.php
lang/fr/billing.php
lang/en/common.php
lang/fr/common.php
lang/en/statuses.php
lang/fr/statuses.php
lang/en/messages.php
lang/fr/messages.php
```

Rules:

* Payment is not revenue.
* Revenue comes from invoice/billing posting.
* Do not change reconciliation logic.
* Do not change cashier logic.
* Do not expose restricted financial values without permission.
* Do not calculate financial totals in Blade.

---

# 4. Target Area C — Accounting Reports / Journals / Periods / AP / Settings

Check active route-linked views and frontend files:

```text id="yuylr8"
resources/views/accounting/
resources/views/accounts/
resources/views/accounting/reports/
resources/views/reports/
resources/js/Pages/Accounting/
```

Translate remaining visible copy in:

* chart of accounts
* account categories
* journal entry index/create/show
* journal posting/reversal screens
* trial balance
* general ledger
* profit and loss
* balance sheet
* cashbook
* accounts payable pages
* AP aging pages
* fiscal years
* accounting periods
* accounting settings
* failed postings
* report filters
* table headers
* print/export labels
* modal labels
* empty states
* inline JavaScript strings

Use or extend:

```text id="3o4qt1"
lang/en/accounting.php
lang/fr/accounting.php
lang/en/reports.php
lang/fr/reports.php
lang/en/common.php
lang/fr/common.php
lang/en/statuses.php
lang/fr/statuses.php
lang/en/messages.php
lang/fr/messages.php
```

Rules:

* Official accounting reports must use posted journal entries only.
* Do not change accounting calculations.
* Do not change posting/reversal logic.
* Do not expose restricted accounting data without permission.
* Do not calculate reports in Blade.

---

# 5. Target Area D — Billing Inertia / Frontend Pages

Check active frontend/Inertia files:

```text id="cdzslt"
resources/js/Pages/Billing/
resources/js/Pages/Invoices/
resources/js/Pages/Payments/
resources/js/Pages/Sponsors/
resources/js/Pages/CreditNotes/
resources/js/Pages/Statements/
resources/js/Pages/Aging/
resources/js/Pages/Discounts/
resources/js/pages/
resources/js/components/
resources/js/Components/
```

Translate visible strings in active frontend pages for:

* billing dashboard
* sponsors
* credit notes
* statements
* aging
* discounts
* invoice/payment frontend widgets
* dashboard filters
* chart labels
* table headers
* action buttons
* modals
* empty states
* alert/confirm/loading strings

Use existing localisation approach:

```text id="h7vpp3"
window.UHMS_I18N
useTrans()
module-level Blade i18n bridge
```

Do not introduce a new frontend i18n package.

If a frontend string cannot be safely localised yet, document it clearly with file path and reason.

---

# 6. Target Area E — Reports Hub / Operational Reports / Print Templates

Check active report views and print templates:

```text id="cuvy5q"
resources/views/reports/
resources/views/accounting/reports/
resources/views/billing/reports/
resources/views/claims/
resources/views/stock/reports/
resources/views/hr/reports/
```

Translate remaining visible copy in:

* reports hub
* report cards
* management reports
* operational reports
* clinical reports
* billing reports
* claims reports
* accounting reports
* stock reports
* HR/payroll reports
* audit reports
* filter labels
* chart labels
* KPI labels
* table headers
* empty states
* print templates
* PDF templates
* export labels
* generated by/generated at labels
* permission warning labels
* redaction labels
* inline JavaScript strings

Use or extend:

```text id="bn033e"
lang/en/reports.php
lang/fr/reports.php
lang/en/common.php
lang/fr/common.php
lang/en/statuses.php
lang/fr/statuses.php
```

Rules:

* Reports must respect permissions.
* Export and print must respect permissions.
* Restricted columns must remain redacted.
* Do not introduce a new chart/export package.
* Do not duplicate report logic in Blade.

---

# 7. Target Area F — Settings / Users / Roles / Departments / Modules

Check active route-linked views:

```text id="i7tk54"
resources/views/settings/
resources/views/users/
resources/views/roles/
resources/views/admin/
resources/views/departments/
resources/views/modules/
```

Translate remaining visible copy in:

* organization settings
* invoice settings
* ward settings
* payment method settings
* notification preferences
* profile settings
* activity log settings
* log retention
* users index/create/edit/show
* roles and permissions
* departments
* modules
* service catalog admin leftovers
* table headers
* form labels
* help text
* modal labels
* action buttons
* empty states
* inline JavaScript strings

Use or extend:

```text id="g00fgf"
lang/en/settings.php
lang/fr/settings.php
lang/en/users.php
lang/fr/users.php
lang/en/roles.php
lang/fr/roles.php
lang/en/common.php
lang/fr/common.php
lang/en/messages.php
lang/fr/messages.php
```

Rules:

* Do not change permission logic.
* Do not change module enable/disable logic.
* Do not expose restricted controls to unauthorized users.

---

# 8. Target Area G — Shared Layouts / Components / Partials

Check active shared files:

```text id="e070xy"
resources/views/components/
resources/views/partials/
resources/views/layouts/
resources/views/layout/
```

Translate remaining active shared UI text:

* header
* footer
* breadcrumb labels
* global modals
* global alerts
* action dropdowns
* status components
* empty-state components
* pagination wrappers if custom
* shared buttons
* sidebar if any active label bypasses translation

Be careful:

* `SidebarMenuBuilder.php` may store raw labels but translate them downstream.
* If already translated through `translateLabel()`, document as false positive.
* Do not translate demo/template components unless active.
* Do not break slots, props, or reusable components.

---

# 9. Target Area H — Service Event Titles / Messages Final Classification

Review remaining service strings in:

```text id="irpfwx"
app/Services/
```

Classify each audit finding as:

```text id="ba4cye"
A. User-facing timeline/notification/report/API label — translate now
B. Internal audit/event code — do not translate
C. Stored canonical event title — risky, document
D. SQL/internal expression — false positive
E. Already translated downstream — false positive
```

Translate high-confidence user-facing strings only.

Examples likely needing review:

* admission billing event titles
* emergency billing event titles
* blood bank workflow titles
* lab workflow titles
* pharmacy billing titles
* procedure workflow titles
* queue service titles
* journal reversal descriptions
* service rendering titles

Use named placeholders for dynamic values.

Do not change stored values, accounting semantics, workflow status, or audit semantics.

---

# 10. Localisation Audit Allowlist Improvement

Improve the audit scanner/report to separate real active debt from noise.

Add or refine allowlist rules for:

```text id="o85iyq"
demo/template views
routes/web.php.bak-only views
language files themselves
SidebarMenuBuilder raw labels translated downstream
SQL expressions
CSS classes
JS selectors
known format examples
UHMS brand strings
N/A fallback
clinical units
currency symbols
blood group codes
commented-out code
backup files
```

The report should include separate counts for:

```text id="nk8reu"
active runtime candidates
demo/template candidates
backup-only candidates
language-file candidates
known false positives
service-title manual-review candidates
```

Do not hide real active runtime strings.

---

# 11. Dynamic Labels

Search target files for:

```php id="ea93kg"
->label()
->statusLabel()
->typeLabel()
getLabelAttribute()
displayName()
humanName()
ucfirst(
ucwords(
str_replace('_', ' ',
```

Where displayed to users and safe, replace with translation helpers or explicit lang keys.

Do not change stored enum values.
Do not change enum constants.
Do not change workflow/status transitions.

---

# 12. Responsive Cleanup While Translating

Fix obvious responsive issues while touching these pages:

```text id="c87j9t"
table overflow
filter wrapping
action button overflow
modal sizing
tab overflow
long French labels breaking layout
billing/accounting/report tables overflowing on mobile
```

Use Bootstrap 5 utilities only.
Do not introduce Tailwind.

---

# 13. Verification

Run:

```bash id="we11nc"
php artisan view:clear
php artisan config:clear
php artisan cache:clear
php artisan route:list
php artisan view:cache
php artisan view:clear
```

Run language lint:

```bash id="2tjepb"
for f in lang/en/*.php lang/fr/*.php; do php -l "$f"; done
```

Run nested EN/FR parity verification.

Run localisation audit again:

```bash id="mrg69d"
php scripts/localisation-audit.php
```

The raw candidate count may remain high if demo/template files are still included, but the new audit report must separate noise from active runtime candidates.

---

# 14. Documentation

Create:

```text id="eytir3"
docs/LOCALISATION_PHASE_13_ACTIVE_PAGES_BATCH_6_REPORT.md
```

Include:

* active routes/views checked in this batch
* claims/insurance files translated
* cashier/accounts/reconciliation files translated
* accounting reports/journals/AP/settings files translated
* billing Inertia/frontend files translated or deferred
* reports/print/export files translated
* settings/users/roles/departments/modules files translated
* shared component/layout files translated
* service event strings translated/classified
* audit allowlist changes
* language files added/updated
* JavaScript/frontend strings translated
* dynamic labels updated
* responsive fixes made
* EN/FR parity result
* PHP lint result
* cache/route/view-cache verification result
* localisation audit result with separated active-vs-noise counts
* remaining active untranslated pages

---

# 15. Acceptance Criteria

This phase is complete when:

* deeper claims/insurance pages are checked and translated or documented
* cashier/accounts/reconciliation pages are checked and translated or documented
* accounting reports/journals/AP/settings pages are checked and translated or documented
* billing Inertia/frontend pages are checked and translated or documented
* reports hub/operational/print/export pages are checked and translated or documented
* settings/users/roles/departments/modules pages are checked and translated or documented
* active shared components/layouts are checked and translated or documented
* service event titles/messages are translated or classified
* audit allowlist separates active runtime debt from false positives/noise
* EN/FR parity remains clean
* touched files pass lint
* caches clear
* route list works
* view cache works
* no business logic changed
* no workflows changed
* no duplicate localisation system created
* no new packages introduced

Proceed with UHMS Localisation Phase 13 — Active Pages Translation Batch 6 now.
