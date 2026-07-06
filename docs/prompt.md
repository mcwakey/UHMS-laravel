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
* Phase 10: Admin Configuration UI

Phase 10 added admin-facing configuration screens for consultation specialty profiles, sections, resolver mappings, favorites, order sets, order set items, and doctor preference visibility/reset.

Now implement:

# Consultation Specialist Extension — Phase 11: Specialty Billing and Service Mapping

## Phase 11 Goal

Add safe specialty billing/service mapping so each specialist consultation can be linked to the correct hospital service catalogue item.

Examples:

```text
General Medicine → General OPD Consultation Service
Physiotherapy → Physiotherapy Assessment Service
Ophthalmology → Eye Clinic Consultation Service
Dental → Dental Consultation Service
```

This phase should support:

```text
default consultation service per specialty
optional department-specific override
optional route-specific override
procedure/service suggestions per specialty
therapy-session service suggestions
dental procedure service suggestions
eye procedure/investigation service suggestions
billing readiness warnings
admin configuration
audit logging
safe fallback to existing billing behavior
```

This phase must **not** recklessly auto-bill. The system should only auto-create charges when it can reuse an existing guarded billing workflow safely and when the mapping explicitly allows it.

---

# Important Rules

Do not rewrite the billing module.

Do not rewrite the consultation module.

Do not create separate billing flows for physio, eye, or dental.

Do not create invoice items from label-only favorites/order-set suggestions.

Do not silently bill services unless:

```text
the mapping is active
the mapped service exists and is active
auto_bill is enabled
existing billing workflow supports safe programmatic billing
no duplicate invoice/charge already exists
the user/action context is authorized
```

If any of those conditions are not met, show billing suggestion/warning only.

Do not remove existing consultation billing behavior.

Do not change NHIS/insurance/sponsor pricing behavior unless the existing service pricing layer already handles it.

Do not implement dashboard integration yet.

Do not run the full test suite yet.

Only run focused checks for this phase.

If specialty billing mapping fails to resolve, the existing billing flow must continue normally.

---

# Current Context

Existing specialist consultation system now includes:

```text
specialty profiles
sections
resolver mappings
structured entries
favorites
order sets
readiness
summary builder
doctor workspace
admin configuration UI
```

The next missing production piece is service/billing awareness.

Phase 11 should connect the specialist consultation profile to the existing service catalogue and finance/billing workflow safely.

---

# Required Deliverables

## 1. Inspect Existing Billing and Service Flow

Before coding, inspect the actual project code for:

```text
service catalogue model/table
department services
consultation service assignment
visit service selection
consultation route billing
invoice creation
invoice item creation
cash/insurance/NHIS pricing
sponsor pricing
payment status
duplicate invoice prevention
procedure request billing
investigation request billing
pharmacy billing
activity/audit logging
permissions/middleware
admin service configuration screens
```

Identify:

```text
models
services
controllers
routes
relationships
existing billing helper methods
existing invoice-item uniqueness rules
how visit/consultation services are selected
how service price is resolved
how insurance/sponsor price is resolved
how discounts/write-offs/credit notes are separated from service billing
```

Do not guess model names.

Do not invent new invoice logic if reusable services already exist.

Document findings in the phase report.

---

## 2. Add Specialty Service Mapping Table

Create migration:

```text
consultation_specialty_service_mappings
```

Model:

```text
app/Models/ConsultationSpecialtyServiceMapping.php
```

Suggested fields:

```text
id
consultation_specialty_profile_id foreign key cascade delete
service_id foreign key nullable
department_id nullable foreign key if departments table exists
consultation_route_id nullable unsignedBigInteger indexed if route FK is risky
department_type nullable string
section_key nullable string
mapping_context string
billing_trigger string default 'manual'
priority unsigned integer default 0
is_default boolean default false
auto_bill boolean default false
requires_confirmation boolean default true
is_active boolean default true
metadata nullable json
created_at
updated_at
```

