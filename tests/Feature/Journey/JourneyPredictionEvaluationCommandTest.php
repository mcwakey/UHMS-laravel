<?php

namespace Tests\Feature\Journey;

use App\Enums\VisitStatus;
use App\Models\Department;
use App\Models\JourneyHandoffAssignment;
use App\Models\JourneyPredictionOutcome;
use App\Models\User;
use App\Models\Visit;
use App\Services\Journey\JourneyPredictionEvaluationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class JourneyPredictionEvaluationCommandTest extends TestCase
{
    use RefreshDatabase;

    private function seedEvaluable(): JourneyPredictionOutcome
    {
        $consult = Department::create(['name' => 'OPD', 'code' => 'EV'.substr(uniqid(), -6), 'type' => 'consultation', 'status' => 'active']);
        $lab = Department::create(['name' => 'Lab', 'code' => 'EVL'.substr(uniqid(), -5), 'type' => 'investigation', 'status' => 'active']);
        $visit = Visit::factory()->create(['status' => VisitStatus::COMPLETED, 'current_department_id' => $consult->id, 'created_by' => User::factory()->create()->id]);

        $assignment = JourneyHandoffAssignment::create([
            'visit_id' => $visit->id, 'cause' => 'awaiting_lab_result',
            'from_department_id' => $consult->id, 'to_department_id' => $lab->id, 'to_department_type' => 'investigation',
            'status' => JourneyHandoffAssignment::STATUS_RESOLVED,
            'assigned_at' => now()->subHours(3), 'acknowledged_at' => now()->subHours(2), 'resolved_at' => now()->subHour(),
        ]);

        $outcome = JourneyPredictionOutcome::create([
            'prediction_date' => today()->subDays(2)->toDateString(),
            'handoff_identity_hash' => hash('sha256', $assignment->identityKey()),
            'visit_id' => $visit->id,
            'from_department_id' => $consult->id, 'from_department_type' => 'consultation',
            'to_department_id' => $lab->id, 'to_department_type' => 'investigation', 'cause' => 'awaiting_lab_result',
            'predicted_risk_level' => 'critical', 'predicted_risk_score' => 75, 'predicted_minutes_to_breach' => 10, 'confidence' => 'low',
        ]);
        DB::table('journey_prediction_outcomes')->where('id', $outcome->id)->update(['created_at' => now()->subDays(2)]);

        return $outcome->fresh();
    }

    public function test_evaluation_marks_resolved_and_breached(): void
    {
        $outcome = $this->seedEvaluable();

        app(JourneyPredictionEvaluationService::class)->evaluateOutcome($outcome);

        $outcome->refresh();
        $this->assertNotNull($outcome->evaluated_at);
        $this->assertTrue($outcome->actual_resolved);
        $this->assertTrue($outcome->actual_breached); // resolved well after the predicted breach moment
    }

    public function test_evaluation_leaves_undeterminable_unevaluated(): void
    {
        $outcome = JourneyPredictionOutcome::create([
            'prediction_date' => today()->toDateString(),
            'handoff_identity_hash' => hash('sha256', 'nope'), 'visit_id' => null, 'cause' => 'awaiting_lab_result',
            'predicted_risk_level' => 'high', 'predicted_risk_score' => 60, 'confidence' => 'low',
        ]);

        app(JourneyPredictionEvaluationService::class)->evaluateOutcome($outcome);

        $this->assertNull($outcome->fresh()->evaluated_at);
    }

    public function test_command_dry_run_does_not_evaluate(): void
    {
        $outcome = $this->seedEvaluable();

        $this->artisan('journey:predictions:evaluate --evaluate --dry-run')->assertSuccessful();

        $this->assertNull($outcome->fresh()->evaluated_at);
        $this->assertFalse(DB::table('activity_log')->where('event', 'JOURNEY_PREDICTION_EVALUATE_RUN')->exists());
    }

    public function test_command_is_audited(): void
    {
        $this->seedEvaluable();

        $this->artisan('journey:predictions:evaluate --evaluate')->assertSuccessful();

        $this->assertTrue(DB::table('activity_log')->where('event', 'JOURNEY_PREDICTION_EVALUATE_RUN')->exists());
    }
}
