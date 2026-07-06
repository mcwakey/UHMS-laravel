# Consultation Personalised Workspace Flow

This document explains how a consultation becomes personalised: from admin setup, to resolver selection, to the doctor seeing a general, physiotherapy, eye, or dental consulting page.

## What "Personalised Consultation" Means

The consultation page is still one safe consultation workspace, but it changes its clinical layout and helper tools based on the active specialty profile.

- A general doctor gets the general medicine consultation layout.
- A physiotherapist gets physiotherapy sections such as presenting problem, pain assessment, treatment plan, therapy session, and home exercise plan.
- An eye clinic doctor gets ophthalmology sections such as eye complaint, visual acuity, refraction, IOP, eye examination, and eye follow-up.
- A dental doctor gets dental sections such as dental complaint, tooth chart, oral examination, dental diagnosis, dental procedures, and consent.

The system does not open separate hard-coded pages per specialty. Instead, it resolves the correct specialty profile and builds the same consultation page with that profile's configured sections, quick actions, favorites, order sets, readiness rules, summary builder, and billing context.

## Main Building Blocks

### 1. Specialty Profiles

Specialty profiles define the type of consultation workspace.

Seeded profiles:

- `general_medicine`
- `physiotherapy`
- `ophthalmology`
- `dental`

Each profile has visible ordered sections. These sections decide what appears in the doctor workspace and which structured specialty forms are available.

Relevant storage:

- `consultation_specialty_profiles`
- `consultation_specialty_sections`

Relevant admin area:

- Consultation Specialties -> Specialty Profiles
- Consultation Specialties -> Sections

### 2. Specialty Profile Mappings

Mappings tell the system when to use a profile.

Relevant storage:

- `consultation_specialty_profile_mappings`

A mapping can target:

- a specific consultation route
- a department
- a department type
- a doctor/user

Examples:

- Department "Physiotherapy" maps to `physiotherapy`.
- Department "Eye Clinic" maps to `ophthalmology`.
- Department "Dental" maps to `dental`.
- Department type "consultation" can map to `general_medicine` as the safe default.

Relevant admin area:

- Consultation Specialties -> Mappings

### 3. Doctor Preferences

Doctor preferences let a doctor have a preferred consultation profile, pinned actions, and layout settings.

Relevant storage:

- `doctor_consultation_preferences`

Preferences include:

- default specialty profile
- default department
- pinned quick actions
- compact mode
- preferred layout

Relevant UI:

- Doctor workspace header, where pinned actions and compact mode are reflected.
- Admin can inspect/reset preferences from Consultation Specialties -> Doctor Preferences.

### 4. Specialist Extras

Once a profile is active, the workspace can also load profile-specific extras:

- specialty favorites
- order sets
- readiness rules
- specialty summary builder
- billing/service mapping

These are additive. If one extra is missing, the main consultation page should still open.

## Setup Flow

### Step 1: Create Or Confirm Specialty Profiles

Admin confirms that profiles exist for:

- General Medicine
- Physiotherapy
- Ophthalmology
- Dental

Seeded profiles already provide the starter version of these.

Admin can configure:

- profile name/code
- icon/color
- active/inactive status
- visible section list
- section order
- required sections

### Step 2: Configure Sections For Each Profile

Each profile has its own section list.

Example general medicine:

- patient summary
- complaints
- examination
- diagnosis
- investigations
- prescription
- procedures
- notes
- summary

Example physiotherapy:

- patient summary
- presenting problem
- pain assessment
- functional limitation
- physical assessment
- treatment plan
- therapy session
- home exercise plan
- progress notes
- summary

Example ophthalmology:

- patient summary
- eye complaint
- visual acuity
- refraction
- IOP
- eye examination
- diagnosis
- investigations
- procedures
- prescription
- follow-up
- summary

Example dental:

- patient summary
- dental complaint
- tooth chart
- oral examination
- dental diagnosis
- dental x-ray
- dental procedures
- consent
- prescription
- follow-up
- summary

### Step 3: Map Departments To Profiles

Admin maps clinic departments to profiles.

