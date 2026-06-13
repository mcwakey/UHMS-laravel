You are working on UHMS — Ultimate Hospital Management System.

Important:
There is currently no docs/UHMS_IMPLEMENTATION_SKILL.md file in this project.
Do not try to read it.
Follow the instructions in this prompt directly.

We completed UHMS Localisation Phase 13 — Active Pages Translation Batch 6.

Phase 13 completed:

* deeper claims show/review localisation
* insurance provider setup/help text localisation
* cashier handover, daily collection, and reconciliation localisation
* accounting enum/status label cleanup
* accounting journal/fiscal year/period/payable/trial-balance label cleanup
* localisation audit classifier improvement

Phase 13 audit result:

* files scanned: 1268
* files with candidates: 510
* total candidates: 18,567
* active runtime candidates: 4,340
* demo/template candidates: 208
* backup-only candidates: 0
* language-file candidates: 133
* known false positives: 13,492
* service-title manual-review candidates: 394

Phase 13 remaining backlog:

* Billing Inertia pages
* settings/users/roles/departments/modules
* broader report hubs
* manual service-title review candidates

Now proceed with:

# UHMS Localisation Phase 14 — Final Active Runtime Cleanup Gate

## Goal

Perform the final localisation cleanup before Full Test Suite.

This phase must focus only on:

1. Remaining active runtime candidates from the classified audit
2. Billing Inertia/frontend pages
3. Settings/users/roles/departments/modules
4. Reports hub and remaining report views
5. Manual service-title review candidates
6. Remaining active shared components/layouts
7. Final localisation go/no-go report

Do not restart the localisation project.
Do not translate demo/template/sample pages.
Do not translate known false positives.
Do not translate backup-only files.
Do not translate language files themselves.
Do not hide real active runtime strings.
Do not change business logic.
Do not change workflows.
Do not change permissions.
Do not expose restricted financial, clinical, payroll, stock-cost, or accounting data.
Do not introduce a new localisation system.
Do not introduce new packages.
Do not introduce Tailwind.

---

# 1. Source Reports

Use these reports:

```text
docs/LOCALISATION_PHASE_7_COMPLETE_ACTIVE_PAGE_TRANSLATION_REPORT.md
docs/LOCALISATION_PHASE_8_ACTIVE_PAGES_BATCH_1_REPORT.md
docs/LOCALISATION_PHASE_9_ACTIVE_PAGES_BATCH_2_REPORT.md
docs/LOCALISATION_PHASE_11_ACTIVE_PAGES_BATCH_4_REPORT.md
docs/LOCALISATION_PHASE_12_ACTIVE_PAGES_BATCH_5_REPORT.md
docs/LOCALISATION_PHASE_13_ACTIVE_PAGES_BATCH_6_REPORT.md
docs/LOCALISATION_COVERAGE_AUDIT_REPORT.md
```

The Phase 13 audit classifier is now the source of truth.

Prioritise:

```text
active runtime candidates
service-title manual-review candidates
```

Ignore unless proven active:

```text
demo/template candidates
known false positives
language-file candidates
backup-only candidates
```

---

# 2. Target Area A — Billing Inertia / Frontend Pages

Check active frontend files for billing-related pages:

```text
resources/js/Pages/Billing/
resources/js/Pages/Invoices/
resources/js/Pages/Payments/
resources/js/Pages/Sponsors/
resources/js/Pages/CreditNotes/
resources/js/Pages/Statements/
resources/js/Pages/Aging/
resources/js/Pages/Discounts/
resources/js/Pages/Reports/
resources/js/pages/
resources/js/components/
resources/js/Components/
```

Translate visible strings in active frontend/Inertia surfaces:

* billing dashboard
* sponsors
* credit notes
* write-offs
* discounts
* refunds
* statements
* aging
* receivables
* invoice/payment frontend widgets
* table headers
* filters
* chart labels
* empty states
* modals
* confirmation dialogs
* loading messages
* error/success messages
* date range labels

Use existing frontend localisation patterns only:

```text
window.UHMS_I18N
useTrans()
module-level Blade i18n bridge
```

Do not add a new i18n package.

Rules:

* Payment is not revenue.
* Revenue comes from invoice/billing posting.
* Credit note, write-off, discount, and refund must remain separate concepts.
* Do not hardcode NHIS.
* Do not hardcode sponsors or insurance providers.
* Do not expose restricted financial data to unauthorized users.

---

# 3. Target Area B — Settings / Users / Roles / Departments / Modules

Check active views:

```text
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
* feature/module enablement pages
* service catalog admin leftovers
* table headers
* form labels
* help text
* modal titles
* modal body text
* action buttons
* empty states
* inline JavaScript strings

Use or extend:

```text
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
lang/en/statuses.php
lang/fr/statuses.php
```

Rules:

* Do not change permission logic.
* Do not change module enable/disable logic.
* Do not expose restricted controls to unauthorized users.

---

# 4. Target Area C — Reports Hub / Broader Reports

Check active report views and print/export templates:

```text
resources/views/reports/
resources/views/accounting/reports/
resources/views/billing/reports/
resources/views/claims/
resources/views/store/
resources/views/hr/
resources/js/Pages/Reports/
```

Translate remaining visible copy in:

* report hub
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
* CSV/export controls
* generated by/generated at labels
* permission warning labels
* redaction labels
* inline JavaScript strings

Use or extend:

```text
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

