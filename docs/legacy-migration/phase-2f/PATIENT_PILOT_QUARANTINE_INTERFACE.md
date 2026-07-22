# Patient pilot quarantine interface

## Boundary

This is a logical interface, not a quarantine table or review UI. It applies D-207, D-211, D-212 and D-213 and keeps all Phase 2C-2E root and exception rules intact.

Normative machine contract: `specifications/patient_pilot_quarantine_interface.json` version 2F.1.0.

## Required record

A quarantine outcome contains one root token, optional typed subchain, one primary and ordered secondary exception codes, blocking scope, inherited manual-review owner and SLA, release conditions, dependency edges, current disposition and protected approval reference.

Valid roots are:

- `PATIENT-PRIV-007` for a patient chain;
- `PATIENT-PRIV-008` for an orphan-attendance chain;
- `PATIENT-PRIV-009` for an orphan-insurance chain.

The Phase 2E insurance-row correlation token remains secondary and never replaces the Phase 2C orphan root.

## Release order

Release is topological:

1. environment and snapshots;
2. reference parents and actors;
3. patient identity and target state;
4. patient core;
5. alias, contact and individual insurance row;
6. insurance patient/provider group;
7. reconciliation.

Releasing a root does not automatically release children. Each child reruns current transformation, target-collision and reconciliation rules at the pinned coordinate.

## Safety invariants

- Every held outcome has exactly one valid root.
- No child releases before its valid parent.
- An optional-child issue never replaces a patient-core exception.
- Alias duplication never becomes a patient-merge decision.
- Insurance conflict never alters patient identity.
- No orphan is assigned to a similar, current, first, administrator, importer, generic or guessed patient.
- Conflicting root paths use `LEGACY-PATIENT-CHAIN-042` and remain held.

