<?php

namespace Tests\Feature\Journey;

use App\Models\JourneyFlowSnapshot;
use App\Services\Journey\JourneyAnalyticsQueryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JourneyAnalyticsQueryTest extends TestCase
{
    use RefreshDatabase;

    private function snap(array $attrs): void
    {
        JourneyFlowSnapshot::create(array_merge([
            'snapshot_date' => today()->toDateString(), 'granularity' => 'day',
            'from_department_type' => 'consultation', 'to_department_type' => 'investigation',
            'cause' => 'awaiting_lab_result', 'sla_status' => 'breached',
            'handoff_count' => 0, 'breached_count' => 0, 'critical_breach_count' => 0,
            'unassigned_count' => 0, 'assigned_count' => 0, 'acknowledged_count' => 0, 'resolved_count' => 0,
            'total_elapsed_minutes' => 0, 'total_time_to_acknowledge_minutes' => null, 'total_time_to_resolve_minutes' => null,
        ], $attrs));
    }

    private function filters(array $extra = []): array
    {
        return array_merge([
            'date_from' => today()->subDays(7)->toDateString(),
            'date_to' => today()->toDateString(),
            'granularity' => 'day', '_ttl' => 0,
        ], $extra);
    }

    private function service(): JourneyAnalyticsQueryService
    {
        return app(JourneyAnalyticsQueryService::class);
    }

    public function test_summary_computes_breach_rate(): void
    {
        $this->snap(['handoff_count' => 10, 'breached_count' => 4, 'critical_breach_count' => 1, 'total_elapsed_minutes' => 1000]);

        $summary = $this->service()->summary($this->filters());

        $this->assertSame(10, $summary['handoff_volume']);
        $this->assertSame(4, $summary['sla_breaches']);
        $this->assertSame(40.0, $summary['breach_rate']);
        $this->assertSame(100, $summary['avg_wait_minutes']); // 1000 / 10
    }

    public function test_time_to_acknowledge_and_resolve(): void
    {
        $this->snap(['sla_status' => 'resolved', 'cause' => 'awaiting_lab_result',
            'resolved_count' => 5, 'total_time_to_acknowledge_minutes' => 100, 'total_time_to_resolve_minutes' => 500]);

        $summary = $this->service()->summary($this->filters());

        $this->assertSame(20, $summary['time_to_acknowledge_avg']); // 100 / 5
        $this->assertSame(100, $summary['time_to_resolve_avg']);    // 500 / 5
    }

    public function test_department_ranking_sorts_blocking_by_breaches(): void
    {
        $this->snap(['to_department_type' => 'investigation', 'cause' => 'awaiting_lab_result', 'handoff_count' => 8, 'breached_count' => 6]);
        $this->snap(['to_department_type' => 'pharmacy', 'cause' => 'awaiting_dispensing', 'from_department_type' => 'inpatient', 'handoff_count' => 5, 'breached_count' => 2]);

        $ranking = $this->service()->departmentRanking($this->filters(), 'to');

        $this->assertSame('investigation', $ranking[0]['type']);
        $this->assertSame(6, $ranking[0]['breached_count']);
    }

    public function test_cause_filtering(): void
    {
        $this->snap(['cause' => 'awaiting_lab_result', 'handoff_count' => 10]);
        $this->snap(['cause' => 'awaiting_dispensing', 'to_department_type' => 'pharmacy', 'from_department_type' => 'inpatient', 'handoff_count' => 7]);

        $summary = $this->service()->summary($this->filters(['cause' => 'awaiting_lab_result']));

        $this->assertSame(10, $summary['handoff_volume']);
    }

    public function test_date_range_filtering(): void
    {
        $this->snap(['handoff_count' => 10]);
        $this->snap(['snapshot_date' => today()->subDays(30)->toDateString(), 'sla_status' => 'within', 'handoff_count' => 99]);

        $summary = $this->service()->summary($this->filters());

        $this->assertSame(10, $summary['handoff_volume']); // the 30-day-old row is out of range
    }

    public function test_period_comparison_reports_direction(): void
    {
        $this->snap(['handoff_count' => 20]); // current period (today)
        $this->snap(['snapshot_date' => today()->subDays(10)->toDateString(), 'sla_status' => 'within', 'handoff_count' => 10]);

        $current = $this->filters();
        $previous = $this->filters(['date_from' => today()->subDays(14)->toDateString(), 'date_to' => today()->subDays(8)->toDateString()]);
        $comparison = $this->service()->comparePeriods($current, $previous);

        $this->assertSame('up', $comparison['handoff_volume']['direction']);
    }
}
