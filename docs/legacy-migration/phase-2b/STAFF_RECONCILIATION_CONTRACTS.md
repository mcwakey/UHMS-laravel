# Staff reconciliation contracts

The machine contracts are [staff_reconciliation_contracts.json](specifications/staff_reconciliation_contracts.json): master/security/cardinality controls, 28 source-field partitions, 99 target-field actor policies, existing-user immutability and artifact scanning.

## Staff master

`86 = matched existing + disabled historical candidate + quarantined unverified + quarantined ambiguous + failed`

Primary precedence is failed, ambiguous/duplicate, verified existing match, verified historical candidate, then unverified quarantine. Security-excluded columns are secondary zero assertions and do not double-count source rows.

## Security

Passwords mapped, permission-derived authorization, historical roles, permissions, login capability, sessions/reset/invitation paths, notification routing, Classic-derived department access and Classic-derived specialty privilege must each equal zero.

## Existing matches

Each verified Classic identity maps to at most one target identity. Each target identity receives at most one Classic identity unless a reviewed many-source-to-one employment contract exists. Ambiguous target matches must equal zero for readiness.

## Actor relationships

For each of 28 fields, reconciliation uses two separate partitions.

Source-evidence partition for numeric references:

`child rows = null + field zero sentinel + matched Classic user parent + orphan Classic user parent`

Source-evidence partition for text references:

`child rows = null + blank sentinel + one-name diagnostic + ambiguous-name diagnostic + unmatched nonblank text`

Target-resolution partition:

`child rows = verified target actor + permitted Legacy Actor Unknown + target null/provenance-only + quarantined + failed`

Precedence is extraction/fingerprint failure, null, field sentinel, non-sentinel source evidence, external verified identity/action-role resolution, permitted unknown/null/provenance, then quarantine. A Classic parent or one-name diagnostic is never labelled a verified actor. Both partition differences must be zero.

Each `TARGET-ACTOR-*` field has a separate `PROHIBITED` or `PERMITTED` unknown policy. Prohibited use has expected count zero and stops the run. Permitted `visits.created_by` use requires D-202, `LEGACY-STAFF-ACTOR-032`, a protected source reason and manual review. Existing renewed users also have protected pre/post HMAC field-group comparisons; any migration-caused scalar, credential, employee, role, permission, department, specialty, session/reset, notification or external-identity change stops the run.
