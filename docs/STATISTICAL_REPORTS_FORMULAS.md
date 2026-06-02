# Statistical Reports — Formulas

All statistics are scoped to a date range (default: **first day of the current month → today**). "Range" below means rows whose anchor date column falls within `[date_from, date_to]`.

## Activity / patients

- **New patients** = `COUNT(patients)` created in range.
- **Returning patients** = distinct `visits.patient_id` in range that also have an earlier visit (correlated `EXISTS`).
- **Visit mix** = counts of `visits.visit_type = OPD`, `emergency_cases` (by `arrival_time`), `admissions` (by `admission_date`).

## Consultation

- **Completion rate** = `completed sessions / total sessions × 100`
  where a session is a `visit_consultation_routes` row and completed = `status = COMPLETED`.

## Pharmacy

- **Most prescribed** = `COUNT(prescription_items)` grouped by `drug_name` (source: prescriptions, **not** dispensing).
- **Most dispensed** = `SUM(dispensing_records.quantity_dispensed)` grouped by drug (source: dispensing records, **not** product table).
- **Drug sales revenue** = `SUM(invoice_items.paid_amount)` where `product_id IS NOT NULL`.

## Investigations

- **Turnaround time** (avg minutes) = `AVG(accepted_at − created_at)` for lab requests/items with both timestamps (computed in PHP for DB portability).

## Procedures / Theatre

- **Completion rate** = `completed / requested × 100` on `procedure_requests` (completed = `status = COMPLETED`; cancelled bucket = `CANCELLED | POSTPONED | REJECTED`).

## Emergency

- **Average triage waiting time (min)** = `AVG(triaged_at − arrival_time)` over emergency cases in range with both timestamps.
- **Triage / disposition / arrival breakdowns** = `COUNT` grouped by `final_triage_category` / `disposition` / `arrival_mode`.

## Admission

- **Average length of stay (days)** = `AVG(actual_discharge_date − admission_date)` over admissions **discharged** in range.
- **Bed occupancy rate** = `occupied beds / total beds × 100` (`beds.status = OCCUPIED`).
- **Ward occupancy %** = per ward, `occupied / total beds × 100`.

## MAR / medication administration

- **Compliance** = `given doses / total administrations × 100` (`status = GIVEN`).
- **Adverse reactions** = administrations with a non-empty `reaction`.

## Billing / financial

- **Collection rate (Payment Collection Rate)** = `total paid / total billed × 100`
  - total billed = `SUM(invoices.total_amount)` created in range
  - total paid = `SUM(payments.amount)` where `is_reversal = false`, by `paid_at`
- **Outstanding** = `SUM(invoices.balance)` where `status IN (PENDING, PARTIALLY_PAID)` (all-time snapshot).
- **Revenue by department** = `SUM(invoice_items.paid_amount)` grouped by `department_id`.
- **Revenue by payment method** = `SUM(payments.amount)` grouped by `payment_method`.

## Claims

- **Approval rate (Claims Approval Rate)** = `approved claims / submitted claims × 100`
  - approved = `status = APPROVED`
  - submitted = `submitted_at IS NOT NULL`
- **Claim amount / paid** = `SUM(total_claim_amount)` / `SUM(paid_amount)`.

## Stock / inventory

- **Fast-moving products** = `SUM(stock_movements.quantity)` where `direction = OUT`, grouped by product.
- **Stock on hand by location** = `SUM(stock_balances.quantity_on_hand)` grouped by location.
- **Low stock** = balances with `0 < quantity_on_hand ≤ 10`; **out of stock** = `quantity_on_hand ≤ 0` (heuristic threshold — replace with per-product reorder level when available).

## Blood bank

- **Units available** = `COUNT(blood_units)` where `status = AVAILABLE` (the source-of-truth for availability).
- **Crossmatch compatibility rate (Blood Crossmatch Compatibility Rate)** = `compatible crossmatches / total crossmatches × 100`
  (compatible = `blood_crossmatches.result = COMPATIBLE`).
- **Deferred donors** = donors with `screening_status IN (TEMPORARILY_DEFERRED, PERMANENTLY_DEFERRED)`.
- **Transfusion reactions** = `blood_issues.reaction_occurred = true`.

## Staff performance (permission-protected)

For each metric, `COUNT(*)` grouped by the **acting user** column:

- Diagnoses per doctor → `diagnoses.doctor_id`
- Dispenses per pharmacist → `dispensing_records.dispensed_by`
- Administrations per nurse → `medication_administrations.administered_by`
- Payments per cashier → `payments.received_by`

> Time-difference averages (turnaround, waiting time, length of stay) are computed in PHP over a bounded fetch (≤10 000 rows) so the same query runs on both sqlite (tests) and MariaDB (production), which lacks a shared `TIMESTAMPDIFF`/`DATEDIFF` signature.
