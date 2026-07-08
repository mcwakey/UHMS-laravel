# Consultation Complaint Section Canonicalisation Audit

Phase 16.6 — Canonical Complaints Pane With Specialty-Specific Labels.

## Problem

Several specialist workspaces captured the patient's main complaint through a
schema-driven **structured entry** (`consultation_specialty_entries`, a
generic key/value form) instead of the real core **Complaint** model used by
general medicine. This meant:

- Duration/severity, HOPC linking, and completion/status behavior differed
  from the general medicine complaints pane, even though the clinical intent
  (record the chief complaint) was identical.
- Two independent data channels existed for "what is wrong with the
  patient" — the real `complaints` table (used by HOPC, the summary
  fallback, and the referral/next-appointment flows) and a parallel
  specialty entry (`eye_complaint`, `gyne_complaint`, etc.) that none of
  those systems ever looked at.

`eye_complaint` and `dental_complaint` were nominally "aliased" to
`complaints` in `ConsultationSpecialtySectionComponentRegistry::ALIASES`,
but because both keys also have a structured schema, component resolution
checked `hasSchema()` first and rendered them as structured panels anyway —
the alias never actually took effect for rendering, only masked itself as
intent in the code.

## Decision Rule Applied

If a section captures the patient's main/chief complaint → map it to
canonical `complaints`, keep a profile-specific **display label**, and
preserve any previously saved structured entry as hidden legacy data
(readable through readiness fallback and the summary builder).

## Complaint Section Mapping

| Profile | Legacy key | Canonical key | Display label | Notes |
| --- | --- | --- | --- | --- |
| general_medicine | `complaints` (already canonical) | `complaints` | Complaints | No change. |
| physiotherapy | `presenting_problem` | `complaints` | Presenting Problem | See analysis below. |
| ophthalmology | `eye_complaint` | `complaints` | Eye Complaint | Was falsely "aliased" (see Problem). |
| dental | `dental_complaint` | `complaints` | Dental Complaint | Was falsely "aliased" (see Problem). |
| obstetrics | *(none previously seeded)* | `complaints` | Current Complaint | New canonical section added; `current_pregnancy` stays structured. |
| gynecology | `gyne_complaint` | `complaints` | Gyne Complaint | |
| ent | `ent_complaint` | `complaints` | ENT Complaint | |
| pediatrics | `pediatric_complaint` | `complaints` | Pediatric Complaint | |
| emergency | `emergency_complaint` | `complaints` | Emergency Complaint | No readiness rule existed for this key; none added. |
| orthopedics | `ortho_complaint` | `complaints` | Ortho Complaint | |
| surgery | `surgical_complaint` | `complaints` | Surgical Complaint | |

## Physiotherapy `presenting_problem` — detailed analysis

Schema fields: `problem_description`, `onset_date`, `onset_type`,
`mechanism_of_injury`, `affected_area`, `referral_reason`.

`problem_description` is functionally the chief complaint. The remaining
fields (`onset_date`, `onset_type`, `mechanism_of_injury`, `affected_area`,
`referral_reason`) are referral/injury context beyond a plain complaint —
but physiotherapy already has dedicated structured sections for the
clinical detail that matters ongoing (`physical_assessment` captures
findings, `treatment_plan` captures goals/modalities/schedule). No open
capture need is lost by canonicalising the complaint itself: the decision
rule says "if uncertain, prefer canonical complaints and keep the
specialty-specific structured data available as legacy/summary data," and
that is what was done — `presenting_problem` is hidden but its saved data
still surfaces under the "Presenting Problem" summary heading merged with
any core complaint text. No new section was created to hold the referral
fields; they remain readable exactly where they were saved.

## Layout Changes

Every affected profile's seeded **visible** section list now contains
`complaints` in place of the legacy key (same position), with a
profile-specific DB label. The legacy key moves to `legacy_sections`
(`is_visible = false`, row preserved, schema still validates the historical
shape for any pre-existing saved entries).

## Files Touched

See the companion report,
[docs/CONSULTATION_COMPLAINT_CANONICALISATION_REPORT.md](CONSULTATION_COMPLAINT_CANONICALISATION_REPORT.md),
for the full file list, behavior, and test results.
