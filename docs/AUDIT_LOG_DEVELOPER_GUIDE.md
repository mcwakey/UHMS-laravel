# Audit Log Developer Guide

This guide explains how UHMS application code should integrate with the audit
logging system. Two log surfaces co-exist:

| Surface | Stored in | Purpose |
| --- | --- | --- |
| Domain status logs (`visit_status_logs`, `medication_administration_logs`, …) | Custom tables | Per-record clinical timelines |
| **Spatie activity log** (`activity_log`) | Single shared table | Tamper-evident audit trail (compliance) |

This guide covers the second surface, which is consumed by `/admin/logs`.

---

## 1. Two ways to write to the audit trail

### 1a. `LogsActivity` trait (CRUD)
Use for every model whose lifecycle (create / update / delete) should be
recorded automatically. Configure with `getActivitylogOptions()`:

```php
public function getActivitylogOptions(): LogOptions
{
    return LogOptions::defaults()
        ->logOnly(['name', 'status', 'price'])
        ->logOnlyDirty()
        ->useLogName(LogModule::PHARMACY->value)
        ->dontSubmitEmptyLogs();
}
```

The trait is enabled on 21 models already. New models should follow the same
pattern unless writes happen exclusively through a service (then use 1b).

### 1b. `ActivityLogService::log()` (workflow events)
Use for anything that is **not** a plain row CRUD: workflow transitions,
financial corrections, security events, integrations, etc.

```php
app(ActivityLogService::class)->log(
    LogModule::BILLING,
    'PAYMENT_RECORDED',
    [
        'severity' => LogSeverity::INFO,
        'invoice_id' => $payment->invoice_id,
        'patient_id' => $payment->patient_id,
        'metadata' => ['amount' => $payment->amount, 'method' => $payment->payment_method],
    ],
    $payment,
    'Payment recorded'
);
```

Helpers — prefer these when applicable:
- `logCreated(Model, LogModule)`
- `logUpdated(Model, LogModule, $old, $new)`
- `logCorrection(Model, LogModule, $before, $after, $reason)` — emits severity `WARNING`
- `logDeleted(Model, LogModule, $reason)`
- `logSecurity(string $action, array $data, ?Model $subject)` — auto severity `SECURITY`

---

## 2. Severity ladder

| Level | When to use |
| --- | --- |
| `DEBUG` | Diagnostic noise, never shown to ops |
| `INFO` | Normal expected events (default) |
| `NOTICE` | Routine but worth surfacing (settings updates, scheduled jobs) |
| `WARNING` | Reversible undesirable state (low stock, voided invoice) |
| `ERROR` | Failed workflow that needs follow-up |
| `CRITICAL` | Major incident (data corruption, outage) — forwarded to Slack/SIEM |
| `SECURITY` | Authn/authz events, RBAC changes, PHI exports — always synchronous |

Critical and security rows are forwarded to external sinks (see §6).

---

## 3. Required context keys

Always include the identifiers a SOC analyst needs to pivot on:

| Domain | Keys |
| --- | --- |
| Clinical | `patient_id`, `visit_id`, `admission_id` (when relevant) |
| Financial | `invoice_id`, `payment_id` |
| Pharmacy/stock | `drug_id`, `stock_location_id` |
| Security | `user_id` / inferred causer, `ip` (auto), `user_agent` (auto) |

Pass them as top-level keys on the `$data` array; the service moves
non-reserved keys into `properties.metadata.context`.

---

## 4. Sensitive data masking

`ActivityLogService::sanitise()` is applied automatically to `old_values`,
`new_values`, and `metadata`. Fields matching any of:

```
password, password_confirmation, token, api_token, remember_token,
secret, authorization, ssn, national_id
```

are replaced with `***MASKED***`. Add new sensitive field names to
`ActivityLogService::SENSITIVE_FIELDS`.

Never inline raw PHI/PII into `description`. Keep descriptions terse and
reference the subject via the activity row's `subject_id`.

---

## 5. Asynchronous writes

When `AUDIT_LOG_ASYNC=true`, `ActivityLogService::log()` dispatches
`ProcessActivityLogJob` instead of writing synchronously. Security-severity
rows always stay synchronous so audit trails remain hot-path consistent
with the request that produced them. Wire the queue with:

```env
AUDIT_LOG_ASYNC=true
AUDIT_LOG_QUEUE=audit
QUEUE_CONNECTION=redis
```

---

## 6. External sink forwarding

Critical/security rows are forwarded by `ForwardCriticalActivityListener`
(bound to `eloquent.saved: Activity`). Each sink is queued:

| Sink | Env switch | Job |
| --- | --- | --- |
| Slack | `AUDIT_SLACK_ENABLED=true` + `AUDIT_SLACK_WEBHOOK_URL` | `ForwardActivityToSlackJob` |
| SIEM (HEC/Elastic) | `AUDIT_SIEM_ENABLED=true` + `AUDIT_SIEM_ENDPOINT` + token | `ForwardActivityToSiemJob` |
| S3 long-term archive | `AUDIT_ARCHIVE_ENABLED=true` (uses default S3 disk) | `ArchiveActivityLogJob` |

All three are no-ops without env config, so the system runs cleanly in dev.

---

## 7. Logging failures (meta-logging)

If `activity()->log()` throws (DB down, etc.), the service writes a fallback
warning to the dedicated `activity_failures` channel
(`storage/logs/activity_failures-YYYY-MM-DD.log`, 30-day rotation). Configure
via `LOG_ACTIVITY_FAILURES_CHANNEL`.

---

## 8. Permission scoping at the viewer

`/admin/logs` filters visible modules by user permission:

| Permission | Allows viewing |
| --- | --- |
| `logs.view` | All log_names not gated by sub-perms |
| `logs.view_clinical` | `PATIENT`, `ADMISSION`, `PHARMACY`, `INVESTIGATION`, `EMERGENCY` |
| `logs.view_financial` | `BILLING`, `PAYMENTS`, `INSURANCE`, `CLAIMS` |
| `logs.view_stock` | `STOCK`, `PROCUREMENT` |
| `logs.view_security` | `AUTH`, `USERS`, `ROLES`, `PATIENT_MERGE` |
| `logs.export` | CSV/JSON exports |
| `logs.manage_retention` | `/admin/logs/retention` |

`ActivityLogController::filterByScope()` is the single source of truth.

---

## 9. Retention

Default: `LOG_RETENTION_DAYS=365`. Per-module overrides live in
`log_retention_overrides` and are managed under **Admin → Logs → Retention**.
The `logs:cleanup` schedule purges expired rows daily at 02:45.

---

## 10. Observer-based logging

Some models log through a hand-written observer rather than the Spatie trait
to capture richer context:

- `InvoiceObserver` — invoice status transitions (cancel/refund/void ⇒ WARNING)
- `PaymentObserver` — explicit `PAYMENT_RECORDED` / `PAYMENT_REFUNDED`
- `UserObserver` — status/email/department changes + password change (SECURITY)

Register additional observers in `AppServiceProvider::boot()`.
