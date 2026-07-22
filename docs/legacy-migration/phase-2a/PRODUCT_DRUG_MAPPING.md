# Product and Drug Mapping

## Confirmed quality and target gates

Classic `medicine` has 4,303 rows: 2,669 blank names, 2,903 blank `Serve`, 3,874 blank categories, 154 blank types, and two duplicate nonblank code groups/four rows. Target products require name/type/unit; target drug companions additionally require category, dosage form and unit.

| Source | Specification |
|---|---|
| `MED_ID` | protected mapping identity |
| `MedName` | product/drug name; blank blocks |
| `MedCode` | unique nonblank code preferred; duplicate/blank exception path |
| `MedType` | DRUG→drug (4,127); CONSUMABLE→consumable (22); blank 154 blocks |
| `Category` | approved drug-category crosswalk; target category name is not unique |
| `Serve` | requires separate unit and dosage-form vocabulary; never populate both by assumption |
| `GEN_ID` | evidence only; no evidenced generic parent and not `gens` |
| `MedDescription` | sanitized product description candidate |
| `DEP_ID` | product_department after department crosswalk; 4,150 matched, 153 zero sentinels |
| `Buy`, `IncCash/Nhis/Private` | deferred until meaning, currency, unit, payer and scale are approved |
| `RegDate` | valid created-at candidate only; not update/delete watermark |

Natural key: unique canonical nonblank code. Without code, name+type+approved unit is manual review only. Drug companion is keyed by mapped product ID and must be zero-or-one despite the target lacking that uniqueness constraint. Consumables must have zero drug companions.

Classic names allow 1,000 characters while target product/drug names allow 191. Overlength names are blocking exceptions; no truncation, abbreviation or generated replacement is permitted.

Stock dependencies must not create stubs: pharmacy snapshot has 4,278 non-sentinel product orphans plus one zero; store 4,265; batch 73; request 11. `ProductAndDrugSeeder` is prohibited because it generates codes and writes prices/opening stock.
