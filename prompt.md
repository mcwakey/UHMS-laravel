Next should be **Responsiveness + Localisation Phase 2: Module Translation & Browser QA Hardening**.

The first pass already added the real localisation infrastructure: `lang/en`, `lang/fr`, `SetLocale` middleware, `users.locale`, language switcher, menu translation, status badge translation, shared component translation, and a responsive CSS layer. It also verified sidebar translations, status parity, Blade compilation, locale switching, and MariaDB migration. 

But the report still leaves some important follow-up work: page-level strings in high-traffic modules, controller flash messages, Form Request attributes, DataTables/Select2 UI strings, and manual browser checks at different screen widths. 

So the next prompt should be this:

````text
You are working on UHMS — Ultimate Hospital Management System.

The first Responsiveness + Localisation phase is complete.

The implementation already added:
- English/French language files
- SetLocale middleware
- users.locale
- POST /locale switch route
- EN/FR language switcher in the topbar
- translated SidebarMenuBuilder labels
- translated status badge labels
- shared component translation defaults
- responsive CSS hardening
- table responsiveness fixes
- print/mobile CSS improvements

Now proceed with Responsiveness + Localisation Phase 2:

Module-Level Translation, Flash Messages, Form Requests, Plugin Locales & Browser QA Hardening.

Goal:
Complete the practical localisation and responsive polish for the most-used UHMS modules so users can actually work in English or French across real hospital workflows.

Do not redesign the system.
Do not introduce Tailwind.
Do not introduce a second CSS framework.
Do not break existing workflows.
Do not translate database enum stored values.
Do not translate route names.
Do not translate permission names.
Do not translate user-entered clinical notes.
Do not translate patient/doctor/supplier/product names unless they are system-defined labels.

---

# 1. Phase 2 Scope

Focus on high-traffic production pages first:

1. Dashboards
2. Patients
3. Visits
4. Consultation
5. Emergency
6. Admissions / Wards
7. Pharmacy
8. Investigations / Lab
9. Theatre / Procedures
10. Billing
11. Insurance / Claims
12. Stock / Store / Procurement
13. Accounting / Finance
14. Reports
15. Auth / Profile / Settings

Do not waste time translating legacy/vendor template demo pages unless they are used in UHMS workflows.

---

# 2. Module Page Translation

Audit Blade views and Vue/Inertia islands for hardcoded user-facing strings.

Translate:

- page titles
- section titles
- buttons
- labels
- placeholders
- table headings
- filter labels
- empty states
- modal titles
- confirmation messages
- dashboard KPI labels
- report headings
- print labels

Use existing lang files where available.

If module language files are missing, create them:

```text
lang/en/patients.php
lang/fr/patients.php
lang/en/visits.php
lang/fr/visits.php
lang/en/billing.php
lang/fr/billing.php
lang/en/pharmacy.php
lang/fr/pharmacy.php
lang/en/investigations.php
lang/fr/investigations.php
lang/en/emergency.php
lang/fr/emergency.php
lang/en/admissions.php
lang/fr/admissions.php
lang/en/stock.php
lang/fr/stock.php
lang/en/accounting.php
lang/fr/accounting.php
lang/en/reports.php
lang/fr/reports.php
````

Use structured keys.

Examples:

```php
__('patients.title')
__('patients.search_placeholder')
__('patients.create_patient')

__('visits.create_visit')
__('visits.visit_type')
__('visits.patient_search')

__('billing.invoice')
__('billing.outstanding_balance')
__('billing.record_payment')

__('pharmacy.pending_prescriptions')
__('pharmacy.dispense')
__('pharmacy.out_of_stock')