Typical setup:

| Department | Specialty profile |
| --- | --- |
| General OPD / Consulting Room | `general_medicine` |
| Physiotherapy | `physiotherapy` |
| Eye Clinic / Ophthalmology | `ophthalmology` |
| Dental | `dental` |

This is the most important part of the personalisation flow. When a patient is routed to a department, the consultation route carries the department. The resolver uses that department to choose the correct specialty profile.

### Step 4: Optional Doctor Preference

If a doctor should default to a specialty even when department mapping is not enough, admin or the preference flow can set a doctor default.

Example:

- Dr. Mensah defaults to `ophthalmology`.
- Dr. Aidoo defaults to `physiotherapy`.

Doctor preference is lower priority than a direct route or department mapping, so a specific routed consultation can still override it.

### Step 5: Optional Specialty Tools

Admin can configure profile-specific tools:

- Favorites: common diagnoses, procedures, drugs, frequency defaults, tasks, and follow-up instructions.
- Order sets: grouped safe clinical suggestions for common specialty scenarios.
- Service mappings: billing-aware links from specialty profile/context to existing service catalog items.
- Readiness rules and summary builder are profile-aware from the implementation.

## How The System Decides Which Page The Doctor Gets

The resolver is `ConsultationSpecialtyProfileResolver`.

When the consultation page opens, the controller calls the resolver with:

- authenticated doctor/user
- active visit
- selected consultation route
- route department

The resolver priority is:

1. Consultation route mapping
2. Department mapping
3. Department type mapping
4. Doctor preference
5. User primary/assigned department mapping
6. Existing specialty entry for that route
7. General medicine fallback

Inactive mappings and inactive profiles are ignored. If a non-general profile has no visible sections, the system falls back to general medicine so the doctor is not blocked by bad configuration.

## What Happens When A Doctor Opens A Consultation

The consultation controller builds the workspace in this order:

1. Resolve active specialty profile.
2. Build the layout from that profile's visible ordered sections.
3. Load saved specialty entries for the active route and profile.
4. Load specialty favorites for the active profile.
5. Load specialty order sets for the active profile.
6. Evaluate specialty readiness for completion safety.
7. Build the specialty summary builder payload.
8. Build the doctor personal workspace header.
9. Build specialty billing context.
10. Render `resources/views/consultations/show.blade.php` with all payloads.

Important payloads passed to the view:

- `specialtyContext`
- `specialtyLayout`
- `specialtyEntries`
- `specialtyFavorites`
- `specialtyOrderSets`
- `specialtyReadiness`
- `specialtySummaryBuilder`
- `doctorSpecialtyWorkspace`
- `specialtyBillingContext`

## What The Doctor Sees

### General Doctor

If the resolver returns `general_medicine`, the doctor sees the normal general consultation workspace.

The page remains light:

- complaints
- history
- examination
- diagnosis
- investigations
- prescription
- procedures
- notes/summary

Quick actions are general actions such as complaints, examination, diagnosis, prescription, summary, and readiness.

### Physiotherapy Doctor

If the resolver returns `physiotherapy`, the same consultation page becomes a physiotherapy workspace.

The doctor sees physiotherapy-specific sections and actions:

- presenting problem
- pain assessment
- functional limitation
- physical assessment
- treatment plan
- therapy session
- home exercise plan
- progress notes

Physio favorites, physio order sets, physio readiness rules, and physio summary generation become available when configured.

### Eye Clinic Doctor

If the resolver returns `ophthalmology`, the page becomes an eye consultation workspace.

The doctor sees eye-specific sections and actions:

- eye complaint
- visual acuity
- refraction
- intraocular pressure
- eye examination
- diagnosis
- investigations
- procedures
- prescription
- follow-up

Eye favorites, eye order sets, eye readiness, eye summary generation, and mapped eye service billing become available when configured.

### Dental Doctor

If the resolver returns `dental`, the page becomes a dental consultation workspace.

The doctor sees dental-specific sections and actions:

- dental complaint
- tooth chart
- oral examination
- dental diagnosis
- dental x-ray
- dental procedures
- consent
- prescription
- follow-up

