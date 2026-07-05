<?php

namespace Tests\Feature\Consultations;

use App\Enums\DepartmentType;
use App\Enums\ServiceType;
use App\Enums\VisitStatus;
use App\Http\Middleware\ConvertBladeViewsToInertia;
use App\Models\ConsultationSpecialtyProfile;
use App\Models\ConsultationSpecialtyProfileMapping;
use App\Models\Department;
use App\Models\Patient;
use App\Models\ServiceCatalog;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitConsultationRoute;
use App\Services\Consultation\Specialty\ConsultationSpecialtyLayoutService;
use App\Services\Consultation\Specialty\ConsultationSpecialtyProfileResolver;
use App\Services\Consultation\Specialty\ConsultationSpecialtySectionComponentRegistry;
use App\Services\ConsultationSessionService;
use Database\Seeders\ConsultationSpecialtySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ConsultationSpecialtyLayoutTest extends TestCase
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
        $role->givePermissionTo(Permission::findOrCreate('consultations.view', 'web'));
        $this->doctor->assignRole($role);
    }

    public function test_general_layout_preserves_core_section_order(): void
    {
        $layout = app(ConsultationSpecialtyLayoutService::class)
            ->buildLayout($this->resolvedContext('general_medicine'));

        $this->assertSame([
            'patient_summary',
            'complaints',
            'hopc',
            'examination',
            'diagnosis',
            'investigations',
            'prescription',
            'procedures',
            'tasks',
            'notes',
            'summary',
            'completion_readiness',
        ], collect($layout['sections'])->pluck('key')->all());
    }

    public function test_core_section_registry_resolves_known_sections_to_non_generic_components(): void
    {
        $registry = app(ConsultationSpecialtySectionComponentRegistry::class);

        foreach (['complaints', 'hopc', 'examination', 'diagnosis', 'investigations', 'prescription', 'procedures', 'tasks', 'summary'] as $key) {
            $this->assertNotSame($registry->fallbackComponent(), $registry->resolveComponent($key));
        }
    }

    public function test_unknown_section_uses_generic_fallback(): void
    {
        $registry = app(ConsultationSpecialtySectionComponentRegistry::class);

        $this->assertSame($registry->fallbackComponent(), $registry->resolveComponent('unmapped_specialist_section'));
    }

    public function test_seeded_specialist_layout_orders_are_preserved(): void
    {
        $expected = [
            'physiotherapy' => [
                'patient_summary',
                'presenting_problem',
                'pain_assessment',
                'functional_limitation',
                'physical_assessment',
                'treatment_plan',
                'therapy_session',
                'home_exercise_plan',
                'tasks',
                'progress_notes',
                'summary',
                'completion_readiness',
            ],
            'ophthalmology' => [
                'patient_summary',
                'eye_complaint',
                'visual_acuity',
                'refraction',
                'iop',
                'eye_examination',
                'diagnosis',
                'investigations',
                'procedures',
                'prescription',
                'follow_up',
                'summary',
                'completion_readiness',
            ],
            'dental' => [
                'patient_summary',
                'dental_complaint',
                'tooth_chart',
                'oral_examination',
                'dental_diagnosis',
                'dental_xray',
                'dental_procedures',
                'consent',
                'prescription',
                'follow_up',
                'summary',
                'completion_readiness',
            ],
        ];

        foreach ($expected as $code => $order) {
            $layout = app(ConsultationSpecialtyLayoutService::class)
                ->buildLayout($this->resolvedContext($code));

            $this->assertSame($order, collect($layout['sections'])->pluck('key')->all());
        }
    }

    public function test_required_badge_metadata_is_preserved(): void
    {
        $profile = ConsultationSpecialtyProfile::query()->byCode('dental')->firstOrFail();
        $profile->sections()->where('section_key', 'consent')->update(['is_required' => true]);

        $layout = app(ConsultationSpecialtyLayoutService::class)
            ->buildLayout($this->resolvedContext('dental'));

        $this->assertTrue(collect($layout['sections'])->firstWhere('key', 'consent')['is_required']);
    }

    public function test_invalid_or_missing_context_falls_back_to_general_layout(): void
    {
        $layout = app(ConsultationSpecialtyLayoutService::class)->buildLayout([]);

        $this->assertSame('general_medicine', $layout['profile']['code']);
        $this->assertSame('complaints', collect($layout['sections'])->pluck('key')->values()[1]);
    }

    public function test_consultation_workspace_renders_specialty_identity_and_structured_sections(): void
    {
        [$visit, $route, $department] = $this->consultationRouteFixture('Physiotherapy', 'PHY');
        ConsultationSpecialtyProfileMapping::query()->whereNotNull('department_id')->delete();
        ConsultationSpecialtyProfileMapping::query()->create([
            'consultation_specialty_profile_id' => ConsultationSpecialtyProfile::query()->byCode('physiotherapy')->firstOrFail()->id,
            'department_id' => $department->id,
            'source' => 'test',
            'priority' => 50,
            'is_active' => true,
        ]);

        $response = $this->withoutMiddleware(ConvertBladeViewsToInertia::class)
            ->actingAs($this->doctor)
            ->get(route('admin.consultations.routes.show', [$visit, $route]));

        $response->assertOk();
        $response->assertSee('Physiotherapy Workspace');
        $response->assertSee('Pain Assessment');
        $response->assertSee(__('consultation_specialties.actions.save_section'));
        $response->assertSee('name="pain_score"', false);
        $response->assertSee('id="complaints-section"', false);
    }

    private function resolvedContext(string $profileCode): array
    {
        $profile = ConsultationSpecialtyProfile::query()
            ->byCode($profileCode)
            ->with('sections')
            ->firstOrFail();

        return [
            'profile' => [
                'id' => $profile->id,
                'code' => $profile->code,
                'name' => $profile->name,
                'translated_name' => $profile->translatedName(),
                'icon' => $profile->icon,
                'color' => $profile->color,
            ],
            'source' => 'test',
            'is_fallback' => false,
            'sections' => $profile->activeSections()->get()->map(fn ($section) => [
                'key' => $section->section_key,
                'label' => $section->label,
                'translated_label' => __('consultation_specialties.sections.'.$section->section_key),
                'component' => $section->component,
                'display_order' => $section->display_order,
                'is_required' => $section->is_required,
                'is_visible' => $section->is_visible,
                'config' => $section->config ?? [],
            ])->all(),
        ];
    }

    private function consultationRouteFixture(string $departmentName, string $departmentCode): array
    {
        $department = Department::factory()->create([
            'name' => $departmentName,
            'code' => $departmentCode,
            'type' => DepartmentType::TREATMENT->value,
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
            'code' => 'LAY' . random_int(10000, 99999),
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

        app(ConsultationSessionService::class)->getOrCreateMedicalRecordForRoute($route, $this->doctor);

        return [$visit, $route->fresh(['department', 'medicalRecord']), $department];
    }
}
