# Patient pilot target collision contract

## Boundary and snapshot

Version `2F.1.0`. Source `legacy_uhms`/`uuhms` and approved non-production target are read-only in this phase. The current structural fingerprint is `12e3a4c6ca80a0c1a54ea9267d9c4935453b7a36d27c7f8ad900062c1c12bff0`; it is a mutable baseline, not permission to commit.

Cohort C binds a protected target snapshot and rechecks each affected set immediately before its future unit. Repository output contains aggregate categories only.

## Deterministic branches

| Collision | Result |
|---|---|
| Environment/schema/constraint drift | Stop entire run and recapture |
| Missing, duplicate, revoked or conflicting crosswalk | Existing-target ambiguity quarantine; never fall through to create |
| Missing target | Quarantine; allocate zero |
| Soft-deleted target | Quarantine; never restore or replace |
| Merged/redirected target | Quarantine; never follow the merge pointer |
| Target scalar/child drift | Stop; preserve target; require refreshed approval |
| Patient-number collision across live/trashed/merged patients, archives or aliases | Stop; no suffix, reallocation or recycling |
| Number configuration/sequence/period drift | Stop coordinate and re-pin |
| Alias collision | Withhold alias only; never use it as identity evidence |
| New-parent contact-set drift or unproved prior contact | Withhold/stop Unit C |
| Multiple primary contacts | Stop; never clear or select one automatically |
| Explicit existing-target child difference | Comparison only; enrichment zero; `LEGACY-PATIENT-CHILD-CONTACT-012` |
| Attempted/detected existing-target child mutation | Stop, retain comparison evidence and prove zero delta; `LEGACY-PATIENT-CHILD-TARGET-036` plus `LEGACY-PATIENT-CHILD-CONTACT-014` |
| Existing patient/provider membership | Exact prior lineage gives no-op; otherwise immutable conflict |
| Duplicate patient/provider or protected member conflict | Stop/history-only; never deduplicate or overwrite |
| Provider crosswalk/target provider drift | Quarantine insurance group; patient identity remains unchanged |

Detection uses unscoped/with-trashed patient queries, archive evidence, complete alias/contact/membership sets, provider/crosswalk versions and sequence configuration. Canonical target-set evidence uses domain-separated HMAC with typed length-prefixed inputs in the protected runtime. Plain hashes of identifiers are prohibited.

Collision precedence is environment → crosswalk/identity → patient state → number → alias → optional contact → insurance. A later child collision cannot change an earlier patient identity decision. Machine contract: `specifications/patient_pilot_target_collision_rules.json` (16 records).
