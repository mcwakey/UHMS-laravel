You are working on the UHMS Laravel codebase.

We have completed:

* Phase 1: Specialist Consultation Profile Foundation
* Phase 2: Specialty Resolver
* Phase 3: Specialty Layout Engine
* Phase 4: Structured Specialist Forms
* Phase 5: Specialty Favorites and Smart Defaults
* Phase 6: Specialty Order Sets
* Phase 7: Specialty Completion Readiness
* Phase 8: Specialty Summary Builder
* Phase 9: Doctor Personal Workspace

Phase 9 added a compact doctor specialty workspace header with doctor-scoped payload, quick actions, pinned actions, compact mode preferences, lightweight metrics, readiness/order-set/summary alerts, and safe fallback behavior.

Now implement:

# Consultation Specialist Extension — Phase 10: Admin Configuration UI

## Phase 10 Goal

Build admin-facing configuration screens for the specialist consultation system.

Admins should be able to manage the configuration that currently exists mostly through seeders/code:

```text
Specialty profiles
Specialty sections
Specialty mappings
Specialty favorites
Specialty order sets
Order set items
Doctor consultation preferences visibility/reset where safe
```

This phase is about **configuration management**, not clinical workflow changes.

The consultation workspace must continue working exactly as it does now.

---

# Important Rules

Do not rewrite the consultation module.

Do not create separate specialist consultation modules.

Do not implement billing/service mapping yet.

Do not implement dashboard integration yet.

Do not make readiness rules or summary templates fully database-driven yet unless it is already trivial and safe.

Do not allow admins to edit raw unsafe PHP/template logic.

Do not expose patient clinical data in admin configuration pages.

Do not run the full test suite yet.

Only run focused checks for this phase.

If admin configuration pages fail, the consultation workspace must still keep working from seeded/configured data.

---

# Current Context

Existing specialist consultation system includes:

```text
consultation_specialty_profiles
consultation_specialty_sections
consultation_specialty_profile_mappings
consultation_specialty_favorites
consultation_specialty_order_sets
consultation_specialty_order_set_items
doctor_consultation_preferences
```

Existing permissions from Phase 1 include:

```text
consultation-specialties.view
consultation-specialties.create
consultation-specialties.update
consultation-specialties.delete
consultation-specialties.configure
```

Phase 9 added preference routes:

```text
PATCH admin/consultations/preferences/pinned-actions
PATCH admin/consultations/preferences/layout
```

Use the project’s existing admin UI style, route naming style, permission middleware, validation style, flash/JSON response style, audit logging style, and localization conventions.

---

# Required Deliverables

## 1. Inspect Existing Admin Setup Patterns

Before coding, inspect existing admin configuration screens for:

```text
Departments
Services
Roles/permissions
ICD codes if present
Clinical setup/configuration pages
Consultation routes/setup if present
Frequency options if present
Seeder/configuration management patterns
Activity/audit logging patterns
```

Identify:

```text
route groups
controller namespaces
Blade layout
table/list style
form style
pagination/search/filter patterns
permission middleware
breadcrumb/menu patterns
translation namespace
delete/deactivate conventions
ActivityLogService usage
```

Do not guess. Follow the actual project convention.

Document findings in the phase report.

---

## 2. Add Admin Navigation Entry

Add a clean admin navigation entry under the most appropriate existing menu.

Suggested location:

```text
Admin / Clinical Setup / Consultation Specialties
```

or if the project has a consultation setup area:

```text
Admin / Consultation Setup / Specialties
```

Menu should be visible only to users with:

```text
consultation-specialties.view
```

Suggested sub-pages:

```text
Specialty Profiles
Sections
Mappings
Favorites
Order Sets
```

Do not overcrowd the sidebar.

---

## 3. Specialty Profile Management

Create admin controller:

```text
app/Http/Controllers/Admin/ConsultationSpecialtyProfileController.php
```

Or follow the actual project namespace convention.

Routes:

```text
GET    admin/consultation-specialties
GET    admin/consultation-specialties/create
POST   admin/consultation-specialties
GET    admin/consultation-specialties/{profile}
GET    admin/consultation-specialties/{profile}/edit
PATCH  admin/consultation-specialties/{profile}
DELETE admin/consultation-specialties/{profile}
```

