<?php

namespace Tests\Feature\Consultations;

use App\Enums\DepartmentType;
use App\Enums\ServiceType;
use App\Enums\VisitStatus;
use App\Http\Middleware\ConvertBladeViewsToInertia;
use App\Models\ConsultationSpecialtyEntry;
use App\Models\ConsultationSpecialtyProfile;
use App\Models\ConsultationSpecialtyProfileMapping;
use App\Models\DoctorConsultationPreference;
use App\Models\Patient;
use App\Models\QueueEntry;
use App\Models\Department;
use App\Models\ServiceCatalog;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitConsultationRoute;
use App\Services\Consultation\Specialty\DoctorSpecialtyWorkspaceService;
use App\Services\ConsultationSessionService;
use Database\Seeders\ConsultationSpecialtySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Lang;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class DoctorSpecialtyWorkspaceTest extends TestCase
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

    public function test_workspace_payload_exists_and_renders_header(): void
    {
        [$visit, $route] = $this->consultationRouteFixture('Ophthalmology', 'EYE', 'ophthalmology');

        $response = $this->withoutMiddleware(ConvertBladeViewsToInertia::class)
            ->actingAs($this->doctor)
            ->get(route('admin.consultations.routes.show', [$visit, $route]));

        $response->assertOk()
            ->assertViewHas('doctorSpecialtyWorkspace')
            ->assertSee('Dr. '.$this->doctor->full_name)
            ->assertSee(__('consultation_specialties.workspace.quick_actions'));
    }

    public function test_general_medicine_remains_light(): void
    {
        [, $route, $context] = $this->consultationRouteFixture('General Medicine', 'GEN', 'general_medicine');

        $payload = app(DoctorSpecialtyWorkspaceService::class)
            ->build($this->doctor, $route, $context)
            ->toArray();

        $keys = collect($payload['quick_actions'])->pluck('key');

        $this->assertTrue($keys->contains('complaints'));
        $this->assertTrue($keys->contains('generate_summary'));
        $this->assertFalse($keys->contains('visual_acuity'));
        $this->assertLessThanOrEqual(6, $keys->count());
    }

    public function test_specialty_quick_actions_are_profile_specific(): void
    {
        $cases = [
            ['Physiotherapy', 'PHY', 'physiotherapy', DepartmentType::TREATMENT, ['pain_assessment', 'treatment_plan', 'therapy_session', 'home_exercise_plan', 'order_sets', 'generate_summary', 'readiness']],
            ['Ophthalmology', 'EYE', 'ophthalmology', DepartmentType::CONSULTATION, ['visual_acuity', 'refraction', 'iop', 'eye_examination', 'order_sets', 'generate_summary', 'readiness']],
            ['Dental', 'DEN', 'dental', DepartmentType::CONSULTATION, ['tooth_chart', 'oral_examination', 'dental_diagnosis', 'dental_procedure', 'consent', 'order_sets', 'generate_summary', 'readiness']],
            ['Obstetrics', 'OBS', 'obstetrics', DepartmentType::CONSULTATION, ['obstetric_history', 'current_pregnancy', 'fetal_assessment', 'risk_assessment', 'birth_plan', 'order_sets']],
            ['ENT', 'ENT', 'ent', DepartmentType::CONSULTATION, ['ent_complaint', 'ear_assessment', 'nose_assessment', 'throat_assessment', 'hearing_balance_assessment', 'order_sets']],
            ['Emergency', 'EMR', 'emergency', DepartmentType::EMERGENCY, ['triage_summary', 'primary_survey', 'vitals_monitoring', 'emergency_interventions', 'disposition', 'handover']],
            ['Surgery', 'SUR', 'surgery', DepartmentType::CONSULTATION, ['surgical_complaint', 'wound_assessment', 'local_or_abdominal_exam', 'procedure_plan', 'theatre_referral', 'post_op_instructions']],
        ];

        foreach ($cases as [$name, $code, $profileCode, $type, $expected]) {
            [, $route, $context] = $this->consultationRouteFixture($name, $code, $profileCode, $type);
            $keys = collect(app(DoctorSpecialtyWorkspaceService::class)->build($this->doctor, $route, $context)->toArray()['quick_actions'])->pluck('key');

            foreach ($expected as $key) {
                $this->assertTrue($keys->contains($key), "{$profileCode} is missing {$key}");
            }
        }
    }

    public function test_pinned_actions_and_compact_mode_persist(): void
    {
        $profile = $this->profile('ophthalmology');
        DoctorConsultationPreference::query()->create([
            'user_id' => $this->doctor->id,
            'default_consultation_specialty_profile_id' => $profile->id,
            'pinned_actions' => [],
        ]);

        $this->actingAs($this->doctor)
            ->patchJson(route('admin.consultations.preferences.pinned-actions.update'), [
                'pinned_actions' => ['visual_acuity', 'iop'],
            ])
            ->assertOk();

        $this->actingAs($this->doctor)
            ->patchJson(route('admin.consultations.preferences.layout.update'), [
                'preferred_layout' => 'compact',
                'compact_mode' => true,
            ])
            ->assertOk();

        $preference = DoctorConsultationPreference::query()->where('user_id', $this->doctor->id)->firstOrFail();

        $this->assertSame(['visual_acuity', 'iop'], $preference->pinned_actions);
        $this->assertTrue($preference->compact_mode);
        $this->assertSame('compact', $preference->preferred_layout);
    }

    public function test_invalid_pinned_action_is_rejected(): void
    {
        DoctorConsultationPreference::query()->create([
            'user_id' => $this->doctor->id,
            'default_consultation_specialty_profile_id' => $this->profile('ophthalmology')->id,
        ]);

        $this->actingAs($this->doctor)
            ->patchJson(route('admin.consultations.preferences.pinned-actions.update'), [
                'pinned_actions' => ['unsafe_mutation'],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['pinned_actions.0']);
    }

    public function test_metrics_are_safe_and_alerts_reflect_readiness(): void
    {
        [, $route, $context] = $this->consultationRouteFixture('Dental', 'DEN', 'dental');
        QueueEntry::query()->create([
            'visit_id' => $route->visit_id,
            'department_id' => $route->department_id,
            'queue_number' => 1,
            'priority' => 'normal',
            'status' => 'waiting',
        ]);
        ConsultationSpecialtyEntry::query()->create([
            'consultation_id' => $route->id,
            'consultation_specialty_profile_id' => $this->profile('dental')->id,
            'section_key' => 'consent',
            'entry' => ['consent_required' => true, 'consent_obtained' => false],
            'created_by' => $this->doctor->id,
            'updated_by' => $this->doctor->id,
        ]);

        $payload = app(DoctorSpecialtyWorkspaceService::class)->build($this->doctor, $route, $context, [
            'specialtyReadiness' => app(\App\Services\Consultation\Specialty\ConsultationSpecialtyReadinessService::class)->evaluate($route, $context),
            'specialtyOrderSets' => [['id' => 1]],
            'specialtySummaryBuilder' => ['available' => true],
        ])->toArray();

        $this->assertTrue(collect($payload['metrics'])->every(fn ($metric) => is_int($metric['value']) || $metric['value'] === null));
        $this->assertStringNotContainsString($route->patient?->full_name ?? 'Patient', json_encode($payload['metrics']));
        $this->assertTrue(collect($payload['alerts'])->pluck('key')->contains('readiness_blocks'));
    }

    public function test_localisation_keys_exist(): void
    {
        foreach (['en', 'fr'] as $locale) {
            foreach (['workspace.quick_actions', 'workspace.compact_mode', 'quick_actions.visual_acuity', 'quick_actions.consent', 'metrics.waiting_today'] as $key) {
                $this->assertTrue(Lang::has("consultation_specialties.{$key}", $locale));
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
        ]);
        $service = ServiceCatalog::query()->create([
            'name' => $departmentName.' Consultation',
            'code' => 'DWS'.random_int(10000, 99999),
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

        return [$visit, $route->fresh(['department', 'medicalRecord', 'patient']), $context];
    }

    private function profile(string $code): ConsultationSpecialtyProfile
    {
        return ConsultationSpecialtyProfile::query()->byCode($code)->firstOrFail();
    }
}
