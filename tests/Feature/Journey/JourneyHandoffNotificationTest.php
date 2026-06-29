<?php

namespace Tests\Feature\Journey;

use App\Data\Journey\JourneyHandoff;
use App\Enums\VisitStatus;
use App\Models\Department;
use App\Models\User;
use App\Models\Visit;
use App\Services\Journey\JourneyHandoffAssignmentService;
use App\Services\Journey\JourneyHandoffNotificationService;
use App\Services\Journey\JourneyHandoffResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class JourneyHandoffNotificationTest extends TestCase
{
    use RefreshDatabase;

    private Department $lab;

    private function department(string $type): Department
    {
        return Department::create(['name' => ucfirst($type).' '.uniqid(), 'code' => 'NT'.uniqid(), 'type' => $type, 'status' => 'active']);
    }

    private function userIn(Department $department): User
    {
        return User::factory()->create(['department_id' => $department->id]);
    }

    /** A Consultation→Investigation handoff whose destination lab is $this->lab. */
    private function labHandoff(): JourneyHandoff
    {
        $consult = $this->department('consultation');
        $this->lab = $this->department('investigation');
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

    public function test_assignment_notifies_the_assignee(): void
    {
        $handoff = $this->labHandoff();
        $assignee = $this->userIn($this->lab);
        $assigner = $this->userIn($this->lab);

        app(JourneyHandoffAssignmentService::class)->assignTo($handoff, $assignee, $assigner);

        $this->assertSame(1, $assignee->fresh()->notifications()->count());
        $this->assertSame(0, $assigner->fresh()->notifications()->count()); // actor not notified
        $data = $assignee->fresh()->notifications()->first()->data;
        $this->assertStringContainsString('journey/worklist', $data['action_url'] ?? $data['url'] ?? '');
    }

    public function test_unauthorized_user_is_not_notified(): void
    {
        $handoff = $this->labHandoff();
        $stranger = $this->userIn($this->department('stores')); // no investigation capability
        app(JourneyHandoffAssignmentService::class)->assignTo($handoff, $this->userIn($this->lab), $this->userIn($this->lab));

        $this->assertSame(0, $stranger->fresh()->notifications()->count());
    }

    public function test_critical_unassigned_notifies_eligible_destination_staff(): void
    {
        $handoff = $this->labHandoff();
        $labStaff = $this->userIn($this->lab);

        $sent = app(JourneyHandoffNotificationService::class)->notifyCriticalUnassigned($handoff);

        $this->assertGreaterThan(0, $sent);
        $this->assertSame(1, $labStaff->fresh()->notifications()->count());
    }

    public function test_resolved_notifies_assignee_when_resolved_by_someone_else(): void
    {
        $handoff = $this->labHandoff();
        $assignee = $this->userIn($this->lab);
        $resolver = $this->userIn($this->lab);
        $service = app(JourneyHandoffAssignmentService::class);
        $assignment = $service->assignTo($handoff, $assignee, $resolver);
        $assignee->fresh()->notifications()->delete(); // ignore the assignment notification

        $service->resolve($assignment->fresh(), $resolver);

        $this->assertSame(1, $assignee->fresh()->notifications()->count());
    }

    public function test_duplicate_notifications_are_deduped(): void
    {
        $handoff = $this->labHandoff();
        $assignee = $this->userIn($this->lab);
        $service = app(JourneyHandoffNotificationService::class);

        $assignment = app(JourneyHandoffAssignmentService::class)->assignTo($handoff, $assignee, $this->userIn($this->lab));
        $service->notifyAssigned($assignment, $this->userIn($this->lab)); // same dedupe key within window

        $this->assertSame(1, $assignee->fresh()->notifications()->count());
    }
}
