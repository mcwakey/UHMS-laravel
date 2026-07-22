# Patient pilot reconciliation interface

## Contract

Every reconciliation result binds run/cohort, domain, contract/version, population, exact equation, measured values, difference, tolerance, acceptance state, evidence hashes, classified exceptions and approval reference.

Normative machine contract: `specifications/patient_pilot_reconciliation_interface.json` version 2F.1.0. The tolerance for every Phase 2F count, partition and safety assertion is exactly zero.

## Partition rules

Every population member enters exactly one ordered primary bucket. Secondary diagnostics never increase the partition total. A classified nonzero exception is an allowed bucket only when its surrounding equation difference is zero; it never excuses an unexplained difference.

A mandatory measurement that is absent is `blocked_not_measured`, never passed. Any change to snapshot, query, target collision state, contract, HMAC key or canonicalization version invalidates the prior result.

## Composed coverage

The pilot must reconcile:

- cohort inclusion/exclusion and one entity branch per root;
- every consumed source field and direct relationship;
- patient required fields, target state, number action and existing-target branch;
- OPD alias independently under `PATIENT-REC-ALIAS-002`;
- Phase 2D NOK, optional demographics, contact creation/withholding and parent release;
- every insurance source row, relationship, provider/member/date/type/scheme outcome and patient/provider group under `INS-REC-001` through `INS-REC-015`;
- every exception, quarantine root, target collision, idempotency result and checkpoint;
- privacy, side effects, source writes and target writes.

All insurance source rows require protected provenance; no mapped group may select more than one current representation. Specification and dry-run write deltas are zero.

## Acceptance states

Results are passed, failed, blocked-not-measured or explicitly not applicable under a rule. The complete pilot verdict is defined in `PATIENT_PILOT_ACCEPTANCE_CRITERIA.md`; no single passing domain result authorizes commit mode.

