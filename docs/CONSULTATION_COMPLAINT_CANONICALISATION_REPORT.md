# Consultation Complaint Canonicalisation Report

Phase 16.6 — Canonical Complaints Pane With Specialty-Specific Labels.

## Summary

Every complaint-like specialist section (`presenting_problem`, `eye_complaint`,
`dental_complaint`, `gyne_complaint`, `ent_complaint`, `pediatric_complaint`,
`emergency_complaint`, `ortho_complaint`, `surgical_complaint`) now
canonicalises to the same core `complaints` pane used by general medicine.
Each workspace still shows a specialty-appropriate label (e.g. "Eye
Complaint", "Presenting Problem"), but the underlying save/list/completion
behavior, duration/severity fields, and HOPC linking are the single core
complaints implementation — there is no longer a parallel schema-driven
complaint form. Obstetrics, which never had a complaint section, gained one
("Current Complaint"). Legacy schema-driven complaint entries saved before
this phase are preserved (hidden, not deleted) and continue to satisfy
readiness and appear in the summary builder as a merged fallback.

## Problem Found

`eye_complaint` and `dental_complaint` were nominally aliased to `complaints`
in the component registry, but because both keys also carried a structured
schema, component resolution checked `hasSchema()` before consulting the
alias — so they silently rendered as their own schema-driven forms
(`complaint_text`, `duration`, etc.) instead of the real Complaint model.
The other six complaint-like keys (`gyne_complaint`, `ent_complaint`,
`pediatric_complaint`, `emergency_complaint`, `ortho_complaint`,
`surgical_complaint`, and physiotherapy's `presenting_problem`) had no alias
at all and were fully independent structured entries. None of these
channels fed HOPC, the general summary fallback, or duration/severity
behavior — two parallel "what's wrong with the patient" data stores existed
per specialty workspace.

## Complaint Section Audit

Full mapping and per-profile analysis in
[docs/CONSULTATION_COMPLAINT_SECTION_CANONICALISATION_AUDIT.md](CONSULTATION_COMPLAINT_SECTION_CANONICALISATION_AUDIT.md).

| Profile | Legacy key | Canonical | Display label |
| --- | --- | --- | --- |
| general_medicine | *(already canonical)* | `complaints` | Complaints |
| physiotherapy | `presenting_problem` | `complaints` | Presenting Problem |
| ophthalmology | `eye_complaint` | `complaints` | Eye Complaint |
| dental | `dental_complaint` | `complaints` | Dental Complaint |
| obstetrics | *(new)* | `complaints` | Current Complaint |
| gynecology | `gyne_complaint` | `complaints` | Gyne Complaint |
| ent | `ent_complaint` | `complaints` | ENT Complaint |
| pediatrics | `pediatric_complaint` | `complaints` | Pediatric Complaint |
| emergency | `emergency_complaint` | `complaints` | Emergency Complaint |
| orthopedics | `ortho_complaint` | `complaints` | Ortho Complaint |
| surgery | `surgical_complaint` | `complaints` | Surgical Complaint |

Physiotherapy's `presenting_problem` was analysed in detail: its
`problem_description` field is the chief complaint; the remaining fields
(`onset_date`, `onset_type`, `mechanism_of_injury`, `affected_area`,
`referral_reason`) are referral context already complemented by
`physical_assessment` and `treatment_plan`. Per the decision rule ("if
uncertain, prefer canonical complaints and keep the specialty data as
legacy/summary data"), the section was canonicalised rather than split; no
new section was created for the referral fields.

## Files Added

- `docs/CONSULTATION_COMPLAINT_SECTION_CANONICALISATION_AUDIT.md`
- `docs/CONSULTATION_COMPLAINT_CANONICALISATION_REPORT.md` (this report)
- `tests/Feature/Consultations/ConsultationComplaintCanonicalisationTest.php` — 12 tests / 188 assertions

## Files Modified

- `app/Services/Consultation/Specialty/ConsultationSpecialtySectionAliasService.php` — added complaint aliases for 9 profiles and a new `displayLabelFor(profileCode, canonicalKey)` method (backed by existing `sections.*` lang keys).
- `app/Services/Consultation/Specialty/ConsultationSpecialtySectionComponentRegistry.php` — moved the 9 complaint keys into `DUPLICATE_SECTION_ALIASES` (checked before the schema short-circuit) so `canonicalSectionKey()` resolves them correctly; removed the now-redundant entries from the old `ALIASES` const.
- `app/Services/Consultation/Specialty/ConsultationSpecialtyLayoutService.php` — `normalizeSection()` now accepts the profile code and overrides a section's label/translated_label via the alias service's `displayLabelFor()`; threaded through `buildLayout()` and `generalLayout()`.
- `database/seeders/ConsultationSpecialtySeeder.php` — 9 profiles now seed `complaints` in their visible section list (with a profile-specific DB label) instead of the legacy key; the legacy key moves to `legacy_sections` (hidden, preserved); obstetrics gained a `complaints` section it never had.
- `app/Services/Consultation/Specialty/ConsultationSpecialtyReadinessRuleRegistry.php` — new `complaint()` rule factory; 8 profiles' complaint-readiness rules switched from `entryAny`/`core` to `complaint()`, anchored to the canonical `complaints` section.
- `app/Services/Consultation/Specialty/ConsultationSpecialtyReadinessService.php` — `core_complaint` rule source now also accepts a legacy specialty-entry fallback; fixed a latent bug in `anchors()` where the tab anchor was always computed as a bare `#` (the model has no `tab_target` attribute) — anchors are now resolved via the section component registry for every rule, not just complaint ones.
- `app/Services/Consultation/Specialty/ConsultationSpecialtySummaryTemplateRegistry.php` — 8 profiles' complaint summary sections now source from `core.complaints` merged with the legacy entry (`complaints_review` bucket) under the specialty label; added a `current_complaint` section for obstetrics.
- `app/Services/Consultation/Specialty/ConsultationSpecialtySummaryBuilder.php` — new `complaints_plus_entry` formatter (core complaint text + legacy entry key/value, joined).
- `app/Services/Consultation/Specialty/ConsultationSpecialtyQuickActionRegistry.php` — 6 profiles' complaint quick actions (physiotherapy, gynecology, ent, pediatrics, orthopedics, surgery) retarget `#complaints-section` directly instead of a hidden legacy tab; action keys unchanged.
- `app/Http/Controllers/Admin/ConsultationSpecialtyProfileController.php`, `ConsultationSpecialtySectionController.php` — pass `complaintDisplayLabel` (from the alias service) to the admin views.
- `resources/views/admin/consultation-specialties/partials/section-fields.blade.php`, `show.blade.php` — show a "Displayed as X" badge on the canonical `complaints` row when a profile overrides its label.
- `lang/en/consultation_specialties.php`, `lang/fr/consultation_specialties.php` — added `sections.current_complaint`, `admin.displayed_as`; aligned `gyne_complaint`/`ortho_complaint` section labels to "Gyne Complaint"/"Ortho Complaint" (previously "Gynecology Complaint"/"Orthopedic Complaint") to match the specialty-label wording used elsewhere.
- `app/Services/Consultation/Specialty/ConsultationSpecialtyBrowserFixtureService.php` — `expected_sections` for the 8 affected profiles now list `complaints`; obstetrics/emergency fixtures updated to exercise the new/relabelled section.
- `tests-e2e/tests/consultation-specialty-workspaces.spec.ts` — `HIDDEN_DUPLICATE_SECTIONS` extended with the 9 legacy complaint keys so the smoke test asserts they render no tab; `EXPECTED_CANONICAL_SECTIONS.obstetrics` includes `complaints`.
- `docs/CONSULTATION_SPECIALIST_WORKSPACE_UAT_CHECKLIST.md`, `docs/CONSULTATION_SPECIALIST_VISUAL_QA_NOTES.md` — added the "same core complaints workflow, specialty-specific label" rule and per-profile checklist lines.
- Tests updated: `ConsultationSpecialtyLayoutTest.php` (seeded layout order expectations), `ConsultationSpecialtyReadinessTest.php` (completion-route test now expects `pain_assessment` as the first physiotherapy blocker once a core complaint exists), `ConsultationPersonalisedWorkspaceHardeningTest.php` (`expected_sections` for 8 profiles + obstetrics), `ConsultationWorkspaceSectionDeduplicationTest.php` (the "no aliases" sentinel profile changed from gynecology, which now has a complaint alias, to general_medicine).

## Layout Changes

Every affected profile's visible layout now includes `complaints` in the
same position the legacy key occupied (obstetrics: inserted after
`patient_summary`; emergency: inserted after `triage_summary`). The legacy
key is hidden (`is_visible = false`) but its row and any saved entries are
preserved. Re-seeding does not resurrect the legacy key — verified against
the live database (9 legacy rows present, all hidden) and covered by
`test_normalized_layouts_use_canonical_complaints_and_hide_legacy_sections`.

## Core Complaints Behavior

Confirmed identical across every workspace:

- **Save/list**: `test_core_complaint_saves_and_reloads_under_specialty_label` posts to the real `admin.consultations.complaints.store` endpoint for ophthalmology and reloads the page — the saved description appears under the "Eye Complaint" label inside `#complaints-section`; the legacy `#specialty-eye_complaint-section` pane no longer renders.
- **HOPC linking**: `test_hopc_can_link_to_specialty_labeled_core_complaint` creates a complaint for a dental-labeled workspace, links a `HistoryOfPresentingComplaint` to it via `complaint_id`, and confirms the relation resolves.
- **Completion/status**: readiness (`complaint()` rule) and the doctor workspace both key off the same `complaints` relation as general medicine.
- **Route/Ajax context**: unchanged — the complaints pane is the same hardcoded block in `consultations/show.blade.php` that general medicine already used; no new controller, no new Blade fork.

## Alias / Compatibility Behavior

`ConsultationSpecialtySectionAliasService::PROFILE_ALIASES` now maps each
profile's legacy complaint key to `complaints`; `displayLabelFor()` returns
the profile's specialty label. No `consultation_specialty_sections` or
`consultation_specialty_entries` rows were deleted or re-keyed. Legacy
saved entries remain queryable under their original key and continue to:

- satisfy readiness through the new `core_complaint` legacy fallback (verified for gynecology's `gyne_complaint`),
- render in the summary builder merged with any core complaint data under the specialty label (verified for ENT),
- appear in the admin section list with "Deprecated duplicate" / "Maps to Complaints" badges, plus a new "Displayed as ENT Complaint" hint on the canonical row.

## Readiness Changes

`complaint()` rule (`ConsultationSpecialtyReadinessRuleRegistry`) replaces
the old `core`/`entryAny` complaint rules for 8 profiles. It is satisfied
by a real `Complaint` record (or the visit's `chief_complaint`) **or** a
non-empty legacy specialty entry under the profile's old complaint key.
The rule's `section_key` is always `complaints`, so readiness anchors now
correctly point at `#complaints-section` for every profile — this also
fixed a pre-existing bug where `ConsultationSpecialtyReadinessService::anchors()`
always produced a bare `#` (the resolved-context section models have no
`tab_target` attribute); anchors are now computed via the section component
registry for all rules, not just complaint ones.

## Summary Builder Changes

Complaint summary sections for 8 profiles now source from `core.complaints`
merged with the legacy entry (`complaints_review` bucket, populated by the
existing alias-merge mechanism) via the new `complaints_plus_entry`
formatter, under the specialty label. No duplicate "Complaints" +
"Eye Complaint" headings can appear — verified for ENT
(`test_summary_builder_merges_core_and_legacy_complaint_data_under_specialty_label`),
which seeds both a legacy `ent_complaint` entry and a real complaint and
asserts exactly one `ent_complaint` heading containing both values.

## Quick Action Changes

Physiotherapy, gynecology, ENT, pediatrics, orthopedics, and surgery's
complaint quick actions now target `#complaints-section` directly (matching
the pattern ophthalmology and dental already used), with no
`requires_section` dependency on a hidden legacy section. Action *keys*
were left unchanged (e.g. `gyne_complaint`) since they are just
identifiers for translation/pinning — only their target and section
requirement changed, so no existing test asserting on action-key presence
needed to change.

## Admin UX Changes

The admin Sections page and profile overview show, for each hidden legacy
complaint section, the existing "Deprecated duplicate" / "Maps to
Complaints" badges (now populated by the extended alias map), plus a new
"Displayed as {label}" badge on the canonical `complaints` row explaining
why the doctor sees a specialty name instead of "Complaints".

## UAT/Browser Changes

- UAT checklist: new "Complaint Sections Use One Shared Workflow" section stating the rule; each affected profile's checklist now has a "{X} Complaint saves/reloads using the normal complaints form" line and a "no separate duplicate {X} Complaint panel appears" line.
- Visual QA notes: sidebar/section behavior note explains the complaints pane is shared across all workspaces with only the label varying.
- Browser fixture: `expected_sections` for physiotherapy, ophthalmology, dental, gynecology, ent, pediatrics, emergency, orthopedics, and surgery list `complaints` instead of the legacy key.
- Playwright: `HIDDEN_DUPLICATE_SECTIONS` extended with all 9 legacy complaint keys (asserts no tab renders); the generic `expected_sections.slice(0,4)` loop now also verifies the canonical complaints tab is visible for each of these profiles since `complaints` occupies one of the first four fixture-listed sections.

## Test Results

| Command | Result |
| --- | --- |
| `php artisan migrate` | Nothing to migrate |
| `php artisan db:seed --class=ConsultationSpecialtySeeder` | OK — 9 legacy complaint rows present and hidden; every profile's `complaints` row carries the correct specialty label |
| `php artisan test tests/Feature/Consultations/ConsultationComplaintCanonicalisationTest.php` | PASS — 12 tests, 188 assertions |
| `php artisan test tests/Feature/Consultations/ConsultationWorkspaceSectionDeduplicationTest.php` | PASS — 14 tests, 454 assertions (one 16.5 assertion updated: the "no alias map" sentinel profile) |
| `php artisan test tests/Feature/Consultations` | PASS — 199 tests, 3326 assertions |
| `php artisan test tests/Feature/ConsultationWorkspaceStabilisationTest.php` | PASS — 4 tests |
| `php artisan test tests/Feature/System/RouteLoadMemoryTest.php` | PASS — 2 tests |
| `php artisan route:list` | OK |
| `php artisan view:cache` / `view:clear` | OK |
| `npm run build` | OK |
| `npx playwright test tests/consultation-workspace.spec.ts` | PASS — 1 test |
| `npx playwright test tests/consultation-specialty-workspaces.spec.ts` | PASS — 22 tests |

## Full Suite Result

```
php artisan test
Tests:    1451 passed (7889 assertions)
Duration: 597.08s
```

No failures, no skips introduced by this phase.

## Backward Compatibility

- No `consultation_specialty_sections` or `consultation_specialty_entries` rows deleted or re-keyed.
- Every legacy complaint entry saved before this phase remains readable and continues to satisfy readiness/summary via the fallback merge.
- All 11 workspace profiles resolve, render, and pass the full hardening + dedup + complaint-canonicalisation suites together.
- Billing card removal (Phase 16.5) and section de-duplication (Phase 16.5) remain intact — verified by re-running their dedicated test suites alongside this phase's changes.

## Known Issues / Follow-up

- Gynecology and orthopedics remain outside the Playwright `SPECIALTY_PROFILES` smoke list (pre-existing gap from Phase 16.5); their complaint canonicalisation is covered by the PHPUnit suite only.
- The admin "Displayed as X" hint only covers the `complaints` canonical section; if future phases add more profile-specific labels for other canonical sections, `displayLabelFor()` and the admin badge should be generalised beyond the current `complaints`-only lookup table.
- Physiotherapy's legacy `presenting_problem` referral fields (`onset_date`, `onset_type`, `mechanism_of_injury`, `affected_area`, `referral_reason`) are readable only through the summary merge, not through any active capture UI; a future phase could fold the clinically useful ones into `physical_assessment` if referral tracking turns out to matter going forward.
