<?php

namespace App\Services\Department;

use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Shared daily-trend source. Returns an ordered N-day series (oldest → today)
 * using a single `GROUP BY DATE(...)` query instead of N per-day queries. Used by
 * both the chart service (trend charts) and the data service (KPI sparklines), so
 * the same shape of work is computed one way, once.
 */
class DepartmentTrendRepository
{
    /**
     * @param  array{0:string,1:array<int,string>}|null  $filter  [column, values] to whereIn
     * @return array<int, int|float>  values for [today-(days-1) .. today]
     */
    public function dailySeries(
        string $table,
        string $dateColumn,
        ?string $departmentColumn,
        ?int $departmentId,
        int $days = 7,
        string $agg = 'count',
        ?string $sumColumn = null,
        ?array $filter = null,
    ): array {
        $zero = array_fill(0, $days, $agg === 'sum' ? 0.0 : 0);

        if (! DepartmentSchemaCache::hasColumn($table, $dateColumn)) {
            return $zero;
        }
        if ($agg === 'sum' && (! $sumColumn || ! DepartmentSchemaCache::hasColumn($table, $sumColumn))) {
            return $zero;
        }

        $start = today()->subDays($days - 1)->toDateString();
        $aggExpr = $agg === 'sum' ? 'SUM('.$table.'.'.$sumColumn.')' : 'COUNT(*)';

        try {
            $query = DB::table($table)
                ->selectRaw('DATE('.$table.'.'.$dateColumn.') as d, '.$aggExpr.' as v')
                ->whereDate($table.'.'.$dateColumn, '>=', $start);

            if ($departmentColumn && $departmentId && DepartmentSchemaCache::hasColumn($table, $departmentColumn)) {
                $query->where($table.'.'.$departmentColumn, $departmentId);
            }
            if ($filter !== null && DepartmentSchemaCache::hasColumn($table, $filter[0])) {
                $query->whereIn($table.'.'.$filter[0], $filter[1]);
            }

            $rows = $query->groupBy('d')->pluck('v', 'd');
        } catch (Throwable) {
            return $zero;
        }

        $series = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = today()->subDays($i)->toDateString();
            $value = $rows[$date] ?? 0;
            $series[] = $agg === 'sum' ? round((float) $value, 2) : (int) $value;
        }

        return $series;
    }

    /**
     * Daily series plus matching `d M` labels — convenient for chart datasets.
     *
     * @return array{labels: array<int,string>, values: array<int, int|float>}
     */
    public function dailySeriesWithLabels(
        string $table,
        string $dateColumn,
        ?string $departmentColumn,
        ?int $departmentId,
        int $days = 7,
        string $agg = 'count',
        ?string $sumColumn = null,
        ?array $filter = null,
    ): array {
        $values = $this->dailySeries($table, $dateColumn, $departmentColumn, $departmentId, $days, $agg, $sumColumn, $filter);

        $labels = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $labels[] = today()->subDays($i)->format('d M');
        }

        return ['labels' => $labels, 'values' => $values];
    }
}
