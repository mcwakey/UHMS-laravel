# Target required fields and constraints

This is the discovery-level contract for high-priority target domains, not a substitute for a column-level mapping specification. Phase 1B verified it against the actual non-production catalogue; the machine-complete types/defaults/nullability/keys/constraints are in `TARGET_CONSTRAINT_MANIFEST.json`.

## Patients

Evidence: `database/migrations/2026_04_11_300001_create_patients_table.php`.

- Required: unique `patient_number`, `first_name`, `last_name`, `date_of_birth`, `gender`, `phone`.
- Nullable: other names, blood group, marital status, secondary contact, email, Ghana Card, occupation/address, emergency contact, avatar, clinical summary, `registered_by`.
- Ghana Card is nullable-unique; patient has timestamps and soft deletes.
- Model casts use enums for gender/blood group/marital status.
- Later migration `database/migrations/2026_04_13_095727_remove_nhis_fields_from_patients_table.php` removes patient-level `nhis_number` and `nhis_expiry_date`; insurance belongs in `patient_insurances`.
- Patient aliases have a global unique constraint on `(alias_type, normalized_alias_value)` in `database/migrations/2026_05_28_091000_create_patient_merge_tables.php`, so duplicate normalized Classic OPD aliases cannot be attached to different target patients.
- Patient insurance permits one row per patient/provider in `database/migrations/2026_04_13_100001_create_patient_insurances_table.php`; 36 duplicate source provider-key groups covering 88 rows require an approved collapse/history rule before provider consolidation.

**Source conflict:** blank names/OPD/sex/phone, duplicate OPD, malformed sex/date/phone values.

The missing-required-field disposition and duplicate-alias strategy are blocking governance decisions; placeholders and arbitrary alias ownership are prohibited.

## Visits and appointments

Evidence: `2026_04_11_400001_create_visits_table.php` and appointment migrations.

- Visit requires unique number, patient, type, visit date, and creator; status/priority default.
- Appointment requires unique number, patient, department, date, start time, and creator.
- Appointment doctor and later visit/route/record links are nullable.
- Visit and appointment are soft-deletable.

**Source conflict:** many missing users/patients, mixed status/pathway values, historical appointment validation, unreliable admission placeholders.

Classic `appointement` has only `APP_ID`, nullable `ATT_ID`, and nullable `DATETIME AppDate`, with 67 unmatched attendance links. In the observed 1,364 rows, `AppDate` is populated and non-midnight in every row, so it is candidate start-date/time evidence subject to timezone/semantic validation. The source does not directly evidence required target patient, department, creator, or number. Appointment import is conditional on an approved inheritance/quarantine rule.

## Consultation and clinical records

- Medical record requires visit, patient, and doctor.
- Complaint requires medical record and description.
- Diagnosis requires medical record and description; type defaults provisional.
- Investigation requires medical record, type, and description; status/urgency have defaults.
- Treatment requires medical record, type, and description.
- Route/service/department linkage was added later and may lack complete MariaDB FK enforcement.

**Source conflict:** catalogue and encounter orphans; many empty descriptions/results; source doctor can be text while target needs a user.

Classic has no consultation-route event model. Creating target routes/medical records for child attachment requires an approved deterministic encounter grouping, clinician attribution, and duplicate rule; it is not an automatic structural mapping.

## Admissions

Evidence: `2026_04_12_400003_create_admissions_table.php`.

- Required: unique admission number, visit, patient, bed, admitting user, admission date.
- Discharge date/user/summary/instructions nullable at DB level; web/domain rules may require more.
- Status defaults admitted; timestamps and soft deletes.

**Source conflict:** bed identifiers are mostly sentinel/unmatched; actor gaps; reversed/default dates.

## Claims and billing

- Claim requires unique number, provider, patient, visit, claim/period dates, amount, creator; invoice and assigned doctor nullable.
- Invoice items retain decimal pricing, coverage, patient-payable, paid, balance, payer, source, and payment-status snapshots.
- Payment allocation requires payment, invoice item, and decimal(12,2) amount.
- Active-invoice uniqueness may be service-only depending on installed DDL outcome.

**Source conflict:** billing is a flattened double-precision snapshot, every `SERV_ID` is zero, user/visit orphans exist, and candidate equations do not always reconcile.

## Inventory and accounting

- Stock movement requires product, location, type, direction, decimal(14,4) quantity, and movement date.
- Balance is unique per product/location.
- Journal requires unique number, entry date, fiscal year, accounting period, description, status, and exactly balanced lines.
- Historical journal posting through the operational service requires an open period and stamps current context.

**Source conflict:** Classic primarily has stock snapshots, not a complete movement ledger; its small accounts tables do not represent renewed double-entry structures.

## Cross-cutting constraints

- Do not turn Classic inactive/cancelled values into soft deletes without an approved rule.
- Use decimal strings/integers for transformations; never binary floats for reconciliation.
- Validate enum strings before persistence; unknown casts can fail at hydration.
- Validate logical links not enforced by the installed DB.
- Supply approved migration actor/history attribution rather than falling back to current/first user.
- Preserve valid source timestamps through the persistence boundary, and label missing/derived times.
