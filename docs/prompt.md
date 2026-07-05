You are working on the UHMS Laravel codebase.

We have completed **Specialist Consultation Extension Phase 1 — Specialty Profile Foundation**.

Phase 1 added:

* consultation specialty profile schema
* specialty sections
* specialty templates
* specialty entries
* doctor consultation preferences
* default specialty profiles: general medicine, physiotherapy, ophthalmology, dental
* EN/FR localisation
* focused foundation tests

Now implement:

# Consultation Specialist Extension — Phase 2: Specialty Resolver

## Phase 2 Goal

Build the resolver layer that determines which consultation specialty profile applies to a doctor/visit/consultation route/department context.

This phase must still avoid changing the visible consultation UI behavior.

The existing consultation page should continue to render exactly as before. This phase may pass resolved specialty context into the backend/view/Inertia payload for future use, but it must not reorder, hide, rename, or replace consultation sections yet.

The layout engine comes in Phase 3.

---

# Important Rules

Do not rewrite the consultation module.

Do not create separate Physio Consultation, Eye Consultation, Dental Consultation modules.

Do not change the visible consultation UI in this phase.

Do not run the full test suite yet.

Only run focused checks for migrations, resolver tests, route/view compilation if touched, localisation if touched, and linting of changed PHP files.

Keep all changes backward compatible.

The default fallback must always be `general_medicine`.

If anything is ambiguous, prefer safe fallback to general medicine instead of throwing errors in the consultation flow.

---

# Required Deliverables

## 1. Inspect Existing Consultation Context

Before coding the resolver, inspect the existing project structure for:

```text
Consultation model/table
Visit model/table
Consultation route/session model/table
Department model/table
User department assignment / department_user pivot
Current consultation controller(s)
Current consultation workspace route(s)
Current active department/dashboard context service, if any
DoctorConsultationPreference model from Phase 1
ConsultationSpecialtyProfile model from Phase 1
ConsultationSpecialtySection model from Phase 1
```

Use the actual existing model names and table names.

Do not guess model names if the project already has equivalents.

---

## 2. Add Specialty Context Mapping Support

The resolver needs a clean way to map departments/routes/types to specialty profiles.

Create a migration and model for:

```text
consultation_specialty_profile_mappings
```

Suggested fields:

```text
id
consultation_specialty_profile_id foreign key cascade delete
department_id nullable foreign key if departments table exists
consultation_route_id nullable foreign key if a consultation route/session table exists
department_type nullable string
user_id nullable foreign key users if useful, but prefer doctor_consultation_preferences for user defaults
source nullable string
priority unsigned integer default 0
is_active boolean default true
metadata nullable json
created_at
updated_at
```

Suggested indexes:

```text
index consultation_specialty_profile_id
index department_id
index consultation_route_id
index department_type
index is_active
index priority
```

Notes:

* If the project has a different route/session table name, adapt safely.
* If adding a direct foreign key to consultation route is risky because table names vary, use nullable unsignedBigInteger plus indexed column and document why.
* Do not make this table too rigid.
* This table is for resolver hints, not billing. Billing mapping will come later.

Create model:

```text
app/Models/ConsultationSpecialtyProfileMapping.php
```

Relationships:

```php
profile()
department()
consultationRoute()
user()
```

Scopes:

```php
active()
ordered()
forDepartment($department)
forConsultationRoute($route)
forDepartmentType(?string $departmentType)
```

If exact relationships cannot be safely typed because table/model naming varies, keep the model defensive and document the adaptation.

---

## 3. Extend the Specialty Seeder Safely

Update the existing `ConsultationSpecialtySeeder` to seed basic resolver mappings idempotently.

Minimum mapping:

```text
department_type = consultation → general_medicine
```

Optional safe mappings:

Only if matching departments already exist by code/name/type, map:

```text
physiotherapy / physio → physiotherapy
ophthalmology / eye → ophthalmology
dental → dental
```

Important:

* Do not create fake departments in this phase.
* Do not alter existing departments unexpectedly.
* Use `updateOrCreate`.
* These are configuration mappings only.

---

## 4. Add Specialty Resolver DTO / Value Object

Create a lightweight value object:

