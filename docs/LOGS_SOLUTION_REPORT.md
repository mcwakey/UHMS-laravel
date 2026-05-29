# Logs & Audit Trail — Solution Report

_Phase-Logs implementation against `prompt.md` (sections §3–§30)._

## 1. Strategy

Extend the existing `spatie/laravel-activitylog` instead of building a parallel
system. The `log_name` column carries the canonical **module** value (one of
`App\Enums\LogModule`). The `event` column carries the **action** verb. The
`properties` JSON carries `module`, `severity`, `reason`, structured `old` /
`attributes`, and a `metadata` bag. All writes go through a single
`ActivityLogService`, which also sanitises sensitive fields and never breaks
the parent request if delivery fails.

## 2. Files added

| File | Purpose |
|---|---|
| `app/Enums/LogModule.php` | 25-case canonical module list with `label()` and `color()` helpers. |
| `app/Enums/LogSeverity.php` | 7-level severity (`DEBUG`, `INFO`, `NOTICE`, `WARNING`, `ERROR`, `CRITICAL`, `SECURITY`) with `label()` and `color()`. |
| `app/Services/ActivityLogService.php` | Central write surface. Public methods: `log()`, `logCreated()`, `logUpdated()`, `logDeleted()`, `logCorrection()`, `logOverride()`, `logSecurity()`, plus public `sanitise()` and helper `buildProperties()`. |
| `app/Listeners/Auth/LogAuthEvents.php` | Bridges Laravel auth events (`Login`, `Logout`, `Failed`, `PasswordReset`) into `ActivityLogService::logSecurity()` with `module=AUTH`. |
| `resources/views/settings/activity-log-show.blade.php` | Detail page rendered by `admin.logs.show` — Overview / Context / Changes (old vs new diff) / Metadata / Raw Properties. |
| `tests/Feature/ActivityLogServiceTest.php` | 7 feature tests (see §6). |
| `docs/LOGS_GAP_ANALYSIS.md` | Companion analysis. |
| `docs/LOGS_REMAINING_RECOMMENDATIONS.md` | Follow-up backlog. |
| `docs/LOGS_SOLUTION_REPORT.md` | This document. |

## 3. Files modified

| File | Change |
|---|---|
| `app/Providers/AppServiceProvider.php` | Registered the four `Illuminate\Auth\Events\*` listeners on `LogAuthEvents`. |
| `app/Http/Controllers/Admin/ActivityLogController.php` | Full rewrite — DI of `ActivityLogService`, new filter set (search / module / severity / action / user / patient / visit / subject_type / date_from / date_to), permission-scoped `buildQuery()`, `show()` returning the new view, `export()` streaming CSV. |
| `routes/web.php` | Added a dedicated `admin.logs.*` route group (`index` / `show` / `export`) with `can:logs.view` and `can:logs.export` middleware, while **keeping** the legacy `admin.settings.activity-log` alias for backward compatibility. |
| `database/seeders/RoleSeeder.php` | Added 7 permissions (`logs.view`, `logs.view_clinical`, `logs.view_financial`, `logs.view_stock`, `logs.view_security`, `logs.export`, `logs.manage_retention`). `Super Admin` auto-syncs all via `Permission::all()`. |
| `app/Services/ProcedureWorkflowService.php` | Inject `ActivityLogService` and write a `PROCEDURE / CANCELLED` (severity `WARNING`) row on cancel, `PROCEDURE / COMPLETED` row on complete. Mirrors the existing `procedure_status_logs` write so the unified audit trail also reflects the transition. |
| `resources/views/settings/activity-log.blade.php` | Rewritten to consume `$modules`, `$severities`, `$filters`, `$totalCount`; module + severity badges in the table; "View" links to the new detail page; "Export CSV" button gated by `@can('logs.export')`. |

## 4. ActivityLogService API

```php
$logger = app(\App\Services\ActivityLogService::class);

// Generic write — used for any custom event:
$logger->log(
    LogModule::SETTINGS,
    'UPDATED',
    [
        'description' => 'Changed invoice tax rate',
        'severity'    => LogSeverity::NOTICE,
        'reason'      => 'Finance director request',
        'patient_id'  => null,
        'metadata'    => ['field' => 'tax_rate', 'from' => 16, 'to' => 18],
    ],
    subject: $settings,           // optional Eloquent model
    description: 'Tax rate update',
);

// Model lifecycle helpers — set severity automatically:
$logger->logCreated($patient, LogModule::PATIENTS);                  // INFO
$logger->logUpdated($patient, LogModule::PATIENTS, $old, $new, ...); // INFO, diffs only
$logger->logDeleted($patient, LogModule::PATIENTS);                  // WARNING
$logger->logCorrection($invoice, LogModule::BILLING, $reason);       // WARNING
$logger->logOverride($claim,    LogModule::CLAIMS,   $reason);       // WARNING

// Security events:
$logger->logSecurity('FAILED_LOGIN', [
    'severity' => LogSeverity::WARNING,
    'metadata' => ['email' => $request->input('email'), 'guard' => 'web'],
]);
```

### 4.1 Sensitive-field masking

`ActivityLogService::SENSITIVE_FIELDS` contains: `password`,
`password_confirmation`, `current_password`, `new_password`, `token`, `api_key`,
`api_secret`, `secret`, `remember_token`, `access_token`, `refresh_token`,
`card_number`, `cvv`, `pin`, `two_factor_secret`, `two_factor_recovery_codes`.

The recursive `sanitise()` helper masks matching keys to `***MASKED***` at any
nesting depth, applied to `old`, `attributes`, and `metadata` before they are
persisted.

### 4.2 Context capture

