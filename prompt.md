Absolutely — this is a very important step now. UHMS has grown into a large system, so without a **UI/UX governance layer**, every new module will start looking and behaving differently.

Here is the full implementation prompt for Codex/Copilot:

````text
You are a senior UI/UX architect, Laravel + Inertia/Vue frontend engineer, and design system specialist working on UHMS — Ultimate Hospital Management System.

We need to perform a complete UI/UX audit of the whole UHMS system and create a unified design system rule file that must guide all future UI development and edits.

UHMS now includes many modules:

- Dashboard
- Patients
- Visits
- Consultation
- Consultation Summary
- Emergency
- Admission
- MAR / Medication Administration
- Pharmacy
- Billing
- Insurance / Claims
- Investigations
- Procedures
- Theatre Rooms
- Blood Bank
- Stock / Inventory
- Procurement
- Supplier Ledger
- Assets
- Reports
- Statistics / Analytics
- Notifications
- Logs
- Roles / Permissions
- Modules
- Settings

Because the system has grown quickly, many pages may now have inconsistent UI patterns, layouts, forms, tables, modals, filters, buttons, badges, cards, spacing, colors, typography, status displays, and workflow actions.

We need to inspect the whole UI, identify gaps and inconsistencies, create a full recommendation report, and define a permanent UI/UX theme rule file that future developers must follow.

Do not randomly redesign everything.

First inspect the current UI implementation, identify patterns that already work, then standardize and document the rules.

Do not break existing workflows.

---

# 1. Main Objectives

Perform a full UI/UX audit and standardization plan.

You must:

1. Inspect the entire UHMS frontend.
2. Identify UI inconsistencies across modules.
3. Identify UX workflow gaps.
4. Identify broken or confusing layouts.
5. Identify inconsistent buttons, forms, tables, modals, cards, badges, filters, status labels, navigation, and spacing.
6. Identify accessibility problems.
7. Identify responsive/mobile issues.
8. Identify inconsistent terminology.
9. Identify places where actions lack confirmation, warning, validation, or feedback.
10. Create a detailed UI/UX gap report.
11. Create a full recommendation report.
12. Create a theme/design rule file that future UI work must follow.
13. Add reusable UI guidelines for new pages/components.
14. Define guards/rules for maintaining uniform design across UHMS.
15. Update existing UI where safe and practical.
16. Document remaining UI refactor TODOs.

---

# 2. Required Documentation Files

Create these files:

