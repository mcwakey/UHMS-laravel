<?php

namespace App\Services\Department;

use App\Enums\DepartmentType;

/**
 * Which charts each department type's showcase actually renders. The chart service
 * builds only these, instead of all seven (four of which no showcase ever used:
 * service_usage_trend, stock_usage_trend, department_workload_by_day, revenue_trend).
 *
 * `stock_status_breakdown` is produced by the data service (it reuses stockCount),
 * so it is listed here only to advertise intent; the data service adds it for the
 * stock-bearing types.
 */
class DepartmentDashboardChartRegistry
{
    public const DEFAULT_CHARTS = ['activity_trend', 'queue_status_breakdown'];

    /**
     * Charts produced by the chart service for a department type.
     *
     * @return list<string>
     */
    public function chartServiceChartsFor(?DepartmentType $type): array
    {
        return match ($type) {
            // Diagnostics show a request-status donut instead of a visit-queue donut.
            DepartmentType::INVESTIGATION,
            DepartmentType::RADIOLOGY,
            DepartmentType::BLOOD_BANK => ['activity_trend', 'request_status_breakdown'],

            // Stock/finance showcases use only the activity-trend chart (+ a donut the
            // data service supplies for stock).
            DepartmentType::PHARMACY,
            DepartmentType::STORES,
            DepartmentType::FINANCE,
            DepartmentType::ADMINISTRATIVE => ['activity_trend'],

            default => self::DEFAULT_CHARTS,
        };
    }

    /** Whether this type's data service should also build the stock-status donut. */
    public function needsStockStatus(?DepartmentType $type): bool
    {
        return in_array($type, [DepartmentType::PHARMACY, DepartmentType::STORES, DepartmentType::BLOOD_BANK], true);
    }
}
