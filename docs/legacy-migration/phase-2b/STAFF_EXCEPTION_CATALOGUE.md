# Staff exception catalogue

[staff_exception_codes.json](specifications/staff_exception_codes.json) defines 32 stable `LEGACY-STAFF-<CATEGORY>-<NUMBER>` codes. Each includes severity, trigger, scope, primary disposition, retryability, manual-review owner/SLA, release condition, reconciliation, blocking scope, security class and provenance. Permitted reviewed unknown attribution (`...-032`) is separate from prohibited unknown use (`...-018`).

Required categories are separately represented: missing/duplicate employment identity; ambiguous target; duplicate/blank username; unverified identity; invalid/unmapped department; ambiguous specialty; missing actor parent; zero sentinel; unsupported free-text actor; target uniqueness; existing-user conflict; password/permission exclusion; historical-security violation; unknown actor prohibited; chain quarantine; fingerprint drift; and extraction identity failure.

Additional codes govern excluded notification actor content, dormant staff-contact drift, hospital configuration/signature fields, ambiguous `MdfId` semantics and the all-zero ambiguous cashier identifier.

Operational SLA:

- security/fingerprint/extraction failures: immediate stop and owner review;
- critical match/target/chain failures: two business days;
- HR/reference/clinical attribution queues: five business days.

An SLA is a review objective, never permission to default, discard or invent an actor after expiry.