```text
app/Data/Consultation/Specialty/ResolvedConsultationSpecialty.php
```

Or follow the project’s existing DTO/data namespace convention.

It should contain:

```php
public ConsultationSpecialtyProfile $profile;
public ?string $source;
public ?string $reason;
public ?object $department;
public ?object $consultationRoute;
public array $sections;
public bool $isFallback;
```

If the project does not use DTO classes, create a simple immutable class.

It should have:

```php
toArray(): array
```

The array should be safe for controller/view/Inertia payloads:

```php
[
    'profile' => [
        'id' => ...,
        'code' => ...,
        'name' => ...,
        'translated_name' => ...,
        'icon' => ...,
        'color' => ...,
    ],
    'source' => ...,
    'reason' => ...,
    'is_fallback' => ...,
    'sections' => [...]
]
```

Sections should include:

```php
[
    'key' => ...,
    'label' => ...,
    'translated_label' => ...,
    'component' => ...,
    'display_order' => ...,
    'is_required' => ...,
    'is_visible' => ...,
    'config' => ...
]
```

Do not expose sensitive user data.

---

## 5. Create the Resolver Service

Create:

```text
app/Services/Consultation/Specialty/ConsultationSpecialtyProfileResolver.php
```

Resolver method:

```php
public function resolve(
    User $user,
    mixed $visit = null,
    mixed $consultation = null,
    mixed $consultationRoute = null,
    mixed $department = null,
    array $options = []
): ResolvedConsultationSpecialty
```

Use exact type hints where safe. Use `mixed` only if existing model names vary or circular dependencies make strict typing risky.

## Resolution Priority

Resolve in this order:

### 1. Consultation route mapping

If an active mapping exists for the current consultation route/session, use that profile.

Source:

```text
consultation_route_mapping
```

### 2. Explicit department mapping

If an active mapping exists for the active department, use that profile.

Source:

```text
department_mapping
```

### 3. Active department type mapping

If the active department has a type, and an active mapping exists for that department type, use that profile.

Source:

```text
department_type_mapping
```

### 4. Doctor/user saved preference

Use `doctor_consultation_preferences.default_consultation_specialty_profile_id` if:

```text
profile exists
profile is active
```

Source:

```text
doctor_preference
```

### 5. User primary department / assigned department

If the user has a primary/active department relation and that department maps to a profile, use that profile.

Source:

```text
user_department_mapping
```

### 6. Existing consultation specialty entry

If the current consultation already has a specialty entry with an active profile, use that profile.

Source:

```text
existing_consultation_entry
```

This protects old specialist entries from changing context unexpectedly.

### 7. Fallback

Return `general_medicine`.

Source:

```text
fallback
```

## Resolver Rules

* Inactive profiles must never be selected.
* Inactive mappings must be ignored.
* Missing mappings must not throw.
* Missing department/route context must not throw.
* Invalid doctor preference must fall back safely.
* Always return visible ordered sections for the selected profile.
* If selected profile has no visible sections, fall back to `general_medicine`.

---

## 6. Add Helper Methods to Existing Phase 1 Service

Extend:

```text
app/Services/Consultation/Specialty/ConsultationSpecialtyProfileService.php
```

Add or refine:

```php
getDefaultProfile(): ConsultationSpecialtyProfile
getActiveProfileById(?int $id): ?ConsultationSpecialtyProfile
getActiveProfileByCode(?string $code): ?ConsultationSpecialtyProfile
getVisibleOrderedSections(ConsultationSpecialtyProfile $profile): Collection
fallbackResolvedContext(...): ResolvedConsultationSpecialty
```

Keep backward compatibility with Phase 1 tests.

---

## 7. Add Read-Only Controller Integration

Find the main consultation workspace controller/action.

Add resolver call and pass read-only specialty context to the response payload.

Example:

```php
$specialtyContext = $resolver->resolve(
    user: $request->user(),
    visit: $visit ?? null,
    consultation: $consultation ?? null,
    consultationRoute: $consultationRoute ?? null,
    department: $activeDepartment ?? null,
);
```

Add to payload as:

```php
'specialtyContext' => $specialtyContext->toArray()
```

or project naming convention equivalent.

Important:

