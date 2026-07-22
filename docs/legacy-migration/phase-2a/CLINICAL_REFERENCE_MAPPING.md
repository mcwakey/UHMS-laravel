# Clinical Reference Mapping

## Complaints

`list_complaints` has 8,924 rows, 12 blank values and 366 normalized duplicate groups/900 rows. Map nonblank exact canonical names to `complaint_catalogues.name`; blank rows quarantine. Many-source-to-one is permitted only for exact canonical duplicates after target review.

## Diagnoses

`list_diagnosis` has 5,948 rows. `Icd10` is incomplete in 1,687 rows and has 853 duplicate groups/3,224 rows; even code+description has 2,522 incomplete and 485 duplicate groups/1,205 rows. Map validated code→`icd_codes.code` and description→description; conflicts quarantine. Mdc, Level and G-DRG remain deferred/evidence unless separately approved. All `LDIS_ID` are zero sentinels; do not create disorder parents.

The Classic diagnosis description permits 500 characters while the installed target permits 191. Overlength text is a blocking exception; never truncate it into a misleading clinical label.

## Procedures

`list_procedures` has 768 rows. G-DRG+operation still has a duplicate pair; one operation is blank and type is blank in 754 rows. Map code/name/category only through explicit rules. Tariff/Cash are price candidates after semantics; Private/Corp have no approved target price structure.

## No safe automatic destination

`list_causes` (18), `list_disorder` (1), `list_signs` (2) and `list_symptoms` (6) remain excluded/evidence-only: cause/sign parents are all orphaned, all symptom parent strings are nonnumeric and unmatched, and the disorder link is entirely zero. No text/name inference may repair them.

`serv_results` is a clinical-history transaction (460,758 rows), not catalogue reference data. It is deferred to the clinical-history phase with its extensive orphan/empty-result evidence intact.
