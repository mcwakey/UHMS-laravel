# Preliminary mapping matrix

## Status and rules

This is a table/domain-level hypothesis for detailed mapping—not approval to import. `Candidate` means a plausible structural destination exists; legacy `Decision` labels in the evidence tables identify rows whose semantics required policy. Those business policies are now accepted in [APPROVED_DECISION_SPECIFICATIONS.md](APPROVED_DECISION_SPECIFICATIONS.md), but their column-level mapping remains a Phase 2 task. `Exclude` means the approved or preliminary policy is no operational migration. Every committed record will require an explicit source-to-target record map.

Phase 1B strengthens the target side with an actual `uhms_clean` catalogue (335 tables, 5,347 columns, 1,202 foreign keys), confirms the target schema dump is stale, and confirms that route/medical-record links and several operational invariants remain application-enforced. The 2026-07-21 owner directive resolves business policy but does **not** decide unexamined column mappings. The target active-invoice generated column exists but its intended unique index does not; migration validation cannot rely on that uniqueness.

## Approved policy overlay

| Area | Accepted mapping boundary | Phase 2 specification still required |
|---|---|---|
| Patients and aliases | Renewed numbers; unique valid OPD aliases; Classic PK only in protected crosswalk; no automatic merge | Field split/validity, alias normalization, quarantine/identity-review and reconciliation |
| Staff actors | Verified disabled historical identities; constrained Legacy Actor Unknown; no migrated credentials/permissions | Employment-identity crosswalk and actor exception rules |
| Relationships/statuses/dates | Per-relationship sentinels, explicit domain value maps, field-specific dates, no defaults/artificial parents | Every join predicate, value row, date representation, and exception code |
| Appointments/clinical | Evidence-backed parents, grouping, clinician and history only; migration-specific persistence | Column derivations, grouping predicates, logical invariants, clinical-content/result states |
| Insurance/claims/finance | Deterministic memberships; preserve reported facts; decimal reconciliation; unsupported histories empty | Exact field/equation/opening/posting-gate contracts |
| Pharmacy/stock | Preserve snapshots; approved labelled openings only; negatives/expired unavailable pending review | Product/unit/location/batch maps, opening equation, disposition and reconciliation |
| Privacy/audit | Notification content excluded; empty modules excluded with fingerprint stop; separate migration audit | Audit/retention design and zero-import/scope-guard checks |
| Incremental/cutover | Per-table evidence-backed extraction, staged freeze/final delta, isolated runtime, fail-closed `uuhms` guards | Table extraction contracts, runtime runbook, thresholds and rollback |

## Patient, encounter, and clinical mappings