Route names should follow project convention, for example:

```text
admin.consultation-specialties.index
admin.consultation-specialties.create
admin.consultation-specialties.store
admin.consultation-specialties.show
admin.consultation-specialties.edit
admin.consultation-specialties.update
admin.consultation-specialties.destroy
```

Fields:

```text
code
name
description
department_type
icon
color
is_active
sort_order
metadata
```

Rules:

* `code` must be unique and slug-like.
* Prevent deleting `general_medicine`.
* Prefer soft deactivate over destructive delete if project convention supports it.
* If deleting a non-general profile, prevent delete when it has entries/applications unless project supports safe deletion.
* Admin can activate/deactivate profiles.
* Admin can reorder profiles.
* Audit profile create/update/deactivate/delete.

Views:

```text
index list with search/filter
create form
edit form
show/details page
```

Index should show:

```text
name
code
department_type
active status
section count
favorite count
order set count
mapping count
sort order
actions
```

---

## 4. Specialty Section Management

Create controller:

```text
app/Http/Controllers/Admin/ConsultationSpecialtySectionController.php
```

Routes can be nested under profile:

```text
GET    admin/consultation-specialties/{profile}/sections
POST   admin/consultation-specialties/{profile}/sections
PATCH  admin/consultation-specialties/{profile}/sections/{section}
DELETE admin/consultation-specialties/{profile}/sections/{section}
POST   admin/consultation-specialties/{profile}/sections/reorder
```

Fields:

```text
section_key
label
component
display_order
is_required
is_visible
config
```

Rules:

* `section_key` must be unique per profile.
* Existing known section keys should be selectable from a dropdown.
* Allow custom future section keys but validate slug-like format.
* `component` should not accept arbitrary unsafe file paths.
* Prefer selecting from registered components returned by `ConsultationSpecialtySectionComponentRegistry`.
* Unknown/custom section should use the generic shell.
* Admin can mark visible/hidden.
* Admin can mark required.
* Admin can reorder sections.
* Prevent deleting all visible sections from `general_medicine`.
* If profile has no visible sections, warn admin that layout will fall back to general.

Important:

Do not make this screen capable of editing PHP/Blade templates.

---

## 5. Specialty Mapping Management

Create controller:

```text
app/Http/Controllers/Admin/ConsultationSpecialtyMappingController.php
```

Routes:

```text
GET    admin/consultation-specialties/mappings
POST   admin/consultation-specialties/mappings
PATCH  admin/consultation-specialties/mappings/{mapping}
DELETE admin/consultation-specialties/mappings/{mapping}
```

Manage:

```text
profile
department
consultation_route_id if applicable
department_type
source
priority
is_active
metadata
```

Rules:

* Admin can map department type to profile.
* Admin can map department to profile.
* Admin can map consultation route/session to profile if the project has a stable route/session model.
* Do not require all mapping targets.
* At least one of department, department_type, consultation_route_id, or user_id must be present.
* Warn if mapping conflicts with a higher-priority mapping.
* Sorting/priority should be clear.
* Do not create or modify departments here.

Index filters:

```text
profile
department_type
department
active/inactive
```

---

## 6. Specialty Favorites Management

Create controller:

```text
app/Http/Controllers/Admin/ConsultationSpecialtyFavoriteController.php
```

Routes can be nested under profile:

```text
GET    admin/consultation-specialties/{profile}/favorites
POST   admin/consultation-specialties/{profile}/favorites
PATCH  admin/consultation-specialties/{profile}/favorites/{favorite}
DELETE admin/consultation-specialties/{profile}/favorites/{favorite}
POST   admin/consultation-specialties/{profile}/favorites/reorder
```

Fields:

```text
favorite_type
favoritable_type
favoritable_id
code
label
description
search_terms
metadata
sort_order
is_active
```

Favorite types:

```text
diagnosis
investigation
procedure
drug
frequency
task
follow_up_instruction
clinical_instruction
```

Rules:

* Label is required.
* Favorite type is required.
* Profile is required.
* If favoritable is selected, validate the linked model exists.
* If linked model does not exist, allow label-only favorite.
* Do not allow arbitrary unsafe class names for `favoritable_type`.
* Use a controlled list of allowed favoritable model types based on existing project models:

  * ICD/diagnosis model if present
  * Service model for investigations/procedures if present
  * Drug model if present
  * Other safe catalogue models if present
