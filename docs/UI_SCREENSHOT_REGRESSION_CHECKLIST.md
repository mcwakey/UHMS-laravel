# UHMS UI Screenshot Regression Checklist

Manual visual-regression checklist for high-traffic pages after UI
standardization (Phases 2–9). **No screenshot-testing library is installed** —
this is a human capture/compare checklist (browser dev-tools or a tool of your
choice). Do not claim a page is verified unless it was actually viewed.

For **each** page capture/verify:
- **Desktop** (≥1280px)
- **Small laptop/tablet** (≈768–1024px) — tables scroll, filters wrap, no overflow
- **Print view** if applicable — black-on-white, hospital header, `d-print-none` hides chrome
- **Empty state** — `<x-empty-state>` renders (icon + title + message)
- **Permission-restricted view** — gated actions hidden for users without the permission

Legend: ☐ desktop · ☐ tablet · ☐ print · ☐ empty · ☐ restricted

## Dashboard
- [ ] Main dashboard (`dashboard/admin|doctor|staff`)
- [ ] Statistics dashboard (`/admin/statistics`)

## Patients
- [ ] Patient list (`patients/index`)
- [ ] Patient folder/detail (`patients/show`)
- [ ] Patient merge (`patients/merge/index|compare|show`)

## Visits
- [ ] Visit list (`visits/index`)
- [ ] Create visit (`visits/create`)
- [ ] Visit preview (`visits/preview`)
- [ ] Patient pathway timeline (`visits/show`)

## Consultation
- [ ] Consultation session (`consultations/show`)
- [ ] Consultation summary (`consultations/history`) — print
- [ ] Complaints / HOPC / diagnosis sections

## Emergency
- [ ] Emergency board (`emergency/board`)
- [ ] Emergency case detail (`emergency/show`)
- [ ] Triage / vitals (`triage/create`, `vitals/record`)
- [ ] Medication / MAR (`medication-administration/emergency-board`)
- [ ] Disposition

## Admission
- [ ] Admission board (`admissions/index`)
- [ ] Admission detail (`admissions/show`)
- [ ] Bed assignment
- [ ] MAR (`medication-administration/admission-show`)

## Pharmacy
- [ ] Prescription billing (`prescriptions/show`)
- [ ] Dispensing page (`pharmacy/dispense`)
- [ ] Drug catalogue (`pharmacy/drugs`)

## Billing
- [ ] Invoice list (`billing/invoices/index`)
- [ ] Invoice detail (`billing/invoices/show`)
- [ ] Payment form (`billing/payments`)
- [ ] Receipt print (`billing/payments/receipt-pdf`) — print

## Investigations
- [ ] Investigation request list (`lab/requests`)
- [ ] Result entry (`lab/process`)
- [ ] Result verification
- [ ] Result print — print

## Procedures / Theatre
- [ ] Procedure requests (`theatre/index`)
- [ ] Theatre board
- [ ] Theatre room calendar (`theatre/calendar`)
- [ ] Theatre case detail (`theatre/show`)

## Stock
- [ ] Products list (`admin/products/index`)
- [ ] Stock balance matrix (`store/stock/balances`, `admin/product-stock/balances`)
- [ ] Stock movements / ledger
- [ ] Requisitions / transfers (`store/stock-requisitions`, `store/transfers`)

## Blood Bank
- [ ] Dashboard (`blood-bank/dashboard`)
- [ ] Donor screening
- [ ] Blood request
- [ ] Compatibility / crossmatch
- [ ] Blood issue / transfusion (`blood-bank/units`)

## Reports / Statistics
- [ ] Diagnosis statistics
- [ ] Pharmacy statistics (`reports/pharmacy-sales`)
- [ ] Billing statistics (`reports/income`, `reports/daily-collection`)
- [ ] Stock statistics (`reports/stock-valuation`, `reports/expired-stock`)
- [ ] Blood bank statistics (`blood-bank/reports`)

## Administration
- [ ] Roles (`admin/roles`)
- [ ] Permissions (`admin/permissions/index`)
- [ ] Modules (`admin/modules/index`)
- [ ] Logs (`settings/activity-log`)
- [ ] Notifications (`notifications/index`)
- [ ] Settings (`settings/*`)

## Error / Disabled Pages
- [ ] 403 (`errors/403`)
- [ ] 404 (`errors/404`)
- [ ] 419 (`errors/419`)
- [ ] 500 (`errors/500`)
- [ ] Disabled module page (`errors/module-disabled`)

> Tip: error pages can be previewed by temporarily routing to them or triggering the
> condition in a non-production environment. Verify each is self-contained
> (no app chrome) and uses the friendly layout.
