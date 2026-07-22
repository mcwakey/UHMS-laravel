# Phase 3 migration-foundation handoff

## Boundary

Phase 2F specifies interfaces; it implements none of them. The normative capability list is `specifications/phase3_foundation_requirements.json` version 2F.1.0. Patient commit remains blocked until the required foundation exists, is tested and is independently reviewed.

## Required capabilities

Phase 3 must implement:

1. environment/schema guards;
2. least-privilege Classic source connection;
3. coordinated source snapshot manager;
4. target-collision snapshot manager;
5. run manifest with the bounded cohort identity;
6. protected crosswalk;
7. protected remediation store;
8. provenance/history store;
9. quarantine/exception ledger;
10. reconciliation store;
11. HMAC/key management;
12. separate migration audit;
13. migration-safe patient-number allocator;
14. migration-specific patient persistence;
15. alias persistence;
16. contact persistence;
17. insurance-history and membership persistence;
18. side-effect isolation;
19. idempotency;
20. checkpoints;
21. resume;
22. rollback/compensation;
23. target-state validators;
24. privacy scanning;
25. dry-run reporting.

## Required proof

Each capability traces to a `PILOT-*` interface in the machine specification and has focused fail-closed tests. Required proofs include exact environment guards, source read-only privilege, snapshot drift invalidation, crosswalk cardinality/immutability, protected data access and integrity, every insurance row preserved, one-root/topological quarantine, exact reconciliation, HMAC separation, atomic unit behavior, allocator rerun/concurrency safety, existing-target immutability, at-most-one membership per patient/provider, zero prohibited side effects, crash-boundary resume without duplication, unit-specific compensation without universal deletion, explicit target-state initialization and complete privacy-safe dry-run reporting.

## Entry restriction

Foundation implementation does not itself authorize a patient pilot. After Phase 3, the programme still requires current source/target collision snapshots, completed target-state matrices, approved persistence/isolation evidence, zero-difference dry-run results and independent commit-specific review.
