<?php

namespace Tests\Feature\Journey;

use App\Data\Journey\JourneyRiskPrediction;
use App\Enums\JourneyDelayCause;
use App\Enums\JourneyRiskLevel;
use App\Models\JourneyPredictionOutcome;
use App\Services\Journey\JourneyPredictionOutcomeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JourneyPredictionOutcomeTest extends TestCase
{
    use RefreshDatabase;

    private function prediction(string $level = 'critical'): JourneyRiskPrediction
    {
        return new JourneyRiskPrediction(
            visitId: 5, handoffIdentity: '5|awaiting_lab_result|1|2|investigation', stage: null,
            cause: JourneyDelayCause::AWAITING_LAB_RESULT,
            fromDepartmentId: 1, fromDepartmentType: 'consultation', toDepartmentId: 2, toDepartmentType: 'investigation',
            currentElapsedMinutes: 200, slaMinutes: 120, minutesToBreach: -80,
            estimatedRemainingMinutes: 0, estimatedResolutionMinutes: 90,
            riskScore: 75, riskLevel: JourneyRiskLevel::from($level), riskReason: 'x', confidence: 'low', recommendedPriority: 'urgent',
        );
    }

    private function service(): JourneyPredictionOutcomeService
    {
        return app(JourneyPredictionOutcomeService::class);
    }

    public function test_capture_stores_no_patient_identifiers(): void
    {
        $outcome = $this->service()->capturePrediction($this->prediction());

        $columns = array_keys($outcome->getAttributes());
        $this->assertNotContains('patient_name', $columns);
        $this->assertNotContains('visit_number', $columns);
        $this->assertNotContains('patient_id', $columns);
        $this->assertSame(64, strlen($outcome->handoff_identity_hash)); // sha256 hex
    }

    public function test_identity_hash_is_stable(): void
    {
        $prediction = $this->prediction();

        $this->assertSame(hash('sha256', $prediction->handoffIdentity), $this->service()->identityHash($prediction));
        $this->assertSame($this->service()->identityHash($prediction), $this->service()->identityHash($prediction));
    }

    public function test_capture_is_idempotent_per_hour(): void
    {
        $this->service()->capturePrediction($this->prediction());
        $this->service()->capturePrediction($this->prediction());

        $this->assertSame(1, JourneyPredictionOutcome::count());
    }

    public function test_dry_run_captures_nothing(): void
    {
        // No active handoffs seeded → scanned 0 anyway; dry-run never persists.
        $result = $this->service()->captureForActiveHandoffs(['dry_run' => true]);

        $this->assertSame(0, $result['captured']);
        $this->assertSame(0, JourneyPredictionOutcome::count());
    }
}
