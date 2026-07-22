# Department Mapping

## Evidence and target constraints

[Confirmed] `departements` has 18 rows: `DEP_ID, Departement, Permission, DepType, Billable`. Target `departments.code` is unique; name/code are required. Target type is application-enumerated, while result type, stock-managed state and status have defaults that are not source mappings.

## Column specification

| Source | Disposition | Target/rule |
|---|---|---|
| `DEP_ID` | Crosswalk | protected identity, never target PK |
| `Departement` | Transform | `departments.name`; normalized exact comparison |
| `DepType` | Crosswalk | explicit target DepartmentType only |
| `Permission` | Excluded | D-008; never permissions/access |
| `Billable` | Evidence only | no department target field; do not infer service billing |

Target `code`, `result_type`, `is_stock_managed`, `status`, description, supervisor and escalation are not silently defaulted. Each needs an explicit crosswalk/configuration outcome. Supervisor/escalation wait for staff mappings.

## Identity and creation

[Technical specification] Preferred target identity is an explicitly approved target code. A seeded-code match is a candidate only and must compare semantics. Normalized name/type is review-only. Do not invoke `DepartmentController`: it calls `StockLocationSyncService`, may create locations and emits audit activity.

Seeded candidates (`OPD`, `PED`, `OBG`, etc.) are reference inputs, not accepted mappings. `DepartmentSeeder` uses code-only `firstOrCreate` and does not repair conflicts; it is not a migration matcher.

Blockers: department code/type/result/stock/status crosswalks, duplicate/blank checks, and target-existing comparison.