* Admin can activate/deactivate favorites.
* Admin can reorder favorites.
* Existing workspace behavior must continue: label-only favorites remain suggestions only.

Index filters:

```text
type
active status
linked vs label-only
search
```

---

## 7. Specialty Order Set Management

Create controller:

```text
app/Http/Controllers/Admin/ConsultationSpecialtyOrderSetController.php
```

Routes nested under profile:

```text
GET    admin/consultation-specialties/{profile}/order-sets
GET    admin/consultation-specialties/{profile}/order-sets/create
POST   admin/consultation-specialties/{profile}/order-sets
GET    admin/consultation-specialties/{profile}/order-sets/{orderSet}
GET    admin/consultation-specialties/{profile}/order-sets/{orderSet}/edit
PATCH  admin/consultation-specialties/{profile}/order-sets/{orderSet}
DELETE admin/consultation-specialties/{profile}/order-sets/{orderSet}
POST   admin/consultation-specialties/{profile}/order-sets/reorder
```

Fields:

```text
code
name
description
category
icon
color
is_active
sort_order
metadata
```

Rules:

* `code` unique per profile.
* Prevent destructive delete if order set has application history, unless project convention allows it.
* Prefer deactivate when application history exists.
* Admin can activate/deactivate.
* Admin can reorder.
* Audit changes.

Show page should list items and recent application count, but not patient-identifying details.

---

## 8. Order Set Item Management

Create controller:

```text
app/Http/Controllers/Admin/ConsultationSpecialtyOrderSetItemController.php
```

Routes nested under order set:

```text
GET    admin/consultation-specialties/{profile}/order-sets/{orderSet}/items
POST   admin/consultation-specialties/{profile}/order-sets/{orderSet}/items
PATCH  admin/consultation-specialties/{profile}/order-sets/{orderSet}/items/{item}
DELETE admin/consultation-specialties/{profile}/order-sets/{orderSet}/items/{item}
POST   admin/consultation-specialties/{profile}/order-sets/{orderSet}/items/reorder
```

Fields:

```text
item_type
label
description
target_section
target_field
favoritable_type
favoritable_id
code
payload
apply_mode
is_required
sort_order
is_active
metadata
```

Allowed `item_type` values:

```text
diagnosis
investigation
procedure
drug
prescription
frequency
task
follow_up_instruction
clinical_instruction
specialty_entry_patch
note
```

Allowed `apply_mode` values:

```text
suggest
insert_text
create_task
patch_specialty_entry
create_diagnosis_if_supported
create_investigation_if_linked
create_procedure_if_linked
create_prescription_if_linked
```

Rules:

* Do not allow unsafe arbitrary apply modes.
* Validate `target_section` exists in the parent profile when apply mode is `patch_specialty_entry`.
* Validate `target_field` against `ConsultationSpecialtySectionSchema` when possible.
* Payload must be valid JSON.
* For `patch_specialty_entry`, payload should include a safe `merge` object.
* For `create_task`, payload should include a title or label.
* Linked catalogue items should use controlled allowed model types.
* Label-only catalogue-sensitive items must remain suggestion-only unless explicitly supported by existing safe workflow.
* Admin UI should show warnings for unsafe/non-auto-applicable combinations.

---

## 9. Doctor Preference Visibility / Reset

Do not build a full staff management module.

Add a small admin view only if it fits cleanly:

```text
admin/consultation-specialties/doctor-preferences
```

Purpose:

* See which doctors have pinned actions/default layouts/default specialty preferences.
* Reset a doctor’s consultation preference if needed.
* Do not expose private clinical data.
* Do not allow editing another doctor’s pinned actions in detail unless project convention supports it.

Controller:

```text
app/Http/Controllers/Admin/DoctorConsultationPreferenceAdminController.php
```

Actions:

```text
index
destroy/reset
```

Rules:

* Reset deletes or clears the preference row.
* Audit reset action.
* Must require `consultation-specialties.configure`.

If this is too much for this phase, skip the UI and document as follow-up. Do not force it.

---

## 10. Validation Layer

Use Form Requests if project convention supports them.

Suggested request classes:

