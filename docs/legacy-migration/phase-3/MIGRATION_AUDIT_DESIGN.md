# Migration audit design

Migration audit is separate from operational activity logging. It records run/actor authority reference, command, state transition, mapping/transformation versions, failures, approvals, reconciliation and recovery using protected tokens and integrity checksums.

It never creates contemporaneous-looking Classic operational events and never attributes work to the current user, administrator, importer account or first user. Audit forwarding is suppressed in migration context. Audit entries are append-only and privacy-safe.
