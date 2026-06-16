# Accounting Execution Phase H: Fixed Assets and Depreciation

## Scope

Phase H adds the fixed asset accounting foundation: asset register, category mappings, capitalization, custody/location tracking, depreciation runs, disposal and verification.

## Delivered

- Added optional `fixed_assets` module dependent on `accounting_advanced`.
- Added chart accounts for:
  - Accumulated Depreciation
  - Depreciation Expense
  - Gain on Fixed Asset Disposal
  - Loss on Fixed Asset Disposal
- Added fixed asset schema:
  - `asset_categories`
  - `fixed_assets`
  - `asset_acquisitions`
  - `asset_locations`
  - `asset_custody_assignments`
  - `asset_transfers`
  - `asset_depreciation_runs`
  - `asset_depreciation_lines`
  - `asset_impairments`
  - `asset_disposals`
  - `asset_verifications`
- Added fixed asset workbench under Accounting.
- Added permissions for view, manage, capitalize, transfer, depreciate, impair, dispose and verify.
- Added fixed asset service operations for:
  - Draft asset creation
  - Capitalization journal
  - Straight-line depreciation runs
  - Location/custodian transfers
  - Verification
  - Disposal journal with gain/loss calculation

## Accounting Rules

- Capitalization posts `Dr Fixed Asset Cost / Cr Cash, Bank or Clearing`.
- Depreciation posts `Dr Depreciation Expense / Cr Accumulated Depreciation`.
- Depreciation is idempotent by accounting period.
- Disposal removes asset cost and accumulated depreciation, records proceeds, and posts gain or loss.
- No depreciation is run before the asset is placed in service or after disposal.

## Verification

Covered by `tests/Feature/Accounting/FixedAssetPhaseHTest.php`.
