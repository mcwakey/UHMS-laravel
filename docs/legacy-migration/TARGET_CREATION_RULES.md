# Target creation rules

## Purpose

Renewed UHMS services implement live operational behavior. They are evidence for invariants, but most are unsafe as historical import entry points because they generate present-day state. A later migration persistence boundary should reuse pure validation/calculation where possible without invoking operational workflows.

## Patient creation

- `PatientService::create()` replaces any supplied patient number with a generated value, stamps the authenticated user, and can write avatar files.
- Patient numbers use a locked period sequence and collision check.
- Web registration validates required demographics and creates contacts/insurance after the patient, not in one encompassing transaction.

**Migration implication:** decide identifier policy first; persist patient and children transactionally through migration-specific code; reconcile the number sequence.

## Visit and appointment creation

- `VisitService::create()` regenerates number/creator, defaults date to today, rejects another visit for the patient/date, sets current workflow, and can bill services.
- Its “age at visit” calculation uses current age rather than age on historical visit date.
- Appointment request validation rejects past dates; appointment creation generates numbers, stamps actor, applies current prices, and synchronizes services.
- Appointment check-in creates a current visit, queues/routes, workflow transitions, and billing.

**Migration implication:** historical visits/appointments cannot use these pathways unchanged.

## Consultation creation

- Route creation validates department/doctor/service associations, creates logs/pivots, and normally creates/recalculates invoice items.
- Activation pauses routes, moves queues/visit state, creates/synchronizes a medical record, and writes activity.
- Completion timestamps `now()` and may auto-complete the visit.
- Clinical entry services stamp current users and create contributors/audit rows; completed/locked routes restrict edits.

**Migration implication:** persist approved historical route/record/entry facts directly with validated links and original timestamps; do not simulate transitions.

## Admission/discharge

- Admission occupies beds, changes visit status, creates initial charges, releases emergency resources, writes history, dispatches notifications, and logs activity.
- Discharge stamps current date/user, releases the bed, changes visit state, evaluates readiness/completion, dispatches notifications, and logs activity.

**Migration implication:** load historical admission facts without invoking the service. Reconcile only approved current active occupancy at cutover.

## Billing, payment, claims, and accounting

- Billing resolves current insurance-aware price, prevents duplicate sources, creates invoice/items/usages/rendering work, and recalculates.
- Recalculation can overwrite totals/status, rebalance insurance coverage, create receivables, and post accounting.
- Payment recording validates exact allocations, changes item/invoice/AR state, posts accounting, dispatches events, and logs activity.
- Claims derive number/date/amount/current user and status history from current invoice snapshots.
- Journal posting requires an open period, exact balance, and stamps current poster/time. Some posting paths fall back to the first user if no actor exists.

**Migration implication:** import only approved source-evidenced facts and/or clearly labelled approved opening positions; allocation/refund/credit/GL domains start empty unless directly evidenced. Never invoke normal recording/posting or synthesize a “complete” graph to recreate history.

## Inventory/procurement

- Stock movement validates availability, calculates weighted average cost, changes balance, and posts accounting.
- Balance rebuild treats movement ledger as canonical.
- Dispensing requires billing settlement, decrements stock, writes dispensing/MAR/pathway/activity, and can notify low stock.
- Procurement receipt creates GRN, movements, supplier ledger/payable/accounting, budget changes, and audit.

**Migration implication:** Classic does not evidence a complete movement ledger. Persist only approved source-evidenced stock/procurement facts and/or a clearly labelled opening position, validate quantity/value reconciliation, and avoid clinical/procurement services during historical load.

## Safe-boundary principle

“Direct persistence” means a controlled, transactional migration service that bypasses Eloquent observers/events and explicit operational calls while enforcing target foreign keys, unique keys, enum values, precision, business invariants, timestamps, mappings, and reconciliation. It is not raw unvalidated copying.
