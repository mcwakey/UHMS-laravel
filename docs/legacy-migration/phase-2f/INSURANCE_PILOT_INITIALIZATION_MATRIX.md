# Insurance pilot initialization matrix

## Contract boundary

Version `2F.1.0`. `legacy_uhms`/`uuhms` remains read-only and the approved non-production target remains inspection-only. This contract composes `NK-004`, `INS-CONS-*`, `INS-TARGET-*` and `INS-ELIG-*`; it creates neither memberships nor verification records.

## Initialization

| Field | Installed target | Future rule | Status |
|---|---|---|---|
| `patient_id` | required patient FK | Resolved newly migrated parent only | Parent prerequisite |
| `insurance_provider_id` | required provider FK | Exact approved Phase 2A provider crosswalk | Crosswalk prerequisite |
| `insurance_tier_id` | nullable | null/not evidenced exactly under `INS-CONS-010`; no tier crosswalk or operational default may populate it | Upstream-required null/not evidenced |
| `member_type` | non-null `holder|beneficiary`, default `holder` | Source proves neither value; default is prohibited | Target-representation commit blocker |
| `card_holder_insurance_id` | nullable self-FK | null/not evidenced exactly under `INS-CONS-010`; no holder relationship may be inferred | Upstream-required null/not evidenced |
| `membership_number` | nullable | Exact Phase 2E member-number disposition or null; never generated | Upstream member-number rules |
| `policy_number` | nullable | null/not evidenced exactly under `INS-CONS-010`; never copy the membership number or scheme/plan evidence | Upstream-required null/not evidenced |
| `ccc_code` | nullable | null/not evidenced exactly under `INS-CONS-010`; no CCC or verification value may be inferred | Upstream-required null/not evidenced |
| start/expiry dates | nullable | Only valid field-specific D-206 values; no swap/coercion | Upstream rules |
| `is_primary` | non-null, default false | Recommend explicit false and later target-owned selection; no source primary fact | Owner approval blocker |
| `is_active` | non-null, default true | Active-looking dates do not authorize target activation or eligibility | Target-representation commit blocker |
| created/updated timestamps | nullable; Eloquent normally stamps | Recommend explicit null/not-evidenced with protected provenance | Timestamp blocker |
| verification/eligibility | separate operational records | Create zero rows/statuses/actors/times/visit links | Prohibited by `INS-ELIG-001`–`006` |

Historical classification/provenance must still account for every source insurance row. A current membership may be withheld while history is complete. At most one current representation may ever be projected per resolved patient/provider. Existing-target patients and memberships remain comparison-only and immutable under `INS-CONS-012` and `INS-TARGET-009`.

Commit mode for current membership remains blocked until `member_type`, `is_active`, `is_primary` and timestamps have truthful owner-approved representations. Machine contract: `specifications/insurance_pilot_initialization_rules.json` (15 records).
