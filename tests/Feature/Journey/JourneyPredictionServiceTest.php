<?php

namespace Tests\Feature\Journey;

use App\Data\Journey\JourneyHandoff;
use App\Enums\JourneyDelayCause;
use App\Enums\JourneyRiskLevel;
use App\Services\Journey\JourneyEtaService;
use App\Services\Journey\JourneyPredictionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class JourneyPredictionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    private function handoff(string $sla = 'breached', int $elapsed = 130, int $slaMin = 120): JourneyHandoff
    {
        return new JourneyHandoff(
            visitId: 1, patientId: 1, patientName: 'P', visitNumber: 'V1', stage: null,
            cause: JourneyDelayCause::AWAITING_LAB_RESULT, severity: 'critical',
            fromDepartmentId: 1, fromDepartmentName: 'OPD', fromDepartmentType: 'consultation',
            toDepartmentId: 2, toDepartmentName: 'Lab', toDepartmentType: 'investigation',
            actionLabel: 'x', actionStatus: 'actionable', actionUrl: null,
            elapsedMinutes: $elapsed, slaMinutes: $slaMin, slaStatus: $sla, minutesToBreach: $slaMin - $elapsed, waitingSince: null,
        );
    }

    public function test_predict_for_handoff_is_explainable(): void
    {
        $prediction = app(JourneyPredictionService::class)->predictForHandoff($this->handoff('breached', 130));

        $this->assertNotEmpty($prediction->riskReason);
        $this->assertGreaterThanOrEqual(JourneyRiskLevel::HIGH->priorityRank(), $prediction->riskLevel->priorityRank());
        $this->assertSame('low', $prediction->confidence); // no snapshot history
    }

    public function test_eta_uses_history_when_available(): void
    {
        $baseline = ['sample' => 50, 'avg_wait' => 150, 'avg_resolve' => 90, 'breach_rate' => 0.3];

        $eta = app(JourneyEtaService::class)->estimate($this->handoff('near_breach', 100, 120), $baseline);

        $this->assertTrue($eta['has_history']);
        $this->assertSame(50, $eta['estimated_remaining_minutes']); // avg_wait 150 - elapsed 100
        $this->assertSame(90, $eta['estimated_resolution_minutes']);
    }

    public function test_eta_falls_back_to_sla_without_history(): void
    {
        $eta = app(JourneyEtaService::class)->estimate($this->handoff('near_breach', 100, 120), ['sample' => 0]);

        $this->assertFalse($eta['has_history']);
        $this->assertSame(20, $eta['estimated_remaining_minutes']); // sla 120 - elapsed 100
        $this->assertSame('sla_estimate', $eta['note_key']);
    }

    public function test_disabled_prediction_returns_empty_summary(): void
    {
        config(['journey.prediction.enabled' => false]);

        $summary = app(JourneyPredictionService::class)->summaryForUser(\App\Models\User::factory()->create());

        $this->assertSame(0, $summary['likely']);
    }
}
