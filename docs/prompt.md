# UHMS Consultation View & Workflow — Full Gap Analysis, Repetitive JS Audit and Improvement Plan

## Goal

Perform a full gap analysis of the consultation view page and the complete consultation workflow.

This is an **analysis-only phase**.

Do not implement fixes yet.

The goal is to understand:

```text id="offmdm"
why the consultation page has repetitive JavaScript
where the page is becoming fragile
which clinical/workflow features are incomplete or risky
which privacy/permission issues may exist
which UI/UX improvements are needed
how to ultimately refactor the page safely
```

Produce a clear technical and functional report before any implementation.

---

## 1. Scope

Analyse the consultation view page and all related partials, scripts, controllers, services, routes, requests, and tests.

Focus on:

```text id="xfdz1l"
consultation show/view page
consultation workspace
patient header/summary section
clinical notes
diagnosis
vitals display
investigation requests
radiology requests
prescriptions
procedures/treatments
follow-ups/reviews
referrals
admissions/escalations
billing links
attachments/files if any
print/PDF if any
privacy masking
permissions
JavaScript interactions
modals
AJAX endpoints
inline scripts
duplicated logic
```

Do not limit the analysis to only what is visually broken.

Look for hidden architectural problems too.

---

## 2. Files To Inspect

Start with likely files:

```text id="jkfjrn"
resources/views/consultations/show.blade.php
resources/views/doctor/consultation/show.blade.php
resources/views/admin/consultations/show.blade.php
resources/views/consultations/partials
resources/views/components/patient-card.blade.php
resources/views/components/patient-long-card.blade.php
resources/views/components/patient-protected-field.blade.php
app/Http/Controllers/Doctor/ConsultationController.php
app/Http/Controllers/Admin/Consultations
app/Http/Controllers/Admin/Lab
app/Http/Controllers/Admin/Pharmacy
app/Http/Controllers/Admin/Procedures
app/Services/Consultation*
app/Services/PatientPrivacyService.php
app/Services/Billing
routes/web.php
lang/en
lang/fr
tests/Feature/Consultations
tests/Feature/PatientPrivacy*
```

Then search for all related files:

```bash id="x0kjob"
grep -R "consultation" resources app routes tests -n
grep -R "doctor.consultation\|consultations.show\|consultation.show" resources app routes tests -n
grep -R "@push('scripts')\|@section('scripts')\|<script" resources/views/consultations resources/views/doctor resources/views/admin -n
```

Also search for repeated JavaScript patterns:

```bash id="mm290c"
grep -R "DOMContentLoaded\|addEventListener\|fetch(\|axios\|$.ajax\|data-bs-toggle\|modal\|querySelector\|getElementById" resources/views app -n
```

---

## 3. Repetitive JavaScript Audit

Investigate why JS is repetitive.

Look specifically for:

```text id="2ijqj2"
same script copied in multiple partials
same event listener registered multiple times
multiple DOMContentLoaded blocks
inline AJAX repeated for lab/pharmacy/procedure/forms
modal submit handlers repeated
duplicated CSRF setup
duplicated toast/alert handling
duplicated validation handling
duplicated select/search initialisation
duplicate element IDs
scripts inside Blade partials that can be rendered more than once
handlers bound directly to dynamic elements instead of delegated events
reinitialisation bugs after modal close/open
scripts depending on Blade-generated IDs
same route URLs hardcoded in many scripts
business rules inside JavaScript instead of services/controllers
```

For each JS issue, document:

```text id="mob693"
file
line or section
duplicated logic
risk
recommended fix
priority
```

Classify risks:

```text id="nmqq2a"
Low: messy but harmless
Medium: maintainability issue
High: can break workflow or submit wrong data
Critical: can create wrong clinical/billing data or leak patient data
```

---

## 4. Ultimate JS Solution

Design a proper long-term JS structure.

Do not implement yet.

Recommend how to move from repeated inline scripts to structured modules.

