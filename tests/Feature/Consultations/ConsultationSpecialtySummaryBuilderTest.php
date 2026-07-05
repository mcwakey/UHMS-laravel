<?php

namespace Tests\Feature\Consultations;

use App\Enums\DepartmentType;
use App\Enums\ServiceType;
use App\Enums\VisitStatus;
use App\Http\Middleware\ConvertBladeViewsToInertia;
use App\Models\ConsultationSpecialtyEntry;
use App\Models\ConsultationSpecialtyProfile;
use App\Models\ConsultationSpecialtyProfileMapping;
use App\Models\Department;
use App\Models\Patient;
use App\Models\ServiceCatalog;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitConsultationRoute;
use App\Services\Consultation\Specialty\ConsultationSpecialtyProfileResolver;
use App\Services\Consultation\Specialty\ConsultationSpecialtySummaryBuilder;
use App\Services\ConsultationSessionService;
use Database\Seeders\ConsultationSpecialtySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Lang;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ConsultationSpecialtySummaryBuilderTest extends TestCase
{
    use RefreshDatabase;

    private User $doctor;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(ConsultationSpecialtySeeder::class);

        $this->doctor = User::factory()->create();
        $role = Role::findOrCreate('Doctor', 'web');
        foreach (['consultations.view', 'consultations.create'] as $permission) {
            $role->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
        $this->doctor->assignRole($role);
    }

    public function test_general_summary_uses_safe_fallback(): void
    {
        [, $route, $context] = $this->consultationRouteFixture('General Medicine', 'GEN', 'general_medicine');
        $this->addCoreClinicalRecord($route, ['complaint', 'diagnosis']);

        $summary = app(ConsultationSpecialtySummaryBuilder::class)->build($route, $context);

        $this->assertTrue($summary->isFallback);
        $this->assertStringContainsString('Headache', $summary->plainText());
        $this->assertStringContainsString('Working diagnosis', $summary->plainText());
    }

    public function test_physiotherapy_summary_includes_structured_fields_and_arrays(): void
    {
        [, $route, $context] = $this->consultationRouteFixture('Physiotherapy', 'PHY', 'physiotherapy', DepartmentType::TREATMENT);
        $profile = $this->profile('physiotherapy');
        $this->entry($route, $profile, 'presenting_problem', ['problem_description' => 'Low back pain', 'affected_area' => 'Lumbar spine']);
        $this->entry($route, $profile, 'pain_assessment', ['pain_score' => 7, 'pain_location' => 'Lower back']);
        $this->entry($route, $profile, 'physical_assessment', ['gait' => 'Stable gait']);
        $this->entry($route, $profile, 'treatment_plan', ['modalities' => ['Exercise', 'Manual therapy'], 'session_frequency' => 'Three times weekly']);

        $summary = app(ConsultationSpecialtySummaryBuilder::class)->build($route, $context);

        $this->assertFalse($summary->isFallback);
        $this->assertStringContainsString('Low back pain', $summary->plainText());
        $this->assertStringContainsString('Pain score: 7', $summary->plainText());
        $this->assertStringContainsString('Exercise, Manual therapy', $summary->plainText());
        $this->assertStringContainsString('Three times weekly', $summary->plainText());
    }

    public function test_ophthalmology_summary_includes_eye_fields_and_follow_up(): void
    {
        [, $route, $context] = $this->consultationRouteFixture('Ophthalmology', 'EYE', 'ophthalmology');
        $profile = $this->profile('ophthalmology');
        $this->addCoreClinicalRecord($route, ['complaint', 'diagnosis']);
        $this->entry($route, $profile, 'visual_acuity', ['right_eye_unaided' => '6/9', 'left_eye_unaided' => '6/6']);
        $this->entry($route, $profile, 'iop', ['right_eye_iop' => 16, 'left_eye_iop' => 15, 'method' => 'Tonometry']);
        $this->entry($route, $profile, 'eye_examination', ['conjunctiva' => 'Injected', 'cornea' => 'Clear']);
        $this->entry($route, $profile, 'follow_up', ['warning_signs' => 'Return if vision worsens']);

        $summary = app(ConsultationSpecialtySummaryBuilder::class)->build($route, $context);

        $this->assertStringContainsString('6/9', $summary->plainText());
        $this->assertStringContainsString('16', $summary->plainText());
        $this->assertStringContainsString('Tonometry', $summary->plainText());
        $this->assertStringContainsString('Injected', $summary->plainText());
        $this->assertStringContainsString('Return if vision worsens', $summary->plainText());
    }

    public function test_dental_summary_includes_dental_fields_booleans_and_procedure(): void
    {
        [, $route, $context] = $this->consultationRouteFixture('Dental', 'DEN', 'dental');
        $profile = $this->profile('dental');
        $this->addCoreClinicalRecord($route, ['complaint']);
        $this->entry($route, $profile, 'tooth_chart', ['tooth_number' => '36', 'condition' => 'Caries']);
        $this->entry($route, $profile, 'oral_examination', ['gingiva' => 'Swollen']);
        $this->entry($route, $profile, 'dental_diagnosis', ['diagnosis_text' => 'Pulpitis']);
        $this->entry($route, $profile, 'dental_procedures', ['procedure_planned' => 'Extraction planned']);
        $this->entry($route, $profile, 'consent', ['consent_required' => true, 'consent_obtained' => true]);

        $summary = app(ConsultationSpecialtySummaryBuilder::class)->build($route, $context);

        $this->assertStringContainsString('Tooth number: 36', $summary->plainText());
        $this->assertStringContainsString('Pulpitis', $summary->plainText());
        $this->assertStringContainsString('Extraction planned', $summary->plainText());
        $this->assertStringContainsString('Consent obtained: Yes', $summary->plainText());
    }

    public function test_empty_fields_are_skipped_and_readiness_warnings_are_included(): void
    {
        [, $route, $context] = $this->consultationRouteFixture('Physiotherapy', 'PHY', 'physiotherapy', DepartmentType::TREATMENT);
        $this->entry($route, $this->profile('physiotherapy'), 'presenting_problem', ['problem_description' => 'Knee pain', 'affected_area' => '']);

        $summary = app(ConsultationSpecialtySummaryBuilder::class)->build($route, $context);

        $this->assertStringContainsString('Knee pain', $summary->plainText());
        $this->assertStringNotContainsString('Affected area:', $summary->plainText());
        $this->assertNotEmpty($summary->warnings);
        $this->assertStringContainsString('Record the pain assessment', implode(' ', $summary->warnings));
    }

    public function test_preview_endpoint_returns_json_and_does_not_persist_final_note(): void
    {
        [$visit, $route] = $this->consultationRouteFixture('Dental', 'DEN', 'dental');
        $route->medicalRecord->forceFill(['final_note' => 'Existing clinician note'])->save();
        $this->entry($route, $this->profile('dental'), 'dental_diagnosis', ['diagnosis_text' => 'Dental abscess']);

        $response = $this->actingAs($this->doctor)
            ->getJson(route('admin.consultations.specialty-summary.preview', $visit).'?consultation_route_id='.$route->id);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('summary.isFallback', false)
            ->assertJsonFragment(['title' => __('consultation_specialties.summary_builder.dental_title')]);

        $this->assertSame('Existing clinician note', $route->medicalRecord->fresh()->final_note);
    }

    public function test_workspace_payload_includes_summary_builder_metadata(): void
    {
        [$visit, $route] = $this->consultationRouteFixture('Ophthalmology', 'EYE', 'ophthalmology');

        $response = $this->withoutMiddleware(ConvertBladeViewsToInertia::class)
            ->actingAs($this->doctor)
            ->get(route('admin.consultations.routes.show', [$visit, $route]));

        $response->assertOk();
        $response->assertViewHas('specialtySummaryBuilder', fn (array $payload) => $payload['available'] === true && filled($payload['preview_url'] ?? null));
        $response->assertSee(__('consultation_specialties.summary_builder.title'));
    }

    public function test_wrong_profile_entries_do_not_appear_in_active_summary(): void
    {
        [, $route, $context] = $this->consultationRouteFixture('Dental', 'DEN', 'dental');
        $this->entry($route, $this->profile('physiotherapy'), 'treatment_plan', ['session_frequency' => 'Weekly physio']);

        $summary = app(ConsultationSpecialtySummaryBuilder::class)->build($route, $context);

        $this->assertStringNotContainsString('Weekly physio', $summary->plainText());
    }

    public function test_localisation_keys_exist(): void
    {
        foreach (['en', 'fr'] as $locale) {
            foreach (['title', 'generate', 'preview', 'insert', 'not_saved_yet', 'sections.visual_acuity', 'sections.consent'] as $key) {
                $this->assertTrue(Lang::has("consultation_specialties.summary_builder.{$key}", $locale));
            }
        }
    }

    private function consultationRouteFixture(string $departmentName, string $departmentCode, string $profileCode, DepartmentType $type = DepartmentType::CONSULTATION): array
    {
        $department = Department::factory()->create([
            'name' => $departmentName,
            'code' => $departmentCode.random_int(100, 999),
            'type' => $type->value,
            'status' => 'active',
        ]);
        $this->doctor->forceFill(['department_id' => $department->id])->save();

        $patient = Patient::factory()->create(['registered_by' => $this->doctor->id]);
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'created_by' => $this->doctor->id,
            'status' => VisitStatus::CONSULTING->value,
            'current_department_id' => $department->id,
            'chief_complaint' => null,
        ]);
        $service = ServiceCatalog::query()->create([
            'name' => $departmentName.' Consultation',
            'code' => 'SUM'.random_int(10000, 99999),
            'category' => ServiceType::CONSULTATION->value,
            'department_id' => $department->id,
            'department_type' => $department->type->value,
            'price' => 100,
            'is_active' => true,
            'is_billable' => true,
        ]);

        $route = VisitConsultationRoute::query()->create([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'department_id' => $department->id,
            'service_id' => $service->id,
            'doctor_id' => $this->doctor->id,
            'status' => VisitConsultationRoute::STATUS_ACTIVE,
            'routed_by' => $this->doctor->id,
            'started_by' => $this->doctor->id,
            'started_at' => now(),
            'activated_at' => now(),
        ]);

        ConsultationSpecialtyProfileMapping::query()->create([
            'consultation_specialty_profile_id' => $this->profile($profileCode)->id,
            'department_id' => $department->id,
            'source' => 'test',
            'priority' => 50,
            'is_active' => true,
        ]);

        app(ConsultationSessionService::class)->getOrCreateMedicalRecordForRoute($route, $this->doctor);

        $context = app(ConsultationSpecialtyProfileResolver::class)
            ->resolve($this->doctor, visit: $visit, consultationRoute: $route->fresh('department'), department: $department);

        return [$visit, $route->fresh(['department', 'medicalRecord']), $context];
    }

    private function profile(string $code): ConsultationSpecialtyProfile
    {
        return ConsultationSpecialtyProfile::query()->byCode($code)->firstOrFail();
    }

    private function entry(VisitConsultationRoute $route, ConsultationSpecialtyProfile $profile, string $sectionKey, array $entry): void
    {
        ConsultationSpecialtyEntry::query()->updateOrCreate([
            'consultation_id' => $route->id,
            'consultation_specialty_profile_id' => $profile->id,
            'section_key' => $sectionKey,
        ], [
            'entry' => $entry,
            'created_by' => $this->doctor->id,
            'updated_by' => $this->doctor->id,
        ]);
    }

    private function addCoreClinicalRecord(VisitConsultationRoute $route, array $sections = ['complaint', 'examination', 'diagnosis', 'plan']): void
    {
        $record = $route->medicalRecord;
        $base = [
            'consultation_route_id' => $route->id,
            'visit_id' => $route->visit_id,
            'patient_id' => $route->patient_id,
            'department_id' => $route->department_id,
            'doctor_id' => $this->doctor->id,
            'created_by' => $this->doctor->id,
        ];

        if (in_array('complaint', $sections, true)) {
            $record->complaints()->create($base + ['description' => 'Headache']);
        }

        if (in_array('examination', $sections, true)) {
            $record->physicalExaminations()->create($base + ['findings' => 'Stable']);
        }

        if (in_array('diagnosis', $sections, true)) {
            $record->diagnoses()->create($base + [
                'description' => 'Working diagnosis',
                'type' => 'provisional',
                'is_primary' => true,
            ]);
        }

        if (in_array('plan', $sections, true)) {
            $record->treatments()->create($base + [
                'type' => 'advice',
                'description' => 'Clinical plan',
            ]);
        }
    }
}
