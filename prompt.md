You are working on UHMS — Ultimate Hospital Management System.

Important:
There is currently no docs/UHMS_IMPLEMENTATION_SKILL.md file in this project.
Do not try to read it.
Follow the instructions in this prompt directly.

We completed a Critical Page Translation Audit & Fix Pass after Phase 14.

That pass fixed critical appointment and product localisation blockers:

* appointment create/edit/index/show pages
* product index/show/edit modal/pricing modal
* inline JavaScript-generated labels
* feedback messages
* confirmations
* accessibility labels
* validation attributes

Verification passed:

* route:list passed with 714 routes
* view:clear passed
* config:clear passed
* cache:clear passed
* view:cache passed
* all EN/FR lang PHP lint passed
* recursive EN/FR parity passed
* git diff --check passed
* localisation audit passed

Latest audit result:

* files with candidates: 496
* total candidates: 18,440
* active runtime candidates: 4,302
* known false positives: 13,403
* service-title manual-review candidates: 394

Important:
This is not yet full localisation completion.
Do not proceed to Full Test Suite yet.
The next goal is to burn down the remaining 4,302 active-runtime candidates.

Now proceed with:

# UHMS Localisation Phase 15 — Active Runtime Candidate Burn-Down

## Goal

Use the classified localisation audit to systematically reduce the remaining active runtime candidates.

This phase must focus only on:

1. Active runtime candidates
2. Confirmed user-facing strings
3. Critical active modules still carrying untranslated text
4. Remaining JavaScript/frontend strings
5. Remaining shared component strings
6. Remaining route-linked Blade strings

Do not chase:

* known false positives
* demo/template views
* backup-only views
* language files themselves
* commented-out code
* CSS classes
* JavaScript selectors
* SQL expressions
* clinical units
* currency symbols
* UHMS brand text
* user-entered database content

Do not change business logic.
Do not change workflow logic.
Do not change permission logic.
Do not expose restricted clinical, financial, payroll, stock-cost, or accounting data.
Do not introduce new packages.
Do not introduce Tailwind.
Do not create a new localisation system.

---

# 1. Source Reports

Use:

```text id="4n4ws1"
docs/LOCALISATION_COVERAGE_AUDIT_REPORT.md
docs/LOCALISATION_PHASE_6_CRITICAL_PAGE_AUDIT_REPORT.md
docs/LOCALISATION_PHASE_13_ACTIVE_PAGES_BATCH_6_REPORT.md
docs/LOCALISATION_PHASE_12_ACTIVE_PAGES_BATCH_5_REPORT.md
docs/LOCALISATION_PHASE_11_ACTIVE_PAGES_BATCH_4_REPORT.md
docs/LOCALISATION_PHASE_9_ACTIVE_PAGES_BATCH_2_REPORT.md
docs/LOCALISATION_PHASE_8_ACTIVE_PAGES_BATCH_1_REPORT.md
docs/LOCALISATION_PHASE_7_COMPLETE_ACTIVE_PAGE_TRANSLATION_REPORT.md
```

The latest `docs/LOCALISATION_COVERAGE_AUDIT_REPORT.md` is the main source of truth.

---

# 2. Build Active Runtime Candidate Worklist

From the latest audit, extract all candidates classified as:

```text id="06mnix"
active runtime candidates
```

Group them by:

```text id="64ogog"
module
file path
route-linked status
user-facing confidence
risk level
recommended action
```

Create a worklist table with columns:

```text id="as39kz"
module
file
candidate count
active route-linked? yes/no
shared component? yes/no
priority
action: fix / defer / false-positive / manual-review
```

Prioritise high-impact active modules first.

---

# 3. Priority Order

Process remaining active runtime candidates in this order:

## Priority 1 — Active Patient/Clinical Flow Pages

```text id="jmdfbu"
consultations
visits
appointments residuals
patients residuals
triage
queue
emergency
admissions
wards
medication administration
lab/investigations
theatre/procedures
blood bank
```

## Priority 2 — Active Financial Flow Pages

```text id="xbeb96"
billing
invoices
payments
cashier
claims
insurance
sponsors
accounting
accounts
reports
```

## Priority 3 — Active Operational/Admin Pages

```text id="n4m220"
store
stock
procurement
suppliers
purchase orders
HR
employees
attendance
leave
payroll
settings
users
roles
departments
modules
```

## Priority 4 — Shared Runtime Surfaces

```text id="xk7q7f"
components
partials
layouts
layout partials
shared modals
shared alerts
shared empty states
shared action menus
shared print layouts
frontend components
```

---

# 4. Fix Rules

For each confirmed user-facing hardcoded string:

* replace with `__('...')`
* add EN and FR keys together
* use existing module language files where possible
* create paired EN/FR files only where necessary
* keep key names grouped and meaningful
* preserve all dynamic placeholders
* preserve existing data display behavior
* preserve existing permissions

Examples:

```blade id="x30gci"
{{ __('visits.create.title') }}
{{ __('billing.invoice.status_paid') }}
{{ __('common.actions.delete') }}
```

