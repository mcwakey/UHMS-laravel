<?php

namespace Tests\Feature\Consultations;

use App\Enums\DepartmentType;
use App\Enums\ServiceType;
use App\Enums\VisitStatus;
use App\Http\Middleware\ConvertBladeViewsToInertia;
use App\Models\ConsultationSpecialtyFavorite;
use App\Models\ConsultationSpecialtyOrderSet;
use App\Models\ConsultationSpecialtyOrderSetApplication;
use App\Models\ConsultationSpecialtyOrderSetItem;
use App\Models\ConsultationSpecialtyProfile;
use App\Models\ConsultationSpecialtyProfileMapping;
use App\Models\ConsultationSpecialtySection;
use App\Models\ConsultationSpecialtyServiceMapping;
use App\Models\Department;
use App\Models\Patient;
use App\Models\ServiceCatalog;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitConsultationRoute;
use App\Services\Consultation\Specialty\ConsultationSpecialtyFavoriteService;
use App\Services\Consultation\Specialty\ConsultationSpecialtyOrderSetService;
use App\Services\Consultation\Specialty\ConsultationSpecialtyProfileResolver;
use App\Services\ConsultationSessionService;
use Database\Seeders\ConsultationSpecialtySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Lang;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ConsultationSpecialtyAdminConfigurationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $viewer;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(ConsultationSpecialtySeeder::class);

        $this->admin = User::factory()->create();
        $adminRole = Role::findOrCreate('Specialty Admin', 'web');
        foreach ([
            'consultation-specialties.view',
            'consultation-specialties.create',
            'consultation-specialties.update',
            'consultation-specialties.delete',
            'consultation-specialties.configure',
            'consultations.view',
            'consultations.create',
        ] as $permission) {
            $adminRole->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
        $this->admin->assignRole($adminRole);

        $this->viewer = User::factory()->create();
        $viewerRole = Role::findOrCreate('Specialty Viewer', 'web');
        $viewerRole->givePermissionTo(Permission::findOrCreate('consultation-specialties.view', 'web'));
        $this->viewer->assignRole($viewerRole);
    }

    public function test_permission_protection_for_admin_pages(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.consultation-specialties.index'))
            ->assertForbidden();

        $this->actingAs($this->viewer)
            ->get(route('admin.consultation-specialties.index'))
            ->assertOk();

        $this->actingAs($this->viewer)
            ->post(route('admin.consultation-specialties.store'), $this->profilePayload('new_profile'))
            ->assertForbidden();
    }

    public function test_admin_can_create_update_and_protect_profiles(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.consultation-specialties.store'), $this->profilePayload('sports_medicine'))
            ->assertRedirect();

        $profile = ConsultationSpecialtyProfile::query()->where('code', 'sports_medicine')->firstOrFail();
        $this->assertDatabaseHas('consultation_specialty_profiles', ['code' => 'sports_medicine', 'name' => 'Sports Medicine']);

        $this->actingAs($this->admin)
            ->patch(route('admin.consultation-specialties.update', $profile), $this->profilePayload('sports_medicine', ['name' => 'Sports and Rehab']))
            ->assertRedirect();

        $this->assertDatabaseHas('consultation_specialty_profiles', ['id' => $profile->id, 'name' => 'Sports and Rehab']);

        $this->actingAs($this->admin)
            ->post(route('admin.consultation-specialties.store'), $this->profilePayload('sports_medicine'))
            ->assertSessionHasErrors('code');

        $general = ConsultationSpecialtyProfile::query()->where('code', ConsultationSpecialtyProfile::GENERAL_MEDICINE)->firstOrFail();
        $this->actingAs($this->admin)
            ->delete(route('admin.consultation-specialties.destroy', $general))
            ->assertRedirect();

        $this->assertTrue($general->fresh()->is_active);
    }

    public function test_section_management_rejects_duplicates_and_unsafe_components(): void
    {
        $profile = $this->profile('physiotherapy');

        $payload = [
            'section_key' => 'gait_review',
            'label' => 'Gait Review',
            'component' => 'consultations.partials.specialty.generic-section',
            'display_order' => 99,
            'is_visible' => 1,
        ];

        $this->actingAs($this->admin)
            ->post(route('admin.consultation-specialties.sections.store', $profile), $payload)
            ->assertRedirect();

        $this->actingAs($this->admin)
            ->post(route('admin.consultation-specialties.sections.store', $profile), $payload)
            ->assertSessionHasErrors('section_key');

        $this->actingAs($this->admin)
            ->post(route('admin.consultation-specialties.sections.store', $profile), array_merge($payload, ['section_key' => 'unsafe_section', 'component' => '../../unsafe']))
            ->assertSessionHasErrors('component');

        $this->actingAs($this->admin)
            ->post(route('admin.consultation-specialties.sections.reorder', $profile), [
                'orders' => [$profile->sections()->firstOrFail()->id => 123],
            ])
            ->assertRedirect();
    }

    public function test_mapping_management_and_resolver_safety(): void
    {
        $profile = $this->profile('physiotherapy');
        $department = Department::factory()->create(['type' => DepartmentType::TREATMENT->value, 'status' => 'active']);

        $this->actingAs($this->admin)
            ->post(route('admin.consultation-specialties.mappings.store'), [
                'consultation_specialty_profile_id' => $profile->id,
                'source' => 'admin',
                'priority' => 10,
                'is_active' => 1,
            ])
            ->assertSessionHasErrors('mapping_target');

        $this->actingAs($this->admin)
            ->post(route('admin.consultation-specialties.mappings.store'), [
                'consultation_specialty_profile_id' => $profile->id,
                'department_type' => DepartmentType::TREATMENT->value,
                'source' => 'admin',
                'priority' => 10,
                'is_active' => 0,
            ])
            ->assertRedirect();

        $resolved = app(ConsultationSpecialtyProfileResolver::class)->resolve($this->admin, department: $department);
        $this->assertNotSame('physiotherapy', $resolved->profile->code);

        $mapping = ConsultationSpecialtyProfileMapping::query()->where('department_type', DepartmentType::TREATMENT->value)->firstOrFail();
        $this->actingAs($this->admin)
            ->patch(route('admin.consultation-specialties.mappings.update', $mapping), [
                'consultation_specialty_profile_id' => $profile->id,
                'department_type' => DepartmentType::TREATMENT->value,
                'source' => 'admin',
                'priority' => 100,
                'is_active' => 1,
            ])
            ->assertRedirect();

        $resolved = app(ConsultationSpecialtyProfileResolver::class)->resolve($this->admin, department: $department);
        $this->assertSame('physiotherapy', $resolved->profile->code);
    }

    public function test_favorites_are_safe_and_affect_workspace_defaults(): void
    {
        $profile = $this->profile('ophthalmology');

        $this->actingAs($this->admin)
            ->post(route('admin.consultation-specialties.favorites.store', $profile), [
                'favorite_type' => ConsultationSpecialtyFavorite::TYPE_CLINICAL_INSTRUCTION,
                'code' => 'avoid_eye_rubbing',
                'label' => 'Avoid eye rubbing',
                'is_active' => 1,
            ])
            ->assertRedirect();

        $favorites = app(ConsultationSpecialtyFavoriteService::class)->getFavoritesForProfile($profile, ConsultationSpecialtyFavorite::TYPE_CLINICAL_INSTRUCTION);
        $this->assertTrue($favorites->pluck('code')->contains('avoid_eye_rubbing'));

        $favorite = ConsultationSpecialtyFavorite::query()->where('code', 'avoid_eye_rubbing')->firstOrFail();
        $this->actingAs($this->admin)
            ->patch(route('admin.consultation-specialties.favorites.update', [$profile, $favorite]), [
                'favorite_type' => ConsultationSpecialtyFavorite::TYPE_CLINICAL_INSTRUCTION,
                'code' => 'avoid_eye_rubbing',
                'label' => 'Avoid eye rubbing',
                'is_active' => 0,
            ])
            ->assertRedirect();

        $favorites = app(ConsultationSpecialtyFavoriteService::class)->getFavoritesForProfile($profile, ConsultationSpecialtyFavorite::TYPE_CLINICAL_INSTRUCTION);
        $this->assertFalse($favorites->pluck('code')->contains('avoid_eye_rubbing'));

        $this->actingAs($this->admin)
            ->post(route('admin.consultation-specialties.favorites.store', $profile), [
                'favorite_type' => ConsultationSpecialtyFavorite::TYPE_DRUG,
                'label' => 'Unsafe',
                'favoritable_type' => 'App\\Models\\User',
                'favoritable_id' => $this->admin->id,
            ])
            ->assertSessionHasErrors('favoritable_type');
    }

    public function test_order_sets_and_items_are_validated_safely(): void
    {
        $profile = $this->profile('dental');

        $this->actingAs($this->admin)
            ->post(route('admin.consultation-specialties.order-sets.store', $profile), $this->orderSetPayload('admin_dental_review'))
            ->assertRedirect();

        $orderSet = ConsultationSpecialtyOrderSet::query()->where('code', 'admin_dental_review')->firstOrFail();

        $this->actingAs($this->admin)
            ->post(route('admin.consultation-specialties.order-sets.store', $profile), $this->orderSetPayload('admin_dental_review'))
            ->assertSessionHasErrors('code');

        $this->actingAs($this->admin)
            ->post(route('admin.consultation-specialties.order-sets.items.store', [$profile, $orderSet]), [
                'item_type' => 'specialty_entry_patch',
                'label' => 'Consent reminder',
                'target_section' => 'consent',
                'target_field' => 'consent_type',
                'payload_json' => json_encode(['merge' => ['consent_type' => 'Procedure consent']]),
                'apply_mode' => 'patch_specialty_entry',
                'is_active' => 1,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('consultation_specialty_order_set_items', ['consultation_specialty_order_set_id' => $orderSet->id, 'label' => 'Consent reminder']);

        $this->actingAs($this->admin)
            ->post(route('admin.consultation-specialties.order-sets.items.store', [$profile, $orderSet]), [
                'item_type' => 'specialty_entry_patch',
                'label' => 'Invalid field',
                'target_section' => 'consent',
                'target_field' => 'not_a_field',
                'payload_json' => json_encode(['merge' => ['not_a_field' => 'No']]),
                'apply_mode' => 'patch_specialty_entry',
            ])
            ->assertSessionHasErrors('target_field');

        $this->actingAs($this->admin)
            ->post(route('admin.consultation-specialties.order-sets.items.store', [$profile, $orderSet]), [
                'item_type' => 'note',
                'label' => 'Invalid mode',
                'apply_mode' => 'unsafe_mode',
            ])
            ->assertSessionHasErrors('apply_mode');

        $this->actingAs($this->admin)
            ->post(route('admin.consultation-specialties.order-sets.items.store', [$profile, $orderSet]), [
                'item_type' => 'note',
                'label' => 'Invalid JSON',
                'payload_json' => '{bad-json',
                'apply_mode' => 'suggest',
            ])
            ->assertSessionHasErrors('payload_json');

        [$visit, $route] = $this->consultationRouteFixture('Dental', 'DEN', 'dental');
        $preview = app(ConsultationSpecialtyOrderSetService::class)->previewOrderSet($route, $orderSet->fresh(['profile']), $this->admin);
        $this->assertTrue(collect($preview['items'])->pluck('label')->contains('Consent reminder'));
    }

    public function test_admin_can_manage_service_mappings_with_auto_bill_guard(): void
    {
        $profile = $this->profile('dental');
        $service = ServiceCatalog::query()->create([
            'name' => 'Dental Consultation',
            'code' => 'DENT-BILL',
            'category' => ServiceType::CONSULTATION->value,
            'price' => 100,
            'is_active' => true,
            'is_billable' => true,
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.consultation-specialties.service-mappings.index'))
            ->assertOk()
            ->assertSee(__('consultation_specialties.billing.admin.service_mappings'));

        $payload = [
            'consultation_specialty_profile_id' => $profile->id,
            'service_id' => $service->id,
            'mapping_context' => ConsultationSpecialtyServiceMapping::CONTEXT_CONSULTATION,
            'billing_trigger' => ConsultationSpecialtyServiceMapping::TRIGGER_MANUAL,
            'priority' => 5,
            'is_default' => 1,
            'auto_bill' => 1,
            'requires_confirmation' => 1,
            'is_active' => 1,
        ];

        $this->actingAs($this->admin)
            ->post(route('admin.consultation-specialties.service-mappings.store'), $payload)
            ->assertSessionHasErrors('auto_bill_acknowledged');

        $this->actingAs($this->admin)
            ->post(route('admin.consultation-specialties.service-mappings.store'), $payload + ['auto_bill_acknowledged' => 1])
            ->assertRedirect();

        $this->assertDatabaseHas('consultation_specialty_service_mappings', [
            'consultation_specialty_profile_id' => $profile->id,
            'service_id' => $service->id,
            'auto_bill' => true,
        ]);
    }

    public function test_order_set_with_history_deactivates_instead_of_deleting(): void
    {
        [$visit, $route] = $this->consultationRouteFixture('Dental', 'DEN', 'dental');
        $profile = $this->profile('dental');
        $orderSet = ConsultationSpecialtyOrderSet::query()->create($this->orderSetPayload('history_set') + [
            'consultation_specialty_profile_id' => $profile->id,
        ]);

        ConsultationSpecialtyOrderSetApplication::query()->create([
            'consultation_id' => $route->id,
            'consultation_specialty_order_set_id' => $orderSet->id,
            'consultation_specialty_profile_id' => $profile->id,
            'applied_by' => $this->admin->id,
            'status' => 'applied',
        ]);

        $this->actingAs($this->admin)
            ->delete(route('admin.consultation-specialties.order-sets.destroy', [$profile, $orderSet]))
            ->assertRedirect();

        $this->assertFalse($orderSet->fresh()->is_active);
    }

    public function test_audit_and_localisation_keys_exist(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.consultation-specialties.store'), $this->profilePayload('audit_profile'))
            ->assertRedirect();

        $this->assertTrue(Activity::query()->where('event', 'PROFILE_CREATED')->exists());

        foreach (['en', 'fr'] as $locale) {
            foreach (['title', 'profiles', 'sections', 'mappings', 'favorites', 'order_sets', 'order_set_items', 'saved'] as $key) {
                $this->assertTrue(Lang::has("consultation_specialties.admin.{$key}", $locale));
            }
        }
    }

    private function profilePayload(string $code, array $overrides = []): array
    {
        return array_merge([
            'code' => $code,
            'name' => str($code)->replace('_', ' ')->title()->toString(),
            'description' => 'Admin configured profile',
            'department_type' => DepartmentType::CONSULTATION->value,
            'icon' => 'ti ti-stethoscope',
            'color' => 'primary',
            'sort_order' => 50,
            'is_active' => 1,
            'metadata_json' => json_encode(['source' => 'test']),
        ], $overrides);
    }

    private function orderSetPayload(string $code, array $overrides = []): array
    {
        return array_merge([
            'code' => $code,
            'name' => str($code)->replace('_', ' ')->title()->toString(),
            'category' => 'admin',
            'description' => 'Admin configured order set',
            'sort_order' => 10,
            'is_active' => 1,
        ], $overrides);
    }

    private function profile(string $code): ConsultationSpecialtyProfile
    {
        return ConsultationSpecialtyProfile::query()->where('code', $code)->firstOrFail();
    }

    private function consultationRouteFixture(string $departmentName, string $departmentCode, string $profileCode, DepartmentType $type = DepartmentType::CONSULTATION): array
    {
        $department = Department::factory()->create([
            'name' => $departmentName,
            'code' => $departmentCode.random_int(100, 999),
            'type' => $type->value,
            'status' => 'active',
        ]);
        $this->admin->forceFill(['department_id' => $department->id])->save();

        $patient = Patient::factory()->create(['registered_by' => $this->admin->id]);
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'created_by' => $this->admin->id,
            'status' => VisitStatus::CONSULTING->value,
            'current_department_id' => $department->id,
        ]);
        $service = ServiceCatalog::query()->create([
            'name' => $departmentName.' Consultation',
            'code' => 'ADM'.random_int(10000, 99999),
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
            'doctor_id' => $this->admin->id,
            'status' => VisitConsultationRoute::STATUS_ACTIVE,
            'routed_by' => $this->admin->id,
            'started_by' => $this->admin->id,
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

        app(ConsultationSessionService::class)->getOrCreateMedicalRecordForRoute($route, $this->admin);

        return [$visit, $route->fresh(['department', 'medicalRecord'])];
    }
}
