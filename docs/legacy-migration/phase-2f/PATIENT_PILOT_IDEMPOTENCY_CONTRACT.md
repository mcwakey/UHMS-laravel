# Patient pilot idempotency contract

## Boundary

Version `2F.1.0`; `legacy_uhms`/`uuhms` is read-only; target is approved non-production and read-only in Phase 2F. No key or token described here is stored by this phase.

Installed target uniqueness is not lineage: patient-number uniqueness cannot prevent replacement allocation, alias uniqueness cannot prove source ownership, contacts have no unique migration key, and patient/provider uniqueness cannot distinguish a prior migration row from unrelated target data.

## Stable protected keys

| Outcome | Key material |
|---|---|
| Patient core | Domain, protected source-patient token, transformation bundle and canonicalization version |
| Existing-target link | Source token, protected target token and approved crosswalk version |
| Number allocation | Patient-core key, numbering configuration fingerprint and pinned period |
| Legacy OPD alias | Source token, mapped-target token, canonical alias token and rule version |
| Emergency contact | Source token, target-parent token, tuple HMAC and child rule version |
| Inline demographics | Patient-core key and field-bundle version |
| Insurance history row | Protected source-row token and extraction/transformation versions |
| Insurance patient/provider group | Mapped-patient token, provider-target token and consolidation version |
| Exception record | Quarantine-chain key, exception code and rule/evidence versions; multiple applicable codes remain ordered members of one chain |
| Quarantine chain | Typed authoritative root token, root domain, source snapshot and contract-bundle version; exactly one stable chain identity per root |
| Reconciliation/checkpoint | Run/cohort/contract/stage plus evidence hashes and contract version |

Keys use domain-separated HMAC over typed length-prefixed canonical inputs inside the protected runtime. Raw PHI, raw IDs and plain hashes of low-entropy fields are prohibited.

On rerun, resolve successful mapping before sequence allocation; return the same patient and number; create no duplicate alias/contact/membership; preserve classifications unless versioned evidence changes; and refuse unexplained partial states. A database unique-key collision is never success without exact protected lineage. Drift creates a new reviewable evaluation and does not overwrite the prior outcome.

Machine contract: `specifications/patient_pilot_idempotency_rules.json` (13 records).
