<?php

namespace App\Services\Journey;

/**
 * Phase 9.8 — canonical journey analytics metric definitions: stable keys, display
 * format and documented numerator/denominator. The query service computes values;
 * this is the single source of metric meaning (and label keys for EN/FR).
 */
class JourneyAnalyticsMetricRegistry
{
    /**
     * key => [format, formula]. format ∈ count|minutes|percent.
     */
    private const METRICS = [
        'handoff_volume'          => ['format' => 'count',   'formula' => 'Σ handoff_count'],
        'delayed_handoffs'        => ['format' => 'count',   'formula' => 'Σ handoff_count where sla_status != within'],
        'sla_breaches'            => ['format' => 'count',   'formula' => 'Σ breached_count'],
        'critical_breaches'       => ['format' => 'count',   'formula' => 'Σ critical_breach_count'],
        'near_breaches'           => ['format' => 'count',   'formula' => 'Σ handoff_count where sla_status = near_breach'],
        'unassigned_count'        => ['format' => 'count',   'formula' => 'Σ unassigned_count'],
        'assigned_count'          => ['format' => 'count',   'formula' => 'Σ assigned_count'],
        'acknowledged_count'      => ['format' => 'count',   'formula' => 'Σ acknowledged_count'],
        'resolved_count'          => ['format' => 'count',   'formula' => 'Σ resolved_count'],
        'time_to_acknowledge_avg' => ['format' => 'minutes', 'formula' => 'Σ total_time_to_acknowledge_minutes / Σ acknowledged_count'],
        'time_to_resolve_avg'     => ['format' => 'minutes', 'formula' => 'Σ total_time_to_resolve_minutes / Σ resolved_count'],
        'breach_rate'             => ['format' => 'percent',  'formula' => 'Σ breached_count / Σ handoff_count'],
        'resolution_rate'         => ['format' => 'percent',  'formula' => 'Σ resolved_count / Σ assigned_count'],
        'acknowledgement_rate'    => ['format' => 'percent',  'formula' => 'Σ acknowledged_count / Σ assigned_count'],
        'top_delay_cause'         => ['format' => 'label',    'formula' => 'cause with max Σ handoff_count'],
        'top_blocking_department' => ['format' => 'label',    'formula' => 'to_department_type with max Σ breached_count'],
        'top_waiting_department'  => ['format' => 'label',    'formula' => 'from_department_type with max Σ handoff_count'],
    ];

    /** @return array<string, array{format:string,formula:string}> */
    public function all(): array
    {
        return self::METRICS;
    }

    public function keys(): array
    {
        return array_keys(self::METRICS);
    }

    public function format(string $key): string
    {
        return self::METRICS[$key]['format'] ?? 'count';
    }

    public function formula(string $key): ?string
    {
        return self::METRICS[$key]['formula'] ?? null;
    }

    public function label(string $key): string
    {
        return __('journey.analytics.metric.'.$key);
    }
}