* Do not make the UI consume it yet.
* Do not change section rendering.
* Do not change save behavior.
* Do not change completion readiness.
* Do not change prescription/investigation/procedure behavior.
* The page should look the same after this phase.

If controller integration is too risky because the consultation controller is complex, create a dedicated small provider/service and only add focused integration where safe. Document exactly what was done.

---

## 8. Add Focused Tests

Create:

```text
tests/Feature/Consultations/ConsultationSpecialtyResolverTest.php
```

Test cases:

### Resolver fallback

When no department, route, preference, or mapping exists:

```text
resolver returns general_medicine
is_fallback = true
source = fallback
```

### Department type mapping

When active department type maps to a profile:

```text
resolver returns mapped profile
source = department_type_mapping
```

### Department mapping beats department type mapping

When department maps to dental and department type maps to general:

```text
resolver returns dental
source = department_mapping
```

### Route mapping beats department mapping

When route maps to ophthalmology and department maps to general:

```text
resolver returns ophthalmology
source = consultation_route_mapping
```

### Doctor preference works

When no route/department mapping exists and doctor has preference physiotherapy:

```text
resolver returns physiotherapy
source = doctor_preference
```

### Inactive profile ignored

When mapping points to inactive profile:

```text
resolver falls back to next valid source or general
```

### Inactive mapping ignored

When mapping is inactive:

```text
resolver ignores it
```

### Sections returned

Resolved context includes visible ordered sections.

### Controller payload smoke

Where feasible, test that the consultation workspace response includes `specialtyContext` without changing the rendered page behavior.

If existing consultation fixture setup is heavy, keep the controller payload smoke minimal and document if deferred.

---

## 9. Localisation

If new source/reason labels are shown anywhere, add EN/FR keys.

If source/reason is only internal and not displayed, localisation is not required.

Do not add UI text unless necessary.

---

## 10. Minimal Checks to Run

Run only focused checks:

```bash
php artisan migrate
php artisan db:seed --class=ConsultationSpecialtySeeder
php artisan test tests/Feature/Consultations/ConsultationSpecialtyFoundationTest.php
php artisan test tests/Feature/Consultations/ConsultationSpecialtyResolverTest.php
php artisan route:list
```

Also run PHP lint on new/modified PHP files.

If the project has a localisation lock/parity command and translations were changed, run that focused command too.

Do not run the wide full-suite yet.

---

## 11. Phase Report

Create:

```text
docs/consultation/CONSULTATION_SPECIALIST_EXTENSION_PHASE_2_RESOLVER_REPORT.md
```

If the repo convention currently places consultation docs directly under `docs/`, use:

```text
docs/CONSULTATION_SPECIALIST_EXTENSION_PHASE_2_RESOLVER_REPORT.md
```

The report must include:

```text
# Consultation Specialist Extension — Phase 2 Resolver Report

## Summary
Explain what was implemented.

## Files Added
List all new files.

## Files Modified
List all modified files.

## Database Changes
List migration(s), table(s), and mapping fields.

## Resolver Priority
Document the final implemented resolver priority.

## Seeded Mappings
List any mappings seeded.

## Controller Integration
Explain whether `specialtyContext` was added to consultation payload and where.

## Tests Added
List resolver tests.

## Checks Run
Include commands and pass/fail summary.

## Backward Compatibility
Confirm the visible consultation UI was not changed.

## Known Issues / Follow-up
List anything to handle in Phase 3.
```

---

# Acceptance Criteria

Phase 2 is complete only when:

* `ConsultationSpecialtyProfileResolver` exists.
* Resolver always returns a valid active specialty profile.
* Resolver falls back safely to `general_medicine`.
* Resolver respects priority:

  1. route mapping
  2. department mapping
  3. department type mapping
  4. doctor preference
  5. user department mapping
  6. existing consultation entry
  7. fallback
* Resolved context includes visible ordered sections.
* Basic mapping support exists.
* Default mapping for consultation department type exists.
* Focused resolver tests pass.
* Phase 1 foundation tests still pass.
* Existing consultation UI behavior remains visually unchanged.
* Phase 2 report is created.

Stop after Phase 2. Do not start the layout engine yet.
