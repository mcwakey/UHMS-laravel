<?php

namespace App\Services\Journey;

use App\Models\JourneyFlowSnapshot;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Reads aggregate flow snapshots into summaries, trends, rankings, the handoff matrix
 * and period comparisons. Never scans live handoffs. Capability scoping is applied via
 * the `scope_types` filter (set by the controller); results are cached per scope+filter
 * so there is no cross-user leakage.
 */
class JourneyAnalyticsQueryService
{
    /** @return array<string, mixed> */
    public function summary(array $filters): array
    {
        return $this->cached('summary', $filters, function () use ($filters) {
            $agg = $this->base($filters)->selectRaw(
                'COALESCE(SUM(handoff_count),0) volume, COALESCE(SUM(breached_count),0) breaches, '.
                'COALESCE(SUM(critical_breach_count),0) critical, COALESCE(SUM(unassigned_count),0) unassigned, '.
                'COALESCE(SUM(assigned_count),0) assigned, COALESCE(SUM(acknowledged_count),0) acknowledged, '.
                'COALESCE(SUM(resolved_count),0) resolved, COALESCE(SUM(total_elapsed_minutes),0) elapsed, '.
                'COALESCE(SUM(total_time_to_acknowledge_minutes),0) ack_minutes, COALESCE(SUM(total_time_to_resolve_minutes),0) resolve_minutes'
            )->first();

            $volume = (int) $agg->volume;
            $resolved = (int) $agg->resolved;
            $lifecycle = (int) $agg->assigned + (int) $agg->acknowledged + $resolved;

            return [
                'handoff_volume' => $volume,
                'sla_breaches' => (int) $agg->breaches,
                'critical_breaches' => (int) $agg->critical,
                'unassigned_count' => (int) $agg->unassigned,
                'resolved_count' => $resolved,
                'breach_rate' => $this->rate((int) $agg->breaches, $volume),
                'resolution_rate' => $this->rate($resolved, $lifecycle),
                'acknowledgement_rate' => $this->rate((int) $agg->acknowledged + $resolved, $lifecycle),
                'time_to_acknowledge_avg' => $this->avg((int) $agg->ack_minutes, $resolved),
                'time_to_resolve_avg' => $this->avg((int) $agg->resolve_minutes, $resolved),
                'avg_wait_minutes' => $this->avg((int) $agg->elapsed, $volume),
            ];
        });
    }

    /** @return list<array{date:string,handoff_count:int,breached_count:int,resolved_count:int}> */
    public function trend(array $filters): array
    {
        return $this->cached('trend', $filters, function () use ($filters) {
            return $this->base($filters)
                ->selectRaw('snapshot_date, SUM(handoff_count) handoff_count, SUM(breached_count) breached_count, SUM(resolved_count) resolved_count')
                ->groupBy('snapshot_date')
                ->orderBy('snapshot_date')
                ->get()
                ->map(fn ($row) => [
                    'date' => (string) $row->snapshot_date,
                    'handoff_count' => (int) $row->handoff_count,
                    'breached_count' => (int) $row->breached_count,
                    'resolved_count' => (int) $row->resolved_count,
                ])->all();
        });
    }

    /** Ranking by department type. $dimension: 'to' = blocking, 'from' = waiting. */
    public function departmentRanking(array $filters, string $dimension = 'to'): array
    {
        $column = $dimension === 'from' ? 'from_department_type' : 'to_department_type';

        return $this->cached('deptrank:'.$dimension, $filters, function () use ($filters, $column, $dimension) {
            return $this->base($filters)
                ->whereNotNull($column)
                ->selectRaw("$column as type, SUM(handoff_count) handoff_count, SUM(breached_count) breached_count, SUM(critical_breach_count) critical_count, SUM(total_elapsed_minutes) elapsed")
                ->groupBy($column)
                ->get()
                ->map(fn ($row) => [
                    'type' => $row->type,
                    'handoff_count' => (int) $row->handoff_count,
                    'breached_count' => (int) $row->breached_count,
                    'critical_count' => (int) $row->critical_count,
                    'avg_wait' => $this->avg((int) $row->elapsed, (int) $row->handoff_count),
                    'breach_rate' => $this->rate((int) $row->breached_count, (int) $row->handoff_count),
                ])
                ->sortByDesc(fn ($r) => $dimension === 'from' ? $r['handoff_count'] : $r['breached_count'])
                ->values()->all();
        });
    }

