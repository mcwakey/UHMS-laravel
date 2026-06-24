# UHMS UI Improvements Tracker

Use this file as the shared checklist for UI improvements. Attach it to new UI prompts so we can first confirm whether a requested improvement already exists, where it is implemented, and what still needs adoption.

## How To Use

- Check the component or pattern here before adding another implementation.
- Prefer existing reusable Blade components over page-specific markup.
- When a page adopts a tracked pattern, update the status or notes here.
- Keep entries short and practical: component path, intent, usage rules, status, and next adoption targets.

## Component Patterns

### `x-page-header`

- **Status:** Implemented and broadly adopted.
- **Component:** `resources/views/components/page-header.blade.php`
- **Purpose:** Standard page title area with optional description, icon, breadcrumbs, and action buttons.
- **Use For:** Top header on admin/list/detail/workbench pages.
- **Usage Notes:**
  - Use `:title`, optional `description`, optional `icon`, and optional `breadcrumbs`.
  - Put page actions in `<x-slot:actions>`.
  - Avoid custom page-header markup unless the page truly needs a different layout.
- **Current Adoption Examples:**
  - `resources/views/visits/index.blade.php`
  - `resources/views/appointments/index.blade.php`
  - `resources/views/patients/index.blade.php`
  - `resources/views/triage/index.blade.php`
  - Many accounting, billing, emergency, pharmacy, admissions, and report pages.
- **Next Check:** When touching any page with a custom `.page-header` block, replace it with `x-page-header`.

### `x-filter-bar`

- **Status:** Implemented, adoption in progress.
- **Component:** `resources/views/components/filter-bar.blade.php`
- **Purpose:** Standard reusable filter/search card with consistent input alignment, sizing, apply button, and reset action.
- **Use For:** Index/list/report pages with search, status, date range, department, provider, doctor, or other filters.
- **Usage Notes:**
  - Pass `:action` and `:reset-url`.
  - Place each filter control in a `col-*` wrapper inside the default slot.
  - Let the component render Apply/Reset by default, or provide `<x-slot:actions>` for page-specific buttons.
  - Keep field sizes/styles consistent with existing `.form-control`, `.form-select`, and button styles.
- **Current Adoption Examples:**
  - `resources/views/visits/index.blade.php`
  - `resources/views/appointments/index.blade.php`
  - `resources/views/patients/index.blade.php`
  - `resources/views/billing/credit-notes/index.blade.php`
  - `resources/views/billing/reports/aging.blade.php`
  - `resources/views/billing/reports/discounts.blade.php`
  - `resources/views/billing/sponsors/index.blade.php`
  - `resources/views/billing/statements/index.blade.php`
  - `resources/views/billing/statements/show.blade.php`
  - `resources/views/statistics/show.blade.php`
- **Next Check:** When touching an index/list/report page with a hand-written filter card, convert it to `x-filter-bar`.

### `x-data-table`

- **Status:** Implemented, adoption in progress.
- **Component:** `resources/views/components/data-table.blade.php`
- **Purpose:** Standard reusable table card with responsive wrapper, consistent table classes, optional paginator summary, per-page selector, and pagination links.
- **Use For:** Index/list/report pages that display paginator-backed tabular records.
- **Usage Notes:**
  - Pass `:paginator`.
  - Put table headings in `<x-slot:head>`.
  - Put `@forelse` table rows in the default slot.
  - Use `show-summary` to render `Showing :from to :to of :total results`.
  - Use `show-per-page` with `:current-per-page` and `:per-page-options` when the connected filter form supports `per_page`.
  - When used with `x-filter-bar ajax`, keep the table inside the AJAX target container so pagination and per-page changes refresh only the result area.
- **Current Adoption Examples:**
  - `resources/views/patients/index.blade.php`
  - `resources/views/blood-bank/units.blade.php`
- **Next Check:** Convert hand-written table cards and page-specific pagination footers to `x-data-table` when those pages are touched.

### `x-page-header-back`

- **Status:** Implemented, first adoption complete.
- **Component:** `resources/views/components/page-header-back.blade.php`
- **Purpose:** Compact page header for create/edit/detail screens where the title is also a back link.
- **Use For:** Workflow pages that need a simple return link instead of a full list-page header.
- **Usage Notes:**
  - Pass `:title` and `:href`.
  - Use the default `ti-chevron-left` icon unless the page needs a clearer back affordance.
  - Put secondary actions in `<x-slot:actions>` when needed.
- **Current Adoption Examples:**
  - `resources/views/visits/create.blade.php`
