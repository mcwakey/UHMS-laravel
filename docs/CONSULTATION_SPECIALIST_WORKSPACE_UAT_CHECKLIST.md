# Consultation Specialist Workspace UAT Checklist

## Test Preparation

1. Use a local or testing environment only.
2. Run `php artisan migrate`.
3. Run `php artisan db:seed --class=ConsultationSpecialtySeeder`.
4. Run `php artisan consultation:specialty-e2e-fixture --json`.
5. Keep the JSON output open. It contains each workspace URL and login credential.

## Login Credentials / Fixture Command

Command:

```bash
php artisan consultation:specialty-e2e-fixture --json
```

Each profile has its own doctor:

- Email: `specialist.<profile_code>.e2e@uhms.test`
- Password: `password`

Admin/reporting tester:

- Email: `specialist.admin.e2e@uhms.test`
- Password: `password`

## How To Open Each Specialty Workspace

1. Log in with the doctor email for the profile.
2. Open the profile `workspace_url` from the fixture JSON.
3. Confirm the workspace header shows the expected specialty name.
4. Complete the checklist for that profile.

## Complaint Sections Use One Shared Workflow

Complaint-like sections (Eye Complaint, Dental Complaint, Gyne Complaint, ENT
Complaint, Pediatric Complaint, Emergency Complaint, Ortho Complaint,
Surgical Complaint, Presenting Problem, Current Complaint) all use the same
core complaints workflow in every workspace — same fields, same duration/
severity behavior, same HOPC linking, same completion status. Only the
displayed label changes to match the specialty. There is no separate
complaint panel per specialty; saving under any of these labels writes a
normal complaint record.

## General Medicine Checklist

- Workspace opens without an error page.
- General Medicine workspace name appears.
- Complaints, diagnosis, investigations, prescription, procedures, tasks, summary, and patterns are reachable.
- Quick actions navigate to the correct area.
- A clinical field can be saved through the normal general consultation workflow.
- The value reloads after refresh.
- Readiness card remains visible.
- Summary area renders.
- No console or page error is seen.

## Physiotherapy Checklist

- Physiotherapy workspace opens.
- Presenting Problem, Pain Assessment, Functional Limitation, Physical Assessment, Treatment Plan, Therapy Session, Home Exercise Plan, Progress Notes, Summary, and Patterns appear.
- Presenting Problem saves/reloads using the normal complaints form (duration/severity, HOPC linking).
- Pain Assessment quick action opens the section.
- A Pain Assessment field saves and reloads.
- At least one readiness blocker or warning is visible on incomplete data.
- Summary preview opens.
- Order set preview opens if order sets are present.
- No billing/service mapping card appears on the doctor workspace.
- No console or page error is seen.

## Ophthalmology Checklist

- Ophthalmology workspace opens.
- Eye Complaint, Visual Acuity, Refraction, Intraocular Pressure, Eye Examination, Diagnosis, Investigations, Procedures, Prescription, Follow-up, Summary, and Patterns appear.
- Eye Complaint saves/reloads using the normal complaints form.
- Visual Acuity quick action opens the section.
- A Visual Acuity value saves and reloads.
- Readiness status is visible.
- Summary preview opens.
- Order set preview opens.
- No billing/service mapping card appears on the doctor workspace.
- No console or page error is seen.

## Dental Checklist

- Dental workspace opens.
- Dental Complaint, Tooth Chart, Oral Examination, Diagnosis, Investigations, Procedures, Consent, Prescription, Follow-up, Summary, and Patterns appear.
- Dental Complaint saves/reloads using the normal complaints form.
- No separate Dental Diagnosis, Dental X-ray, Dental Procedures, or duplicate Dental Complaint panel appears unless configured by admin.
- Diagnosis and Procedures quick actions open the shared sections.
- Tooth Chart quick action opens the section.
- A Tooth Chart value saves and reloads.
- Readiness status is visible.
- Summary preview opens.
- Order set preview opens.
- No billing/service mapping card appears on the doctor workspace.
- No console or page error is seen.

