# Target-state validation design

Each target field is classified as approved value, approved conditional null, recommendation pending approval, target-owned operational state, prohibited default or commit blocker. The validator refuses any unresolved complete patient state tuple.

Insurance validation separately covers `member_type`, `is_active`, `is_primary`, tier, card-holder relation, policy number, CCC, verification, eligibility and timestamps. Phase 2F currently identifies unresolved commit-blocking representations; the foundation must preserve those blocks rather than apply installed defaults.

Existing-target validation proves immutability and metadata-only linkage. Validation emits rule IDs and protected coordinates, not patient/member values.

The current PHP policy is fail-closed but is not derived from or hash-bound to the exact authoritative Phase 2F JSON paths. Phase 2F artifact drift would not automatically invalidate it. Exact artifact-path/hash binding is required before any persistence authorization.
