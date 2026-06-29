<?php

namespace Tests\Feature\Journey;

use App\Data\Journey\JourneyHandoff;
use App\Enums\JourneyDelayCause;
use App\Enums\JourneyRiskLevel;
use App\Services\Journey\JourneyRiskScoringService;
use Tests\TestCase;

class JourneyRiskScoringTest extends TestCase
{
    private function handoff(string $sla, int $elapsed, int $slaMin = 120, string $assignment = 'unassigned', string $escalation = 'none'): JourneyHandoff
    {
        $handoff = new JourneyHandoff(
            visitId: 1, patientId: 1, patientName: 'P', visitNumber: 'V1', stage: null,
            cause: JourneyDelayCause::AWAITING_LAB_RESULT, severity: 'critical',
            fromDepartmentId: 1, fromDepartmentName: 'OPD', fromDepartmentType: 'consultation',
            toDepartmentId: 2, toDepartmentName: 'Lab', toDepartmentType: 'investigation',
            actionLabel: 'x', actionStatus: 'actionable', actionUrl: null,
            elapsedMinutes: $elapsed, slaMinutes: $slaMin, slaStatus: $sla, minutesToBreach: $slaMin - $elapsed, waitingSince: null,
        );
        $handoff->assignmentStatus = $assignment;
        $handoff->escalationLevel = $escalation;
        $handoff->assignedToUserId = $assignment === 'unassigned' ? null : 5;

        return $handoff;
    }

    private function score(JourneyHandoff $handoff, array $baseline = [], float $pressure = 0.0)
    {
        return app(JourneyRiskScoringService::class)->score($handoff, $baseline + ['sample' => 0, 'breach_rate' => 0.0, 'avg_wait' => 0, 'avg_resolve' => 0], $pressure);
    }

    public function test_within_sla_is_low_risk(): void
    {
        $p = $this->score($this->handoff('within', 30, 120, 'acknowledged'));

        $this->assertSame(JourneyRiskLevel::LOW, $p->riskLevel);
        $this->assertSame('routine', $p->recommendedPriority);
    }

    public function test_critical_breach_is_critical_risk(): void
    {
        $p = $this->score($this->handoff('critical_breach', 260, 120));

        $this->assertSame(JourneyRiskLevel::CRITICAL, $p->riskLevel);
        $this->assertSame('urgent', $p->recommendedPriority);
    }

    public function test_breached_is_at_least_high(): void
    {
        $p = $this->score($this->handoff('breached', 130, 120, 'assigned'));

        $this->assertGreaterThanOrEqual(JourneyRiskLevel::HIGH->priorityRank(), $p->riskLevel->priorityRank());
    }

    public function test_unassigned_scores_higher_than_acknowledged(): void
    {
        $unassigned = $this->score($this->handoff('near_breach', 100, 120, 'unassigned'));
        $acknowledged = $this->score($this->handoff('near_breach', 100, 120, 'acknowledged'));

        $this->assertGreaterThan($acknowledged->riskScore, $unassigned->riskScore);
    }

    public function test_critical_escalation_increases_score(): void
    {
        $with = $this->score($this->handoff('near_breach', 100, 120, 'assigned', 'critical'));
        $without = $this->score($this->handoff('near_breach', 100, 120, 'assigned', 'none'));

        $this->assertGreaterThan($without->riskScore, $with->riskScore);
    }

    public function test_higher_elapsed_ratio_increases_score(): void
    {
        $high = $this->score($this->handoff('near_breach', 110, 120, 'unassigned'));
        $low = $this->score($this->handoff('within', 40, 120, 'unassigned'));

        $this->assertGreaterThan($low->riskScore, $high->riskScore);
    }

    public function test_historical_breach_rate_increases_score(): void
    {
        $slow = $this->score($this->handoff('near_breach', 100, 120, 'assigned'), ['sample' => 50, 'breach_rate' => 0.9]);
        $fast = $this->score($this->handoff('near_breach', 100, 120, 'assigned'), ['sample' => 50, 'breach_rate' => 0.0]);

        $this->assertGreaterThan($fast->riskScore, $slow->riskScore);
        $this->assertSame('high', $slow->confidence); // sample 50 → high confidence
    }

    public function test_no_history_yields_low_confidence(): void
    {
        $p = $this->score($this->handoff('near_breach', 100, 120));

        $this->assertSame('low', $p->confidence);
    }
}
