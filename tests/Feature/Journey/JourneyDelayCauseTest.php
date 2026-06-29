<?php

namespace Tests\Feature\Journey;

use App\Enums\JourneyDelayCause;
use App\Enums\VisitStatus;
use App\Models\Department;
use App\Models\User;
use App\Models\Visit;
use App\Services\Journey\JourneyDelayCauseResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class JourneyDelayCauseTest extends TestCase
{
    use RefreshDatabase;

    private function visit(VisitStatus $status, string $type = 'consultation'): Visit
    {
        $user = User::factory()->create();
        $dept = Department::create(['name' => $type, 'code' => 'C'.uniqid(), 'type' => $type, 'status' => 'active']);

        return Visit::factory()->create([
            'status' => $status, 'current_department_id' => $dept->id, 'created_by' => $user->id,
        ]);
    }

    private function cause(Visit $visit): JourneyDelayCause
    {
        return app(JourneyDelayCauseResolver::class)->resolve($visit)['cause'];
    }

    public function test_consultation_causes(): void
    {
        $this->assertSame(JourneyDelayCause::AWAITING_CONSULTATION, $this->cause($this->visit(VisitStatus::WAITING)));
        $this->assertSame(JourneyDelayCause::AWAITING_CLINICAL_REVIEW, $this->cause($this->visit(VisitStatus::CONSULTING)));
    }

    public function test_payment_cause(): void
    {
        $this->assertSame(JourneyDelayCause::AWAITING_PAYMENT, $this->cause($this->visit(VisitStatus::BILLING)));
    }

    public function test_investigation_causes_depend_on_the_request(): void
    {
        // In the investigation stage with no request yet → awaiting the request.
        $visit = $this->visit(VisitStatus::LAB, 'investigation');
        $this->assertSame(JourneyDelayCause::AWAITING_LAB_REQUEST, $this->cause($visit));

        // Once a pending request exists → awaiting the result.
        DB::table('lab_requests')->insert([
            'request_number' => 'LR-'.uniqid(), 'requested_by' => $visit->created_by,
            'visit_id' => $visit->id, 'patient_id' => $visit->patient_id,
            'target_department_id' => $visit->current_department_id, 'status' => 'pending',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->assertSame(JourneyDelayCause::AWAITING_LAB_RESULT, $this->cause($visit->fresh()));
    }

    public function test_radiology_uses_imaging_causes(): void
    {
        $this->assertSame(JourneyDelayCause::AWAITING_RADIOLOGY_REQUEST, $this->cause($this->visit(VisitStatus::LAB, 'radiology')));
    }

    public function test_pharmacy_cause(): void
    {
        // In pharmacy with no prescription yet → awaiting the prescription.
        $this->assertSame(JourneyDelayCause::AWAITING_PRESCRIPTION, $this->cause($this->visit(VisitStatus::PHARMACY, 'pharmacy')));
    }

    public function test_ward_causes(): void
    {
        $this->assertSame(JourneyDelayCause::AWAITING_BED, $this->cause($this->visit(VisitStatus::ADMITTING, 'inpatient')));
        $this->assertSame(JourneyDelayCause::AWAITING_DISCHARGE, $this->cause($this->visit(VisitStatus::DISCHARGING, 'inpatient')));
        $this->assertSame(JourneyDelayCause::AWAITING_ADMISSION, $this->cause($this->visit(VisitStatus::ADMITTED, 'inpatient')));
    }

    public function test_terminal_status_is_unknown(): void
    {
        $this->assertSame(JourneyDelayCause::UNKNOWN, $this->cause($this->visit(VisitStatus::CANCELLED)));
    }

    public function test_resolution_includes_owner_and_action(): void
    {
        $visit = $this->visit(VisitStatus::LAB, 'investigation');
        DB::table('lab_requests')->insert([
            'request_number' => 'LR-'.uniqid(), 'requested_by' => $visit->created_by,
            'visit_id' => $visit->id, 'patient_id' => $visit->patient_id,
            'target_department_id' => $visit->current_department_id, 'status' => 'pending',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $resolved = app(JourneyDelayCauseResolver::class)->resolve($visit->fresh());

        $this->assertSame('investigation', $resolved['owner_type']);
        $this->assertSame($visit->current_department_id, $resolved['owner_department_id']);
        $this->assertSame(__('journey.action.awaiting_lab_result'), $resolved['action_label']);
    }
}
