You are working on the UHMS Laravel codebase.

We have completed:

* Phase 1: Specialist Consultation Profile Foundation
* Phase 2: Specialty Resolver
* Phase 3: Specialty Layout Engine
* Phase 4: Structured Specialist Forms

Phase 4 added schema-driven structured forms for physiotherapy, ophthalmology, and dental using `consultation_specialty_entries`.

Now implement:

# Consultation Specialist Extension — Phase 5: Specialty Favorites and Smart Defaults

## Phase 5 Goal

Make the consultation workspace feel faster and more specialty-aware by showing relevant diagnoses, investigations, procedures, prescriptions, frequencies, tasks, and follow-up instructions first for each specialty.

This phase must add **smart defaults and specialty favorites** without removing global search behavior.

Example:

* Eye clinic should see eye-related diagnoses, investigations, procedures, and eye medications first.
* Dental should see dental diagnoses, dental X-rays, dental procedures, analgesics, antibiotics, and consent-related defaults first.
* Physiotherapy should see rehab problems, therapy procedures, session frequencies, exercise tasks, and progress defaults first.
* General medicine should continue behaving as it does now.

The doctor must still be able to search and select any global diagnosis, service, investigation, procedure, or drug.

---

# Important Rules

Do not rewrite the consultation module.

Do not create separate consultation modules for physiotherapy, ophthalmology, or dental.

Do not remove existing search/dropdown behavior.

Do not hide global records from doctors.

Do not implement order sets yet. That comes in Phase 6.

Do not implement specialty completion readiness yet. That comes later.

Do not implement specialty summary builder yet. That comes later.

Do not implement billing/service mapping yet. That comes later.

Do not run the full test suite yet.

Only run focused checks for this phase.

If specialty favorites fail to load, the consultation workspace must continue using the existing global options.

---

# Current Context

Phase 4 report confirms:

* Specialist entries are stored in `consultation_specialty_entries`.
* Specialist forms are schema-driven.
* The workspace still uses the shared consultation view.
* Core consultation forms, IDs, routes, Ajax attributes, route context, and save behavior remain stable.
* Existing save pattern uses `data-ajax-form`, `data-consultation-form`, `data-refresh-section`, and `data-route-context-required`.
* Clinical mutation routes use the existing consultation guard and response conventions.

Phase 5 should integrate with this safely.

---

# Required Deliverables

## 1. Inspect Existing Option Sources

Before coding, inspect how the current consultation workspace loads or searches:

```text id="h9ujn1"
ICD-10 diagnosis options/search
investigation/service options
procedure/service options
drug/prescription options
frequency options
task options/frequencies
follow-up/instruction options if any
```

Identify:

```text id="m62rrs"
models
tables
controllers
search endpoints
Blade variables
JavaScript dropdown/search behavior
Select2/TomSelect/native select usage if any
current seed data sources
current permissions/guards
```

Do not guess. Follow the actual project conventions.

Document findings in the phase report.

---

## 2. Add Specialty Favorite Model and Table

Create migration and model:

```text id="seyqqf"
consultation_specialty_favorites
```

Model:

```text id="6nj8zs"
app/Models/ConsultationSpecialtyFavorite.php
```

Suggested table fields:

```text id="8uit4s"
id
consultation_specialty_profile_id foreign key cascade delete
favorite_type string
favoritable_type nullable string
favoritable_id nullable unsignedBigInteger
code nullable string
label string
description nullable text
search_terms nullable text
metadata nullable json
sort_order unsigned integer default 0
is_active boolean default true
created_at
updated_at
```

Indexes:

```text id="4v950j"
index consultation_specialty_profile_id
index favorite_type
index favoritable_type + favoritable_id
index is_active
index sort_order
unique profile + favorite_type + code nullable if safe
```

Important:

* `favoritable_type` / `favoritable_id` should support linking to existing models like services, drugs, ICD codes, procedures, etc.
* `code` and `label` allow fallback entries where a linked model does not exist yet.
* Do not require all favorites to be linked to existing records.
* This table should be configuration data, not patient data.

Suggested favorite types:

```text id="slibsl"
diagnosis
investigation
procedure
drug
frequency
task
follow_up_instruction
clinical_instruction
```

Relationships:

```php id="mgihcz"
profile()
favoritable()
```

Scopes:

```php id="f3zm6j"
active()
ordered()
ofType(string $type)
forProfile(ConsultationSpecialtyProfile $profile)
```

---

## 3. Add Specialty Favorite Service

Create:

```text id="1qmda0"
app/Services/Consultation/Specialty/ConsultationSpecialtyFavoriteService.php
```

Responsibilities:

