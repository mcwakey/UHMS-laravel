# Target patient-insurance contract

**Confirmed installed non-production target:** MariaDB 10.4.32; fingerprint `12e3a4c6…`; 335 tables and 5,347 columns. Because synthetic/de-identification status is not evidenced and the insurance population is below ten, nonzero membership/provider/tier/verification counts and breakdowns are suppressed to protected evidence. Repository evidence records only zero duplicate patient/provider groups and zero patients with multiple primary rows.

`patient_insurances` requires valid patient/provider foreign keys and enforces unique `(patient_id, insurance_provider_id)`. Nullable source-relevant fields are `insurance_tier_id`, `membership_number`, `policy_number`, `ccc_code`, `start_date`, `expiry_date`, and `card_holder_insurance_id`. It has no source-history, scheme, plan or eligibility field and no soft delete.

Unsafe defaults are `member_type=holder` and `is_active=true`; neither is source evidence. `is_primary=false` records only non-assignment, not a historical fact. Target initialization remains blocked until Phase 3 specifies how to avoid unsafe defaults and represent absent member type. Timestamps are not Classic event timestamps.

Operational services/controllers are prohibited: they may create cash memberships, default tiers, active/holder/primary state, verification actors/events, visit links or merge/deduplicate memberships. Migration-specific isolated persistence is a Phase 3 prerequisite.
