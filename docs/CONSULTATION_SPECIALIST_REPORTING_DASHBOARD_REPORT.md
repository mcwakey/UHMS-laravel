# Consultation Specialist Reporting Dashboard Report

## Scope

Phase 15 adds specialist consultation analytics without changing the doctor consultation workflow. The report reads persisted specialist metadata only: profile, section key, doctor, department, order-set application status, readiness status, billing mapping status, and invoice line totals linked through specialist billing applications.

## New Reporting Surface

- `GET /admin/reports/consultation-specialties`
- `GET /admin/reports/consultation-specialties/data`
- `GET /admin/reports/consultation-specialties/export`

The routes live inside the existing reports route group, so page and JSON access use the current `module:reports` plus `reports.view` convention. CSV export additionally requires `reports.export`.

## Services

- `ConsultationSpecialtyAnalyticsService`
  Builds the report payload: summary metrics, specialty volume, department volume, doctor workload, section completion, readiness breakdown, order-set usage, billing mapping health, summary-builder availability, and revenue totals.

- `ConsultationSpecialtyDashboardWidgetService`
  Produces a compact management-dashboard widget payload for users with `reports.view`.

- `ConsultationSpecialtyReportExportService`
  Streams CSV exports for the full report or selected datasets.

## Dashboard Integration

The management reports dashboard now displays a specialist consultation widget when the user can view reports. The widget links to the full specialist report and shows:

- specialist consultation count
- applied order-set count
- specialist-linked revenue
- top specialties by consultation volume

## Data Safety

The report intentionally avoids raw clinical content. It does not expose patient names, patient IDs, visit numbers, or JSON entry payloads. Section analytics are grouped by section key/label and profile. Revenue is reported as aggregated invoice line totals linked to specialist billing applications.

## Report Hub And Sidebar

The report is registered in the report hub under Clinical Reports and added to the Clinical Reports sidebar group as Consultation Specialties.

## Validation

Automated coverage is added in `tests/Feature/Consultations/ConsultationSpecialtyReportingTest.php` for:

- report page rendering
- raw clinical payload exclusion from the page
- JSON analytics payload
- export permission enforcement
- CSV streaming
- management dashboard widget visibility
