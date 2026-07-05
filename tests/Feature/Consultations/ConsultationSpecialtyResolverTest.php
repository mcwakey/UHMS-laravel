<?php

namespace Tests\Feature\Consultations;

use App\Enums\DepartmentType;
use App\Enums\ServiceType;
use App\Enums\VisitStatus;
use App\Models\ConsultationSpecialtyEntry;
use App\Models\ConsultationSpecialtyProfile;
use App\Models\ConsultationSpecialtyProfileMapping;
use App\Models\Department;
use App\Models\DoctorConsultationPreference;
use App\Models\Patient;
use App\Models\ServiceCatalog;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitConsultationRoute;
use App\Http\Middleware\ConvertBladeViewsToInertia;
use App\Services\Consultation\Specialty\ConsultationSpecialtyProfileResolver;
use App\Services\ConsultationSessionService;
use Database\Seeders\ConsultationSpecialtySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ConsultationSpecialtyResolverTest extends TestCase
{
    use RefreshDatabase;

    private User $doctor;

    private ConsultationSpecialtyProfileResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->seed(ConsultationSpecialtySeeder::class);
        $this->resolver = app(ConsultationSpecialtyProfileResolver::class);
        $this->doctor = User::factory()->create();

        $role = Role::findOrCreate('Doctor', 'web');
        $role->givePermissionTo(Permission::findOrCreate('consultations.view', 'web'));
        $this->doctor->assignRole($role);
    }

    public function test_resolver_falls_back_to_general_medicine(): void
    {
        ConsultationSpecialtyProfileMapping::query()->delete();

        $resolved = $this->resolver->resolve($this->doctor);

        $this->assertSame('general_medicine', $resolved->profile->code);
        $this->assertTrue($resolved->isFallback);
        $this->assertSame('fallback', $resolved->source);
    }

    public function test_department_type_mapping_resolves_profile(): void
    {
        $department = $this->department('General Medicine', 'GMD', DepartmentType::CONSULTATION);

        $resolved = $this->resolver->resolve($this->doctor, department: $department);

        $this->assertSame('general_medicine', $resolved->profile->code);
        $this->assertSame('department_type_mapping', $resolved->source);
        $this->assertFalse($resolved->isFallback);
    }

    public function test_department_mapping_beats_department_type_mapping(): void
    {
        $department = $this->department('Dental Clinic', 'DENTX', DepartmentType::CONSULTATION);
        $this->mapping(['department_id' => $department->id], 'dental', priority: 20);

        $resolved = $this->resolver->resolve($this->doctor, department: $department);

        $this->assertSame('dental', $resolved->profile->code);
        $this->assertSame('department_mapping', $resolved->source);
    }

    public function test_route_mapping_beats_department_mapping(): void
    {
        [$visit, $route, $department] = $this->consultationRouteFixture();
        $this->mapping(['department_id' => $department->id], 'dental', priority: 20);
        $this->mapping(['consultation_route_id' => $route->id], 'ophthalmology', priority: 30);

        $resolved = $this->resolver->resolve($this->doctor, visit: $visit, consultationRoute: $route, department: $department);

        $this->assertSame('ophthalmology', $resolved->profile->code);
        $this->assertSame('consultation_route_mapping', $resolved->source);
    }

    public function test_doctor_preference_resolves_when_no_mapping_exists(): void
    {
        ConsultationSpecialtyProfileMapping::query()->delete();
        $profile = $this->profile('physiotherapy');

        DoctorConsultationPreference::query()->create([
            'user_id' => $this->doctor->id,
            'default_consultation_specialty_profile_id' => $profile->id,
        ]);

        $resolved = $this->resolver->resolve($this->doctor);

        $this->assertSame('physiotherapy', $resolved->profile->code);
        $this->assertSame('doctor_preference', $resolved->source);
    }

    public function test_inactive_profile_is_ignored(): void
    {
        ConsultationSpecialtyProfileMapping::query()->delete();
        $department = $this->department('Dental Clinic', 'DENI', DepartmentType::CONSULTATION);
        $this->profile('dental')->update(['is_active' => false]);
        $this->mapping(['department_id' => $department->id], 'dental', priority: 20);

        $resolved = $this->resolver->resolve($this->doctor, department: $department);

        $this->assertSame('general_medicine', $resolved->profile->code);
        $this->assertTrue($resolved->isFallback);
    }

    public function test_inactive_mapping_is_ignored(): void
    {
        ConsultationSpecialtyProfileMapping::query()->delete();
        $department = $this->department('Dental Clinic', 'DENM', DepartmentType::CONSULTATION);
        $this->mapping(['department_id' => $department->id], 'dental', isActive: false, priority: 20);

        $resolved = $this->resolver->resolve($this->doctor, department: $department);

        $this->assertSame('general_medicine', $resolved->profile->code);
        $this->assertTrue($resolved->isFallback);
    }

    public function test_user_primary_department_mapping_resolves_profile(): void
    {
        ConsultationSpecialtyProfileMapping::query()->delete();
        $department = $this->department('Dental Clinic', 'DENU', DepartmentType::CONSULTATION);
        $this->doctor->departments()->attach($department->id, ['is_primary' => true]);
        $this->mapping(['department_id' => $department->id], 'dental', priority: 20);

        $resolved = $this->resolver->resolve($this->doctor);

        $this->assertSame('dental', $resolved->profile->code);
        $this->assertSame('user_department_mapping', $resolved->source);
    }

    public function test_resolved_context_includes_visible_ordered_sections(): void
    {
        $resolved = $this->resolver->resolve($this->doctor);
        $payload = $resolved->toArray();

        $this->assertNotEmpty($payload['sections']);
        $this->assertSame(
            collect($payload['sections'])->pluck('display_order')->sort()->values()->all(),
            collect($payload['sections'])->pluck('display_order')->values()->all(),
        );
        $this->assertTrue(collect($payload['sections'])->every(fn ($section) => $section['is_visible'] === true));
    }

    public function test_existing_consultation_entry_resolves_after_user_department_mapping_priority(): void
    {
        ConsultationSpecialtyProfileMapping::query()->delete();
        [$visit, $route, $department] = $this->consultationRouteFixture();

        ConsultationSpecialtyEntry::query()->create([
            'consultation_id' => $route->id,
            'consultation_specialty_profile_id' => $this->profile('ophthalmology')->id,
            'section_key' => 'summary',
            'entry' => ['text' => 'Existing specialist context'],
        ]);

        $resolved = $this->resolver->resolve($this->doctor, visit: $visit, consultationRoute: $route, department: $department);

        $this->assertSame('ophthalmology', $resolved->profile->code);
        $this->assertSame('existing_consultation_entry', $resolved->source);
    }

    public function test_controller_payload_includes_specialty_context(): void
    {
        [$visit, $route] = $this->consultationRouteFixture();

        $response = $this->withoutMiddleware(ConvertBladeViewsToInertia::class)
            ->actingAs($this->doctor)
            ->get(route('admin.consultations.routes.show', [$visit, $route]));

        $response->assertOk();
        $response->assertViewHas('specialtyContext', function (array $context): bool {
            return ($context['profile']['code'] ?? null) === 'general_medicine'
                && array_key_exists('sections', $context);
        });
    }

    private function profile(string $code): ConsultationSpecialtyProfile
    {
        return ConsultationSpecialtyProfile::query()->byCode($code)->firstOrFail();
    }

    private function mapping(array $attributes, string $profileCode, bool $isActive = true, int $priority = 0): ConsultationSpecialtyProfileMapping
    {
        return ConsultationSpecialtyProfileMapping::query()->create(array_merge([
            'consultation_specialty_profile_id' => $this->profile($profileCode)->id,
            'source' => 'test',
            'priority' => $priority,
            'is_active' => $isActive,
        ], $attributes));
    }

    private function consultationRouteFixture(): array
    {
        $department = $this->department('General Medicine', 'GEN' . random_int(100, 999), DepartmentType::CONSULTATION);
        $this->doctor->forceFill(['department_id' => $department->id])->save();

        $patient = Patient::factory()->create(['registered_by' => $this->doctor->id]);
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'created_by' => $this->doctor->id,
            'status' => VisitStatus::CONSULTING->value,
            'current_department_id' => $department->id,
        ]);
        $service = ServiceCatalog::query()->create([
            'name' => 'General Consultation',
            'code' => 'CON' . random_int(10000, 99999),
            'category' => ServiceType::CONSULTATION->value,
            'department_id' => $department->id,
            'department_type' => DepartmentType::CONSULTATION->value,
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

    private function department(string $name, string $code, DepartmentType $type): Department
    {
        return Department::factory()->create([
            'name' => $name,
            'code' => $code,
            'type' => $type->value,
            'status' => 'active',
        ]);
    }
}
