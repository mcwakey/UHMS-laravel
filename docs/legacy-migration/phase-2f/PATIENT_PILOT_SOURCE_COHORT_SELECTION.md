# Patient pilot source cohort selection

Version `2F.1.0`; specification only. Classic is exact read-only `legacy_uhms` / `uuhms`.

## Algorithm

`PILOT-COHORT-SELECTION-V1` first verifies D-101/D-102, the 55-table/479-column shape, approved fingerprint/version and read-only transaction. It pins one source snapshot and all Phase 2A–2F hashes. It derives typed protected roots, then ranks each stratum by domain-separated HMAC-SHA-256 over length-prefixed `phase2f-cohort-rank-v1`, snapshot, stratum and protected root. SQL randomness, clocks and operator choice are prohibited.

Strata execute in the order below. A root already assigned is excluded from later primary strata, but all secondary coverage flags remain in its protected manifest. Each stratum selects `min(quota, available-unassigned)`. Underfill is recorded and is never silently refilled from another stratum.

`N_B = Σ min(q_s,A_s_after_prior_assignment)`, with maximum 148 roots. The realized count is frozen only after privacy-safe joint-capacity evidence runs at the pinned snapshot. Phase 2F future-ready count is zero.

**Readiness:** the bounded algorithm is specified, but Cohort B is not yet executable or ready for a future dry-run. Before selection, all 24 normalized predicates must be versioned and SHA-256 bound to the approved upstream rule bundle and pinned snapshot; every incomplete capacity query must likewise be normalized, hashed and executed at that coordinate. Current frozen predicate count is zero. Any missing hash, joint-capacity result or disjoint-precedence proof blocks selection without fallback.

| Order | Stratum | Quota | Primary expected coverage |
|---:|---|---:|---|
| 1 | B-INS-CONFLICT | 4 | Conflicting patient/provider insurance groups; membership quarantined. |
| 2 | B-INS-EXACT-DUP | 6 | Exact insurance duplicates; at most one projection, all history retained. |
| 3 | B-INS-ORPHAN | 6 | Insurance orphan chains; no patient. |
| 4 | B-ATT-ORPHAN | 6 | Attendance orphan/sentinel chains; no patient. |
| 5 | B-ALIAS-DUP | 8 | Duplicate OPD withheld; patient independent. |
| 6 | B-NAME-BLANK | 6 | Name remediation required. |
| 7 | B-NAME-AMBIG | 6 | Independent name components required. |
| 8 | B-DOB-INVALID | 6 | DOB remediation required. |
| 9 | B-GENDER-INVALID | 6 | Gender remediation required. |
| 10 | B-PHONE-INVALID | 6 | Phone remediation required. |
| 11 | B-DUP-REVIEW | 6 | Separate patient plus secondary identity-review flag. |
| 12 | B-NOK-COMPLETE | 6 | Contact candidate after parent. |
| 13 | B-NOK-PARTIAL | 6 | Contact withheld, patient independent. |
| 14 | B-NOK-BLANK | 6 | No contact. |
| 15 | B-DEMO-VALUE | 6 | Optional values classified independently. |
| 16 | B-DEMO-BLANK | 6 | Blank optional fields withheld only. |
| 17 | B-INS-MULTI | 6 | Multi-row insurance consolidation. |
| 18 | B-INS-ONE | 6 | One insurance row. |
| 19 | B-INS-ZERO | 6 | Zero insurance rows; no self-pay invention. |
| 20 | B-INS-UNKNOWN-DATE | 6 | Unknown-date history/current withholding. |
| 21 | B-INS-PROVIDER-UNRESOLVED | 6 | Provider unresolved; history retained. |
| 22 | B-ALIAS-BLANK | 6 | Alias absence only. |
| 23 | B-ALIAS-UNIQUE | 8 | Unique source alias candidate, target collision pending. |
| 24 | B-STRAIGHTFORWARD | 8 | Mapping-classifiable but still requires independent name evidence. |

## Evidence and gaps

Confirmed marginals include 16,950 patients; 11,732 passing provisional source checks but not separate-name evidence; OPD 141 blank/15,550 unique candidates/1,259 duplicate rows; complete/partial/blank NOK coverage; 31,307 insurance rows, 316 patient orphans, 32 exact duplicate groups and four conflicting groups.

Before selection, hash-bound aggregate queries must close the zero/one/multiple-insurance patient counts and distinct-root capacities for duplicate/conflict/date/provider strata. The final suspected-duplicate review predicate must be frozen. The manifest must record all 24 predicate hashes, applicable capacity-query hashes, the exact snapshot and contract-bundle hashes, with coverage difference zero. Provider candidate counts are not target mappings. No raw key/value/token is published.
