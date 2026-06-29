<?php

namespace Tests\Feature\Journey;

use App\Data\Journey\JourneyHandoff;
use App\Data\Journey\JourneyRiskPrediction;
use App\Enums\JourneyDelayCause;
use App\Enums\JourneyRiskLevel;
use App\Models\JourneyFlowSnapshot;
use App\Models\JourneyHandoffAssignment;
use App\Services\Journey\JourneyPredictionBaselineService;
use App\Services\Journey\JourneyPredictiveEscalationAdvisor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class JourneyPredictionClosureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    private function snapshot(array $attrs): void
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

    private function handoff(): JourneyHandoff
    {
        return new JourneyHandoff(
            visitId: 1, patientId: 1, patientName: 'P', visitNumber: 'V1', stage: null, cause: JourneyDelayCause::AWAITING_LAB_RESULT, severity: 'delayed',
            fromDepartmentId: 1, fromDepartmentName: null, fromDepartmentType: 'consultation',
            toDepartmentId: 2, toDepartmentName: null, toDepartmentType: 'investigation',
            actionLabel: 'x', actionStatus: 'open', actionUrl: null,
            elapsedMinutes: 100, slaMinutes: 120, slaStatus: 'near_breach', minutesToBreach: 20, waitingSince: null,
        );
    }

    public function test_baseline_uses_id_level_when_sample_is_sufficient(): void
    {
        // id-level row with sample 10 (>= minimum 5) + a different type-level row.
        $this->snapshot(['from_department_id' => 1, 'to_department_id' => 2, 'handoff_count' => 10, 'breached_count' => 7]);

        $baseline = app(JourneyPredictionBaselineService::class)->baselineFor($this->handoff());

        $this->assertSame(10, $baseline['sample']);
        $this->assertSame(0.7, $baseline['breach_rate']);
    }

    public function test_baseline_avoids_low_sample_id_level_overconfidence(): void
    {
        // id-level row with only 3 samples (< 5) on a different type-path, plus the
        // consultation→investigation type-level row with 20.
        $this->snapshot(['from_department_id' => 1, 'to_department_id' => 2, 'from_department_type' => 'radiology', 'handoff_count' => 3, 'breached_count' => 3, 'sla_status' => 'critical_breach']);
        $this->snapshot(['from_department_id' => null, 'to_department_id' => null, 'handoff_count' => 20, 'breached_count' => 4, 'sla_status' => 'breached']);

        $baseline = app(JourneyPredictionBaselineService::class)->baselineFor($this->handoff());

        // Falls through to the type-level (20-sample) baseline, not the low-sample id one.
        $this->assertSame(20, $baseline['sample']);
    }

    public function test_predictive_advisor_recommends_without_mutating(): void
    {
        $prediction = new JourneyRiskPrediction(
            visitId: 1, handoffIdentity: 'x', stage: null, cause: JourneyDelayCause::AWAITING_LAB_RESULT,
            fromDepartmentId: 1, fromDepartmentType: 'consultation', toDepartmentId: 2, toDepartmentType: 'investigation',
            currentElapsedMinutes: 200, slaMinutes: 120, minutesToBreach: -80, estimatedRemainingMinutes: 0, estimatedResolutionMinutes: 90,
            riskScore: 85, riskLevel: JourneyRiskLevel::CRITICAL, riskReason: 'x', confidence: 'low', recommendedPriority: 'urgent',
        );

        $advice = app(JourneyPredictiveEscalationAdvisor::class)->adviseFor($prediction);

        $this->assertSame('pre_assign', $advice['recommendation']);
        $this->assertTrue($advice['manual_action_required']);
        $this->assertFalse($advice['auto_enabled']);
        // It performed no action.
        $this->assertSame(0, JourneyHandoffAssignment::count());
    }
}
