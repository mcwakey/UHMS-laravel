# Patient mapping readiness matrix

`Yes` here means the Phase 2C detailed specification exists. It never authorizes importer implementation or target persistence.

| Area | Source evidence | Target evidence | Policy approved | Technical specification | Persistence ready | Remaining blocker |
|---|---|---|---|---|---|---|
| Classic patient master/columns | Yes | N/A | Yes | Yes | No | Phase 3 crosswalk/provenance foundation |
| Patient number | Yes | Yes | Yes | Yes | No | Migration-safe allocator, atomic crosswalk, freeze/ledger, config fingerprint |
| OPD aliases | Canonical source partition complete | Yes | Yes | Yes | No | Unicode/NFC runtime parity, semantic grammar, `legacy_opd` support and target collision/review foundation |
| First/last names | Yes | Yes | Yes | Yes, fail-closed | No | Protected component remediation; source alone proves zero valid splits |
| DOB | Yes | Yes | Yes | Yes | No | Remediation for 19 invalid/implausible rows; pilot cohort decisions |
| Gender | Yes | Yes | Yes | Yes | No | Remediation for 166 missing/invalid rows |
| Phone | Exact target-regex partition | Yes | Yes | Yes | No | Remediation for 5,050 missing and 163 invalid values; pre-pilot refresh |
| Target patient state | No approved source derivation | Yes | No | Fail-closed rules complete | No | Coherent policy for status, active, temporary, merge and deceased fields; database defaults prohibited |
| Existing target links | Source crosswalk absent | Yes | Yes | Yes | No | Protected approved crosswalk and refreshed withTrashed collision snapshot |
| Duplicate review | Yes | Yes | Yes | Yes | No | Protected review ledger/workflow integration; no automatic action |
| Registration attribution | Yes—actor absent | Yes | Yes | Yes (`null`) | No | Migration persistence boundary/provenance ledger |
| Direct patient relationships | Yes, 5/5 | Yes | Yes | Yes | No | Quarantine ledger and later child mappings |
| Downstream dependency chains | Edge evidence complete | Yes | Yes | Yes, 28 edges | No | Conditional chain-size/cross-link evidence and later domain gates |
| Extraction/change detection | Yes | N/A | Yes | Yes | No | Least-privilege D-101 account, execution tooling, freeze/snapshot controls |
| Privacy/provenance | Aggregate evidence only | Target risks documented | Yes | Yes | No | HMAC key management and protected ledgers |
| Phase 2D input readiness | Yes | Yes | Yes | Yes | Specification work only | None for Phase 2D specification entry; patient persistence blockers remain |

The patient entity pilot cannot persist until first/last-name remediation and the Phase 3 foundation are approved and implemented. Phase 2D may nevertheless specify aliases, contacts and demographic children against the protected patient-parent contract.
