# Patient-child classification

## Contact outcomes

Every source patient receives one mutually exclusive source-tuple outcome: complete valid, partial, all blank, invalid, parent quarantined, existing-target evidence only, or failed. Diagnostic subtypes such as name-only and phone-only do not double-count the primary partition.

## Demographic outcomes

Each of occupation, address, religion and marital status independently receives exactly one:

- `VALUE_VALID`
- `VALUE_BLANK_NULL`
- `VALUE_INVALID_WITHHELD`
- `VALUE_OVERLENGTH_WITHHELD`
- `VALUE_UNREPRESENTABLE`
- `VALUE_PARENT_QUARANTINED`
- `VALUE_EXISTING_TARGET_IMMUTABLE`
- `VALUE_EXTRACTION_FAILURE`

Precedence is failure, parent state, existing-target state, then field validity/absence. These outcomes never alter or double-count the Phase 2C patient-entity partition. Optional child failure is local after parent success; patient-root failure still blocks every child.

