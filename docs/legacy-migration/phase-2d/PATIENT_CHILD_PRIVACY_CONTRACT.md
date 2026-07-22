# Patient-child privacy and provenance contract

All seven fields are protected patient data. NOK and address values are restricted contact/location data. Repository artifacts contain no raw NOK name/phone/high-cardinality relationship, address, identifying occupation, OPD, Classic key or row-level token.

Protected runtime diagnostics later use HMAC-SHA-256 with an environment-controlled secret, key version, canonicalization version and length-prefixed typed message. Domains are separate:

- `patient-child-contact-v1`
- `patient-child-address-v1`
- `patient-child-occupation-v1`
- `patient-child-demographic-v1`

Never compare across domain, environment, key or canonicalization version. Every child retains the Phase 2C `PATIENT-PRIV-007` patient-root token; a local `PATIENT-PRIV-012` hold never replaces it.

Existing-target comparisons store only protected flags/tokens. Migration audit records run, mappings, transformations, failures and reconciliation separately and creates no false operational activity. Raw values, row-level low-entropy hashes, messages/notifications and contact-search identity signals must reconcile to zero.