```php id="n3g7u5"
getFavoritesForProfile(ConsultationSpecialtyProfile $profile, string $type): Collection;

getFavoritesForResolvedContext(array|ResolvedConsultationSpecialty $context, string $type): Collection;

mergeFavoritesWithGlobalOptions(
    Collection|array $favorites,
    Collection|array $globalOptions,
    string $type,
    array $options = []
): array;

getWorkspaceDefaults(ConsultationSpecialtyProfile $profile): array;
```

Rules:

* Favorites appear first.
* Global options still remain available.
* Duplicates are removed safely.
* Matching should deduplicate by linked model identity where possible, then by normalized label/code.
* Inactive favorites are ignored.
* Inactive profiles are ignored.
* If no favorites exist, return the current global options unchanged.
* Service must be defensive and not break if a linked model was deleted.

---

## 4. Seed Starter Favorites

Update `ConsultationSpecialtySeeder` or create a dedicated seeder:

```text id="ssstuy"
ConsultationSpecialtyFavoriteSeeder
```

If using a dedicated seeder, call it from `ConsultationSpecialtySeeder` or `DatabaseSeeder` only if it is safe configuration data.

Seed idempotently using `updateOrCreate`.

Do not create fake patients, visits, prescriptions, or clinical records.

---

# Starter Favorites to Seed

## Physiotherapy

### Diagnoses / Problems

```text id="ez9zx1"
Low back pain
Neck pain
Knee pain
Shoulder stiffness
Stroke rehabilitation
Post-fracture rehabilitation
Sports injury
Gait abnormality
Muscle weakness
Joint stiffness
```

### Procedures / Therapy Services

```text id="oy0wi8"
Physiotherapy assessment
Therapeutic exercise
Manual therapy
Gait training
Range of motion exercise
Strengthening exercise
Electrotherapy
Heat therapy
Post-operative rehabilitation
Home exercise instruction
```

### Frequencies

```text id="t03p9f"
Once daily
Twice weekly
Three times weekly
Weekly
Every 2 weeks
Review in 2 weeks
```

### Tasks

```text id="gvias0"
Perform therapy session
Review pain score
Review range of motion
Review home exercise compliance
Schedule next physiotherapy session
Reassess functional goal
```

### Follow-up Instructions

```text id="kq812k"
Continue home exercises as instructed.
Avoid activities that worsen pain.
Return earlier if pain, weakness, or numbness worsens.
Apply heat or cold as advised.
Attend all scheduled therapy sessions.
```

---

## Ophthalmology

### Diagnoses

```text id="9f192d"
Conjunctivitis
Cataract
Glaucoma
Refractive error
Dry eye syndrome
Eye trauma
Corneal abrasion
Uveitis
Diabetic retinopathy
Foreign body in eye
```

### Investigations

```text id="i53lfy"
Visual acuity test
Refraction
Intraocular pressure measurement
Slit lamp examination
Fundus examination
OCT
Visual field test
Fundus photography
Eye ultrasound
Fluorescein staining
```

### Procedures

```text id="2h6fvw"
Foreign body removal
Eye dressing
Eye irrigation
Refraction procedure
Slit lamp examination
Fundus examination
Tonometry
```

### Drugs

```text id="cxvyr3"
Lubricating eye drops
Antibiotic eye drops
Anti-allergy eye drops
Steroid eye drops
Anti-glaucoma eye drops
Eye ointment
Oral analgesic
```

### Frequencies

```text id="n1ulb7"
Once daily
Twice daily
Three times daily
Four times daily
Every 4 hours
At night
As needed
Review in 3 days
Review in 1 week
```

### Follow-up Instructions

```text id="8tudng"
Avoid rubbing the eye.
Return immediately if vision worsens.
Return immediately if severe pain develops.
Use eye drops as prescribed.
Avoid sharing towels or eye cosmetics.
Attend follow-up for eye pressure or vision review.
```

---

## Dental

### Diagnoses

```text id="n3h0f6"
Dental caries
Pulpitis
Periodontitis
Dental abscess
Gingivitis
Impacted tooth
Tooth fracture
Pericoronitis
Oral ulcer
Malocclusion
```

### Investigations

```text id="xlh57x"
Periapical X-ray
Panoramic X-ray
Bitewing X-ray
Dental vitality test
Dental examination
```

### Procedures

```text id="zj9vg7"
Tooth extraction
Dental filling
Scaling and polishing
Root canal treatment
Dental dressing
Incision and drainage
Dental review
Oral hygiene instruction
```

### Drugs

```text id="cxb22p"
Oral analgesic
Antibiotic
Antiseptic mouthwash
Anti-inflammatory medicine
Local anaesthetic
```

### Frequencies

```text id="74h5so"
Once daily
Twice daily
Three times daily
Every 8 hours
Every 12 hours
After meals
As needed
Review in 3 days
Review in 1 week
```

### Follow-up Instructions

