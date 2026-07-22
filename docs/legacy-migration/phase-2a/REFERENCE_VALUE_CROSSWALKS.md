# Reference Value Crosswalks

Machine contract: [reference_value_crosswalks.json](specifications/reference_value_crosswalks.json).

## Rules

- [Approved policy] D-205 requires explicit source-to-target value tables; unknowns never receive convenient defaults.
- [Technical specification] Text is decoded from Classic latin1, normalized to Unicode NFC and trimmed for comparison. Raw protected provenance is retained; comparison normalization never overwrites it.
- [Technical specification] Codes retain leading zeros and punctuation. No fuzzy or punctuation-insensitive automatic matching is permitted.
- [Technical specification] Machine states are exactly: Exact, Approved transform, Target-existing match, Configure separately, Unknown, Invalid, Excluded or Deferred. “Unknown” includes technical mappings still awaiting an explicit value rule; it never authorizes a default.

## Approved or evidence-backed entries

| Source | Value | Destination | State |
|---|---|---|---|
| `sett_private.PrivateType` | `CORPORATE` | insurance type `CORPORATE`; provider type `corporate` | Approved transform |
| `sett_private.PrivateType` | `PRIVATE INSURANCE` | insurance type `PRIVATE`; provider type `private` | Approved transform |
| `medicine.MedType` | `DRUG` | product type `drug`, with an eligible drug companion | Approved transform |
| `medicine.MedType` | `CONSUMABLE` | product type `consumable`; no drug companion | Approved transform |
| `acc_titles.ExpInc` | `INC` / `EXP` | `income` / `expense` | Approved transform |
| `beds.BedStatus` | `AVAILABLE` / `OCCUPIED` | no Phase 2A target state | Deferred |
| `claims_specialty.Status` | `INPATIENT` / `OUTPATIENT` | claims care-setting evidence, not `specialties.is_active` | Deferred |
| `services.Corp` | `TRUE` / `FALSE` | no target catalogue field | Deferred |

## Pending vocabularies

Department types, product category/unit/dosage form, service and criterion result types, procedure categories, specialty semantic matches, and many finance category matches remain technical mapping inputs. Sanitized evidence deliberately represents unapproved raw product categories/units as redacted buckets. Their absence from this document is a privacy/safety control, not permission to discard them.
