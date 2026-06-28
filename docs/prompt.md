Yes. We should absolutely start with **Phase 8.1 — Data Integrity and Trustworthiness**.

There is no point optimizing performance or adding advanced widgets if users cannot trust the numbers on the screen.

This phase should focus entirely on:

* making every KPI correct
* making every chart truthful
* making every department truly scoped
* eliminating fake values

Here is the full implementation prompt.

---

# UHMS Department Dashboard Phase 8.1

# Data Integrity & Metric Correctness

You are implementing **Phase 8.1 – Dashboard Data Integrity** for the UHMS Department Dashboard system.

The dashboard architecture, themes, layouts, showcases, and department identities already exist and must remain unchanged.

The objective of this phase is:

> Every KPI, sparkline, chart, queue, and drilldown must accurately represent the data visible to the current department.

---

## Rules

* Do NOT redesign the UI.
* Do NOT modify showcase layouts.
* Do NOT introduce new visual widgets.
* Do NOT change themes.
* Do NOT modify dashboard identities.
* Preserve all existing routes.
* Preserve all existing cards.

Only improve data correctness.

---

# TASK 1 — Audit Every Metric

Review every metric produced by:

```php
DepartmentDashboardDataService::metricCard()
```

Classify each metric as:

* Department-scoped
* Hospital-wide
* User-specific
* Unknown

Add an internal definition table:

```php
protected array $metricDefinitions = [
    'visits_today' => [
        'scope' => 'department',
    ],
];
```

---

# TASK 2 — Fix Department Scoping

The following metrics currently appear globally.

Investigate and properly scope them.

* pending_prescriptions
* dispensed_today
* active_cases
* critical_cases
* beds_occupied
* stock_issues
* in_theatre

Use:

* department_id
* service_department_id
* requesting_department_id
* visit department
* encounter department

depending on the underlying model.

If a metric genuinely cannot be scoped:

```php
[
    'scope' => 'hospital'
]
```

and expose:

```php
'is_hospital_wide' => true
```

for future UI badges.

---

# TASK 3 — Fix Critical Cases

Current implementation:

```php
active_cases == critical_cases
```

Implement proper critical filtering.

Possible sources:

* priority
* severity
* triage_level
* urgency

Investigate existing emergency models before implementing.

Requirements:

* active_cases != critical_cases
* critical_cases <= active_cases

Add tests.

---

# TASK 4 — Eliminate Fake Metrics

Remove all hardcoded values.

Current:

```php
vitals_due => 0
discharges_pending => 0
default => 0
```

Replace with:

```php
null
```

or

```php
[
    'available' => false
]
```

Unimplemented metrics must never silently display zero.

---

# TASK 5 — Metric Definition Validation

Unknown metrics must fail loudly.

Replace:

```php
default => 0
```

with:

```php
throw new InvalidArgumentException(
    "Unknown department dashboard metric [$metric]"
);
```

This prevents silent dashboard corruption.

---

# TASK 6 — Fix Sparklines

Review:

```php
metricSpark()
```

The sparkline dataset must use the exact same filters as the card.

Examples:

## dispensed_today

Card:

```php
status = dispensed
```

Spark:

must also use:

```php
status = dispensed
```

---

## completed_results_today

Card:

```php
status = completed
```

Spark:

must also filter:

```php
status = completed
```

Requirement:

The headline number and sparkline trend must describe the same dataset.

---

# TASK 7 — Introduce Metric Definitions

Create:

```php
DepartmentMetricDefinitionService
```

Example:

```php
return [
    'waiting_queue' => [
        'scope' => 'department',
        'permission' => 'visits.view',
        'sparkline' => true,
    ],
];
```

This service will become the single source of truth for:

* scope
* permissions
* chart eligibility
* drilldown eligibility

Future phases will reuse it.

---

# TASK 8 — Correct Activity Data

Review:

```php
activities()
```

Current implementation uses:

```php
invoice_items.description
```

as department activity.

Replace with actual operational activity.

Examples:

* patient checked in
* prescription dispensed
* result validated
* investigation completed
* procedure performed

If no audit data exists, hide the section.

---

# TASK 9 — Add Data Integrity Tests

Create:

```text
tests/Feature/Departments/
    DepartmentMetricScopingTest.php
    DepartmentMetricCorrectnessTest.php
```

Cover:

* department A cannot see department B data
* critical_cases differs from active_cases
* hospital-wide metrics flagged correctly
* unknown metrics throw exceptions
* sparklines match card filters

---

# TASK 10 — Safety Validation

Run:

* route:list
* view compilation
* dashboard rendering tests
* existing department tests

Verify:

* no broken cards
* no missing views
* no theme regressions
* no layout regressions

---

# Deliverables

Provide:

1. Updated metric inventory.
2. List of hospital-wide metrics.
3. Metrics that remain unavailable.
4. New tests added.
5. Query count impact.
6. Remaining Phase 8.2 tasks.

---

# Important

The goal of Phase 8.1 is:

> Users must trust every number on the screen.

Performance optimization, permission enforcement, advanced widgets, caching, and dashboard intelligence belong to later phases.

A slower dashboard with correct numbers is preferable to a fast dashboard with incorrect numbers.