Preferred approach:

```text id="op9j5q"
one consultation page JS entrypoint
small feature modules
event delegation
shared AJAX helper
shared form submit helper
shared modal manager
shared toast/notification helper
shared route/data config
no duplicated inline scripts in partials
```

Possible structure:

```text id="v3nuc5"
resources/js/pages/consultation-show.js
resources/js/consultation/modules/diagnosis.js
resources/js/consultation/modules/investigations.js
resources/js/consultation/modules/prescriptions.js
resources/js/consultation/modules/procedures.js
resources/js/consultation/modules/followups.js
resources/js/consultation/modules/vitals.js
resources/js/consultation/modules/billing.js
resources/js/consultation/shared/ajax.js
resources/js/consultation/shared/forms.js
resources/js/consultation/shared/modals.js
resources/js/consultation/shared/toasts.js
```

If the project does not currently use a JS build pattern for page modules, propose a Blade-safe alternative:

```text id="d6zpnc"
single pushed script file
one global ConsultationPage object
data attributes for route/config
delegated handlers
shared helpers
no duplicated event binding
```

Document which approach best fits the current UHMS frontend.

---

## 5. Blade Structure Audit

Analyse whether the consultation view has become too large.

Check for:

```text id="zr8d13"
too many responsibilities in one Blade file
large inline conditionals
duplicated cards/tables
mixed clinical, billing, pharmacy, lab, and JS logic
hardcoded English
hardcoded permissions
hardcoded routes
complex calculations in Blade
patient privacy fields rendered raw
```

Recommend Blade decomposition.

Suggested structure:

```text id="quaein"
consultations/show.blade.php
consultations/partials/header.blade.php
consultations/partials/patient-summary.blade.php
consultations/partials/clinical-notes.blade.php
consultations/partials/diagnosis.blade.php
consultations/partials/vitals.blade.php
consultations/partials/investigations.blade.php
consultations/partials/radiology.blade.php
consultations/partials/prescriptions.blade.php
consultations/partials/procedures.blade.php
consultations/partials/treatment-plan.blade.php
consultations/partials/follow-up.blade.php
consultations/partials/billing-summary.blade.php
consultations/partials/activity-timeline.blade.php
consultations/partials/modals.blade.php
```

But do not over-split if the current page is still manageable.

---

## 6. Consultation Workflow Gap Analysis

Review the functional flow.

Expected consultation workflow:

```text id="mj6pyo"
open patient consultation
review patient summary
review vitals
review history/previous visits
record complaints
record examination findings
enter diagnosis
request investigations/radiology
prescribe medicines
request procedures/treatments
add clinical notes
create follow-up/review
refer/admit/escalate if needed
complete consultation
generate billing where appropriate
show next workflow step
```

Check whether UHMS currently supports each step clearly.

For each step, document:

```text id="3cwi9e"
exists / partial / missing
current implementation
issues
data model/service involved
permission needed
improvement recommendation
priority
```

---

## 7. Clinical Safety Gaps

Look for risks that can affect clinical correctness.

Check:

```text id="ag9j6i"
can consultation be completed without diagnosis?
can prescription be added without diagnosis?
can lab/radiology requests be duplicated accidentally?
can a user submit the same form twice?
are medication allergies visible/protected correctly?
are chronic conditions shown with correct privacy permission?
are urgent requests clearly marked?
is there confirmation before finalising consultation?
can finalised consultation still be edited?
is edit-after-complete audited?
are clinical notes versioned or overwritten?
are abnormal vitals highlighted?
are previous diagnoses visible?
is there a clear audit trail of clinical changes?
```

Document each issue and risk level.

---

## 8. Billing and Service Rendering Gaps

Consultation workflows often create billable items.

Check:

```text id="lx79mw"
consultation fee billing
investigation billing
radiology billing
procedure billing
prescription billing
insurance pricing
coverage calculation
service rendering status
duplicate billing prevention
billing reversal/credit note relationship
unbilled items visibility
```

