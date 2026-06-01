<?php

namespace Tests\Feature;

use App\Enums\DepartmentType;
use App\Enums\ServiceType;
use App\Enums\VisitStatus;
use App\Models\Complaint;
use App\Models\ComplaintCatalogue;
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
use Database\Seeders\ComplaintCatalogueSeeder;
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
            'complaints.view',
            'complaints.create',
            'complaints.edit_own',
            'complaints.delete_own',
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

    public function test_complaint_catalogue_seeder_is_idempotent_and_search_returns_active_entries(): void
    {
        $this->seed(ComplaintCatalogueSeeder::class);
        $count = ComplaintCatalogue::count();

        $this->seed(ComplaintCatalogueSeeder::class);

        $this->assertSame($count, ComplaintCatalogue::count());

        $headache = ComplaintCatalogue::where('name', 'Headache')->firstOrFail();
        $headache->update(['is_active' => false]);

        $this->actingAs($this->mainDoctor)
            ->getJson(route('admin.complaints.search', ['q' => 'headache']))
            ->assertOk()
            ->assertJsonMissing(['id' => $headache->id]);

        $this->actingAs($this->mainDoctor)
            ->getJson(route('admin.complaints.search', ['q' => 'chest']))
            ->assertOk()
            ->assertJsonFragment(['name' => 'Chest pain']);
    }

    public function test_doctor_can_record_catalogue_and_custom_patient_complaints(): void
    {
        $this->seed(ComplaintCatalogueSeeder::class);
        [$visit, $route] = $this->makeConsultingVisit();
        $catalogue = ComplaintCatalogue::where('name', 'Chest pain')->where('category', 'Cardiovascular')->firstOrFail();

        $this->actingAs($this->mainDoctor)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->post(route('admin.consultations.complaints.store', $visit), [
                'consultation_route_id' => $route->id,
                'complaint_catalogue_id' => $catalogue->id,
                'duration' => '2',
                'duration_unit' => 'days',
                'severity' => 'severe',
                'notes' => 'Radiates to left arm.',
            ])
            ->assertOk()
            ->assertJsonPath('complaint.description', 'Chest pain')
            ->assertJsonPath('complaint.complaint_catalogue_id', $catalogue->id);

        $this->actingAs($this->mainDoctor)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->post(route('admin.consultations.complaints.store', $visit), [
                'consultation_route_id' => $route->id,
                'description' => 'Feels unusually cold at night',
                'duration' => '1',
                'duration_unit' => 'weeks',
                'severity' => 'mild',
            ])
            ->assertOk()
            ->assertJsonPath('complaint.description', 'Feels unusually cold at night')
            ->assertJsonPath('complaint.complaint_catalogue_id', null);

        $this->assertDatabaseHas('complaints', [
            'medical_record_id' => $route->medicalRecord->id,
            'consultation_route_id' => $route->id,
            'visit_id' => $visit->id,
            'patient_id' => $visit->patient_id,
            'department_id' => $this->department->id,
            'created_by' => $this->mainDoctor->id,
            'complaint_catalogue_id' => $catalogue->id,
            'description' => 'Chest pain',
            'duration' => '2',
            'duration_unit' => 'days',
            'severity' => 'severe',
            'notes' => 'Radiates to left arm.',
        ]);
        $this->assertDatabaseHas('complaints', [
            'medical_record_id' => $route->medicalRecord->id,
            'description' => 'Feels unusually cold at night',
            'complaint_catalogue_id' => null,
        ]);
        $this->assertDatabaseCount('history_of_presenting_complaints', 0);

        $summary = app(ConsultationSummaryService::class)->forRecord($route->medicalRecord->fresh());
        $summaryComplaint = collect($summary['sections']['complaints'])->firstWhere('content', 'Chest pain');

        $this->assertSame('Chest pain', $summaryComplaint['details']['Catalogue']);
        $this->assertSame('Cardiovascular', $summaryComplaint['details']['Category']);
        $this->assertSame('2 days', $summaryComplaint['details']['Duration']);
        $this->assertSame('Severe', $summaryComplaint['details']['Severity']);

        $preview = app(VisitPreviewService::class)->build($visit->fresh());
        $previewComplaint = collect($preview['timeline'])->firstWhere('source_type', 'complaint');

        $this->assertSame('Chest pain', $previewComplaint['description']);
        $this->assertSame('Chest pain', $previewComplaint['details']['Complaint']);
        $this->assertSame('Cardiovascular', $previewComplaint['details']['Category']);
    }

    public function test_view_only_claims_user_cannot_edit_original_clinical_complaint(): void
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

        $claimsRole = Role::findOrCreate('Claims Officer', 'web');
        $claimsRole->givePermissionTo(Permission::findOrCreate('complaints.view', 'web'));
        /** @var User $claimsUser */
        $claimsUser = User::factory()->create(['department_id' => $this->department->id]);
        $claimsUser->assignRole($claimsRole);

        $this->actingAs($claimsUser)
            ->patch(route('admin.patient-complaints.update', $complaint), [
                'description' => 'Changed by claims',
            ])
            ->assertForbidden();

        $this->assertSame('Chest pain', $complaint->fresh()->description);
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
            ['type' => 'complaint', 'data' => ['description' => 'Fever', 'duration' => '2', 'duration_unit' => 'days', 'severity' => 'moderate'], 'sort_order' => 0],
            ['type' => 'history_of_presenting_complaint', 'data' => ['content' => 'Fever for two days.'], 'sort_order' => 1],
            ['type' => 'examination', 'data' => ['findings' => 'Febrile, no neck stiffness.'], 'sort_order' => 2],
            ['type' => 'task', 'data' => ['title' => 'Review after treatment', 'description' => 'Review in 48 hours.'], 'sort_order' => 3],
        ]);

        app(MedicalPatternService::class)->applyPattern($pattern, $record, [
            'complaint',
            'history_of_presenting_complaint',
            'examination',
            'task',
        ], $this->additionalDoctor);

        $this->assertDatabaseHas('complaints', [
            'description' => 'Fever',
            'duration' => '2',
            'duration_unit' => 'days',
            'severity' => 'moderate',
            'created_by' => $this->additionalDoctor->id,
            'source_pattern_id' => $pattern->id,
        ]);
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

    public function test_history_summary_page_renders_as_document_with_print_button(): void
    {
        [$visit, $route] = $this->makeConsultingVisit();

        // Main doctor records a complaint
        $this->actingAs($this->mainDoctor)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->post(route('admin.consultations.complaints.store', $visit), [
                'consultation_route_id' => $route->id,
                'description' => 'Severe headache for 3 days',
                'severity' => 'moderate',
            ])
            ->assertOk();

        // Additional doctor records a HOPC entry
        $this->actingAs($this->additionalDoctor)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->post(route('admin.consultations.hopc.store', $visit), [
                'consultation_route_id' => $route->id,
                'content' => 'No fever, no photophobia.',
            ])
            ->assertOk();

        $response = $this->actingAs($this->mainDoctor)->get(route('admin.consultations.history', $visit));

        $response->assertOk();
        $response->assertSee('CONSULTATION SUMMARY', false);
        $response->assertSee('Patient Information', false);
        $response->assertSee('Visit Information', false);
        $response->assertSee('Care Team', false);
        $response->assertSee('Print Summary', false);
        $response->assertSee('window.print()', false);
        $response->assertSee('Complaints', false);
        $response->assertSee('History of Presenting Complaint', false);
        $response->assertSee('Investigations', false);
        $response->assertSee('Procedures', false);
        $response->assertSee('Dr. Kofi Mensah', false);
        $response->assertSee('Dr. Ama Boateng', false);
        $response->assertSee('Main Doctor', false);
        $response->assertSee('Contributor', false);
        $response->assertDontSee('Unknown user', false);
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
