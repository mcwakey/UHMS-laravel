# Phase 2C — Patient identity and registration mapping specifications

Status: **PASS after independent migration review. Phase 2D specification work may begin; no persistence is authorized.**

## Authority and scope

This package applies D-201, D-211, D-213, Q-001, Q-002, Q-006, Q-007 and Q-008 from [APPROVED_DECISION_SPECIFICATIONS.md](../APPROVED_DECISION_SPECIFICATIONS.md) and [DECISIONS.md](../DECISIONS.md). It consumes Phase 2A reference contracts and Phase 2B actor contract `TARGET-ACTOR-054` without reopening them.

Only Classic `uuhms` was queried, inside a read-only repeatable-read transaction that was rolled back. The renewed target was inspected read-only in local non-production database `uhms_clean`. No importer, crosswalk/state/quarantine table, migration, schema change, generator, UI, service, seeder, target write or Classic write was introduced.

Evidence labels used throughout:

- **Confirmed evidence** — installed metadata, repository behavior or sanitized aggregate evidence.
- **Approved policy** — authoritative project-owner decision.
- **Inference** — plausible semantics that are not mapping authority.
- **Phase 2C technical specification** — fail-closed rule for later design.
- **Phase 3 prerequisite** — required foundation capability, not implemented here.

## Package result

- Classic `patients`: 16,950 rows and 24 columns, all classified exactly once.
- Source identity: one combined `PatientName`; no separate first/last names, email, national identifier or registration actor.
- OPD source canonical partition: 141 blank, 15,550 unique nonblank candidates and 1,259 duplicate-affected rows in 275 groups after trimming, removing whitespace and uppercasing; the semantic validity grammar and target collision snapshot remain prerequisites.
- Required-field evidence: 211 blank combined names; gender 16,784 mapped/151 blank/15 invalid; DOB 16,931 conservative valid/19 invalid or implausible; phone 11,737 target-regex-compatible/5,050 blank/163 invalid.
- Five direct Classic patient relationships and 28 direct/downstream field-specific sentinel contracts.
- Installed target: 46 patient columns, 100 patient rows, one current sequence row at 100, zero aliases, 84 patient FKs across 79 tables and eight patient-shaped fields without FK.
- Target numbers are commit-time, crosswalk-first and atomic. The normal service's random collision suffix, current-user attribution and file/runtime behavior are prohibited.
- `registered_by` and legacy-alias `created_by` are null plus protected absence provenance; no Legacy Actor Unknown or operational-user fallback.
- Existing target patients are immutable and may link only through protected explicit evidence.

Canonical machine contracts are in [specifications](specifications/). Four non-overlapping discovery drafts remain in [drafts](drafts/) as read-only workstream handoffs; they are evidence inputs, not policy or implementation authority.

## Central blockers

Classic supplies only one combined patient name while target creation requires independently valid first and last names. No automatic split is safe. Under the source-only baseline, zero rows can yet prove both target components without a protected remediation input. This blocks patient persistence, not completion of the Phase 2C specification or start of Phase 2D child mapping.

The installed target also requires five coherent non-null state fields. Classic has no approved derivation and database defaults are not policy evidence, so new-patient persistence remains quarantined until a controlled patient-state matrix is approved. Existing-target links preserve their state unchanged.

## Required next use

1. Preserve this specification as the Phase 2C policy/technical baseline.
2. Begin Phase 2D mapping for aliases, contacts and demographic children without creating rows.
3. In Phase 3, design and independently review the protected crosswalk, remediation, quarantine/chain ledger, migration audit, HMAC controls, migration-safe allocator and isolated persistence boundary.
4. Refresh the privacy-safe source partitions and target OPD collision namespaces immediately before any patient pilot implementation.
