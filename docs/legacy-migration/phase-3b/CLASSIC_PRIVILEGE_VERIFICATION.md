# Classic privilege verification

Status: implemented; external D-101 account remains required.

All current Classic-accessing migration commands now use `SourceAccountVerifier`. `legacy-migration:capture-classic-evidence` opens a repeatable-read read-only transaction through `LaravelMetadataConnection`, verifies privileges, passes the resulting authority reference into evidence capture, and rolls the transaction back. The evidence service cannot run without that complete verification reference.

Verification inspects `SHOW GRANTS`, global, schema, table, column and routine privilege registries, the active role, wildcard/cross-schema scopes, grant option, proxy and role assignments. It accepts only `USAGE` globally and `SELECT` plus optional `SHOW VIEW` on exact `uuhms`. It also reads the exact base-table catalogue and proves effective SELECT coverage for every approved table: schema-wide `uuhms.*` SELECT covers all 55, while table-scoped accounts must enumerate all 55 exact names without missing, duplicate or unexpected tables. It rejects DML, DDL, administrative/global access, other schemas, wildcard schema names, routine execution, grant option, proxy and any assigned/active role. Safety is never tested by attempting a write. Reports expose only privilege names, counts and inspected surface labels.

No live Classic connection was accessed. Final execution remains blocked until the DBA supplies the dedicated D-101 account and the verifier passes on the active exact-`uuhms` connection.
