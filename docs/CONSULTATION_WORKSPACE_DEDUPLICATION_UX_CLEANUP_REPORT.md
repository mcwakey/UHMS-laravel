# Consultation Workspace De-duplication and UX Cleanup Report

Phase 16.5 — Consultation Workspace UX De-duplication and Billing Card Removal.

## Summary

Specialist consultation workspaces no longer duplicate shared clinical
sections under specialty-specific names. Ten legacy duplicate section keys
across five profiles (obstetrics, emergency, orthopedics, surgery, dental)
were hidden from the doctor workspace and aliased to the canonical shared
sections (`investigations`, `procedures`, `diagnosis`, `prescription`). The
normalized layouts now reuse the shared panes, specialty needs surface as
"Specialty suggestions" (favorites) inside those panes, readiness and the
summary builder honor both canonical data and legacy saved entries, and the
billing/service-mapping card was removed from the doctor consultation page
while remaining fully available to admin, finance, reporting, and backend
services. No database rows or saved entries were deleted.

## Problem Found

1. **Duplicate specialist sections.** Newly created specialty sections
   duplicated shared consultation sections under different names:
   - obstetrics: `ultrasound_findings`, `lab_screening` vs shared `investigations`
   - emergency: `urgent_investigations`, `urgent_procedures`, `medications_given` vs shared `investigations`/`procedures`/`prescription`
   - orthopedics: `imaging`, `procedure_plan` vs shared `investigations`/`procedures`
   - surgery: `procedure_plan` vs shared `procedures`
   - dental: `dental_diagnosis`, `dental_xray`, `dental_procedures` vs shared `diagnosis`/`investigations`/`procedures`

   This bloated the sidebar, repeated clinical concepts, split data across
   parallel channels, and created readiness rules that could only be
   satisfied through the duplicate panels (orthopedics/surgery
   `procedure_plan_recorded` was a blocking rule with no core fallback).

2. **Billing-card UX noise.** The doctor consultation workspace rendered a
   billing/service mapping card (mapped service, billed/not-billed badge,
   billable suggestion counts, preview/apply actions, mapping warnings) —
   finance concerns inside a clinical page.

## Section Audit

Full classification in
[docs/CONSULTATION_WORKSPACE_SECTION_DEDUPLICATION_AUDIT.md](CONSULTATION_WORKSPACE_SECTION_DEDUPLICATION_AUDIT.md).
Decisions by profile:

| Profile | duplicate_of_shared_core / hybrid_review (hidden, aliased) | specialty_structured (kept) |
| --- | --- | --- |
| general_medicine | — | — (all shared_core) |
| physiotherapy | — | presenting_problem, pain_assessment, functional_limitation, physical_assessment, treatment_plan, therapy_session, home_exercise_plan, progress_notes |
| ophthalmology | — | eye_complaint, visual_acuity, refraction, iop, eye_examination, follow_up |
| dental | dental_diagnosis → diagnosis; dental_xray → investigations; dental_procedures → procedures | dental_complaint, tooth_chart, oral_examination, consent, follow_up |
| obstetrics | lab_screening → investigations (hybrid_review); ultrasound_findings → investigations (hybrid_review) | obstetric_history, current_pregnancy, lmp_edd_gestational_age, antenatal_vitals, fetal_assessment, risk_assessment, birth_plan, follow_up |
| gynecology | — | gyne_complaint, menstrual_history, obstetric_history, contraceptive_history, sexual_sti_history, pelvic_examination, breast_examination, follow_up |
| ent | — | ent_complaint, ear_assessment, nose_assessment, throat_assessment, hearing_balance_assessment, neck_assessment, follow_up |
| pediatrics | — | pediatric_complaint, birth_history, feeding_history, growth_assessment, immunization_status, developmental_assessment, pediatric_examination, caregiver_instructions, follow_up |
| emergency | urgent_investigations → investigations; urgent_procedures → procedures; medications_given → prescription | triage_summary, emergency_complaint, primary_survey, vitals_monitoring, trauma_assessment, emergency_interventions, disposition, handover |
| orthopedics | imaging → investigations; procedure_plan → procedures | ortho_complaint, injury_history, pain_mobility_assessment, joint_limb_examination, neurovascular_status, cast_splint_plan, follow_up |
| surgery | procedure_plan → procedures | surgical_complaint, surgical_history, wound_assessment, local_or_abdominal_exam, consent, theatre_referral, post_op_instructions, follow_up |

