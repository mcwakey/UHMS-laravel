You are working on UHMS — Ultimate Hospital Management System.

Important:
There is currently no docs/UHMS_IMPLEMENTATION_SKILL.md file in this project.
Do not try to read it.
Follow the instructions in this prompt directly.

We completed:

* Localisation Phases 1–5
* Localisation Coverage Audit
* Localisation Coverage Cleanup — Real Runtime Messages

The latest cleanup report shows:

* files scanned stayed at 1,248
* files with candidates reduced from 551 to 536
* candidates reduced from 19,758 to 19,723
* controller/API messages and several report/service labels were fixed
* many remaining candidates are demo/template/sample pages
* `SidebarMenuBuilder.php` still appears because raw source labels are translated downstream
* enum/model labels need a dedicated safe pass
* real Blade screens still need a separate cleanup excluding demo/template files

Now proceed with:

# UHMS Localisation Phase 6 — Real Blade Screens & Enum/Status Label Cleanup

## Goal

Clean remaining real UHMS Blade screens and safely localise enum/model/status labels that are visible to users.

This phase must avoid wasting time on demo/template/sample pages.

Do not blindly translate all audit candidates.
Do not translate demo/template files unless they are actively used by UHMS routes or menus.
Do not change stored enum/database values.
Do not change business logic.
Do not change workflow logic.
Do not create a parallel localisation system.

---

# 1. Audit Real Routes First

Before editing Blade files, map real runtime routes to views.

Use:

```bash
php artisan route:list
```

Then identify which Blade files are actually used by active UHMS routes/controllers.

Create a classification list:

1. Real active UHMS views — clean now
2. Shared components used by real views — clean now
3. Demo/template/sample views — ignore/document
4. Legacy unused views — document as cleanup candidates
5. Unsure views — document for manual review

Do not rely only on filename.
Check route/controller usage.

---

# 2. Exclude Demo/Template Views

