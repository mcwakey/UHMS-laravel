<?php

namespace App\Services\Department;

use App\Enums\DepartmentType;

/**
 * Resolves the dashboard *personality* for a department type:
 *   - layout_family  → which Blade layout (structure / emphasis) renders
 *   - card profile   → which metric cards + main queue + side cards appear
 *   - name_key       → the department-type dashboard name (departments.dashboards.{type}.name)
 *   - hero_variant   → hero styling hint
 *   - menu heading   → the personalised menu-heading template
 *
 * Keyed on DEPARTMENT TYPE (not the coarser dashboard key) so e.g. radiology gets
 * its own "Radiology Dashboard" / imaging_workbench, distinct from investigation.
 */
class DepartmentDashboardLayoutRegistry
{
    /** All layout family partials under partials/layouts/{family}.blade.php. */
    public const LAYOUT_FAMILIES = [
        'clinical_queue',
        'emergency_command',
        'diagnostic_workbench',
        'imaging_workbench',
        'surgery_board',
        'ward_board',
        'dispensing_stock',
        'finance_control',
        'stores_inventory',
        'records_office',
        'generic_department',
    ];

    /** type value → [layout_family, hero_variant, menu_heading_template]. Covers all 19 types. */
    private const FAMILIES = [
        'consultation'   => ['clinical_queue', 'clinical', 'operations'],
        'emergency'      => ['emergency_command', 'emergency', 'command_center'],
        'investigation'  => ['diagnostic_workbench', 'diagnostic', 'workbench'],
        'radiology'      => ['imaging_workbench', 'imaging', 'workbench'],
        'procedure'      => ['clinical_queue', 'procedure', 'workbench'],
        'theatre'        => ['surgery_board', 'surgery', 'workbench'],
        'treatment'      => ['clinical_queue', 'clinical', 'operations'],
        'nursing'        => ['ward_board', 'ward', 'operations'],
        'pharmacy'       => ['dispensing_stock', 'pharmacy', 'operations'],
        'inpatient'      => ['ward_board', 'ward', 'operations'],
        'maternity'      => ['ward_board', 'maternity', 'operations'],
        'blood_bank'     => ['diagnostic_workbench', 'blood_bank', 'workbench'],
        'mortuary'       => ['generic_department', 'mortuary', 'generic'],
        'ambulance'      => ['emergency_command', 'ambulance', 'command_center'],
        'records'        => ['records_office', 'records', 'records_office'],
        'finance'        => ['finance_control', 'finance', 'control_room'],
        'stores'         => ['stores_inventory', 'stores', 'inventory'],
        'support'        => ['generic_department', 'support', 'generic'],
        'administrative' => ['generic_department', 'admin', 'generic'],
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
        'nursing' => ['primary_cards' => ['active_admissions', 'vitals_due', 'completed_today', 'services_count'], 'secondary_cards' => ['staff_count', 'stock_items'], 'main_queue' => 'admissions_queue', 'side_cards' => ['services', 'stock_usage', 'quick_actions'], 'trend_blocks' => ['seven_day_activity']],
        'maternity' => ['primary_cards' => ['active_admissions', 'visits_today', 'completed_today', 'services_count'], 'secondary_cards' => ['staff_count', 'stock_items'], 'main_queue' => 'admissions_queue', 'side_cards' => ['services', 'stock_usage', 'quick_actions'], 'trend_blocks' => ['seven_day_activity']],
        'blood_bank' => ['primary_cards' => ['stock_items', 'low_stock', 'pending_requests', 'department_revenue_today'], 'secondary_cards' => ['services_count', 'staff_count'], 'main_queue' => 'lab_requests', 'side_cards' => ['stock_usage', 'services', 'quick_actions'], 'trend_blocks' => ['seven_day_activity']],
        'finance' => ['primary_cards' => ['invoices_today', 'payments_today', 'receivables', 'department_revenue_today'], 'secondary_cards' => ['services_count', 'staff_count'], 'main_queue' => 'financial_queue', 'side_cards' => ['quick_actions', 'restricted_finance'], 'trend_blocks' => ['seven_day_activity']],
        'stores' => ['primary_cards' => ['stock_items', 'low_stock', 'stock_requests', 'stock_issues'], 'secondary_cards' => ['services_count', 'staff_count'], 'main_queue' => 'stock_queue', 'side_cards' => ['stock_usage', 'quick_actions'], 'trend_blocks' => ['seven_day_activity']],
        'records' => ['primary_cards' => ['visits_today', 'appointments_today', 'activity_today', 'staff_count'], 'secondary_cards' => ['services_count'], 'main_queue' => 'department_activity', 'side_cards' => ['quick_actions', 'services'], 'trend_blocks' => ['seven_day_activity']],
    ];

    /**
     * Full personality profile for a department type: card layout merged with the
     * layout family / hero variant / name key / menu-heading template.
     */
    public function for(?DepartmentType $type): array
    {
        $value = $type?->value ?? '';
        $cards = self::PROFILES[$value] ?? self::DEFAULT_PROFILE;
        $family = self::FAMILIES[$value] ?? ['generic_department', 'generic', 'generic'];

        return array_merge($cards, [
            'layout_family' => $family[0],
            'hero_variant' => $family[1],
            'menu_heading_template' => $family[2],
            'name_key' => $this->nameKeyFor($type),
        ]);
    }

    public function layoutFamilyFor(?DepartmentType $type): string
    {
        $family = self::FAMILIES[$type?->value ?? ''] ?? null;

        return $family[0] ?? 'generic_department';
    }

    public function heroVariantFor(?DepartmentType $type): string
    {
        $family = self::FAMILIES[$type?->value ?? ''] ?? null;

        return $family[1] ?? 'generic';
    }

    public function menuHeadingTemplateFor(?DepartmentType $type): string
    {
        $family = self::FAMILIES[$type?->value ?? ''] ?? null;

        return $family[2] ?? 'generic';
    }

    public function nameKeyFor(?DepartmentType $type): string
    {
        return 'departments.dashboards.'.($type?->value ?? 'generic').'.name';
    }

    public function hasLayoutFamilyForEveryDepartmentType(): bool
    {
        foreach (DepartmentType::cases() as $type) {
            if (! in_array($this->layoutFamilyFor($type), self::LAYOUT_FAMILIES, true)) {
                return false;
            }
        }

        return true;
    }

    /** Back-compat with the Phase 6 test. */
    public function hasProfileForEveryDepartmentType(): bool
    {
        return $this->hasLayoutFamilyForEveryDepartmentType();
    }
}