| Legacy source | Candidate renewed destination | Confidence | Required transformation/decision |
|---|---|---|---|
| `patients` | `patients`; emergency contact fields/children; aliases | Decision/candidate | Split names; missing-required-field and identifier policy; duplicate normalized OPD aliases collide with target global uniqueness; preserve legacy key only in mapping |
| `insurance` | patient insurances, providers/tiers, verification source facts | Decision/candidate | Provider natural-key map; zero/invalid dates; member-number blanks/duplicates; resolve 36 duplicate patient/provider-key groups covering 88 rows |
| `attendance` | visits; visit insurance/payment context; status/department/pathway history; possibly admissions | Candidate, multi-destination | Split visit type/global status/pathway; patient/user/bed orphans; placeholder dates; do not synthesize emergency cases from labels alone |
| `appointement` | appointments with deferred visit link | Decision/high gap | `AppDate` supplies candidate date/start time for all observed rows, subject to timezone/semantic validation; approve inheritance/quarantine for patient, department, creator and number; 67 attendance links unmatched |
| `beds` | wards/beds plus separately reconciled active state | Candidate | Bed/ward natural keys; do not copy patient occupancy snapshot blindly |
| `consult_complaints` | complaints | Decision/candidate | Approve encounter→route→medical-record grouping and clinician; catalogue/free-text precedence; empty descriptions |
| `consult_diagnosis` | diagnoses and ICD links | Decision/candidate | Approve route/record grouping and clinician; catalogue crosswalk unreliable; preserve free text/doctor snapshot |
| `consult_history` | HOPC/history entry | Candidate | Plain versus RTF precedence |
| `consult_prescriptions` | prescriptions/medication orders and possibly billing source links | Candidate | Product/actor/visit mappings; quantity/status/billing semantics |
| `consult_procedures` | procedures/theatre or clinical procedure history | Decision | Procedure type/status/result semantics and catalogue map |
| `consult_scan_lab` | investigations/orders/results | Candidate | Service map; all-zero actor; result/status mapping; avoid live lab events |
| `consult_services` | visit service items or historical service renderings | Decision | Meaning of `DONE/BILLED`, missing services, no target workflow generation |
| `consult_treatment` | treatment plan/clinical entry | Candidate | Plain versus RTF precedence |
| `nurses_note` | nursing notes | Candidate | Actor map and RTF/plain precedence |
| `treatment` | medication administration/treatment history | Decision | Route/dosage/routine semantics, actor/product gaps |
| `vitals` | vitals/clinical observations | Candidate | Parse strings; retain raw unparseable values; routine/time semantics |
| `mat_family` | no operational destination for approved baseline | Exclude (0 rows, approved) | Fingerprint/nonzero change is a stop condition requiring new scope review |
| `mat_obstetrics` | no operational destination for approved baseline | Exclude (0 rows, approved) | Fingerprint/nonzero change is a stop condition requiring new scope review |

## Clinical/reference and investigation mappings

| Legacy source | Candidate renewed destination | Confidence | Required transformation/decision |
|---|---|---|---|
| `services` | service catalogue and price records | Candidate | Department/type/natural-key map; multiple payer prices; blank result types |
| `serv_group` | investigation service groups/panels | Candidate | Resolve 9 service orphans |
| `serv_criterias` | investigation criteria/analytes | Candidate | Resolve 7 group orphans; unit/normal range transform |
| `serv_options_cri` | criterion selectable options | Candidate, keyless | Deduplicate; all current criterion links unmatched—catalogue-version decision |
| `serv_options_out` | result options | Candidate, keyless | Deduplicate and service natural-key map |
| `serv_results` | structured investigation result values | Candidate with severe link risk | No timestamps; mostly no order link; encounter/service/criterion exceptions; empty values |
| `list_complaints` | complaint catalogue | Candidate | Normalize/deduplicate without altering historical text |
| `list_diagnosis` | ICD/diagnosis catalogue crosswalk | Candidate | Missing/duplicate codes; do not assume code uniqueness |
| `list_procedures` | procedure catalogue/pricing | Candidate | Type/code/natural-key/pricing map |
| `list_causes` | diagnosis causes/reference | No approved destination | All inferred parents unmatched; clinical-owner decision |
| `list_disorder` | disorder reference | Exclude proposed | One unused row; all diagnosis disorder IDs zero |
| `list_signs` | sign reference | Exclude proposed | Two rows; both inferred parents unmatched |
| `list_symptoms` | symptom reference | No approved destination | Long-text parent identifiers; semantic/manual review |

## Pharmacy, stock, and procurement mappings

| Legacy source | Candidate renewed destination | Confidence | Required transformation/decision |
|---|---|---|---|
| `medicine` | products/drugs/generics/categories/prices | Candidate | Missing names/codes/categories; type/unit/natural-key maps |
| `med_pharm` | source snapshot evidence plus approved labelled opening position | Policy approved; mapping pending | No movement reconstruction; map product/location/unit; exclude negatives and unresolved duplicates/orphans |
| `med_store` | source snapshot evidence plus approved labelled opening position | Policy approved; mapping pending | No movement reconstruction; map product/location/unit; exclude negatives and unresolved duplicates/orphans |
| `batch` | product batches/lots | Candidate | Product/supplier links; expiry status/date reconciliation |
| `request` | stock requisitions/history | Decision | Negative quantities, missing product/actors, ambiguous current quantities |
| `suppliers` | suppliers; possibly approved opening payable | Candidate/decision | Natural-key mapping; financial balances handled separately |