## Obstetrics Checklist

- Obstetrics workspace opens.
- Current Complaint, Obstetric History, Current Pregnancy, LMP/EDD/Gestational Age, Antenatal Vitals, Fetal Assessment, Risk Assessment, Investigations, Diagnosis, Prescription, Birth Plan, Follow-up, Summary, and Patterns appear.
- Current Complaint saves/reloads using the normal complaints form.
- Antenatal Vitals saves/reloads.
- Investigations section is available for lab/ultrasound requests.
- No separate Lab Screening or Ultrasound Findings panel appears unless configured by admin.
- Fetal Assessment or Obstetric History quick action opens the section.
- An Antenatal Vitals value saves and reloads.
- Readiness status is visible.
- Summary preview opens.
- Order set preview opens if present.
- No billing/service mapping card appears on the doctor workspace.
- No console or page error is seen.

## Gynecology Checklist

- Gynecology workspace opens.
- Gyne Complaint, Menstrual History, Contraceptive History, Sexual/STI History, Pelvic Examination, Breast Examination, Follow-up, Summary, and Patterns appear.
- Gyne Complaint saves/reloads using the normal complaints form.
- No separate duplicate Gyne Complaint panel appears unless configured by admin.
- Gyne Complaint or Pelvic Examination quick action opens the section.
- A Menstrual History value saves and reloads.
- Readiness status is visible.
- Summary preview opens.
- Order set preview opens if present.
- No billing/service mapping card appears on the doctor workspace.
- No console or page error is seen.

## ENT Checklist

- ENT workspace opens.
- ENT Complaint, Ear Assessment, Nose Assessment, Throat Assessment, Hearing/Balance Assessment, Neck Assessment, Follow-up, Summary, and Patterns appear.
- ENT Complaint saves/reloads using the normal complaints form.
- No separate duplicate ENT Complaint panel appears unless configured by admin.
- Ear Assessment quick action opens the section.
- An Ear Assessment value saves and reloads.
- Readiness status is visible.
- Summary preview opens.
- Order set preview opens if present.
- No billing/service mapping card appears on the doctor workspace.
- No console or page error is seen.

## Pediatrics Checklist

- Pediatrics workspace opens.
- Pediatric Complaint, Birth History, Feeding History, Growth Assessment, Immunization Status, Developmental Assessment, Pediatric Examination, Caregiver Instructions, Follow-up, Summary, and Patterns appear.
- Pediatric Complaint saves/reloads using the normal complaints form.
- No separate duplicate Pediatric Complaint panel appears unless configured by admin.
- Growth Assessment quick action opens the section.
- A Growth Assessment value saves and reloads.
- Readiness status is visible.
- Summary preview opens.
- Order set preview opens if present.
- No billing/service mapping card appears on the doctor workspace.
- No console or page error is seen.

## Emergency Checklist

- Emergency workspace opens.
- Triage Summary, Emergency Complaint, Primary Survey, Vitals Monitoring, Trauma Assessment, Emergency Interventions, Diagnosis, Investigations, Procedures, Prescription, Disposition, Handover, Summary, and Patterns appear.
- Emergency Complaint saves/reloads using the normal complaints form.
- No separate Urgent Investigations, Urgent Procedures, Medications Given, or duplicate Emergency Complaint panel appears unless configured by admin.
- Primary Survey quick action opens the section.
- A Primary Survey value saves and reloads.
- Readiness status is visible.
- Summary preview opens.
- No billing/service mapping card appears on the doctor workspace.
- No console or page error is seen.

## Orthopedics Checklist

- Orthopedics workspace opens.
- Ortho Complaint, Injury History, Pain/Mobility Assessment, Joint/Limb Examination, Neurovascular Status, Investigations, Diagnosis, Procedures, Cast/Splint Plan, Prescription, Follow-up, Summary, and Patterns appear.
- Ortho Complaint saves/reloads using the normal complaints form.
- No separate Imaging, Procedure Plan, or duplicate Ortho Complaint panel appears unless configured by admin.
- Neurovascular Status quick action opens the section.
- A Neurovascular Status value saves and reloads.
- Readiness status is visible.
- Summary preview opens.
- Order set preview opens if present.
- No billing/service mapping card appears on the doctor workspace.
- No console or page error is seen.

