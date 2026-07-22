# Classic account verification

D-101 is an external DBA prerequisite. The application verifies but never creates or alters the account.

The verifier inspects `DATABASE()`, server/session version and read-only state, `SHOW GRANTS`, and read-only metadata. It permits only exact-`uuhms` `SELECT` plus the minimum metadata visibility available inherently to the account. Global privileges, grant option, file/process/admin/DDL/DML privileges, wildcard database scope or access to another Classic schema block execution.

A read-only transaction executes metadata/constant `SELECT` statements only. The foundation never tests safety by attempting a write. Reports redact username, host and all credential material. Until a dedicated verified account is configured, migration execution remains blocked.
