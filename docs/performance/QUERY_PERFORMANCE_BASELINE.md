# UHMS Database Query Performance Baseline

Date: 2026-07-19

## Scope and environment

This baseline was captured before the optimisation changes using the existing authenticated department-list flow. It complements the Laravel Debugbar screenshots supplied with the task.

- Laravel: 12.56.0
- PHP: 8.2.12
- Local database: MariaDB 10.4.32
- Automated benchmark database: SQLite in memory
- Local cache, session and queue drivers: database
- Test cache and session drivers: array
- Permissions: Spatie Laravel Permission
- Existing development profiler: Laravel Debugbar (`require-dev`)
- Production deployment commands: Composer optimised autoload plus Laravel config, route, view and event caches in `docs/DEPLOYMENT.md`

No raw bindings or patient, clinical, billing or identity values were retained during profiling.

## Supplied browser baseline

| Request | Method | Approximate queries | Source |
|---|---:|---:|---|
| `/admin/departments?page=1` | GET | 441 | supplied Debugbar screenshot |
| `/admin/departments` | GET, AJAX | 437-439 | supplied Debugbar screenshot |
| `/admin/users` | GET | 441 | supplied Debugbar screenshot |
| `/admin/patients` | GET, AJAX | 445 | supplied Debugbar screenshot |
| `/admin/notifications/recent` | GET, AJAX | 9 | supplied Debugbar screenshot |

The screenshots did not preserve database time, request time, memory, or normalized SQL, so those fields cannot be reconstructed accurately.

## Uncapped reproduction

An isolated authenticated `admin.departments.index` feature request exposed the full repetition that Debugbar capped in its display:

| Route | URL | Status | Total | Unique | Duplicate | Request time |
|---|---|---:|---:|---:|---:|---:|
| `admin.departments.index` | `/admin/departments` | 200 | 12,395 | 14 | 12,381 | 27.74 s |

The dominant fingerprint occurred 12,380 times:

```sql
select * from departments where status = ? order by name asc
```

This is a test-harness measurement, not a claim that every browser request executed all 12,395 queries. It establishes the uncapped behaviour and the source of the Debugbar symptom.

## Repeated patterns and narrowed call sites

| Rank | Pattern or operation | Baseline frequency | Source/call site |
|---:|---|---:|---|
| 1 | Active departments ordered by name | 12,380 | `DepartmentContextSwitcherService::availableDepartments()` called through workspace checks |
| 2 | Unread notification count | 3 | wildcard composer, sidebar and Inertia shared data |
| 3 | User role permissions | 1+ per shell build | repeated menu permission checks before relationship preload |
| 4 | User direct permissions | 1+ per shell build | repeated menu permission checks before relationship preload |
| 5 | User roles | 1+ per shell build | role checks in `SidebarMenuBuilder` |
| 6 | Assigned departments pivot | 1 | active/primary department context resolution |
| 7 | Enabled module flags | repeated consumers | `ModuleService` and Inertia shared data |
| 8 | Department paginator count | 1 | department index controller/query |
| 9 | Department page select | 1 | department index controller/query |
| 10 | Supervisor candidates | 1 | department index page data |
| 11 | Latest notifications | 1 page query plus immediate AJAX | Inertia shared data and `/admin/notifications/recent` |
| 12 | Workspace type checks | repeated in memory plus pattern 1 SQL | wildcard view composer and `WorkspaceRouteResolver::viewContext()` |

## Root-cause chain

1. `View::composer('*')` runs for the root view and every nested Blade view.
2. Each callback resolved a fresh `WorkspaceRouteResolver` and called `viewContext()`.
3. `viewContext()` performed multiple workspace-type checks.
4. Those checks repeatedly asked `DepartmentContextSwitcherService` for the current or available department.
5. For Admin and Super Admin users, `availableDepartments()` executed the same active-departments query every time.

The controller's paginator and page-specific lookups were a small minority of the baseline. The principal defect was shared-shell request context being recomputed for nested views.

## Baseline conclusions

- Shared infrastructure, rather than the departments controller, caused almost all duplicate queries.
- Query growth was tied to render/composer activity rather than table row count.
- Notification and module reads were secondary duplicate sources.
- A request-scoped and session-aware memoization strategy was justified.
- No index could correct thousands of identical application-level executions.