    /** From → To handoff matrix with SLA + lifecycle metrics. */
    public function handoffPathRanking(array $filters): array
    {
        return $this->cached('pathrank', $filters, function () use ($filters) {
            return $this->base($filters)
                ->whereNotNull('from_department_type')->whereNotNull('to_department_type')
                ->selectRaw('from_department_type, to_department_type, SUM(handoff_count) handoff_count, SUM(breached_count) breached_count, '.
                    'SUM(critical_breach_count) critical_count, SUM(total_elapsed_minutes) elapsed, '.
                    'SUM(resolved_count) resolved, SUM(total_time_to_acknowledge_minutes) ack_minutes, SUM(total_time_to_resolve_minutes) resolve_minutes')
                ->groupBy('from_department_type', 'to_department_type')
                ->get()
                ->map(fn ($row) => [
                    'from' => $row->from_department_type,
                    'to' => $row->to_department_type,
                    'handoff_count' => (int) $row->handoff_count,
                    'breached_count' => (int) $row->breached_count,
                    'critical_count' => (int) $row->critical_count,
                    'avg_wait' => $this->avg((int) $row->elapsed, (int) $row->handoff_count),
                    'avg_ack' => $this->avg((int) $row->ack_minutes, (int) $row->resolved),
                    'avg_resolve' => $this->avg((int) $row->resolve_minutes, (int) $row->resolved),
                    'resolved_count' => (int) $row->resolved,
                    'breach_rate' => $this->rate((int) $row->breached_count, (int) $row->handoff_count),
                ])
                ->filter(fn ($r) => $r['handoff_count'] > 0 || $r['resolved_count'] > 0)
                ->sortByDesc('breached_count')
                ->values()->all();
        });
    }

    public function causeBreakdown(array $filters): array
    {
        return $this->cached('cause', $filters, function () use ($filters) {
            return $this->base($filters)
                // Alias avoids the model's enum cast on the `cause` column.
                ->selectRaw('cause as cause_key, SUM(handoff_count) handoff_count, SUM(breached_count) breached_count')
                ->groupBy('cause')
                ->get()
                ->map(fn ($row) => ['cause' => (string) $row->cause_key, 'handoff_count' => (int) $row->handoff_count, 'breached_count' => (int) $row->breached_count])
                ->filter(fn ($r) => $r['handoff_count'] > 0)
                ->sortByDesc('handoff_count')->values()->all();
        });
    }

    public function slaBreakdown(array $filters): array
    {
        return $this->cached('sla', $filters, function () use ($filters) {
            return $this->base($filters)
                ->selectRaw('sla_status, SUM(handoff_count) handoff_count')
                ->groupBy('sla_status')
                ->get()
                ->map(fn ($row) => ['sla_status' => $row->sla_status, 'handoff_count' => (int) $row->handoff_count])
                ->filter(fn ($r) => $r['handoff_count'] > 0)
                ->sortByDesc('handoff_count')->values()->all();
        });
    }

    /** @return array<string, array{current:float|int,previous:float|int,direction:string,delta_pct:?float}> */
    public function comparePeriods(array $current, array $previous): array
    {
        $now = $this->summary($current);
        $then = $this->summary($previous);
        $keys = ['handoff_volume', 'breach_rate', 'resolution_rate', 'avg_wait_minutes', 'critical_breaches'];
        $out = [];
        foreach ($keys as $key) {
            $c = $now[$key] ?? 0;
            $p = $then[$key] ?? 0;
            $out[$key] = [
                'current' => $c,
                'previous' => $p,
                'direction' => $c > $p ? 'up' : ($c < $p ? 'down' : 'flat'),
                'delta_pct' => $p > 0 ? round((($c - $p) / $p) * 100, 1) : null,
            ];
        }

        return $out;
    }

    // ------------------------------------------------------------------

    private function base(array $filters): Builder
    {
        $from = $filters['date_from'] ?? Carbon::today()->subDays((int) config('journey.analytics.default_days', 7))->toDateString();
        $to = $filters['date_to'] ?? Carbon::today()->toDateString();

        $query = JourneyFlowSnapshot::query()
            ->forGranularity($filters['granularity'] ?? 'day')
            ->forDateRange($from, $to);

        if (! empty($filters['cause'])) {
            $query->where('cause', $filters['cause']);
        }
        if (! empty($filters['from_department_type'])) {
            $query->where('from_department_type', $filters['from_department_type']);
        }
        if (! empty($filters['to_department_type'])) {
            $query->where('to_department_type', $filters['to_department_type']);
        }
        if (! empty($filters['sla_status'])) {
            $query->where('sla_status', $filters['sla_status']);
        }
        // Capability scope: restrict to allowed department types (either side).
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
        // Scope + filters are part of the key → no cross-user/scope leakage.
        $key = 'journey:analytics:'.$name.':'.Str::substr(md5(json_encode($filters)), 0, 16);

        return Cache::remember($key, $ttl, $callback);
    }

    private function rate(int $numerator, int $denominator): float
    {
        return $denominator > 0 ? round(($numerator / $denominator) * 100, 1) : 0.0;
    }

    private function avg(int $total, int $count): int
    {
        return $count > 0 ? (int) round($total / $count) : 0;
    }
}
