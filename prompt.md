You are a senior Laravel developer working on **UHMS — Ultimate Hospital Management System**.

We need to create a reusable **Visit Preview** page/component using the design pattern from:

```text
resources/views/activities.blade.php
```

The uploaded `activities.blade.php` page uses a clean timeline/card layout. Build the Visit Preview using the same visual style:

```text
page-wrapper
content
page header
card
card-body
timeline rows
timeline-date
border-start
border-circle
chronological activity entries
```

The Visit Preview must automatically generate a full clinical and chronological summary when given a `Visit` as parameter.

Do not break existing workflows.

---

# 1. Main Objective

Create a reusable Visit Preview feature.

Whenever any page needs to display the full summary of a patient visit, the system should call this preview and pass the visit.

Examples:

```php
@include('visits.partials.visit-preview', ['visit' => $visit])
```

or:

```php
<x-visit-preview :visit="$visit" />
```

or route/modal usage:

```php
route('visits.preview', $visit)
```

The preview must show the full visit story clinically and chronologically.

---

# 2. Files to Create

Create these files, adapting naming to the current project structure:

```text
resources/views/visits/preview.blade.php
resources/views/visits/partials/visit-preview-timeline.blade.php
resources/views/visits/partials/visit-preview-summary.blade.php
```

Optional component approach:

```text
resources/views/components/visit-preview.blade.php
app/View/Components/VisitPreview.php
```

Also create or update:

```text
app/Http/Controllers/VisitPreviewController.php
app/Services/VisitPreviewService.php
routes/web.php
```

---

# 3. Route

Add a route:

```php
Route::get('/visits/{visit}/preview', [VisitPreviewController::class, 'show'])
    ->name('visits.preview')
    ->middleware(['auth']);
```

Use existing route prefix and middleware conventions if different.

If admin routes are used:

```php
Route::get('/admin/visits/{visit}/preview', [VisitPreviewController::class, 'show'])
    ->name('admin.visits.preview')
    ->middleware(['auth']);
```

---

# 4. Controller

Create:

```php
VisitPreviewController
```

Required method:

```php
public function show(Visit $visit, VisitPreviewService $service)
{
    $preview = $service->build($visit);

    return view('visits.preview', [
        'visit' => $visit,
        'preview' => $preview,
    ]);
}
```

If the project uses Inertia/Vue for this page, create an Inertia page, but still keep a Blade partial/component available for modal reuse if current layout is Blade-based.

---

# 5. VisitPreviewService

Create:

```php
app/Services/VisitPreviewService.php
```

Required method:

```php
public function build(Visit $visit): array
```

This service should collect all visit-related clinical and operational information and convert it into a clean timeline.

Do not put complex data-building logic inside Blade.

---

# 6. Data to Include

The Visit Preview must include as much as available from the existing database.

## Patient Summary

Show at the top:

```text
Patient name
Patient number / Patient ID
Age / gender
Phone number
Ghana Card number if available
Insurance used for the visit
Visit number
Visit type: OPD / Emergency / Inpatient
Visit status
Visit date
Assigned doctor
Department / service
```

## Visit Timeline

Show a chronological timeline of all major visit activities:

```text
Visit created / checked in
Triage / vitals recorded
Consultation started
Complaints recorded
Diagnosis added
Treatments / clinical notes recorded
Prescriptions created
Investigations requested
Investigation items accepted / billed
Investigation results entered
Investigation results verified
Procedures requested
Procedure accepted / billed / scheduled
Anaesthesia note
Operative note
Post-op note
Pharmacy dispensing
Products / consumables used
Payments made
Admission created if patient was admitted
Ward notes if available
Discharge / completion
Referral / death / outcome if available
```

Each timeline item must show:

```text
date/time
title
description/details
entered by / performed by user
department if available
status/badge if useful
source module
```

---

# 7. Timeline Sorting

Timeline entries must be sorted chronologically.

Default:

```text
oldest first
```

Add optional support later for newest first.

Use exact timestamps where available.

If only a date exists, still include it in the timeline.

---

# 8. Timeline Item Structure

The service should normalize all events into a common structure:

