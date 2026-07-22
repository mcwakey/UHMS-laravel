# Patient pilot dry-run contract

## Purpose

The future dry-run is a complete zero-write simulation. It is not the patient pilot, does not reserve patient numbers and cannot authorize persistence. Classic is exactly read-only `legacy_uhms` / `uuhms`; target access is read-only in the approved non-production environment.

Normative machine contract: `specifications/patient_pilot_dry_run_contract.json` version 2F.1.0.

## Required inputs

The dry-run requires approved source/target environments and fingerprints, coordinated source and target-collision snapshot coordinates, the complete contract-version bundle, bounded cohort manifest, protected remediation input, reference and existing-target crosswalks, patient-state and insurance-initialization matrices, HMAC metadata, configuration fingerprints, a pinned evaluation coordinate/timezone and the expected scenario matrix.

D-101 and D-102 guards fail closed for any wrong connection, schema, privilege, version, 55-table/479-column shape or source fingerprint. Missing inputs are blockers, never defaults.

## Evaluation

The evaluator follows the Phase 2F dependency sequence and may not change an earlier patient identity or target branch in a later child stage. It resolves references and actors, validates remediation/state, classifies the patient, alias, optional children and every insurance row/group, builds one-root quarantine outcomes, projects atomic units, reconciles all populations and runs privacy/side-effect scans.

## Required outputs

Aggregate output includes:

- preflight and cohort inclusion/exclusion verdicts;
- patient entity and nonbinding number actions;
- alias, demographic and contact outcomes;
- insurance row, provider and consolidation outcomes;
- exception, quarantine and target-collision counts;
- exact reconciliation results;
- privacy and side-effect zero assertions;
- idempotency, resume and rollback projections;
- the pilot entry/exit verdict and explicit blockers.

Row-level output remains protected. Repository reports contain aggregate counts, stable IDs and nonsecret hashes only.

Any simulated patient-number action is explicitly nonbinding. No number is allocated or reserved unless a later independently reviewed reservation design exists.

## Required zeroes

Source writes, target writes, operational service calls, queue/scheduler/notification/SMS/email activity, operational audit events and eligibility/verification activity all equal zero. Missing evidence for any assertion fails the dry-run.