Suggested `mapping_context` values:

```text
consultation
specialist_assessment
investigation
procedure
therapy_session
dental_procedure
eye_procedure
follow_up
consent_related
```

Suggested `billing_trigger` values:

```text
manual
on_visit_create
on_route_open
on_consultation_start
on_order_set_apply
on_specialty_entry_save
on_completion
```

Important:

* Keep `auto_bill` default false.
* Keep `requires_confirmation` default true.
* Use existing service catalogue FK if safe.
* If service table/model name differs, adapt to actual codebase.
* If route table FK is uncertain, use nullable unsigned integer and document.

Relationships:

```php
profile()
service()
department()
```

Scopes:

```php
active()
ordered()
defaults()
forProfile(ConsultationSpecialtyProfile $profile)
forContext(string $context)
forDepartment($department)
forDepartmentType(?string $departmentType)
```

---

## 3. Add Billing Mapping Service

Create:

```text
app/Services/Consultation/Specialty/ConsultationSpecialtyBillingMappingService.php
```

Responsibilities:

```php
resolveDefaultConsultationService(
    ConsultationSpecialtyProfile $profile,
    mixed $department = null,
    mixed $consultationRoute = null,
    array $options = []
): ?ConsultationSpecialtyServiceMapping;

resolveMappingsForContext(
    ConsultationSpecialtyProfile $profile,
    string $context,
    mixed $department = null,
    mixed $consultationRoute = null
): Collection;

getWorkspaceBillingContext(
    $consultation,
    ResolvedConsultationSpecialty|array $specialtyContext,
    array $workspacePayload = []
): array;

canAutoBill(
    $consultation,
    ConsultationSpecialtyServiceMapping $mapping,
    User $user,
    array $options = []
): array;

suggestBillableServices(
    ConsultationSpecialtyProfile $profile,
    string $context,
    array $options = []
): array;
```

Resolution priority:

```text
1. active route-specific mapping
2. active department-specific mapping
3. active department-type mapping
4. profile default mapping for context
5. fallback to existing/general consultation billing behavior
```

Rules:

* Inactive mappings ignored.
* Inactive/deleted services ignored.
* Higher priority wins.
* If same priority, default mapping wins.
* If nothing resolves, return null and do not break workspace.
* Do not create charges from this service by default.

---

## 4. Add Safe Billing Application Service

Create:

```text
app/Services/Consultation/Specialty/ConsultationSpecialtyBillingApplicationService.php
```

Responsibilities:

```php
previewBilling(
    $consultation,
    ConsultationSpecialtyServiceMapping $mapping,
    User $user,
    array $options = []
): array;

applyBilling(
    $consultation,
    ConsultationSpecialtyServiceMapping $mapping,
    User $user,
    array $options = []
): array;
```

Preview should show:

```php
[
    'mapping_id' => ...,
    'service' => [
        'id' => ...,
        'name' => ...,
        'code' => ...,
        'price' => ...,
    ],
    'context' => ...,
    'can_bill' => true/false,
    'requires_confirmation' => true/false,
    'already_billed' => true/false,
    'warnings' => [...],
]
```

Apply behavior:

* Use existing billing/invoice service only.
* Use transaction.
* Respect authorization.
* Respect price resolution for cash/insurance/sponsor.
* Prevent duplicate invoice items.
* Record audit log.
* Return structured result.

If no safe existing billing service can be reused, implement preview/suggestion only and document that auto-application is deferred.

Do not build parallel invoice logic.

---

## 5. Add Billing Audit Table If Needed

If existing invoice/audit/activity logging already tracks this clearly, do not add another table.

If not, add:

```text
consultation_specialty_billing_applications
```

Fields:

```text
id
consultation_id foreign key
consultation_specialty_profile_id nullable foreign key
consultation_specialty_service_mapping_id nullable foreign key
service_id nullable foreign key
invoice_id nullable unsignedBigInteger
invoice_item_id nullable unsignedBigInteger
applied_by foreign key users
status string
trigger string nullable
preview_payload nullable json
applied_payload nullable json
warnings nullable json
metadata nullable json
created_at
updated_at
```

