<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Department;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitConsultationRoute;
use App\Services\ActivityLogService;
use App\Services\LabService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Investigation (lab) department lifecycle must surface on the patient timeline
 * with explicit context — without duplicating the consultation-side request log.
 */
class InvestigationLogTest extends TestCase
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
    }

    private function lab(): LabService
    {
        return app(LabService::class);
    }

    private function timelineEvents(): array
    {
        return app(ActivityLogService::class)->getPatientTimeline($this->patient)->pluck('event')->all();
    }

    public function test_full_investigation_lifecycle_logs_to_patient_timeline(): void
    {
        $request = $this->lab()->createRequest($this->visit, [['name' => 'Full Blood Count']], ['urgency' => 'routine']);

        // Direct request → INVESTIGATION_REQUESTED with context.
        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'INVESTIGATION',
            'event' => 'INVESTIGATION_REQUESTED',
            'patient_id' => $this->patient->id,
            'visit_id' => $this->visit->id,
        ]);

        $this->lab()->acceptRequest($request->fresh());
        $item = $request->items()->first();
        $result = $this->lab()->enterResult($item, ['result_type' => 'parameters', 'result_value' => 'Normal']);
        $this->lab()->verifyResult($result->fresh());

        $events = $this->timelineEvents();
        $this->assertContains('INVESTIGATION_ACCEPTED', $events);
        $this->assertContains('RESULT_ENTERED', $events);
        $this->assertContains('RESULT_VERIFIED', $events);

        // Result logs carry the request and result ids.
        $entered = app(ActivityLogService::class)->getPatientTimeline($this->patient)
            ->where('event', 'RESULT_ENTERED')->first();
        $this->assertSame((int) $request->id, (int) $entered->properties['investigation_request_id']);
        $this->assertSame((int) $result->id, (int) $entered->properties['investigation_result_id']);
        $this->assertStringContainsString('Full Blood Count', $entered->description);
    }

    public function test_result_update_logs_old_no_and_new_value(): void
    {
        $request = $this->lab()->createRequest($this->visit, [['name' => 'Malaria RDT']]);
        $item = $request->items()->first();

        $this->lab()->enterResult($item->fresh(), ['result_type' => 'parameters', 'result_value' => 'Negative']);
        $this->lab()->enterResult($item->fresh(), ['result_type' => 'parameters', 'result_value' => 'Positive']);

        $events = $this->timelineEvents();
        $this->assertContains('RESULT_ENTERED', $events);
        $this->assertContains('RESULT_UPDATED', $events);

        $updated = app(ActivityLogService::class)->getPatientTimeline($this->patient)
            ->where('event', 'RESULT_UPDATED')->first();
        // buildProperties stores new_values under Spatie's "attributes" key.
        $this->assertSame('Positive', $updated->properties['attributes']['result']);
    }

    public function test_cancel_request_logs_cancellation(): void
    {
        $request = $this->lab()->createRequest($this->visit, [['name' => 'Chest X-Ray']]);
        $this->lab()->cancelRequest($request->fresh());

        $this->assertContains('INVESTIGATION_CANCELLED', $this->timelineEvents());
    }

    public function test_consultation_created_request_does_not_duplicate_request_log(): void
    {
        $department = Department::create([
            'name' => 'Consult Dept', 'code' => 'CSD', 'type' => 'consultation', 'status' => 'active',
        ]);
        $route = VisitConsultationRoute::create([
            'visit_id' => $this->visit->id,
            'patient_id' => $this->patient->id,
            'department_id' => $department->id,
            'doctor_id' => $this->user->id,
            'status' => VisitConsultationRoute::STATUS_ACTIVE,
            'routed_by' => $this->user->id,
        ]);

        $this->lab()->createRequest($this->visit, [['name' => 'Urea & Electrolytes']], [
            'consultation_route_id' => $route->id,
        ]);

        // The consultation entry funnel already logs this request, so the lab
        // service must NOT add a second INVESTIGATION_REQUESTED.
        $this->assertSame(0, ActivityLog::where('event', 'INVESTIGATION_REQUESTED')
            ->where('patient_id', $this->patient->id)->count());
    }
}