All hidden duplicates are `deprecated_hidden` in the database
(`is_visible = false`, rows preserved). `lab_screening` and
`ultrasound_findings` were treated as hybrid_review: their result-style data
stays stored and renders in the summary under the canonical Investigations
heading. `procedure_plan` narrative was not renamed to
`orthopedic_plan`/`surgical_plan` because `cast_splint_plan` and
`theatre_referral`/`post_op_instructions` already carry the specialty plan;
its saved entries render under the canonical Procedures heading.
`medications_given` was merged into prescription (it duplicated the
prescription flow and risked pharmacy/billing confusion); administered
resuscitation drugs remain part of `emergency_interventions`.

## Files Added

- `app/Services/Consultation/Specialty/ConsultationSpecialtySectionAliasService.php` — alias/compatibility layer.
- `tests/Feature/Consultations/ConsultationWorkspaceSectionDeduplicationTest.php` — 14 tests / 388 assertions.
- `docs/CONSULTATION_WORKSPACE_SECTION_DEDUPLICATION_AUDIT.md` — section classification audit.
- `docs/CONSULTATION_WORKSPACE_DEDUPLICATION_UX_CLEANUP_REPORT.md` — this report.

## Files Modified

- `database/seeders/ConsultationSpecialtySeeder.php` — normalized visible layouts; new `legacy_sections` seeding that keeps duplicate rows hidden on every reseed.
- `database/seeders/ConsultationSpecialtyFavoriteSeeder.php` — extended investigation favorites (ENT: ear swab, sinus X-ray, CT sinuses; emergency: blood glucose, ECG; orthopedics: CT scan, MRI; dental: OPG).
- `app/Services/Consultation/Specialty/ConsultationSpecialtySectionComponentRegistry.php` — `DUPLICATE_SECTION_ALIASES` consulted before the schema short-circuit in `canonicalSectionKey()`.
- `app/Services/Consultation/Specialty/ConsultationSpecialtyReadinessRuleRegistry.php` — dental rules anchored to canonical sections; orthopedics/surgery `procedure_plan_recorded` became a custom rule with core fallbacks.
- `app/Services/Consultation/Specialty/ConsultationSpecialtyReadinessService.php` — `procedure_plan_recorded` custom check (legacy entry OR treatments OR procedure request OR notes); dental X-ray warning also cleared by core investigations; extraction detection also scans core treatments.
- `app/Services/Consultation/Specialty/ConsultationSpecialtySummarySourceCollector.php` — merges legacy entries into canonical `*_review` buckets via the alias service.
- `app/Services/Consultation/Specialty/ConsultationSpecialtySummaryTemplateRegistry.php` — canonical headings for dental, obstetrics, emergency, orthopedics, surgery; added core diagnosis/investigations/prescription/follow-up sections where missing.
- `app/Services/Consultation/Specialty/ConsultationSpecialtySummaryBuilder.php` — new `entry_plus_prescriptions` merge formatter.
- `app/Services/Consultation/Specialty/ConsultationSpecialtyQuickActionRegistry.php` — dental `dental_diagnosis`/`dental_procedure` → `diagnosis`/`procedures`; surgery `procedure_plan` → `procedures` (canonical anchors).
- `app/Services/Consultation/Specialty/ConsultationSpecialtyBrowserFixtureService.php` — surgery expected quick actions use `procedures`.
- `app/Http/Controllers/Doctor/Consultations/Concerns/HandlesConsultationWorkspace.php` — no longer computes/passes `specialtyBillingContext`.
- `app/Http/Controllers/Admin/ConsultationSpecialtyProfileController.php`, `ConsultationSpecialtySectionController.php` — pass the alias map to admin views.
- `resources/views/consultations/partials/specialty-workspace-band.blade.php` — billing card removed.
- `resources/views/consultations/partials/page-config.blade.php` — `specialtyBillingContext` removed from the JS page config.
- `resources/views/consultations/show.blade.php` — "Specialty suggestions" headings above favorite chips in the shared Investigations and Procedures panes.
- `resources/views/admin/consultation-specialties/partials/section-fields.blade.php`, `show.blade.php` — deprecated-duplicate / maps-to badges.
- `lang/en/consultation_specialties.php`, `lang/fr/consultation_specialties.php` — `favorites.specialty_suggestions`, `summary_builder.sections.procedures`, `admin.deprecated_duplicate`, `admin.maps_to`, `admin.hidden_from_doctor_workspace`.
- `tests-e2e/tests/consultation-specialty-workspaces.spec.ts` — canonical/hidden section assertions; billing-card absence assertions.
- `docs/CONSULTATION_SPECIALIST_WORKSPACE_UAT_CHECKLIST.md`, `docs/CONSULTATION_SPECIALIST_VISUAL_QA_NOTES.md` — updated expectations.
- Tests updated: `ConsultationSpecialtyLayoutTest.php`, `DoctorSpecialtyWorkspaceTest.php`, `ConsultationSpecialtyBillingMappingTest.php`, `ConsultationPersonalisedWorkspaceHardeningTest.php`.

