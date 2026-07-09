# Consultation Section Presentation Labels Report

Phase 16.7 — Profile-Aware Section Presentation Labels.

## Summary

Every shared/core consultation section (`complaints`, `diagnosis`,
`investigations`, `procedures`, `prescription`, `tasks`, `follow_up`,
`summary`) can now display a specialty-specific label in the workspace
sidebar, quick actions, readiness items, and the summary preview — all
sourced from **one** place, `ConsultationSpecialtySectionAliasService`. No
new sections were created, no storage keys changed, and no clinical
workflow behavior changed: a section's canonical key, its saved data, and
its underlying form are exactly what they were before this phase. Only the
*label attached to that canonical key* is now profile-aware, uniformly,
across every surface that shows a section name.

## Problem Found

Phase 16.6 solved this for exactly one canonical key (`complaints`) via a
narrow, hardcoded lookup table (`DISPLAY_LABEL_TRANSLATION_KEYS`) inside the
alias service. Every other canonical section — `diagnosis`,
`investigations`, `procedures`, `prescription`, `follow_up`, `summary` —
still showed the same generic label for every specialty (e.g. every
profile's Investigations tab literally read "Investigations", even though
obstetrics, orthopedics, and dental clinicians think of that pane as "ANC
Investigations / Screening", "Orthopedic Imaging / Investigations", and
"Dental Investigations / X-ray" respectively). Separately, the summary
preview computed its own headings via an entirely different lang lookup
(`summary_builder.sections.*`), so even where a workspace label already
existed, the generated document could show different wording for the same
concept — a second, independent hardcoding the phase explicitly forbids.

## Design

`ConsultationSpecialtySectionAliasService` gained:

- `canonicalSectionKey($profileCode, $sectionKey)` — now chains the
  profile-scoped duplicate-alias map (Phase 16.5/16.6) with the section
  component registry's global aliases, so it correctly resolves every
  section, not just the profile-specific duplicates.
- `displayLabelFor($profileCode, $canonicalKey)` — the profile override, or
  `null`. Backed by one new lang block, `section_presentation_labels.*`.
- `presentationLabelFor($profileCode, $canonicalKey)` — the override, or the
  generic canonical label (`sections.*`), or a title-cased fallback.
- `previewHeadingFor($profileCode, $templateKey, $canonicalKey)` — the
  **single heading source for the summary builder**: the override first,
  then the pre-existing specialty-only `summary_builder.sections.*` label
  (for structured sections that were never part of this relabeling, e.g.
  `visual_acuity`), then the generic fallback. This is the mechanism that
  removes the summary builder's independent hardcoding.
- `presentableCanonicalKeys()` — the 8 keys that can carry an override,
  used by the admin controllers to build a label map without hardcoding the
  list a third time.

`follow_up` is treated as a labelable canonical concept alongside the true
`CORE_SECTIONS` entries: it is a schema-backed structured section reused
across 8 of the 11 profiles (distinct from `tasks`, which general
medicine/physiotherapy use instead), so it is genuinely "shared" even
though the component registry doesn't classify it as core.

## Files Modified