Unless proven active, exclude these from translation cleanup:

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
resources/views/components/modal-popup.blade.php
```

For each excluded file, document:

* whether it has a route
* whether it is linked in menu/sidebar
* whether it appears to be template/demo
* whether it should be deleted, archived, or left as reference later

Do not delete these files in this phase.

---

# 3. Real Blade Screen Cleanup

Clean real active Blade views only.

Prioritise:

```text
resources/views/consultations/
resources/views/theatre/
resources/views/blood-bank/
resources/views/medication-administration/
resources/views/hr/
resources/views/admin/
resources/views/store/
resources/views/layout/partials/
resources/views/components/
resources/views/partials/
resources/views/emails/
resources/views/mail/
```

Translate visible:

* page headings
* section headings
* breadcrumbs
* buttons
* labels
* placeholders
* table headers
* filter labels
* modal titles
* modal button text
* empty states
* alerts
* dropdown actions
* print labels
* email labels
* JavaScript UI strings inside Blade

Do not translate:

* patient names
* doctor names
* supplier names
* product names entered by users
* service names entered by users unless system-defined
* clinical free text
* diagnosis notes
* audit event codes
* route names
* permission names
* internal codes
* CSS classes
* JavaScript selectors
* units like mmHg, bpm, °C, kg, ml
* currency symbols
* UHMS brand name

---

# 4. Enum / Model / Status Label Cleanup

Audit:

```text
app/Enums/
app/Models/
app/Services/
app/Helpers/
```

Look for:

```php
label()
getLabelAttribute()
statusLabel()
typeLabel()
displayName()
humanName()
```

Also check enum-like classes such as:

```text
InvoiceStatus
ClaimStatus
BillingType
PaymentStatus
VisitStatus
AdmissionStatus
EmergencyStatus
ProcedureStatus
StockMovementType
```

If labels are visible to users, localise them safely.

Preferred approach:

* do not change stored values
* do not change canonical enum constants
* add `translatedLabel()` if changing `label()` is risky
* use existing `statuses.php` groups where possible
* use module-specific lang files when needed

Example:

```php
public function translatedLabel(): string
{
    return __('statuses.invoices.' . $this->value);
}
```

If an existing status badge resolver already handles translation, reuse it.

Do not duplicate status logic.

---

# 5. Service Event Title Review

Review remaining service `title` and `message` findings.

Focus on services where strings may appear in:

* patient timeline
* visit timeline
* notifications
* audit logs
* report payloads
* task queues
* dashboard cards
* API responses

Known areas from previous report:

* admission workflow services
* blood bank services
* procedure workflow services
* lab workflow services
* merge-preview services
* queue services
* emergency services
* pharmacy services

Classify each string:

1. User-facing timeline/notification/report/API label — translate
2. Internal audit/event code — do not translate
3. Stored canonical event title — risky, document for later
4. False positive — ignore

Do not change audit/event semantics accidentally.

---

# 6. Sidebar/Menu Handling

Inspect `app/Services/SidebarMenuBuilder.php`.

If active labels are translated downstream through `translateLabel()` or existing menu translation logic, document this as a false positive.

Only fix labels that are active and not translated at render time.

Ignore commented-out menu blocks.

Do not break:

* permissions
* module visibility
* menu hierarchy
* route names
* icon names

---

# 7. Translation Keys

Use existing files where possible:

```text
lang/en/common.php
lang/fr/common.php
lang/en/menu.php
lang/fr/menu.php
lang/en/statuses.php
lang/fr/statuses.php
lang/en/messages.php
lang/fr/messages.php
lang/en/reports.php
lang/fr/reports.php
lang/en/consultations.php
lang/fr/consultations.php
lang/en/theatre.php
lang/fr/theatre.php
lang/en/blood_bank.php
lang/fr/blood_bank.php
lang/en/hr.php
lang/fr/hr.php
lang/en/lab.php
lang/fr/lab.php
lang/en/stock.php
lang/fr/stock.php
```

If a needed module file does not exist, create both EN and FR files.

Maintain EN/FR parity.

Avoid duplicate keys.
Avoid vague keys like `label1`, `text2`, `button_new`.

---

# 8. Re-run Localisation Audit

After cleanup, rerun:

```bash
php scripts/localisation-audit.php
```

The total candidate count may still be high because of excluded demo/template files.

That is acceptable.

But the report must clearly show:

* real active UHMS views cleaned
* demo/template files documented separately
* enum/status labels handled or documented
* remaining real candidates listed clearly
* no hardcoded controller flash literals
* no obvious untranslated text in active high-priority modules

---

# 9. Verification

Run:

```bash
php artisan view:clear
php artisan config:clear
php artisan cache:clear
php artisan route:list
```

Run lint:

```bash
find app database routes config -name "*.php" -print0 | xargs -0 -n1 php -l
for f in lang/en/*.php lang/fr/*.php; do php -l "$f"; done
```

Run nested EN/FR parity check.

Required:

* 0 missing EN keys
* 0 missing FR keys
* touched files pass PHP lint
* caches clear
* route list works

If full recursive lint times out, run touched-file lint and document the timeout honestly.

---

# 10. Documentation

Create:

```text
docs/LOCALISATION_PHASE_6_REAL_BLADE_ENUM_STATUS_CLEANUP_REPORT.md
```

Include:

* route-to-view audit summary
* real active views cleaned
* shared components cleaned
* demo/template views excluded
* legacy unused views documented
* enum/model/status labels updated
* service event titles translated or classified
* sidebar/menu false-positive decision
* language files changed
* audit result after cleanup
* EN/FR parity result
* lint/cache/route verification result
* remaining TODOs

---

# 11. Acceptance Criteria

This phase is complete when:

* real active UHMS Blade views from the audit are cleaned
* demo/template files are separated from real app files
* enum/status labels visible to users are translated or safely documented
* service event titles are translated or classified
* sidebar/menu false positives are documented
* EN/FR parity remains clean
* touched files pass lint
* caches clear
* route list works
* no business logic was changed
* no workflow logic was changed
* no duplicate localisation system was created
* no Tailwind or new packages introduced

Proceed with UHMS Localisation Phase 6 now.
