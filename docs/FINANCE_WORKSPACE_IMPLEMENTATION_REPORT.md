# Finance Department Workspace — Implementation Report

Dedicated `/finance/*` browser workspace for `DepartmentType::FINANCE`,
following the established workspace architecture (Records / Nursing / Emergency
/ Inpatient / Investigations / Pharmacy / Stores). All pages are thin browser
adapters over the existing billing, payment, claims, cashier, accounting,
journey and reporting services — no invoice, payment, journal, claims or
reconciliation logic was duplicated, and every posting continues through the
existing pipelines.

## 1. What the audit found (and what shaped the scope)

The finance domain already exists across four admin route families, all reused:

- **Billing** (`admin.billing.*`, module `billing`): invoices
  (`Billing\InvoiceController` — index/create/show/cancel/print/pdf, item
  discounts), payments (`Billing\PaymentController` — receive/index/store/
  reverse + receipts), cross-visit allocation
  (`PreviousBalanceController::allocate`, `can:billing.payment.allocate_cross_visit`),
  credit notes (`CreditNoteController`), sponsors, patient statements, AR aging
  (`BillingReportController`), counter sale, financial-risk / payment-policy /
  arrangement / clearance worklists (Payment Timing Policy phases 4–8).
- **Claims** (`admin.claims.*`, modules `insurance`+`claims`):
  `Admin\Billing\ClaimController` — index/create/show/validate/mark-ready/
  submit/review/record-payment/appeal with `ClaimStatus`
  (draft→ready→submitted→…→paid).
- **Cashier & basic accounting** (`admin.accounts.*`, module
  `accounting_basic`): cashier shifts (`CashierShiftController` —
  open/close/verify with `ShiftStatus` open/closed/verified), daily collection,
  reconciliation (`FinancialEntryController`).
- **Advanced accounting** (`admin.accounting.*`, module `accounting_advanced`):
  journals (`JournalEntryController`), trial balance / general ledger
  (`AccountingReportController`), chart of accounts
  (`Accounting\AccountController`), bank reconciliation, periods, fiscal years.

Statuses reused as-is: `InvoiceStatus` (draft/pending/partially_paid/paid/
cancelled/refunded), `PaymentStatus` (active/reversed/reversal), `ShiftStatus`,
`ClaimStatus`. Spec items without backing functionality (standalone refunds
module, write-offs, deposits/advances, digital-payment reconciliation UI,
insurance-specific reconciliation) were **not** given placeholder pages;
reversals/credit notes cover the correction flows that exist today.

## 2. Routes (`routes/web.php`)

`Route::prefix('finance')->name('finance.')->middleware('department.type:finance')`
— inserted after the stores group. Workspace route names mirror the **full**
admin names (`finance.billing.invoices.index`, `finance.claims.index`,
`finance.accounts.handover.index`, `finance.accounting.journals.index`), so the
resolver's generic `admin.` → `finance.` fallback maps every page with no
special-case table (only handoffs are mapped explicitly).

| Area | URL | Names | Permissions |
|---|---|---|---|
| Dashboard | `/finance` (+ `/dashboard` redirect) | `finance.dashboard[.redirect]` | dept guard |
| Invoices | `/finance/invoices[...]` | `finance.billing.invoices.index/create/store/show/cancel` | `invoices.view/create/void` |
| Payments | `/finance/payments[...]` | `finance.billing.payments.index/receive/store/reverse` + `finance.billing.previous-balance.allocate` | `payments.view/create/refund`, `billing.payment.allocate_cross_visit` |
| Credit notes | `/finance/credit-notes[...]` | `finance.billing.credit-notes.index/create/store` | `credit_notes.view/create` |
| Sponsors / statements / aging | `/finance/sponsors`, `/finance/statements[...]`, `/finance/receivables/aging` | `finance.billing.sponsors.index`, `finance.billing.statements.index/show`, `finance.billing.reports.aging` | `sponsors.view`, `invoices.view`, `reports.ar_aging.view` |
| Claims | `/finance/claims[...]` | `finance.claims.index/create/show/review/validate/mark-ready/submit/payments.store` | `claims.view/create/approve` |
| Cashier | `/finance/cashier[...]` | `finance.accounts.handover.index/open/close/verify` | `accounts.cashier` (+ `accounts.entries.approve`) |
| Daily collection / reconciliation | `/finance/daily-collection`, `/finance/reconciliation` | `finance.accounts.daily-collection/reconciliation` | `accounts.entries.view` |
| Accounting | `/finance/journals[...]`, `/finance/trial-balance`, `/finance/general-ledger`, `/finance/chart-of-accounts` | `finance.accounting.journals.index/show`, `finance.accounting.trial-balance/general-ledger/accounts.index` | `accounting.journals.view`, `accounting.reports.*`, `accounting.accounts.view` |
| Patients | `/finance/patients[/{id}]` | `finance.patients.index/show` | `patients.view` |
| Handoffs | `/finance/handoffs[...]` | `finance.handoffs.*` | journey capabilities |
| Reports | `/finance/reports` | `finance.reports.index` (`report=billing`) | `reports.billing` |

Print/PDF/receipt routes and provider callbacks stay on their admin/public URLs
(the redirect middleware never maps them because no `finance.*` mirror exists).
Mutations without workspace mirrors keep posting to admin endpoints (non-GET is
never redirected) and return via `back()` into the workspace.

## 3. URL resolution & redirects

