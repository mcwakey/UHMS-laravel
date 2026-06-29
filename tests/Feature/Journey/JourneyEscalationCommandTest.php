<?php

namespace Tests\Feature\Journey;

use App\Enums\VisitStatus;
use App\Models\Department;
use App\Models\JourneyHandoffAssignment;
use App\Models\User;
use App\Models\Visit;
use App\Services\Journey\JourneyHandoffAssignmentService;
use App\Services\Journey\JourneyHandoffResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class JourneyEscalationCommandTest extends TestCase
{
    use RefreshDatabase;

    private function department(string $type): Department
    {
        return Department::create(['name' => ucfirst($type).' '.uniqid(), 'code' => 'EC'.uniqid(), 'type' => $type, 'status' => 'active']);
    }

    private function userIn(string $type): User
    {
        return User::factory()->create(['department_id' => $this->department($type)->id]);
    }

    /** @return array{0:JourneyHandoffAssignment,1:Visit,2:JourneyHandoffAssignment} a claimed critical-breach handoff. */
    private function claimedHandoff(): array
    {
        $consult = $this->department('consultation');
        $lab = $this->department('investigation');
        $creator = User::factory()->create();
        $visit = Visit::factory()->create([
            'status' => VisitStatus::LAB, 'current_department_id' => $consult->id, 'created_by' => $creator->id,
            'created_at' => now()->subHours(5),
        ]);
        DB::table('lab_requests')->insert([
            'request_number' => 'LR-'.uniqid(), 'requested_by' => $creator->id,
            'visit_id' => $visit->id, 'patient_id' => $visit->patient_id,
            'target_department_id' => $lab->id, 'status' => 'pending',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $handoff = app(JourneyHandoffResolver::class)->resolve($visit->fresh(), null);
        $assignment = app(JourneyHandoffAssignmentService::class)->claim($handoff, $this->userIn('investigation'));

        return [$assignment, $visit, $assignment];
    }

    public function test_dry_run_mutates_nothing(): void
    {
        [$assignment] = $this->claimedHandoff();
        $this->assertSame('none', $assignment->escalation_level);

        $this->artisan('journey:handoffs:escalate --dry-run')->assertSuccessful();

        $this->assertSame('none', $assignment->fresh()->escalation_level);
    }

    public function test_escalates_breached_assignment_to_critical(): void
    {
        [$assignment] = $this->claimedHandoff();

        $this->artisan('journey:handoffs:escalate')->assertSuccessful();

        $this->assertSame('critical', $assignment->fresh()->escalation_level);
    }

    public function test_no_duplicate_escalation_on_repeat_run(): void
    {
        [$assignment] = $this->claimedHandoff();
        $this->artisan('journey:handoffs:escalate');
        $firstEscalatedAt = $assignment->fresh()->last_escalated_at;

        $this->artisan('journey:handoffs:escalate');

        $this->assertEquals($firstEscalatedAt, $assignment->fresh()->last_escalated_at);
    }

    public function test_stale_assignment_is_dismissed(): void
    {
        [$assignment, $visit] = $this->claimedHandoff();
        $visit->update(['status' => VisitStatus::COMPLETED]); // handoff no longer active

        $this->artisan('journey:handoffs:escalate')->assertSuccessful();

        $this->assertSame('dismissed', $assignment->fresh()->status);
        $this->assertNotNull($assignment->fresh()->dismissed_at);
    }

    public function test_resolved_assignment_is_skipped(): void
    {
        [$assignment] = $this->claimedHandoff();
        $assignment->update(['status' => 'resolved', 'resolved_at' => now()]);

        $this->artisan('journey:handoffs:escalate')->assertSuccessful();

        $this->assertSame('resolved', $assignment->fresh()->status);
        $this->assertSame('none', $assignment->fresh()->escalation_level);
    }

    public function test_cause_filter_is_respected(): void
    {
        [$assignment] = $this->claimedHandoff(); // cause = awaiting_lab_result

        $this->artisan('journey:handoffs:escalate --cause=awaiting_dispensing')->assertSuccessful();

        $this->assertSame('none', $assignment->fresh()->escalation_level);
    }

    public function test_limit_is_respected(): void
    {
        $this->claimedHandoff();
        $this->claimedHandoff();

        $this->artisan('journey:handoffs:escalate --limit=1')
            ->expectsOutputToContain('Checked: 1')
            ->assertSuccessful();
    }

    public function test_run_is_audited(): void
    {
        $this->claimedHandoff();

        $this->artisan('journey:handoffs:escalate')->assertSuccessful();

        $this->assertTrue(DB::table('activity_log')->where('event', 'JOURNEY_HANDOFF_ESCALATION_RUN')->exists());
    }

    public function test_escalation_notifies_the_assignee(): void
    {
        [$assignment] = $this->claimedHandoff();
        $assignee = $assignment->assignedTo;

        $this->artisan('journey:handoffs:escalate')->assertSuccessful();

        $this->assertGreaterThan(0, $assignee->fresh()->notifications()->count());
    }
}