__('accounting.trial_balance')
__('accounting.general_ledger')
__('accounting.journal_entries')
```

Avoid random long keys.

---

# 3. Controller Flash Message Translation

Audit controllers and services for hardcoded flash/session messages.

Translate messages like:

```text
Saved successfully.
Updated successfully.
Deleted successfully.
Invoice created successfully.
Payment recorded successfully.
Visit created successfully.
Patient registered successfully.
Unauthorized action.
Something went wrong.
```

Use:

```php
__('common.saved_successfully')
__('common.updated_successfully')
__('common.deleted_successfully')
__('billing.payment_recorded')
__('visits.visit_created')
```

Do not leave mixed English/French flash messages on translated pages.

---

# 4. Form Request Localisation

Audit Form Request classes.

Add or update:

```php
attributes()
messages()
```

Use translation keys for attribute names and custom messages.

Example:

```php
public function attributes(): array
{
    return [
        'patient_id' => __('patients.patient'),
        'visit_type' => __('visits.visit_type'),
        'payment_method' => __('billing.payment_method'),
    ];
}
```

Make validation errors readable in both English and French.

---

# 5. DataTables / Select2 / Datepicker Localisation

Add locale support for JavaScript plugin UI where used.

Priority plugins:

* DataTables
* Select2
* daterangepicker / datepicker
* SweetAlert2 confirmation text

Requirements:

* English plugin UI when locale is `en`
* French plugin UI when locale is `fr`
* no broken JS when locale changes
* fallback to English if plugin translation file missing

Do not introduce new JS libraries.

---

# 6. SweetAlert2 / Confirmation Translation

Translate all confirmation dialogs.

Examples:

```php
__('common.are_you_sure')
__('common.this_action_cannot_be_undone')
__('common.reason_required')
__('common.cancel')
__('common.confirm')
```

High-risk actions must still require reasons where already required.

Do not remove confirmation logic while translating.

---

# 7. Responsive Browser QA

Perform browser/manual QA for these widths:

```text
375px
414px
768px
1024px
1366px
1920px
```

Check at least:

1. Dashboard
2. Patient list
3. Patient profile
4. Visit creation
5. Consultation page
6. Emergency case page
7. Pharmacy dispensing
8. Investigation result entry
9. Invoice create/show
10. Payment screen
11. Stock balances
12. Stock movements
13. Journal entries
14. General ledger
15. Trial balance
16. AR aging
17. AP aging
18. Invoice/receipt print preview

Fix:

* horizontal overflow
* broken buttons
* unreadable tables
* modals too wide for mobile
* hidden totals
* hidden clinical warnings
* hidden financial warnings
* broken dropdowns
* bad spacing
* action buttons wrapping badly

Do not hide critical information just to make the page smaller.

---

# 8. Financial and Clinical Safety Checks

On mobile/tablet, ensure critical information remains visible.

Clinical:

* patient name
* visit number
* status
* triage/emergency priority
* allergies if available
* diagnosis/clinical context where relevant
* active medication/MAR warnings

Financial:

* invoice total
* amount paid
* balance
* payer responsibility
* accounting status
* payment/refund/write-off status

Stock:

* product
* location
* quantity on hand
* low/out/expired status
* stock movement direction

---

# 9. Print View QA

Check print views:

* invoice
* receipt
* lab result
* prescription
* discharge summary
* consultation summary
* claim report
* trial balance
* general ledger
* AR aging
* AP aging

Rules:

* sidebar/topbar hidden
* black-on-white
* hospital identity visible
* patient/visit context visible where relevant
* totals visible
* printed by / printed at visible
* signatures where needed
* no broken page layout

---

# 10. Translation Parity Script

Add or update a script/command to verify translation parity.

It should check:

* every `lang/en/*.php` key exists in `lang/fr/*.php`
* every `lang/fr/*.php` key exists in `lang/en/*.php`
* statuses have matching keys
* menu keys have matching translations
* dashboard keys have matching translations

Do not block development for vendor/demo pages, but report missing keys clearly.

Suggested command:

```bash
php artisan translations:audit
```

or a documented script if command is too much.

---

# 11. Documentation

Update:

```text
docs/RESPONSIVENESS_LOCALISATION_REPORT.md
```

Add Phase 2 section:

* module pages translated
* controller flash messages translated
* Form Request attributes/messages translated
* plugin locale behavior
* responsive browser QA results
* print QA results
* remaining untranslated pages
* known TODOs
* screenshots if useful

---

# 12. Manual Verification Required

1. Switch to English and browse major modules.
2. Switch to French and browse major modules.
3. Confirm menus remain translated.
4. Confirm status badges remain translated.
5. Confirm flash messages are translated.
6. Confirm validation messages are translated.
7. Confirm DataTables/Select2 UI strings are translated.
8. Confirm dashboard labels are translated.
9. Confirm reports headings are translated.
10. Confirm user-entered clinical notes are not translated.
11. Confirm route names and permissions are not translated.
12. Confirm mobile views work at 375px and 414px.
13. Confirm tablet views work at 768px and 1024px.
14. Confirm desktop views work at 1366px and 1920px.
15. Confirm print views are clean.
16. Confirm existing workflows still work.

---

# 13. Acceptance Criteria

Phase 2 is complete when:

* major module pages are practically usable in English and French
* controller flash messages are translated
* validation attributes/messages are translated
* status badges remain translated
* menu translation remains stable
* plugin UI strings are localised where practical
* dashboard labels are translated
* reports headings are translated
* major pages pass responsive browser QA
* print views remain clean
* translation parity is documented
* existing workflows are not broken
* no new CSS framework is introduced
* documentation is updated

---

# 14. Important Rules

Do not introduce Tailwind.
Do not introduce a second CSS framework.
Do not translate stored enum values.
Do not translate permissions.
Do not translate route names.
Do not translate audit log event codes.
Do not translate user-entered clinical notes.
Do not hide critical clinical, financial, or stock information on mobile.
Do not break existing dashboards.
Do not break SidebarMenuBuilder.
Do not bypass existing UI components.
Do not skip manual browser QA.

Proceed with Responsiveness + Localisation Phase 2 now.

```
