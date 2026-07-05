<?php

namespace Tests\Feature\Consultations;

use App\Enums\DepartmentType;
use App\Enums\ServiceType;
use App\Enums\VisitStatus;
use App\Http\Middleware\ConvertBladeViewsToInertia;
use App\Models\ConsultationSpecialtyEntry;
use App\Models\ConsultationSpecialtyOrderSet;
use App\Models\ConsultationSpecialtyOrderSetApplication;
use App\Models\ConsultationSpecialtyProfile;
use App\Models\ConsultationSpecialtyProfileMapping;
use App\Models\ConsultationTask;
use App\Models\Department;
use App\Models\Patient;
use App\Models\ServiceCatalog;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitConsultationRoute;
use App\Services\Consultation\Specialty\ConsultationSpecialtyOrderSetService;
use App\Services\ConsultationSessionService;
use Database\Seeders\ConsultationSpecialtySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Lang;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ConsultationSpecialtyOrderSetTest extends TestCase
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

    public function test_seeder_creates_starter_order_sets(): void
    {
        $this->assertDatabaseHas('consultation_specialty_order_sets', ['code' => 'physio_low_back_pain']);
        $this->assertDatabaseHas('consultation_specialty_order_sets', ['code' => 'physio_stroke_rehab']);
        $this->assertDatabaseHas('consultation_specialty_order_sets', ['code' => 'eye_conjunctivitis']);
        $this->assertDatabaseHas('consultation_specialty_order_sets', ['code' => 'eye_glaucoma_review']);
        $this->assertDatabaseHas('consultation_specialty_order_sets', ['code' => 'dental_extraction_prep']);
        $this->assertDatabaseHas('consultation_specialty_order_sets', ['code' => 'dental_abscess']);
    }

    public function test_order_sets_are_scoped_by_active_profile(): void
    {
        $service = app(ConsultationSpecialtyOrderSetService::class);

        $eye = collect($service->getWorkspaceOrderSets($this->context('ophthalmology')))->pluck('code');
        $dental = collect($service->getWorkspaceOrderSets($this->context('dental')))->pluck('code');
        $general = collect($service->getWorkspaceOrderSets($this->context('general_medicine')))->pluck('code');

        $this->assertTrue($eye->contains('eye_conjunctivitis'));
        $this->assertFalse($eye->contains('dental_extraction_prep'));
        $this->assertTrue($dental->contains('dental_extraction_prep'));
        $this->assertEmpty($general);
    }

    public function test_preview_returns_item_statuses(): void
    {
        [$visit, $route] = $this->consultationRouteFixture('Ophthalmology', 'EYE', 'ophthalmology');
        $orderSet = $this->orderSet('eye_conjunctivitis');

        $response = $this->actingAs($this->doctor)
            ->getJson(route('admin.consultations.specialty-order-sets.preview', [$visit, $orderSet]) . '?consultation_route_id=' . $route->id);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(8, 'preview.items');

        $items = collect($response->json('preview.items'));
        $this->assertTrue($items->firstWhere('apply_mode', 'patch_specialty_entry')['can_apply']);
        $this->assertTrue($items->firstWhere('type', 'diagnosis')['requires_manual_action']);
    }

    public function test_apply_creates_application_audit_and_items(): void
    {
        [$visit, $route] = $this->consultationRouteFixture('Dental', 'DEN', 'dental');
        $orderSet = $this->orderSet('dental_extraction_prep');

        $response = $this->actingAs($this->doctor)
            ->postJson(route('admin.consultations.specialty-order-sets.apply', [$visit, $orderSet]), [
                'consultation_route_id' => $route->id,
            ]);

        $response->assertOk()->assertJsonPath('success', true);

        $application = ConsultationSpecialtyOrderSetApplication::query()->firstOrFail();
        $this->assertSame($route->id, $application->consultation_id);
        $this->assertGreaterThan(0, $application->items()->count());
    }

    public function test_apply_patches_dental_consent_entry(): void
    {
        [$visit, $route] = $this->consultationRouteFixture('Dental', 'DEN', 'dental');
        $orderSet = $this->orderSet('dental_extraction_prep');

        $this->actingAs($this->doctor)
            ->postJson(route('admin.consultations.specialty-order-sets.apply', [$visit, $orderSet]), [
                'consultation_route_id' => $route->id,
            ])
            ->assertOk();

        $entry = ConsultationSpecialtyEntry::query()->where('section_key', 'consent')->firstOrFail();

        $this->assertTrue($entry->entry['consent_required']);
        $this->assertSame('Dental extraction consent', $entry->entry['consent_type']);
    }

    public function test_apply_patches_physio_treatment_plan(): void
    {
        [$visit, $route] = $this->consultationRouteFixture('Physiotherapy', 'PHY', 'physiotherapy', DepartmentType::TREATMENT);
        $orderSet = $this->orderSet('physio_low_back_pain');

        $this->actingAs($this->doctor)
            ->postJson(route('admin.consultations.specialty-order-sets.apply', [$visit, $orderSet]), [
                'consultation_route_id' => $route->id,
            ])
            ->assertOk();

        $entry = ConsultationSpecialtyEntry::query()->where('section_key', 'treatment_plan')->firstOrFail();

        $this->assertSame('Three times weekly', $entry->entry['session_frequency']);
        $this->assertSame(6, $entry->entry['number_of_sessions']);
    }

    public function test_apply_patches_eye_follow_up_warning_signs(): void
    {
        [$visit, $route] = $this->consultationRouteFixture('Ophthalmology', 'EYE', 'ophthalmology');
        $orderSet = $this->orderSet('eye_conjunctivitis');

        $this->actingAs($this->doctor)
            ->postJson(route('admin.consultations.specialty-order-sets.apply', [$visit, $orderSet]), [
                'consultation_route_id' => $route->id,
            ])
            ->assertOk();

        $entry = ConsultationSpecialtyEntry::query()->where('section_key', 'follow_up')->firstOrFail();

        $this->assertStringContainsString('vision worsens', $entry->entry['warning_signs']);
    }

    public function test_safe_task_items_create_consultation_tasks(): void
    {
        [$visit, $route] = $this->consultationRouteFixture('Dental', 'DEN', 'dental');
        $orderSet = $this->orderSet('dental_abscess');

        $this->actingAs($this->doctor)
            ->postJson(route('admin.consultations.specialty-order-sets.apply', [$visit, $orderSet]), [
                'consultation_route_id' => $route->id,
            ])
            ->assertOk();

        $this->assertTrue(ConsultationTask::query()
            ->where('consultation_route_id', $route->id)
            ->where('title', 'Review in 3 days')
            ->exists());
    }

    public function test_existing_specialty_entry_values_are_not_overwritten(): void
    {
        [$visit, $route] = $this->consultationRouteFixture('Dental', 'DEN', 'dental');
        $profile = $this->profile('dental');
        ConsultationSpecialtyEntry::query()->create([
            'consultation_id' => $route->id,
            'consultation_specialty_profile_id' => $profile->id,
            'section_key' => 'consent',
            'entry' => ['consent_required' => false, 'consent_type' => 'Existing consent text'],
            'created_by' => $this->doctor->id,
            'updated_by' => $this->doctor->id,
        ]);

        $this->actingAs($this->doctor)
            ->postJson(route('admin.consultations.specialty-order-sets.apply', [$visit, $this->orderSet('dental_extraction_prep')]), [
                'consultation_route_id' => $route->id,
            ])
            ->assertOk();

        $entry = ConsultationSpecialtyEntry::query()->where('section_key', 'consent')->firstOrFail();

        $this->assertFalse($entry->entry['consent_required']);
        $this->assertSame('Existing consent text', $entry->entry['consent_type']);
    }

    public function test_inactive_order_set_is_rejected(): void
    {
        [$visit, $route] = $this->consultationRouteFixture('Dental', 'DEN', 'dental');
        $orderSet = $this->orderSet('dental_abscess');
        $orderSet->update(['is_active' => false]);

        $this->actingAs($this->doctor)
            ->getJson(route('admin.consultations.specialty-order-sets.preview', [$visit, $orderSet]) . '?consultation_route_id=' . $route->id)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['order_set']);
    }

    public function test_wrong_profile_order_set_is_rejected(): void
    {
        [$visit, $route] = $this->consultationRouteFixture('Ophthalmology', 'EYE', 'ophthalmology');

        $this->actingAs($this->doctor)
            ->postJson(route('admin.consultations.specialty-order-sets.apply', [$visit, $this->orderSet('dental_abscess')]), [
                'consultation_route_id' => $route->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['order_set']);
    }

    public function test_workspace_payload_includes_order_sets(): void
    {
        [$visit, $route] = $this->consultationRouteFixture('Ophthalmology', 'EYE', 'ophthalmology');

        $response = $this->withoutMiddleware(ConvertBladeViewsToInertia::class)
            ->actingAs($this->doctor)
            ->get(route('admin.consultations.routes.show', [$visit, $route]));

        $response->assertOk();
        $response->assertViewHas('specialtyOrderSets', fn (array $sets) => collect($sets)->pluck('code')->contains('eye_conjunctivitis'));
        $response->assertSee('Conjunctivitis Care');
    }

    public function test_localisation_keys_exist(): void
    {
        foreach (['en', 'fr'] as $locale) {
            foreach (['title', 'preview', 'apply_selected', 'manual_action', 'partially_applied', 'from_order_set'] as $key) {
                $this->assertTrue(Lang::has("consultation_specialties.order_sets.{$key}", $locale));
            }
        }
    }

    private function context(string $profileCode): array
    {
        $profile = $this->profile($profileCode);

        return ['profile' => ['id' => $profile->id]];
    }

    private function profile(string $code): ConsultationSpecialtyProfile
    {
        return ConsultationSpecialtyProfile::query()->byCode($code)->firstOrFail();
    }

    private function orderSet(string $code): ConsultationSpecialtyOrderSet
    {
        return ConsultationSpecialtyOrderSet::query()->where('code', $code)->firstOrFail();
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
            'code' => 'ORD'.random_int(10000, 99999),
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

        return [$visit, $route->fresh(['department', 'medicalRecord'])];
    }
}
