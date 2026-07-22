# Staff security exclusion contract

The canonical zero-migration assertions are [security_exclusion_contract.json](specifications/security_exclusion_contract.json).

## Source exclusions

- `users.Password`: security-excluded; values are never exported, logged, hashed for evidence, compared or mapped.
- `users.CanAdd`, `CanEdit`, `CanDelete`, `CanPrint`: security-excluded; values are never exported and never derive target roles, permissions or privileges.
- Classic ACTIVE/INACTIVE does not become renewed authorization.
- Classic username is not a renewed credential.

## Required zeroes

- Classic password values mapped: 0.
- Classic permission flags mapped: 0.
- Historical identities with roles, permissions, active login, sessions, password-reset/invitation route, notification routing, department access or specialty privileges: 0.
- Welcome, verification, reset or historical notification events: 0.
- Existing renewed user fields changed from Classic evidence: 0.

Low-entropy staff names and usernames must never use plain SHA-256 fingerprints. Protected diagnostics and pre/post comparisons use purpose/domain-separated HMAC-SHA-256 with an environment-controlled key, key version and canonicalization version. Keys and cross-environment comparable fingerprints never enter source control.

## Runtime protections

Normal `UserService`, Eloquent events, `UserObserver`, Spatie activity logging, role/permission audit, auth/reset flows, notifications and external critical-audit forwarding are not migration creation paths. Phase 3 must design and test the migration-specific persistence and non-login representation before Phase 2B candidates can be created.
