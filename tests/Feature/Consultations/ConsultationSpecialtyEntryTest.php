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
use App\Services\Consultation\Specialty\ConsultationSpecialtyEntryService;
use App\Services\ConsultationSessionService;
use Database\Seeders\ConsultationSpecialtySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ConsultationSpecialtyEntryTest extends TestCase
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

    public function test_entry_service_upserts_one_row_per_consultation_profile_and_section(): void
    {
        [$visit, $route] = $this->consultationRouteFixture('Physiotherapy', 'PHY', 'physiotherapy');
        $profile = $this->profile('physiotherapy');
        $service = app(ConsultationSpecialtyEntryService::class);

        $service->upsertEntry($route, $profile, 'pain_assessment', ['pain_score' => 6], $this->doctor);
        $updated = $service->upsertEntry($route, $profile, 'pain_assessment', ['pain_score' => 3, 'pain_location' => 'Knee'], $this->doctor);

        $this->assertSame(1, ConsultationSpecialtyEntry::query()
            ->where('consultation_id', $route->id)
            ->where('consultation_specialty_profile_id', $profile->id)
            ->where('section_key', 'pain_assessment')
            ->count());
        $this->assertSame(3, $updated->entry['pain_score']);
        $this->assertSame($this->doctor->id, $updated->updated_by);
        $this->assertTrue($visit->exists);
    }

    public function test_physiotherapy_pain_assessment_can_be_saved(): void
    {
        [$visit, $route] = $this->consultationRouteFixture('Physiotherapy', 'PHY', 'physiotherapy');
        $profile = $this->profile('physiotherapy');

        $this->actingAs($this->doctor)
            ->postJson(route('admin.consultations.specialty-entries.store', [$visit, 'pain_assessment']), [
                'consultation_route_id' => $route->id,
                'specialty_profile_id' => $profile->id,
                'pain_score' => 8,
                'pain_location' => 'Lower back',
                'aggravating_factors' => 'Bending',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('entry.section_key', 'pain_assessment');

        $entry = ConsultationSpecialtyEntry::query()->where('section_key', 'pain_assessment')->firstOrFail();

        $this->assertSame($route->id, $entry->consultation_id);
        $this->assertSame(8, $entry->entry['pain_score']);
        $this->assertSame('Lower back', $entry->entry['pain_location']);
    }

    public function test_ophthalmology_visual_acuity_can_be_saved(): void
    {
        [$visit, $route] = $this->consultationRouteFixture('Ophthalmology', 'EYE', 'ophthalmology');
        $profile = $this->profile('ophthalmology');

        $this->actingAs($this->doctor)
            ->postJson(route('admin.consultations.specialty-entries.store', [$visit, 'visual_acuity']), [
                'consultation_route_id' => $route->id,
                'specialty_profile_id' => $profile->id,
                'right_eye_unaided' => '6/9',
                'left_eye_unaided' => '6/12',
                'notes' => 'Uses old glasses',
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $entry = ConsultationSpecialtyEntry::query()->where('section_key', 'visual_acuity')->firstOrFail();

        $this->assertSame('6/9', $entry->entry['right_eye_unaided']);
        $this->assertSame('6/12', $entry->entry['left_eye_unaided']);
    }

    public function test_ophthalmology_refraction_validates_axis_range(): void
    {
        [$visit, $route] = $this->consultationRouteFixture('Ophthalmology', 'EYE', 'ophthalmology');
        $profile = $this->profile('ophthalmology');

        $this->actingAs($this->doctor)
            ->postJson(route('admin.consultations.specialty-entries.store', [$visit, 'refraction']), [
                'consultation_route_id' => $route->id,
                'specialty_profile_id' => $profile->id,
                'right_axis' => 181,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['right_axis']);
    }

    public function test_dental_chart_and_consent_can_be_saved(): void
    {
        [$visit, $route] = $this->consultationRouteFixture('Dental', 'DEN', 'dental');
        $profile = $this->profile('dental');

        $this->actingAs($this->doctor)
            ->postJson(route('admin.consultations.specialty-entries.store', [$visit, 'tooth_chart']), [
                'consultation_route_id' => $route->id,
                'specialty_profile_id' => $profile->id,
                'tooth_number' => '36',
                'tooth_surface' => 'Occlusal',
                'condition' => 'Caries',
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->actingAs($this->doctor)
            ->postJson(route('admin.consultations.specialty-entries.store', [$visit, 'consent']), [
                'consultation_route_id' => $route->id,
                'specialty_profile_id' => $profile->id,
                'consent_required' => '1',
                'consent_obtained' => '0',
                'consent_type' => 'Extraction',
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $chart = ConsultationSpecialtyEntry::query()->where('section_key', 'tooth_chart')->firstOrFail();
        $consent = ConsultationSpecialtyEntry::query()->where('section_key', 'consent')->firstOrFail();

        $this->assertSame('36', $chart->entry['tooth_number']);
        $this->assertTrue($consent->entry['consent_required']);
        $this->assertFalse($consent->entry['consent_obtained']);
    }

    public function test_unknown_section_is_rejected(): void
    {
        [$visit, $route] = $this->consultationRouteFixture('Physiotherapy', 'PHY', 'physiotherapy');

        $this->actingAs($this->doctor)
            ->postJson(route('admin.consultations.specialty-entries.store', [$visit, 'unknown_section']), [
                'consultation_route_id' => $route->id,
                'specialty_profile_id' => $this->profile('physiotherapy')->id,
                'notes' => 'Nope',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['section_key']);
    }

    public function test_mismatched_profile_is_rejected(): void
    {
        [$visit, $route] = $this->consultationRouteFixture('Physiotherapy', 'PHY', 'physiotherapy');

        $this->actingAs($this->doctor)
            ->postJson(route('admin.consultations.specialty-entries.store', [$visit, 'pain_assessment']), [
                'consultation_route_id' => $route->id,
                'specialty_profile_id' => $this->profile('dental')->id,
                'pain_score' => 4,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['specialty_profile_id']);
    }

    public function test_blank_save_does_not_create_empty_entry(): void
    {
        [$visit, $route] = $this->consultationRouteFixture('Physiotherapy', 'PHY', 'physiotherapy');

        $this->actingAs($this->doctor)
            ->postJson(route('admin.consultations.specialty-entries.store', [$visit, 'pain_assessment']), [
                'consultation_route_id' => $route->id,
                'specialty_profile_id' => $this->profile('physiotherapy')->id,
            ])
            ->assertOk()
            ->assertJsonPath('entry', null);

        $this->assertDatabaseMissing('consultation_specialty_entries', [
            'consultation_id' => $route->id,
            'section_key' => 'pain_assessment',
        ]);
    }

    public function test_workspace_loads_saved_specialist_entry_values(): void
    {
        [$visit, $route] = $this->consultationRouteFixture('Physiotherapy', 'PHY', 'physiotherapy');
        $profile = $this->profile('physiotherapy');

        ConsultationSpecialtyEntry::query()->create([
            'consultation_id' => $route->id,
            'consultation_specialty_profile_id' => $profile->id,
            'section_key' => 'pain_assessment',
            'entry' => ['pain_score' => 7, 'pain_location' => 'Right shoulder'],
            'created_by' => $this->doctor->id,
            'updated_by' => $this->doctor->id,
        ]);

        $response = $this->withoutMiddleware(ConvertBladeViewsToInertia::class)
            ->actingAs($this->doctor)
            ->get(route('admin.consultations.routes.show', [$visit, $route]));

        $response->assertOk();
        $response->assertSee('Right shoulder');
        $response->assertSee('value="7"', false);
        $response->assertSee(__('consultation_specialties.messages.entry_loaded'));
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
            'code' => 'SPE'.random_int(10000, 99999),
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

        return [$visit, $route->fresh(['department', 'medicalRecord']), $department];
    }
}