- **Next Check:** Replace matching hand-written back-title headers on create/edit pages as they are touched.

### `x-patient-selection-card`

- **Status:** Implemented, first adoption complete.
- **Component:** `resources/views/components/patient-selection-card.blade.php`
- **Purpose:** Standard patient lookup card with selected-patient summary, deceased warning, last-visit display, and optional active-admission override warning.
- **Use For:** Create/workflow pages that need a patient picker before continuing, such as visits, appointments, admissions, emergency cases, and clinical work queues.
- **Usage Notes:**
  - Defaults preserve the current visits create DOM IDs: `patientSearch`, `patientId`, `patientInfo`, `deceasedWarning`, and active-admission IDs.
  - Pass `:selected-patient` when editing or returning from validation errors.
  - Pass `:can-override-active-admission` when the workflow needs active admission override handling.
  - Keep page JavaScript pointed at the component IDs instead of duplicating the markup.
- **Current Adoption Examples:**
  - `resources/views/visits/create.blade.php`
  - `resources/views/appointments/create.blade.php`
- **Next Check:** Convert admission/emergency patient selection cards to this component when those pages are touched.

### `x-insurance-selection-card`

- **Status:** Implemented, first adoption complete.
- **Component:** `resources/views/components/insurance-selection-card.blade.php`
- **Purpose:** Standard insurance selection card with fallback badge, optional add-insurance button, hidden selected insurance input, and optional provider-agnostic verification panel.
- **Use For:** Visit, appointment, and other clinical workflow pages that need a patient insurance selector before billing or service capture.
- **Usage Notes:**
  - Defaults preserve the visits create DOM IDs: `insuranceCard`, `insuranceFallbackBadge`, `insuranceList`, `visitInsuranceId`, `insuranceVerificationId`, and verification panel IDs.
  - Pass `:can-add-insurance` to control the Add Insurance button.
  - Set `:show-verification="false"` for simpler workflows that do not use `/admin/insurance/verify`.
  - Override text with `:title`, `:fallback-label`, `:add-button-label`, and `:loading-label`.
  - Use the default slot for workflow-specific insurance summary panels while keeping the shared list/input wrapper.
  - Keep page JavaScript pointed at the component IDs instead of duplicating the markup.
- **Current Adoption Examples:**
  - `resources/views/visits/create.blade.php`
  - `resources/views/appointments/create.blade.php`
  - `resources/views/visits/edit.blade.php`
  - `resources/views/appointments/edit.blade.php`
- **Next Check:** Convert other visit/appointment insurance cards to this component when those pages are touched.

### `x-visit-details-card`

- **Status:** Implemented, first adoption complete.
- **Component:** `resources/views/components/visit-details-card.blade.php`
- **Purpose:** Standard visit details form card for visit type, priority, visit date, scheduling fields, consultation mode, chief complaint, and notes.
- **Use For:** Visit create/edit workflows and related appointment-to-visit flows that capture the same clinical visit details.
- **Usage Notes:**
  - Defaults preserve the current visits create DOM IDs: `visitDate`, `schedulingFields`, and `schedulingHint`.
  - Pass `:visit="$visit"` when using the component on edit pages.
  - Override labels/placeholders with props such as `:visit-type-label`, `:visit-date-label`, `:chief-complaint-label`, and `:notes-label`.
  - Use `visit-date-field-name`, `visit-date-id`, `:visit-date-required`, and `:visit-date-min` when a workflow stores the date under a different field, such as appointments.
  - Use `:show-scheduling-fields`, `:show-scheduling-hint`, `:start-time-required`, `:end-time-hint`, `:notes-rows`, and `:stack-textareas` for workflow-specific layout without duplicating the card.
  - Keep scheduling JavaScript pointed at the component IDs instead of duplicating the markup.
- **Current Adoption Examples:**
  - `resources/views/visits/create.blade.php`
  - `resources/views/appointments/create.blade.php`
  - `resources/views/visits/edit.blade.php`
  - `resources/views/appointments/edit.blade.php`
- **Next Check:** Convert other visit-detail sections to this component when those pages are touched.

### `x-department-services-card`

