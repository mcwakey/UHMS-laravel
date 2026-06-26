<?php

namespace App\Services\Dashboard;

use App\Enums\DepartmentType;
use App\Models\Department;
use App\Models\InvoiceItem;
use App\Models\ServiceCatalog;
use App\Models\VisitConsultationRoute;

/**
 * Department-TYPE metrics. The existing reports aggregate by individual
 * department_id; this registry rolls those up by department type so the system
 * can answer "how much emergency revenue / investigation volume" etc.
 *
 * Metrics here are GENERIC (they apply to every type and are derived purely from
 * department_id joins). Type-specific metrics can be layered on later via
 * {@see typeSpecificMetrics()} without changing callers.
 */
class DepartmentMetricsRegistry
{
    /**
     * Per-type rollup for every department type that has at least one department.
     *
     * @param  array{date_from?:?string, date_to?:?string}  $filters
     * @return array{rows: array<int, array<string, mixed>>, totals: array<string, mixed>, date_from: ?string, date_to: ?string}
     */
    public function summary(array $filters = []): array
    {
        // Portable COUNT(*) grouped by the plain `type` string column. Use the
        // base builder (toBase) so the enum cast is NOT applied to the `type`
        // key — a legacy/unknown type value must not throw, just be ignored below.
        $countsByType = Department::query()
            ->whereNotNull('type')
            ->selectRaw('type, COUNT(*) as total')
            ->groupBy('type')
            ->toBase()
            ->pluck('total', 'type');

        $rows = [];
        foreach ($countsByType as $typeValue => $total) {
            $type = DepartmentType::tryFrom((string) $typeValue);
            if (! $type) {
                continue; // ignore legacy / unknown type values
            }
            $rows[] = $this->metricsForType($type, $filters);
        }

        usort($rows, fn ($a, $b) => $b['departments'] <=> $a['departments']);

        $sum = fn (string $key) => array_sum(array_column($rows, $key));

        return [
            'rows' => $rows,
            'totals' => [
                'departments' => $sum('departments'),
                'active_departments' => $sum('active_departments'),
                'services' => $sum('services'),
                'consultation_routes' => $sum('consultation_routes'),
                'revenue' => round($sum('revenue'), 2),
            ],
            'date_from' => $filters['date_from'] ?? null,
            'date_to' => $filters['date_to'] ?? null,
        ];
    }

    /**
     * Metrics for a single department type.
     *
     * @param  array{date_from?:?string, date_to?:?string}  $filters
     * @return array<string, mixed>
     */
    public function metricsForType(DepartmentType $type, array $filters = []): array
    {
        $from = $filters['date_from'] ?? null;
        $to = $filters['date_to'] ?? null;

        $departmentIds = Department::where('type', $type->value)->pluck('id');
        $hasDepts = $departmentIds->isNotEmpty();

        $services = $hasDepts
            ? ServiceCatalog::whereIn('department_id', $departmentIds)->count()
            : 0;

        $routes = $hasDepts
            ? VisitConsultationRoute::whereIn('department_id', $departmentIds)
                ->when($from, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
                ->when($to, fn ($q, $d) => $q->whereDate('created_at', '<=', $d))
                ->count()
            : 0;

        $revenue = $hasDepts
            ? (float) InvoiceItem::whereIn('department_id', $departmentIds)
                ->when($from, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
                ->when($to, fn ($q, $d) => $q->whereDate('created_at', '<=', $d))
                ->sum('total_price')
            : 0.0;

        return array_merge([
            'type' => $type->value,
            'label' => $type->translatedLabel(),
            'color' => $type->color(),
            'clinical' => $type->isClinical(),
            'departments' => $departmentIds->count(),
            'active_departments' => $hasDepts
                ? Department::where('type', $type->value)->where('status', 'active')->count()
                : 0,
            'services' => $services,
            'consultation_routes' => $routes,
            'revenue' => round($revenue, 2),
        ], $this->typeSpecificMetrics($type, $departmentIds->all(), $filters));
    }

    /**
     * Extension point for metrics that only make sense for certain types
     * (e.g. bed occupancy for inpatient, transfusions for blood_bank). Empty for
     * now — the generic metrics above already cover every type.
     *
     * @param  array<int, int>  $departmentIds
     * @return array<string, mixed>
     */
    protected function typeSpecificMetrics(DepartmentType $type, array $departmentIds, array $filters): array
    {
        return [];
    }
}
