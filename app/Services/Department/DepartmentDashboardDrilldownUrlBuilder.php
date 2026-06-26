<?php

namespace App\Services\Department;

use Illuminate\Support\Facades\Route;

class DepartmentDashboardDrilldownUrlBuilder
{
    public function build(DepartmentDashboardContext $context, string $metricKey): ?string
    {
        $definition = $this->definition($metricKey);

        if (! $definition || ! Route::has($definition['route'])) {
            return null;
        }

        if (($definition['permission'] ?? null) && ! $context->user->can($definition['permission'])) {
            return null;
        }

        $filters = $definition['filters'] ?? [];
        if ($context->department_id) {
            $filters['department_id'] = $context->department_id;
            $filters['scope'] = 'current_department';
        }
        if ($context->department_type) {
            $filters['department_type'] = $context->department_type->value;
        }

        return route($definition['route'], $filters);
    }

    private function definition(string $metricKey): ?array
    {
        $today = today()->toDateString();

        return match ($metricKey) {
            'services_count' => ['route' => 'admin.services.index'],
            'staff_count' => ['route' => 'admin.users.index', 'permission' => 'users.view'],
            'visits_today' => ['route' => 'admin.visits.index', 'permission' => 'visits.view', 'filters' => ['date_range' => "$today to $today"]],
            'appointments_today' => ['route' => 'admin.appointments.index', 'permission' => 'appointments.view', 'filters' => ['date_range' => "$today to $today"]],
            'waiting_queue' => ['route' => 'admin.visits.index', 'permission' => 'visits.view', 'filters' => ['status' => 'waiting']],
            'completed_today', 'completed_results_today', 'completed_imaging_today' => ['route' => 'admin.visits.index', 'permission' => 'visits.view', 'filters' => ['status' => 'completed', 'date_range' => "$today to $today"]],
            'department_revenue_today', 'revenue_today', 'payments_today' => ['route' => 'admin.reports.daily-collection', 'permission' => 'reports.financial_values.view', 'filters' => ['date_from' => $today, 'date_to' => $today]],
            'pending_requests', 'pending_imaging', 'samples_awaiting_acceptance' => ['route' => 'admin.lab.requests.index', 'permission' => 'lab.requests.view', 'filters' => ['status' => 'pending']],
            'scheduled_imaging', 'scheduled' => ['route' => 'admin.appointments.index', 'permission' => 'appointments.view', 'filters' => ['status' => 'scheduled']],
            'pending_prescriptions' => ['route' => 'admin.pharmacy.prescriptions.index', 'permission' => 'pharmacy.prescriptions.view', 'filters' => ['status' => 'pending']],
            'low_stock', 'stock_items' => ['route' => 'admin.store.stock.balances', 'permission' => 'store.purchase.view'],
            'stock_requests' => ['route' => 'admin.store.stock-requisitions.index', 'permission' => 'store.requisition.view'],
            'active_admissions' => ['route' => 'admin.admissions.index', 'permission' => 'admissions.view', 'filters' => ['status' => 'admitted']],
            default => null,
        };
    }
}
