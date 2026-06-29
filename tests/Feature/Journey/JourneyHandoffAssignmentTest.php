<?php

namespace Tests\Feature\Journey;

use App\Data\Journey\JourneyHandoff;
use App\Enums\VisitStatus;
use App\Models\Department;
use App\Models\JourneyHandoffAssignment;
use App\Models\User;
use App\Models\Visit;
use App\Services\Journey\JourneyHandoffAssignmentService;
use App\Services\Journey\JourneyHandoffResolver;
use App\Services\Journey\JourneyHandoffWorklistService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\UnauthorizedException;
use RuntimeException;
use Tests\TestCase;

class JourneyHandoffAssignmentTest extends TestCase
{
    use RefreshDatabase;

    private function department(string $type): Department
    {
        return Department::create(['name' => ucfirst($type).' '.uniqid(), 'code' => 'A'.uniqid(), 'type' => $type, 'status' => 'active']);
    }

    private function userIn(string $type): User
    {
        return User::factory()->create(['department_id' => $this->department($type)->id]);
    }

    /** @return array{0:JourneyHandoff,1:Department,2:Visit} a Consultation→Investigation handoff. */
    private function labHandoff(): array
    {
        $consult = $this->department('consultation');
        $lab = $this->department('investigation');
        $creator = User::factory()->create();
        $visit = Visit::factory()->create([
            'status' => VisitStatus::LAB, 'current_department_id' => $consult->id, 'created_by' => $creator->id,
            'created_at' => now()->subHours(3),
        ]);
        DB::table('lab_requests')->insert([
            'request_number' => 'LR-'.uniqid(), 'requested_by' => $creator->id,
            'visit_id' => $visit->id, 'patient_id' => $visit->patient_id,
            'target_department_id' => $lab->id, 'status' => 'pending',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return [app(JourneyHandoffResolver::class)->resolve($visit->fresh(), null), $lab, $visit];
    }

    private function service(): JourneyHandoffAssignmentService
    {
        return app(JourneyHandoffAssignmentService::class);
    }

    public function test_claim_unassigned_handoff(): void
    {
        [$handoff] = $this->labHandoff();
        $user = $this->userIn('investigation');

        $assignment = $this->service()->claim($handoff, $user);

        $this->assertSame(JourneyHandoffAssignment::STATUS_ASSIGNED, $assignment->status);
        $this->assertSame($user->id, $assignment->assigned_to_user_id);
        $this->assertTrue(DB::table('activity_log')->where('event', 'JOURNEY_HANDOFF_CLAIMED')->exists());
    }

    public function test_assign_to_eligible_user(): void
    {
        [$handoff] = $this->labHandoff();
        $assignee = $this->userIn('investigation');

        $assignment = $this->service()->assignTo($handoff, $assignee, $this->userIn('investigation'));

        $this->assertSame($assignee->id, $assignment->assigned_to_user_id);
    }

    public function test_prevent_assign_to_ineligible_user(): void
    {
        [$handoff] = $this->labHandoff();

        $this->expectException(RuntimeException::class);
        $this->service()->assignTo($handoff, $this->userIn('stores'), $this->userIn('investigation'));
    }

    public function test_prevent_unauthorized_claim(): void
    {
        [$handoff] = $this->labHandoff();

        $this->expectException(UnauthorizedException::class);
        $this->service()->claim($handoff, $this->userIn('stores'));
    }

    public function test_acknowledge_then_resolve(): void
    {
        [$handoff] = $this->labHandoff();
        $user = $this->userIn('investigation');
        $assignment = $this->service()->claim($handoff, $user);

        $this->service()->acknowledge($assignment, $user);
        $this->assertSame(JourneyHandoffAssignment::STATUS_ACKNOWLEDGED, $assignment->fresh()->status);

        $this->service()->resolve($assignment->fresh(), $user, 'done');
        $this->assertSame(JourneyHandoffAssignment::STATUS_RESOLVED, $assignment->fresh()->status);
        $this->assertNotNull($assignment->fresh()->resolved_at);
    }

    public function test_prevent_unauthorized_resolve(): void
    {
        [$handoff] = $this->labHandoff();
        $assignment = $this->service()->claim($handoff, $this->userIn('investigation'));

        $this->expectException(UnauthorizedException::class);
        $this->service()->resolve($assignment, $this->userIn('stores'));
    }

    public function test_resolved_handoff_disappears_from_active_worklist(): void
    {
        [$handoff, $lab] = $this->labHandoff();
        $user = $this->userIn('investigation');
        $assignment = $this->service()->claim($handoff, $user);
        $this->service()->resolve($assignment, $user);

        $rows = app(JourneyHandoffWorklistService::class)->owedByDepartment($lab, $user);

        $this->assertFalse(collect($rows)->contains('visitId', $handoff->visitId));
    }

    public function test_stale_assignment_is_dismissed_when_handoff_no_longer_active(): void
    {
        [$handoff, , $visit] = $this->labHandoff();
        $assignment = $this->service()->claim($handoff, $this->userIn('investigation'));

        // The patient leaves the journey → the derived handoff is gone.
        $visit->update(['status' => VisitStatus::COMPLETED]);

        $this->service()->dismissIfResolved($assignment->fresh());

        $this->assertSame(JourneyHandoffAssignment::STATUS_DISMISSED, $assignment->fresh()->status);
    }

    public function test_same_handoff_reuses_one_assignment_row(): void
    {
        [$handoff] = $this->labHandoff();
        $user = $this->userIn('investigation');

        $first = $this->service()->claim($handoff, $user);
        $again = $this->service()->assignmentFor($handoff);

        $this->assertSame($first->id, $again->id);
        $this->assertSame(1, JourneyHandoffAssignment::where('visit_id', $handoff->visitId)->count());
    }
}