- **Status:** Implemented, first adoption complete.
- **Component:** `resources/views/components/department-services-card.blade.php`
- **Purpose:** Standard department, doctor, available services, service filtering, selected services, and billing total card.
- **Use For:** Visit and appointment workflows that route patients to departments/doctors and collect billable services.
- **Usage Notes:**
  - Defaults preserve the visits create DOM IDs: `departmentSelect`, `doctorSelect`, `showExtraServices`, `servicesList`, `servicesContent`, `servicesItems`, `selectedServicesCard`, `billingBody`, and `totalAmount`.
  - Pass `:departments="$departments"` and, when needed, `:doctors="$doctors"` to populate selectors.
  - Override labels/placeholders with `:title`, `:department-label`, `:doctor-label`, `:available-services-label`, `:selected-services-label`, `:department-placeholder`, `:doctor-placeholder`, `:services-placeholder`, and `:service-filter-placeholder`.
  - Set `department-name` and `doctor-name` when the workflow needs native form submission for those fields.
  - Use `:department-required`, `:doctor-required`, and `:doctor-disabled-until-department` to match validation and loading behavior.
  - Use `:show-extra-services-toggle="false"` for appointment-style service selection.
  - Use `billing-table-variant="quantity"` with `:estimated-total-label` when the workflow needs quantity/unit-price/line-total columns.
  - Keep page JavaScript pointed at the component IDs instead of duplicating the markup.
- **Current Adoption Examples:**
  - `resources/views/visits/create.blade.php`
  - `resources/views/appointments/create.blade.php`
  - `resources/views/visits/edit.blade.php`
  - `resources/views/appointments/edit.blade.php`
- **Next Check:** Convert remaining department-services sections to this component when those pages are touched.

### `x-patient-insurance-form-modal`

- **Status:** Implemented, first adoption complete.
- **Component:** `resources/views/components/patient-insurance-form-modal.blade.php`
- **Purpose:** Standard add/edit patient insurance modal with provider, tier, membership, policy, CCC code, member type, expiry date, and primary flag fields.
- **Use For:** Patient insurance add/edit flows embedded in visits, appointments, patient profile, and other registration workflows.
- **Usage Notes:**
  - Defaults preserve the visits create DOM IDs: `insuranceModal`, `insuranceForm`, `insuranceFormPatientId`, `insuranceFormInsuranceId`, `insuranceProviderSelect`, `insuranceTierSelect`, and `insuranceFormSaveBtn`.
  - Pass `:insurance-providers="$insuranceProviders"` to populate provider options and tier metadata.
  - Keep modal JavaScript pointed at the component IDs instead of duplicating the markup.
  - Permission checks should stay at the call site unless every consumer shares the same permission rule.
- **Current Adoption Examples:**
  - `resources/views/visits/create.blade.php`
- **Next Check:** Convert remaining inline patient insurance modals/partials to this component when those pages are touched.

### `x-patient-personal-information-card`

- **Status:** Implemented, first adoption complete.
- **Component:** `resources/views/components/patient-personal-information-card.blade.php`
- **Purpose:** Standard patient personal information card with profile image upload/webcam controls, names, DOB, gender, marital status, religion, blood group, and occupation.
- **Use For:** Patient create/edit workflows and any registration-like page that captures patient demographics.
- **Usage Notes:**
  - Pass `:patient="$patient"` on edit pages and omit it on create pages.
  - Pass `:occupations="$occupations"` to populate the searchable occupation select.
  - Defaults preserve the patient registration DOM IDs: `avatar-preview`, `patientAvatarInput`, and `startPatientCameraBtn`.
  - Keep webcam scripts pointed at the component IDs instead of duplicating the card.
- **Current Adoption Examples:**
  - `resources/views/patients/create.blade.php`
  - `resources/views/patients/edit.blade.php`
- **Next Check:** Use this component before adding another patient demographic card.

### `x-patient-contact-identification-card`

- **Status:** Implemented, first adoption complete.
- **Component:** `resources/views/components/patient-contact-identification-card.blade.php`
- **Purpose:** Standard patient contact and identification card with primary phone, secondary phone, email, and ID card number.
- **Use For:** Patient create/edit workflows and registration-like pages that need the same contact inputs.
- **Usage Notes:**
  - Pass `:patient="$patient"` on edit pages and omit it on create pages.
  - Pass country-aware `:phone-pattern` and `:phone-placeholder`.
  - Defaults preserve masking classes: `js-phone-mask`, `js-email-input`, and `js-id-card-input`.
- **Current Adoption Examples:**
  - `resources/views/patients/create.blade.php`
  - `resources/views/patients/edit.blade.php`
- **Next Check:** Use this component before adding another patient contact/ID card.

### `x-patient-address-information-card`