```php
[
    'datetime' => $timestamp,
    'date_label' => '24 Sep 2026',
    'time_label' => '09:30 AM',
    'title' => 'Triage completed',
    'description' => 'Vitals recorded and patient directed to consultation.',
    'entered_by' => 'Nurse Ama Mensah',
    'department' => 'Triage',
    'badge' => 'TRIAGE',
    'badge_class' => 'bg-info',
    'details' => [],
    'source_type' => 'triage',
    'source_id' => $triage->id,
]
```

The Blade should loop through these normalized items.

---

# 9. Design Requirements

Use the uploaded `activities.blade.php` style.

The Visit Preview page should have:

```text
Page header: Visit Preview
Card container
Timeline entries
Date column on the left
Vertical border line
Clinical summary blocks
Badges
Readable details
```

Timeline row structure should be similar to:

```blade
<div class="d-flex align-items-start">
    <p class="text-dark me-4 mb-0 timeline-date flex-shrink-0">
        {{ $item['date_label'] }}
        <small class="d-block text-muted">{{ $item['time_label'] }}</small>
    </p>

    <div class="border-start ps-4 py-4 border-circle position-relative">
        <div class="d-flex align-items-center gap-2 mb-1">
            <p class="text-dark fw-semibold mb-0">{{ $item['title'] }}</p>

            @if(!empty($item['badge']))
                <span class="badge {{ $item['badge_class'] ?? 'bg-secondary' }}">
                    {{ $item['badge'] }}
                </span>
            @endif
        </div>

        <p class="mb-1">{{ $item['description'] }}</p>

        <small class="text-muted">
            Entered by: {{ $item['entered_by'] ?? 'System' }}
            @if(!empty($item['department']))
                · Department: {{ $item['department'] }}
            @endif
        </small>
    </div>
</div>
```

Adapt class names to match the current template.

---

# 10. Empty State

If no timeline data exists, show:

```text
No clinical activities recorded for this visit yet.
```

Do not crash if some relationships are missing.

---

# 11. Reusability

The preview must be reusable in three ways:

## Full Page

```php
route('visits.preview', $visit)
```

## Blade Include

```blade
@include('visits.partials.visit-preview-timeline', ['preview' => $preview])
```

## Modal

Any page should be able to open Visit Preview in a modal or link to the preview page.

Examples of pages that may call it:

```text
Patient profile
Previous visits list
Consultation page
Emergency case page
Admission page
Investigation page
Billing page
Reports
```

---

# 12. Visit Preview Button

Where visit lists are displayed, add an action button:

```text
Preview Visit
```

The button should open the preview page or modal.

Suggested icon:

```text
eye / file-medical / timeline
```

---

# 13. Data Loading / Relationships

Use eager loading to avoid N+1 queries.

Load relationships such as:

```php
$visit->load([
    'patient',
    'department',
    'assignedDoctor',
    'triage.triagedBy',
    'medicalRecord.doctor',
    'medicalRecord.complaints.createdBy',
    'medicalRecord.diagnoses.createdBy',
    'medicalRecord.treatments.createdBy',
    'medicalRecord.prescriptions.items',
    'labRequests.items.results',
    'procedureRequests.schedule',
    'procedureRequests.anaesthesiaNote',
    'procedureRequests.operativeNote',
    'procedureRequests.postOpNote',
    'invoice.items',
    'invoice.payments',
    'admission',
    'vitals.recordedBy',
    'statusLogs.user',
]);
```

Adapt relationships to the actual model names.

Do not load huge unrelated data.

---

# 14. Clinical Sections to Summarize

Before the timeline, show quick summary cards:

```text
Visit Information
Patient Information
Insurance / Billing Summary
Clinical Summary
```

Clinical summary may include:

```text
Chief complaint
Primary diagnosis
Final diagnosis if available
Prescriptions count
Investigations count
Procedures count
Billing status
Visit outcome
```

---

# 15. Users Who Made Entries

Every timeline item should show who created/performed the entry when available.

Examples:

```text
Registered by
Triaged by
Consulted by
Diagnosis entered by
Investigation requested by
Result entered by
Result verified by
Prescription created by
Drug dispensed by
Procedure accepted by
Anaesthesia note by
Operative note by
Payment received by
Discharged by
```

If user is missing, show:

```text
System
```

