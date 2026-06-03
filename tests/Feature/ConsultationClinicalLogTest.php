<?php

namespace Tests\Feature;

use App\Models\Complaint;
use App\Models\Diagnosis;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use App\Services\ActivityLogService;
use App\Services\ConsultationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Consultation clinical entries (complaint/diagnosis/…) must surface on the
 * patient timeline via the central activity log, with context + old/new values,
 * and without duplicate logs.
 */
class ConsultationClinicalLogTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Patient $patient;
    private Visit $visit;
    private $record;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $this->patient = Patient::factory()->create();
        $this->visit = Visit::factory()->create(['patient_id' => $this->patient->id, 'created_by' => $this->user->id]);
        $this->record = app(ConsultationService::class)->getOrCreateRecord($this->visit);
    }

    private function cs(): ConsultationService
    {
        return app(ConsultationService::class);
    }

    private function timelineEvents(): array
    {
        return app(ActivityLogService::class)->getPatientTimeline($this->patient)->pluck('event')->all();
    }

    public function test_adding_complaint_logs_to_patient_timeline_with_context(): void
    {
        $complaint = $this->cs()->addComplaint($this->record, [
            'description' => 'Fever', 'severity' => 'moderate',
        ]);

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'CONSULTATION',
            'event' => 'COMPLAINT_ADDED',
            'patient_id' => $this->patient->id,
            'visit_id' => $this->visit->id,
        ]);

        $log = app(ActivityLogService::class)->getPatientTimeline($this->patient)
            ->where('event', 'COMPLAINT_ADDED')->first();
        $this->assertNotNull($log);
        $this->assertStringContainsString('Fever', $log->description);
        $this->assertSame((int) $this->record->id, (int) $log->properties['medical_record_id']);
    }

    public function test_updating_complaint_logs_old_and_new_values(): void
    {
        $complaint = $this->cs()->addComplaint($this->record, ['description' => 'Cough', 'severity' => 'mild']);
        $this->cs()->updateComplaint($complaint, ['description' => 'Cough', 'severity' => 'severe']);

        $log = app(ActivityLogService::class)->getPatientTimeline($this->patient)
            ->where('event', 'COMPLAINT_UPDATED')->first();
        $this->assertNotNull($log);
        $this->assertSame('severe', $log->properties['attributes']['severity']);  // new
        $this->assertSame('mild', $log->properties['old']['severity']);            // old
    }

    public function test_deleting_complaint_logs_removal(): void
    {
        $complaint = $this->cs()->addComplaint($this->record, ['description' => 'Rash']);
        $this->cs()->deleteComplaint($complaint->fresh());

        $this->assertContains('COMPLAINT_REMOVED', $this->timelineEvents());
    }

    public function test_diagnosis_add_and_correction_log(): void
    {
        $diagnosis = $this->cs()->addDiagnosis($this->record, ['description' => 'Malaria']);
        $this->assertContains('DIAGNOSIS_ADDED', $this->timelineEvents());

        $this->cs()->updateDiagnosis($diagnosis->fresh(), ['description' => 'Typhoid fever']);

        $log = app(ActivityLogService::class)->getPatientTimeline($this->patient)
            ->where('event', 'DIAGNOSIS_UPDATED')->first();
        $this->assertNotNull($log);
        $this->assertSame('Malaria', $log->properties['old']['description']);
        $this->assertSame('Typhoid fever', $log->properties['attributes']['description']);
    }

    public function test_single_action_does_not_create_duplicate_logs(): void
    {
        $this->cs()->addComplaint($this->record, ['description' => 'Headache']);

        $count = \App\Models\ActivityLog::where('log_name', 'CONSULTATION')
            ->where('event', 'COMPLAINT_ADDED')
            ->where('patient_id', $this->patient->id)
            ->count();

        $this->assertSame(1, $count);
    }

    public function test_applying_medical_pattern_logs_pattern_and_created_records(): void
    {
        $pattern = \App\Models\MedicalPattern::create([
            'name' => 'Malaria Bundle', 'doctor_id' => $this->user->id, 'usage_count' => 0, 'is_active' => true,
        ]);
        $pattern->items()->create(['type' => 'complaint', 'data' => ['description' => 'Fever'], 'sort_order' => 1]);

        app(\App\Services\MedicalPatternService::class)->applyPattern($pattern->fresh('items'), $this->record);

        $events = $this->timelineEvents();
        $this->assertContains('PATTERN_APPLIED', $events);   // the application itself
        $this->assertContains('COMPLAINT_ADDED', $events);   // record created under current user
    }

    public function test_clinical_logs_carry_consultation_module_on_timeline(): void
    {
        $this->cs()->addComplaint($this->record, ['description' => 'Nausea']);
        $this->cs()->addTreatment($this->record, ['description' => 'IV fluids', 'type' => 'medication']);

        $modules = app(ActivityLogService::class)->getPatientTimeline($this->patient)
            ->pluck('log_name')->unique()->all();
        $this->assertContains('CONSULTATION', $modules);
        $this->assertContains('TREATMENT_ADDED', $this->timelineEvents());
    }
}
