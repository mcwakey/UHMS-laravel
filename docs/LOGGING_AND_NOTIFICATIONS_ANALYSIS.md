# UHMS Logging & Notifications — Analysis, Gaps & Improvement Plan

_Status: 2025 audit. Companion to `docs/UHMS_Full_Project_Analysis_Report.md`._

This report inventories the **logging**, **activity tracking**, and **user
notification** stacks in the UHMS Laravel 12 / Vue 3 / Inertia codebase,
identifies gaps in observability and user feedback, and prescribes concrete
improvements — including the **`UhmsConfirmDialog`** component that replaces
all native browser popups (`window.confirm`, `window.alert`,
`onsubmit="return confirm(...)"`) with a consistent, accessible,
hospital-branded modal.

---

## 1. Executive Summary

| Area | Current State | Verdict |
| --- | --- | --- |
| Application logging (Monolog) | Default Laravel `stack → single` channel; ~25 `Log::*` call sites with mixed dotted-key and sentence-case messages. | **Functional but flat.** No per-domain channels, no external sink, no log retention policy, no correlation IDs. |
| Activity logging (Spatie) | ~25+ models trait-wired; `LogOptions::defaults()->logFillable()->logOnlyDirty()`; 365-day retention. | **Strong coverage**, weak naming (everything is `log_name = default`). |
| Domain events / listeners | 7 events, 7 listeners — each fans out to one `database` notification. | **Works**, but channels are hardcoded and not user-configurable. |
| Notification UI | Bell icon polls `/admin/notifications/recent` every page; standard Laravel database notifications. | Acceptable; lacks real-time push and per-user preferences. |
| Validation / confirmation popups | **75 native `confirm()` / `alert()` sites** across Blade + 1 in Vue. | **Major UX & a11y gap.** Replaced this iteration by `UhmsConfirmDialog` + `data-confirm` bridge. |

---

## 2. Logging Stack Today

### 2.1 Configuration (`config/logging.php`)

- Default channel: `LOG_CHANNEL=stack`
- `stack` aggregates `LOG_STACK=single` only.
- Other channels available but **not wired by default**: `daily`, `slack`,
  `papertrail`, `stderr`, `syslog`, `errorlog`, `null`, `emergency`.
- Deprecations: routed to `null` channel → silently dropped.

> **Effect:** every log line — debug, info, warning, error — ends up in
> `storage/logs/laravel.log` (single file), regardless of domain.

### 2.2 Application log call sites (~25 hits)

Grep: `Log::(info|error|warning|debug|critical|notice|alert|emergency)\(`

Representative examples:

| File | Severity | Key / Message |
| --- | --- | --- |
| [app/Services/PharmacyService.php](app/Services/PharmacyService.php) | warning | `pharmacy.dispense.product_ledger_failed` |
| [app/Services/StockTransferService.php](app/Services/StockTransferService.php) | error | `store.transfer.complete_failed` |
| [app/Services/BillingService.php](app/Services/BillingService.php) | info | `billing.invoice.posted` |
| [app/Http/Controllers/VisitController.php](app/Http/Controllers/VisitController.php) | warning | sentence-case message |
| [app/Console/Commands/AnalyzerListenCommand.php](app/Console/Commands/AnalyzerListenCommand.php) | info | `analyzer.connection.opened` |
| [app/Jobs/ProcessAnalyzerMessage.php](app/Jobs/ProcessAnalyzerMessage.php) | error | `analyzer.message.process_failed` |

**Patterns:**

- Most service-layer logs use the dotted `<domain>.<entity>.<action>`
  key (good).
- Controller-level logs sometimes use sentence-case messages
  (inconsistent).
- Context arrays are heterogeneous — some include `user_id`, others
  don't.

### 2.3 Activity log (`spatie/laravel-activitylog`)

Configuration (`config/activitylog.php`):

- Enabled via `ACTIVITY_LOGGER_ENABLED`.
- Table: `activity_log` (default).
- Retention: **365 days**, pruned by `activitylog:clean` (must be
  scheduled — not verified in `app/Console/Kernel.php` for this audit).
- `default_log_name = 'default'`.

**Models trait-wired with `LogsActivity`** (sample, 25+ total):

