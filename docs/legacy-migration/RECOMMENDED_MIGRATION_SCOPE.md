# Recommended preliminary migration scope

## Scope status

The project owner accepted the policy boundaries in this scope by direct decision on 2026-07-21. This is authorization to begin detailed mapping, not authorization to migrate. Inclusion remains conditional on column-level mappings, transformations, exception rules, reconciliation contracts, extraction design, and a reviewed migration foundation.

## Core scope recommended

1. **Reference crosswalks**: departments, specialties, insurance providers, services, clinical catalogues, products/drugs, suppliers, wards/beds, and only approved accounting references.
2. **Staff attribution**: mapped staff identities needed to preserve historical authorship; no credentials or Classic authorization grants.
3. **Patient pilot**: patients, emergency/contact fields, unique valid legacy-number aliases, protected Classic-PK mappings, and deterministic insurance membership under the accepted identity policy.
4. **Encounter history**: appointments, visits, approved workflow/pathway facts, and insurance context.
5. **Clinical history**: medical records, complaints/history/diagnoses/treatments, prescriptions, procedures, investigations/results, vitals, and nursing notes where relationships/content pass approved rules.
6. **Admissions**: only where the Phase 2 field rules establish valid dates, actors, beds, and corroborated episodes; equal dates alone do not establish admission.
7. **Claims/billing**: only source-evidenced facts under the accepted decimal reconciliation policy and a clearly labelled approved opening/cutover contract. Renewed allocations/refunds/credits/GL history starts empty unless evidenced.
8. **Stock/procurement**: only source-evidenced snapshots/batches/requests and a clearly labelled approved opening contract. No movement reconstruction; negative and expired quantities are unavailable pending controlled review/disposition.

## Configure/seed rather than migrate

- Roles, permissions, modules, renewed department access, notification preferences.
- Patient-number sequences after imported identifier reconciliation.
- Payment timing/financial-clearance policy.
- Integration credentials and providers.
- Renewed accounting templates, fiscal controls, service-rendering workflow, and analyzer configuration.
- Facility settings from `hospitalinfo`, field by field and without automatic signature/asset copying.
- Human-resources structures and operational emergency-case workflows start empty/configured unless a source-evidenced mapping is approved.
- Use an explicit allow-list of reference seeders. Never run `DatabaseSeeder`, which also seeds demo users, patients, and appointments.

## Proposed exclusions

- Classic passwords and per-user permission flags.
- Operational `notifications`; retain only non-identifying aggregate reconciliation unless a new explicit legal-retention decision is made.
- Empty `mat_family`, `mat_obstetrics`, and `sett_ocuupation`; a fingerprint or nonzero-count change stops execution for scope review.
- Dormant/incomplete `list_disorder` and `list_signs` unless clinical owners approve.
- Classic sequence rows (`gens`) as business data.
- Current bed occupancy snapshots before final cutover reconciliation.
- Synthetic target-only histories: merge/privacy, journey, audit, integration, notification, service-rendering, receivable, allocation, GL, or stock events not evidenced by Classic.
- Any orphan/malformed/unreconciled row lacking an approved destination rule; such rows remain explicit exceptions, not silent exclusions.

## Conditional/no-approved-destination items

- `acc_petty`, `list_causes`, and `list_symptoms` have no approved operational destination; Phase 2 may propose one only with supporting evidence and a controlled specification.
- Financial account/bank/supplier balances require an exact, labelled opening contract; unsupported histories remain empty.
- `consult_services`, `treatment`, `claims_scan_lab`, and stock `request` require field-level destination semantics under the accepted no-invention policy.
- `serv_results` is potentially valuable clinical history but needs a robust fallback linkage strategy because almost all order IDs are zero and it has no timestamp.

## Pilot recommendation

After the patient-pilot mapping contract and migration foundation are independently reviewed, pilot patients first using synthetic/anonymised fixtures plus aggregate source dry-run evidence. Include deliberately difficult cases: missing required fields, duplicate/blank OPD numbers, malformed gender/phone/date, multiple insurance rows, invalid insurance dates, and downstream encounter references. The pilot must prove no name-only merge, no source-PK reuse, idempotent rerun, resume from checkpoint, deterministic failures, mapping audit, and patient-number sequence reconciliation.

## Discovery readiness assessment

Phase 1B actual-target and reproducible Classic evidence is captured. The direct-owner directive resolves the business-policy gates, so **every domain may enter Phase 2 detailed mapping** in the approved dependency order. Importer implementation remains blocked because the column maps, transformation specifications, exception taxonomy/SLAs, reconciliation contracts, extraction contracts, and reviewed migration foundation do not yet exist.

## Exact next phase

**Begin Phase 2A — Organisation and Reference Crosswalk Specifications.** Produce column-level natural-key/deduplication maps, explicit value and sentinel rules, exception codes, reconciliation contracts, and per-table extraction strategies. Do not implement importers or write either database.