or:

```text
Unknown user
```

Do not crash.

---

# 16. Status Logs

Include visit status changes in the timeline.

Example:

```text
Visit status changed from TRIAGE to WAITING_CONSULTATION by Nurse Ama
Visit status changed from WAITING_CONSULTATION to CONSULTING by Dr. Mensah
Visit completed by Dr. Mensah
```

Use `visit_status_logs` if available.

---

# 17. Billing Timeline

Include billing and payment events:

```text
Invoice created
Invoice item added
Discount applied
Payment received
Invoice marked paid
Invoice cancelled
```

Show financial amounts clearly but not too much detail.

Example:

```text
Payment received: GHS 150.00 by Cashier John
```

---

# 18. Investigation Timeline

Include:

```text
Investigation requested
Items accepted
Items billed
Result entered
Result verified
Result printed if tracked
```

Group or summarize if there are many items.

Example:

```text
Lab request created for Full Blood Count, Malaria Test
Result verified by Lab Technician
```

---

# 19. Procedure Timeline

Include:

```text
Procedure requested
Accepted
Billed
Scheduled
Pre-op recorded
Anaesthesia note
Operative note
Post-op note
Completed
```

This should align with the theatre/procedure workflow.

---

# 20. Pharmacy Timeline

Include:

```text
Prescription created
Prescription item dispensed
Dispensed product billed
```

If stock/product movement exists, show product and quantity.

---

# 21. Emergency and Admission Compatibility

The same Visit Preview must work for:

```text
OPD visits
Emergency visits
Inpatient/admission visits
```

Do not create separate preview pages for each workflow.

If Emergency data exists, include it.

If Admission data exists, include it.

If not, omit those sections.

---

# 22. Print / Export Preparation

Design the page so it can later be printed.

Add a button:

```text
Print Visit Summary
```

For now, it can call:

```js
window.print()
```

Optional later: PDF export.

Use print-friendly CSS where possible.

---

# 23. Permissions

Add or verify permission:

```text
visits.preview
```

Only authorized users should view Visit Preview.

Recommended roles:

```text
Doctor
Nurse
Records
Admin
Super Admin
Emergency staff
Claims officer if allowed
```

Do not expose sensitive visit preview to unauthorized users.

---

# 24. Performance Rules

* Do not query inside Blade loops.
* Build timeline in `VisitPreviewService`.
* Eager-load relationships.
* Paginate is not needed for one visit preview.
* Keep large notes collapsed if needed.
* Avoid loading unrelated visits.

---

# 25. Error Handling

If visit does not exist, return 404.

If user is unauthorized, return 403.

If some relationships are missing, skip that timeline section gracefully.

---

# 26. Tests Required

Add or update tests:

1. Authorized user can open Visit Preview.
2. Unauthorized user cannot open Visit Preview.
3. Visit Preview page loads for OPD visit.
4. Visit Preview page loads for Emergency visit.
5. Visit Preview page loads for admitted visit.
6. Timeline includes triage entry when triage exists.
7. Timeline includes consultation entry when medical record exists.
8. Timeline includes diagnosis entries.
9. Timeline includes prescription entries.
10. Timeline includes investigation requests/results.
11. Timeline includes procedure events.
12. Timeline includes billing/payment events.
13. Timeline includes users who made entries.
14. Missing optional relationships do not crash preview.
15. Timeline is sorted chronologically.

---

# 27. Deliverables

Provide:

1. `VisitPreviewService`.
2. Visit preview route.
3. Visit preview controller.
4. Visit preview Blade page using `activities.blade.php` design.
5. Reusable partial/component.
6. Preview Visit button where appropriate.
7. Permission check.
8. Timeline generation logic.
9. Tests or verification notes.
10. Files modified.
11. Remaining TODOs if any.

---

# 28. Important Rules

Do not duplicate visit data manually.

Do not create separate preview systems for OPD, Emergency, and Admission.

Do not query heavily inside Blade.

Do not break existing visit, consultation, billing, investigation, procedure, pharmacy, emergency, or admission workflows.

Do not expose preview to unauthorized users.

Do not crash if some visit sections are missing.

Build one reusable Visit Preview that automatically generates a clinical chronological summary when given a Visit.
