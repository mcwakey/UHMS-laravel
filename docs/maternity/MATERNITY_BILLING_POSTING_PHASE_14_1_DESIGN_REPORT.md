# Maternity Billing Posting Phase 14.1 Design Report

## Scope

Phase 14.1 is a billing posting design and safety audit. It adds preview-only scaffolding for maternity billing events and does not post charges, create invoices, recalculate historical invoices, change ledger behavior, dispense stock, or alter insurance pricing.

## Existing Billing Architecture Reviewed

The safest billing funnel to reuse in a later posting phase is `App\Services\BillingService`:

- `addItemToVisitInvoice()` creates service invoice items on the single visit invoice.
- `addProductToVisitInvoice()` creates product invoice items on the single visit invoice.
- Both methods preserve source references through `source_type` and `source_id`.
- Both methods use duplicate guards before inserting invoice items.
- Service pricing is resolved through `ServicePriceResolver`.
- Product pricing is resolved through `ProductPriceResolver`.
- Insurance coverage and limit usage are handled inside the existing billing path.
- Invoice totals and receivable sync are handled by `InvoiceService::recalculateTotals()`.

Reviewed billing patterns:

- Admission billing uses `AdmissionBedBillingService` and stores linkage records such as admission bed charges and daily consumable charges.
- Emergency billing uses `EmergencyBillingService`, `EmergencyBedBillingService`, `EmergencyMedicationService`, and `EmergencyConsumableService`, all funnelling through `BillingService`.
- Procedure billing uses `ProcedureWorkflowService` with source type `procedure_request`.
- Lab/investigation billing uses `InvestigationRequestService` with source type `lab_request_item`.
- Pharmacy billing uses `PharmacyBillingSelectionService` and separates selection/billing from dispensing.
- Credit notes, write-offs, reversals, refunds, and accounting posting already have dedicated services and were not changed.

## Maternity Billing Readiness Reviewed

Phase 13 readiness is warning-only and remains so.

Reviewed components:

- `maternity_service_mappings` table.
- `App\Models\MaternityServiceMapping`.
- `App\Services\Maternity\MaternityBillingReadinessService`.
- Billing readiness UI at `admin.maternity.billing-readiness.show`.

Mapping keys reviewed:

- ANC registration/package
- ANC follow-up
- Maternity admission
- Labor observation
- Normal delivery
- Assisted delivery
- Caesarean/theatre handoff
- Delivery consumables
- Newborn care
- Neonatal observation
- Newborn resuscitation
- Postnatal mother care
- Postnatal newborn care
- Immunisation placeholder
- Ultrasound placeholder
- Maternity consumables

## Billable Event Design

Each maternity charge should be keyed by:

- source model
- source ID
- mapping key
- responsible patient context
- visit/admission context
- invoice source type

Proposed event mapping:

| Event | Source | Mapping key | Default billing context | Automatic |
| --- | --- | --- | --- | --- |
| ANC registration/package | `AntenatalVisit` visit 1 | `anc_registration_package` | Mother visit/admission | No |
| ANC follow-up | `AntenatalVisit` visit 2+ | `anc_follow_up` | Mother visit/admission | No |
| Maternity admission | `LaborEpisode` with admission | `maternity_admission` | Mother visit/admission | No |
| Labor observation/care | `LaborEpisode` | `labor_observation` | Mother visit/admission | No |
| Normal delivery | `DeliveryRecord` | `normal_delivery` | Mother visit/admission | No |
| Assisted delivery | `DeliveryRecord` | `assisted_delivery` | Mother visit/admission | No |
| Caesarean/theatre handoff | `DeliveryRecord` | `caesarean_theatre_handoff` | Mother visit/admission | No |
| Delivery consumables | `DeliveryRecord` | `delivery_consumables` | Mother visit/admission | No |
| Newborn care | `NewbornRecord` | `newborn_care` | Mother by default | No |
| Neonatal observation | `NewbornRecord` | `neonatal_observation` | Mother by default | No |
| Newborn resuscitation | `NewbornRecord` | `newborn_resuscitation` | Mother by default | No |
| Postnatal mother care | `PostnatalCase` | `postnatal_mother_care` | Mother visit/admission | No |
| Postnatal newborn care | `PostnatalCase` | `postnatal_newborn_care` | Mother by default | No |

Missing mappings must block posting. They are shown as warnings in preview.

## Duplicate Prevention Strategy

The proposed duplicate identity is:

```text
source_type + source_id + mapping_key
```

For future invoice items, the planned invoice source type is:

