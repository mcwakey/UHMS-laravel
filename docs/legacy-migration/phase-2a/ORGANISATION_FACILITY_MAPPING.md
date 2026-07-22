# Organisation and Facility Mapping

## Evidence

[Confirmed] `hospitalinfo` has one row and 12 columns. Renewed UHMS has no first-class organisation/facility/site table; it stores a singleton organisation in unique `settings(group,key)` rows. The UI keys are name, email, phone, address, city, region, postal_code, website and logo.

## Column specification

| Classic column | Disposition | Target/consequence |
|---|---|---|
| `ID` | Crosswalk | protected source identity only |
| `CompanyName` | Configure | candidate `settings(group=organization,key=name)` after target-existing reconciliation |
| `LegalName` | Evidence-only | corroborates CompanyName in this snapshot; no separate legal-name key is approved |
| `Address`, `Email` | Configure | validate, then bind to approved settings keys |
| `PhoneNo` | Quarantine | observed length 21 exceeds the application limit and broad validation; never truncate or configure until remediated |
| `WebSite` | Evidence only | observed blank; create no setting from blank |
| `Slogan` | Deferred | no current approved destination |
| `ClaimOfficer` | Evidence only | protected private configuration evidence |
| `ClaimSignature` | Excluded | signature material is never automatically copied |
| `Admn` | Evidence only | meaning not evidenced |
| `PharmStore` | Deferred | observed value is not a valid boolean and meaning is unapproved; never infer a setting or stock location |

[Technical specification] Use a controlled settings adapter that preserves serialization and cache invalidation. Do not use the controller because logo handling writes files. No organisation value belongs in public reports.

## Facility boundary

[Confirmed] Neither source nor target evidence establishes a multi-facility entity crosswalk. Renewed generic `facility_id` or `branch_id` columns elsewhere do not create a facility master. Phase 2A must not collapse multiple future facilities into singleton settings.

Natural key: exact `(group='organization', key)`. Reconciliation: one source row, every column configured/evidence/deferred/excluded/quarantined, zero signatures/assets copied.
