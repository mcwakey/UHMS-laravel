# Target staff identity contract

## Installed target evidence

The actual local non-production target `uhms_clean` was inspected read-only.

| Structure | Relevant installed constraints/evidence |
|---|---|
| `users` | 17 rows, all active; required first_name, last_name, unique email, password and status; nullable unique employee_id; nullable department/designation; soft deletes |
| `employees` | 0 rows; required unique employee_number, first/last name, phone, position, hire date and status; optional unique user relationship is not database-unique |
| `department_user` | required user/department; historical date/context columns; no rows; operational access implications |
| `doctor_specialty` | required user/specialty; 5 installed rows; operational clinical implications |
| Roles/permissions | 26 roles, 891 permissions, 17 user-role assignments; no direct user-permission rows |
| Runtime state | 47 notifications and 2 sessions existed at capture; historical identities must receive neither |
| Audit | `User` uses LogsActivity; `UserObserver` emits creation/change/security audit |
| Authentication | login rejects non-active status after credential authentication |
| Password reset | reset broker/controller does not independently reject inactive status |
| Notifications | `User` is Notifiable; active-user recipient resolvers exist, but notification capability is not database-prohibited |

The installed catalogue contains 426 actual foreign keys to `users`: 53 are non-nullable and 373 nullable. It also contains 34 actor-shaped columns without a user FK, plus polymorphic actor identifiers in activity, notification and authorization structures. Application-level actor validation is therefore required even when the database accepts an ID. No `belongsTo(User::class)->withTrashed()` actor relationship was found; soft deletion can hide the actor while retaining the FK, and force deletion can cascade or null historical attribution.

## Exact representations

### Verified match to an existing renewed user

Retain the existing `users.id` in the protected crosswalk. Do not modify target credentials, roles, permissions, name, email, phone, status, department, specialties or employee data. Record conflicts as flags/hashes only. Active target access remains a renewed operational decision, not a Classic-derived grant.

### Verified Classic staff with no renewed user

Classification: `CREATE_DISABLED_HISTORICAL_IDENTITY_CANDIDATE`. Phase 2B creates no row.

A normal inactive user is not yet a sufficient contract: required email/password columns, already-authenticated sessions, and password-reset/notification/audit paths remain. Because downstream actor FKs point to `users.id`, the specified Phase 3 candidate is a hardened historical `users` identity with an enforced immutable historical/non-login marker, inactive status, unusable unretained generated secret, reserved non-routable internal email, auth-provider/session/reset/invitation/notification exclusions, retention protection, zero roles/permissions/access pivots and migration-specific observer isolation. It has no `employees` row unless independent HR/payroll requirements later justify all mandatory employee fields.

This is prerequisite `LEGACY-STAFF-TARGET-025`, not authorization to change schema. A materially different dedicated-identity/FK design would require separate architecture review.

### Unverified, orphan or free-text identity

No target actor link is created automatically. Preserve protected provenance and quarantine. The single Legacy Actor Unknown may be used only under [its field-specific contract](LEGACY_ACTOR_UNKNOWN_CONTRACT.md).

## Target invariants

- One Classic verified identity maps to no more than one target identity.
- One target identity receives no more than one Classic identity unless an explicit reviewed many-source-to-one employment contract exists.
- Existing target users are immutable from Classic staff evidence.
- Historical-only/unknown identities: inactive/non-login, no roles, permissions, sessions, reset/invitation path, notification routing, department access or clinical privileges.
- Normal operational services, observers and events are not historical creation APIs.

## Runtime side effects and unsafe fallbacks

- `UserService::create()` hashes credentials, may write avatar files, assigns roles and synchronizes specialties.
- `UserObserver`, Spatie activity logging, auth events and external critical-audit forwarding can create contemporaneous/security records.
- `NotificationService::notifyUser()` does not universally reject inactive users; lab/workflow listeners can notify attributed clinicians.
- Normal login checks status only after credential authentication; existing sessions are not globally revalidated and password reset does not independently reject inactive users.
- Many services stamp the current authenticated user. Accounting posting code contains first-user fallbacks. Both are prohibited for historical attribution.
- `HRService` generates present-day employee numbers and employee rows require unevidenced HR/payroll fields.

Phase 3 persistence must bypass or suppress these paths without weakening normal production behavior.