- `WorkspaceRouteResolver`: `isFinance()`, `isDepartmentWorkspace()` inclusion,
  `dashboardRouteName() → finance.dashboard`, `routeName()` branch (handoffs
  map + generic `admin.` → `finance.` fallback guarded by `Route::has()`),
  handoff helpers, `viewContext()` (`workspaceKey: finance`,
  `workspaceScope: financial_operations`) and `financeBreadcrumbs()`.
- `records.redirect` added to the admin `billing`, `claims`, `accounts` and
  `accounting` groups: generic browser GETs bounce into `/finance/*` for
  finance users. JSON / AJAX / signed URLs and all non-GET verbs pass through;
  other workspaces are unaffected (their mapped candidates don't exist).
- `EnsureActiveDepartmentType` returns `__('finance.unauthorized')`.
- Login and department switching land on `/finance` via the resolver.

## 4. Dashboard (finance command board)

New `FinanceDashboardService` + `dashboards/finance.blade.php` in the modern
design language. **Billing and collections are reported separately** (per the
spec's accounting rule):

- **Insight** — unpaid-invoice backlog with oldest-open-age chip.
- **Pressure** — collection load (unpaid + partially paid) with
  unpaid/partial/open-shift/pending-claims metric chips.
- **KPIs** — billed today (gross invoiced, explicitly captioned "not
  collections"), collected today (active payments by `paid_at`), outstanding AR
  (open invoice balances), unpaid invoice count.
- **Charts** — 7-day billed-vs-collected trend; today's collections by payment
  method (donut).
- **Lists** — largest open invoices (→ `finance.billing.invoices.show`) and
  claims requiring attention (draft/ready/rejected → `finance.claims.*`).

Amount KPIs sit behind the workspace guard + the menu-level permissions;
reversed payments are excluded via `PaymentStatus::ACTIVE`.

## 5. Sidebar

`financeSections()` in `SidebarMenuBuilder` — Dashboard; Billing & Payments
(Invoices, Collect Payment, Payments, Counter Sale, Credit Notes); Receivables
(AR Aging, Patient Statements, Sponsors); Insurance & Claims; Cashier (Sessions,
Daily Collection, Reconciliation); Accounting (Journals, General Ledger, Trial
Balance, Chart of Accounts); Patients & Coordination; Reports; General. All
permission/module filtered — a cashier-only user sees no accounting or claims
entries (test-verified).

## 6. Localization

New `lang/en/finance.php` + `lang/fr/finance.php` (`unauthorized`,
`workspace.title`, `menu.*`, `dashboard.*`, `breadcrumbs.*`) with recursive
EN/FR parity verified by test.

## 7. Tests

`tests/Feature/FinanceWorkspaceTest.php` — 8 tests, 51 assertions, all passing:

1. Workspace URLs + `department.type:finance` + permission middleware.
2. Non-finance department gets 403 on all workspace pages.
3. Sidebar is workspace-specific, permission filtered, active-state correct
   (including cashier-only narrowing).
4. Invoice worklist + detail render inside the workspace.
5. Dashboard renders with accurate open-invoice count; `/finance/dashboard`
   redirects.
6. Legacy browser routes (`admin.billing.invoices.index`, `admin.claims.index`,
   `admin.accounting.journals.index`) redirect into the workspace; JSON stays.
7. Login and department switch land on `/finance`.
8. EN/FR recursive locale parity.

Regression runs:

- **Accounting directory** — 169 passed (472 assertions).
- **Billing/claims** (`BillingTest`, `BillingEnhancementsTest`,
  `BillingDiscountPermissionTest`, `InsuranceTypeClaimWorkflowTest`) — 39 passed.
- **Broad workspace suite** (all 8 workspace tests + RoleDashboard +
  DepartmentMenuProfile + DepartmentDashboard + FrenchRouteSmoke) — 100 passed
  with 2 stale-test fixes, both unrelated to finance logic:
  - `DepartmentMenuProfileTest` used a FINANCE user to assert the *generic*
    menu — finance now has a workspace menu, so the test uses THEATRE (a
    profiled type without a workspace) instead.
  - `EmergencyWorkspaceTest` asserted menu items (`emergency.cases.create`,
    `emergency.triage.index`) that were deliberately commented out of the
    curated emergency menu on disk (user's menu curation — the routes
    themselves still exist); assertions updated to match the curated menu.
  - After fixes: 21/21 across the three affected suites.

## 8. Financial integrity & security notes

- The workspace registers **no new financial write paths** — every mutation it
  mounts is the existing audited controller action (invoice cancel, payment
  store/reverse, cross-visit allocate, credit-note store, claim
  validate/submit/record-payment, shift open/close/verify), unchanged.
- Payment posting, journal balancing, receipt generation, reversal rules and
  claim settlement all remain inside the existing services.
- The department-type guard adds to — never replaces — `can:` middleware and
  module gates; broad billing permissions alone do not open the workspace.
- Trial balance / GL / COA keep their dedicated `accounting.*` permissions;
  chart-of-accounts editing stays behind the admin accounting permissions.
- Provider callbacks, public payment links, signed URLs, prints, PDFs and
  exports are untouched and never redirected.

## 9. Remaining limitations

- Refunds (as distinct from reversals), write-offs, patient deposits/advances,
  digital-payment-provider reconciliation UI, accounting-period admin and
  branch/facility scoping have no dedicated backing modules today; when built,
  mount them under `/finance/*` with this same adapter pattern.
- Payment-gate policy pages (`admin.settings.*` payment-gate admin) remain in
  the admin area by design — they are configuration, not daily finance
  operations.
