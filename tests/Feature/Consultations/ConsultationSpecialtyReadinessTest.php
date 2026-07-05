<?php

namespace Tests\Feature\Consultations;

use App\Enums\DepartmentType;
use App\Enums\ServiceType;
use App\Enums\VisitStatus;
use App\Http\Middleware\ConvertBladeViewsToInertia;
use App\Models\ConsultationSpecialtyEntry;
use App\Models\ConsultationSpecialtyOrderSet;
use App\Models\ConsultationSpecialtyProfile;
use App\Models\ConsultationSpecialtyProfileMapping;
use App\Models\Department;
use App\Models\Patient;
use App\Models\ServiceCatalog;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitConsultationRoute;
use App\Services\Consultation\Specialty\ConsultationSpecialtyReadinessService;
use App\Services\ConsultationSessionService;
use Database\Seeders\ConsultationSpecialtySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Lang;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ConsultationSpecialtyReadinessTest extends TestCase
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

    public function test_general_readiness_falls_back_to_existing_logic(): void
    {
        [, $route, $context] = $this->consultationRouteFixture('General Medicine', 'GEN', 'general_medicine');
        $this->addCoreClinicalRecord($route, ['complaint']);

        $result = app(ConsultationSpecialtyReadinessService::class)->evaluate(
            $route,
            $context,
            ['completionReadiness' => app(\App\Services\Consultation\ConsultationCompletionReadinessService::class)->forRoute($route)]
        );

        $this->assertTrue($result->isFallback);
        $this->assertFalse($result->canComplete);
        $this->assertTrue(collect($result->blockingItems)->pluck('key')->contains('examination'));
    }

    public function test_physio_blocks_when_required_entries_are_missing(): void
    {
        [, $route, $context] = $this->consultationRouteFixture('Physiotherapy', 'PHY', 'physiotherapy', DepartmentType::TREATMENT);

        $result = app(ConsultationSpecialtyReadinessService::class)->evaluate($route, $context);

        $this->assertSame('blocked', $result->status);
        $this->assertFalse($result->canComplete);
        $this->assertEqualsCanonicalizing([
            'presenting_problem_recorded',
            'pain_assessment_recorded',
            'physical_assessment_recorded',
            'treatment_plan_recorded',
        ], collect($result->blockingItems)->pluck('key')->all());
    }

    public function test_physio_ready_after_required_entries_with_schedule_warning_only(): void
    {
        [, $route, $context] = $this->consultationRouteFixture('Physiotherapy', 'PHY', 'physiotherapy', DepartmentType::TREATMENT);
        $profile = $this->profile('physiotherapy');

        $this->entry($route, $profile, 'presenting_problem', ['problem_description' => 'Low back pain']);
        $this->entry($route, $profile, 'pain_assessment', ['pain_score' => 6]);
        $this->entry($route, $profile, 'physical_assessment', ['range_of_motion' => 'Reduced lumbar flexion']);
        $this->entry($route, $profile, 'treatment_plan', ['treatment_goals' => 'Reduce pain']);

        $result = app(ConsultationSpecialtyReadinessService::class)->evaluate($route, $context);

        $this->assertTrue($result->canComplete);
        $this->assertSame('needs_attention', $result->status);
        $this->assertTrue(collect($result->warningItems)->pluck('key')->contains('session_schedule_missing'));
    }

    public function test_ophthalmology_blocks_when_visual_acuity_missing_and_iop_is_warning_only(): void
    {
        [, $route, $context] = $this->consultationRouteFixture('Ophthalmology', 'EYE', 'ophthalmology');
        $profile = $this->profile('ophthalmology');
        $this->addCoreClinicalRecord($route, ['complaint', 'diagnosis']);
        $this->entry($route, $profile, 'eye_examination', ['cornea' => 'Clear']);

        $result = app(ConsultationSpecialtyReadinessService::class)->evaluate($route, $context);

        $this->assertFalse($result->canComplete);
        $this->assertTrue(collect($result->blockingItems)->pluck('key')->contains('visual_acuity_recorded'));
        $this->assertTrue(collect($result->warningItems)->pluck('key')->contains('iop_missing'));

        $this->entry($route, $profile, 'visual_acuity', ['right_eye_unaided' => '6/9']);
        $ready = app(ConsultationSpecialtyReadinessService::class)->evaluate($route, $context);

        $this->assertTrue($ready->canComplete);
    }

    public function test_dental_diagnosis_can_come_from_specialty_entry_and_consent_blocks_conditionally(): void
    {
        [, $route, $context] = $this->consultationRouteFixture('Dental', 'DEN', 'dental');
        $profile = $this->profile('dental');
        $this->addCoreClinicalRecord($route, ['complaint']);
        $this->entry($route, $profile, 'tooth_chart', ['tooth_number' => '36', 'condition' => 'Caries']);
        $this->entry($route, $profile, 'dental_diagnosis', ['diagnosis_text' => 'Irreversible pulpitis']);
        $this->entry($route, $profile, 'dental_procedures', ['procedure_planned' => 'Extraction']);
        $this->entry($route, $profile, 'consent', ['consent_required' => true, 'consent_obtained' => false]);

        $blocked = app(ConsultationSpecialtyReadinessService::class)->evaluate($route, $context);

        $this->assertFalse($blocked->canComplete);
        $this->assertTrue(collect($blocked->blockingItems)->pluck('key')->contains('consent_obtained_if_required'));
        $this->assertTrue(collect($blocked->warningItems)->pluck('key')->contains('xray_missing_if_extraction_planned'));

        $this->entry($route, $profile, 'consent', ['consent_required' => true, 'consent_obtained' => true]);
        $ready = app(ConsultationSpecialtyReadinessService::class)->evaluate($route, $context);

        $this->assertTrue($ready->canComplete);
        $this->assertTrue(collect($ready->warningItems)->pluck('key')->contains('xray_missing_if_extraction_planned'));
    }

    public function test_wrong_profile_entries_do_not_satisfy_active_readiness(): void
    {
        [, $route, $context] = $this->consultationRouteFixture('Dental', 'DEN', 'dental');
        $this->entry($route, $this->profile('physiotherapy'), 'treatment_plan', ['session_frequency' => 'Weekly']);

        $result = app(ConsultationSpecialtyReadinessService::class)->evaluate($route, $context);

        $this->assertFalse($result->canComplete);
        $this->assertTrue(collect($result->blockingItems)->pluck('key')->contains('oral_or_tooth_exam_recorded'));
    }

    public function test_order_set_patches_contribute_to_readiness_without_falsely_completing_other_items(): void
    {
        [$visit, $route, $context] = $this->consultationRouteFixture('Physiotherapy', 'PHY', 'physiotherapy', DepartmentType::TREATMENT);
        $orderSet = ConsultationSpecialtyOrderSet::query()->where('code', 'physio_low_back_pain')->firstOrFail();

        $this->actingAs($this->doctor)
            ->postJson(route('admin.consultations.specialty-order-sets.apply', [$visit, $orderSet]), [
                'consultation_route_id' => $route->id,
            ])
            ->assertOk();

        $result = app(ConsultationSpecialtyReadinessService::class)->evaluate($route, $context);

        $this->assertFalse($result->canComplete);
        $this->assertTrue(collect($result->completedItems)->pluck('key')->contains('treatment_plan_recorded'));
        $this->assertTrue(collect($result->blockingItems)->pluck('key')->contains('physical_assessment_recorded'));
    }

    public function test_workspace_payload_includes_specialty_readiness(): void
    {
        [$visit, $route] = $this->consultationRouteFixture('Dental', 'DEN', 'dental');

        $response = $this->withoutMiddleware(ConvertBladeViewsToInertia::class)
            ->actingAs($this->doctor)
            ->get(route('admin.consultations.routes.show', [$visit, $route]));

        $response->assertOk();
        $response->assertViewHas('specialtyReadiness');
        $response->assertSee(__('consultation_specialties.readiness.title'));
    }

    public function test_completion_route_blocks_specialist_missing_requirements_but_not_warnings(): void
    {
        [$visit, $route] = $this->consultationRouteFixture('Physiotherapy', 'PHY', 'physiotherapy', DepartmentType::TREATMENT);
        $this->addCoreClinicalRecord($route);

        $this->actingAs($this->doctor)
            ->postJson(route('admin.consultations.routes.complete', [$visit, $route]), ['notes' => 'Ready'])
            ->assertStatus(422)
            ->assertJsonFragment(['code' => 'specialty_presenting_problem_recorded']);

        $profile = $this->profile('physiotherapy');
        $this->entry($route, $profile, 'presenting_problem', ['problem_description' => 'Low back pain']);
        $this->entry($route, $profile, 'pain_assessment', ['pain_score' => 4]);
        $this->entry($route, $profile, 'physical_assessment', ['gait' => 'Antalgic']);
        $this->entry($route, $profile, 'treatment_plan', ['treatment_goals' => 'Restore function']);

        $this->actingAs($this->doctor)
            ->postJson(route('admin.consultations.routes.complete', [$visit, $route]), ['notes' => 'Ready'])
            ->assertOk();

        $this->assertSame(VisitConsultationRoute::STATUS_COMPLETED, $route->fresh()->status);
    }

    public function test_localisation_keys_exist(): void
    {
        foreach (['en', 'fr'] as $locale) {
            foreach (['title', 'blocked', 'visual_acuity_missing', 'consent_required_missing', 'xray_missing_if_extraction_planned'] as $key) {
                $this->assertTrue(Lang::has("consultation_specialties.readiness.{$key}", $locale));
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
            'code' => 'RDY'.random_int(10000, 99999),
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

        $context = app(\App\Services\Consultation\Specialty\ConsultationSpecialtyProfileResolver::class)
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
            $record->complaints()->create($base + ['description' => 'Pain']);
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
