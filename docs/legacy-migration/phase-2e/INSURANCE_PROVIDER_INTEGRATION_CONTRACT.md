# Insurance provider integration contract

Consume Phase 2A `NK-004`, `EXTRACT-SETT_PRIVATE` and the approved `sett_private` source-row-to-target-provider crosswalk without reopening them. NK-004 covers `sett_private(PrivateName, PrivateType)`; it has no `insurance.Company` input.

Phase 2E therefore defines `INSURANCE-COMPANY-TO-PROVIDER-PROJECTION-V1`: strictly decode and normalize `insurance.Company` for name comparison, require exact equality to canonical `sett_private.PrivateName`, explicit `InsType`/Phase 2A `PrivateType` compatibility, a unique approved Phase 2A target-provider crosswalk result and matching versions. `PrivateShort` comparisons are diagnostics only. No fuzzy, substring or punctuation-insensitive match is allowed.

**Confirmed source-candidate diagnostic:** 31,307 = 25,042 blank/not evidenced + 2,247 rows with one Classic `sett_private` candidate + 1 source-ambiguous row + 4,017 rows with no Classic candidate. The 38 provider rows form 33 keys, including five duplicate keys / ten rows. These are not target-provider mappings: Phase 2A marks NK-004 as a candidate natural key with automatic matching prohibited.

A membership requires an explicit approved Phase 2A source-to-target provider crosswalk result. Until then, all 6,264 nonblank source-candidate/unmatched rows remain target-provider-unresolved, the one ambiguous row remains ambiguous, and blank rows remain not evidenced. None creates an artificial provider or Standard tier.
