You are working on UHMS — Ultimate Hospital Management System.

Important:
There is currently no docs/UHMS_IMPLEMENTATION_SKILL.md file in this project.
Do not try to read it.
Follow the instructions in this prompt directly.

We completed UHMS Localisation Phase 7.

Phase 7 established:

* Active Laravel routes: 714
* Live controller/route `view(...)` references found: 240
* Blade files under `resources/views`: 590
* Direct active route-linked Blade views matched: 227
* Inertia pages were observed separately and still need frontend localisation review
* The application is not yet fully free of hardcoded runtime text

Phase 7 translated:

* `resources/views/appointments/index.blade.php`
* `resources/views/admin/products/index.blade.php`
* `resources/views/admin/services/index.blade.php`

Phase 7 still lists many remaining active pages with hardcoded user-facing text.

Now proceed with:

# UHMS Localisation Phase 8 — Active Pages Translation Batch 1

## Goal

Continue translating active route-linked UHMS pages from the Phase 7 inventory.

This phase must focus on real active runtime pages only.

Do not translate demo/template/sample pages.
Do not translate backup-route-only pages.
Do not translate files only referenced by `routes/web.php.bak`.
Do not guess based on folder names only.
Use the active route/view inventory and actual controller/view usage.

This batch must focus on:

1. Appointments remaining pages
2. Visits remaining pages
3. Products remaining pages
4. Services and service rendering remaining pages
5. Related Inertia/frontend strings for visits and billing if present and safe

---

# 1. Start From Phase 7 Inventory

Open and use:

```text
docs/LOCALISATION_PHASE_7_COMPLETE_ACTIVE_PAGE_TRANSLATION_REPORT.md
docs/LOCALISATION_COVERAGE_AUDIT_REPORT.md
```

Use these as the starting point.

Do not repeat the full broad audit from scratch unless necessary.

Work from the active backlog.

---

# 2. Batch 1 Target Areas

Translate all active route-linked views and related partials/components for:

## Appointments

Check and translate active appointment pages beyond the index:

```text
resources/views/appointments/
```

Cover:

* create page
* edit page
* show page
* calendar page
* appointment forms
* appointment partials
* appointment modals
* check-in flows
* appointment status labels
* doctor schedule links
* appointment reminders
* inline scripts

Use or extend:

```text
lang/en/appointments.php
lang/fr/appointments.php
```

## Visits

Check and translate all active visit pages:

```text
resources/views/visits/
```

Also check Inertia/frontend visit pages if used:

```text
resources/js/Pages/Visits/
resources/js/pages/Visits/
resources/js/views/Visits/
```

depending on the actual project structure.

Cover:

* visit index
* create
* edit
* show
* preview
* visit partials
* visit timeline labels
* visit queue/session panels
* billing preview
* insurance switching panels
* emergency visit panels
* consultation assignment labels
* inline scripts
* Inertia labels if present

Use or extend:

```text
lang/en/visits.php
lang/fr/visits.php
```

Do not change visit workflow logic.
Emergency visits must remain part of the Visit workflow.

## Products

Phase 7 translated only the active product index.

Now translate remaining active product pages, modals, and related partials under actual active paths such as:

```text
resources/views/admin/products/
resources/views/products/
resources/views/pharmacy/
resources/views/store/
```

depending on route/view inventory.

Cover:

* product show
* product create/edit forms
* product modal internals
* product pricing modal internals
* product category labels
* product type labels
* billable/stock labels
* stock-linked product screens
* reorder labels
* expiry labels
* batch labels
* supplier history labels
* inline scripts

Use or extend:

```text
lang/en/products.php
lang/fr/products.php
lang/en/stock.php
lang/fr/stock.php
lang/en/pharmacy.php
lang/fr/pharmacy.php
```

Rules:

* Products are physical stock items.
* Services are billable activities.
* Do not mix product/service terminology.
* Do not expose stock cost unless existing permission allows it.

## Services / Service Rendering

Phase 7 translated only the active service catalogue index.

Now translate remaining active service pages and partials under actual active paths such as:

```text
resources/views/admin/services/
resources/views/services/
resources/views/service-rendering/
resources/views/service-catalog/
```

