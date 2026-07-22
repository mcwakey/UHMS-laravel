# Patient pilot exception flow

## Composition rule

Phase 2F uses the existing Phase 2A-2E exception catalogues. It does not duplicate or weaken their trigger, owner, SLA, blocking scope or release condition.

Normative precedence: `specifications/patient_pilot_exception_precedence.json` version 2F.1.0.

## Ordered precedence

| Order | Class | Primary effect |
|---:|---|---|
| 1 | Environment, fingerprint or privacy stop | Stop run/snapshot; contain exposure. |
| 2 | Extraction, key, query, snapshot or checkpoint failure | Stop extraction and descendants. |
| 3 | Reference-parent or actor failure | Hold dependency; create no artificial parent/actor. |
| 4 | Existing-target ambiguity, drift or conflict | Stop root/group; target stays immutable. |
| 5 | Required patient identity/remediation failure | Quarantine patient and descendants. |
| 6 | Patient-state or number failure | Patient is not commit-eligible; no default/allocation. |
| 7 | Patient-core eligibility | Fix the core branch before children. |
| 8 | Alias-only failure | Withhold/reconcile alias independently. |
| 9 | Optional demographic/contact failure | Withhold optional projection without rewriting core. |
| 10 | Insurance row/provider/group failure | Preserve history; withhold unsafe current representation. |
| 11 | Side-effect violation | Stop and prove zero delta. |
| 12 | Reconciliation failure | No acceptance until every difference is zero. |
| 13 | Commit, lineage or idempotency failure | Stop; reconstruct durable facts and use reviewed compensation. |

At the first applicable level, the most specific normative code is primary. Other applicable codes remain ordered secondary facts. Severity cannot promote an optional-child issue above a patient-root stop.

## Root and release

Each quarantine outcome has one Phase 2C root domain. Release is topological and requires unchanged environment/snapshots, valid keys, resolved parents/actors, approved patient identity/state, deterministic target branch, parent reconciliation, child collision checks and child reconciliation. Every term must be measured and pass.

Duplicate aliases do not imply duplicate patients. Insurance conflicts do not alter patient identity. Orphan children are never reassigned.