```text id="7wh6un"
Do not rinse mouth vigorously for 24 hours after extraction.
Bite on gauze as instructed.
Avoid hot food and drinks after extraction.
Return if bleeding persists.
Return if swelling or fever develops.
Maintain oral hygiene as advised.
```

---

## General Medicine

Keep this light.

Do not overload general medicine.

Seed only if needed for the new service tests:

```text id="zl3xp6"
Review in 1 week
Review in 2 weeks
As needed
Once daily
Twice daily
Three times daily
```

General medicine must still rely primarily on existing global data.

---

## 5. Add Favorite Payload to Consultation Workspace

Update the consultation workspace controller/provider to pass:

```text id="mqeezv"
specialtyFavorites
```

as a normalized array grouped by type.

Example:

```php id="m8j1a6"
[
    'diagnosis' => [...],
    'investigation' => [...],
    'procedure' => [...],
    'drug' => [...],
    'frequency' => [...],
    'task' => [...],
    'follow_up_instruction' => [...],
]
```

Each item should include:

```php id="i7ehhj"
[
    'id' => ...,
    'type' => ...,
    'label' => ...,
    'code' => ...,
    'favoritable_type' => ...,
    'favoritable_id' => ...,
    'metadata' => ...,
]
```

Do not expose unnecessary internal data.

---

## 6. Integrate Favorites Into Existing Dropdown/Search UI

Carefully integrate into existing consultation controls.

Priority areas:

```text id="0r64n2"
Diagnosis ICD-10 search/dropdown
Investigation multi-select/search
Procedure service dropdown/search
Prescription drug dropdown/search
Prescription frequency dropdown
Task frequency/options
Follow-up/instruction helpers where present
```

Rules:

* Favorites must appear first.
* Global search must still work.
* Existing endpoint/API search behavior must not break.
* If the control is server-rendered, prepend favorites to the existing option list.
* If the control is Ajax/search-based, expose favorites as:

  * initial suggestions, or
  * preferred results when query is empty/short, or
  * tagged “Specialty favorite” group
* Do not force-select a favorite.
* Do not silently submit favorite text as a linked model unless the existing flow supports free text.
* If a favorite is not linked to an existing model, it can act as a suggestion/free-text helper only.
* Prefer opt-in selection by the doctor.

Suggested UI grouping:

```text id="7tg0oz"
Specialty favorites
All options
```

If grouping is too risky, simply sort favorites first and add a small badge.

---

## 7. Add Specialty Favorite Badges/Labels

Where favorites appear in UI, add subtle labels:

```text id="bbg4c8"
Specialty
Favorite
Common for Eye Clinic
Common for Dental
Common for Physio
```

Keep it clean. Do not overcrowd the consultation screen.

For general medicine, avoid excessive badges.

---

## 8. Add Smart Frequency Defaults

Prescription frequency dropdown and task frequency dropdown should include specialty-relevant defaults first.

This is especially important for:

```text id="gb3k5y"
eye drops
physio sessions
dental antibiotics/analgesics
```

Rules:

* Keep all standard frequencies available.
* Do not remove existing frequencies.
* Add missing standard frequencies if the project currently lacks them.
* Dedupe frequency labels.
* Do not change existing stored frequency format unless already standardized.

Recommended standard frequencies:

```text id="m7qt6g"
Once daily
Twice daily
Three times daily
Four times daily
Every 4 hours
Every 6 hours
Every 8 hours
Every 12 hours
At night
After meals
Before meals
As needed
Once weekly
Twice weekly
Three times weekly
Review in 3 days
Review in 1 week
Review in 2 weeks
```

---

## 9. Optional: Small “Apply to Field” Helpers

For follow-up instructions and clinical instructions, if the existing UI has textareas, add small clickable chips that insert text into the relevant textarea.

Example:

```text id="oibsch"
[Return immediately if vision worsens]
[Use eye drops as prescribed]
```

Rules:

* Keep this optional and minimal.
* Do not disrupt existing notes/summary separation.
* Do not auto-submit.
* No order set behavior yet.

---

## 10. Localisation

Add EN/FR keys for new UI labels.

Suggested keys:

```text id="4ukw77"
favorites.title
favorites.specialty_favorites
favorites.all_options
favorites.badge
favorites.common_for_specialty
favorites.no_favorites
favorites.insert_instruction
favorites.smart_defaults
favorites.frequency_defaults
messages.favorite_loaded
```

Use the existing `consultation_specialties.php` files if appropriate.

Do not leave new visible UI text untranslated.

---

## 11. Add Focused Tests

Create:

```text id="itcg0r"
tests/Feature/Consultations/ConsultationSpecialtyFavoriteTest.php
```

Suggested tests:

### Seeder creates favorites

* Physiotherapy, ophthalmology, and dental profiles have seeded favorites.

### Favorites are grouped by type

* Service returns favorites grouped by diagnosis, investigation, procedure, drug, frequency, task, follow-up instruction.

