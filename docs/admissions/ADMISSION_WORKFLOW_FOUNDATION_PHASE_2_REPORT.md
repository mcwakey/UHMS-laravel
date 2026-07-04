# Admission Workflow Foundation Phase 2 Report

## Implemented

- Fixed `AdmissionService::discharge()` so `PatientDischarged` is dispatched and admission discharge activity logging runs after the transaction.
- Added a first-class admission request lifecycle beside the existing direct admission flow.
- Added request states, source labels, relationships, service-layer transition rules, protected routes, simple request board/detail/create pages, and targeted tests.
- Wired Emergency admitted disposition to create an admission request while preserving the existing redirect into the New Admission form.
- Added optional request-to-admission conversion through the existing admission creation form so current billing preview and initial admission billing behavior remain the single conversion path.

## Files Changed

- `app/Enums/AdmissionRequestSource.php`
- `app/Enums/AdmissionRequestStatus.php`
- `app/Http/Controllers/Admin/AdmissionsWard/AdmissionController.php`
- `app/Http/Controllers/Admin/AdmissionsWard/AdmissionRequestController.php`
- `app/Http/Controllers/Admin/Emergency/EmergencyDispositionController.php`
- `app/Http/Requests/StoreAdmissionRequest.php`
- `app/Models/Admission.php`
- `app/Models/AdmissionRequest.php`
- `app/Models/Bed.php`
- `app/Models/Patient.php`
- `app/Models/Visit.php`
- `app/Services/AdmissionService.php`
- `app/Services/Admissions/AdmissionRequestService.php`
- `database/migrations/2026_07_04_000001_create_admission_requests_table.php`
- `database/seeders/RoleSeeder.php`
- `lang/en/admissions.php`
- `lang/fr/admissions.php`
- `resources/views/admissions/create.blade.php`
- `resources/views/admissions/request-create.blade.php`
- `resources/views/admissions/request-show.blade.php`
- `resources/views/admissions/requests.blade.php`
- `routes/web.php`
- `tests/Feature/AdmissionWorkflowFoundationTest.php`

## New Table and Column

New table: `admission_requests`

Key columns:

- patient_id
- visit_id
- source_type
- source_id
- requested_by
- accepted_by
- rejected_by
- cancelled_by
- requested_ward_id
- preferred_bed_type
- reserved_bed_id
- priority
- provisional_diagnosis
- clinical_summary
- status
- requested_at
- accepted_at
- rejected_at
- cancelled_at
- converted_at
- reason
- timestamps
- soft deletes

New nullable column: `admissions.admission_request_id`

Historical admissions remain valid because the column is nullable.

## Admission Request Statuses

- requested
- under_review
- accepted
- bed_pending
- reserved
- converted
- rejected
- cancelled

Rejected, cancelled, and already converted requests cannot be converted.

## Source Compatibility

Supported source types:

- consultation
- emergency
- direct
- maternity
- transfer
- theatre

Only generic source storage/labels were added for maternity, transfer, and theatre. Their full workflows were intentionally not implemented in this phase.

## New Routes

- `GET admin/admissions/requests`
- `GET admin/admissions/requests/create`
- `POST admin/admissions/requests`
- `GET admin/admissions/requests/{admissionRequest}`
- `PATCH admin/admissions/requests/{admissionRequest}/accept`
- `PATCH admin/admissions/requests/{admissionRequest}/reject`
- `PATCH admin/admissions/requests/{admissionRequest}/cancel`
- `PATCH admin/admissions/requests/{admissionRequest}/bed-pending`
- `PATCH admin/admissions/requests/{admissionRequest}/reserve-bed`
- `POST admin/admissions/requests/{admissionRequest}/convert`

The existing `admin.admissions.requests` route name remains the board route.

## New Permissions

- `admission.requests.view`
- `admission.requests.create`
- `admission.requests.accept`
- `admission.requests.reject`
- `admission.requests.cancel`
- `admission.requests.convert`
- `admission.requests.bed_pending`
- `admission.requests.reserve_bed`

Existing ward permissions were not removed:

- `ward.view`
- `ward.manage`
- `ward.admit`
- `ward.discharge`
- `beds.view`
- `beds.manage`

## Compatibility Notes

Existing direct admission remains compatible:

- `admissions/create` and `admissions/store` still work without an admission request.
- `admission_request_id` is optional.
- Existing admission billing still runs only when an actual admission is created.

Emergency flow remains compatible:

- Emergency disposition still marks the visit as admitting.
- Emergency admitted disposition now creates or reuses an open emergency-source admission request.
- The user is still redirected to the New Admission form, now with `admission_request_id` included.

Consultation integration:

- Generic consultation source support was added.
- A consultation UI hook was deferred because this phase did not redesign consultation/admit actions.

Request-to-admission conversion:

- Completed through the existing New Admission form.
- Conversion calls the admission request service, which then calls the existing admission service.
- Billing is not posted during request creation, acceptance, bed pending, or reservation.

## Tests and Checks Run

- `php -l app/Services/Admissions/AdmissionRequestService.php`
- `php -l app/Http/Controllers/Admin/AdmissionsWard/AdmissionRequestController.php`
- `php -l app/Http/Controllers/Admin/AdmissionsWard/AdmissionController.php`
- `php -l app/Http/Controllers/Admin/Emergency/EmergencyDispositionController.php`
- `php -l tests/Feature/AdmissionWorkflowFoundationTest.php`
- `php -l app/Models/AdmissionRequest.php`
- `php -l app/Enums/AdmissionRequestStatus.php`
- `php -l database/migrations/2026_07_04_000001_create_admission_requests_table.php`
- `php artisan test tests/Feature/AdmissionWorkflowFoundationTest.php`
- `php artisan test tests/Feature/WardAdmissionTest.php`
- `php artisan route:list --name=admissions.requests`
- `php artisan view:clear`
- `php artisan config:clear`

## Known Risks

- The request board is intentionally basic and not yet at Emergency UI parity.
- Legacy visits already marked `ADMITTING` are shown as compatibility rows until they are converted into first-class requests.
- Consultation admit UI integration is prepared through source support but not wired to a consultation button in this phase.
- Bed reservation uses the existing `reserved` bed status but does not yet include reservation expiry or location history.

## Next Recommended Phase

Phase 3: Admission UI/UX Parity with Emergency.

Focus areas:

- Richer ward/request board.
- Admission workspace request/handover panel.
- Timeline display.
- Billing readiness warnings.
- Discharge readiness panel.
- Bed transfer and location history design.
