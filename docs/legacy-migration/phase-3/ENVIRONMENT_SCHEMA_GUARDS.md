# Environment and schema guards

Every operation verifies the foundation enabled flag, environment allow-list, production rejection, exact connection names, `DATABASE()`, database version, structural fingerprints, expected table/column counts and configuration fingerprint.

Classic is immutable policy: connection `legacy_uhms`, database `uuhms`, 55 tables and 479 columns. The approved Phase 1B structural fingerprint is `150fcf4783fcb8bdc25f7e17fe0ece5050955f0c68e7dd03651ee8bd58498977`.

The installed-target baseline captured on 2026-07-21 was non-production `uhms_clean`, MariaDB `10.4.32-MariaDB`, 335 tables, 5,347 columns and fingerprint `12e3a4c6ca80a0c1a54ea9267d9c4935453b7a36d27c7f8ad900062c1c12bff0`. Phase 3 requires explicit environment configuration rather than silently assuming that capture remains current.

Missing, mismatched or stale values fail closed with redacted diagnostics.

Known blocker: configured/observed host, port, TLS identity and server UUID (or approved MariaDB equivalent) are not pinned. Labels and schema fingerprints alone cannot prove the physical server is non-production. Foundation DDL remains unauthorized until this is implemented and tested.