Also verify the recent rule:

```text id="dfmbnh"
insurance coverage calculation must use selected insurance price, not cash/base price
```

Document if consultation-triggered billing respects this rule.

---

## 9. Patient Privacy and Permission Audit

The consultation page must respect patient privacy phases.

Check for raw fields:

```text id="pr2rf3"
phone
email
address
digital address
Ghana Card / identity number
insurance membership/policy number
emergency contact
allergies
chronic conditions
confidential clinical fields
```

Rules:

```text id="oqrjmh"
Level 2 PII requires granular privacy permission or patients.pii.view.
Level 3 clinical-sensitive data requires patients.clinical_sensitive.view.
patients.pii.view must not reveal Level 3 fields.
Exports/prints require patients.export_sensitive.view.
```

Document any raw exposure.

Use:

```text id="53ai68"
PatientPrivacyService
<x-patient-protected-field>
```

Do not recommend inline masking.

---

## 10. Permission and Role Gaps

Check that every consultation action is permission protected.

Actions:

```text id="drjzjg"
view consultation
start consultation
update notes
add diagnosis
request lab
request radiology
prescribe medicines
request procedure
add follow-up
refer patient
admit patient
complete consultation
reopen/edit completed consultation
print/export consultation summary
view sensitive patient data
```

For each action, document:

```text id="ckcesn"
route
controller method
current middleware
expected permission
gap
recommended permission
```

Run or inspect:

```bash id="a9pxmr"
php artisan route:list | grep consultation
php artisan permissions:audit --strict
```

---

## 11. UI/UX Gap Analysis

Review the usability of the consultation page.

Check:

```text id="fjlcsb"
is the page too long?
are sections clearly grouped?
is clinical priority obvious?
are actions easy to find?
does the page work well on tablet?
does it work on mobile?
are modals too many?
are empty states clear?
are loading states clear?
are errors shown near the affected form?
are success messages consistent?
are disabled/restricted states clear?
are urgent/critical indicators visible?
```

Recommend a better layout.

Possible improved layout:

```text id="ezjoxp"
sticky patient/visit header
left clinical timeline
center active consultation workspace
right quick actions/results/alerts panel
tabbed or accordion sections for requests/prescriptions/procedures
bottom completion/follow-up panel
```

Or:

```text id="4ljx0h"
top patient banner
clinical summary cards
workflow tabs
side action drawer
timeline of clinical events
```

---

## 12. Performance Audit

Check for:

```text id="630ptd"
N+1 queries
too many relationships loaded
too many AJAX calls on page load
large Blade payload
large inline JSON
repeated chart/script initialisation
unnecessary queries for hidden sections
queries not scoped to current consultation/visit
```

Recommend:

```text id="y85uho"
eager loading
service layer payload builder
lazy loading sections
separate JSON endpoints for heavy tabs
caching read-only dropdowns
only querying permission-visible sections
```

---

## 13. Data Integrity and Duplicate Submission Audit

Check if repeated JS can cause duplicate records.

Risk examples:

```text id="ju1k2j"
same investigation request submitted twice
same prescription submitted twice
same procedure request submitted twice
consultation completed twice
billing line generated twice
follow-up duplicated
same modal handler fires multiple times
```

Recommend:

```text id="7f11jg"
server-side idempotency keys
disable submit buttons after click
unique request tokens
transaction boundaries
duplicate detection before billing/service creation
audit logs for repeated actions
```

---

## 14. Error Handling Audit

Check:

```text id="7s6jz3"
AJAX error handling
validation errors
network failure states
server exceptions
permission denied responses
session timeout
CSRF token expiration
partial form failures
```

Recommend unified error handling:

```text id="kp3hya"
show validation errors near fields
toast generic failures
modal-level errors
redirect on session expiration
clear 403 restricted messages
retry only where safe
```

---

## 15. Localisation Audit

Look for hardcoded English/French strings.

Check:

