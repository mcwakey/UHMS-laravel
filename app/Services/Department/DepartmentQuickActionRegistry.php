<?php

namespace App\Services\Department;

use App\Enums\DepartmentType;

/**
 * Type-specific quick actions for a department dashboard. Each entry is
 * [label_key, icon, route_name, capability|null]; the data service filters by
 * capability (the same vocabulary as metric/drilldown visibility) and route
 * existence, and resolves the localized label
 * (`dashboards.department.actions.{label_key}`). Routes are verified to exist;
 * any that don't are dropped gracefully.
 */
class DepartmentQuickActionRegistry
{
    /**
     * @return list<array{0:string,1:string,2:string,3:?string}>
     */
    public function for(?DepartmentType $type): array
    {
        return match ($type) {
            DepartmentType::CONSULTATION, DepartmentType::TREATMENT, DepartmentType::PROCEDURE => [
                ['new_visit', 'ti-user-plus', 'admin.visits.create', null],
                ['queue_management', 'ti-list-numbers', 'admin.visits.index', 'consultation_access'],
                ['appointments', 'ti-calendar-event', 'admin.appointments.index', 'consultation_access'],
                ['view_services', 'ti-list-details', 'admin.services.index', null],
            ],
            DepartmentType::EMERGENCY, DepartmentType::AMBULANCE => [
                ['new_visit', 'ti-ambulance', 'admin.visits.create', null],
                ['queue_management', 'ti-urgent', 'admin.visits.index', 'consultation_access'],
                ['view_services', 'ti-list-details', 'admin.services.index', null],
            ],
            DepartmentType::INVESTIGATION, DepartmentType::RADIOLOGY, DepartmentType::BLOOD_BANK => [
                ['view_requests', 'ti-clipboard-list', 'admin.lab.requests.index', 'investigation_access'],
                ['view_results', 'ti-file-text', 'admin.lab.results.index', 'investigation_access'],
                ['view_services', 'ti-list-details', 'admin.services.index', null],
            ],
            DepartmentType::PHARMACY => [
                ['dispense', 'ti-prescription', 'admin.pharmacy.dispensing.index', 'pharmacy_access'],
                ['view_stock', 'ti-packages', 'admin.store.stock.balances', 'stock_access'],
                ['view_services', 'ti-list-details', 'admin.services.index', null],
            ],
            DepartmentType::STORES => [
                ['view_stock', 'ti-packages', 'admin.store.stock.balances', 'stock_access'],
                ['requisitions', 'ti-clipboard-check', 'admin.store.stock-requisitions.index', 'stock_access'],
                ['view_services', 'ti-list-details', 'admin.services.index', null],
            ],
            DepartmentType::THEATRE => [
                ['queue_management', 'ti-list-numbers', 'admin.visits.index', 'consultation_access'],
                ['view_services', 'ti-list-details', 'admin.services.index', null],
            ],
            DepartmentType::INPATIENT, DepartmentType::MATERNITY, DepartmentType::NURSING => [
                ['admissions', 'ti-bed', 'admin.admissions.index', 'ward_access'],
                ['queue_management', 'ti-list-numbers', 'admin.visits.index', 'consultation_access'],
                ['view_services', 'ti-list-details', 'admin.services.index', null],
            ],
            DepartmentType::FINANCE, DepartmentType::ADMINISTRATIVE => [
                ['revenue', 'ti-cash-banknote', 'admin.reports.daily-collection', 'financial_access'],
                ['invoices', 'ti-receipt', 'admin.billing.invoices.index', 'financial_access'],
                ['payments', 'ti-credit-card', 'admin.billing.payments.index', 'financial_access'],
            ],
            DepartmentType::RECORDS => [
                ['queue_management', 'ti-list-numbers', 'admin.visits.index', 'consultation_access'],
                ['appointments', 'ti-calendar-event', 'admin.appointments.index', 'consultation_access'],
                ['view_services', 'ti-list-details', 'admin.services.index', null],
            ],
            default => [
                ['view_services', 'ti-list-details', 'admin.services.index', null],
                ['queue_management', 'ti-list-numbers', 'admin.visits.index', 'consultation_access'],
            ],
        };
    }
}