# 5. Target Area D — Service Title Manual Review

Use the audit classifier bucket:

```text
service-title manual-review candidates: 394
```

Review remaining service title/message findings in:

```text
app/Services/
```

For each candidate, classify as:

```text
A. User-facing timeline/notification/report/API label — translate now
B. Internal audit/event code — do not translate
C. Stored canonical event title — risky, document
D. SQL/internal expression — false positive
E. Already translated downstream — false positive
```

Translate only high-confidence user-facing strings.

Examples to review carefully:

* admission billing event titles
* emergency billing event titles
* blood bank workflow titles
* lab workflow titles
* pharmacy billing titles
* procedure workflow titles
* queue service titles
* journal reversal descriptions
* service rendering titles
* financial report labels

Use named placeholders for dynamic values.

Do not change stored values.
Do not change accounting semantics.
Do not change workflow status.
Do not change audit semantics.
Do not change timeline/event storage semantics unless clearly safe.

---

# 6. Target Area E — Active Shared Components / Layouts

Check active shared files:

```text
resources/views/components/
resources/views/partials/
resources/views/layouts/
resources/views/layout/
```

Translate remaining active shared UI text in:

* active header
* active footer
* breadcrumbs
* global modals
* global alerts
* shared action dropdowns
* status components
* empty-state components
* shared buttons
* shared confirmation components
* shared workflow cards
* shared print layouts

Be careful:

* `SidebarMenuBuilder.php` may store raw labels before downstream translation.
* If `translateLabel()` already handles it, document it as a false positive.
* Do not translate demo/template components unless active.
* Do not break Blade slots, props, or reusable components.

---

# 7. Audit Classifier Refinement

Improve the audit output so the next report is useful for QA.

The report must clearly separate:

```text
active runtime candidates
service-title manual-review candidates
demo/template candidates
language-file candidates
known false positives
backup-only candidates
```

For active runtime candidates, include:

```text
file path
line number
string
context
recommended action
status after Phase 14: fixed / deferred / false positive / manual review
```

Do not hide real active runtime strings.

The goal is not to force the raw candidate count to zero.
The goal is to get the active runtime candidate count as low as safely possible and document the rest honestly.

---

# 8. Dynamic Label Final Sweep

Search active runtime paths for:

```php
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

Where displayed to users and safe, replace with:

```php
->translatedLabel()
```

or explicit translation keys.

Do not change enum stored values.
Do not change constants.
Do not change workflow transitions.

---

# 9. JavaScript / Frontend Final Sweep

Search active frontend files for visible strings.

Translate:

* alerts
* confirmations
* loading messages
* empty states
* placeholders
* chart labels
* DataTables labels
* Select2 labels
* modal labels
* button labels
* error/success messages

Use existing localisation bridge only.

Document any strings that cannot be safely translated yet.

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

Run localisation audit:

```bash
php scripts/localisation-audit.php
```

Required:

* route list works
* view cache works
* EN/FR parity passes
* language lint passes
* audit runs successfully
* active runtime candidates are reduced or fully classified
* service-title candidates are translated or classified

---

# 11. Documentation

Create:

```text
docs/LOCALISATION_PHASE_14_FINAL_ACTIVE_RUNTIME_CLEANUP_GATE_REPORT.md
```

Include:

```text
active runtime candidates before/after
service-title candidates before/after
billing Inertia/frontend files translated/deferred
settings/users/roles/departments/modules files translated/deferred
reports hub/report files translated/deferred
shared component/layout files translated/deferred
service event titles translated/classified
dynamic label sweep result
frontend string sweep result
audit classifier changes
language files added/updated
EN/FR parity result
PHP lint result
cache/route/view-cache verification result
localisation audit result
remaining active runtime candidates
remaining manual-review candidates
final recommendation: ready for Full Test Suite / not ready yet
```

---

# 12. Acceptance Criteria

This phase is complete when:

* Billing Inertia/frontend strings are checked and translated or documented
* settings/users/roles/departments/modules are checked and translated or documented
* reports hub and broader report surfaces are checked and translated or documented
* active shared components/layouts are checked and translated or documented
* service-title manual-review candidates are translated or classified
* dynamic label final sweep is complete
* frontend string final sweep is complete
* audit report separates active runtime debt from noise
* EN/FR parity remains clean
* touched files pass lint
* caches clear
* route list works
* view cache works
* no business logic changed
* no workflow logic changed
* no permission logic changed
* no duplicate localisation system created
* no new packages introduced

Proceed with UHMS Localisation Phase 14 — Final Active Runtime Cleanup Gate now.
