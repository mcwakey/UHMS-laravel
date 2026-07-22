# Phase 2 entry report

## Entry determination

**Phase 2 detailed mapping may begin.** All Phase 1B business-policy questions are resolved by the direct project-owner decision dated 2026-07-21, and the consolidated package passed independent migration review. No workshop occurred; all nine workshop instruments are superseded by final-authority decision.

This entry authorizes documentation and mapping design only. Importer implementation, migration-state tables, UI/orchestration, synchronization runtime, target writes, production tests, and cutover remain blocked.

## Authority

- Approval method: Direct project-owner decision
- Approver: `mcwakey` (established repository identity)
- Role: Project Owner and Final Decision Authority
- Date: 2026-07-21
- Reference: Project-owner directive recorded in the migration decision register

## Domains unlocked for detailed mapping

All evidence-catalogued domains may enter detailed mapping under the approved policies: organisation/reference data; staff identity/attribution; patient identity, aliases, contacts, and demographic children; patient insurance; appointments; visits/workflow; consultation routes; clinical history; investigations/results; admissions; billing; claims; payments/accounting opening design; pharmacy catalogues; stock/procurement; privacy/audit; and incremental/cutover design.

“Unlocked” means the team may specify source columns, target columns, transformations, exception outcomes, reconciliations, and extraction rules. It does not mean each source row has an approved destination or that any operational target domain should be populated.

## Required sequence

1. Organisation and reference crosswalks
2. Staff identity and historical attribution
3. Patient identity and registration
4. Patient aliases, contacts and demographic children
5. Patient insurance
6. Patient pilot mapping contract
7. Visits and workflow
8. Clinical history
9. Admissions
10. Billing and claims
11. Pharmacy and stock
12. Incremental synchronization and cutover

## Patient-pilot position

The patient-pilot mapping contract may begin after stages 1–5 produce the reference, staff, patient, alias/contact, and insurance contracts it consumes. The mapping work may start now in sequence; no patient importer or target write is authorized.

## Phase 2 deliverables required before importer implementation

- Complete column-level source-to-target maps with explicit crosswalk ownership.
- Approved transformation specifications implementing the owner policies without defaults.
- Stable exception codes, chain handling, manual-review roles, and SLAs.
- Reconciliation contracts with counts, equations, tolerances, and fail-closed gates.
- Per-table extraction/watermark/snapshot specifications.
- Migration-specific persistence, provenance/audit, security, and runtime-isolation designs.
- A verified dedicated SELECT/metadata-only Classic account on exactly `uuhms`.
- Independent review of the mapping package and migration-foundation design.

## Remaining blockers

No domain is blocked from *detailed mapping* by unsigned business policy. Every domain remains blocked from importer implementation until its Phase 2 artefacts above are complete and reviewed. Environment-specific cutover dates, thresholds, runtime controls, and restricted-account provisioning also remain execution blockers.

## Exact next action

Start **Phase 2A — Organisation and Reference Crosswalk Specifications**. Produce column-level mappings, natural-key/deduplication rules, explicit value crosswalks, relationship/sentinel rules, exception codes, reconciliation contracts, and extraction strategies for organisation, departments, service/clinical reference catalogues, insurance providers, specialties, wards/beds, products, suppliers, stock locations, and finance reference data. Do not implement importers or write either database.