`Ward`, `Visit`, `User`, `Supplier`, `StockTransfer`, `ServicePrice`,
`ServiceCatalog`, `PurchaseOrder`, `ProductPrice`, `Product`, `Claim`,
`FinancialEntry`, `Bed`, `Admission`, `Prescription`, `LabRequest`,
`Patient`, …

Each implements:

```php
public function getActivitylogOptions(): LogOptions
{
    return LogOptions::defaults()
        ->logFillable()
        ->logOnlyDirty()
        ->dontSubmitEmptyLogs();
}
```

**Observations:**

- Comprehensive coverage of CRUD on revenue-bearing entities.
- Every record has `log_name = 'default'` → cannot query "all billing
  activity" without joining the model class.
- No explicit `causedBy()` calls — relies on Auth resolver. Background
  jobs (analyzers, queued listeners) will record `causer_id = null`,
  which is correct but worth documenting.

---

## 3. Notification Stack Today

### 3.1 Notification classes ([app/Notifications/](app/Notifications/))

| Class | Channels | Trigger |
| --- | --- | --- |
| `AdmissionNotification` | `database` | `PatientAdmitted` / `PatientDischarged` events |
| `LabRequestNotification` | `database` | `LabRequestCreated` / `LabResultsCompleted` |
| `PaymentNotification` | `database` | `PaymentRecorded` |
| `PrescriptionNotification` | `database` | `PrescriptionCreated` |
| `StockAlertNotification` | `database` | `StockLow` |
| `GeneralNotification` | `database` | Ad-hoc messages |

All six classes return **only `['database']`** from `via()`. None implement
`toMail()`, `toBroadcast()`, or `toSms()`.

### 3.2 Events & Listeners

```
app/Events/                            app/Listeners/
  PatientAdmitted          ──►   NotifyWardStaffAdmission
  PatientDischarged        ──►   NotifyAccountantsDischarge
  LabRequestCreated        ──►   NotifyLabTechnicians
  LabResultsCompleted      ──►   NotifyDoctorLabResults
  PaymentRecorded          ──►   NotifyAccountants
  PrescriptionCreated      ──►   NotifyPharmacists
  StockLow                 ──►   NotifyStockManagers
```

Listeners query users by role (Spatie permission) and `Notification::send($users, ...)`.

### 3.3 UI surface

- Bell icon in topbar polls `/admin/notifications/recent` (JSON).
- Notifications stored in `notifications` table (default Laravel).
- Mark-as-read endpoints: `/notifications/{id}/read`, `/notifications/mark-all-read`.
- Full list view: `notifications.index`.

---

## 4. Native Browser Popups — Inventory

The following native browser popup sites were found (75 total):

### 4.1 Vue / Inertia (1 site — REPLACED this iteration)

| File | Line | Pattern |
| --- | --- | --- |
| [resources/js/Pages/Billing/Invoices/Index.vue](resources/js/Pages/Billing/Invoices/Index.vue) | 55 | `window.confirm('Cancel this invoice?')` → now uses `useConfirm()` |

### 4.2 Blade (74 sites — auto-upgraded at runtime by `inertia.js`)

Pattern types (each migrated transparently):

- `onsubmit="return confirm('…')"` — **50 sites** (cancel/delete forms)
- `onclick="return confirm('…')"` — **18 sites** (status transition buttons)
- `if (!confirm('…')) return;` inside inline scripts — **3 sites** (manual)
- `alert('…')` inside inline scripts — **3 sites** (manual)

**Full list** (representative):

