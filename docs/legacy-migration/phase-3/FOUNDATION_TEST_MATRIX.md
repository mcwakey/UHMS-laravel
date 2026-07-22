# Foundation test matrix

The focused suite exercises all mandatory foundation groups, including protected-store integrity, exact state transitions, repository-derived recovery evidence, durable crash/restart decisions, protected runtime audit, the 25-subsystem runtime registry and invocation-time guards, deterministic allocation and rollback, and privacy-safe no-domain-write boundaries.

All fixtures and identifiers are synthetic. The final recovery/runtime/allocation selection passes **136 tests and 567 assertions**. The separately enabled disposable MariaDB 10.4 allocator verification passes **1 test and 36 assertions** against the immutable 24-table foundation schema. It proves concurrent same/different coordinate behavior, deadlock retry, connection-loss and transaction-fault rollback, repository sealing and exact ordinal reconciliation. The server listener and process were absent after clean shutdown.

These focused counts are workstream evidence, not an importer authorization. Overall Phase 3B authorization remains subject to the four repeated independent reviews and the consolidated exit report.
