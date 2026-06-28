# Department Dashboard — Subsystem Documentation

The department dashboard answers, for the signed-in user, **"what does my
department need me to know and do right now?"** — scoped to their department,
filtered by their capabilities, fast, and localized (EN/FR).

This folder is the maintenance reference. Start here, then read the topic you need.

| Doc | Covers |
|---|---|
| [architecture.md](architecture.md) | Request flow, components, the dependency map |
| [registries.md](registries.md) | The five registries and their single responsibilities |
| [capabilities.md](capabilities.md) | RBAC: capability profiles, metric→capability, unified gating |
| [caching.md](caching.md) | Payload cache, schema cache, the shared trend repository |
| [intelligence.md](intelligence.md) | Operational widget, alerts, status |
| [extending.md](extending.md) | How to add a type / widget / capability / KPI / chart |

## Phase history (context, not required reading)

| Phase | Theme | Report |
|---|---|---|
| 8.1 | Data correctness (scoping, critical≠active) | — |
| 8.2 | Performance (schema cache, grouped queries, chart registry, cache) | `../DEPARTMENT_DASHBOARD_PHASE_8_2_PERFORMANCE_REPORT.md` |
| 8.3 | Experience (status badges, friendly time, identity widget, header) | `../DEPARTMENT_DASHBOARD_PHASE_8_3_EXPERIENCE_REPORT.md` |
| 8.4 | Capability security (unified visibility) | `../DEPARTMENT_DASHBOARD_PHASE_8_4_RBAC_REPORT.md` |
| 8.5 | Operational intelligence (widgets, alerts, status) | `../DEPARTMENT_DASHBOARD_PHASE_8_5_INTELLIGENCE_REPORT.md` |
| 8.6 | Architecture cleanup & this documentation | `../DEPARTMENT_DASHBOARD_PHASE_8_6_CLEANUP_REPORT.md` |

## One-paragraph mental model

`DepartmentDashboardController` resolves a **context** (which department, which user,
which dashboard key), then `DepartmentDashboardDataService::build()` assembles a
**payload** (KPI cards, queues, charts, services, the operational widget, alerts,
status). Every metric is **department-scoped** and **capability-gated**; the heavy
work is **cached** (30s payload cache) and **deduplicated** (schema cache, one
grouped query per trend). The payload is rendered by a per-key **showcase**
(`types/{key}.blade.php` → `_chrome` + `{key}_showcase`). Adding a department type or
widget means editing a registry/builder + a Blade file — never the engine.
