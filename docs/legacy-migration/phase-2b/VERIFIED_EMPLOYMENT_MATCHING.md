# Verified employment matching

## Deterministic ladder

The machine rules are [staff_matching_rules.json](specifications/staff_matching_rules.json). Evaluate in order and stop only on one unique verified outcome:

1. pre-approved protected Classic-to-target crosswalk;
2. unique verified employee/staff number;
3. unique verified professional registration number;
4. unique verified organization email;
5. externally verified multi-field employment identity;
6. manual HR review.

Only the first rung can exist without new HR input, and no approved operational crosswalk currently exists. Classic has no staff number, registration or email column. Therefore no existing renewed-user match is asserted in Phase 2B.

All available authoritative identifiers must agree. Any disagreement between individually unique rungs quarantines the identity; a higher rung never overrides contradictory verified evidence.

Username alone, name alone, telephone alone and department alone are prohibited. Unverified combinations of these weak attributes remain prohibited. Normalized name comparisons in the actor evidence are diagnostic counts only.

## Existing target behavior

The installed target has 17 active users, 15 nonblank employee identifiers and 15 verified-email timestamps. These are target candidates only; values were not exported. A successful verified match preserves target ID and every operational identity/security field. Multiple target candidates route to `LEGACY-STAFF-MATCH-003`.

## Required protected HR input

A future HR crosswalk must contain protected source key, verified identity type/issuer, normalized verified value or opaque reference, target candidate if any, verifier/approval metadata, validity period, rule version and revocation state. It must not be committed to this repository. The operational crosswalk is a Phase 3 foundation deliverable.