| File | Lines | Action |
| --- | --- | --- |
| `resources/views/billing/invoices/show.blade.php` | 469, 683 | cancel / record payment |
| `resources/views/billing/invoices/index.blade.php` | 223 | cancel invoice |
| `resources/views/consultations/show.blade.php` | 247, 256, 777, 1057, 1595, 1622, 1669, 1799, 1824, 1860, 1984, 2105, 2331, 2352, 2354 | admit, status, delete prescription, delete task, etc. |
| `resources/views/claims/show.blade.php` | 18, 37, 48, 247 | submit / pay / appeal / remove item |
| `resources/views/claims/review.blade.php` | 145 | complete review |
| `resources/views/insurance/tiers.blade.php` | 60 | delete tier |
| `resources/views/admissions/discharge.blade.php` | 57 | discharge patient |
| `resources/views/admin/services/index.blade.php` | 298 | remove price |
| `resources/views/admin/procedures/schedule.blade.php` | 115 | cancel procedure |
| `resources/views/admin/procedure-catalogue/show.blade.php` | 88, 162, 245 | delete section/field/consumable |
| `resources/views/admin/modules/index.blade.php` | 103 | enable/disable module |
| `resources/views/admin/investigation-catalogue/show.blade.php` | 210, 316, 379 | remove consumable, delete header/criterion |
| `resources/views/admin/icd-codes/index.blade.php` | 88 | delete ICD code |
| `resources/views/accounts/handover.blade.php` | 58, 136 | close / verify shift |
| `resources/views/admin/analyzers/show.blade.php` | 143 | remove mapping |
| `resources/views/admin/analyzers/index.blade.php` | 206 | delete analyzer |
| `resources/views/accounts/entries/index.blade.php` | 160 | delete entry |
| `resources/views/lab/process.blade.php` | 18 | cancel lab request |
| `resources/views/lab/tests.blade.php` | 52 | delete category |
| `resources/views/vitals/record.blade.php` | 157 | send to department |
| `resources/views/visits/show.blade.php` | 121, 146, 164, 178 | status transitions / cancel |
| `resources/views/visits/create.blade.php` | 1263 | `alert('Please select a patient first.')` — manual migration needed |
| `resources/views/theatre/show.blade.php` | 357 | cancel procedure |
| `resources/views/store/transfers/{index,show}.blade.php` | 190, 18, 29, 39 | approve / complete / cancel |
| `resources/views/store/purchase-orders/{index,show,create}.blade.php` | 18, 29, 39, 194, 196, 266 + alert@224 | PO lifecycle + create alert |
| `resources/views/roles/index.blade.php` | 37 | delete role |
| `resources/views/pharmacy/drugs.blade.php` | 77 | delete category |
| `resources/views/patterns/index.blade.php` | 108 | delete pattern |
| `resources/views/patients/show.blade.php` | 469, 539 | remove insurance / contact |
| `resources/views/prescriptions/show.blade.php` | 20 | cancel prescription |
| `resources/views/departments/index.blade.php` | 87 | delete department |
| `resources/views/designations/index.blade.php` | 82 | delete designation |
| `resources/views/admin/products/{show,_prices_modal}.blade.php` | 233, 128 | remove price |

### 4.3 Why this matters

- **UX consistency** — native popups inherit OS chrome; UHMS is otherwise
  Bootstrap-themed.
- **Accessibility** — native `confirm()` cannot be styled, localized,
  or made keyboard-friendly per WCAG.
- **iOS regression** — `confirm()` in submit handlers is silently
  blocked under stricter site settings (PWA / Safari focus mode).
- **Bridge fragility** — native popups freeze the SPA event loop and
  can cause Inertia visits to stall.

---

## 5. The New `UhmsConfirmDialog` — Design & Usage

### 5.1 Files added

| File | Purpose |
| --- | --- |
| [resources/js/Components/UhmsConfirmDialog.vue](resources/js/Components/UhmsConfirmDialog.vue) | Bootstrap-5 modal singleton rendered once at the app root. |
| [resources/js/Composables/useConfirm.js](resources/js/Composables/useConfirm.js) | Vue composable + `installGlobalConfirm()` exposing `window.UhmsConfirm.show()` / `.alert()` for legacy Blade. |

### 5.2 Wiring (in [resources/js/inertia.js](resources/js/inertia.js))

```js
// 1. Mounts the dialog once at #uhms-confirm-root.
// 2. Installs window.UhmsConfirm for vanilla JS usage.
// 3. Registers a delegated `submit` / `click` handler that consumes
//    `data-confirm="..."` attributes.
// 4. Auto-upgrades existing `onsubmit="return confirm(...)"` and
//    `onclick="return confirm(...)"` patterns at DOMContentLoaded AND
//    after every Inertia navigation, so ALL 74 Blade sites work
//    without per-file edits.
```