```text
maternity_{mapping_key}
```

This keeps multiple billable events on one clinical source distinct. Example: a delivery can later have both `maternity_normal_delivery` and `maternity_delivery_consumables` against the same `delivery_record` ID.

Phase 14.1 also adds `maternity_billing_events` as a maternity linkage/audit table. This table does not replace invoices. It records source-to-billing linkage and future duplicate/audit state.

## Mother/Newborn Billing Policy

Added billing configuration:

```php
'maternity_billing' => [
    'enabled' => env('MATERNITY_BILLING_ENABLED', false),
    'auto_post' => env('MATERNITY_BILLING_AUTO_POST', false),
    'newborn_billing_policy' => env('MATERNITY_NEWBORN_BILLING_POLICY', 'mother'),
],
```

Allowed newborn policies:

- `mother`: newborn care previews bill to the mother context.
- `newborn_if_linked`: newborn care previews bill to linked newborn patient when present, otherwise mother.
- `disabled`: newborn care previews are blocked.

Defaults avoid unexpected billing.

## Service Design Implemented

Added `App\Services\Maternity\MaternityBillingPostingService`.

Implemented preview methods:

- `previewManyForSource(Model $sourceModel)`
- `previewForSource(Model $sourceModel, string $mappingKey)`
- `alreadyPosted(Model $sourceModel, string $mappingKey)`
- `resolveMapping(string $mappingKey)`
- `resolveBillingContext(Model $sourceModel, string $mappingKey)`
- `buildInvoiceItemPayload(...)`

`postForSource()` exists only as an explicit non-posting placeholder and returns `posting_not_implemented`.

Preview statuses:

- `missing_mapping`
- `mapping_disabled`
- `service_inactive`
- `already_posted`
- `billing_disabled`
- `newborn_billing_disabled`
- `ready_to_post`
- `posting_not_implemented`

## Table Added

Added `maternity_billing_events` with:

- mapping key
- source type/source ID
- patient/visit/admission
- invoice/invoice item linkage
- service/amount
- status
- posted actor/timestamp
- skipped reason
- metadata

Indexes support source lookup, invoice lookup, and status review.

## UI Preview Behavior

Added a read-only billing preview panel to:

- ANC visit detail
- Labor episode detail
- Delivery record detail
- Newborn record detail
- Postnatal case detail

The panel is gated by `maternity.billing.preview`, shows relevant mapping keys, configured/missing service, estimated amount, status, and a warning that no posting happens in this phase. It exposes no posting button.

## Permissions Added

Added permissions:

- `maternity.billing.preview`
- `maternity.billing.post`
- `maternity.billing.override`
- `maternity.billing.audit.view`

Clinical maternity roles receive preview/audit view only. Accountant receives preview/post/audit view. Finance Manager receives override through the existing finance role expansion. Admin/super-admin receive all permissions through the existing seeder pattern.

## Localisation Added

Added EN/FR Phase 14 maternity keys for:

- maternity billing
- billing preview
- posting disabled
- missing mapping/status reasons
- inactive service
- already posted
- ready to post
- duplicate prevention
- mother/newborn billing
- newborn billing policies
- billing event/audit

## Intentionally Deferred

Deferred to Phase 14.2:

- Manual posting button/action.
- Calls to `BillingService::addItemToVisitInvoice()`.
- Invoice creation.
- Ledger/accounting posting.
- Reversal integration for maternity billing events.
- Stock consumption.
- Pharmacy dispensing.
- Theatre/emergency/lab/radiology creation hooks.
- Historical invoice recalculation.

## Risks

- Some maternity sources have more than one clinically plausible billable event. Phase 14.2 should require explicit user selection for manual posting.
- Newborn separate billing requires linked newborn patients and site approval before activation.
- Future reversal integration must keep `maternity_billing_events` aligned with credit notes/write-offs/reversals.
- Preview estimates use mapped service base price. Final posting must continue to use existing visit insurance pricing resolution.

## Next Phase Recommendation

Proceed with Phase 14.2: Controlled Maternity Billing Posting for Manual Actions Only.

Recommended Phase 14.2 guardrails:

- Keep `MATERNITY_BILLING_ENABLED=false` by default.
- Add a manual post action behind `maternity.billing.post`.
- Use `BillingService::addItemToVisitInvoice()` only.
- Write `maternity_billing_events` records atomically with invoice item creation.
- Preserve existing duplicate guards and add tests for repeated clicks.
- Do not introduce auto-posting until manual posting is proven safe.
