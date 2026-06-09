# Accounting Phase 4 Receivables & AR Aging Report

## Scope

Implemented payer-based accounts receivable for UHMS billing:

- Patient receivables
- Insurance receivables
- Sponsor receivables
- Corporate client receivables
- Payer-specific payment recording
- Manual payer responsibility reallocation
- Payer-based AR aging report

Existing invoice, payment, sponsor, insurance, claim, discount, credit note, write-off, and accounting posting workflows were extended rather than replaced.

## Existing System Found

- Sponsors already existed through `sponsors`, `Sponsor`, `SponsorController`, and `admin.billing.sponsors.*`.
- Insurance providers and claims already existed and are reused for insurance receivables.
- Phase 2 accounting settings already included separate receivable control accounts:
  - patient receivables
  - insurance receivables
  - sponsor receivables
  - corporate receivables
- The previous AR aging report was invoice-header based. It has now been moved to payer receivable rows.

## Schema Added

- `corporate_clients`
- `sponsor_authorizations`
- `invoice_receivables`
- `invoices.corporate_client_id`
- payer fields on `payments`:
  - `invoice_receivable_id`
  - `payer_type`
  - `payer_id`
  - `insurance_provider_id`
  - `sponsor_id`
  - `corporate_client_id`
  - `claim_id`

MariaDB compatibility note: `invoice_receivables.metadata` uses `LONGTEXT` instead of raw `JSON`.

## Models Added

- `CorporateClient`
- `SponsorAuthorization`
- `InvoiceReceivable`

Relationships were added to invoices, payments, sponsors, and insurance providers.

## Services Added

- `InvoiceReceivableService`
- `ReceivableAllocationService`
- `ReceivableAccountingPostingService`
- `ARAgingService`

Key behavior:

- invoices sync payer receivable rows during invoice recalculation
- new payments are linked to the selected payer receivable
- payment reversals inherit the original payer/receivable identity
- reallocation posts accounting Dr target AR / Cr source AR
- AR aging backfills open invoices that have no receivable rows yet

## UI Changes

- Invoice detail now shows **Payer Responsibility / Receivables**.
- Invoice payment form now allows selecting the paying party.
- Payment history now displays payer type/name.
- Invoice detail includes a **Reallocate** modal guarded by `receivables.reallocate`.
- AR aging report now filters by payer type, payer master, status, and as-of date.
- Invoice edit page supports corporate clients for corporate AR.

## Routes / Backend Enforcement

- Added payer reallocation route:
  - `POST admin/billing/invoices/{invoice}/receivables/reallocate`
  - guarded by `receivables.reallocate`
- AR aging routes now require `reports.ar_aging.view`.
- payer-specific AR aging filters enforce:
  - `reports.ar_aging.patient`
  - `reports.ar_aging.insurance`
  - `reports.ar_aging.sponsor`
  - `reports.ar_aging.corporate`
- Sponsor routes now use granular middleware while `sponsors.manage` remains a compatibility alias through `Gate::before`.

## Permissions Added

- `receivables.view`
- `receivables.allocate`
- `receivables.reallocate`
- `receivables.payment.record`
- `receivables.write_off`
- `sponsors.view`
- `sponsors.create`
- `sponsors.edit`
- `sponsors.authorize`
- `sponsors.payment.record`
- `corporate_clients.view`
- `corporate_clients.create`
- `corporate_clients.edit`
- `corporate_clients.payment.record`
- `reports.ar_aging.view`
- `reports.ar_aging.patient`
- `reports.ar_aging.insurance`
- `reports.ar_aging.sponsor`
- `reports.ar_aging.corporate`

Role defaults:

- Admin / Super Admin inherit all.
- Accountant receives receivable allocation, payer management, sponsor/corporate payment, and AR aging permissions.
- Cashier receives `receivables.view`, `receivables.payment.record`, and general AR aging view only.
- Clinical roles were not given receivable or sponsor/corporate permissions.

## Logging

- Receivable reallocation logs `RECEIVABLE_RESPONSIBILITY_REALLOCATED`.
- Reallocation accounting posts log success/failure under accounting.
- Sponsor create/update/toggle now logs billing activity.
- Payment and reversal logging continues through existing payment funnels, with payer metadata added.

## Verification

Passed:

- `php artisan migrate`
- `php artisan db:seed --class=RoleSeeder`
- `php artisan permissions:audit`
  - missing route permissions: 0
  - unprotected admin mutation routes: 0
- `php artisan logs:audit`
  - sponsor backlog removed
  - remaining findings are unrelated existing backlog
- `php artisan view:cache`
- `npm run build`
- PHP syntax checks for new/changed controllers, services, models, migrations, and provider
- rollback smoke test:
  - synced invoice receivables
  - reallocated amount to sponsor receivable
  - recorded payment against sponsor receivable
  - generated sponsor AR aging summary
  - rolled back all smoke data

## Remaining TODOs

- Add a full corporate client management UI.
- Add sponsor authorization UI and authorization consumption workflows.
- Add claim-payment integration so insurance claim receipts can directly settle insurance receivables.
- Add full feature tests for receivable allocation, payer payments, reversals, and AR aging filters.
- Consider payer-specific credit note/write-off assignment instead of defaulting invoice adjustments to the primary invoice payer.
