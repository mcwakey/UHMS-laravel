# Patient-child reconciliation contracts

All partitions are mutually exclusive after fixed precedence and require difference zero.

## Source tuple

`16,950 = complete_valid + partial + all_blank + invalid + parent_quarantined + existing_target_evidence_only + failed`

The presence evidence `14,578 + 1,001 + 1,371 = 16,950` is a structural diagnostic; final validity is calculated under the frozen validator.

## Contact runtime

`eligible = created + exact_idempotent_prior + withheld_partial_or_invalid + parent_not_released + existing_target_immutable + commit_failed`

During Phase 2D all write/commit buckets are zero. Future runs prove at most one migration-created primary per newly created patient.

## Four demographic fields

For each field:

`16,950 = valid + blank_null + invalid + overlength + unrepresentable + parent_quarantined + existing_target_immutable + failed`

## Required zeros

- Alias: Phase 2D-created rows, alternate canonicalizers, Original* aliases, auto-assigned duplicates and child-influenced ownership.
- Existing target: changes to demographics/localities/contacts/primary flags/aliases/timestamps/activity.
- Privacy/side effects: raw protected artifacts, plain row hashes, cross-domain HMAC comparisons, notifications, SMS and contact-search effects.
- Chain: released-before-parent, guessed/reassigned/artificial parent, cross-chain union, missing root token, unclassified omission and snapshot mismatch.

The machine contract defines ten reconciliation IDs and references Phase 2C `PATIENT-REC-ALIAS-002`, `PATIENT-REC-EXISTING-030`, `PATIENT-REC-CHAIN-100` and `PATIENT-REC-PRIVACY-050` without replacing them.

