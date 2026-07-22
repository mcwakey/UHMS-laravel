# Specialty Mapping

[Confirmed] Classic `claims_specialty` has nine rows, eight codes and one duplicated code (`OPDC`). `Status` contains INPATIENT/OUTPATIENT and is claims care-setting evidence—not target activation. The catalogue mixes clinical specialties with claim classifications.

| Source | Disposition | Rule |
|---|---|---|
| `SPEC_ID` | Crosswalk | protected source identity |
| `SpecDesc` | Transform/crosswalk | exact approved semantic target `specialties.name` |
| `SpecCode` | Evidence/crosswalk input | preserve leading zeros/punctuation; code alone is unsafe |
| `Status` | Evidence only | never `specialties.is_active` |

Target specialty name is globally unique; department is optional. Doctor/service pivots are separate and cannot be inferred.

[Technical specification] High-confidence candidates still require row-level approval: MEDICAL→General Medicine, OBSTETRICS AND GYNECOLOGY→Obstetrics & Gynecology, PAEDIATRICS→Pediatrics, ADULT SURGERY→General Surgery, ANTENATAL/POSTNATAL CARE→Midwifery & Antenatal Care. PAEDIATRIC SURGERY, RECONSTRUCTIVE SURGERY, G-DRG and ZOOM have no safe automatic destination.

`SpecialtySeeder` and fuzzy names are not migration matchers. Reconcile `9 = approved specialty links + claims-only evidence + exceptions`; consumer evidence is `31,892 = 31,891 matched + 1 zero sentinel`.
