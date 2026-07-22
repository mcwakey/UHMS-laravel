# Phase 2A — Organisation and Reference Crosswalk Specifications

Status: **Specification package complete; implementation is not authorized.**

## Authority and evidence

`APPROVED_DECISION_SPECIFICATIONS.md` and `DECISIONS.md` are the policy ceiling. Phase 1 evidence is preserved. Labels used throughout this package are:

- **Confirmed** — observed in repository code, installed-target metadata, or sanitized Classic aggregate evidence.
- **Approved policy** — direct project-owner decision already recorded.
- **Inference** — plausible interpretation that is not accepted as a mapping.
- **Technical specification** — Phase 2 design rule derived within the approved policy.
- **Open technical input** — required mapping/configuration work; not a governance reversal.

Only the Classic schema `uuhms` is approved. It remains read-only. No application/database behavior, importer, migration-state table, UI, synchronization runtime, seeder execution, or database row was introduced.

## Package

The domain documents define human-reviewable contracts. `specifications/` contains eight versioned machine-readable specifications. The column manifest classifies 178 columns across 28 reference or reference-adjacent Classic tables exactly once. `serv_results` is explicitly outside this reference phase: it is clinical history and remains deferred.

## Scope boundary

Phase 2A specifies organisation settings, departments, specialties, insurance providers, service/investigation/clinical catalogues, wards/beds, products, suppliers, stock locations and approved finance references. Stock quantities, batches, requests, current bed occupancy, memberships, claims, payments, balances, journals and postings are dependency evidence only or deferred.

## Required use

1. Validate source and target fingerprints.
2. Review target-existing reference rows read-only.
3. Resolve the red readiness cells and configuration inputs.
4. Version any approved technical crosswalk change.
5. Re-run coverage, relationship and reconciliation validation.
6. Obtain independent review before entering Phase 2B.

No document here authorizes persistence.

## Seeder allow-list recommendation

The Phase 2A migration-execution allow-list is **empty**. `DatabaseSeeder` is prohibited. Existing target rows produced by `CountryLocationSeeder`, `CashAndCarrySeeder`, `DepartmentSeeder`, `SpecialtySeeder`, `AccountCategorySeeder`, `ServiceCatalogSeeder`, `LabCatalogSeeder` and other reference seeders are read-only reconciliation candidates, not evidence of source equivalence and not commands to rerun.

`InsuranceProviderSeeder`, `ProductAndDrugSeeder`, `SupplierSeeder`, `WardAndBedSeeder`, `AccountingChartSeeder`, `AccountingPostingTemplateSeeder`, `InsurancePricingSeeder`, all demo/manual/testing seeders, and any operational seeder are specifically excluded from migration execution because they introduce nonsource values or can mutate prices, opening stock, occupancy, contacts, accounting periods or transactions.

## Future persistence boundary

[Approved policy] D-214 requires any later historical/reference persistence to use the migration-specific boundary. Normal controllers and operational services are not creation APIs for this package: department paths can synchronize stock locations and log activity; stock-location resolvers can create/select defaults; bed workflows enforce live occupancy; product, pricing, billing, stock and accounting paths can cause operational effects. Phase 2A performs none of these calls.
