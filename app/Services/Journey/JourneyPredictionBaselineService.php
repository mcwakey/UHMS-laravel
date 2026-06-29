<?php

namespace App\Services\Journey;

use App\Data\Journey\JourneyHandoff;
use App\Enums\JourneyDelayCause;
use App\Models\JourneyFlowSnapshot;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Phase 9.9 — historical baselines from Phase 9.8 aggregate snapshots, with a safe
 * fallback chain (path → to+cause → cause → global → config). The full map is built
 * once and cached, so a worklist scores N rows without N snapshot queries. No
 * patient-level data.
 */
class JourneyPredictionBaselineService
{
    /**
     * @return array{path:array,to_cause:array,cause:array,global:array}
     */
    public function baselineMap(): array
    {
        $days = (int) config('journey.prediction.history_days', 30);
        $ttl = (int) config('journey.prediction.cache.baseline_ttl', 1800);

        return Cache::remember('journey:prediction:baselines:'.$days, $ttl, function () use ($days) {
            $from = Carbon::today()->subDays($days)->toDateString();
            $to = Carbon::today()->toDateString();

            $rows = JourneyFlowSnapshot::query()
                ->forDateRange($from, $to)
                ->selectRaw('from_department_id fi, to_department_id ti, from_department_type ft, to_department_type tt, cause as cause_key, '.
                    'SUM(handoff_count) hc, SUM(breached_count) bc, SUM(critical_breach_count) cc, SUM(total_elapsed_minutes) el, '.
                    'SUM(resolved_count) rc, SUM(COALESCE(total_time_to_resolve_minutes,0)) tr, SUM(COALESCE(total_time_to_acknowledge_minutes,0)) ta')
                ->groupBy('from_department_id', 'to_department_id', 'from_department_type', 'to_department_type', 'cause')
                ->get();

            $pathId = [];
            $toIdCause = [];
            $path = [];
            $toCause = [];
            $cause = [];
            $global = $this->blank();

            foreach ($rows as $row) {
                $this->add($global, $row);
                $cKey = (string) $row->cause_key;
                $cause[$cKey] = $this->add($cause[$cKey] ?? $this->blank(), $row);
                if ($row->tt) {
                    $toCause[$row->tt.'|'.$cKey] = $this->add($toCause[$row->tt.'|'.$cKey] ?? $this->blank(), $row);
                }
                if ($row->ft && $row->tt) {
                    $path[$row->ft.'|'.$row->tt.'|'.$cKey] = $this->add($path[$row->ft.'|'.$row->tt.'|'.$cKey] ?? $this->blank(), $row);
                }
                // id-level (only populated once per-department snapshots exist).
                if ($row->ti) {
                    $toIdCause[$row->ti.'|'.$cKey] = $this->add($toIdCause[$row->ti.'|'.$cKey] ?? $this->blank(), $row);
                }
                if ($row->fi && $row->ti) {
                    $pathId[$row->fi.'|'.$row->ti.'|'.$cKey] = $this->add($pathId[$row->fi.'|'.$row->ti.'|'.$cKey] ?? $this->blank(), $row);
                }
            }

            return [
                'path_id' => array_map([$this, 'finalize'], $pathId),
                'to_id_cause' => array_map([$this, 'finalize'], $toIdCause),
                'path' => array_map([$this, 'finalize'], $path),
                'to_cause' => array_map([$this, 'finalize'], $toCause),
                'cause' => array_map([$this, 'finalize'], $cause),
                'global' => $this->finalize($global),
            ];
        });
    }

    /**
     * Fallback chain (Phase 9.10): id-path → type-path → to-id+cause → to-type+cause →
     * cause → global → config. Id-level levels are used ONLY when their sample is large
     * enough — they never make confidence look higher than the data supports.
     */
    public function baselineFor(JourneyHandoff $handoff): array
    {
        $map = $this->baselineMap();
        $cause = $handoff->cause->value;
        $min = (int) config('journey.prediction.minimum_snapshot_count', 5);

        $idPath = $map['path_id'][$handoff->fromDepartmentId.'|'.$handoff->toDepartmentId.'|'.$cause] ?? null;
        if ($idPath !== null && $idPath['sample'] >= $min) {
            return $idPath;
        }
        $typePath = $map['path'][$handoff->fromDepartmentType.'|'.$handoff->toDepartmentType.'|'.$cause] ?? null;
        if ($typePath !== null) {
            return $typePath;
        }
        $idToCause = $map['to_id_cause'][$handoff->toDepartmentId.'|'.$cause] ?? null;
        if ($idToCause !== null && $idToCause['sample'] >= $min) {
            return $idToCause;
        }

        return $map['to_cause'][$handoff->toDepartmentType.'|'.$cause]
            ?? $map['cause'][$cause]
            ?? ($map['global']['sample'] > 0 ? $map['global'] : $this->configFallback());
    }

    public function pathBaseline(string $fromType, string $toType, JourneyDelayCause $cause): array
    {
        return $this->baselineMap()['path'][$fromType.'|'.$toType.'|'.$cause->value] ?? $this->configFallback();
    }

    public function causeBaseline(JourneyDelayCause $cause): array
    {
        return $this->baselineMap()['cause'][$cause->value] ?? $this->configFallback();
    }

    public function globalBaseline(): array
    {
        $global = $this->baselineMap()['global'];

        return $global['sample'] > 0 ? $global : $this->configFallback();
    }

    private function blank(): array
    {
        return ['hc' => 0, 'bc' => 0, 'cc' => 0, 'el' => 0, 'rc' => 0, 'tr' => 0, 'ta' => 0];
    }

    private function add(array $accum, $row): array
    {
        $accum['hc'] += (int) $row->hc;
        $accum['bc'] += (int) $row->bc;
        $accum['cc'] += (int) $row->cc;
        $accum['el'] += (int) $row->el;
        $accum['rc'] += (int) $row->rc;
        $accum['tr'] += (int) $row->tr;
        $accum['ta'] += (int) $row->ta;

        return $accum;
    }

    /** @return array{avg_wait:int,breach_rate:float,critical_rate:float,avg_resolve:int,avg_ack:int,sample:int} */
    private function finalize(array $a): array
    {
        return [
            'avg_wait' => $a['hc'] > 0 ? (int) round($a['el'] / $a['hc']) : 0,
            'breach_rate' => $a['hc'] > 0 ? round($a['bc'] / $a['hc'], 3) : 0.0,
            'critical_rate' => $a['hc'] > 0 ? round($a['cc'] / $a['hc'], 3) : 0.0,
            'avg_resolve' => $a['rc'] > 0 ? (int) round($a['tr'] / $a['rc']) : 0,
            'avg_ack' => $a['rc'] > 0 ? (int) round($a['ta'] / $a['rc']) : 0,
            'sample' => $a['hc'] + $a['rc'],
        ];
    }

    private function configFallback(): array
    {
        return ['avg_wait' => 0, 'breach_rate' => 0.0, 'critical_rate' => 0.0, 'avg_resolve' => 0, 'avg_ack' => 0, 'sample' => 0];
    }
}