## Surgery Checklist

- Surgery workspace opens.
- Surgical Complaint, Surgical History, Wound Assessment, Local/Abdominal Exam, Diagnosis, Investigations, Procedures, Consent, Theatre Referral, Post-op Instructions, Follow-up, Summary, and Patterns appear.
- Surgical Complaint saves/reloads using the normal complaints form.
- No separate Procedure Plan or duplicate Surgical Complaint panel appears unless configured by admin.
- Procedures or Surgical Complaint quick action opens the section.
- A Consent value saves and reloads.
- Readiness status is visible.
- Summary preview opens.
- Order set preview opens if present.
- No billing/service mapping card appears on the doctor workspace.
- No console or page error is seen.

## Billing / Service Mapping Checklist

Billing/service mapping is managed by admin/finance and visible in reports.
The doctor consultation workspace should not show a billing mapping card.

- The doctor consultation workspace shows no billing mapping card, preview link, or apply button for any profile.
- Admin service mapping configuration (`/admin/consultation-specialties/service-mappings`) still lists, creates, and updates mappings.
- The specialist reporting dashboard still shows billing mapping health/applications.
- Finance/billing flows can still apply mapped services; no charge is created from the doctor consultation page.

## Readiness Checklist

- Readiness card is visible on the right panel.
- Blocking items are shown for incomplete specialist workflows.
- Completed items appear after required data is saved.
- Warning items do not hide the main completion status.
- Readiness links scroll to the relevant section when present.

## Summary Builder Checklist

- Summary quick action opens the summary area.
- Generate Summary opens a preview modal.
- Preview contains recorded specialist information when data exists.
- Copy and insert actions do not crash.
- Closing the modal returns the tester to the workspace.

## Order Set Checklist

- Order set cards appear for seeded profiles.
- Preview opens in a modal.
- Applicable items are listed.
- Manual-action items are disabled or clearly marked.
- Closing the modal returns to the workspace.
- Do not apply order sets during basic UAT unless specifically testing order-set application.

## Reporting Dashboard Checklist

- Log in as `specialist.admin.e2e@uhms.test`.
- Open `/admin/reports/consultation-specialties`.
- Summary cards render.
- Specialty filter works.
- Date filter works.
- Tables remain visible after filtering.
- Export CSV link is visible.
- Management reports dashboard shows the specialist widget.

## Responsive / Mobile Checklist

Check desktop, tablet, and mobile widths:

- Header does not overlap patient or action badges.
- Sidebar/tabs remain usable.
- Structured forms can be opened.
- Save button remains reachable.
- Readiness card remains reachable.
- Summary and order-set modals can be closed.
- No critical action is hidden behind horizontal overflow.

## Bug Reporting Template

- Tester:
- Date:
- Environment:
- Profile:
- Workspace URL:
- Browser:
- Viewport:
- Steps to reproduce:
- Expected result:
- Actual result:
- Screenshot/video:
- Console error:
- Network/server error:
- Severity:
- Passed before:

## Pass / Fail Sign-off Table

| Area | Tester | Pass/Fail | Notes | Date |
| --- | --- | --- | --- | --- |
| General Medicine | | | | |
| Physiotherapy | | | | |
| Ophthalmology | | | | |
| Dental | | | | |
| Obstetrics | | | | |
| Gynecology | | | | |
| ENT | | | | |
| Pediatrics | | | | |
| Emergency | | | | |
| Orthopedics | | | | |
| Surgery | | | | |
| Billing/service mapping (admin/reports) | | | | |
| Readiness | | | | |
| Summary builder | | | | |
| Order sets | | | | |
| Reporting dashboard | | | | |
| Responsive/mobile | | | | |