```text
app/Http/Requests/Admin/ConsultationSpecialtyProfileRequest.php
app/Http/Requests/Admin/ConsultationSpecialtySectionRequest.php
app/Http/Requests/Admin/ConsultationSpecialtyMappingRequest.php
app/Http/Requests/Admin/ConsultationSpecialtyFavoriteRequest.php
app/Http/Requests/Admin/ConsultationSpecialtyOrderSetRequest.php
app/Http/Requests/Admin/ConsultationSpecialtyOrderSetItemRequest.php
```

Or controller validation if that is the project’s style.

Validation must cover:

```text
required fields
unique constraints
slug-like codes/keys
allowed enums
safe model types
valid JSON payload
profile ownership
section ownership
order set ownership
permission checks
```

---

## 11. Audit Logging

Follow existing audit/activity convention.

Log admin configuration changes for:

```text
profile created/updated/deactivated/deleted
section created/updated/reordered/deleted
mapping created/updated/deactivated/deleted
favorite created/updated/reordered/deactivated/deleted
order set created/updated/reordered/deactivated/deleted
order set item created/updated/reordered/deactivated/deleted
doctor preference reset if implemented
```

Do not log excessive full JSON if project avoids it. Store concise before/after metadata where safe.

---

## 12. Localization

Add EN/FR keys for admin UI.

Suggested namespace:

```text
consultation_specialties.php
```

or separate:

```text
consultation_specialty_admin.php
```

Use whichever matches project convention.

Keys should cover:

```text
admin.title
admin.profiles
admin.sections
admin.mappings
admin.favorites
admin.order_sets
admin.order_set_items
admin.create
admin.edit
admin.update
admin.delete
admin.deactivate
admin.activate
admin.reorder
admin.status
admin.active
admin.inactive
admin.search
admin.filters
admin.no_records
admin.confirm_delete
admin.confirm_deactivate
admin.saved
admin.updated
admin.deleted
admin.deactivated
admin.reordered
admin.general_profile_locked
admin.component_registry
admin.generic_shell
admin.label_only_suggestion
admin.linked_catalogue_item
admin.application_history_exists
admin.unsafe_apply_mode_warning
```

No hardcoded visible admin text.

---

## 13. UI Quality Requirements

Admin pages should be clean and practical:

```text
searchable lists
filters where useful
pagination
active/inactive badges
linked/label-only badges
sort order display
quick links from profile show page to sections/favorites/order sets
clear warnings for risky config
empty states
responsive forms
```

No huge one-page monster forms.

Keep forms moderate and aligned with existing UHMS UI.

---

## 14. Focused Tests

Create:

```text
tests/Feature/Consultations/ConsultationSpecialtyAdminConfigurationTest.php
```

Suggested tests:

### Permission protection

* User without permission cannot access admin specialty pages.
* User with `consultation-specialties.view` can view index.
* Create/update/delete/configure permissions are respected.

### Profile management

* Admin can create profile.
* Admin can update profile.
* Cannot delete or deactivate `general_medicine` destructively.
* Duplicate profile code rejected.

### Section management

* Admin can add section to profile.
* Duplicate section key per profile rejected.
* Admin can reorder sections.
* Invalid unsafe component rejected.
* Unknown custom section uses generic shell behavior.

### Mapping management

* Admin can create department type mapping.
* Mapping requires at least one target.
* Inactive mapping does not affect resolver.
* Higher priority mapping works if resolver supports priority.

### Favorites management

* Admin can create label-only favorite.
* Admin can create linked favorite using allowed model type.
* Unsafe favoritable type rejected.
* Favorite appears in workspace after creation.
* Inactive favorite is hidden from workspace.

### Order set management

* Admin can create order set.
* Duplicate order set code per profile rejected.
* Cannot delete order set with application history; can deactivate instead.

### Order set item management

* Admin can create suggestion item.
* Admin can create `patch_specialty_entry` item with valid target section/field.
* Invalid target field rejected.
* Unsafe apply mode rejected.
* Payload must be valid JSON.
* Created order set item appears in workspace preview.

### Audit logging

* At least one admin config change writes activity/audit log if project convention supports assertion.

### Localisation keys

* EN/FR keys exist.

### Existing workspace regression

* Phase 1-9 focused tests still pass.
* Workspace stabilisation still passes.

---

## 15. Optional Browser Smoke Test

