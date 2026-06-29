<?php

namespace Tests\Feature\Journey;

use App\Data\Journey\JourneyHandoff;
use App\Enums\UserStatus;
use App\Enums\VisitStatus;
use App\Models\Department;
use App\Models\User;
use App\Models\Visit;
use App\Services\Journey\JourneyEscalationService;
use App\Services\Journey\JourneyHandoffAssignmentService;
use App\Services\Journey\JourneyHandoffResolver;
use App\Services\Journey\JourneySupervisorResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class JourneySupervisorRoutingTest extends TestCase
{
    use RefreshDatabase;

    private Department $lab;

    private function userIn(Department $department): User
    {
        return User::factory()->create(['department_id' => $department->id]);
    }

    private function labHandoff(): JourneyHandoff
    {
        $consult = Department::create(['name' => 'OPD '.uniqid(), 'code' => 'SR'.uniqid(), 'type' => 'consultation', 'status' => 'active']);
        $this->lab = Department::create(['name' => 'Lab '.uniqid(), 'code' => 'SL'.uniqid(), 'type' => 'investigation', 'status' => 'active']);
        $creator = User::factory()->create();
        $visit = Visit::factory()->create([
            'status' => VisitStatus::LAB, 'current_department_id' => $consult->id, 'created_by' => $creator->id,
            'created_at' => now()->subHours(5),
        ]);
        DB::table('lab_requests')->insert([
            'request_number' => 'LR-'.uniqid(), 'requested_by' => $creator->id,
            'visit_id' => $visit->id, 'patient_id' => $visit->patient_id,
            'target_department_id' => $this->lab->id, 'status' => 'pending',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return app(JourneyHandoffResolver::class)->resolve($visit->fresh(), null);
    }

    public function test_department_supervisor_receives_escalation(): void
    {
        $handoff = $this->labHandoff();
        $supervisor = $this->userIn($this->lab);
        $this->lab->update(['supervisor_user_id' => $supervisor->id]);
        $assignment = app(JourneyHandoffAssignmentService::class)->claim($handoff, $this->userIn($this->lab));
        $supervisor->fresh()->notifications()->delete();

        app(JourneyEscalationService::class)->applyEscalation($handoff, $assignment, null);

        $this->assertGreaterThan(0, $supervisor->fresh()->notifications()->count());
    }

    public function test_fallback_to_eligible_staff_when_no_supervisor(): void
    {
        $handoff = $this->labHandoff();
        $labStaff = $this->userIn($this->lab); // eligible, no supervisor configured
        $assignment = app(JourneyHandoffAssignmentService::class)->claim($handoff, $this->userIn($this->lab));
        $labStaff->fresh()->notifications()->delete();

        app(JourneyEscalationService::class)->applyEscalation($handoff, $assignment, null);

        $this->assertGreaterThan(0, $labStaff->fresh()->notifications()->count());
    }

    public function test_inactive_supervisor_is_ignored(): void
    {
        $this->labHandoff();
        $supervisor = $this->userIn($this->lab);
        $supervisor->update(['status' => UserStatus::INACTIVE]);
        $this->lab->update(['supervisor_user_id' => $supervisor->id]);

        $recipients = app(JourneySupervisorResolver::class)->escalationRecipientsFor($this->lab->id, 'investigation', 'supervisor');

        $this->assertFalse($recipients->contains('id', $supervisor->id));
    }

    public function test_actor_is_not_self_notified_on_claim(): void
    {
        $handoff = $this->labHandoff();
        $user = $this->userIn($this->lab);

        app(JourneyHandoffAssignmentService::class)->claim($handoff, $user);

        $this->assertSame(0, $user->fresh()->notifications()->count());
    }
}
