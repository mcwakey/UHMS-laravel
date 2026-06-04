<?php

namespace Tests\Feature;

use App\Enums\ProcedureStatus;
use App\Models\ActivityLog;
use App\Models\Patient;
use App\Models\ProcedureRequest;
use App\Models\User;
use App\Models\Visit;
use App\Services\ActivityLogService;
use App\Services\ProcedureWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Procedure / Theatre lifecycle must surface on the patient timeline (via the
 * single logStatusChange funnel) without duplicating the consultation-side
 * procedure-request log.
 */
class ProcedureTheatreLogTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Patient $patient;
    private Visit $visit;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
        $this->patient = Patient::factory()->create();
        $this->visit = Visit::factory()->create(['patient_id' => $this->patient->id, 'created_by' => $this->user->id]);
        $this->department = \App\Models\Department::create([
            'name' => 'Theatre Dept', 'code' => 'THD', 'type' => 'procedure', 'status' => 'active',
        ]);
    }

    private \App\Models\Department $department;

    private function request(): ProcedureRequest
    {
        return ProcedureRequest::create([
            'request_number' => 'PR-' . fake()->unique()->numberBetween(10000, 99999),
            'visit_id' => $this->visit->id,
            'patient_id' => $this->patient->id,
            'requested_by' => $this->user->id,
            'department_id' => $this->department->id,
            'indication' => 'Clinical indication for test',
            'priority' => 'routine',
            'status' => ProcedureStatus::REQUESTED,
        ]);
    }

    private function wf(): ProcedureWorkflowService
    {
        return app(ProcedureWorkflowService::class);
    }

    private function timeline(): \Illuminate\Support\Collection
    {
        return app(ActivityLogService::class)->getPatientTimeline($this->patient)->get();
    }

    public function test_lifecycle_logs_procedure_and_theatre_events_on_timeline(): void
    {
        $request = $this->request();

        $this->wf()->acceptProcedure($request, $this->user, 'Accepted for theatre');
        $this->wf()->transition($request->fresh(), ProcedureStatus::BILLED, $this->user);
        $this->wf()->transition($request->fresh(), ProcedureStatus::SCHEDULED, $this->user, 'Theatre Room 1');
        $this->wf()->transition($request->fresh(), ProcedureStatus::PRE_OP, $this->user);

        $events = $this->timeline()->pluck('event')->all();
        $this->assertContains('PROCEDURE_ACCEPTED', $events);
        $this->assertContains('PROCEDURE_BILLED', $events);
        $this->assertContains('THEATRE_CASE_SCHEDULED', $events);
        $this->assertContains('PREOP_CHECKLIST_UPDATED', $events);

        // The consultation owns the request; the lifecycle funnel must NOT re-log it.
        $this->assertNotContains('PROCEDURE_REQUESTED', $events);

        // Context + module + old/new status.
        $accepted = $this->timeline()->firstWhere('event', 'PROCEDURE_ACCEPTED');
        $this->assertSame('PROCEDURE', $accepted->log_name);
        $this->assertSame($this->patient->id, (int) $accepted->patient_id);
        $this->assertSame($request->id, (int) $accepted->properties['procedure_request_id']);
        $this->assertSame('requested', $accepted->properties['old']['status']);
        $this->assertSame('accepted', $accepted->properties['attributes']['status']);

        // Theatre-phase events use the THEATRE module.
        $scheduled = $this->timeline()->firstWhere('event', 'THEATRE_CASE_SCHEDULED');
        $this->assertSame('THEATRE', $scheduled->log_name);
    }

    public function test_rejection_logs_reason(): void
    {
        $request = $this->request();
        $this->wf()->rejectProcedure($request, $this->user, 'Patient not fit for surgery');

        $log = $this->timeline()->firstWhere('event', 'PROCEDURE_REJECTED');
        $this->assertNotNull($log);
        $this->assertSame('Patient not fit for surgery', $log->properties['reason']);
        $this->assertStringContainsString('not fit', $log->description);
    }

    public function test_cancellation_logs_reason_and_is_not_duplicated(): void
    {
        $request = $this->request();
        $this->wf()->cancelProcedure($request, $this->user, 'Theatre unavailable');

        $cancelled = $this->timeline()->where('event', 'PROCEDURE_CANCELLED');
        $this->assertSame(1, $cancelled->count()); // exactly one (no double log)
        $this->assertSame('Theatre unavailable', $cancelled->first()->properties['reason']);
    }
}