## Billing, claims, and accounting mappings

| Legacy source | Candidate renewed destination | Confidence | Required transformation/decision |
|---|---|---|---|
| `billing` | approved source facts plus separately labelled approved opening representation | Policy approved; mapping high risk | Preserve Bill/Discount/Paid/Balance; decimal equation; 0.01 fail-closed exceptions; no invented allocations/postings |
| `claims` | claims with patient/visit/provider/invoice maps and protected reported/components facts | Policy approved; mapping high risk | Explicit status/date maps; decimal component reconciliation; differences over 0.01 cannot post |
| `claims_diagnosis` | claim diagnosis items | Candidate | Claim/catalogue orphan handling |
| `claims_prescriptions` | claim medication items | Candidate | Claim/product orphan handling and quantity/tariff precision |
| `claims_procedures` | claim procedure items | Candidate | Claim/procedure mapping |
| `claims_scan_lab` | claim investigation/procedure items | Decision | Only 3 rows; destination semantics |
| `claims_specialty` | specialties/crosswalk | Candidate | Reconcile with seeded specialties |
| `accounts` | protected source facts and only explicitly approved labelled opening evidence | Policy approved; mapping pending | Classic is not double-entry; unsupported journal/subledger history starts empty |
| `acc_titles` | account category/chart crosswalk | Candidate | Natural-key reconciliation; no source timestamps |
| `bank` | bank account configuration and possibly opening balance | Decision | Configure identity separately from balance |
| `acc_petty` | petty-cash configuration/opening evidence | No approved destination | Finance must define meaning of reset fields |

## Administration and operational mappings

| Legacy source | Candidate renewed destination | Confidence | Required transformation/decision |
|---|---|---|---|
| `departements` | departments and service associations | Candidate | Natural-key/type/billable/permission crosswalk |
| `users` | users/staff attribution map | Candidate for identity only | Exclude passwords and blanket permission flags; inactive placeholders policy |
| `hospitalinfo` | application/facility configuration | Manual configure | Approve each field; do not migrate signature/assets automatically |
| `sett_private` | insurance provider/type reference | Candidate | Natural-key/provider-type mapping |
| `sett_ocuupation` | no operational destination for approved baseline | Exclude (0 rows, approved) | Fingerprint/nonzero change is a stop condition requiring scope review |
| `gens` | no row-level destination; identifier-sequence evidence | Exclude from direct migration | Target sequence is reconciled after patient load |
| `notifications` | no renewed operational notification rows | Exclude (approved) | Retain non-identifying aggregate reconciliation only; zero historical triggers |

## Destination domains with no source mapping

The following target domains have no known Classic source and must not receive invented records: renewed authorization assignments; patient privacy/merge/archive/financial-risk workflows; employee/shift/staff-attendance/leave/payroll operations; emergency-case workflow; payment timing/financial-clearance history; journey events/predictions; integration credentials/events; notification preferences/digests; blood bank; populated maternity/newborn/postnatal; complete service-rendering workflow; refunds/credit notes/payment allocations; detailed procurement/GRN/returns; journal/subledger/budget/tax/payroll/assets and bank reconciliation; analyzer integration; and front-desk operational/audit logs.

## Legacy data with no approved destination

No operational destination is approved for `acc_petty` reset semantics, `list_causes`, `list_symptoms`, operational `notifications`, Classic sequence rows (`gens`), or the unused `list_disorder`/`list_signs` records. Classic passwords and permission flags are explicitly excluded. Unresolved bed snapshots, malformed/sentinel references, duplicate identities, invalid dates, and unreconciled financial/stock values are protected evidence or classified exceptions, not silently dropped and not operational target records. Phase 2 may refine a destination only when evidence and the accepted policy support it.