### Favorites are ordered

* Favorites respect `sort_order`.

### Inactive favorites ignored

* Inactive favorite is not returned.

### Deleted linked model does not break

* Favorite linked to a missing model is skipped or safely returned as label-only depending on design.

### Favorites merge before global options

* Specialty favorites appear before global options.
* Duplicates are removed.

### Workspace payload includes favorites

* Consultation workspace payload/view contains `specialtyFavorites`.

### General medicine stays light

* General medicine does not get physio/eye/dental favorites.

### Ophthalmology favorites appear

* Eye profile includes visual acuity/refraction/IOP/fundus-related favorites.

### Dental favorites appear

* Dental profile includes dental X-ray/procedure/instruction favorites.

### Physiotherapy favorites appear

* Physio profile includes therapy/task/frequency favorites.

### Frequency defaults include standard frequencies

* Prescription/task frequency defaults include the recommended standard frequencies without duplicates.

---

## 12. Optional UI/Workspace Smoke Tests

If existing consultation workspace tests are stable, add a focused smoke test to confirm:

```text id="l1hn1k"
Eye consultation shows ophthalmology favorites.
Dental consultation shows dental favorites.
Physio consultation shows physio favorites.
General consultation still shows general behavior.
```

Do not create a heavy browser suite in this phase.

---

## 13. Minimal Checks to Run

Run:

```bash id="85f3cz"
php artisan migrate
php artisan db:seed --class=ConsultationSpecialtySeeder
php artisan test tests/Feature/Consultations/ConsultationSpecialtyFoundationTest.php
php artisan test tests/Feature/Consultations/ConsultationSpecialtyResolverTest.php
php artisan test tests/Feature/Consultations/ConsultationSpecialtyLayoutTest.php
php artisan test tests/Feature/Consultations/ConsultationSpecialtyEntryTest.php
php artisan test tests/Feature/Consultations/ConsultationSpecialtyFavoriteTest.php
php artisan test tests/Feature/ConsultationWorkspaceStabilisationTest.php
php artisan route:list
php artisan view:cache
php artisan view:clear
```

Also run PHP lint on new/modified PHP files.

If localisation keys were added and the project has a localisation parity/lock command, run it.

Do not run the wide full-suite yet.

---

## 14. Phase Report

Create:

```text id="ca8f5y"
docs/CONSULTATION_SPECIALIST_EXTENSION_PHASE_5_FAVORITES_SMART_DEFAULTS_REPORT.md
```

If the repo convention has a consultation docs subfolder, use:

```text id="qqdn5e"
docs/consultation/CONSULTATION_SPECIALIST_EXTENSION_PHASE_5_FAVORITES_SMART_DEFAULTS_REPORT.md
```

The report must include:

```text id="d67rb4"
# Consultation Specialist Extension — Phase 5 Favorites and Smart Defaults Report

## Summary
Explain what was implemented.

## Existing Option Source Findings
Document how diagnoses, investigations, procedures, drugs, frequencies, tasks, and instructions are currently loaded.

## Files Added
List all new files.

## Files Modified
List all modified files.

## Database Changes
List migration(s), table(s), and fields.

## Specialty Favorite Design
Explain `consultation_specialty_favorites`, linked-model support, label-only support, and deduping.

## Seeded Favorites
List seeded favorites by specialty and type.

## Workspace Integration
Explain how `specialtyFavorites` is passed to the consultation workspace.

## UI Changes
Explain where favorites/smart defaults appear and how global search remains available.

## Frequency Defaults
List standard frequencies added or exposed.

## Backward Compatibility
Confirm general consultation behavior and existing dropdown/search behavior remain stable.

## Tests Added
List focused tests.

## Checks Run
Include commands and pass/fail summary.

## Known Issues / Follow-up
List anything for:
- Phase 6 order sets
- Phase 7 completion readiness
- Phase 8 summary builder
- later admin configuration UI
```

---

# Acceptance Criteria

Phase 5 is complete only when:

* `consultation_specialty_favorites` table exists.
* `ConsultationSpecialtyFavorite` model exists.
* `ConsultationSpecialtyFavoriteService` exists.
* Starter favorites are seeded idempotently for physiotherapy, ophthalmology, and dental.
* Favorites are grouped by type and passed to the consultation workspace.
* Favorites appear first where safely integrated.
* Global search/options remain available.
* Standard frequency defaults are available without duplicates.
* General medicine behavior remains stable and not overloaded.
* Inactive favorites are ignored.
* Missing linked models do not break the workspace.
* Focused favorite tests pass.
* Phase 1, 2, 3, and 4 tests still pass.
* Workspace stabilisation test still passes.
* View cache/build check passes.
* Phase 5 report is created.

Stop after Phase 5. Do not implement order sets, completion readiness, summary builder, or billing mapping yet.