depending on route/view inventory.

Cover:

* service show
* service create/edit forms
* service modal internals
* service pricing modal internals
* service rendering pages
* service categories
* billable service labels
* consultation service labels
* emergency service labels
* lab/procedure/pharmacy service links
* inline scripts

Use or extend:

```text
lang/en/services.php
lang/fr/services.php
lang/en/common.php
lang/fr/common.php
```

Rules:

* Services are billable activities.
* Products are physical stock items.
* Do not hardcode emergency services.
* Do not hardcode consultation services.
* Do not move pricing or billing logic into Blade.

---

# 3. Translation Rules

Translate user-facing:

```text
page titles
headings
breadcrumbs
tabs
buttons
dropdown actions
form labels
placeholders
help text
filters
search fields
table headers
empty states
status labels
modal titles
modal body text
modal buttons
confirmation messages
alert messages
validation hints
print labels
PDF labels
JavaScript UI text
Inertia/frontend page labels
```

Do not translate:

```text
patient names
staff names
doctor names
supplier names
sponsor names
insurance provider names
product names entered by users
service names entered by users unless system-defined
clinical notes
diagnosis free text
prescription notes
audit event codes
permission names
route names
database/internal codes unless mapped through display labels
CSS classes
JS selectors
units such as mmHg, bpm, °C, kg, ml, %
currency symbols such as GH₵ or ₵
format examples
UHMS brand name
```

---

# 4. Dynamic Labels

Search targeted files for:

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

# 5. JavaScript / Inertia Localisation

If active frontend/Inertia files are present for visits, billing, products, services, or appointments:

1. Identify hardcoded visible strings.
2. Use existing localisation bridge if already present.
3. Do not introduce a new frontend framework.
4. Do not introduce a new translation package.
5. Do not expose sensitive data to JavaScript.

If the project has no clear frontend i18n pattern for Inertia yet, document the needed pattern and safely localise only Blade-provided strings where practical.

---

# 6. Language Files

Add EN and FR keys together.

Use existing files:

```text
lang/en/appointments.php
lang/fr/appointments.php
lang/en/visits.php
lang/fr/visits.php
lang/en/products.php
lang/fr/products.php
lang/en/services.php
lang/fr/services.php
lang/en/common.php
lang/fr/common.php
lang/en/statuses.php
lang/fr/statuses.php
lang/en/messages.php
lang/fr/messages.php
lang/en/stock.php
lang/fr/stock.php
lang/en/pharmacy.php
lang/fr/pharmacy.php
```

Create new paired files only if absolutely needed.

Maintain nested EN/FR parity.

---

# 7. Responsive Cleanup While Translating

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

# 8. Verification

Run:

```bash
php artisan view:clear
php artisan config:clear
php artisan cache:clear
php artisan route:list
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

* appointments remaining pages cleaned
* visits remaining pages cleaned
* products remaining pages cleaned
* services/service-rendering remaining pages cleaned
* Inertia/frontend review result
* remaining active pages after this batch

---

# 9. Documentation

Create:

```text
docs/LOCALISATION_PHASE_8_ACTIVE_PAGES_BATCH_1_REPORT.md
```

Include:

* active routes/views checked in this batch
* appointments files translated
* visits files translated
* product files translated
* service/service-rendering files translated
* Inertia/frontend files checked or deferred
* language files added/updated
* JavaScript strings translated
* dynamic labels updated
* responsive fixes made
* EN/FR parity result
* PHP lint result
* cache/route verification result
* localisation audit result
* remaining active untranslated pages

---

# 10. Acceptance Criteria

This phase is complete when:

* all active appointment pages in this batch are checked and translated or documented
* all active visit pages in this batch are checked and translated or documented
* all active product pages in this batch are checked and translated or documented
* all active service/service-rendering pages in this batch are checked and translated or documented
* related active Inertia/frontend strings are checked and translated or documented
* EN/FR parity remains clean
* touched files pass lint
* caches clear
* route list works
* no business logic changed
* no workflows changed
* no duplicate localisation system created
* no new packages introduced

Proceed with UHMS Localisation Phase 8 — Active Pages Translation Batch 1 now.
