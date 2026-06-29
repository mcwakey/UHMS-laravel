<?php

namespace Tests\Feature\Journey;

use App\Enums\JourneyDelayCause;
use App\Enums\PatientJourneyStage;
use App\Enums\VisitStatus;
use App\Models\Department;
use App\Models\User;
use App\Models\Visit;
use App\Services\Journey\JourneyActionResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class JourneyActionResolverTest extends TestCase
{
    use RefreshDatabase;

    private function userIn(string $type): User
    {
        $dept = Department::create(['name' => $type, 'code' => 'U'.uniqid(), 'type' => $type, 'status' => 'active']);

        return User::factory()->create(['department_id' => $dept->id]);
    }

    private function visit(VisitStatus $status, string $type): Visit
    {
        $creator = User::factory()->create();
        $dept = Department::create(['name' => $type, 'code' => 'R'.uniqid(), 'type' => $type, 'status' => 'active']);

        return Visit::factory()->create([
            'status' => $status, 'current_department_id' => $dept->id, 'created_by' => $creator->id,
            'created_at' => now()->subHours(3),
        ]);
    }

    public function test_open_status_for_consultation(): void
    {
        $action = app(JourneyActionResolver::class)->resolve($this->visit(VisitStatus::WAITING, 'consultation'), $this->userIn('consultation'));

        $this->assertSame(JourneyDelayCause::AWAITING_CONSULTATION, $action->cause);
        $this->assertSame('open', $action->actionStatus);
        $this->assertNotNull($action->actionUrl);
    }

    public function test_actionable_status_for_pending_lab_result(): void
    {
        $visit = $this->visit(VisitStatus::LAB, 'investigation');
        DB::table('lab_requests')->insert([
            'request_number' => 'LR-'.uniqid(), 'requested_by' => $visit->created_by,
            'visit_id' => $visit->id, 'patient_id' => $visit->patient_id,
            'target_department_id' => $visit->current_department_id, 'status' => 'pending',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $action = app(JourneyActionResolver::class)->resolve($visit->fresh(), $this->userIn('investigation'));

        $this->assertSame(JourneyDelayCause::AWAITING_LAB_RESULT, $action->cause);
        $this->assertSame('actionable', $action->actionStatus);
        $this->assertNotNull($action->actionUrl);
    }

    public function test_blocked_status_for_bed_without_admission(): void
    {
        $action = app(JourneyActionResolver::class)->resolve($this->visit(VisitStatus::ADMITTING, 'inpatient'), $this->userIn('inpatient'));

        $this->assertSame(JourneyDelayCause::AWAITING_BED, $action->cause);
        $this->assertSame('blocked', $action->actionStatus);
    }

    public function test_resolved_status_for_terminal_visit(): void
    {
        $action = app(JourneyActionResolver::class)->resolve($this->visit(VisitStatus::CANCELLED, 'consultation'), $this->userIn('consultation'));

        $this->assertSame('resolved', $action->actionStatus);
        $this->assertNull($action->actionUrl);
    }

    public function test_dto_exposes_patient_stage_and_timing(): void
    {
        $visit = $this->visit(VisitStatus::WAITING, 'consultation');
        $action = app(JourneyActionResolver::class)->resolve($visit, $this->userIn('consultation'));

        $this->assertSame($visit->id, $action->visitId);
        $this->assertNotEmpty($action->patientName);
        $this->assertSame(PatientJourneyStage::CONSULTATION, $action->stage);
        $this->assertGreaterThan(0, $action->elapsedMinutes);
        $this->assertContains($action->severity, ['delayed', 'critical']);
        $this->assertNotEmpty($action->actionLabel);
    }
}
