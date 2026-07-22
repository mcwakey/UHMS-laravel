# Migration audit design

Migration audit is separate from operational activity logging. It records run/actor authority reference, command, state transition, mapping/transformation versions, failures, approvals, reconciliation and recovery using protected tokens and integrity checksums.

It never creates contemporaneous-looking Classic operational events and never attributes work to the current user, administrator, importer account or first user. Audit forwarding is suppressed in migration context. Audit entries are append-only and privacy-safe.

Runtime activation is fail-closed without a verified protected audit session. The repository records activation request, isolation activation, denied-effect aggregates, operation failure and restoration outcome against the sealed run and target-snapshot coordinate. Payloads contain only protected references and aggregate facts.
