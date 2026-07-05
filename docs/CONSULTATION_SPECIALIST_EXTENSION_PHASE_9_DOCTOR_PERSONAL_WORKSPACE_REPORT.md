# Consultation Specialist Extension — Phase 9 Doctor Personal Workspace Report

## Summary
Implemented the compact doctor personal workspace layer for the consultation workspace. Phase 9 now builds a doctor-scoped workspace payload with profile-aware quick actions, lightweight daily metrics, readiness/order-set/summary alerts, persisted pinned actions, and persisted layout/compact-mode preferences.

## Existing Doctor/Context Findings
- Doctor context is based on the authenticated `User`, including `full_name`, direct `department`, primary departments, and the existing `doctorConsultationPreference()` relation.
- Existing `DoctorConsultationPreference` storage already supports default profile/department, pinned actions, preferred layout, compact mode, and metadata.
- Consultation specialty context is resolved through `ConsultationSpecialtyProfileResolver`, which already supports route, department, department type, and doctor preference fallback.
- Queue, appointment, lab request, and consultation task models provide enough lightweight status data for non-patient-identifying workspace metrics.
- The consultation workspace already exposes specialist readiness, order sets, favorites, and the Phase 8 summary builder payloads.

## Files Added
- `app/Data/Consultation/Specialty/DoctorSpecialtyWorkspace.php`
- `app/Http/Controllers/Doctor/Consultations/DoctorConsultationPreferenceController.php`
- `app/Services/Consultation/Specialty/DoctorConsultationPreferenceService.php`
- `app/Services/Consultation/Specialty/ConsultationSpecialtyQuickActionRegistry.php`
- `app/Services/Consultation/Specialty/DoctorSpecialtyWorkspaceMetricService.php`
- `app/Services/Consultation/Specialty/DoctorSpecialtyWorkspaceService.php`
- `tests/Feature/Consultations/DoctorSpecialtyWorkspaceTest.php`
- `docs/CONSULTATION_SPECIALIST_EXTENSION_PHASE_9_DOCTOR_PERSONAL_WORKSPACE_REPORT.md`

## Files Modified
- `app/Http/Controllers/Doctor/Consultations/Concerns/HandlesConsultationWorkspace.php`
- `resources/views/consultations/partials/page-config.blade.php`
- `resources/views/consultations/show.blade.php`
- `routes/web.php`
- `lang/en/consultation_specialties.php`
- `lang/fr/consultation_specialties.php`

## Doctor Workspace Design
- `DoctorSpecialtyWorkspace` is the DTO returned to Blade and page config.
- `DoctorSpecialtyWorkspaceService` composes doctor identity, active specialty profile, department, metrics, quick actions, pinned action ordering, alerts, preference state, readiness summary, order-set summary, and summary-builder availability.
- `ConsultationSpecialtyQuickActionRegistry` keeps action definitions profile-aware and only exposes actions whose target sections are available in the active profile.
- `DoctorSpecialtyWorkspaceMetricService` keeps counts lightweight and scoped to the doctor and department where possible.
- `DoctorConsultationPreferenceService` centralizes creation and updates of per-doctor workspace preferences.
- Workspace building is wrapped with safe fallbacks so the consultation page still opens if preference, metric, or context data cannot be assembled.

## Quick Actions Implemented
- General Medicine: complaints, examination, diagnosis, prescription, generate summary, readiness.
- Physiotherapy: presenting problem, pain assessment, physical assessment, treatment plan, therapy session, home exercise plan, order sets, generate summary, readiness.
- Ophthalmology: eye complaint, visual acuity, refraction, intraocular pressure, eye examination, prescription, order sets, generate summary, readiness.
- Dental: dental complaint, tooth chart, oral examination, dental diagnosis, dental procedure, consent, order sets, generate summary, readiness.

Quick actions navigate or open existing UI only. They do not directly mutate clinical records.

