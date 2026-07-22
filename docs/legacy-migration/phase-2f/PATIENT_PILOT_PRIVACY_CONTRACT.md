# Patient pilot privacy contract

## Protected scope

Patient identity, OPD, NOK/contact, address, occupation, insurance membership, provider/company, remediation evidence, target comparison and quarantine-chain facts are protected. This contract composes `PATIENT-PRIV-*`, `PATIENT-CHILD-PRIV-*` and `INS-PRIV-*`.

Normative machine contract: `specifications/patient_pilot_privacy_contract.json` version 2F.1.0.

## Repository prohibition

No repository artifact, prompt, screenshot, report or log may contain Classic, target-derived or plausibly real patient/contact names, OPD values, phone/address/contact values, member/policy/CCC values, raw patient/insurance keys, raw target IDs, row-level dates/tokens, remediation values or eligibility facts. Credentials and HMAC key material are prohibited. The sole value-bearing exception is the rigidly namespaced `patient_pilot_scenarios.json` Cohort A contract: every record must be explicitly synthetic, obviously fictitious, independently generated and scanner-allowlisted; no value may be copied or perturbed from Classic or target data.

Repository-safe evidence is aggregate counts, stable contract IDs, approved nonidentifying allow-list values and nonsecret specification/query/result hashes. Synthetic fixtures must be overtly fictitious and independently generated.

## Protected tokens

Runtime tokens use domain-separated HMAC-SHA-256 with environment-protected secret material, pinned key/canonicalization versions and typed length-prefixed UTF-8 values with explicit null/type markers. Plain hashes of low-entropy values and cross-domain/environment/key/version comparisons are prohibited.

## Artifact-wide regression scan

The focused scan covers every file under `docs/legacy-migration/` and the Phase 2F focused consistency test, not merely Phase 2F output. `P2F-PRIVACY-SCANNER-2` records a SHA-256 manifest of ordinal-sorted repository-relative paths, scanner/`P2F-SYNTHETIC-ALLOWLIST-1` versions, file count, coverage difference, detector classes, structured checks, finding count and handling.

Coverage difference and unallowlisted findings must both be zero. Diagnostics print only path, detector ID and redacted location, never matched content.

Suspected exposure stops the run, triggers containment/purge and any necessary secret/key rotation, then requires a complete rescan before release.