```text
docs/UI_UX_GAP_ANALYSIS.md
docs/UI_UX_RECOMMENDATION_REPORT.md
docs/UHMS_UI_THEME_RULES.md
docs/UI_COMPONENT_STANDARDS.md
docs/UI_UX_REMAINING_TODOS.md
````

If the project already has a documentation folder convention, follow it.

---

# 3. UI_UX_GAP_ANALYSIS.md

This report must include:

* current frontend framework and styling tools found
* layout system found
* theme/colors currently used
* typography patterns found
* button styles found
* form patterns found
* table patterns found
* modal patterns found
* card patterns found
* filter/search patterns found
* status badge patterns found
* sidebar/menu patterns found
* dashboard widget patterns found
* empty state patterns found
* loading/error patterns found
* inconsistent UI elements
* inconsistent UX flows
* broken layouts
* pages with overcrowded information
* pages hiding important information
* pages missing user feedback
* pages missing confirmation dialogs
* pages missing permissions-based action visibility
* pages not responsive
* pages using different spacing/style rules
* duplicate components that should be reusable
* UI risks by module
* priority ranking of issues

Group findings by module.

Example:

```text
Emergency Module
- Triage section layout differs from Admission vitals layout.
- Medication section does not follow Consultation prescription UI.
- Billing and Procedure sections are visually mixed.
- Some actions have no success/error feedback.
Recommendation priority: HIGH.
```

---

# 4. UI_UX_RECOMMENDATION_REPORT.md

This report must include:

* recommended global layout standard
* recommended page structure
* recommended color system
* recommended typography
* recommended spacing
* recommended button hierarchy
* recommended form structure
* recommended table structure
* recommended modal behavior
* recommended filter/search behavior
* recommended status badges
* recommended clinical document layout
* recommended dashboard cards
* recommended print layouts
* recommended responsive behavior
* recommended accessibility improvements
* recommended module-specific UI fixes
* priority implementation roadmap
* files/components that should be refactored first

---

# 5. UHMS_UI_THEME_RULES.md

Create a permanent theme rule file.

This file must be treated as the design law for UHMS.

It should define:

1. Brand personality.
2. Color tokens.
3. Typography rules.
4. Spacing rules.
5. Layout rules.
6. Button rules.
7. Form rules.
8. Table rules.
9. Modal rules.
10. Card rules.
11. Badge/status rules.
12. Dashboard rules.
13. Clinical document rules.
14. Print rules.
15. Mobile/responsive rules.
16. Accessibility rules.
17. Error/success feedback rules.
18. Permission-based UI rules.
19. Module layout rules.
20. Do and Do Not rules.

---

# 6. UI_COMPONENT_STANDARDS.md

Create component-level standards.

Document how to use/create:

* PageHeader
* SectionHeader
* ModuleCard
* StatCard
* DataTable
* FilterBar
* SearchInput
* StatusBadge
* PriorityBadge
* ActionButtonGroup
* ConfirmDialog
* FormSection
* FormInput
* SelectSearch
* DateRangePicker
* EmptyState
* LoadingState
* ErrorState
* SuccessToast
* Modal
* Drawer/SidePanel
* Timeline
* ClinicalTimeline
* VisitPreviewBlock
* DocumentSummaryBlock
* PrintButton
* ExportButton
* PermissionGuard

If equivalent components already exist, document and reuse them.

If they do not exist, create or recommend them.

---

# 7. UI_UX_REMAINING_TODOS.md

This file must include:

* issues not fixed yet
* pages needing full redesign
* pages needing screenshot regeneration
* components needing extraction
* accessibility tasks
* responsive tasks
* print layout tasks
* technical debt
* recommended next prompts/tasks

---

# 8. Inspect Frontend Structure

Inspect:

```text
resources/js
resources/views
resources/css
resources/sass
tailwind.config.js
vite.config.js
package.json
layouts
components
pages
partials
blade views
sidebar/menu components
theme/config files
```

Search for:

```text
button
btn
card
modal
badge
table
datatable
form
input
select
sidebar
layout
dashboard
status
toast
alert
print
theme
colors
```

Identify repeated hardcoded classes/styles that should become reusable components.

---

# 9. Design Philosophy for UHMS

UHMS must feel:

```text
clean
modern
medical
professional
calm
fast
readable
trustworthy
consistent
data-rich but not chaotic
```

It should not feel:

```text
random
overcrowded
unfinished
inconsistent
too colorful
too dark
too flat
too noisy
hard to scan
```

Because UHMS is clinical software, clarity is more important than decoration.

---

# 10. Global Page Layout Standard

Every page should follow a consistent structure:

```text
Page Header
    - Title
    - Short description
    - Primary action buttons
    - Breadcrumbs if used

Summary / KPI Cards if needed

Filter/Search Bar if needed

Main Content
    - Table / Form / Clinical document / Board / Timeline

Secondary Content
    - Notes / logs / supporting details