For dynamic strings, use placeholders:

```php id="vmsh2w"
__('messages.queue.patient_waiting_for_consultation', ['name' => $patientName])
```

Do not concatenate translated fragments if a full sentence is better.

---

# 5. JavaScript / Frontend Strings

Search active frontend and Blade inline JavaScript for visible strings:

```text id="lsczhf"
resources/js/
resources/js/Pages/
resources/js/Components/
resources/js/components/
public/js/
inline <script> blocks in active Blade views
```

Translate:

* alerts
* confirmations
* loading labels
* empty states
* placeholders
* Select2 labels
* DataTables labels
* chart labels
* calendar labels
* modal labels
* button labels
* AJAX success/error text

Use existing patterns only:

```text id="3ysxz8"
window.UHMS_I18N
useTrans()
module-level Blade i18n map
```

Do not introduce a new frontend i18n package.

Document frontend strings that cannot be safely localised yet.

---

# 6. Shared Components

Review active shared components with many active-runtime candidates:

```text id="ag2rtk"
resources/views/components/
resources/views/partials/
resources/views/layouts/
resources/views/layout/
```

Be careful with:

* slot content
* props
* reusable labels
* global modals
* global alerts
* status badges
* print layouts
* empty state components

Do not translate inactive template/demo components.

If a component is used only by demo/template pages, classify it as demo/template noise.

---

# 7. SidebarMenuBuilder Handling

For `app/Services/SidebarMenuBuilder.php`:

1. Confirm whether labels are translated downstream through `translateLabel()`.
2. If yes, keep as false positive and document.
3. If any active menu label bypasses translation, fix it using `menu.php`.

Do not break:

* menu hierarchy
* module visibility
* permissions
* route names
* icon names
* active patterns

---

# 8. Service Title Manual Review

The audit still reports:

```text id="zj3e3m"
394 service-title manual-review candidates
```

Do not blindly translate all.

For each candidate classify:

```text id="7cmu73"
A. User-facing timeline/notification/report/API label — translate
B. Internal audit/event code — leave as-is
C. Stored canonical event title — defer with reason
D. SQL/internal expression — false positive
E. Already translated downstream — false positive
```

Translate only high-confidence user-facing strings.

Document the rest.

Do not change stored event semantics, audit semantics, workflow status, or accounting semantics.

---

# 9. Dynamic Label Final Sweep

Search active runtime files for:

```php id="x5fii4"
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

* `translatedLabel()`
* existing status badge component
* explicit translation keys

Do not change stored enum values.
Do not change enum constants.
Do not change workflow transitions.

---

# 10. Audit Re-run And Candidate Reduction

After fixes, rerun:

```bash id="0ygdlv"
php scripts/localisation-audit.php
```

The report must show:

```text id="zvztwo"
active runtime candidates before
active runtime candidates after
fixed candidates count
deferred candidates count
false-positive candidates count
manual-review candidates count
top remaining active files
```

The raw candidate count may remain high because of known false positives, but active runtime candidates should decrease or be fully classified.

---

# 11. Verification

Run:

```bash id="y3j47w"
php artisan view:clear
php artisan config:clear
php artisan cache:clear
php artisan route:list
php artisan view:cache
php artisan view:clear
```

Run language lint:

```bash id="wtznki"
for f in lang/en/*.php lang/fr/*.php; do php -l "$f"; done
```

Run recursive EN/FR parity check.

Run:

```bash id="f6b7w3"
git diff --check
```

Required:

* route list works
* view cache works
* EN/FR parity passes
* language lint passes
* git diff --check passes
* localisation audit runs successfully

---

# 12. Documentation

Create:

```text id="nrlh7c"
docs/LOCALISATION_PHASE_15_ACTIVE_RUNTIME_CANDIDATE_BURNDOWN_REPORT.md
```

Include:

```text id="deklb0"
active runtime candidates before/after
files/modules fixed
files/modules deferred
false positives confirmed
service-title review summary
frontend strings fixed/deferred
shared components fixed/deferred
dynamic label sweep result
language files changed
EN/FR parity result
PHP lint result
route/cache/view-cache result
git diff --check result
localisation audit result
remaining active runtime candidates
recommendation: ready for Full Test Suite / needs Phase 15B
```

---

# 13. Acceptance Criteria

This phase is complete when:

* remaining active runtime candidates are grouped by module and route-linked status
* high-confidence active user-facing strings are translated
* frontend strings are checked and translated or documented
* shared runtime components are checked and translated or documented
* service-title manual-review candidates are classified
* dynamic label final sweep is complete
* active runtime candidates are reduced or fully classified
* EN/FR parity remains clean
* touched files pass lint
* route list works
* view cache works
* git diff --check passes
* localisation audit runs successfully
* no business logic changed
* no workflow logic changed
* no permission logic changed
* no duplicate localisation system created
* no new packages introduced

Proceed with UHMS Localisation Phase 15 — Active Runtime Candidate Burn-Down now.
