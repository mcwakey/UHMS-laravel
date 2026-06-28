<?php

namespace App\Services\Department;

/**
 * Maps each dashboard metric (and the things that mirror it — drilldown, quick
 * action, widget) to the single capability that governs its visibility. One source
 * of truth so a card and its drilldown are never out of sync.
 *
 * Metrics not listed are low-sensitivity counts (services/staff/activity) that are
 * always visible (capability = null).
 */
class DepartmentMetricDefinitionService
{
    private const METRIC_CAPABILITIES = [
        // Clinical flow
        'visits_today' => 'consultation_access',
        'waiting_queue' => 'consultation_access',
        'completed_today' => 'consultation_access',
        'appointments_today' => 'consultation_access',
        'scheduled' => 'consultation_access',
        'in_theatre' => 'consultation_access',

        // Diagnostics
        'pending_requests' => 'investigation_access',
        'pending_imaging' => 'investigation_access',
        'scheduled_imaging' => 'investigation_access',
        'samples_awaiting_acceptance' => 'investigation_access',
        'completed_results_today' => 'investigation_access',
        'completed_imaging_today' => 'investigation_access',

        // Pharmacy
        'pending_prescriptions' => 'pharmacy_access',
        'dispensed_today' => 'pharmacy_access',

        // Stock
        'low_stock' => 'stock_access',
        'stock_items' => 'stock_access',
        'stock_issues' => 'stock_access',
        'stock_requests' => 'stock_access',

        // Emergency
        'active_cases' => 'emergency_access',
        'critical_cases' => 'emergency_access',

        // Ward
        'active_admissions' => 'ward_access',
        'beds_occupied' => 'ward_access',
        'vitals_due' => 'ward_access',
        'discharges_pending' => 'ward_access',

        // Financial
        'department_revenue_today' => 'financial_access',
        'revenue_today' => 'financial_access',
        'invoices_today' => 'financial_access',
        'payments_today' => 'financial_access',
        'receivables' => 'financial_access',
    ];

    public function capabilityFor(string $metricKey): ?string
    {
        return self::METRIC_CAPABILITIES[$metricKey] ?? null;
    }

    /** @return array<string, string> */
    public function all(): array
    {
        return self::METRIC_CAPABILITIES;
    }
}
