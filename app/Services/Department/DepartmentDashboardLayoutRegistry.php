<?php

namespace App\Services\Department;

use App\Enums\DepartmentType;

class DepartmentDashboardLayoutRegistry
{
    private const DEFAULT_PROFILE = [
        'primary_cards' => ['services_count', 'staff_count', 'visits_today', 'activity_today'],
        'secondary_cards' => ['appointments_today', 'revenue_today'],
        'main_queue' => 'department_activity',
        'side_cards' => ['services', 'stock_usage', 'quick_actions'],
        'trend_blocks' => ['seven_day_activity'],
    ];

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
        'finance' => ['primary_cards' => ['invoices_today', 'payments_today', 'receivables', 'department_revenue_today'], 'secondary_cards' => ['services_count', 'staff_count'], 'main_queue' => 'financial_queue', 'side_cards' => ['quick_actions', 'restricted_finance'], 'trend_blocks' => ['seven_day_activity']],
        'stores' => ['primary_cards' => ['stock_items', 'low_stock', 'stock_requests', 'stock_issues'], 'secondary_cards' => ['services_count', 'staff_count'], 'main_queue' => 'stock_queue', 'side_cards' => ['stock_usage', 'quick_actions'], 'trend_blocks' => ['seven_day_activity']],
    ];

    public function for(?DepartmentType $type): array
    {
        return self::PROFILES[$type?->value ?? ''] ?? self::DEFAULT_PROFILE;
    }

    public function hasProfileForEveryDepartmentType(): bool
    {
        foreach (DepartmentType::cases() as $type) {
            if (! isset(self::PROFILES[$type->value]) && self::DEFAULT_PROFILE === []) {
                return false;
            }
        }

        return true;
    }
}
