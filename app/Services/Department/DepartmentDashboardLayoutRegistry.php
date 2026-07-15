<?php

namespace App\Services\Department;

use App\Enums\DepartmentType;

/**
 * Resolves the dashboard *personality* for a department type:
 *   - card profile   → which metric cards + main queue + side cards appear
 *   - name_key       → the department-type dashboard name (departments.dashboards.{type}.name)
 *   - menu heading   → the personalised menu-heading template
 *
 * Keyed on DEPARTMENT TYPE (not the coarser dashboard key) so e.g. radiology gets
 * its own "Radiology Dashboard", distinct from investigation. The actual body is a
 * per-key `types/{key}.blade.php` showcase; this registry no longer chooses a Blade
 * "layout family" (those were retired in Phase 8.6).
 */
class DepartmentDashboardLayoutRegistry
{
    /** type value → personalised menu-heading template. Covers all 19 types. */
    private const MENU_HEADINGS = [
        'consultation' => 'operations',
        'emergency' => 'command_center',
        'investigation' => 'workbench',
        'radiology' => 'workbench',
        'procedure' => 'workbench',
        'theatre' => 'workbench',
        'treatment' => 'operations',
        'nursing' => 'operations',
        'pharmacy' => 'operations',
        'inpatient' => 'operations',
        'maternity' => 'operations',
        'blood_bank' => 'workbench',
        'mortuary' => 'generic',
        'ambulance' => 'command_center',
        'records' => 'records_office',
        'finance' => 'control_room',
        'stores' => 'inventory',
        'support' => 'generic',
        'administrative' => 'generic',
    ];

    private const DEFAULT_PROFILE = [
        'primary_cards' => ['services_count', 'staff_count', 'visits_today', 'activity_today'],
        'secondary_cards' => ['appointments_today', 'revenue_today'],
        'main_queue' => 'department_activity',
        'side_cards' => ['services', 'stock_usage', 'quick_actions'],
        'trend_blocks' => ['seven_day_activity'],
    ];