## Layout Changes

Normalized doctor-visible layouts (hidden legacy sections in parentheses):

- **Obstetrics**: patient_summary, obstetric_history, current_pregnancy, lmp_edd_gestational_age, antenatal_vitals, fetal_assessment, risk_assessment, **investigations**, diagnosis, prescription, birth_plan, follow_up, summary, completion_readiness. (hidden: ultrasound_findings, lab_screening)
- **Emergency**: patient_summary, triage_summary, emergency_complaint, primary_survey, vitals_monitoring, trauma_assessment, emergency_interventions, diagnosis, **investigations**, **procedures**, **prescription**, disposition, handover, summary, completion_readiness. (hidden: urgent_investigations, urgent_procedures, medications_given)
- **Orthopedics**: patient_summary, ortho_complaint, injury_history, pain_mobility_assessment, joint_limb_examination, neurovascular_status, **investigations**, diagnosis, **procedures**, cast_splint_plan, prescription, follow_up, summary, completion_readiness. (hidden: imaging, procedure_plan)
- **Surgery**: patient_summary, surgical_complaint, surgical_history, wound_assessment, local_or_abdominal_exam, diagnosis, investigations, **procedures**, consent, theatre_referral, post_op_instructions, follow_up, summary, completion_readiness. (hidden: procedure_plan)
- **Dental**: patient_summary, dental_complaint, tooth_chart, oral_examination, **diagnosis**, **investigations**, **procedures**, consent, prescription, follow_up, summary, completion_readiness. (hidden: dental_diagnosis, dental_xray, dental_procedures)
- **General medicine, physiotherapy, ophthalmology, gynecology, ENT, pediatrics**: unchanged (already normalized).

The sidebar shortens automatically: it renders only visible sections deduped
by tab target, so each shared clinical concept appears once.

## Alias / Compatibility Behavior

`ConsultationSpecialtySectionAliasService` provides:

- `canonicalSectionKey(profileCode, sectionKey)` / `displaySectionKey(...)`
- `isDuplicateSection(profileCode, sectionKey)`
- `legacySectionKeysFor(profileCode, canonicalKey)`
- `withMergedLegacyEntries(profileCode, entriesBySection)` — copies legacy
  saved entries into `{canonical}_review` buckets without touching original
  keys (merge, not overwrite; collisions keep both datasets).

Safety properties:

- No section rows or `consultation_specialty_entries` rows deleted or
  re-keyed; legacy entries stay under their original keys and profile ids.
- Re-running `ConsultationSpecialtySeeder` keeps duplicates hidden
  (previously it forced `is_visible = true` on every run) and never removes
  rows.
- `ConsultationSpecialtySectionComponentRegistry::canonicalSectionKey()` now
  resolves duplicate keys to their canonical shared section even though they
  keep structured schemas (schemas retained so saved entries still validate
  and render if an admin re-shows a legacy section).
- Admin-created order-set items targeting legacy keys keep validating and
  keep writing to the legacy entry; readiness/summary continue honoring
  those entries.

## Billing UI Removal

Doctor consultation page:

- The billing/service mapping card (mapped service, billed/not-billed badge,
  billable suggestion count, "Preview billing" link, "Apply charge" form,
  mapping warnings) is removed from `specialty-workspace-band.blade.php`.
- `HandlesConsultationWorkspace::show()` no longer computes
  `specialtyBillingContext`; the variable is no longer passed to the view or
  the `#consultation-page-config` JSON (no JS consumed it).
