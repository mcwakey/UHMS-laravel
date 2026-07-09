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
use App\Models\HistoryOfPresentingComplaint;
use App\Models\Patient;
use App\Models\ServiceCatalog;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitConsultationRoute;
use App\Services\Consultation\Specialty\ConsultationSpecialtyBrowserFixtureService;
use App\Services\Consultation\Specialty\ConsultationSpecialtyQuickActionRegistry;
use App\Services\Consultation\Specialty\ConsultationSpecialtyReadinessService;
use App\Services\Consultation\Specialty\ConsultationSpecialtySectionAliasService;
use App\Services\Consultation\Specialty\ConsultationSpecialtySectionComponentRegistry;
use App\Services\Consultation\Specialty\ConsultationSpecialtySummaryBuilder;
use App\Services\Consultation\Specialty\ConsultationSpecialtyLayoutService;
use App\Services\ConsultationSessionService;
use Database\Seeders\ConsultationSpecialtySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Lang;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ConsultationComplaintCanonicalisationTest extends TestCase
{
    use RefreshDatabase;

    /** Legacy complaint-like key => [profile, display label]. */
    private const COMPLAINT_PROFILES = [
        'presenting_problem' => ['physiotherapy', 'Presenting Problem'],
        'eye_complaint' => ['ophthalmology', 'Eye Complaint'],
        'dental_complaint' => ['dental', 'Dental Complaint'],
        'gyne_complaint' => ['gynecology', 'Gyne Complaint'],
        'ent_complaint' => ['ent', 'ENT Complaint'],
        'pediatric_complaint' => ['pediatrics', 'Pediatric Complaint'],
        'emergency_complaint' => ['emergency', 'Emergency Complaint'],
        'ortho_complaint' => ['orthopedics', 'Ortho Complaint'],
        'surgical_complaint' => ['surgery', 'Surgical Complaint'],
    ];

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

    public function test_complaint_like_keys_alias_to_canonical_complaints(): void
    {
        $aliases = app(ConsultationSpecialtySectionAliasService::class);
        $registry = app(ConsultationSpecialtySectionComponentRegistry::class);

        foreach (self::COMPLAINT_PROFILES as $legacyKey => [$profileCode]) {
            $this->assertTrue($aliases->isDuplicateSection($profileCode, $legacyKey), "{$profileCode}.{$legacyKey} should alias to complaints");
            $this->assertSame('complaints', $aliases->canonicalSectionKey($profileCode, $legacyKey));
            $this->assertSame('complaints', $registry->canonicalSectionKey($legacyKey));
        }
    }

    public function test_normalized_layouts_use_canonical_complaints_and_hide_legacy_sections(): void
    {
        // Re-run the seeder to prove reseeding does not resurrect the legacy
        // complaint sections.
        $this->seed(ConsultationSpecialtySeeder::class);

        foreach (self::COMPLAINT_PROFILES as $legacyKey => [$profileCode]) {
            $profile = $this->profile($profileCode);
            $visibleKeys = $profile->sections()->visible()->pluck('section_key');

            $this->assertTrue($visibleKeys->contains('complaints'), "{$profileCode} should expose canonical complaints");
            $this->assertFalse($visibleKeys->contains($legacyKey), "{$profileCode} should hide legacy {$legacyKey}");

            $legacyRow = $profile->sections()->where('section_key', $legacyKey)->first();
            $this->assertNotNull($legacyRow, "{$profileCode}.{$legacyKey} row must be preserved");
            $this->assertFalse((bool) $legacyRow->is_visible);
        }

        // Obstetrics never had a legacy complaint section; it now gets a
        // fresh canonical complaints section with no legacy row to hide.
        $obstetrics = $this->profile('obstetrics');
        $this->assertTrue($obstetrics->sections()->visible()->pluck('section_key')->contains('complaints'));
    }

    public function test_complaints_pane_uses_specialty_specific_label(): void
    {
        $layoutService = app(ConsultationSpecialtyLayoutService::class);

        $expectedLabels = [
            'general_medicine' => 'Complaints',
            'physiotherapy' => 'Presenting Problem',
            'ophthalmology' => 'Eye Complaint',
            'dental' => 'Dental Complaint',
            'obstetrics' => 'Current Complaint',
            'gynecology' => 'Gyne Complaint',
            'ent' => 'ENT Complaint',
            'pediatrics' => 'Pediatric Complaint',
            'emergency' => 'Emergency Complaint',
            'orthopedics' => 'Ortho Complaint',
            'surgery' => 'Surgical Complaint',
        ];

        foreach ($expectedLabels as $profileCode => $expectedLabel) {
            $profile = $this->profile($profileCode);
            $layout = $layoutService->buildLayout($this->rawContext($profile));
            $complaintsSection = collect($layout['sections'])->firstWhere('key', 'complaints');

            $this->assertNotNull($complaintsSection, "{$profileCode} layout is missing the complaints section");
            $this->assertSame($expectedLabel, $complaintsSection['translated_label'], "{$profileCode} complaints label mismatch");
            $this->assertSame('consultations.partials.specialty.core-section', $complaintsSection['component']);
            $this->assertSame('complaints-section', $complaintsSection['tab_target']);
        }
    }

    public function test_core_complaint_saves_and_reloads_under_specialty_label(): void
    {
        [$visit, $route] = $this->consultationRouteFixture('Ophthalmology', 'EYE', 'ophthalmology');

        $this->actingAs($this->doctor)
            ->withHeaders(['Idempotency-Key' => 'complaint-canon-1'])
            ->postJson(route('admin.consultations.complaints.store', $visit), [
                'consultation_route_id' => $route->id,
                'description' => 'E2E blurred vision',
                'duration' => '3',
                'duration_unit' => 'days',
                'severity' => 'moderate',
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('complaints', [
            'visit_id' => $visit->id,
            'description' => 'E2E blurred vision',
        ]);

        $response = $this->withoutMiddleware(ConvertBladeViewsToInertia::class)
            ->actingAs($this->doctor)
            ->get(route('admin.consultations.routes.show', [$visit, $route]));

        $response->assertOk();
        $response->assertSee('E2E blurred vision');
        $response->assertSee('Eye Complaint');
        $response->assertSee('id="complaints-section"', false);

        // The legacy schema-driven eye_complaint panel must not render.
        $response->assertDontSee('id="specialty-eye_complaint-section"', false);
    }

    public function test_hopc_can_link_to_specialty_labeled_core_complaint(): void
    {
        [$visit, $route] = $this->consultationRouteFixture('Dental', 'DEN', 'dental');

        $complaintResponse = $this->actingAs($this->doctor)
            ->withHeaders(['Idempotency-Key' => 'complaint-canon-2'])
            ->postJson(route('admin.consultations.complaints.store', $visit), [
                'consultation_route_id' => $route->id,
                'description' => 'E2E dental pain',
                'duration' => '2',
                'duration_unit' => 'days',
                'severity' => 'severe',
            ])
            ->assertOk();

        $complaintId = $complaintResponse->json('complaint.id');
        $this->assertNotNull($complaintId, 'complaint id missing from store response');

        $this->actingAs($this->doctor)
            ->withHeaders(['Idempotency-Key' => 'hopc-canon-1'])
            ->postJson(route('admin.consultations.hopc.store', $visit), [
                'consultation_route_id' => $route->id,
                'complaint_id' => $complaintId,
                'onset' => 'Gradual',
                'duration' => '2 days',
                'severity' => 'Severe',
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('history_of_presenting_complaints', [
            'complaint_id' => $complaintId,
            'onset' => 'Gradual',
        ]);

        $hopc = HistoryOfPresentingComplaint::query()->where('complaint_id', $complaintId)->firstOrFail();
        $this->assertSame('E2E dental pain', $hopc->complaint->description);
    }

    public function test_readiness_uses_canonical_complaints_with_profile_specific_label(): void
    {
        [, $route, $context] = $this->consultationRouteFixture('Physiotherapy', 'PHY', 'physiotherapy', DepartmentType::TREATMENT);

        $before = app(ConsultationSpecialtyReadinessService::class)->evaluate($route, $context);
        $item = collect($before->items)->firstWhere('key', 'presenting_problem_recorded');
        $this->assertSame('missing', $item['status']);
        $this->assertSame('complaints', $item['section_key']);
        $this->assertSame('#complaints-section', $item['anchor'], 'readiness anchor must point to the canonical complaints tab');

        $route->medicalRecord->complaints()->create([
            'consultation_route_id' => $route->id,
            'visit_id' => $route->visit_id,
            'patient_id' => $route->patient_id,
            'department_id' => $route->department_id,
            'doctor_id' => $this->doctor->id,
            'created_by' => $this->doctor->id,
            'description' => 'E2E low back pain',
        ]);

        $after = app(ConsultationSpecialtyReadinessService::class)->evaluate($route->fresh(['department', 'medicalRecord']), $context);
        $item = collect($after->items)->firstWhere('key', 'presenting_problem_recorded');
        $this->assertSame('complete', $item['status'], 'a real core complaint must satisfy the presenting_problem rule');
    }

    public function test_legacy_complaint_entries_still_satisfy_readiness_fallback(): void
    {
        [, $route, $context] = $this->consultationRouteFixture('Gynecology', 'GYN', 'gynecology');
        $profile = $this->profile('gynecology');

        $before = app(ConsultationSpecialtyReadinessService::class)->evaluate($route, $context);
        $this->assertSame('missing', collect($before->items)->firstWhere('key', 'gyne_complaint_recorded')['status']);

        ConsultationSpecialtyEntry::query()->create([
            'consultation_id' => $route->id,
            'consultation_specialty_profile_id' => $profile->id,
            'section_key' => 'gyne_complaint',
            'entry' => ['complaint_text' => 'E2E pelvic pain'],
            'created_by' => $this->doctor->id,
            'updated_by' => $this->doctor->id,
        ]);

        $after = app(ConsultationSpecialtyReadinessService::class)->evaluate($route->fresh(['department', 'medicalRecord']), $context);
        $item = collect($after->items)->firstWhere('key', 'gyne_complaint_recorded');
        $this->assertSame('complete', $item['status'], 'a legacy gyne_complaint entry must still satisfy readiness');
    }

    public function test_summary_builder_merges_core_and_legacy_complaint_data_under_specialty_label(): void
    {
        [, $route, $context] = $this->consultationRouteFixture('ENT', 'ENT', 'ent');
        $profile = $this->profile('ent');

        ConsultationSpecialtyEntry::query()->create([
            'consultation_id' => $route->id,
            'consultation_specialty_profile_id' => $profile->id,
            'section_key' => 'ent_complaint',
            'entry' => ['complaint_text' => 'E2E legacy ear pain note', 'side' => 'Right'],
            'created_by' => $this->doctor->id,
            'updated_by' => $this->doctor->id,
        ]);
        $route->medicalRecord->complaints()->create([
            'consultation_route_id' => $route->id,
            'visit_id' => $route->visit_id,
            'patient_id' => $route->patient_id,
            'department_id' => $route->department_id,
            'doctor_id' => $this->doctor->id,
            'created_by' => $this->doctor->id,
            'description' => 'E2E core ear pain',
        ]);

        $summary = app(ConsultationSpecialtySummaryBuilder::class)->build($route->fresh(['department', 'medicalRecord']), $context);
        $keys = collect($summary->sections)->pluck('key');

        $this->assertFalse($summary->isFallback);
        $this->assertSame(1, $keys->filter(fn ($key) => $key === 'ent_complaint')->count(), 'exactly one complaint heading should render');
        $this->assertFalse($keys->contains('complaints'), 'no separate generic Complaints heading should duplicate the specialty one');
        $this->assertStringContainsString('E2E core ear pain', $summary->plainText());
        $this->assertStringContainsString('E2E legacy ear pain note', $summary->plainText());
    }

    public function test_quick_actions_target_canonical_complaints_section(): void
    {
        $registry = app(ConsultationSpecialtyQuickActionRegistry::class);

        $actionKeyByProfile = [
            'physiotherapy' => 'presenting_problem',
            'ophthalmology' => 'eye_complaint',
            'dental' => 'dental_complaint',
            'gynecology' => 'gyne_complaint',
            'ent' => 'ent_complaint',
            'pediatrics' => 'pediatric_complaint',
            'orthopedics' => 'ortho_complaint',
            'surgery' => 'surgical_complaint',
        ];

        foreach ($actionKeyByProfile as $profileCode => $actionKey) {
            $profile = $this->profile($profileCode);
            $action = collect($registry->actionsForProfile($profile))->firstWhere('key', $actionKey);

            $this->assertNotNull($action, "{$profileCode} is missing the {$actionKey} quick action");
            $this->assertSame('#complaints-section', $action['target'], "{$profileCode} quick action must target the canonical complaints pane");
            $this->assertTrue(
                empty($action['requires_section']) || $action['requires_section'] === 'complaints',
                "{$profileCode} quick action must not require a hidden legacy section",
            );
        }
    }

    public function test_admin_shows_deprecated_and_maps_to_badges_for_legacy_complaint_sections(): void
    {
        $admin = User::factory()->create();
        foreach (['consultation-specialties.view', 'consultation-specialties.configure'] as $permission) {
            $admin->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }

        $profile = $this->profile('ent');

        $this->withoutMiddleware(ConvertBladeViewsToInertia::class)
            ->actingAs($admin)
            ->get(route('admin.consultation-specialties.sections.index', $profile))
            ->assertOk()
            ->assertSee('ent_complaint')
            ->assertSee(__('consultation_specialties.admin.deprecated_duplicate'))
            // Phase 16.7: the "maps to" badge is itself profile-aware, so it
            // names ENT's own canonical label instead of the generic one.
            ->assertSee(__('consultation_specialties.admin.maps_to', ['section' => 'ENT Complaint']))
            ->assertSee(__('consultation_specialties.admin.displayed_as', ['label' => 'ENT Complaint']));

        $this->withoutMiddleware(ConvertBladeViewsToInertia::class)
            ->actingAs($admin)
            ->get(route('admin.consultation-specialties.show', $profile))
            ->assertOk()
            ->assertSee(__('consultation_specialties.admin.displayed_as', ['label' => 'ENT Complaint']));
    }

    public function test_browser_fixture_metadata_uses_canonical_complaints_section(): void
    {
        $legacyKeys = array_keys(self::COMPLAINT_PROFILES);
        $complaintProfileCodes = array_column(self::COMPLAINT_PROFILES, 0);

        foreach (app(ConsultationSpecialtyBrowserFixtureService::class)->profileDefinitions() as $definition) {
            $this->assertSame(
                [],
                array_intersect($definition['expected_sections'], $legacyKeys),
                "{$definition['code']} fixture must not expect a legacy complaint section",
            );

            if (in_array($definition['code'], $complaintProfileCodes, true)) {
                $this->assertContains('complaints', $definition['expected_sections'], "{$definition['code']} fixture should expect the canonical complaints section");
            }
        }
    }

    public function test_localisation_keys_exist_for_complaint_canonicalisation(): void
    {
        foreach (['en', 'fr'] as $locale) {
            foreach ([
                'consultation_specialties.sections.current_complaint',
                'consultation_specialties.admin.displayed_as',
            ] as $key) {
                $this->assertTrue(Lang::has($key, $locale), "{$key} missing for {$locale}");
            }
        }
    }

    private function profile(string $code): ConsultationSpecialtyProfile
    {
        return ConsultationSpecialtyProfile::query()->byCode($code)->firstOrFail();
    }

    private function rawContext(ConsultationSpecialtyProfile $profile): array
    {
        return [
            'profile' => ['id' => $profile->id, 'code' => $profile->code, 'name' => $profile->name],
            'sections' => $profile->sections()->visible()->ordered()->get()->map(fn ($section) => [
                'key' => $section->section_key,
                'label' => $section->label,
                'display_order' => $section->display_order,
                'is_required' => $section->is_required,
                'is_visible' => $section->is_visible,
            ])->all(),
        ];
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
            'code' => 'CCT'.random_int(10000, 99999),
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
}
