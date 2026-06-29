<?php

namespace Tests\Feature\Journey;

use App\Data\Journey\JourneyHandoff;
use App\Enums\VisitStatus;
use App\Models\Department;
use App\Models\User;
use App\Models\Visit;
use App\Services\Journey\JourneyHandoffAssignmentService;
use App\Services\Journey\JourneyHandoffResolver;
use App\Services\Journey\JourneyHandoffWorklistService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class JourneyWorklistRefreshTest extends TestCase
{
    use RefreshDatabase;

    private function department(string $type): Department
    {
        return Department::create(['name' => ucfirst($type).' '.uniqid(), 'code' => 'RF'.uniqid(), 'type' => $type, 'status' => 'active']);
    }

    private function userIn(string $type): User
    {
        return User::factory()->create(['department_id' => $this->department($type)->id]);
    }

    /** @return array{0:JourneyHandoff,1:Department} a Consultation→Investigation handoff. */
    private function labHandoff(): array
    {
        $consult = $this->department('consultation');
        $lab = $this->department('investigation');
        $creator = User::factory()->create();
        $visit = Visit::factory()->create([
            'status' => VisitStatus::LAB, 'current_department_id' => $consult->id, 'created_by' => $creator->id,
            'created_at' => now()->subHours(3),
        ]);
        DB::table('visits')->where('id', $visit->id)->update(['updated_at' => now()->subHours(3)]);
        DB::table('lab_requests')->insert([
            'request_number' => 'LR-'.uniqid(), 'requested_by' => $creator->id,
            'visit_id' => $visit->id, 'patient_id' => $visit->patient_id,
            'target_department_id' => $lab->id, 'status' => 'pending',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return [app(JourneyHandoffResolver::class)->resolve($visit->fresh(), null), $lab];
    }

    public function test_refresh_endpoint_requires_journey_capability(): void
    {
        // A user with no department has no journey capability.
        $this->actingAs(User::factory()->create(['department_id' => null]))
            ->get(route('admin.journey.worklist.refresh'))
            ->assertForbidden();
    }

    public function test_refresh_endpoint_renders_partial_only(): void
    {
        $response = $this->actingAs($this->userIn('consultation'))
            ->get(route('admin.journey.worklist.refresh', ['tab' => 'owed_by', 'severity' => 'critical']));

        $response->assertOk();
        $this->assertStringNotContainsString('<html', $response->getContent());
    }

    public function test_assigned_to_me_lists_my_handoffs(): void
    {
        [$handoff] = $this->labHandoff();
        $user = $this->userIn('investigation');
        app(JourneyHandoffAssignmentService::class)->claim($handoff, $user);

        $rows = app(JourneyHandoffWorklistService::class)->assignedToMe($user);

        $this->assertTrue(collect($rows)->contains('visitId', $handoff->visitId));
    }

    public function test_unassigned_only_filter(): void
    {
        [$handoff, $lab] = $this->labHandoff();
        $user = $this->userIn('investigation');
        $service = app(JourneyHandoffWorklistService::class);

        // Unassigned → present with the filter.
        $this->assertTrue(collect($service->owedByDepartment($lab, $user, ['unassigned_only' => true]))->contains('visitId', $handoff->visitId));

        // Once claimed → excluded by the unassigned-only filter.
        app(JourneyHandoffAssignmentService::class)->claim($handoff, $user);
        $this->assertFalse(collect($service->owedByDepartment($lab, $user, ['unassigned_only' => true]))->contains('visitId', $handoff->visitId));
    }

    public function test_refresh_does_not_mutate_escalation_state(): void
    {
        [$handoff] = $this->labHandoff();
        $user = $this->userIn('investigation');
        $assignment = app(JourneyHandoffAssignmentService::class)->claim($handoff, $user);
        $this->assertSame('none', $assignment->escalation_level);

        // Polling renders the derived (critical) level but must NOT persist it.
        $this->actingAs($user)->get(route('admin.journey.worklist.refresh', ['tab' => 'owed_by']))->assertOk();

        $this->assertSame('none', $assignment->fresh()->escalation_level);
    }

    public function test_refresh_shows_assignment_and_escalation(): void
    {
        [$handoff] = $this->labHandoff();
        $user = $this->userIn('investigation');
        app(JourneyHandoffAssignmentService::class)->claim($handoff, $user);

        $response = $this->actingAs($user)->get(route('admin.journey.worklist.refresh', ['tab' => 'owed_by']));

        $response->assertOk();
        $response->assertSee(__('journey.escalation.critical'), false);
        $response->assertSee(__('journey.assignment.status.assigned'), false);
    }
}