- Verified by feature tests (`test_doctor_workspace_does_not_render_billing_card`,
  `test_doctor_workspace_does_not_expose_billing_card_or_context`) and by
  Playwright (`assertNoBillingCardOnDoctorWorkspace` asserts no
  `specialty-billing/apply` form and no `specialty-billing/preview` link on
  every profile smoke).

Kept intact (verified by the existing suites):

- `ConsultationSpecialtyBillingMappingService`, `ConsultationSpecialtyBillingApplicationService`, mapping tables and seeder.
- `admin.consultations.specialty-billing.preview` / `.apply` endpoints (apply still gated by `invoices.create`) for finance workflows.
- Admin service-mapping configuration UI, specialist reporting billing health, CSV export, audit application records.

## Readiness Changes

- **orthopedics / surgery `procedure_plan_recorded` (blocking)**: previously
  satisfiable only by a `procedure_plan` entry (impossible once hidden). Now
  a custom rule satisfied by a legacy `procedure_plan` entry OR core
  treatments OR an existing procedure request OR session notes, anchored to
  the shared Procedures section.
- **dental `dental_diagnosis_recorded` / `procedure_or_plan_recorded`**:
  logic unchanged (legacy entry OR core data) but now anchored to the shared
  Diagnosis/Procedures sections instead of hidden panels.
- **dental `xray_missing_if_extraction_planned` (warning)**: still cleared by
  a legacy `dental_xray` entry, now ALSO cleared by core investigation
  orders; extraction detection scans the legacy `dental_procedures` entry
  AND core treatment descriptions. Warning-only stays warning-only.
- No readiness rules referenced the other six duplicate keys, so no other
  blockers could strand. Hidden duplicate sections can no longer create
  impossible blockers; existing legacy entries still satisfy their checks
  (verified by the hardening matrix which seeds legacy `procedure_plan` /
  `dental_*` entries and asserts completion clears).

## Summary Builder Changes

- The source collector exposes legacy entries under canonical
  `investigations_review` / `procedures_review` / `diagnosis_review` /
  `prescription_review` buckets (originals untouched).
- Templates now use one canonical heading per clinical concept and merge
  legacy + core data (`entry_plus_*` formatters; new
  `entry_plus_prescriptions` for emergency `medications_given` + core
  prescriptions):
  - obstetrics: `Lab screening` heading replaced by `Investigations`
    (legacy lab screening + ultrasound findings + core investigation orders);
    added Treatment/prescription and Follow-up sections.
  - emergency: `Medications given` replaced by `Treatment / prescription`;
    added Diagnosis, Investigations, Procedures canonical sections.
  - orthopedics: `Imaging`/`Procedure plan` replaced by
    `Investigations`/`Procedures`; added Diagnosis and Follow-up.
  - surgery: `Procedure plan` replaced by `Procedures`; added Diagnosis and
    Investigations.
  - dental: `Dental diagnosis` / `X-ray / investigation` / `Procedure plan /
    performed procedure` renamed to canonical `Diagnosis` / `Investigations`
    / `Procedures` (same merge behavior).
- No duplicate headings (e.g. both `Lab Screening` and `Investigations`) can
  appear, and no previously saved specialist data is lost — covered by
  dedicated tests.

## Favorites and Order Set Changes

- Favorites carry no section keys; they already flow into the shared panes.
  The shared Investigations and Procedures panes now label these chips
  "Specialty suggestions" (localized en/fr).
- Seeded investigation favorites extended so common specialty orders are
  suggestions rather than sections: ENT (ear swab, sinus X-ray, CT
  sinuses), emergency (blood glucose, ECG), orthopedics (CT scan, MRI),
  dental (OPG). Obstetrics already seeded the full antenatal screening set.
- Verified that no seeded order-set item targets a duplicate section
  (`test_seeded_order_set_items_do_not_target_duplicate_sections`); order
  sets keep pushing diagnoses, investigations, procedures, drugs, tasks, and
  follow-up instructions into shared flows.

## Admin UX Changes

- The per-profile Sections admin page and the profile overview show a
  "Deprecated duplicate" badge and a "Maps to <Section>" badge on hidden
  duplicate sections, with a tooltip explaining that saved entries remain
  available in summaries/history.
- Admin retains full ability to inspect, edit, re-show, or delete legacy
  sections; nothing was removed from the admin UI.

## UAT/Browser Changes

