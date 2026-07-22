# Patient-child sentinel rules

Each relationship has exactly one rule in `patient_child_sentinel_rules.json`.

1. `PATIENT-CHILD-SENT-001`: demographic fields have no relationship sentinel. Blank is per-field optional absence; invalid nonblank is withheld.
2. `...-002`: the NOK tuple has no parent sentinel. Three blanks are successful absence; partial content is a child-review outcome. Placeholder-looking content is not automatically null.
3. `...-003`: an absent mapped target is parent-not-released, not a nullable FK. Zero, guessed, current, first, administrator/importer IDs and synthetic parents are prohibited.
4. `...-004`: OPD blank/invalid/duplicate/collision behavior delegates completely to Phase 2C.
5. `...-005`: a chain token has no substitute. Missing/mismatched domain, key, canonicalization or snapshot versions are privacy/provenance failure.

No rule establishes a global zero-is-null or blank-is-null policy. Empty-string absence is evidenced only for these installed NOT NULL text fields.

