<?php

namespace Tests\Feature;

use App\Enums\DepartmentType;
use App\Enums\ServiceType;
use App\Enums\VisitStatus;
use App\Models\Complaint;
use App\Models\Department;
use App\Models\MedicalPattern;
use App\Models\Patient;
use App\Models\ServiceCatalog;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitConsultationRoute;
use App\Services\ConsultationSessionService;
use App\Services\ConsultationSummaryService;
use App\Services\MedicalPatternService;
use App\Services\VisitPreviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ConsultationClinicalSectionsTest extends TestCase
{
    use RefreshDatabase;

    private User $mainDoctor;

    private User $additionalDoctor;

    private Department $department;

    private ServiceCatalog $service;

    protected function setUp(): void
    {
        parent::setUp();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $doctorRole = Role::findOrCreate('Doctor', 'web');
        foreach ([
            'consultations.view',
            'consultations.create',
            'consultation.entries.create',
            'consultation.entries.edit_own',
            'consultation.entries.delete_own',
            'consultation.hopc.create',
            'consultation.hopc.view',
            'consultation.examination.create',
            'consultation.examination.view',
            'consultation.tasks.create',
            'consultation.tasks.view',
            'medical_patterns.apply',
        ] as $permission) {
            $doctorRole->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }

        $this->department = Department::factory()->create([
            'name' => 'General Medicine',
            'type' => DepartmentType::CONSULTATION->value,
        ]);

        $this->service = ServiceCatalog::create([
            'name' => 'General Consultation',
            'code' => 'GEN-CON',
            'department_id' => $this->department->id,
            'category' => ServiceType::CONSULTATION->value,
            'price' => 100,
            'is_active' => true,
            'is_billable' => true,
        ]);

        $this->mainDoctor = User::factory()->create([
            'first_name' => 'Kofi',
            'last_name' => 'Mensah',
            'department_id' => $this->department->id,
        ]);
        $this->mainDoctor->assignRole($doctorRole);

        $this->additionalDoctor = User::factory()->create([
            'first_name' => 'Ama',
            'last_name' => 'Boateng',
            'department_id' => $this->department->id,
        ]);
        $this->additionalDoctor->assignRole($doctorRole);
    }

    public function test_consultation_page_shows_required_clinical_order(): void
    {
        [$visit, $route] = $this->makeConsultingVisit();

        $content = $this->actingAs($this->mainDoctor)
            ->get(route('admin.consultations.routes.show', [$visit, $route]))
            ->assertOk()
            ->getContent();

        $labels = [
            'Latest Vitals',
            'Complaints',
            'HOPC',
            'Examination',
            'Diagnoses',
            'Investigations',
            'Treatments',
            'Prescriptions',
            'Procedures',
            'Tasks',
            'Notes',
        ];

        $last = -1;
        foreach ($labels as $label) {
            $position = strpos($content, $label);
            $this->assertNotFalse($position, "Missing {$label}");
            $this->assertGreaterThan($last, $position, "{$label} is out of order");
            $last = $position;
        }
    }

    public function test_hopc_and_examination_are_saved_with_route_context_and_creator(): void
    {
        [$visit, $route] = $this->makeConsultingVisit();

        $this->actingAs($this->additionalDoctor)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->post(route('admin.consultations.hopc.store', $visit), [
                'consultation_route_id' => $route->id,
                'content' => 'Pain started three days ago and worsened today.',
                'duration' => '3 days',
            ])
            ->assertOk()
            ->assertJsonPath('hopc.created_by', $this->additionalDoctor->id);

        $this->actingAs($this->additionalDoctor)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->post(route('admin.consultations.examinations.store', $visit), [
                'consultation_route_id' => $route->id,
                'findings' => 'Febrile, mildly dehydrated, abdomen soft.',
            ])
            ->assertOk()
            ->assertJsonPath('examination.created_by', $this->additionalDoctor->id);

        $this->assertDatabaseHas('history_of_presenting_complaints', [
            'visit_id' => $visit->id,
            'patient_id' => $visit->patient_id,
            'medical_record_id' => $route->medicalRecord->id,
            'consultation_route_id' => $route->id,
            'department_id' => $this->department->id,
            'created_by' => $this->additionalDoctor->id,
        ]);

        $this->assertDatabaseHas('physical_examinations', [
            'visit_id' => $visit->id,
            'patient_id' => $visit->patient_id,
            'consultation_route_id' => $route->id,
            'created_by' => $this->additionalDoctor->id,
        ]);

        $this->assertSame($this->mainDoctor->id, $route->fresh()->doctor_id);
        $this->assertDatabaseHas('consultation_session_contributors', [
            'consultation_route_id' => $route->id,
            'user_id' => $this->additionalDoctor->id,
        ]);
    }

    public function test_additional_doctor_cannot_delete_another_doctors_entry(): void
    {
        [$visit, $route] = $this->makeConsultingVisit();
        $record = app(ConsultationSessionService::class)->getOrCreateMedicalRecordForRoute($route, $this->mainDoctor);

        $complaint = Complaint::create([
            'medical_record_id' => $record->id,
            'consultation_route_id' => $route->id,
            'visit_id' => $visit->id,
            'patient_id' => $visit->patient_id,
            'department_id' => $this->department->id,
            'doctor_id' => $this->mainDoctor->id,
            'created_by' => $this->mainDoctor->id,
            'description' => 'Chest pain',
        ]);

        $this->actingAs($this->additionalDoctor)
            ->delete(route('admin.consultations.complaints.destroy', $complaint))
            ->assertForbidden();

        $this->assertDatabaseHas('complaints', ['id' => $complaint->id]);
    }

    public function test_summary_and_visit_preview_show_entry_creator_not_only_main_doctor(): void
    {
        [$visit, $route] = $this->makeConsultingVisit();
        $record = app(ConsultationSessionService::class)->getOrCreateMedicalRecordForRoute($route, $this->mainDoctor);

        $this->actingAs($this->additionalDoctor)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->post(route('admin.consultations.hopc.store', $visit), [
                'consultation_route_id' => $route->id,
                'content' => 'Reviewed by another doctor.',
            ])
            ->assertOk();

        $summary = app(ConsultationSummaryService::class)->forRecord($record->fresh());
        $this->assertSame('Ama Boateng', $summary['sections']['history_of_presenting_complaint'][0]['entered_by']);

        $preview = app(VisitPreviewService::class)->build($visit->fresh());
        $hopc = collect($preview['timeline'])->firstWhere('source_type', 'history_of_presenting_complaint');

        $this->assertSame('Ama Boateng', $hopc['entered_by']);
        $this->assertStringContainsString('Reviewed by another doctor.', $hopc['description']);
    }

    public function test_medical_pattern_can_apply_hopc_examination_and_task_as_current_doctor(): void
    {
        [$visit, $route] = $this->makeConsultingVisit();
        $record = app(ConsultationSessionService::class)->getOrCreateMedicalRecordForRoute($route, $this->mainDoctor);

        $pattern = MedicalPattern::create([
            'name' => 'Uncomplicated malaria',
            'doctor_id' => $this->mainDoctor->id,
            'is_active' => true,
        ]);
        $pattern->items()->createMany([
            ['type' => 'history_of_presenting_complaint', 'data' => ['content' => 'Fever for two days.'], 'sort_order' => 1],
            ['type' => 'examination', 'data' => ['findings' => 'Febrile, no neck stiffness.'], 'sort_order' => 2],
            ['type' => 'task', 'data' => ['title' => 'Review after treatment', 'description' => 'Review in 48 hours.'], 'sort_order' => 3],
        ]);

        app(MedicalPatternService::class)->applyPattern($pattern, $record, [
            'history_of_presenting_complaint',
            'examination',
            'task',
        ], $this->additionalDoctor);

        $this->assertDatabaseHas('history_of_presenting_complaints', [
            'content' => 'Fever for two days.',
            'created_by' => $this->additionalDoctor->id,
            'source_pattern_id' => $pattern->id,
        ]);
        $this->assertDatabaseHas('physical_examinations', [
            'findings' => 'Febrile, no neck stiffness.',
            'created_by' => $this->additionalDoctor->id,
            'source_pattern_id' => $pattern->id,
        ]);
        $this->assertDatabaseHas('consultation_tasks', [
            'title' => 'Review after treatment',
            'created_by' => $this->additionalDoctor->id,
            'source_pattern_id' => $pattern->id,
        ]);
    }

    private function makeConsultingVisit(): array
    {
        $patient = Patient::factory()->create(['registered_by' => $this->mainDoctor->id]);
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'created_by' => $this->mainDoctor->id,
            'status' => VisitStatus::CONSULTING->value,
        ]);

        $route = VisitConsultationRoute::create([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'department_id' => $this->department->id,
            'service_id' => $this->service->id,
            'doctor_id' => $this->mainDoctor->id,
            'status' => VisitConsultationRoute::STATUS_ACTIVE,
            'routed_by' => $this->mainDoctor->id,
            'started_by' => $this->mainDoctor->id,
            'started_at' => now(),
        ]);

        app(ConsultationSessionService::class)->getOrCreateMedicalRecordForRoute($route, $this->mainDoctor);

        return [$visit, $route->fresh('medicalRecord')];
    }
}