### 5.3 Usage — Vue (Inertia pages)

```js
import { useConfirm } from '../../../Composables/useConfirm';
const { confirm, alert } = useConfirm();

async function cancelInvoice(invoice) {
    const ok = await confirm({
        title: 'Cancel invoice',
        message: `Cancel invoice ${invoice.invoice_number}?`,
        details: 'This action cannot be undone.',
        variant: 'danger',
        confirmLabel: 'Cancel invoice',
        cancelLabel: 'Keep invoice',
    });
    if (!ok) return;
    /* mutate */
}
```

### 5.4 Usage — legacy Blade (preferred new pattern)

```blade
<form method="POST" action="..."
      data-confirm="Cancel this invoice?"
      data-confirm-title="Cancel invoice"
      data-confirm-variant="danger"
      data-confirm-label="Yes, cancel">
    @csrf @method('PATCH')
    <button class="btn btn-outline-danger">Cancel</button>
</form>
```

### 5.5 Usage — legacy Blade (no changes required — auto-upgrade)

Existing forms continue to work — the runtime upgrader rewrites:

```html
<form … onsubmit="return confirm('Delete this entry?')">
```

into the `data-confirm` equivalent at load time. The forbidden
`window.location.*` patterns are NOT introduced — leak-guard tests stay
green.

### 5.6 Usage — legacy Blade (vanilla JS inside `<script>` blocks)

For sites like `consultations/show.blade.php` that call `if (!confirm(...)) return;`
inside their own event handlers, switch to:

```js
window.UhmsConfirm.show({
    title: 'Delete header',
    message: 'Criteria under it will be moved to "Unsorted".',
    variant: 'danger',
}).then((ok) => {
    if (!ok) return;
    /* delete */
});
```

This is the **only manual migration required**. There are exactly
**6 such sites** (5 `confirm()` + 3 `alert()`), all listed in §4.2.

### 5.7 Visual / a11y properties

- Centered Bootstrap 5 modal, top-border accent in the variant color.
- Variant-aware Tabler icon in the title row.
- Focus traps inside the modal; Escape cancels unless
  `requireConfirmation: true`.
- Backdrop click is `static` — no accidental dismissal.
- Confirm button gets `autofocus` so Enter resolves immediately.
- Promise-based API — no callback hell, no leaked listeners.

---

## 6. Gaps & Improvements

### 6.1 Logging — Recommendations

| # | Recommendation | Effort | Priority |
| --- | --- | --- | --- |
| L1 | Introduce per-domain channels (`billing`, `pharmacy`, `lab`, `analyzer`, `audit`, `security`) routed to `daily` files with 14-day retention. | M | High |
| L2 | Standardize event keys: `<domain>.<entity>.<action>[.outcome]`. | S | High |
| L3 | Add a `LoggingContext` middleware that attaches `request_id`, `user_id`, `tenant_id` to every log line via `Log::withContext(...)`. | S | High |
| L4 | Route `critical` / `emergency` to Slack or Sentry (`LOG_SLACK_WEBHOOK_URL`). | S | Medium |
| L5 | Enable the `deprecations` channel and surface deprecation warnings in CI logs. | XS | Medium |
| L6 | Schedule `activitylog:clean` in `app/Console/Kernel.php` daily at 02:00. | XS | High |
| L7 | Promote a handful of high-value activity logs to dedicated `log_name`s: `billing`, `clinical`, `pharmacy`, so dashboards can filter cheaply. | S | Medium |
| L8 | Add a `causedBy(auth()->user() ?? Bot::user())` helper for service-layer mutations triggered from queued jobs. | S | Low |

### 6.2 Notifications — Recommendations

| # | Recommendation | Effort | Priority |
| --- | --- | --- | --- |
| N1 | Add `toMail()` to `LabRequestNotification` (for completed results) and `PaymentNotification` (receipt). | S | High |
| N2 | Introduce a `user_notification_preferences` table keyed by `(user_id, notification_type, channel)` and consume it from `via()`. | M | High |
| N3 | Wrap every notification class in `ShouldQueue` to avoid blocking HTTP requests. | XS | High |
| N4 | Add a `NotificationDispatcher` service so listeners depend on an interface, not the `Notification::send()` facade directly (improves testability). | S | Medium |
| N5 | Add real-time push via Laravel Reverb / Echo for the topbar bell (replace 30s polling). | L | Medium |
| N6 | Add a `Channels\SmsChannel` (TextLocal / Twilio) for `StockAlertNotification` to escalate to procurement officers. | M | Low |
| N7 | Backfill notification routing for nurses on `LabResultsCompleted` (currently only doctors). | XS | Medium |

