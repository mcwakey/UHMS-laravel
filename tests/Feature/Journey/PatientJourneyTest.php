<?php

namespace Tests\Feature\Journey;

use App\Enums\PatientJourneyStage;
use App\Enums\VisitStatus;
use App\Models\Department;
use App\Models\User;
use App\Models\Visit;
use App\Services\Journey\PatientJourneyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PatientJourneyTest extends TestCase
{
    use RefreshDatabase;

    private function visit(VisitStatus $status, ?Department $department = null): Visit
    {
        $user = User::factory()->create();
        $department ??= Department::create(['name' => 'General OPD', 'code' => 'OPD'.uniqid(), 'type' => 'consultation', 'status' => 'active']);

        return Visit::factory()->create([
            'status' => $status,
            'current_department_id' => $department->id,
            'created_by' => $user->id,
            'created_at' => now()->subHours(2),
        ]);
    }

    private function log(Visit $visit, ?string $from, string $to, $at): void
    {
        DB::table('visit_status_logs')->insert([
            'visit_id' => $visit->id, 'from_status' => $from, 'to_status' => $to,
            'changed_by' => $visit->created_by, 'timestamp' => $at,
        ]);
    }

    public function test_current_stage_maps_from_visit_status(): void
    {
        $service = app(PatientJourneyService::class);

        $this->assertSame(PatientJourneyStage::CONSULTATION, $service->currentStage($this->visit(VisitStatus::CONSULTING)));
        $this->assertSame(PatientJourneyStage::INVESTIGATION, $service->currentStage($this->visit(VisitStatus::LAB)));
        $this->assertSame(PatientJourneyStage::PHARMACY, $service->currentStage($this->visit(VisitStatus::PHARMACY)));
        $this->assertSame(PatientJourneyStage::REGISTERED, $service->currentStage($this->visit(VisitStatus::REGISTERED)));
        $this->assertSame(PatientJourneyStage::TRIAGE, $service->currentStage($this->visit(VisitStatus::TRIAGE)));
    }

    public function test_queued_visit_expects_triage_before_consultation(): void
    {
        $snapshot = app(PatientJourneyService::class)->snapshot($this->visit(VisitStatus::QUEUED));
        $relevant = collect($snapshot['relevant_stages'])->map->value;

        $this->assertSame(PatientJourneyStage::CHECKED_IN, $snapshot['current_stage']);
        $this->assertSame(PatientJourneyStage::TRIAGE, $snapshot['next_stage']);
        $this->assertTrue($relevant->contains('triage'));
    }

    public function test_completed_and_next_stages(): void
    {
        $visit = $this->visit(VisitStatus::CONSULTING);
        $this->log($visit, 'registered', 'checked_in', now()->subHours(2));
        $this->log($visit, 'checked_in', 'consulting', now()->subHour());

        $snapshot = app(PatientJourneyService::class)->snapshot($visit);
        $completed = collect($snapshot['completed_stages'])->map->value;

        $this->assertTrue($completed->contains('registered'));
        $this->assertTrue($completed->contains('checked_in'));
        $this->assertSame(PatientJourneyStage::CONSULTATION, $snapshot['current_stage']);
        // No labs/prescriptions → the next relevant stage after consultation is completion.
        $this->assertSame(PatientJourneyStage::COMPLETED, $snapshot['next_stage']);
    }

    public function test_a_lab_request_makes_investigation_relevant_and_next(): void
    {
        $visit = $this->visit(VisitStatus::CONSULTING);
        DB::table('lab_requests')->insert([
            'request_number' => 'LR-'.uniqid(), 'requested_by' => $visit->created_by,
            'visit_id' => $visit->id, 'patient_id' => $visit->patient_id,
            'target_department_id' => $visit->current_department_id, 'status' => 'pending',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $snapshot = app(PatientJourneyService::class)->snapshot($visit);

        $this->assertContains(PatientJourneyStage::INVESTIGATION, $snapshot['relevant_stages']);
        $this->assertSame(PatientJourneyStage::INVESTIGATION, $snapshot['next_stage']);
    }

    public function test_terminal_status_has_no_forward_stage(): void
    {
        $snapshot = app(PatientJourneyService::class)->snapshot($this->visit(VisitStatus::CANCELLED));

        $this->assertTrue($snapshot['is_terminal']);
        $this->assertNull($snapshot['current_stage']);
        $this->assertEmpty($snapshot['completed_stages']);
        $this->assertNull($snapshot['next_stage']);
    }

    public function test_location_reports_department_and_since(): void
    {
        $lab = Department::create(['name' => 'Laboratory', 'code' => 'LAB'.uniqid(), 'type' => 'investigation', 'status' => 'active']);
        $visit = $this->visit(VisitStatus::LAB, $lab);
        $this->log($visit, 'consulting', 'lab', now()->subMinutes(20));

        $location = app(PatientJourneyService::class)->location($visit);

        $this->assertSame('Laboratory', $location['department']);
        $this->assertNotNull($location['since']);
    }
}