- `docs/CONSULTATION_SPECIALIST_WORKSPACE_UAT_CHECKLIST.md`: per-profile
  "Billing card/context does not crash" replaced with "No billing/service
  mapping card appears on the doctor workspace"; the Billing Awareness
  checklist became a Billing/Service Mapping checklist (admin/finance/report
  surfaces); obstetrics/emergency/orthopedics/surgery/dental section lists
  updated to canonical layouts with explicit "no separate <duplicate> panel
  appears unless configured by admin" lines.
- `docs/CONSULTATION_SPECIALIST_VISUAL_QA_NOTES.md`: sidebar de-duplication
  and "Specialty suggestions" expectations added; billing note replaced with
  the admin/finance-only statement.
- Browser fixture metadata: surgery quick action expectation
  `procedure_plan` → `procedures`; fixture-metadata test asserts no
  duplicate section/action is expected for any profile.
- Playwright specialty spec: per-profile assertions that canonical sections
  are visible (`#tab-investigations` etc.), hidden duplicates render no tab,
  and the doctor page contains no billing apply form / preview link
  (`assertNoBillingCardOnDoctorWorkspace`, replacing
  `assertBillingContextDoesNotCrash`).

## Test Results

Focused commands (all on 2026-07-07):

| Command | Result |
| --- | --- |
| `php artisan migrate` | Nothing to migrate (no schema change needed) |
| `php artisan db:seed --class=ConsultationSpecialtySeeder` | OK — 11 duplicate rows present and hidden; canonical sections visible |
| `php artisan test tests/Feature/Consultations/ConsultationWorkspaceSectionDeduplicationTest.php` | PASS — 14 tests, 388 assertions |
| `php artisan test tests/Feature/Consultations` | PASS — 187 tests, 3080+ assertions (one pre-flight run caught a 2000-line blade guard, fixed, suite green) |
| `php artisan test tests/Feature/ConsultationWorkspaceStabilisationTest.php` | PASS — 4 tests |
| `php artisan test tests/Feature/System/RouteLoadMemoryTest.php` | PASS — 2 tests |
| `php artisan route:list` | OK |
| `php artisan view:cache` / `view:clear` | OK |
| `npm run build` | OK (vite + PWA + legacy assets) |

Playwright (server on `http://localhost:8000`, `--workers=1`):

| Command | Result |
| --- | --- |
| `npx playwright test tests/consultation-workspace.spec.ts` | PASS (1 test; first run flaked on a Bootstrap modal-close wait against freshly recompiled views, clean re-run passed) |
| `npx playwright test tests/consultation-specialty-workspaces.spec.ts` | PASS — 22 tests in 3.7m (per-profile smokes incl. canonical-section visible / duplicate-tab absent / no-billing-card assertions, responsive smokes, specialist report page smoke) |

## Full Suite Result

```
php artisan test
Tests:    1439 passed (7643 assertions)
Duration: 810.28s
```

No failures, no skips introduced by this phase.

## Backward Compatibility

- All 11 workspace profiles resolve and render; the hardening matrix seeds
  legacy entries (`procedure_plan`, `dental_diagnosis`, `dental_procedures`)
  and confirms readiness clears and summaries include the legacy values.
- No `consultation_specialty_sections` or `consultation_specialty_entries`
  rows deleted; hidden rows survive reseeding.
- Billing mapping service/application tests, admin service-mapping
  configuration tests, and specialist reporting tests (billing applications
  count, revenue, CSV export) all pass unchanged.
- Admin can still inspect, re-show, or edit legacy sections; order-set items
  targeting legacy sections keep validating and applying.

## Known Issues / Follow-up

- The first Playwright workspace run can flake on the investigation-modal
  close wait when Blade views recompile mid-run; consider a warm-up request
  or a small retry allowance in CI.
- `assertNoBillingCardOnDoctorWorkspace` uses locale-independent selectors
  (route fragments), but the two `assertDontSee` checks in the feature tests
  assume the default locale; revisit if UAT runs French-first.
- Orthopedics and gynecology are still absent from the Playwright
  `SPECIALTY_PROFILES` list (pre-existing gap), so orthopedics' hidden
  `imaging` section has feature-test coverage only.
- Legacy structured schemas are retained for hidden sections; a future phase
  could add read-only rendering of legacy entries in the workspace history
  panel (today they surface through the summary builder only).
- Future advanced UI work (odontogram, partograph, growth charts) remains as
  listed in the visual QA notes.