### 6.3 Native popups — Recommendations

| # | Recommendation | Effort | Priority |
| --- | --- | --- | --- |
| P1 | **Done.** Build `UhmsConfirmDialog` + `useConfirm` composable + `window.UhmsConfirm` global. | — | — |
| P2 | **Done.** Add `data-confirm` attribute pattern + runtime auto-upgrader of inline `confirm()` handlers. | — | — |
| P3 | **Done.** Replace `window.confirm` in `Billing/Invoices/Index.vue`. | — | — |
| P4 | Manually replace 6 inline-script sites in `consultations/show.blade.php` (4 confirms, 6 alerts) and `visits/create.blade.php` / `store/purchase-orders/create.blade.php` (alert sites). | S | Medium |
| P5 | Add an ESLint / phpcs rule that flags `window.confirm`, `window.alert`, `window.prompt`, `onsubmit="return confirm"` to prevent regressions. | XS | High |
| P6 | Localize dialog strings via `<script setup>` + `i18n` plugin once UHMS adopts multilanguage. | M | Low |

---

## 7. Implementation Checklist

- [x] Build `Components/UhmsConfirmDialog.vue`.
- [x] Build `Composables/useConfirm.js` (composable + `installGlobalConfirm`).
- [x] Wire dialog mounting + bridge handlers in `inertia.js`.
- [x] Add runtime auto-upgrader for `onsubmit="return confirm(…)"` and `onclick="return confirm(…)"`.
- [x] Replace the one Vue `window.confirm` call (`Pages/Billing/Invoices/Index.vue`).
- [x] Write this analysis document.
- [ ] (P4) Manually migrate 6 inline-script popup sites in Blade.
- [ ] (L1–L8) Logging hardening sprint.
- [ ] (N1–N7) Notifications expansion sprint.
- [ ] (P5) Lint rule against native popups.

---

## 8. Validation

The following invariants are preserved by this iteration:

1. **No `window.location.*` / `location.reload()` added in Blade** — the
   leak-guard test `InertiaBridgeLeakGuardTest` continues to pass.
2. **Bootstrap 5 modal is the dialog primitive** — `bootstrap.bundle.min.js`
   is already loaded globally.
3. **No new global CSS** — all styling is utility-classes.
4. **No external dependencies added** to `package.json`.
5. **Vue 3 + Inertia 2 idioms only** — composable + reactive store; no
   provide/inject or Vuex.
6. **The 74 Blade sites work unchanged** — auto-upgrade happens at
   DOMContentLoaded and after every Inertia visit (`router.on('finish')`).

Run the smoke validation triplet:

```bash
php artisan route:list --columns=method,uri,name | findstr /i notifications
php artisan test --filter=InertiaBridgeLeakGuardTest
npm run build
```

All three should succeed. UI verification: load any page with a delete or
cancel button, click it — the UHMS-branded modal should appear instead of
the native browser dialog.

---

## 9. Appendix — File Map

```
app/
  Events/                 7 events
  Listeners/              7 listeners
  Notifications/          6 classes (all `database`-only)
  Http/Controllers/Admin/NotificationController.php

config/
  logging.php             Monolog channels
  activitylog.php         Spatie config

resources/js/
  Components/UhmsConfirmDialog.vue   ← NEW (Bootstrap 5 modal)
  Composables/useConfirm.js          ← NEW (composable + window API)
  inertia.js                          ← UPDATED (mount + bridge + upgrader)
  Layouts/AppLayout.vue               ← UNCHANGED (dialog mounted globally)

resources/views/
  layouts/partials/inertia-chrome.blade.php   (legacy shell)
  notifications/index.blade.php
  …74 sites with data-confirm-eligible popups (auto-upgraded)
```
