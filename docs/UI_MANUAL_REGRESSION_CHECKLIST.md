# UHMS UI Manual Regression Checklist

Functional sign-off for major workflows after the UI standardization phases.
The UI work changed **presentation only** — these checks confirm no behaviour
regressed. Tick a box only after actually performing the step. Do not claim a
workflow was verified unless it was.

## Patients
- [ ] Create patient
- [ ] Search patient (name / phone / Ghana Card / insurance / emergency contact)
- [ ] Mark deceased (confirmation + reason where required)
- [ ] Merge patient folder (reason required; merged folder blocks new visits)

## Visit
- [ ] Create OPD visit
- [ ] Active admission guard works (cannot double-admit)
- [ ] Create emergency case from visit
- [ ] Emergency session appears in consultation page
- [ ] Visit preview shows pathway timeline

## Consultation
- [ ] Add complaints
- [ ] Add HOPC
- [ ] Add diagnosis
- [ ] Request investigation
- [ ] Prescribe drug
- [ ] Request procedure
- [ ] Summary updates (and status badges render, not raw UPPER_SNAKE)

## Emergency
- [ ] Triage calculation / override (override requires a reason)
- [ ] Assign bay / bed
- [ ] Administer emergency medication
- [ ] Request investigation
- [ ] Request procedure
- [ ] Dispose to admission
- [ ] Emergency bed count ends correctly

## Admission / MAR
- [ ] Create admission
- [ ] Assign bed
- [ ] Administer medication
- [ ] Hold / missed / refused dose (status badge + reason)
- [ ] Discharge

## Pharmacy
- [ ] Bill selected drugs
- [ ] Reduce quantity
- [ ] Dispense
- [ ] Stock deducted once (no double deduction)

## Billing
- [ ] Invoice item appears
- [ ] Payment recorded
- [ ] Reversal requires reason
- [ ] Receipt prints

## Investigations
- [ ] Request
- [ ] Result entry
- [ ] Verification
- [ ] Print result

## Procedures / Theatre
- [ ] Accept procedure
- [ ] Schedule room
- [ ] Pre-op note
- [ ] Anaesthesia note
- [ ] Operative note
- [ ] Recovery note
- [ ] Complete case

## Stock
- [ ] Receive stock
- [ ] Transfer request
- [ ] Approve / dispatch / receive
- [ ] Adjustment requires reason
- [ ] Balance matrix updates

## Blood Bank
- [ ] Donor screening
- [ ] Donation screening
- [ ] Recipient details
- [ ] Compatibility suggestion
- [ ] Crossmatch
- [ ] Issue blood (incompatible/emergency release requires reason)
- [ ] Record transfusion

## Roles / Permissions / Modules
- [ ] Unauthorized actions hidden in UI (`@can` gates)
- [ ] Direct unauthorized access returns friendly **403**
- [ ] Disabled module page works
- [ ] Module cannot be disabled if core

---

### Automated coverage already backing these

Some of the above is also covered by `php artisan test` (not a substitute for the
manual pass, but a safety net):
- High-risk reason enforcement — `HighRiskActionReasonTest`
- Friendly 403 / disabled-module / query-exception shielding — `ErrorHandlingTest`
- Payment → invoice/visit JSON workflow — `WorkflowJsonResponsesTest`
- MAR chart rendering — `MedicationAdministrationWorkflowTest`
- Full-reload leak guard — `InertiaBridgeLeakGuardTest`
- UI components & audit — `UiComponentsTest`, `UiPhase5ComponentsTest`, `UiAuditCommandTest`
