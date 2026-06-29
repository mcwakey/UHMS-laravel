<?php

namespace App\Services\Journey;

use App\Models\JourneyPredictionOutcome;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Phase 9.10 — prediction accuracy metrics from evaluated outcomes. A prediction
 * "calls a breach" when its level is HIGH/CRITICAL.
 *   precision        = TP / (TP + FP)        — of called breaches, how many breached
 *   recall           = TP / (TP + FN)        — of real breaches, how many we caught
 *   false_alarm_rate = FP / (FP + TN)        — of non-breaches, how many we cried wolf
 *   miss_rate        = FN / (FN + TP)        — of real breaches, how many we missed
 * All zero-denominator safe. Capability-scoped (scope_types); cached per scope+filter.
 */
class JourneyPredictionAccuracyService
{
    public function accuracySummary(array $filters = []): array
    {
        return $this->cached('summary', $filters, function () use ($filters) {
            $agg = $this->base($filters)->evaluated()->whereNotNull('actual_breached')->selectRaw(
                "COUNT(*) total,
                 SUM(CASE WHEN predicted_risk_level IN ('high','critical') AND actual_breached = 1 THEN 1 ELSE 0 END) tp,
                 SUM(CASE WHEN predicted_risk_level IN ('high','critical') AND actual_breached = 0 THEN 1 ELSE 0 END) fp,
                 SUM(CASE WHEN predicted_risk_level NOT IN ('high','critical') AND actual_breached = 1 THEN 1 ELSE 0 END) fn,
                 SUM(CASE WHEN predicted_risk_level NOT IN ('high','critical') AND actual_breached = 0 THEN 1 ELSE 0 END) tn,
                 AVG(CASE WHEN predicted_remaining_minutes IS NOT NULL AND actual_minutes_to_resolve IS NOT NULL THEN ABS(predicted_remaining_minutes - actual_minutes_to_resolve) END) eta_err"
            )->first();

            $tp = (int) $agg->tp;
            $fp = (int) $agg->fp;
            $fn = (int) $agg->fn;
            $tn = (int) $agg->tn;
            $total = $tp + $fp + $fn + $tn;

            return [
                'prediction_count' => (int) $this->base($filters)->count(),
                'evaluated' => $total,
                'true_positive' => $tp,
                'false_positive' => $fp,
                'false_negative' => $fn,
                'true_negative' => $tn,
                'precision' => $this->rate($tp, $tp + $fp),
                'recall' => $this->rate($tp, $tp + $fn),
                'false_alarm_rate' => $this->rate($fp, $fp + $tn),
                'miss_rate' => $this->rate($fn, $fn + $tp),
                'overall_accuracy' => $this->rate($tp + $tn, $total),
                'eta_error_avg' => $agg->eta_err !== null ? (int) round((float) $agg->eta_err) : null,
                'has_data' => $total > 0,
            ];
        });
    }

    /**
     * One-query overall accuracy for the dashboard chip (correct = TP + TN).
     *
     * @return ?array{accuracy:float,evaluated:int}
     */
    public function dashboardAccuracy(?string $type): ?array
    {
        $filters = ['scope_types' => $type ? [$type] : null, '_ttl' => (int) config('journey.prediction.cache.summary_ttl', 60)];

        return $this->cached('dash', $filters, function () use ($filters) {
            $agg = $this->base($filters)->evaluated()->whereNotNull('actual_breached')->selectRaw(
                "COUNT(*) total,
                 SUM(CASE WHEN (CASE WHEN predicted_risk_level IN ('high','critical') THEN 1 ELSE 0 END) = actual_breached THEN 1 ELSE 0 END) correct"
            )->first();
            $total = (int) $agg->total;

            return $total > 0 ? ['accuracy' => round((int) $agg->correct / $total * 100, 1), 'evaluated' => $total] : null;
        });
    }