Dental favorites, dental order sets, dental readiness, dental summary generation, and mapped dental service billing become available when configured.

## Simple Example End To End

### Physiotherapy Example

1. Admin creates or confirms the `physiotherapy` profile.
2. Admin confirms physiotherapy sections are visible.
3. Admin maps the Physiotherapy department to `physiotherapy`.
4. Patient is routed to the Physiotherapy department.
5. Doctor opens the consultation route.
6. Resolver sees the route department is Physiotherapy.
7. Resolver returns the `physiotherapy` profile.
8. Layout service builds physio sections.
9. Doctor workspace service builds physio quick actions and metrics.
10. The doctor sees the physiotherapy consulting page.

### Eye Clinic Example

1. Admin maps Eye Clinic or Ophthalmology department to `ophthalmology`.
2. Patient is routed to Eye Clinic.
3. Eye doctor opens the consultation.
4. Resolver returns `ophthalmology`.
5. The page shows eye complaint, visual acuity, refraction, IOP, eye examination, and related tools.

### General Doctor Example

1. No specific physiotherapy, eye, dental, or route mapping matches.
2. Department type or fallback resolves to `general_medicine`.
3. The doctor sees the general consulting page.

## Safety And Fallback Rules

- The workspace always falls back to general medicine if no active matching specialty can be found.
- Inactive profiles are ignored.
- Inactive mappings are ignored.
- A specialty profile with no visible sections falls back to general medicine.
- Specialty tools are additive; missing favorites, order sets, billing mappings, or summary data should not stop the consultation page.
- Billing mappings never create fake services. They only link to existing active billable service catalog records.
- Specialty billing applies charges only through the existing billing service.

## Where To Look In Code

Resolver:

- `app/Services/Consultation/Specialty/ConsultationSpecialtyProfileResolver.php`

Workspace assembly:

- `app/Http/Controllers/Doctor/Consultations/Concerns/HandlesConsultationWorkspace.php`

Layout:

- `app/Services/Consultation/Specialty/ConsultationSpecialtyLayoutService.php`

Doctor personal workspace:

- `app/Services/Consultation/Specialty/DoctorSpecialtyWorkspaceService.php`

Doctor preferences:

- `app/Models/DoctorConsultationPreference.php`
- `app/Services/Consultation/Specialty/DoctorConsultationPreferenceService.php`

Admin configuration:

- `app/Http/Controllers/Admin/ConsultationSpecialtyProfileController.php`
- `app/Http/Controllers/Admin/ConsultationSpecialtyMappingController.php`
- `app/Http/Controllers/Admin/DoctorConsultationPreferenceAdminController.php`

Consultation view:

- `resources/views/consultations/show.blade.php`
- `resources/views/consultations/partials/specialty-workspace-band.blade.php`
- `resources/views/consultations/partials/workflow-sidebar.blade.php`
- `resources/views/consultations/partials/specialty/structured-section.blade.php`

Billing/service mapping:

- `app/Services/Consultation/Specialty/ConsultationSpecialtyBillingMappingService.php`
- `app/Services/Consultation/Specialty/ConsultationSpecialtyBillingApplicationService.php`

## Admin Checklist

Use this checklist when setting up a new personalised consultation workspace:

1. Create or activate the specialty profile.
2. Configure visible sections and order.
3. Map the department, department type, route, or doctor to the profile.
4. Add specialty favorites if doctors need quick inserts.
5. Add order sets if the specialty has common clinical bundles.
6. Add readiness rules if completion should enforce specialty requirements.
7. Add service mappings if the specialty consultation should show billing awareness.
8. Open a test consultation route for that department.
9. Confirm the workspace header, quick actions, sections, readiness, summary, and billing context match the specialty.

## Mental Model

Think of the system like this:

`Doctor + Visit + Consultation Route + Department`

becomes:

`Resolved Specialty Profile`

which becomes:

`Personalised Consultation Workspace`

So the doctor's page is personalised by configuration, not by hard-coded separate pages.