Statuses:

```text
previewed
applied
suggested
skipped_duplicate
failed
unsupported
```

Only add this table if it provides value and does not duplicate existing audit.

Document the decision.

---

## 6. Seed Starter Service Mappings Safely

Create seeder:

```text
database/seeders/ConsultationSpecialtyServiceMappingSeeder.php
```

Call it from `ConsultationSpecialtySeeder` if safe.

Important:

* Do not create fake services unless the project already has a safe setup pattern for service catalogue seeders.
* Prefer mapping to existing services by code/name/type if present.
* If matching services do not exist, skip mapping and report as “not found”.
* Do not fail seeding because a service does not exist.

Starter mapping hints:

```text
general_medicine:
- General OPD Consultation
- General Consultation
- OPD Consultation

physiotherapy:
- Physiotherapy Assessment
- Physiotherapy Consultation
- Physiotherapy Session

ophthalmology:
- Ophthalmology Consultation
- Eye Clinic Consultation
- Eye Consultation

dental:
- Dental Consultation
- Dental Assessment
```

Procedure/service suggestions:

```text
physiotherapy:
- Therapeutic exercise
- Manual therapy
- Gait training
- Electrotherapy

ophthalmology:
- Visual acuity test
- Refraction
- Tonometry
- Slit lamp examination
- Fundus examination

dental:
- Tooth extraction
- Dental filling
- Scaling and polishing
- Root canal treatment
- Dental X-ray
```

Use `updateOrCreate`.

All seeded mappings should default to:

```text
auto_bill = false
requires_confirmation = true
is_active = true
```

---

## 7. Add Admin UI for Service Mappings

Extend Phase 10 admin configuration UI.

Add screen:

```text
Admin / Clinical Setup / Consultation Specialties / Service Mappings
```

Controller:

```text
app/Http/Controllers/Admin/ConsultationSpecialtyServiceMappingController.php
```

Routes:

```text
GET    admin/consultation-specialties/service-mappings
POST   admin/consultation-specialties/service-mappings
PATCH  admin/consultation-specialties/service-mappings/{mapping}
DELETE admin/consultation-specialties/service-mappings/{mapping}
POST   admin/consultation-specialties/service-mappings/reorder
```

Fields:

```text
profile
service
department
department_type
consultation_route_id
section_key
mapping_context
billing_trigger
priority
is_default
auto_bill
requires_confirmation
is_active
metadata
```

Rules:

* Requires `consultation-specialties.configure`.
* Service must exist and be active if selected.
* At least profile + context + service are required for normal mapping.
* If auto_bill is enabled, require explicit confirmation checkbox in admin form.
* Show warning: “Auto-billing only runs where existing billing workflow supports safe application.”
* Prevent multiple active defaults for same profile/context/department scope unless priority differentiates them.
* Audit changes.

Index filters:

```text
profile
context
service
department
active status
auto_bill
```

---

## 8. Add Workspace Billing Context

Update the consultation workspace controller/provider to pass:

```text
specialtyBillingContext
```

Payload:

```php
[
    'default_service' => [...],
    'billable_suggestions' => [...],
    'auto_bill_enabled' => true/false,
    'requires_confirmation' => true/false,
    'already_billed' => true/false,
    'warnings' => [...],
    'preview_url' => ...,
    'apply_url' => ...,
]
```

Keep it lightweight.

Do not make heavy invoice queries unless necessary.

---

## 9. Add UI: Billing Mapping Awareness

Add a compact, non-intrusive billing card in the specialist workspace.

Suggested placement:

```text
near doctor workspace header
or right panel under readiness
or admin-only small indicator in consultation header
```

Behavior:

