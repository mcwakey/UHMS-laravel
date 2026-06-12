You are working on UHMS — Ultimate Hospital Management System.

Important:
There is currently no docs/UHMS_IMPLEMENTATION_SKILL.md file in this project.
Do not try to read it.
Follow the instructions in this prompt directly.

We completed localisation Phases 1–5, but there is concern that many pages may still contain untranslated visible text.

Before continuing to Full Test Suite or Final Stabilisation, perform a full localisation coverage audit.

# UHMS Localisation Coverage Audit — Find All Remaining Untranslated Pages

## Goal

Scan the entire UHMS project and identify every remaining user-facing hardcoded English string that should be translated.

This phase is primarily an audit and cleanup phase.

Do not assume previous reports covered every page.
Do not rely only on the menu.
Do not rely only on pages already translated.
Do not claim 100% translation unless the full scan supports it.

---

# 1. Audit Scope

Scan these areas:

```text
resources/views/
resources/js/
public/js/
app/Http/Controllers/
app/View/Components/
app/Models/
app/Enums/
app/Services/
app/Helpers/
resources/lang/
lang/en/
lang/fr/
routes/
```

Pay special attention to:

```text
resources/views/**/*.blade.php
resources/views/**/print*.blade.php
resources/views/**/*pdf*.blade.php
resources/views/emails/
resources/views/mail/
resources/views/components/
resources/views/partials/
resources/views/layouts/
resources/js/**/*.js
public/js/**/*.js
```

---

# 2. What To Detect

Find hardcoded user-facing strings such as:

* page titles
* headings
* buttons
* labels
* placeholders
* table headers
* filter labels
* empty states
* alert text
* modal titles
* modal body text
* confirmation messages
* dropdown action labels
* badge/status labels
* print labels
* PDF labels
* email labels
* JavaScript UI messages
* controller flash messages
* model/enum display labels

Detect patterns like:

```blade
<h1>Patients</h1>
<button>Save</button>
<label>Phone Number</label>
<option>Pending</option>
<th>Amount</th>
placeholder="Search patient"
title="Delete"
```

Also detect PHP patterns like:

```php
->with('success', '...')
->with('error', '...')
return 'Pending';
'label' => 'Active'
'title' => 'Reports'
```

And JavaScript patterns like:

```js
alert('Saved successfully')
confirm('Are you sure?')
text: 'Loading...'
placeholder: 'Search'
```

---

# 3. What Not To Flag

Do not flag these as translation problems:

* class names
* route names
* permission names
* config keys
* API keys
* database column names
* model names
* migration names
* CSS classes
* JS selectors
* Alpine/Vue/JS internal variable names
* patient names
* doctor names
* supplier names
* product names entered by users
* service names entered by users unless system-defined
* clinical notes
* diagnosis free text
* audit event codes
* units like mmHg, bpm, °C, kg, %, ml
* currency symbols like GH₵ or ₵
* format examples like GHA-XXXXXXXXX-X
* universal fallback values like N/A
* numeric clinical thresholds
* brand name UHMS

---

# 4. Build A Localisation Scan Script

Create a safe developer utility script or artisan command if appropriate.

Preferred:

```text
php artisan localisation:audit
```

If an artisan command is too much, create a script such as:

```text
scripts/localisation-audit.php
```

The scanner should:

1. Recursively scan Blade, PHP and JS files.
2. Detect likely hardcoded user-facing English strings.
3. Ignore obvious false positives.
4. Group results by file.
5. Include line numbers.
6. Include the detected string.
7. Suggest a likely lang file/key when possible.
8. Output a Markdown report.

Recommended output file:

```text
docs/LOCALISATION_COVERAGE_AUDIT_REPORT.md
```

---

# 5. Report Format

The report must include:

```text
Summary
- total files scanned
- total files with possible hardcoded strings
- total hardcoded candidates found
- modules affected
- high priority files
- medium priority files
- likely false positives

Detailed Findings
- file path
- line number
- detected string
- context
- recommendation
- suggested lang file/key

Coverage Status
- translated modules
- partially translated modules
- untranslated modules
- print/PDF coverage
- email coverage
- JavaScript coverage
- controller flash message coverage
- dynamic enum/model label coverage
```

---

# 6. Cleanup After Audit

After producing the audit report, fix high-confidence translation issues only.

Prioritise:

1. Blade views with obvious visible English text
2. print/PDF templates
3. email templates
4. controller flash messages
5. JavaScript alerts/confirmations/placeholders
6. model/enum labels visible in UI

For every fix:

* use existing lang files where possible
* create EN/FR keys together
* maintain EN/FR parity
* do not duplicate messy keys
* do not change business logic
* do not change workflows
* do not move business logic into Blade

---

# 7. Translation Key Rules

Use existing files where possible:

```text
lang/en/common.php
lang/fr/common.php
lang/en/messages.php
lang/fr/messages.php
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
lang/en/reports.php
lang/fr/reports.php
lang/en/settings.php
lang/fr/settings.php
lang/en/users.php
lang/fr/users.php
lang/en/statuses.php
lang/fr/statuses.php
```

Create new module lang files only if the module has enough unique text and no suitable file exists.

---

# 8. EN/FR Parity

After adding or updating keys, verify full EN/FR parity.

Check nested keys, not only top-level keys.

Required result:

```text
0 missing EN keys
0 missing FR keys
all lang files pass php -l
```

---

# 9. Verification

Run:

```bash
php artisan view:clear
php artisan config:clear
php artisan cache:clear
php artisan route:list
```

Run PHP syntax checks:

```bash
for f in lang/en/*.php lang/fr/*.php; do php -l "$f"; done
```

Run or create a nested parity check script and document the result.

If possible, manually open a sample of pages from each major module in both English and French.

---

# 10. Architecture Rules

Do not create a parallel localisation system.
Do not duplicate middleware.
Do not duplicate locale routes.
Do not introduce a new translation package.
Do not introduce Tailwind.
Do not introduce a new frontend framework.
Do not move business logic into Blade.
Do not bypass ActivityLogService.
Do not bypass permissions.
Use Bootstrap 5 and Tabler Icons only.

---

# 11. Deliverables

At the end, provide:

1. Localisation audit report path.
2. Total files scanned.
3. Total files with hardcoded string candidates.
4. List of high-priority untranslated pages found.
5. List of files fixed.
6. List of language files updated.
7. EN/FR parity result.
8. PHP lint result.
9. Remaining untranslated candidates, if any.
10. False positives intentionally ignored.
11. Confirmation that no duplicate localisation system was created.
12. Confirmation that business logic was not changed.

Proceed with UHMS Localisation Coverage Audit now.
