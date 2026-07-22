# Patient reconciliation contracts

The 47 canonical contracts are in [patient_reconciliation_contracts.json](specifications/patient_reconciliation_contracts.json). Every difference must be zero before the relevant release or later activation.

## Primary partitions

Patient entity precedence is failure, explicit exclusion, explicit target link, target ambiguity, remediation pending, missing/invalid identity, then eligible new candidate:

`16,950 = explicit existing-target links + eligible new candidates + remediation pending + quarantined identity + quarantined target ambiguity + failed + excluded`

OPD alias precedence is failure, blank, invalid, source duplicate, target-alias conflict, target-number conflict, not applicable, then unique valid:

`16,950 = unique valid + duplicate withheld + blank + invalid + target-alias conflict + target-number conflict + not applicable + failed`

The canonical source partition is `16,950 = 15,550 + 1,259 + 141` after trim, whitespace removal and uppercase. It must be refreshed under the versioned Unicode/NFC runtime canonicalizer and completed with the target collision snapshot. Duplicate alias and suspected-duplicate flags never enter the entity sum.

## Required fields

Every required field uses:

`patient rows = valid + missing + invalid + unrepresentable + failed`

Confirmed source-only baselines:

| Field | Valid | Missing | Invalid | Unrepresentable | Failed |
|---|---:|---:|---:|---:|---:|
| First name | 0 | 211 | 0 | 16,739 | 0 |
| Last name | 0 | 211 | 0 | 16,739 | 0 |
| DOB | 16,931 | 0 | 19 | 0 | 0 |
| Gender | 16,784 | 151 | 15 | 0 | 0 |
| Phone | 11,737 | 5,050 | 163 | 0 | 0 |

Patient number is reconciled separately because it has no source field.

The five non-null target state fields each use a separate fail-closed partition:

`16,950 = explicit existing-target unchanged + approved new-patient state + state-policy pending + failed`

The Phase 2C baseline is `0 + 0 + 16,950 + 0` for each of `status`, `is_active`, `is_temporary`, `merge_status`, and `is_deceased`. Database defaults do not enter the approved-state term. These contracts are `PATIENT-REC-REQ-007..011`; all new-patient persistence remains quarantined until one coherent patient-state matrix is approved.

## Required zero assertions

- Automatic merges, name-only, phone-only, OPD-only, fuzzy or weighted target matches: zero.
- Duplicate OPD aliases assigned or decorated: zero.
- Classic primary keys reused as target IDs/numbers/alias source target IDs: zero.
- Successful source patients with multiple/replacement target numbers: zero.
- Current, first, administrator, importer, supervisor or Legacy Actor Unknown registration attribution: zero.
- Existing target patient changes through link path: zero.
- Children released before parent, guessed/reassigned relationships, artificial parents and cross-chain unions: zero.
- Raw patient values or row-level diagnostics in repository/log/report artifacts: zero.

## Number and chain equations

Under an operational registration freeze:

`sequence_after - sequence_before = committed migration allocations + explained consumptions`

Expected explained consumptions are zero under the atomic fail-on-collision contract. Without a freeze, exact operational allocations require a unified allocation ledger; otherwise the window stops.

Each of the five direct patient edges and 23 downstream/context edges has an exact `null + zero + matched + orphan + failed` equation in the machine contract. Edge aggregates do not imply unique chain counts; later runtime reconciliation uses protected opaque chain tokens.