    /** Accuracy split by a dimension column (confidence | predicted_risk_level). */
    public function breakdown(array $filters, string $dimension): array
    {
        $column = $dimension === 'risk_level' ? 'predicted_risk_level' : 'confidence';

        return $this->cached('breakdown:'.$column, $filters, function () use ($filters, $column) {
            return $this->base($filters)->evaluated()->whereNotNull('actual_breached')
                ->selectRaw("$column as bucket, COUNT(*) total,
                    SUM(CASE WHEN predicted_risk_level IN ('high','critical') AND actual_breached = 1 THEN 1 ELSE 0 END) tp,
                    SUM(CASE WHEN predicted_risk_level IN ('high','critical') AND actual_breached = 0 THEN 1 ELSE 0 END) fp")
                ->groupBy($column)
                ->get()
                ->map(fn ($r) => [
                    'bucket' => (string) $r->bucket,
                    'evaluated' => (int) $r->total,
                    'precision' => $this->rate((int) $r->tp, (int) $r->tp + (int) $r->fp),
                ])->all();
        });
    }

    /** Paths with the most prediction errors (FP + FN). */
    public function worstPaths(array $filters): array
    {
        return $this->cached('worstpaths', $filters, function () use ($filters) {
            return $this->base($filters)->evaluated()->whereNotNull('actual_breached')
                ->whereNotNull('from_department_type')->whereNotNull('to_department_type')
                ->selectRaw("from_department_type, to_department_type, COUNT(*) total,
                    SUM(CASE WHEN predicted_risk_level IN ('high','critical') AND actual_breached = 0 THEN 1 ELSE 0 END) fp,
                    SUM(CASE WHEN predicted_risk_level NOT IN ('high','critical') AND actual_breached = 1 THEN 1 ELSE 0 END) fn")
                ->groupBy('from_department_type', 'to_department_type')
                ->get()
                ->map(fn ($r) => [
                    'from' => $r->from_department_type, 'to' => $r->to_department_type,
                    'evaluated' => (int) $r->total, 'errors' => (int) $r->fp + (int) $r->fn,
                ])
                ->filter(fn ($r) => $r['errors'] > 0)
                ->sortByDesc('errors')->values()->all();
        });
    }

    /** @return array<string, array{current:float|int,previous:float|int,direction:string,delta_pct:?float}> */
    public function comparePeriods(array $current, array $previous): array
    {
        $now = $this->accuracySummary($current);
        $then = $this->accuracySummary($previous);
        $out = [];
        foreach (['precision', 'recall', 'false_alarm_rate', 'eta_error_avg'] as $key) {
            $c = $now[$key] ?? 0;
            $p = $then[$key] ?? 0;
            $out[$key] = [
                'current' => $c, 'previous' => $p,
                'direction' => $c > $p ? 'up' : ($c < $p ? 'down' : 'flat'),
                'delta_pct' => ($p > 0) ? round((($c - $p) / $p) * 100, 1) : null,
            ];
        }

        return $out;
    }

    private function base(array $filters): Builder
    {
        $from = $filters['date_from'] ?? Carbon::today()->subDays(30)->toDateString();
        $to = $filters['date_to'] ?? Carbon::today()->toDateString();

        $query = JourneyPredictionOutcome::query()->forDateRange($from, $to);
        if (! empty($filters['cause'])) {
            $query->where('cause', $filters['cause']);
        }
        if (! empty($filters['confidence'])) {
            $query->where('confidence', $filters['confidence']);
        }
        if (! empty($filters['risk_level'])) {
            $query->where('predicted_risk_level', $filters['risk_level']);
        }
        if (! empty($filters['scope_types'])) {
            $types = $filters['scope_types'];
            $query->where(fn ($q) => $q->whereIn('from_department_type', $types)->orWhereIn('to_department_type', $types));
        }

        return $query;
    }

    private function cached(string $name, array $filters, callable $callback): mixed
    {
        $ttl = (int) ($filters['_ttl'] ?? config('journey.analytics.report_cache_ttl', 1800));
        if ($ttl <= 0) {
            return $callback();
        }

        return Cache::remember('journey:accuracy:'.$name.':'.Str::substr(md5(json_encode($filters)), 0, 16), $ttl, $callback);
    }

    private function rate(int $numerator, int $denominator): float
    {
        return $denominator > 0 ? round(($numerator / $denominator) * 100, 1) : 0.0;
    }
}
