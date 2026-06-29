<?php

namespace Tests\Feature\Journey;

use App\Data\Journey\JourneyHandoff;
use App\Enums\JourneyDelayCause;
use App\Models\JourneyFlowSnapshot;
use App\Services\Journey\JourneyPredictionBaselineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class JourneyPredictionBaselineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush(); // baselines are cached; start clean
    }

    private function snap(array $attrs): void
    {
        JourneyFlowSnapshot::create(array_merge([
            'snapshot_date' => today()->toDateString(), 'granularity' => 'day',
            'from_department_type' => 'consultation', 'to_department_type' => 'investigation',
            'cause' => 'awaiting_lab_result', 'sla_status' => 'breached',
            'handoff_count' => 0, 'breached_count' => 0, 'critical_breach_count' => 0,
            'unassigned_count' => 0, 'assigned_count' => 0, 'acknowledged_count' => 0, 'resolved_count' => 0,
            'total_elapsed_minutes' => 0,
        ], $attrs));
    }

    private function handoff(string $from, string $to, JourneyDelayCause $cause): JourneyHandoff
    {
        return new JourneyHandoff(
            visitId: 1, patientId: 1, patientName: 'P', visitNumber: 'V1', stage: null, cause: $cause, severity: 'delayed',
            fromDepartmentId: 1, fromDepartmentName: null, fromDepartmentType: $from,
            toDepartmentId: 2, toDepartmentName: null, toDepartmentType: $to,
            actionLabel: 'x', actionStatus: 'open', actionUrl: null,
            elapsedMinutes: 100, slaMinutes: 120, slaStatus: 'near_breach', minutesToBreach: 20, waitingSince: null,
        );
    }

    public function test_exact_path_baseline(): void
    {
        $this->snap(['handoff_count' => 10, 'breached_count' => 4, 'total_elapsed_minutes' => 1000]);

        $baseline = app(JourneyPredictionBaselineService::class)->pathBaseline('consultation', 'investigation', JourneyDelayCause::AWAITING_LAB_RESULT);

        $this->assertSame(0.4, $baseline['breach_rate']);
        $this->assertSame(100, $baseline['avg_wait']);
        $this->assertSame(10, $baseline['sample']);
    }

    public function test_fallback_when_no_exact_path(): void
    {
        // Only a cause-level row exists (different from-type).
        $this->snap(['from_department_type' => 'emergency', 'handoff_count' => 6, 'breached_count' => 3, 'total_elapsed_minutes' => 600]);

        $baseline = app(JourneyPredictionBaselineService::class)->baselineFor($this->handoff('records', 'investigation', JourneyDelayCause::AWAITING_LAB_RESULT));

        // Falls back to to-type+cause (or cause) — breach rate from the available data.
        $this->assertSame(0.5, $baseline['breach_rate']);
        $this->assertGreaterThan(0, $baseline['sample']);
    }

    public function test_no_history_safe_fallback(): void
    {
        $baseline = app(JourneyPredictionBaselineService::class)->baselineFor($this->handoff('consultation', 'investigation', JourneyDelayCause::AWAITING_LAB_RESULT));

        $this->assertSame(0, $baseline['sample']);
        $this->assertSame(0.0, $baseline['breach_rate']);
    }
}