Only if existing browser fixture is stable:

```text
Admin opens Consultation Specialties
Creates a small test favorite for ophthalmology
Opens an ophthalmology consultation
Confirms favorite appears
Deactivates the favorite
Confirms it no longer appears
```

Do not create a heavy browser suite in this phase.

---

## 16. Minimal Checks to Run

Run:

```bash
php artisan migrate
php artisan db:seed --class=ConsultationSpecialtySeeder
php artisan test tests/Feature/Consultations/ConsultationSpecialtyAdminConfigurationTest.php
php artisan test tests/Feature/Consultations/ConsultationSpecialtyFoundationTest.php
php artisan test tests/Feature/Consultations/ConsultationSpecialtyResolverTest.php
php artisan test tests/Feature/Consultations/ConsultationSpecialtyLayoutTest.php
php artisan test tests/Feature/Consultations/ConsultationSpecialtyEntryTest.php
php artisan test tests/Feature/Consultations/ConsultationSpecialtyFavoriteTest.php
php artisan test tests/Feature/Consultations/ConsultationSpecialtyOrderSetTest.php
php artisan test tests/Feature/Consultations/ConsultationSpecialtyReadinessTest.php
php artisan test tests/Feature/Consultations/ConsultationSpecialtySummaryBuilderTest.php
php artisan test tests/Feature/Consultations/DoctorSpecialtyWorkspaceTest.php
php artisan test tests/Feature/ConsultationWorkspaceStabilisationTest.php
php artisan route:list
php artisan view:cache
php artisan view:clear
```

Also run PHP lint on new/modified PHP files.

If localization keys were added and the project has a localization parity/lock command, run it.

Do not run the wide full-suite yet.

---

## 17. Phase Report

Create:

```text
docs/CONSULTATION_SPECIALIST_EXTENSION_PHASE_10_ADMIN_CONFIGURATION_UI_REPORT.md
```

If the repo convention has a consultation docs subfolder, use:

```text
docs/consultation/CONSULTATION_SPECIALIST_EXTENSION_PHASE_10_ADMIN_CONFIGURATION_UI_REPORT.md
```

The report must include:

```text
# Consultation Specialist Extension — Phase 10 Admin Configuration UI Report

## Summary
Explain what was implemented.

## Existing Admin Pattern Findings
Document route groups, controller style, Blade layout, permissions, forms, localization, and audit conventions discovered.

## Files Added
List all new files.

## Files Modified
List all modified files.

## Admin Screens Added
List profile, section, mapping, favorite, order set, order set item, and preference screens implemented.

## Validation and Safety
Explain slug validation, allowed components, allowed model types, allowed apply modes, JSON payload validation, ownership checks, and general profile protection.

## Audit Logging
Explain what admin configuration changes are logged.

## UI Changes
Explain navigation, lists, filters, forms, badges, warnings, and responsive behavior.

## Backward Compatibility
Confirm consultation workspace behavior, specialist forms, favorites, order sets, readiness, summary builder, and doctor workspace remain stable.

## Tests Added
List focused admin configuration tests.

## Checks Run
Include commands and pass/fail summary.

## Known Issues / Follow-up
List anything for:
- Phase 11 billing/service mapping
- Later dashboard integration
- Later full browser testing
- Later database-driven readiness/summary templates
```

---

# Acceptance Criteria

Phase 10 is complete only when:

* Admin can view consultation specialty profiles.
* Admin can create/update/deactivate non-general profiles.
* `general_medicine` is protected.
* Admin can manage profile sections safely.
* Admin can manage resolver mappings safely.
* Admin can manage specialty favorites safely.
* Admin can manage order sets safely.
* Admin can manage order set items safely.
* Unsafe component paths/model types/apply modes are rejected.
* JSON payloads are validated.
* Configuration changes are permission-protected.
* Configuration changes are audited where project convention supports it.
* Admin navigation entry exists.
* EN/FR localization keys exist.
* Existing consultation workspace behavior remains stable.
* Focused admin configuration tests pass.
* Phase 1-9 focused tests still pass.
* Workspace stabilisation test still passes.
* View cache/build check passes.
* Phase 10 report is created.

Stop after Phase 10. Do not implement billing/service mapping, dashboard integration, or full browser hardening yet.