```text id="3llf2g"
Blade labels
JS strings
modal titles
button text
toasts
validation messages
empty states
chart labels
confirmation messages
```

Run:

```bash id="wv4rwr"
php scripts/localisation-audit.php
php scripts/localisation-parity-check.php
```

Expected:

```text id="dtlz0g"
Active runtime candidates: 0
EN/FR parity OK
```

---

## 16. Accessibility and Responsiveness

Check:

```text id="n5uz80"
keyboard navigation
modal focus
form labels
aria attributes
color-only indicators
button labels
table responsiveness
mobile consultation workflow
doctor tablet workflow
```

Recommend improvements.

---

## 17. Testing Gap Analysis

Inspect existing consultation tests.

Find missing tests for:

```text id="jwed52"
consultation page loads
privacy masking on consultation page
diagnosis creation
investigation request
radiology request
prescription creation
procedure request
follow-up creation
consultation completion
duplicate submit protection
permission protection
billing generation
insurance pricing/coverage
finalised consultation edit restrictions
localisation
view cache
JS behaviour if covered by Playwright
```

Recommend focused tests and E2E tests.

---

## 18. Deliverables

Create these documents:

```text id="xxwua5"
docs/CONSULTATION_VIEW_GAP_ANALYSIS_REPORT.md
docs/CONSULTATION_JS_REPETITION_AUDIT_REPORT.md
docs/CONSULTATION_WORKFLOW_IMPROVEMENT_PLAN.md
```

## CONSULTATION_VIEW_GAP_ANALYSIS_REPORT.md

Include:

```text id="vj88aa"
summary
files inspected
current page structure
functional workflow coverage
privacy/permission gaps
clinical safety gaps
billing gaps
UI/UX gaps
performance gaps
testing gaps
risk matrix
recommended phases
```

## CONSULTATION_JS_REPETITION_AUDIT_REPORT.md

Include:

```text id="6gm3ml"
all inline scripts found
duplicated JS patterns
event listener risks
modal duplication risks
AJAX duplication risks
duplicate ID risks
route/config duplication
recommended JS architecture
quick wins
long-term refactor plan
```

## CONSULTATION_WORKFLOW_IMPROVEMENT_PLAN.md

Include:

```text id="pne0re"
ideal consultation workflow
missing/partial features
recommended UI layout
recommended service architecture
recommended permission model
recommended tests
implementation phases
```

---

## 19. Recommended Output Format

Provide a final summary with:

```text id="vtn3zh"
Top 10 risks
Top 10 quick wins
Top 10 structural improvements
Recommended implementation phases
Files most likely needing refactor
Whether any critical patient privacy issue was found
Whether any duplicate-submission risk was found
Whether repetitive JS is caused by inline scripts/partials/event binding
```

---

## 20. Do Not Implement Yet

This phase is analysis only.

Do not refactor the page.

Do not change JavaScript.

Do not change routes.

Do not change billing logic.

Do not change permissions.

Do not change database schema.

Only create reports and, if useful, small read-only analysis commands.

---

## 21. Verification

Run safe verification only:

```bash id="u86le6"
php artisan route:list | grep consultation
php artisan permissions:audit --strict
php scripts/localisation-audit.php
php scripts/localisation-parity-check.php
git diff --check
```

Do not run the wide full test suite.

Do not run migrations.

Do not apply destructive changes.

---

## 22. Acceptance Criteria

This analysis phase is complete only when:

```text id="wuqz1r"
consultation view structure is fully mapped
all consultation-related JS is inventoried
repetitive JS root causes are identified
duplicate event/submission risks are documented
privacy/permission gaps are documented
clinical safety gaps are documented
billing/workflow gaps are documented
UI/UX improvements are documented
performance issues are documented
test gaps are documented
recommended JS refactor architecture is proposed
recommended implementation phases are proposed
three reports are created
no production code behaviour is changed
```

Proceed with the Consultation View & Workflow full gap analysis now.