    /** Card layout (metrics) per type. Types without an entry use DEFAULT_PROFILE. */
    private const PROFILES = [
        'consultation' => ['primary_cards' => ['visits_today', 'waiting_queue', 'completed_today', 'department_revenue_today'], 'secondary_cards' => ['services_count', 'staff_count'], 'main_queue' => 'visit_queue', 'side_cards' => ['services', 'quick_actions', 'stock_usage'], 'trend_blocks' => ['seven_day_activity']],
        'emergency' => ['primary_cards' => ['active_cases', 'critical_cases', 'visits_today', 'department_revenue_today'], 'secondary_cards' => ['services_count', 'staff_count'], 'main_queue' => 'emergency_queue', 'side_cards' => ['services', 'stock_usage', 'quick_actions'], 'trend_blocks' => ['seven_day_activity']],
        'investigation' => ['primary_cards' => ['pending_requests', 'samples_awaiting_acceptance', 'completed_results_today', 'department_revenue_today'], 'secondary_cards' => ['services_count', 'staff_count'], 'main_queue' => 'lab_requests', 'side_cards' => ['services', 'stock_usage', 'recent_results'], 'trend_blocks' => ['seven_day_activity']],
        'radiology' => ['primary_cards' => ['pending_imaging', 'scheduled_imaging', 'completed_imaging_today', 'department_revenue_today'], 'secondary_cards' => ['services_count', 'staff_count'], 'main_queue' => 'radiology_requests', 'side_cards' => ['services', 'stock_usage', 'recent_results'], 'trend_blocks' => ['seven_day_activity']],
        'procedure' => ['primary_cards' => ['pending_requests', 'scheduled', 'completed_today', 'department_revenue_today'], 'secondary_cards' => ['services_count', 'staff_count'], 'main_queue' => 'procedure_queue', 'side_cards' => ['services', 'stock_usage', 'quick_actions'], 'trend_blocks' => ['seven_day_activity']],
        'theatre' => ['primary_cards' => ['pending_requests', 'scheduled', 'in_theatre', 'department_revenue_today'], 'secondary_cards' => ['services_count', 'staff_count'], 'main_queue' => 'procedure_queue', 'side_cards' => ['services', 'stock_usage', 'quick_actions'], 'trend_blocks' => ['seven_day_activity']],
        'pharmacy' => ['primary_cards' => ['pending_prescriptions', 'dispensed_today', 'low_stock', 'department_revenue_today'], 'secondary_cards' => ['services_count', 'staff_count'], 'main_queue' => 'pharmacy_queue', 'side_cards' => ['stock_usage', 'services', 'quick_actions'], 'trend_blocks' => ['seven_day_activity']],
        'inpatient' => ['primary_cards' => ['active_admissions', 'beds_occupied', 'vitals_due', 'discharges_pending'], 'secondary_cards' => ['services_count', 'staff_count'], 'main_queue' => 'admissions_queue', 'side_cards' => ['services', 'stock_usage', 'quick_actions'], 'trend_blocks' => ['seven_day_activity']],
        'nursing' => ['primary_cards' => ['visits_today', 'waiting_queue', 'completed_today', 'activity_today'], 'secondary_cards' => ['services_count', 'staff_count'], 'main_queue' => 'visit_queue', 'side_cards' => ['services', 'quick_actions'], 'trend_blocks' => ['seven_day_activity']],
        'maternity' => ['primary_cards' => ['active_admissions', 'visits_today', 'completed_today', 'services_count'], 'secondary_cards' => ['staff_count', 'stock_items'], 'main_queue' => 'admissions_queue', 'side_cards' => ['services', 'stock_usage', 'quick_actions'], 'trend_blocks' => ['seven_day_activity']],
        'blood_bank' => ['primary_cards' => ['stock_items', 'low_stock', 'pending_requests', 'department_revenue_today'], 'secondary_cards' => ['services_count', 'staff_count'], 'main_queue' => 'lab_requests', 'side_cards' => ['stock_usage', 'services', 'quick_actions'], 'trend_blocks' => ['seven_day_activity']],
        'finance' => ['primary_cards' => ['invoices_today', 'payments_today', 'receivables', 'department_revenue_today'], 'secondary_cards' => ['services_count', 'staff_count'], 'main_queue' => 'financial_queue', 'side_cards' => ['quick_actions', 'restricted_finance'], 'trend_blocks' => ['seven_day_activity']],
        'stores' => ['primary_cards' => ['stock_items', 'low_stock', 'stock_requests', 'stock_issues'], 'secondary_cards' => ['services_count', 'staff_count'], 'main_queue' => 'stock_queue', 'side_cards' => ['stock_usage', 'quick_actions'], 'trend_blocks' => ['seven_day_activity']],
        'records' => ['primary_cards' => ['visits_today', 'appointments_today', 'activity_today', 'staff_count'], 'secondary_cards' => ['services_count'], 'main_queue' => 'department_activity', 'side_cards' => ['quick_actions', 'services'], 'trend_blocks' => ['seven_day_activity']],
    ];

    /**
     * Full personality profile for a department type: card layout merged with the
     * name key and menu-heading template.
     */
    public function for(?DepartmentType $type): array
    {
        $value = $type?->value ?? '';
        $cards = self::PROFILES[$value] ?? self::DEFAULT_PROFILE;

        return array_merge($cards, [
            'menu_heading_template' => $this->menuHeadingTemplateFor($type),
            'name_key' => $this->nameKeyFor($type),
        ]);
    }

    public function menuHeadingTemplateFor(?DepartmentType $type): string
    {
        return self::MENU_HEADINGS[$type?->value ?? ''] ?? 'generic';
    }

    public function nameKeyFor(?DepartmentType $type): string
    {
        return 'departments.dashboards.'.($type?->value ?? 'generic').'.name';
    }

    /** Every department type resolves a card profile (the default covers the rest). */
    public function hasProfileForEveryDepartmentType(): bool
    {
        foreach (DepartmentType::cases() as $type) {
            if (empty($this->for($type)['primary_cards'])) {
                return false;
            }
        }

        return true;
    }
}
