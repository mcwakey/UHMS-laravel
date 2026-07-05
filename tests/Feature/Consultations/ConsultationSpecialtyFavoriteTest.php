<?php

namespace Tests\Feature\Consultations;

use App\Enums\DepartmentType;
use App\Enums\ServiceType;
use App\Enums\VisitStatus;
use App\Http\Middleware\ConvertBladeViewsToInertia;
use App\Models\ConsultationSpecialtyFavorite;
use App\Models\ConsultationSpecialtyProfile;
use App\Models\ConsultationSpecialtyProfileMapping;
use App\Models\Department;
use App\Models\Drug;
use App\Models\Patient;
use App\Models\ServiceCatalog;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitConsultationRoute;
use App\Services\Consultation\Specialty\ConsultationSpecialtyFavoriteService;
use App\Services\ConsultationSessionService;
use Database\Seeders\ConsultationSpecialtySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ConsultationSpecialtyFavoriteTest extends TestCase
{
    use RefreshDatabase;

    private User $doctor;

    private ConsultationSpecialtyFavoriteService $favorites;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(ConsultationSpecialtySeeder::class);

        $this->favorites = app(ConsultationSpecialtyFavoriteService::class);
        $this->doctor = User::factory()->create();

        $role = Role::findOrCreate('Doctor', 'web');
        foreach (['consultations.view', 'consultations.create'] as $permission) {
            $role->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
        $this->doctor->assignRole($role);
    }

    public function test_seeder_creates_favorites_for_specialist_profiles(): void
    {
        foreach (['physiotherapy', 'ophthalmology', 'dental'] as $code) {
            $profile = $this->profile($code);

            $this->assertTrue($profile->favorites()->active()->exists(), "{$code} should have favorites");
        }
    }

    public function test_favorites_are_grouped_by_type(): void
    {
        $defaults = $this->favorites->getWorkspaceDefaults($this->profile('ophthalmology'));

        foreach (['diagnosis', 'investigation', 'procedure', 'drug', 'frequency', 'follow_up_instruction'] as $type) {
            $this->assertArrayHasKey($type, $defaults);
            $this->assertNotEmpty($defaults[$type]);
        }
    }

    public function test_favorites_respect_sort_order(): void
    {
        $profile = $this->profile('physiotherapy');

        $ordered = $this->favorites->getFavoritesForProfile($profile, 'diagnosis');

        $this->assertSame('Low back pain', $ordered->first()->label);
        $this->assertSame(
            $ordered->pluck('sort_order')->sort()->values()->all(),
            $ordered->pluck('sort_order')->values()->all(),
        );
    }

    public function test_inactive_favorites_are_ignored(): void
    {
        $profile = $this->profile('dental');

        ConsultationSpecialtyFavorite::query()->create([
            'consultation_specialty_profile_id' => $profile->id,
            'favorite_type' => 'diagnosis',
            'code' => 'diagnosis:inactive_test',
            'label' => 'Inactive dental test',
            'is_active' => false,
            'sort_order' => 1,
        ]);

        $labels = $this->favorites->getFavoritesForProfile($profile, 'diagnosis')->pluck('label');

        $this->assertFalse($labels->contains('Inactive dental test'));
    }

    public function test_missing_linked_model_does_not_break_favorites(): void
    {
        $profile = $this->profile('ophthalmology');
        ConsultationSpecialtyFavorite::query()->create([
            'consultation_specialty_profile_id' => $profile->id,
            'favorite_type' => 'drug',
            'favoritable_type' => Drug::class,
            'favoritable_id' => 999999,
            'code' => 'missing_drug',
            'label' => 'Missing linked drug',
            'is_active' => true,
        ]);

        $labels = $this->favorites->getFavoritesForProfile($profile, 'drug')->pluck('label');

        $this->assertFalse($labels->contains('Missing linked drug'));
        $this->assertTrue($labels->contains('Lubricating eye drops'));
    }

    public function test_favorites_merge_before_global_options_and_dedupe(): void
    {
        $profile = $this->profile('ophthalmology');
        $favorites = $this->favorites->getFavoritesForProfile($profile, 'frequency');

        $merged = $this->favorites->mergeFavoritesWithGlobalOptions($favorites, [
            ['value' => 'OD', 'label' => 'OD - once daily'],
            ['value' => 'Q6H', 'label' => 'Every 6 hours'],
        ], 'frequency');

        $this->assertSame('OD', $merged[0]['value']);
        $this->assertTrue($merged[0]['is_favorite']);
        $this->assertSame(1, collect($merged)->where('value', 'OD')->count());
        $this->assertTrue(collect($merged)->contains(fn ($item) => $item['value'] === 'Q6H'));
    }

    public function test_workspace_payload_includes_specialty_favorites(): void
    {
        [$visit, $route] = $this->consultationRouteFixture('Ophthalmology', 'EYE', 'ophthalmology');

        $response = $this->withoutMiddleware(ConvertBladeViewsToInertia::class)
            ->actingAs($this->doctor)
            ->get(route('admin.consultations.routes.show', [$visit, $route]));

        $response->assertOk();
        $response->assertViewHas('specialtyFavorites', function (array $favorites): bool {
            return collect($favorites['diagnosis'] ?? [])->contains(fn ($item) => $item['label'] === 'Glaucoma')
                && collect($favorites['investigation'] ?? [])->contains(fn ($item) => str_contains($item['label'], 'Intraocular'));
        });
    }

    public function test_general_medicine_stays_light(): void
    {
        $defaults = $this->favorites->getWorkspaceDefaults($this->profile('general_medicine'));

        $this->assertEmpty($defaults['diagnosis']);
        $this->assertEmpty($defaults['procedure']);
        $this->assertTrue(collect($defaults['frequency'])->contains(fn ($item) => $item['label'] === 'Review in 1 week'));
        $this->assertFalse(collect($defaults['frequency'])->contains(fn ($item) => $item['label'] === 'Tooth extraction'));
    }

    public function test_specialist_seeded_favorites_include_expected_items(): void
    {
        $eye = $this->favorites->getWorkspaceDefaults($this->profile('ophthalmology'));
        $dental = $this->favorites->getWorkspaceDefaults($this->profile('dental'));
        $physio = $this->favorites->getWorkspaceDefaults($this->profile('physiotherapy'));

        $this->assertTrue(collect($eye['investigation'])->contains(fn ($item) => $item['label'] === 'Fundus examination'));
        $this->assertTrue(collect($dental['procedure'])->contains(fn ($item) => $item['label'] === 'Tooth extraction'));
        $this->assertTrue(collect($physio['task'])->contains(fn ($item) => $item['label'] === 'Review pain score'));
    }

    public function test_frequency_defaults_include_recommended_standard_frequencies_without_duplicates(): void
    {
        $labels = collect($this->favorites->standardFrequencyOptions())->pluck('label');

        foreach (['Once weekly', 'Twice weekly', 'Three times weekly', 'Review in 3 days', 'Review in 1 week', 'Review in 2 weeks', 'As needed'] as $label) {
            $this->assertTrue($labels->contains($label), "{$label} should be available");
        }

        $this->assertSame($labels->count(), $labels->unique()->count());
    }

    public function test_specialty_favorites_render_in_workspace_smoke(): void
    {
        [$visit, $route] = $this->consultationRouteFixture('Dental', 'DEN', 'dental');

        $response = $this->withoutMiddleware(ConvertBladeViewsToInertia::class)
            ->actingAs($this->doctor)
            ->get(route('admin.consultations.routes.show', [$visit, $route]));

        $response->assertOk();
        $response->assertSee('Dental caries');
        $response->assertSee('Tooth extraction');
        $response->assertSee('Do not rinse mouth vigorously');
    }

    private function profile(string $code): ConsultationSpecialtyProfile
    {
        return ConsultationSpecialtyProfile::query()->byCode($code)->firstOrFail();
    }

    private function consultationRouteFixture(string $departmentName, string $departmentCode, string $profileCode): array
    {
        $department = Department::factory()->create([
            'name' => $departmentName,
            'code' => $departmentCode.random_int(100, 999),
            'type' => DepartmentType::CONSULTATION->value,
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
            'code' => 'FAV'.random_int(10000, 99999),
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