- `app/Services/Consultation/Specialty/ConsultationSpecialtySectionAliasService.php` — generalized `displayLabelFor`; added `presentationLabelFor`, `previewHeadingFor`, `presentableCanonicalKeys`; `canonicalSectionKey` now chains through the section component registry; removed the narrow `DISPLAY_LABEL_TRANSLATION_KEYS` const.
- `app/Services/Consultation/Specialty/ConsultationSpecialtySummaryTemplateRegistry.php` — `templateForProfile()` now post-processes every row's label through `previewHeadingFor()` (and the document `title` through `displayLabelFor(..., 'summary')`), using each row's new `canonical_key` marker; added the missing `procedures` row for ophthalmology (a genuine gap — its workspace has a Procedures section the summary never surfaced).
- `app/Services/Consultation/Specialty/ConsultationSpecialtyQuickActionRegistry.php` — `actionsForProfile()` now overrides a quick action's label via `displayLabelFor()` when the action carries a `canonical_key` marker (all complaint actions, plus general medicine's diagnosis/prescription, dental's diagnosis/procedures, surgery's procedures, gynecology's prescription).
- `app/Services/Consultation/Specialty/ConsultationSpecialtyReadinessService.php` — each readiness item now carries a `section_label` field (the same profile-aware label the workspace/summary show for that item's `section_key`), so readiness never names a section differently from where its anchor points.
- `app/Http/Controllers/Admin/ConsultationSpecialtyProfileController.php`, `ConsultationSpecialtySectionController.php` — pass a generalized `canonicalSectionLabels` map (all 8 presentable keys, not just `complaints`) to the admin views.
- `resources/views/admin/consultation-specialties/partials/section-fields.blade.php`, `show.blade.php` — the "Displayed as X" badge now applies to any canonical row with an override (not just `complaints`); the "Maps to X" badge for hidden legacy sections now names the *profile's own* canonical label (e.g. "Maps to Dental Investigations / X-ray" instead of the generic "Maps to Investigations").
- `lang/en/consultation_specialties.php`, `lang/fr/consultation_specialties.php` — new `section_presentation_labels.*` block (10 profiles × up to 7 canonical keys each).
- Tests updated: `ConsultationComplaintCanonicalisationTest.php`, `ConsultationWorkspaceSectionDeduplicationTest.php` (admin "maps to" badge assertions now expect the profile-specific label, since that badge is itself profile-aware as of this phase), `ConsultationSpecialtySummaryBuilderTest.php` (dental's summary document title is now "Dental Summary", sourced from the same service, instead of the old standalone "Dental Consultation Summary" lang string).

## Files Added

- `tests/Feature/Consultations/ConsultationSectionPresentationLabelsTest.php` — 14 tests / 267 assertions.
- `docs/CONSULTATION_SECTION_PRESENTATION_LABELS_REPORT.md` (this report).

## Required Examples — Verified

Every label in the phase's "Required examples" table is asserted verbatim
by `test_alias_service_resolves_every_required_profile_aware_label` and
`test_preview_headings_use_profile_aware_labels_for_every_profile`,
including the "X / Y" hybrid labels (e.g. "ANC Investigations / Screening",
"Orthopedic Imaging / Investigations", "Eye Prescription / Treatment").
`general_medicine`'s labels resolve to the plain canonical wording
(Complaints, Diagnosis, Investigations, Procedures, Prescription,
Tasks) — no override needed, matching the phase's own baseline table.

## Workspace / Summary Consistency

`test_workspace_sidebar_and_summary_preview_labels_match` builds both the
workspace layout and the summary template for ophthalmology, dental,
obstetrics, orthopedics, surgery, and emergency, and asserts the label for
each shared canonical key is character-for-character identical between the
two surfaces — proving the summary preview no longer hardcodes a heading
independently of the workspace display layer.

## Legacy Compatibility

- No storage keys changed: every canonical section keeps the same
  `section_key` it had before this phase; only its `label`/`translated_label`
  presentation changes.
- No sections were created or duplicated; `follow_up`'s treatment as a
  labelable concept is presentation-only — it does not change which DB row
  or form the doctor interacts with.
- Phase 16.5 de-duplication aliases (`PROFILE_ALIASES`, `withMergedLegacyEntries`)
  and Phase 16.6 complaint canonicalisation are untouched data-wise; the
  admin "maps to" badge and the complaint labels keep returning the exact
  same values as before — only their DERIVATION moved to the generalized
  lookup, verified by the existing Phase 16.5/16.6 suites passing unchanged
  (after two badge-text assertions were updated to expect the *more
  specific* profile-aware wording, which is the intended improvement of
  this phase, not a regression).
- Billing/service mapping remains fully absent from the doctor workspace
  and the summary preview (`test_billing_context_is_not_exposed_by_preview_or_workspace`);
  no billing card was reintroduced.
- The summary preview endpoint remains read-only; `final_note` is
  unmodified by a preview call (`test_final_note_is_not_modified_by_preview`).

## Test Results

| Command | Result |
| --- | --- |
| `php artisan migrate` | Nothing to migrate |
| `php artisan db:seed --class=ConsultationSpecialtySeeder` | OK |
| `php artisan test tests/Feature/Consultations/ConsultationSectionPresentationLabelsTest.php` | PASS — 14 tests, 267 assertions |
| `php artisan test tests/Feature/Consultations` | PASS — 213 tests, 3593 assertions |
| `php artisan test tests/Feature/ConsultationWorkspaceStabilisationTest.php` | PASS — 4 tests |
| `php artisan test tests/Feature/System/RouteLoadMemoryTest.php` | PASS — 2 tests |
| `php artisan route:list` | OK |
| `php artisan view:cache` / `view:clear` | OK |
| `npm run build` | OK |
| `npx playwright test tests/consultation-workspace.spec.ts` | PASS — 1 test |
| `npx playwright test tests/consultation-specialty-workspaces.spec.ts` | PASS — 22 tests |
| `php artisan test` (full suite) | PASS — 1466 tests, 8164 assertions |

## Backward Compatibility

All 11 workspace profiles resolve, render, and pass the full hardening +
de-duplication + complaint-canonicalisation + presentation-label suites
together. No previously saved data, billing mapping, admin configuration,
or reporting behavior was affected.

## Known Issues / Follow-up

- Gynecology, ENT, and pediatrics' summary templates still don't surface
  `investigations`/`procedures`/`prescription` rows at all (a pre-existing
  gap predating this phase); their profile-aware labels for those keys are
  registered in `section_presentation_labels` and will apply automatically
  the day those rows are added, but today only `complaints`/`diagnosis` are
  exercised for those three profiles.
- The readiness item's new `section_label` field is additive and not yet
  wired into `right-panel.blade.php` (which still renders only `label` and
  `anchor`); a future phase could show it alongside the blocking/warning
  message if a UI need arises.
- `physiotherapy`'s `diagnosis`, `investigations`, `procedures`, and
  `follow_up` presentation labels are registered but currently unused,
  since physiotherapy's workspace has no sections keyed to those canonical
  concepts today — they will apply automatically if such sections are ever
  added.
