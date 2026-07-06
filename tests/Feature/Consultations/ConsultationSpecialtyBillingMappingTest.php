<?php

namespace Tests\Feature\Consultations;

use App\Enums\DepartmentType;
use App\Enums\ServiceType;
use App\Enums\VisitStatus;
use App\Http\Middleware\ConvertBladeViewsToInertia;
use App\Models\ConsultationSpecialtyBillingApplication;
use App\Models\ConsultationSpecialtyProfile;
use App\Models\ConsultationSpecialtyProfileMapping;
use App\Models\ConsultationSpecialtyServiceMapping;
use App\Models\Department;
use App\Models\InvoiceItem;
use App\Models\Patient;
use App\Models\ServiceCatalog;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitConsultationRoute;
use App\Services\Consultation\Specialty\ConsultationSpecialtyBillingMappingService;
use App\Services\ConsultationSessionService;
use Database\Seeders\ConsultationSpecialtySeeder;
use Database\Seeders\ConsultationSpecialtyServiceMappingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Lang;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ConsultationSpecialtyBillingMappingTest extends TestCase
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
        foreach (['consultations.view', 'consultations.create', 'invoices.create'] as $permission) {
            $role->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
        $this->doctor->assignRole($role);
    }

    public function test_resolver_prefers_route_mapping_before_department_and_default(): void
    {
        [$visit, $route] = $this->consultationRouteFixture('Ophthalmology', 'EYE', 'ophthalmology');
        $profile = $this->profile('ophthalmology');
        $defaultService = $this->service('Eye Default Consultation', $route->department);
        $departmentService = $this->service('Eye Department Consultation', $route->department);
        $routeService = $this->service('Eye Route Consultation', $route->department);

        $this->mapping($profile, $defaultService, ['is_default' => true, 'priority' => 0]);
        $this->mapping($profile, $departmentService, ['department_id' => $route->department_id, 'priority' => 10]);
        $routeMapping = $this->mapping($profile, $routeService, ['consultation_route_id' => $route->id, 'priority' => 20]);

        $resolved = app(ConsultationSpecialtyBillingMappingService::class)
            ->resolveDefaultConsultationService($profile, $route->department, $route);

        $this->assertTrue($resolved->is($routeMapping));
    }

    public function test_workspace_payload_exposes_specialty_billing_context(): void
    {
        [$visit, $route] = $this->consultationRouteFixture('Dental', 'DEN', 'dental');
        $service = $this->service('Dental Consultation', $route->department);
        $this->mapping($this->profile('dental'), $service, ['is_default' => true]);

        $response = $this->withoutMiddleware(ConvertBladeViewsToInertia::class)
            ->actingAs($this->doctor)
            ->get(route('admin.consultations.routes.show', [$visit, $route]));

        $response->assertOk();
        $response->assertViewHas('specialtyBillingContext', function (array $context) use ($service) {
            return data_get($context, 'default_service.service.id') === $service->id
                && data_get($context, 'default_service.already_billed') === false
                && filled(data_get($context, 'preview_url'))
                && filled(data_get($context, 'apply_url'));
        });
    }

    public function test_apply_uses_billing_service_and_writes_application_audit(): void
    {
        [$visit, $route] = $this->consultationRouteFixture('Physiotherapy', 'PHY', 'physiotherapy', DepartmentType::TREATMENT);
        $service = $this->service('Physiotherapy Consultation', $route->department, 150);
        $mapping = $this->mapping($this->profile('physiotherapy'), $service, ['is_default' => true]);

        $response = $this->actingAs($this->doctor)
            ->postJson(route('admin.consultations.specialty-billing.apply', [$visit]), [
                'consultation_route_id' => $route->id,
                'mapping_id' => $mapping->id,
                'confirmed' => true,
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('result.status', ConsultationSpecialtyBillingApplication::STATUS_APPLIED);

        $this->assertDatabaseHas('invoice_items', [
            'visit_id' => $visit->id,
            'service_catalog_id' => $service->id,
            'source_type' => InvoiceItem::SOURCE_SPECIALTY_SERVICE_MAPPING,
        ]);
        $this->assertDatabaseHas('consultation_specialty_billing_applications', [
            'consultation_id' => $route->id,
            'consultation_specialty_service_mapping_id' => $mapping->id,
            'status' => ConsultationSpecialtyBillingApplication::STATUS_APPLIED,
        ]);
    }

    public function test_duplicate_apply_is_skipped_when_service_already_billed(): void
    {
        [$visit, $route] = $this->consultationRouteFixture('Dental', 'DEN', 'dental');
        $service = $this->service('Dental Consultation', $route->department);
        $mapping = $this->mapping($this->profile('dental'), $service, ['is_default' => true]);

        $payload = [
            'consultation_route_id' => $route->id,
            'mapping_id' => $mapping->id,
            'confirmed' => true,
        ];

        $this->actingAs($this->doctor)
            ->postJson(route('admin.consultations.specialty-billing.apply', [$visit]), $payload)
            ->assertOk();

        $this->actingAs($this->doctor)
            ->postJson(route('admin.consultations.specialty-billing.apply', [$visit]), $payload)
            ->assertOk()
            ->assertJsonPath('result.status', ConsultationSpecialtyBillingApplication::STATUS_SKIPPED_DUPLICATE);

        $this->assertSame(1, InvoiceItem::query()
            ->where('visit_id', $visit->id)
            ->where('service_catalog_id', $service->id)
            ->count());
    }

    public function test_seeder_maps_existing_services_only(): void
    {
        $service = $this->service('Dental Consultation');
        $serviceCount = ServiceCatalog::query()->count();

        $this->seed(ConsultationSpecialtyServiceMappingSeeder::class);

        $this->assertDatabaseHas('consultation_specialty_service_mappings', [
            'consultation_specialty_profile_id' => $this->profile('dental')->id,
            'service_id' => $service->id,
            'mapping_context' => ConsultationSpecialtyServiceMapping::CONTEXT_CONSULTATION,
            'auto_bill' => false,
        ]);
        $this->assertDatabaseCount('service_catalog', $serviceCount);
    }

    public function test_localisation_keys_exist(): void
    {
        foreach (['en', 'fr'] as $locale) {
            foreach (['title', 'preview_billing', 'apply_charge', 'applied', 'skipped_duplicate', 'unsupported'] as $key) {
                $this->assertTrue(Lang::has("consultation_specialties.billing.{$key}", $locale));
            }

            foreach (ConsultationSpecialtyServiceMapping::CONTEXTS as $context) {
                $this->assertTrue(Lang::has("consultation_specialties.billing.contexts.{$context}", $locale));
            }
        }
    }

    private function profile(string $code): ConsultationSpecialtyProfile
    {
        return ConsultationSpecialtyProfile::query()->byCode($code)->firstOrFail();
    }

    private function service(string $name, ?Department $department = null, int $price = 100): ServiceCatalog
    {
        return ServiceCatalog::query()->create([
            'name' => $name,
            'code' => 'BIL'.random_int(10000, 99999),
            'category' => ServiceType::CONSULTATION->value,
            'department_id' => $department?->id,
            'department_type' => $this->departmentTypeValue($department),
            'price' => $price,
            'is_active' => true,
            'is_billable' => true,
        ]);
    }

    private function mapping(ConsultationSpecialtyProfile $profile, ServiceCatalog $service, array $overrides = []): ConsultationSpecialtyServiceMapping
    {
        return ConsultationSpecialtyServiceMapping::query()->create(array_merge([
            'consultation_specialty_profile_id' => $profile->id,
            'service_id' => $service->id,
            'mapping_context' => ConsultationSpecialtyServiceMapping::CONTEXT_CONSULTATION,
            'billing_trigger' => ConsultationSpecialtyServiceMapping::TRIGGER_MANUAL,
            'priority' => 0,
            'is_default' => false,
            'auto_bill' => false,
            'requires_confirmation' => true,
            'is_active' => true,
        ], $overrides));
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
        $service = $this->service($departmentName.' Consultation', $department);

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

        return [$visit, $route->fresh(['department', 'medicalRecord'])];
    }

    private function departmentTypeValue(?Department $department): ?string
    {
        $type = $department?->type;

        return $type instanceof \BackedEnum ? $type->value : $type;
    }
}