- **Status:** Implemented, first adoption complete.
- **Component:** `resources/views/components/patient-address-information-card.blade.php`
- **Purpose:** Standard patient address card with address, region, city, town, digital address, and device-location trigger.
- **Use For:** Patient create/edit workflows and registration-like pages that need linked country/region/city/town inputs.
- **Usage Notes:**
  - Pass `:patient="$patient"` on edit pages and omit it on create pages.
  - Pass `:regions="$regions"`, `:digital-address-pattern`, and `:digital-address-placeholder`.
  - Defaults preserve location script IDs: `patientRegionSelect`, `patientCitySelect`, `patientTownSelect`, `digitalAddressInput`, and `detectDigitalAddressBtn`.
- **Current Adoption Examples:**
  - `resources/views/patients/create.blade.php`
  - `resources/views/patients/edit.blade.php`
- **Next Check:** Use this component before adding another patient address card.

### `x-patient-emergency-contacts-card`

- **Status:** Implemented, first adoption complete.
- **Component:** `resources/views/components/patient-emergency-contacts-card.blade.php`
- **Purpose:** Standard repeatable emergency contacts card with add/remove controls, primary contact badge, phone mask support, secondary phone, and relationship select.
- **Use For:** Patient registration workflows that capture one or more emergency contacts.
- **Usage Notes:**
  - Pass `:phone-pattern` and `:phone-placeholder`.
  - Optionally pass `:emergency-contacts` when rendering existing contacts.
  - Defaults preserve emergency-contact script IDs/classes: `add-ec-btn`, `ec-wrapper`, `ec-row`, `remove-ec`, and `ec-label`.
- **Current Adoption Examples:**
  - `resources/views/patients/create.blade.php`
- **Next Check:** Adopt on patient edit if emergency contact editing is moved into that form.

### `x-patient-registration-insurance-card`

- **Status:** Implemented, first adoption complete.
- **Component:** `resources/views/components/patient-registration-insurance-card.blade.php`
- **Purpose:** Standard repeatable patient registration insurance card with type, provider, tier, membership number, policy number, expiry date, and add/remove controls.
- **Use For:** Patient registration workflows that capture insurance during initial patient creation.
- **Usage Notes:**
  - Pass `:registration-insurance-types="$registrationInsuranceTypes"`.
  - Keep the registration page responsible for building `registrationInsuranceTypes` and `insuranceI18n` when its JavaScript needs those variables.
  - Defaults preserve insurance registration script IDs/classes: `add-ins-btn`, `ins-wrapper`, `ins-row`, `ins-type`, `ins-provider`, `ins-tier`, and `ins-row-extra`.
- **Current Adoption Examples:**
  - `resources/views/patients/create.blade.php`
- **Next Check:** Use this component before adding another registration insurance card.

### `x-patient-medical-notes-card`

- **Status:** Implemented, first adoption complete.
- **Component:** `resources/views/components/patient-medical-notes-card.blade.php`
- **Purpose:** Standard patient medical notes card with known allergies and chronic conditions.
- **Use For:** Patient create/edit workflows and registration-like pages that need basic medical notes.
- **Usage Notes:**
  - Pass `:patient="$patient"` on edit pages and omit it on create pages.
  - Use `:show-placeholders="false"` where the existing page should not show placeholder text.
- **Current Adoption Examples:**
  - `resources/views/patients/create.blade.php`
  - `resources/views/patients/edit.blade.php`
- **Next Check:** Use this component before adding another allergies/chronic conditions card.

### Page JavaScript I18n Maps

- **Status:** Pattern established for visit create.
- **Source:** `lang/{locale}/visits.php` under `create_js_keys`
- **Purpose:** Keep large page-specific JavaScript translation maps out of Blade templates while still rendering localized strings server-side.
- **Use For:** Blade pages with large `@json([... __('...') ...])` JavaScript maps.
- **Usage Notes:**
  - Keep the page-specific JavaScript key list beside the related language strings in the relevant `lang/{locale}` file.
  - In the view, resolve `trans('visits.create_js_keys')` to localized strings and output only the JSON assignment for the script.
  - Keep very small one-off maps inline only when they are genuinely short and local to a partial.
- **Current Adoption Examples:**
  - `resources/views/visits/create.blade.php`
- **Next Check:** Move large appointment, patient, product, and consultation JavaScript i18n maps into the relevant lang files when those pages are touched.

## Open Adoption Notes

- Continue replacing page-specific filter cards as pages are touched.
- Keep `x-page-header`, `x-filter-bar`, `x-patient-selection-card`, `x-insurance-selection-card`, `x-visit-details-card`, `x-department-services-card`, `x-patient-insurance-form-modal`, and patient registration card components visually aligned with `resources/css/uhms-design-system.css`.
- If a new reusable UI pattern emerges, add it here before using it across multiple pages.
