# Insurance extraction specifications

1. `uuhms.insurance`: full ordered snapshot, `INS_ID ASC`, initial chunk 2,000; all nine columns; protected key/content comparison for update/delete detection. Issue/expiry are business dates, never watermarks.
2. Patient projection: exactly `PAT_ID, Company, BillStatus` from the identical Phase 2C `PATIENT-EXT-001` snapshot; a separately timed patient extraction is forbidden.
3. Provider dependency: consume versioned Phase 2A `NK-004` / `EXTRACT-SETT_PRIVATE` outcomes.
4. Target collision snapshot: read-only aggregate/protected-token evidence for patient/provider groups, member collisions, provider/tier/verification state, constraints and fingerprint.

Every query records version/hash, snapshot/run coordinate, database/fingerprint and aggregate-only output. Full snapshot/hash comparison is required because insurance has no reliable timestamp. D-101 least-privilege SELECT/metadata credentials and fail-closed exact-`uuhms` guards are Phase 3 prerequisites.
