# Patient pilot dependency graph

Status: **Phase 2F interface specification; future execution only. No writes or importer behavior are authorized.**

The normative ordered graph is [`specifications/patient_pilot_dependency_sequence.json`](specifications/patient_pilot_dependency_sequence.json), version `2F.1.0`. It contains exactly 24 stages, numbered 0 through 23.

## Topology

```text
0 environment/non-production target
  -> 1 D-101 source account and exact read-only uuhms
  -> 2 source/target fingerprints and constraints
  -> 3 pinned policy/specification/configuration/HMAC versions
  -> 4 coordinated source snapshot and cohort order
  -> 5 target collision snapshot
  -> 6 Phase 2A reference crosswalk outcomes
  -> 7 Phase 2B null actor and security-zero outcomes
  -> 8 protected patient remediation outcomes
  -> 9 explicit patient/insurance target-state matrices
  -> 10 patient entity classification or classified root quarantine CLOSES
  -> 11 existing/new/held target disposition and identity envelope SEALS
       |
       +-> 12 patient-number action -----------+
       +-> 13 OPD alias classification ------ Unit B (future)
       +-> 14 inline demographic outcomes ---- Unit A optional fields (future)
       +-> 15 NOK/contact outcome ------------ Unit C (future)
       +-> 16 all insurance row outcomes
             -> 17 provider resolution (consumes Stage 6)
             -> 18 patient/provider consolidation
                  -> current candidate OR classified current-withheld
                  -> history outcome for every source row -- Unit D (future)
       |
       -> 19 rooted dependency/quarantine graph
       -> 20 zero-write dry-run projection
       -> 21 exact reconciliation
       -> 22 privacy and side-effect zero scans
       -> 23 readiness verdict only
```

## Identity seal

Stage 10 closes the entity classification. Stage 11 binds the explicit existing-target or new-patient branch and creates a protected, domain-separated `identity_decision_hash` over the pinned run/snapshot/contract versions, patient source token, remediation versions, required-field outcomes, entity class, duplicate-review flags, branch and target mapping token if present.

The runtime hash and row-level tokens are protected and never published. Stages 12-23 must present the same seal. They may add their own outcome, hold a descendant or stop the root, but cannot:

- select a different target patient;
- infer a patient match from name, phone, OPD, contact, address, provider or membership data;
- merge or split source patients;
- revise first/last-name remediation;
- convert an alias, contact or insurance exception into a patient-identity decision;
- release a quarantined child before its valid parent.

Changed evidence invalidates the candidate/run and requires a new pinned coordinate. It is never an in-place later-stage rewrite.

## Classified blockers remain reachable

The arrows express accounting reachability, not commit eligibility. At Stage 10 an unresolved required identity or owner-approved patient-state prerequisite receives its exact Phase 2C exception and one patient-and-chain quarantine root. The root and its parent-held dependants still traverse Stages 11-23 so the dry run can prove complete classification, provenance and reconciliation and return `BLOCKED_PREREQUISITE`.

At Stage 18 unresolved `member_type`/active/primary/timestamp initialization, incompatible current-looking classes, unknown dates, unresolved providers/roots and immutable existing-target branches produce a classified current-withheld or history-only result. They do not erase or stop insurance history. Every source insurance row proceeds to Stage 19 rooting, Stage 20 zero-write projection and Stage 21 reconciliation.

Only invention, prohibited mutation/reassignment, silent default or row loss, privacy/side-effect failure, drift requiring a fresh coordinate, or an unclassified field/row/group/root is run-stopping.

## Future atomic units

| Unit | Dependencies | Atomic outcome | Failure boundary |
|---|---|---|---|
| A — Patient core | Stages 0-12 plus approved Stage 14 inline fields | Crosswalk-first idempotency, number allocation, new patient, required/state/optional approved values, null registrar, provenance, mapping, core reconciliation and checkpoint | Commit all or none. Existing-target branch records only protected mapping/provenance. A committed number is never replaced, rewound or recycled. |
| B — OPD alias | Committed Unit A or valid explicit existing-target link; Stage 13 | At most one direct `legacy_opd` alias with null creator/source-patient FK, mapping/provenance/reconciliation | Alias-only rollback/compensation. Blank/invalid/duplicate/collision outcomes create zero rows and never roll back identity. |
| C — Emergency contact | Committed Unit A new-patient branch; Stage 15 | At most one migration-origin contact with parent/idempotency/one-primary proof and child provenance/reconciliation | Child-local retry/compensation. Failure cannot rewrite Unit A. Existing target receives zero contact mutation. |
| D — Insurance history/current | Sealed patient; Stage 6 provider input; Stages 16-18 | Protected outcome for every source row; at most one current representation per resolved patient/provider group | Group-local hold/retry/compensation. Existing membership immutable; no source row disappears. |

## Release topology

The patient-root token is authoritative. A child may move from held to released only when every ancestor has a valid, unchanged mapping and its own rule passes. `PATIENT-PRIV-009` remains the orphan-insurance root; a secondary Phase 2E token never replaces it. An optional child exception cannot mask a patient-core exception, and an insurance conflict cannot change patient identity.

## Terminal interpretation

Stage 23 emits the acceptance hierarchy: `FAIL_CLOSED`, `BLOCKED_PREREQUISITE` or `DRY_RUN_ACCEPTED_NOT_COMMIT_AUTHORIZED`. A Phase 3 foundation handoff is documentation-only and does not authorize importer implementation or a patient-pilot commit. All future Units A-D remain conceptual interfaces until the migration foundation and its independent review exist.