## Metrics and Alerts
Available metrics are waiting today, reviewed today, pending completion, pending results, pending tasks, and follow-ups due. Empty or unavailable metrics are hidden in the header and do not block rendering.

Alerts summarize readiness blockers, readiness warnings, available order sets, and generated-summary availability. They intentionally stay compact and do not duplicate the full readiness card.

## UI Changes
The old specialty identity strip in the consultation workspace was upgraded into a compact doctor specialty workspace header. It shows the doctor-facing workspace label, active department/profile context, visible metrics, readiness/order-set/summary alerts, and specialty-aware quick actions.

Pinned actions are prioritized first when present. Compact mode can be toggled from the header. Quick action navigation scrolls to the relevant section or opens the relevant tab/panel when needed. The header remains additive and compact so the specialist forms, order sets, readiness card, and summary builder remain the primary clinical surfaces.

## Preferences
Pinned actions are stored per doctor in `doctor_consultation_preferences.pinned_actions`. Layout and compact mode are stored in `preferred_layout` and `compact_mode`.

New routes:
- `PATCH admin/consultations/preferences/pinned-actions`
- `PATCH admin/consultations/preferences/layout`

Validation restricts pinned actions to the active profile's registered quick-action keys.

## Backward Compatibility
General medicine remains light and keeps the core workflow focused. Specialist entry saving, order-set application, completion readiness, and summary builder behavior remain unchanged. The new workspace consumes those payloads without changing their persistence behavior.

## Tests Added
`tests/Feature/Consultations/DoctorSpecialtyWorkspaceTest.php` covers:
- Workspace payload and header rendering.
- General medicine lightweight behavior.
- Profile-specific quick action availability.
- Pinned action and compact-mode persistence.
- Invalid pinned action rejection.
- Safe metrics and readiness-derived alerts.
- English and French localization keys.

## Checks Run
- `php -l app/Data/Consultation/Specialty/DoctorSpecialtyWorkspace.php && php -l app/Services/Consultation/Specialty/DoctorConsultationPreferenceService.php && php -l app/Services/Consultation/Specialty/ConsultationSpecialtyQuickActionRegistry.php && php -l app/Services/Consultation/Specialty/DoctorSpecialtyWorkspaceMetricService.php && php -l app/Services/Consultation/Specialty/DoctorSpecialtyWorkspaceService.php && php -l app/Http/Controllers/Doctor/Consultations/DoctorConsultationPreferenceController.php && php -l tests/Feature/Consultations/DoctorSpecialtyWorkspaceTest.php` — passed.
- `php artisan test tests/Feature/Consultations/DoctorSpecialtyWorkspaceTest.php` — passed, 7 tests and 52 assertions.
- `php artisan test tests/Feature/Consultations/ConsultationSpecialtyFoundationTest.php tests/Feature/Consultations/ConsultationSpecialtyResolverTest.php tests/Feature/Consultations/ConsultationSpecialtyLayoutTest.php tests/Feature/Consultations/ConsultationSpecialtyEntryTest.php tests/Feature/Consultations/ConsultationSpecialtyFavoriteTest.php tests/Feature/Consultations/ConsultationSpecialtyOrderSetTest.php tests/Feature/Consultations/ConsultationSpecialtyReadinessTest.php tests/Feature/Consultations/ConsultationSpecialtySummaryBuilderTest.php tests/Feature/Consultations/DoctorSpecialtyWorkspaceTest.php tests/Feature/ConsultationWorkspaceStabilisationTest.php` — passed, 89 tests and 447 assertions.
- `php artisan route:list` — passed.
- `php artisan route:list --name=consultations.preferences` — passed, 2 preference routes registered.
- `php artisan view:cache && php artisan view:clear` — passed.

## Known Issues / Follow-up
- Phase 10 admin configuration UI is not implemented yet.
- Billing/service mapping is not implemented in this phase.
- Dashboard integration is not implemented in this phase.
- Full browser testing across real clinic screen sizes remains a later hardening step.
