# Insurance reconciliation contracts

Primary partitions are mutually exclusive and difference-zero. Diagnostic counts never replace primary partitions.

- Patient relationship: `31,307 = 0 null + 0 zero + 30,991 matched + 316 orphan + 0 failed`.
- Provider target outcome: mapped uniquely + blank + target-unresolved + ambiguous + target conflict + failed = 31,307. The refreshed diagnostic is 25,042 blank, 2,247 single Classic provider candidates, one source-ambiguous and 4,017 without Classic candidates; it contains no proven target-mapped count and therefore does not populate the execution partition.
- Member, IssueDate, ExpiryDate, chronology, payer evidence, consolidation groups, provenance and existing-target immutability each have independent equations in JSON.
- Every patient/provider group produces at most one current representation.
- Every source row has one provenance outcome.

Required zeros include artificial patients/providers, reassignment, duplicate target patient/provider rows, silent loss, invented member numbers, coerced/swapped dates, fabricated eligibility/verification/actors, existing-target mutation, claims/financial rows, scheme/tier invention, operational service calls, side effects, non-`uuhms` Classic access and raw restricted values in repository artifacts.
