# Foundation security model

Approved policy requires least privilege, explicit authority, domain separation and fail-closed operation.

- The Classic account must have only `SELECT` and approved metadata visibility on exact `uuhms`; any write/admin/global grant blocks execution.
- HMAC inputs use a versioned typed length-prefix encoding. The API requires a declared token domain, key identifier/version and canonicalization version.
- Secrets are supplied outside source control. Missing or unknown key material blocks token creation; diagnostics reveal neither input nor key.
- Protected payloads use application encryption at rest and are excluded from ordinary serialization.
- Commit, importer and Cohort B flags remain independently false. Enabling the foundation does not grant commit authority.
- `ProtectedStoreAccessGuard` proves domain/environment/key/canonicalization and keyed-seal behavior in isolation, but is not yet integrated into every repository read/write. All non-test protected-store writes and all deletes are therefore Phase-3 blocked pending that integration and an approved retention/purge authority. No web/UI surface is added here.