Pagination / Footer Actions
```

Avoid pages where actions are randomly placed.

Primary actions must be visible at the top-right of the page header where appropriate.

---

# 11. Page Header Rules

Every major page should have a consistent page header.

Required:

* page title
* short description or context
* optional breadcrumb
* primary action button
* secondary actions where needed

Example:

```text
Patients
Manage patient folders, registrations, insurance records, and merge history.
[New Patient]
```

Clinical example:

```text
Emergency Case
ER-2026-000012 · Ama Mensah · RED · Under Emergency Care
[Open MAR] [Disposition]
```

Do not use large inconsistent headers across pages.

---

# 12. Module Layout Rules

Each module should have a consistent internal pattern.

## Patients

Use patient-folder style layout.

## Consultation

Use clinical workspace layout.

## Emergency

Use emergency control-room layout.

## Admission

Use ward/bed/patient-care layout.

## MAR

Use medication grid and task-oriented layout.

## Pharmacy

Use prescription/billing/dispensing workflow layout.

## Investigations

Use department request/result workflow layout.

## Procedures/Theatre

Use schedule/case/clinical note layout.

## Billing

Use invoice/payment financial layout.

## Stock

Use product/location/movement matrix layout.

## Reports/Statistics

Use dashboard/filter/chart/table layout.

## Settings/Roles/Modules

Use admin configuration layout.

---

# 13. Color System

Define a controlled color system.

Use semantic colors, not random colors.

Recommended semantic colors:

```text
Primary = main brand/action color
Secondary = neutral support
Success = completed/paid/verified/available
Warning = pending/attention/low stock
Danger = critical/error/overdue/out of stock
Info = due/current/in progress
Muted = inactive/cancelled/secondary
Dark = high contrast text
Light = backgrounds
```

Medical status examples:

```text
GREEN = success/completed/available/paid
BLUE = active/in progress/due/current
ORANGE/YELLOW = warning/pending/low/held
RED = danger/critical/overdue/out/refused
GRAY = inactive/cancelled/not stocked
PURPLE = special/correction/verified if already used
```

Do not use multiple meanings for the same color.

Example:

Red should not mean both “completed” and “critical”.

---

# 14. Status Badge Rules

All statuses must use a standard badge component.

Create or reuse:

```text
StatusBadge
```

It should accept:

```text
status
variant
label
size
```

Use consistent styling for statuses across modules.

Examples:

## Visit Status

* REGISTERED = gray
* WAITING_TRIAGE = warning
* WAITING_CONSULTATION = warning
* CONSULTING = info
* EMERGENCY = danger
* ADMITTED = primary/info
* COMPLETED = success
* CANCELLED = muted

## Invoice Status

* UNPAID = danger
* PARTIALLY_PAID = warning
* PAID = success
* CANCELLED = muted

## MAR Status

* SCHEDULED = muted
* DUE = info
* OVERDUE = danger
* GIVEN = success
* HELD = warning
* MISSED = danger
* REFUSED = warning
* CANCELLED = muted

## Stock Status

* OK = success
* LOW = warning
* CRITICAL = danger
* OUT = danger
* NOT_STOCKED = muted

Document all badge mappings in `UHMS_UI_THEME_RULES.md`.

---

# 15. Button Rules

Use a consistent button hierarchy.

Button types:

```text
Primary Action
Secondary Action
Danger Action
Ghost/Link Action
Icon Action
Disabled/Locked Action
```

Rules:

* One primary action per main area.
* Destructive actions must be danger style.
* Destructive actions require confirmation.
* Disabled actions must explain why.
* Do not use random button colors.
* Do not use different sizes for same context.
* Buttons must have consistent icons if icons are used.

Examples:

```text
[New Patient] = primary
[Edit] = secondary
[Delete] = danger
[View] = ghost/link
[Print] = secondary
[Export] = secondary
```

---

# 16. Form Rules

All forms must follow consistent layout.

Rules:

* group related fields into sections
* use clear labels
* show required indicators
* show validation errors below fields
* keep save/cancel buttons consistent
* use searchable selects for large lists
* use date/time picker consistently
* use inline helper text where useful
* avoid very long ungrouped forms
* show loading state while saving
* prevent double-submit
* show success/failure feedback

Medical forms should prioritize speed and clarity.

---

# 17. Table Rules

All data tables should have:

* consistent header
* search/filter section
* clear columns
* status badges
* action column at far right
* pagination aligned consistently
* row hover style
* empty state
* loading state
* responsive behavior
* no broken HTML
* no oversized columns without wrapping rules

Common table action order:

```text
View
Edit
Print
Cancel/Delete
```

Danger actions last.

Do not put pagination on random sides. Standardize it, preferably bottom-right.

---

# 18. Filter/Search Rules

All list pages should use a standard FilterBar.

FilterBar should support:

* search input
* date range
* department
* status
* patient
* user/staff
* reset button
* apply button if needed

Rules:

* filters must preserve state
* reset must clear filters
* filters should not break pagination
* search placeholder must be meaningful
* avoid different filter styles per module

---

# 19. Modal Rules

All modals must follow consistent behavior.

Rules:

* title
* short explanation
* form body
* validation errors inside modal
* cancel button
* submit button
* loading state
* closes only after successful save
* no stuck backdrop
* reset form only after close/success
* dangerous modals require confirmation wording/reason
* large clinical data should use drawer/page, not tiny modal

Use modals for:

* simple create/edit
* confirmation
* quick actions

Use full page/drawer for:

* complex clinical notes
* consultation summary
* MAR chart
* theatre case detail
* emergency case detail

---

# 20. Card Rules

Cards should be used for:

* summary stats
* grouped clinical sections
* dashboards
* patient headers
* module summaries

Rules:

* consistent border/shadow
* consistent padding
* title at top
* value clear if KPI
* icon optional
* avoid overcrowding
* use same card heights in grids where possible

---

# 21. Clinical Document Layout Rules

Clinical pages like Consultation Summary, Visit Preview, MAR print, Theatre notes, Emergency summary must feel like readable documents.

Rules:

* document-style container
* clear patient header
* clear section headings
* chronological order where needed
* authors/contributors visible
* no hidden important clinical data
* print-friendly
* readable font size
* avoid overuse of tables for narrative clinical data
* support long text gracefully

---

# 22. Timeline Rules

Use timelines for:

* Visit Preview
* Emergency timeline
* Theatre timeline
* Logs
* Patient pathway
* Medication administration history
* Patient merge history

Timeline items should show:

* time/date
* title
* description
* user/actor
* module/source
* status/badge if relevant

Chronological order should be clear.

---

# 23. Dashboard Rules

Dashboards should be consistent.

Each dashboard should have:

* KPI cards
* trend charts if available
* priority lists
* action shortcuts
* date/department filter where useful

Do not overload dashboards with too many unrelated widgets.

Each dashboard must answer a clear question.

Examples:

Emergency dashboard:

```text
Who needs urgent attention now?
```

Pharmacy dashboard:

```text
What prescriptions need billing/dispensing?
```

Stock dashboard:

```text
What stock is low, moving, or pending transfer?
```

---

# 24. Navigation / Sidebar Rules

Sidebar must be clean and permission-aware.

Rules:

* group menu items logically
* do not show empty modules
* do not show disabled modules
* do not show menu items without permission
* keep icons consistent
* active menu item must be visually clear
* avoid too many top-level menu items
* use nested menus carefully
* module names must be consistent

Recommended top-level groups:

```text
Dashboard
Patients
Clinical
Emergency
Admission
Pharmacy
Billing
Stock
Blood Bank
Reports
Administration
Settings
```

Adapt to existing structure.

---

# 25. Permission-Based UI Guards

Every sensitive UI action must follow permission rules.

Frontend must hide unavailable actions.

Backend must enforce permissions.

UI should show locked reason when helpful.

Example:

```text
[Edit] hidden if user cannot edit.
[Locked] shown if record is completed and user lacks correction permission.
```

Do not show clickable buttons that always fail with 403 unless unavoidable.

---

# 26. Module Disabled UI Rules

If module is disabled:

* hide menu
* block direct URL
* show clear disabled-module message if accessed
* do not break dashboard
* dependent modules should show warning where relevant

Core modules cannot be disabled.

---

# 27. Notification UI Rules

Notification UI must be consistent.

Rules:

* bell icon with unread count
* priority badge
* module/source label
* time ago
* action link
* mark as read
* view all
* empty state

Critical notifications should be visually distinguishable but not chaotic.

---

# 28. Logs UI Rules

Logs must be readable.

Rules:

* filters at top
* log table with module/action/user/time
* severity badge
* detail view for old/new values
* hide sensitive data
* use readable JSON/diff display

---

# 29. Reports / Statistics UI Rules

Reports and statistics must follow:

```text
FilterBar
Summary Cards
Charts
Detailed Table
Export/Print Buttons
```

Rules:

* no chart without table/drill-down
* default date range required
* permissions enforced
* export buttons consistent
* heavy reports must paginate
* charts must be readable

---

# 30. Responsive Rules

All pages must work on:

* desktop
* tablet
* small laptop
* mobile where possible

Rules:

* tables should scroll horizontally on small screens
* forms should stack on mobile
* sidebars should collapse
* cards should wrap
* modals should fit screen
* no fixed-width layouts that break
* action buttons should not overflow

---

# 31. Accessibility Rules

Improve accessibility.

Rules:

* proper labels on inputs
* visible focus states
* sufficient color contrast
* do not rely on color only for status
* status badges should include text
* buttons must have accessible text
* icon-only buttons need title/aria-label
* keyboard navigation where possible
* error messages clearly linked to fields

---

# 32. Feedback Rules

Every user action must give feedback.

Use:

* success toast
* error toast
* validation messages
* loading spinner
* disabled submit while saving
* confirmation dialogs
* empty states
* warning banners

Examples:

```text
Medication administered successfully.
Payment recorded successfully.
Cannot dispense: insufficient Pharmacy stock.
This session is locked because it was completed automatically after midnight.
```

Do not fail silently.

---

# 33. Empty State Rules

Every empty list/table should have a meaningful empty state.

Examples:

```text
No emergency cases are currently active.
No medications are due for this patient.
No stock movements found for the selected date range.
No claims match your filters.
```

If appropriate, include action:

```text
[Create Emergency Case]
```

only if user has permission.

---

# 34. Error State Rules

Errors should be clear.

Avoid raw technical messages like:

```text
SQLSTATE[23000]
```

Instead show:

```text
Unable to save stock movement because the product or stock location is missing.
```

Technical details can be logged, not shown to normal users.

---

# 35. Confirmation Rules

Require confirmation for:

* delete
* cancel
* reverse payment
* refund
* stock adjustment
* patient merge
* mark deceased
* discharge
* dispose emergency case
* cancel theatre case
* override triage
* issue incompatible/emergency blood
* disable module
* assign critical permissions

High-risk actions require reason.

---

# 36. Print Rules

Print views should:

* hide sidebar/navbar/buttons
* show hospital header if available
* show patient/visit context
* show generated date/time
* use readable black/white layout
* avoid dark backgrounds
* avoid tiny font
* include signatures where needed

Applies to:

* invoice
* receipt
* consultation summary
* visit preview
* MAR chart
* investigation result
* theatre report
* blood issue/transfusion report
* claims documents

---

# 37. Terminology Rules

Use consistent terms across the system.

Examples:

Use:

```text
Patient Folder
Visit
Emergency Case
Admission
Consultation Session
Invoice
Invoice Item
Payment
Balance
Dispensed
Administered
Rendered
Verified
Completed
Cancelled
```

Avoid mixing:

```text
folder/file/card for patient folder inconsistently
bill/invoice randomly
drug/item/product inconsistently
rendered/done/served randomly
```

If local terms are needed, document them.

---

# 38. Status Vocabulary Rules

Avoid too many random statuses.

Standardize statuses per workflow and document them.

Examples:

Generic workflow:

```text
PENDING
IN_PROGRESS
COMPLETED
CANCELLED
ON_HOLD
```

Clinical:

```text
REQUESTED
ACCEPTED
VERIFIED
COMPLETED
```

Financial:

```text
UNPAID
PARTIALLY_PAID
PAID
CANCELLED
REFUNDED
```

Stock:

```text
REQUESTED
APPROVED
ISSUED
RECEIVED
REJECTED
```

Medication:

```text
DUE
OVERDUE
GIVEN
HELD
MISSED
REFUSED
```

---

# 39. Create Theme Config File

Create a theme rule/config file.

Preferred:

```text
resources/js/theme/uhmsTheme.js
```

or if the project uses TypeScript:

```text
resources/js/theme/uhmsTheme.ts
```

Also document in:

```text
docs/UHMS_UI_THEME_RULES.md
```

The theme file should define:

```js
export const uhmsTheme = {
  colors: {
    primary: '',
    secondary: '',
    success: '',
    warning: '',
    danger: '',
    info: '',
    muted: '',
    background: '',
    surface: '',
    border: '',
    text: '',
    textMuted: '',
  },
  statusVariants: {
    // visit, invoice, stock, mar, emergency, theatre, blood bank
  },
  spacing: {
    page: '',
    section: '',
    card: '',
    form: '',
  },
  radius: {
    sm: '',
    md: '',
    lg: '',
  },
  shadows: {
    card: '',
    modal: '',
  },
  typography: {
    pageTitle: '',
    sectionTitle: '',
    body: '',
    small: '',
  }
}
```

Adapt values to existing CSS/Tailwind/Bootstrap variables.

Do not hardcode random new design values if the existing framework already has tokens.

---

# 40. Create UI Guard/Checklist File

Create:

```text
docs/UI_IMPLEMENTATION_CHECKLIST.md
```

Every new UI page must satisfy:

* has page header
* has permission checks
* has loading state
* has empty state
* has validation error display
* has success/error feedback
* uses standard buttons
* uses standard badges
* uses standard tables/forms
* is responsive
* has no raw SQL/error display
* uses module theme/status rules
* follows print rules if printable
* includes logs/notifications where relevant
* no hidden critical clinical data

---

# 41. Component Extraction Recommendations

Identify duplicated UI and recommend/create reusable components.

Likely reusable components:

```text
AppLayout
PageHeader
ModuleHeader
SectionCard
StatCard
StatusBadge
PriorityBadge
DataTable
FilterBar
ActionDropdown
ConfirmModal
FormModal
SearchableSelect
DateRangePicker
PatientSummaryHeader
VisitSummaryHeader
ClinicalSection
Timeline
PrintLayout
EmptyState
LoadingState
ErrorState
PermissionGuard
ModuleGuard
```

Create components only if safe.

Otherwise document recommended extraction in the report.

---

# 42. Module-Specific Audit Requirements

Audit these modules individually and write findings/recommendations:

## Dashboard

Check KPI cards, charts, shortcuts, spacing.

## Patients

Check patient list, search filters, folder layout, merge UI, deceased status, documents.

## Visits

Check visit creation, selected services, status flow, preview, pathway timeline.

## Consultation

Check session list, clinical sections, ownership grouping, summary page, edit actions.

## Emergency

Check emergency board, triage/vitals, bay/team, medication/MAR, investigations, procedures, billing, disposition.

## Admission

Check admission board, bed assignment, vitals, MAR, discharge.

## Pharmacy

Check prescription billing, dispensing, catalogue, stock display.

## Billing

Check invoice view, payment flow, invoice items, discount, balance display.

## Investigations

Check request board, result entry, verification, print result.

## Procedures/Theatre

Check procedure requests, theatre rooms, schedule board, case detail, notes.

## Stock

Check products, stock balances matrix, stock movements, requisitions, transfers.

## Blood Bank

Check donor screening, recipient details, compatibility, crossmatch, issue, transfusion.

## Reports/Statistics

Check filters, cards, charts, tables, exports.

## Notifications

Check dropdown, list, priority, action links.

## Logs

Check filters, detail display, severity, old/new values.

## Roles/Permissions/Modules

Check grouped permissions, module descriptions, warnings for critical actions.

---

# 43. UI Risk Ranking

In the report, rank issues by severity:

```text
CRITICAL = causes wrong clinical/financial/stock action or unsafe workflow
HIGH = blocks users or causes major confusion
MEDIUM = inconsistent but usable
LOW = visual polish
```

Example:

```text
CRITICAL: Emergency medication action has no clear stock source.
HIGH: Pharmacy billing and dispensing buttons are visually similar.
MEDIUM: Tables use inconsistent pagination placement.
LOW: Some cards use slightly different border radius.
```

---

# 44. Implementation Scope

First, perform the audit and generate reports.

Then apply safe global improvements:

* standard badges
* standard buttons
* standard page headers
* standard empty/loading states
* standard filter layout
* standard modal behavior
* standard permission guard helper
* standard theme file

Do not attempt to redesign every page in one risky change if the system is large.

Prioritize high-risk clinical/financial pages.

---

# 45. Tests / Verification

Add or update tests where practical.

UI/feature tests should verify:

1. Unauthorized actions are hidden.
2. Backend still blocks unauthorized actions.
3. Status badges render expected labels/classes.
4. Page header appears on key pages.
5. Empty state appears when no data.
6. Filters preserve state.
7. Modals show validation errors.
8. Confirmation appears for destructive actions.
9. Print layout hides navigation.
10. Sidebar respects module/permission access.

If automated UI tests are not available, add manual verification checklist to report.

---

# 46. Required Final Reports

At the end, create:

## docs/UI_UX_GAP_ANALYSIS.md

With module-by-module findings.

## docs/UI_UX_RECOMMENDATION_REPORT.md

With exact recommendations and priority plan.

## docs/UHMS_UI_THEME_RULES.md

With permanent design system rules.

## docs/UI_COMPONENT_STANDARDS.md

With reusable component rules.

## docs/UI_IMPLEMENTATION_CHECKLIST.md

With checklist for future UI work.

## docs/UI_UX_REMAINING_TODOS.md

With remaining refactors.

---

# 47. Deliverables

Provide:

1. Full UI/UX gap analysis.
2. Module-by-module UI/UX findings.
3. Recommendation report.
4. Theme rule file.
5. Component standards document.
6. UI implementation checklist.
7. Remaining TODOs document.
8. Reusable theme config file.
9. Standard status badge rules.
10. Standard button/form/table/modal rules.
11. Permission/module UI guard recommendations or implementation.
12. Safe UI improvements applied where practical.
13. Files modified.
14. Remaining risks.

---

# 48. Important Rules

Do not randomly redesign the system without audit.

Do not introduce a new CSS framework unless already approved.

Do not break working pages.

Do not make clinical data harder to read.

Do not hide important clinical/financial/stock information.

Do not rely only on color to communicate status.

Do not show actions users cannot perform.

Do not allow disabled modules to appear active.

Do not leave modals with stuck backdrops.

Do not show raw technical errors to users.

Do not create inconsistent new components when reusable ones exist.

Do not make all pages look beautiful but clinically unsafe.

Now inspect the current UHMS frontend, perform a full UI/UX audit, generate the required reports, create the UHMS theme/design rule files, and apply safe standardization improvements where appropriate.

```
```