`buildProperties()` automatically pulls these keys from the caller's payload
into the top-level `properties` JSON so filters and detail pages can render
them: `patient_id`, `visit_id`, `admission_id`, `emergency_case_id`,
`department_id`, `invoice_id`, `claim_id`, `payment_id`,
`procedure_request_id`, `theatre_room_id`. It also captures the current
request `ip` and `user_agent` when available.

### 4.3 Failure isolation

The actual Spatie `activity(...)->log(...)` call is wrapped in `try / catch`.
Any exception is degraded to `Log::warning('ActivityLogService delivery failed')`
so audit-pipeline outages **never** propagate into clinical or billing
workflows.

## 5. Auth event wiring

In `AppServiceProvider::boot()`:

```php
Event::listen(\Illuminate\Auth\Events\Login::class,         [LogAuthEvents::class, 'handleLogin']);
Event::listen(\Illuminate\Auth\Events\Logout::class,        [LogAuthEvents::class, 'handleLogout']);
Event::listen(\Illuminate\Auth\Events\Failed::class,        [LogAuthEvents::class, 'handleFailed']);
Event::listen(\Illuminate\Auth\Events\PasswordReset::class, [LogAuthEvents::class, 'handlePasswordReset']);
```

Each handler invokes `ActivityLogService::logSecurity(...)` with module `AUTH`,
severity `SECURITY` (or `WARNING` for `FAILED_LOGIN`), and a metadata bag
containing `guard`, `user_id`, and `email`. Failed-login handler tolerates a
null user object.

## 6. Permissions & route guards

Permissions added via `RoleSeeder`:

```
logs.view              # Full unrestricted access
logs.view_clinical     # EMERGENCY / ADMISSION / CONSULTATION / MAR / CLINICAL_TASKS / INVESTIGATION / PROCEDURE / THEATRE
logs.view_financial    # BILLING / PAYMENTS / CLAIMS / INSURANCE
logs.view_stock        # STOCK / PHARMACY / PURCHASE_ORDERS / SUPPLIER_LEDGER
logs.view_security     # AUTH / USERS / ROLES / PERMISSIONS
logs.export            # CSV export
logs.manage_retention  # Reserved for future retention UI
```

Route guards (`routes/web.php`, inside the `/admin` prefix group):

```
GET  /admin/logs                  -> admin.logs.index   middleware: can:logs.view
GET  /admin/logs/export           -> admin.logs.export  middleware: can:logs.export
GET  /admin/logs/{activityLog}    -> admin.logs.show    middleware: can:logs.view
```

In `ActivityLogController::buildQuery()`, callers **without** `logs.view` and
not in the `Super Admin` role get a per-module allowlist computed from their
specific sub-permissions; a user with **no** `logs.*` permission gets
`whereRaw('1=0')` (empty result set, never an exception).

## 7. Viewer & detail page

### `admin.logs.index` — `resources/views/settings/activity-log.blade.php`
- Filters: search, module, severity, action, user_id, patient_id, visit_id,
  date_from, date_to.
- Module and severity rendered as coloured badges (driven by enum `color()`
  helpers).
- Pagination preserves query string.
- "Export CSV" button gated by `@can('logs.export')`.

### `admin.logs.show` — `resources/views/settings/activity-log-show.blade.php`
- **Overview** card: timestamp, module, action, severity, causer, subject,
  description, reason, IP, user-agent.
- **Context** card: only renders the patient/visit/etc. keys that were
  actually present in `properties`.
- **Changes** table: side-by-side `old` vs `attributes` diff, rendering scalar
  values directly and falling back to `json_encode` for arrays.
- **Metadata** + **Raw Properties** pretty-printed JSON blocks.

## 8. CSV export

`ActivityLogController::export()` streams via `StreamedResponse` with a
chunked cursor capped at 10 000 rows. The same `buildQuery()` permission
scoping applies, so a user can only export rows they are allowed to see.
The export itself is logged (`SETTINGS / EXPORTED`, severity `NOTICE`,
metadata contains the active filters).

Columns: Timestamp, Module, Action, Severity, Description, User, Subject,
Subject ID, Patient ID, Visit ID, Reason, IP.

## 9. Tests

`tests/Feature/ActivityLogServiceTest.php` — **7 passed (27 assertions)**:

```
✓ log writes module action severity to properties
✓ log updated captures old and new values diff only
✓ log updated skips when no change
✓ sanitise masks sensitive fields
✓ log security writes auth module with security severity
✓ log index requires logs view permission
✓ log export requires logs export permission
```

Regression sweep — **12 passed (43 assertions)**:

```
NotificationServiceTest      ✓ 7/7
TheatreRoomsManagementTest   ✓ 5/5
```

## 10. Verification commands

```powershell
# Tests
D:\xampp3\php\php.exe artisan test --filter=ActivityLogServiceTest --no-ansi
D:\xampp3\php\php.exe artisan test --filter=NotificationServiceTest --no-ansi

# Permissions (Super Admin auto-syncs all)
D:\xampp3\php\php.exe artisan db:seed --class=RoleSeeder

# Cache
D:\xampp3\php\php.exe artisan view:cache
D:\xampp3\php\php.exe artisan route:cache
```

## 11. Backward compatibility

- The legacy `admin.settings.activity-log` route name still resolves and uses
  the same updated view, so the settings sidebar link is unaffected.
- The 8 domain status-log tables and their dedicated services
  (`MedicationAdministrationLogService`, `MedicalRecordEntryLogService`) are
  untouched. They continue to serve their workflow-specific UIs.
- 21 existing `LogsActivity` models continue to auto-log; new code can now
  layer module-tagged entries on top via `ActivityLogService` when richer
  context (reason, severity, multi-key metadata) is needed.