* Shows mapped consultation service if available.
* Shows “not mapped” warning to authorized users only.
* Shows already-billed status if safely detectable.
* Shows “Preview billing” button only if mapping exists.
* Shows “Apply charge” only if:

  * user has permission
  * mapping is active
  * service is active
  * not already billed
  * billing application service says it can bill
  * confirmation is shown

For normal doctors without billing permissions, show service context only or hide action buttons.

Important:

* Do not annoy doctors with finance warnings if they cannot act on them.
* Billing warnings should not block clinical completion yet unless explicitly configured later.
* Phase 11 can add readiness warning only, not blocking.

---

## 10. Add Controller and Routes for Billing Preview/Application

Create:

```text
app/Http/Controllers/Doctor/Consultations/ConsultationSpecialtyBillingController.php
```

Actions:

```php
preview(Request $request, Consultation $consultation)
apply(Request $request, Consultation $consultation)
```

Routes:

```text
GET/POST admin/consultations/{consultation}/specialty-billing/preview
POST    admin/consultations/{consultation}/specialty-billing/apply
```

Use actual consultation route style.

Rules:

* Must use existing consultation access/mutation guards.
* Must check billing permission if applying.
* Must resolve active specialty profile and mapping.
* Must reject inactive mappings/services.
* Must prevent duplicates.
* Must return JSON for Ajax.
* Must redirect back with flash if normal post.
* Must audit apply/failed/unsupported action.

If safe apply is not possible, preview endpoint still works and apply returns unsupported with a clear message.

---

## 11. Optional Readiness Warning

Extend specialty readiness result with a **warning-only** item when:

```text
specialty consultation has no mapped service
```

or:

```text
mapped service exists but charge is not detected
```

Only show this if it is useful and not noisy.

Rules:

* Do not block clinical completion.
* Do not affect general medicine unless current billing behavior requires it.
* Hide from doctors without finance/billing visibility if appropriate.

This can also be added as a workspace alert instead of readiness warning.

---

## 12. Localization

Add EN/FR keys for billing/service mapping.

Suggested keys:

```text
billing.title
billing.service_mapping
billing.default_service
billing.mapped_service
billing.no_mapped_service
billing.billable_suggestions
billing.preview_billing
billing.apply_charge
billing.already_billed
billing.not_billed
billing.auto_bill
billing.requires_confirmation
billing.confirm_apply_charge
billing.unsupported
billing.skipped_duplicate
billing.applied
billing.failed
billing.warning_no_mapping
billing.warning_not_charged
billing.visible_to_authorized_users
billing.admin.service_mappings
billing.admin.create_mapping
billing.admin.edit_mapping
billing.admin.context
billing.admin.trigger
billing.admin.priority
billing.admin.auto_bill_warning
billing.admin.active_default_conflict
```

No hardcoded visible text.

---

## 13. Add Focused Tests

Create:

```text
tests/Feature/Consultations/ConsultationSpecialtyBillingMappingTest.php
```

Suggested tests:

### Mapping model/service

* Admin/seeded mapping resolves for profile/context.
* Department-specific mapping beats profile default.
* Department type mapping works.
* Inactive mapping ignored.
* Inactive service ignored.
* Higher priority mapping wins.

### Seeder safety

* Seeder does not fail when service catalogue items are missing.
* Seeder maps existing matching services if present.

### Admin management

* User without permission cannot manage mappings.
* Authorized admin can create mapping.
* Invalid service rejected.
* Invalid context/trigger rejected.
* Auto-bill requires explicit confirmation.
* Duplicate active default conflict handled.
* Audit log written.

### Workspace payload

* Specialist workspace includes `specialtyBillingContext`.
* General medicine remains stable.
* No mapping does not break workspace.

### Billing preview

* Preview returns mapped service and warnings.
* Already-billed status is detected where safely possible.

### Billing apply

Only if safe application is implemented:

* Applies charge through existing billing workflow.
* Prevents duplicate charge.
* Logs application/audit.
* Unauthorized user cannot apply.

