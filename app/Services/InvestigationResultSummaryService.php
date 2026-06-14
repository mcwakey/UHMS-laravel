<?php

namespace App\Services;

use App\Models\LabResult;
use App\Models\ServiceCatalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Tally / aggregate overall investigation results for reporting.
 *
 * The shape of the summary depends on the investigation's configured overall
 * result type. Numeric aggregation is never mixed with text results — each type
 * has its own dedicated summary path. All counting reads the canonical columns
 * populated at result entry (overall_result_*), so values stay locale-neutral.
 */
class InvestigationResultSummaryService
{
    /**
     * Build a summary for a single investigation service over an optional window.
     *
     * @return array{type:string, ...}
     */
    public function summarise(ServiceCatalog $service, ?Carbon $from = null, ?Carbon $to = null): array
    {
        $type = $service->overallResultType();

        return match ($type) {
            ServiceCatalog::OVERALL_RESULT_POSITIVE_NEGATIVE => $this->outcomeSummary($this->baseQuery($service, $from, $to)),
            ServiceCatalog::OVERALL_RESULT_BOOLEAN           => $this->booleanSummary($this->baseQuery($service, $from, $to)),
            ServiceCatalog::OVERALL_RESULT_NUMERIC           => $this->numericSummary($this->baseQuery($service, $from, $to), $service),
            default                                          => $this->freeTextSummary($this->baseQuery($service, $from, $to)),
        };
    }

    /**
     * All recorded results for a service (joined to its request items).
     */
    private function baseQuery(ServiceCatalog $service, ?Carbon $from, ?Carbon $to): Builder
    {
        $query = LabResult::query()
            ->join('lab_request_items', 'lab_results.lab_request_item_id', '=', 'lab_request_items.id')
            ->where('lab_request_items.service_id', $service->id)
            ->select('lab_results.*');

        if ($from) {
            $query->where('lab_results.performed_at', '>=', $from);
        }
        if ($to) {
            $query->where('lab_results.performed_at', '<=', $to);
        }

        return $query;
    }

    /** Positive / negative tally. */
    private function outcomeSummary(Builder $query): array
    {
        $positive = (clone $query)->where('lab_results.overall_result_outcome', 'positive')->count();
        $negative = (clone $query)->where('lab_results.overall_result_outcome', 'negative')->count();
        $total = $positive + $negative;

        return [
            'type'           => ServiceCatalog::OVERALL_RESULT_POSITIVE_NEGATIVE,
            'positive_count' => $positive,
            'negative_count' => $negative,
            'total_tested'   => $total,
            'positive_rate'  => $total > 0 ? round($positive / $total * 100, 1) : 0.0,
            'negative_rate'  => $total > 0 ? round($negative / $total * 100, 1) : 0.0,
        ];
    }

    /** True / false tally. */
    private function booleanSummary(Builder $query): array
    {
        $true = (clone $query)->where('lab_results.overall_result_boolean', true)->count();
        $false = (clone $query)->where('lab_results.overall_result_boolean', false)->count();
        $total = $true + $false;

        return [
            'type'         => ServiceCatalog::OVERALL_RESULT_BOOLEAN,
            'true_count'   => $true,
            'false_count'  => $false,
            'total_tested' => $total,
            'true_rate'    => $total > 0 ? round($true / $total * 100, 1) : 0.0,
            'false_rate'   => $total > 0 ? round($false / $total * 100, 1) : 0.0,
        ];
    }

    /** Numeric aggregation: count / average / min / max, plus normal/abnormal if a range is configured. */
    private function numericSummary(Builder $query, ServiceCatalog $service): array
    {
        $rows = (clone $query)->whereNotNull('lab_results.overall_result_numeric');
        $count = (clone $rows)->count();

        $summary = [
            'type'          => ServiceCatalog::OVERALL_RESULT_NUMERIC,
            'count'         => $count,
            'unit'          => $service->overall_result_unit,
            'average_value' => $count > 0 ? round((float) (clone $rows)->avg('lab_results.overall_result_numeric'), 4) : null,
            'minimum_value' => $count > 0 ? (float) (clone $rows)->min('lab_results.overall_result_numeric') : null,
            'maximum_value' => $count > 0 ? (float) (clone $rows)->max('lab_results.overall_result_numeric') : null,
            'normal_count'  => null,
            'abnormal_count' => null,
        ];

        $min = $service->overall_result_min_value;
        $max = $service->overall_result_max_value;

        if ($count > 0 && ($min !== null || $max !== null)) {
            $abnormal = clone $rows;
            $abnormal->where(function ($q) use ($min, $max) {
                if ($min !== null) {
                    $q->where('lab_results.overall_result_numeric', '<', $min);
                }
                if ($max !== null) {
                    $q->orWhere('lab_results.overall_result_numeric', '>', $max);
                }
            });
            $abnormalCount = $abnormal->count();
            $summary['abnormal_count'] = $abnormalCount;
            $summary['normal_count'] = $count - $abnormalCount;
        }

        return $summary;
    }

    /** Free-text: how many results have been recorded. */
    private function freeTextSummary(Builder $query): array
    {
        return [
            'type'            => ServiceCatalog::OVERALL_RESULT_FREE_TEXT,
            'completed_count' => (clone $query)->count(),
        ];
    }
}
