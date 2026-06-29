# Journey Supervisor Routing (Phase 9.7)

Precise escalation routing so the *right* person is notified — the department
supervisor — instead of broadcasting to all eligible staff.

## Department fields

`departments.supervisor_user_id` and `departments.escalation_user_id` (both nullable,
indexed). Managed on the **Department edit** form; only **active** users are
selectable and the rule rejects inactive ones. Changes are audited
(`JOURNEY_DEPARTMENT_SUPERVISOR_CHANGED`). Multi-supervisor support is intentionally
deferred — these single fields keep the form simple; a future pivot can layer on top.

## Resolver — `App\Services\Journey\JourneySupervisorResolver`

- `supervisorForDepartment($department)` → supervisor (or escalation user) if active.
- `escalationRecipientsFor($toDeptId, $toType, $level)` → ordered, deduped, capped
  recipients.
- `fallbackEligibleRecipients($toDeptId, $toType)` → eligible destination staff.
- `oversightUsers()` → active holders of `journey.oversight` (bounded).

### Fallback order

```
1. destination department supervisor
2. destination escalation user
3. journey.oversight users        (critical only, if route_critical_to_oversight)
4. eligible destination staff     (if no supervisor/oversight resolved)
5. none
```

Always: active-only, capped at `escalation_policy.max_recipients` (8),
capability-scoped, never the acting user.

## Config — `config/journey.php`

```php
'escalation_policy' => [
    'notify_near_breach_unassigned' => true,
    'notify_breached_unassigned'    => true,
    'notify_critical_unassigned'    => true,
    'prefer_supervisor'             => true,
    'fallback_to_eligible_staff'    => true,
    'route_critical_to_oversight'   => true,
    'max_recipients'                => 8,
],
```

## Oversight permission — `journey.oversight`

Granted to Super Admin & Admin via `syncPermissions(all)`; intended also for
operations manager / medical director / matron. Holders receive **critical**
escalation notifications, can open the worklist **Oversight** tab (unassigned
near-breach+ handoffs across domains), and see cross-department coordination. The tab
is hidden and silently inaccessible for everyone else.
