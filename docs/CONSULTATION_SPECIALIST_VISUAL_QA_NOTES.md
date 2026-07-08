# Consultation Specialist Visual QA Notes

## Workspace Header

- The patient banner remains the first clinical context anchor.
- The specialist workspace strip should sit below the vitals/session panels without overlapping them.
- The specialty name, department, doctor, readiness badges, and quick actions should wrap cleanly on small screens.
- Compact mode should not hide the active specialty identity.

## Section / Sidebar Behavior

- Sidebar items should use different representative icons where configured.
- Shared clinical sections (Diagnosis, Investigations, Procedures, Prescription) should appear once per workspace; no duplicate specialty-named panels (Lab Screening, Ultrasound Findings, Urgent Investigations/Procedures, Medications Given, Imaging, Procedure Plan, Dental Diagnosis/X-ray/Procedures) should appear unless an admin re-enables them.
- The patient's main complaint always uses the core Complaints pane (same fields, save/list behavior, HOPC linking, and completion status in every workspace), only the sidebar/tab label changes per specialty (Eye Complaint, Dental Complaint, Gyne Complaint, ENT Complaint, Pediatric Complaint, Emergency Complaint, Ortho Complaint, Surgical Complaint, Presenting Problem, Current Complaint). No separate specialty complaint panel (e.g. a schema-driven "Eye Complaint" form distinct from Complaints) should appear unless an admin re-enables the legacy section.
- Specialty suggestions (favorites) should appear inside the shared Investigations/Procedures/Prescription panes, not as separate panels.
- Section counts should remain visible and aligned with labels.
- The active section should be visibly highlighted.
- Quick actions should move the tester to the target section without page reload.
- Empty states should show one muted message, not a visible empty form.

## Card Spacing

- Specialist cards should match the initial consultation card language: white card, restrained border, compact header, and small badges.
- Forms should appear above saved records after Add is clicked.
- Saved records should appear below the form with edit/delete buttons on the record row.
- Cards should not nest inside unrelated decorative cards.

## Forms

- Add buttons should open the new-entry form.
- Forms should collapse or return to a clean saved-record view after successful save.
- Labels should stay close to fields.
- Long text areas should not push action buttons off-screen on mobile.

## Right Panel / Readiness

- Readiness card should stay reachable on desktop and mobile.
- Blocking, warning, completed, and optional groups should be visually distinct.
- Readiness links should scroll to the correct section where anchors exist.

## Modals

- Summary preview and order-set preview modals should fit inside the viewport.
- Close buttons should remain visible on mobile.
- Modal body should scroll if the content is long.

## Mobile Behavior

- Header, tabs, form controls, and right-panel cards may stack vertically.
- Horizontal scroll should not be required for core actions.
- Buttons may wrap, but text should remain readable.
- The workflow sidebar may become a vertical block; it should still be usable.

## Known Acceptable Limitations

- Advanced clinical visualizations such as odontograms, growth charts, partographs, and orthopedic diagrams are still represented as structured forms.
- Billing/service mapping is managed by admin/finance and visible in reports; the doctor consultation workspace intentionally shows no billing mapping card or billing actions.
- Some long specialist sections can be dense on mobile and may need future visual redesign.

## Future Redesign Candidates

- Dental tooth chart as a proper odontogram.
- Pediatric growth assessment with charting.
- Obstetric partograph and fetal trend visualization.
- Orthopedic neurovascular assessment with limb diagram support.
- Emergency primary survey with high-density resuscitation board mode.
