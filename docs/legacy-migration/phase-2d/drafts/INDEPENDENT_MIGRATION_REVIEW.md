# Independent migration review — Phase 2D

Review date: 2026-07-21  
Reviewer role: independent `migration_reviewer`  
Verdict: **PASS for Phase 2D specification exit and Phase 2E specification entry. No importer or target persistence is authorised.**

## Severity findings

### Critical

None.

### High

None after correction.

The initial review found that most machine mapping rules did not deterministically link outcomes to the stable exception catalogue. The consolidated specifications now carry explicit blank, invalid, overlength, parent, extraction, privacy and existing-target outcome/exception references, and the targeted test requires per-domain coverage. Evidence: `specifications/nok_tuple_rules.json`, `occupation_rules.json`, `address_rules.json`, `religion_value_crosswalks.json`, `marital_status_value_crosswalks.json`, and `Phase2DSpecificationConsistencyTest::every_field_and_tuple_outcome_has_stable_exception_coverage`.

### Medium

None after correction.

The initial NOK-relationship rule allowed two incompatible dispositions. The normative rule is now deterministic: the installed target is nullable free text; structurally valid values are preserved without enum conversion, typo repair or `Other` fallback; blank becomes null; structural failure withholds the tuple. Evidence: `NOK_CONTACT_TUPLE_CONTRACT.md:21`, `specifications/nok_tuple_rules.json` (`PATIENT-CHILD-NOK-001`, `...-009`), and `LEGACY-PATIENT-CHILD-CONTACT-009`/`...-010` in the exception catalogue.

### Low

1. The current PHI assertion in `Phase2DSpecificationConsistencyTest::existing_target_and_privacy_assertions_are_fail_closed` verifies the specification's `contains_raw_phi` declaration rather than scanning every Phase 2D artifact against an allow-list. Manual review found no raw patient key, OPD, address, NOK name/phone, row-level token, or phone-shaped literal; the Classic evidence contains aggregates and approved safe categories only. A future artifact-level regression guard would make that evidence stronger. This does not block Phase 2D because the package was manually inspected and the shared evidence tests still enforce categorical collapse and temporal redaction.

2. `drafts/TARGET_PATIENT_CONTACT_DISCOVERY_DRAFT.md:71` records that free-text NOK relationships outside the seven UI choices may display but may not round-trip through the current select control. The normative preservation rule is safe and default-free, but Phase 3 target prerequisites should explicitly retain this UI round-trip check before contact persistence is authorised. It does not affect Phase 2E insurance specification work.

## Directive verification

| Review item | Result | Evidence |
|---|---|---|
| Exactly seven deferred columns | Pass | `patient_child_column_mappings.json` contains exactly `Work`, `Address`, `NOK`, `NOKPhoneNo`, `NOKRel`, `Religion`, `MaritalStatus`; targeted test passes. |
| Phase 2E and clinical exclusions | Pass | `Company`/`BillStatus` and `Allergies`/`Medication`/`History` are explicit exclusions; `LastVisit`, `Refill`, `OriginalName`, `OriginalOpd`, `PhoneNo`, `PatientName`, `DOB`, and `Sex` remain outside the mapping. |
| Phase 2C identity and alias unchanged | Pass | `ALIAS_CHILD_INTEGRATION_CONTRACT.md` delegates to `LEGACY_OPD_ALIAS_CONTRACT`, `PATIENT-ALIAS-001..017`, `legacy-opd-comparison-v1`, and `PATIENT-REC-ALIAS-002`; Phase 2D-created aliases and alternate canonicalizers must equal zero. |
| NOK treated as one tuple | Pass | One source tuple and mutually exclusive source/runtime partitions are defined; the captured presence equation is `14,578 + 1,001 + 1,371 = 16,950`. |
| Optional child is nonblocking | Pass | Invalid/absent contact and invalid demographic values are child/field-local after parent success; privacy/provenance root failures remain chain-blocking. |
| Parent quarantine and release | Pass | Every child inherits `PATIENT-PRIV-007`; no release precedes the protected parent map; reassignment and artificial parents reconcile to zero. |
| Existing-target immutability | Pass | Fourteen rules prohibit mutation of demographics, locality fields, contacts, primary state, aliases, timestamps and activity; comparison evidence is protected only. |
| No demographic/contact matching | Pass | Name, phone, address, occupation, religion, marital status and NOK similarity are prohibited for linkage, merge, alias ownership and cross-patient deduplication. |
| NOK relationship free text | Pass | No unsupported enum or `Other` fallback; structurally valid normalized display text is preserved with provenance. |
| Address and occupation safety | Pass | Address maps only to unstructured `patients.address`; no parsing/geocoding/locality inference. `Work` remains nullable free text; empty `sett_ocuupation` is not populated or recreated. |
| Religion and marital mapping | Pass | Count-backed, explicit and default-free crosswalks cover every observed safe category; unknown nonblank drift is withheld. |
| Reconciliation completeness | Pass | One NOK tuple partition and one partition for each of occupation, address, religion and marital status cover all 16,950 source rows with required difference zero. |
| Extraction coordination | Pass | `PATIENT-CHILD-EXT-001` is a projection of `PATIENT-EXT-001`, ordered `PAT_ID ASC`; the recorded SQL SHA-256 was independently reproduced as `db20d1995d2c525e8f3efc90a8170581a2ed0832e122623e893467e0dd3722bc`. |
| Source/target evidence | Pass | Guarded Classic evidence is exact `uuhms`, 55 tables/479 columns/fingerprint protected; installed non-production target fields, FK/index/nullability, absent checks/uniqueness/soft delete/one-primary invariant, dormant inline fields and runtime hazards are documented. |
| Privacy and safety | Pass | Aggregate-only evidence, domain-separated HMAC requirements and zero raw-artifact/notification/SMS assertions are present. No raw contact/address values were found. |
| Deliverable integrity | Pass | All 21 required documents and 16 required JSON specifications exist; every JSON file parses, has version `2D.1.0`, exact source `uuhms`, `implementation_authorized=false`, and a correct record count. Cross-reference scan found no unresolved contract identifier. |
| No implementation/write behavior | Pass | Phase 2D changes are limited to documentation/specification files plus one documentation/evidence consistency test. No importer, migration/state table, schema/UI/seeder/synchronization/runtime code or database-writing statement was introduced. |

## Validation performed

- Read `AGENTS.md`, the complete 983-line Phase 2D directive, all 179 files under `docs/legacy-migration/`, and every Phase 2A, 2B, 2C and 2D machine specification.
- Parsed all 60 migration-document JSON files; zero parse failures.
- Confirmed all 21 required Phase 2D documents and 16 required machine specifications are present; zero record-count mismatches.
- Independently reproduced the Phase 2D extraction-query hash.
- Ran only targeted documentation/evidence tests:
  - `php artisan test tests/Unit/LegacyMigration/Evidence/Phase2DSpecificationConsistencyTest.php tests/Unit/LegacyMigration/Evidence/GeneratedEvidenceConsistencyTest.php`
  - Result: **14 passed, 507 assertions**.
- Reviewed repository status for the Phase 2D scope. Only `docs/legacy-migration/phase-2d/` and `tests/Unit/LegacyMigration/Evidence/Phase2DSpecificationConsistencyTest.php` are new for this phase.

## Exit conclusion

Phase 2D satisfies its exit criteria with no Critical or High findings. It is sufficient input for Phase 2E patient insurance and eligibility mapping specifications. This verdict does not resolve Phase 2C patient-persistence blockers, does not authorise a patient pilot, and does not authorise importer implementation, schema changes, UI work, seeders, synchronization runtime or any database write.
