<?php

namespace Tests\Feature\Journey;

use App\Models\JourneyPredictionOutcome;
use App\Services\Journey\JourneyPredictionAccuracyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JourneyPredictionAccuracyTest extends TestCase
{
    use RefreshDatabase;

    private function outcome(array $attrs): void
    {
        JourneyPredictionOutcome::create(array_merge([
            'prediction_date' => today()->toDateString(),
            'handoff_identity_hash' => hash('sha256', uniqid('', true)),
            'cause' => 'awaiting_lab_result',
            'from_department_type' => 'consultation', 'to_department_type' => 'investigation',
            'predicted_risk_level' => 'critical', 'predicted_risk_score' => 75, 'confidence' => 'low',
            'evaluated_at' => now(), 'actual_breached' => true,
        ], $attrs));
    }

    private function filters(array $extra = []): array
    {
        return array_merge([
            'date_from' => today()->subDays(7)->toDateString(),
            'date_to' => today()->toDateString(), '_ttl' => 0,
        ], $extra);
    }

    private function service(): JourneyPredictionAccuracyService
    {
        return app(JourneyPredictionAccuracyService::class);
    }

    public function test_precision(): void
    {
        $this->outcome(['predicted_risk_level' => 'critical', 'actual_breached' => true]);  // TP
        $this->outcome(['predicted_risk_level' => 'high', 'actual_breached' => false]);      // FP

        $this->assertSame(50.0, $this->service()->accuracySummary($this->filters())['precision']);
    }

    public function test_recall(): void
    {
        $this->outcome(['predicted_risk_level' => 'critical', 'actual_breached' => true]); // TP
        $this->outcome(['predicted_risk_level' => 'low', 'actual_breached' => true]);      // FN (missed)

        $this->assertSame(50.0, $this->service()->accuracySummary($this->filters())['recall']);
    }

    public function test_false_alarm_and_miss_rates(): void
    {
        $this->outcome(['predicted_risk_level' => 'high', 'actual_breached' => false]); // FP
        $this->outcome(['predicted_risk_level' => 'low', 'actual_breached' => false]);  // TN
        $this->outcome(['predicted_risk_level' => 'medium', 'actual_breached' => true]); // FN
        $this->outcome(['predicted_risk_level' => 'critical', 'actual_breached' => true]); // TP

        $summary = $this->service()->accuracySummary($this->filters());
        $this->assertSame(50.0, $summary['false_alarm_rate']); // FP / (FP + TN) = 1/2
        $this->assertSame(50.0, $summary['miss_rate']);        // FN / (FN + TP) = 1/2
    }

    public function test_eta_error(): void
    {
        $this->outcome(['predicted_risk_level' => 'critical', 'actual_breached' => true, 'predicted_remaining_minutes' => 100, 'actual_minutes_to_resolve' => 120]);

        $this->assertSame(20, $this->service()->accuracySummary($this->filters())['eta_error_avg']);
    }

    public function test_scoped_by_department_type(): void
    {
        $this->outcome(['to_department_type' => 'investigation', 'actual_breached' => true]);
        $this->outcome(['from_department_type' => 'inpatient', 'to_department_type' => 'pharmacy', 'cause' => 'awaiting_dispensing', 'actual_breached' => true]);

        $scoped = $this->service()->accuracySummary($this->filters(['scope_types' => ['investigation']]));

        $this->assertSame(1, $scoped['evaluated']); // pharmacy-only row excluded
    }

    public function test_dashboard_accuracy_returns_null_without_data(): void
    {
        $this->assertNull($this->service()->dashboardAccuracy('investigation'));
    }

    public function test_dashboard_accuracy_with_data(): void
    {
        $this->outcome(['to_department_type' => 'investigation', 'predicted_risk_level' => 'critical', 'actual_breached' => true]); // correct

        $result = $this->service()->dashboardAccuracy('investigation');

        $this->assertNotNull($result);
        $this->assertSame(100.0, $result['accuracy']);
    }
}