If safe application is not implemented:

* Apply returns unsupported safely.
* Preview still passes.

### Readiness/workspace warning

* Missing mapping warning is shown only where intended.
* Warning does not block completion.

### Existing regression

* Phase 1-10 focused tests still pass.
* Workspace stabilisation still passes.

---

## 14. Optional Browser Smoke Test

Only if existing browser fixtures are stable:

```text
Admin maps Eye Consultation service to Ophthalmology.
Open eye consultation.
Confirm mapped service appears.
Preview billing.
Confirm no duplicate billing action appears if already billed.
```

Do not create a heavy browser suite in this phase.

---

## 15. Minimal Checks to Run

Run:

```bash
php artisan migrate
php artisan db:seed --class=ConsultationSpecialtySeeder
php artisan test tests/Feature/Consultations/ConsultationSpecialtyBillingMappingTest.php
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

## 16. Phase Report

Create:

```text
docs/CONSULTATION_SPECIALIST_EXTENSION_PHASE_11_BILLING_SERVICE_MAPPING_REPORT.md
```

If the repo convention has a consultation docs subfolder, use:

```text
docs/consultation/CONSULTATION_SPECIALIST_EXTENSION_PHASE_11_BILLING_SERVICE_MAPPING_REPORT.md
```

The report must include:

```text
# Consultation Specialist Extension — Phase 11 Billing and Service Mapping Report

## Summary
Explain what was implemented.

## Existing Billing/Service Findings
Document service catalogue, consultation billing, invoice creation, price resolution, duplicate prevention, permissions, and audit patterns discovered.

## Files Added
List all new files.

## Files Modified
List all modified files.

## Database Changes
List migrations, tables, fields, and whether a billing application audit table was added.

## Mapping Design
Explain mapping contexts, triggers, priority resolution, defaults, auto-bill safety, and fallback behavior.

## Seeded Mappings
List mappings seeded or skipped due to missing services.

## Admin UI
Explain service mapping screens, validation, warnings, and audit logging.

## Workspace Integration
Explain `specialtyBillingContext`, billing card, preview/apply behavior, and visibility rules.

## Billing Application Behavior
Explain whether safe apply was implemented or preview-only/unsupported apply was chosen.
Explain duplicate prevention and audit behavior.

## Readiness/Alert Behavior
Explain any warning-only readiness/workspace alerts.

## Backward Compatibility
Confirm existing billing, consultation, specialist forms, favorites, order sets, readiness, summary builder, and doctor workspace remain stable.

## Tests Added
List focused billing mapping tests.

## Checks Run
Include commands and pass/fail summary.

## Known Issues / Follow-up
List anything for:
- Phase 12 dashboard/reporting integration
- Later full browser testing
- Later richer service/catalogue linking
- Later auto-bill expansion if safe
```

---

# Acceptance Criteria

Phase 11 is complete only when:

* `consultation_specialty_service_mappings` exists.
* `ConsultationSpecialtyServiceMapping` model exists.
* `ConsultationSpecialtyBillingMappingService` exists.
* Billing mapping resolution supports profile, department, department type, route/context, priority, active/default logic.
* Starter mappings are seeded safely without failing when services are missing.
* Admin can manage service mappings safely.
* Workspace receives `specialtyBillingContext`.
* Billing card/indicator appears where useful without disrupting clinical workflow.
* Billing preview works.
* Billing apply is either safely implemented through existing billing services or explicitly returns unsupported without breaking.
* Duplicate billing is prevented where apply is supported.
* General consultation billing behavior remains stable.
* Specialty clinical completion is not blocked by billing warnings.
* Focused billing mapping tests pass.
* Phase 1-10 focused tests still pass.
* Workspace stabilisation test still passes.
* View cache/build check passes.
* Phase 11 report is created.

Stop after Phase 11. Do not implement dashboard integration, broad browser hardening, or full-suite testing yet.
