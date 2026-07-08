<?php

namespace Tests\Feature\Consultations;

use App\Enums\DepartmentType;
use App\Enums\ServiceType;
use App\Enums\VisitStatus;
use App\Http\Middleware\ConvertBladeViewsToInertia;
use App\Models\ConsultationSpecialtyEntry;
use App\Models\ConsultationSpecialtyOrderSetItem;
use App\Models\ConsultationSpecialtyProfile;
use App\Models\ConsultationSpecialtyProfileMapping;
use App\Models\ConsultationSpecialtyServiceMapping;
use App\Models\Department;
use App\Models\Patient;
use App\Models\ServiceCatalog;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitConsultationRoute;
use App\Services\Consultation\Specialty\ConsultationSpecialtyBrowserFixtureService;
use App\Services\Consultation\Specialty\ConsultationSpecialtyLayoutService;
use App\Services\Consultation\Specialty\ConsultationSpecialtyQuickActionRegistry;
use App\Services\Consultation\Specialty\ConsultationSpecialtyReadinessService;
use App\Services\Consultation\Specialty\ConsultationSpecialtySectionAliasService;
use App\Services\Consultation\Specialty\ConsultationSpecialtySectionComponentRegistry;
use App\Services\Consultation\Specialty\ConsultationSpecialtySummaryBuilder;
use App\Services\ConsultationSessionService;
use Database\Seeders\ConsultationSpecialtySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Lang;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ConsultationWorkspaceSectionDeduplicationTest extends TestCase
{
    use RefreshDatabase;

    private const DUPLICATE_SECTIONS = [
        'obstetrics' => ['lab_screening' => 'investigations', 'ultrasound_findings' => 'investigations'],
        'emergency' => ['urgent_investigations' => 'investigations', 'urgent_procedures' => 'procedures', 'medications_given' => 'prescription'],
        'orthopedics' => ['imaging' => 'investigations', 'procedure_plan' => 'procedures'],
        'surgery' => ['procedure_plan' => 'procedures'],
        'dental' => ['dental_diagnosis' => 'diagnosis', 'dental_xray' => 'investigations', 'dental_procedures' => 'procedures'],
    ];

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

    public function test_alias_service_classifies_known_duplicates(): void
    {
        $aliases = app(ConsultationSpecialtySectionAliasService::class);

        foreach (self::DUPLICATE_SECTIONS as $profileCode => $map) {
            foreach ($map as $legacy => $canonical) {
                $this->assertTrue($aliases->isDuplicateSection($profileCode, $legacy), "{$profileCode}.{$legacy} should be a duplicate");
                $this->assertSame($canonical, $aliases->canonicalSectionKey($profileCode, $legacy));
                $this->assertSame($canonical, $aliases->displaySectionKey($profileCode, $legacy));
                $this->assertContains($legacy, $aliases->legacySectionKeysFor($profileCode, $canonical));
            }
        }

        $this->assertFalse($aliases->isDuplicateSection('obstetrics', 'antenatal_vitals'));
        $this->assertSame('antenatal_vitals', $aliases->canonicalSectionKey('obstetrics', 'antenatal_vitals'));
        // Phase 16.6 adds a complaint alias for gynecology (gyne_complaint ->
        // complaints); general_medicine has no aliases of its own.
        $this->assertSame([], $aliases->aliasMapFor('general_medicine'));

        $registry = app(ConsultationSpecialtySectionComponentRegistry::class);
        foreach (array_merge(...array_map('array_keys', array_values(self::DUPLICATE_SECTIONS))) as $legacy) {
            $this->assertNotSame($legacy, $registry->canonicalSectionKey($legacy), "registry should alias {$legacy}");
        }
    }

    public function test_normalized_layouts_hide_duplicates_and_reuse_shared_sections(): void
    {
        $expectedCanonical = [
            'obstetrics' => ['investigations', 'diagnosis', 'prescription'],
            'emergency' => ['diagnosis', 'investigations', 'procedures', 'prescription'],
            'orthopedics' => ['investigations', 'diagnosis', 'procedures', 'prescription'],
            'surgery' => ['diagnosis', 'investigations', 'procedures'],
            'dental' => ['diagnosis', 'investigations', 'procedures', 'prescription'],
        ];

        // Re-run the seeder to prove reseeding does not resurrect duplicates.
        $this->seed(ConsultationSpecialtySeeder::class);

        foreach ($expectedCanonical as $profileCode => $canonicalKeys) {
            $profile = $this->profile($profileCode);
            $visibleKeys = $profile->sections()->visible()->ordered()->pluck('section_key');

            foreach ($canonicalKeys as $key) {
                $this->assertTrue($visibleKeys->contains($key), "{$profileCode} should expose {$key}");
            }

            foreach (array_keys(self::DUPLICATE_SECTIONS[$profileCode]) as $legacy) {
                $this->assertFalse($visibleKeys->contains($legacy), "{$profileCode} should hide {$legacy}");

                $legacyRow = $profile->sections()->where('section_key', $legacy)->first();
                $this->assertNotNull($legacyRow, "{$profileCode}.{$legacy} row must be preserved");
                $this->assertFalse((bool) $legacyRow->is_visible);
            }

            $layoutKeys = collect(app(ConsultationSpecialtyLayoutService::class)->buildLayout([
                'profile' => ['id' => $profile->id, 'code' => $profile->code, 'name' => $profile->name],
                'sections' => $profile->sections()->ordered()->get()->map(fn ($section) => [
                    'key' => $section->section_key,
                    'label' => $section->label,
                    'display_order' => $section->display_order,
                    'is_required' => $section->is_required,
                    'is_visible' => $section->is_visible,
                ])->all(),
            ])['sections'])->pluck('key');

            foreach (array_keys(self::DUPLICATE_SECTIONS[$profileCode]) as $legacy) {
                $this->assertFalse($layoutKeys->contains($legacy), "{$profileCode} layout should not render {$legacy}");
            }
        }
    }

    public function test_quick_actions_do_not_target_hidden_sections(): void
    {
        $registry = app(ConsultationSpecialtyQuickActionRegistry::class);
        $coreKeys = app(ConsultationSpecialtySectionComponentRegistry::class)->coreSectionKeys();

        ConsultationSpecialtyProfile::query()->get()->each(function (ConsultationSpecialtyProfile $profile) use ($registry, $coreKeys): void {
            $visibleKeys = $profile->sections()->visible()->pluck('section_key')->all();
            $hiddenKeys = $profile->sections()->where('is_visible', false)->pluck('section_key')->all();

            foreach ($registry->actionsForProfile($profile) as $action) {
                if (! empty($action['requires_section'])) {
                    $this->assertContains(
                        $action['requires_section'],
                        array_merge($visibleKeys, $coreKeys),
                        "{$profile->code} quick action {$action['key']} requires a hidden/unknown section",
                    );
                    $this->assertNotContains($action['requires_section'], $hiddenKeys, "{$profile->code} quick action {$action['key']} requires a hidden section");
                }

                foreach ($hiddenKeys as $hidden) {
                    $this->assertStringNotContainsString('#'.$hidden.'-section', $action['target'] ?? '', "{$profile->code} quick action {$action['key']} anchors a hidden section");
                }
            }
        });
    }

    public function test_orthopedics_readiness_accepts_legacy_procedure_plan_entry(): void
    {
        [, $route, $context] = $this->consultationRouteFixture('Orthopedics', 'ORT', 'orthopedics');
        $profile = $this->profile('orthopedics');

        $this->entry($route, $profile, 'ortho_complaint', ['complaint_text' => 'Wrist injury']);
        $this->entry($route, $profile, 'joint_limb_examination', ['tenderness' => 'Distal radius']);
        $this->entry($route, $profile, 'neurovascular_status', ['capillary_refill' => 'Brisk']);
        $this->entry($route, $profile, 'procedure_plan', ['procedure_planned' => 'Splint application']);

        $result = app(ConsultationSpecialtyReadinessService::class)->evaluate($route, $context);

        $this->assertTrue($result->canComplete, 'legacy procedure_plan entry must satisfy the procedures blocker');
        $item = collect($result->items)->firstWhere('key', 'procedure_plan_recorded');
        $this->assertSame('complete', $item['status']);
        $this->assertSame('procedures', $item['section_key'], 'readiness must anchor the canonical shared section');
    }

    public function test_surgery_readiness_accepts_core_treatment_plan_instead_of_legacy_entry(): void
    {
        [, $route, $context] = $this->consultationRouteFixture('Surgery', 'SUR', 'surgery');
        $profile = $this->profile('surgery');

        $this->entry($route, $profile, 'surgical_complaint', ['complaint_text' => 'Wound review']);
        $this->entry($route, $profile, 'local_or_abdominal_exam', ['inspection' => 'Clean wound']);
        $route->medicalRecord->treatments()->create([
            'consultation_route_id' => $route->id,
            'visit_id' => $route->visit_id,
            'patient_id' => $route->patient_id,
            'department_id' => $route->department_id,
            'doctor_id' => $this->doctor->id,
            'created_by' => $this->doctor->id,
            'type' => 'procedure',
            'description' => 'Wound dressing',
        ]);

        $result = app(ConsultationSpecialtyReadinessService::class)->evaluate($route->fresh(['department', 'medicalRecord']), $context);

        $this->assertTrue(
            collect($result->items)->firstWhere('key', 'procedure_plan_recorded')['status'] === 'complete',
            'core treatments must satisfy the procedures blocker without a legacy entry',
        );
    }

    public function test_dental_xray_warning_is_cleared_by_core_investigations(): void
    {
        [, $route, $context] = $this->consultationRouteFixture('Dental', 'DEN', 'dental');
        $profile = $this->profile('dental');

        $this->entry($route, $profile, 'tooth_chart', ['tooth_number' => '36', 'condition' => 'Caries']);
        $this->entry($route, $profile, 'dental_diagnosis', ['diagnosis_text' => 'Pulpitis']);
        $this->entry($route, $profile, 'dental_procedures', ['procedure_planned' => 'Extraction']);

        $withWarning = app(ConsultationSpecialtyReadinessService::class)->evaluate($route, $context);
        $this->assertTrue(collect($withWarning->warningItems)->pluck('key')->contains('xray_missing_if_extraction_planned'));

        $route->medicalRecord->investigations()->create([
            'consultation_route_id' => $route->id,
            'visit_id' => $route->visit_id,
            'patient_id' => $route->patient_id,
            'department_id' => $route->department_id,
            'doctor_id' => $this->doctor->id,
            'created_by' => $this->doctor->id,
            'investigation_type' => 'imaging',
            'description' => 'Periapical X-ray',
        ]);

        $cleared = app(ConsultationSpecialtyReadinessService::class)->evaluate($route->fresh(['department', 'medicalRecord']), $context);
        $this->assertFalse(collect($cleared->warningItems)->pluck('key')->contains('xray_missing_if_extraction_planned'));
    }

    public function test_summary_builder_merges_duplicate_data_under_canonical_headings(): void
    {
        [, $route, $context] = $this->consultationRouteFixture('Obstetrics', 'OBS', 'obstetrics');
        $profile = $this->profile('obstetrics');

        $this->entry($route, $profile, 'lab_screening', ['hb' => '11.2', 'blood_group' => 'O+']);
        $this->entry($route, $profile, 'ultrasound_findings', ['findings' => 'Single viable intrauterine pregnancy']);
        $route->medicalRecord->investigations()->create([
            'consultation_route_id' => $route->id,
            'visit_id' => $route->visit_id,
            'patient_id' => $route->patient_id,
            'department_id' => $route->department_id,
            'doctor_id' => $this->doctor->id,
            'created_by' => $this->doctor->id,
            'investigation_type' => 'lab',
            'description' => 'Urinalysis',
        ]);

        $summary = app(ConsultationSpecialtySummaryBuilder::class)->build($route->fresh(['department', 'medicalRecord']), $context);
        $keys = collect($summary->sections)->pluck('key');

        $this->assertFalse($summary->isFallback);
        $this->assertTrue($keys->contains('investigations'));
        $this->assertFalse($keys->contains('lab_screening'), 'legacy heading must not appear alongside the canonical one');
        $this->assertFalse($keys->contains('ultrasound_findings'));
        $this->assertSame(1, $keys->filter(fn ($key) => $key === 'investigations')->count());

        $this->assertStringContainsString('11.2', $summary->plainText());
        $this->assertStringContainsString('Single viable intrauterine pregnancy', $summary->plainText());
        $this->assertStringContainsString('Urinalysis', $summary->plainText());
    }

    public function test_emergency_summary_merges_medications_given_under_prescription_heading(): void
    {
        [, $route, $context] = $this->consultationRouteFixture('Emergency', 'EMR', 'emergency', DepartmentType::EMERGENCY);
        $profile = $this->profile('emergency');

        $this->entry($route, $profile, 'medications_given', ['medications' => 'IV artesunate given', 'response' => 'Improved']);
        $this->entry($route, $profile, 'urgent_investigations', ['urgency_reason' => 'Suspected severe malaria']);

        $summary = app(ConsultationSpecialtySummaryBuilder::class)->build($route, $context);
        $keys = collect($summary->sections)->pluck('key');

        $this->assertFalse($keys->contains('medications_given'));
        $this->assertFalse($keys->contains('urgent_investigations'));
        $this->assertTrue($keys->contains('treatment_prescription'));
        $this->assertTrue($keys->contains('investigations'));
        $this->assertStringContainsString('IV artesunate given', $summary->plainText());
        $this->assertStringContainsString('Suspected severe malaria', $summary->plainText());
    }

    public function test_seeded_order_set_items_do_not_target_duplicate_sections(): void
    {
        $duplicates = array_merge(...array_map('array_keys', array_values(self::DUPLICATE_SECTIONS)));

        $offenders = ConsultationSpecialtyOrderSetItem::query()
            ->whereIn('target_section', $duplicates)
            ->pluck('code');

        $this->assertSame([], $offenders->all(), 'seeded order set items must target canonical sections');
    }

    public function test_doctor_workspace_does_not_render_billing_card(): void
    {
        [$visit, $route] = $this->consultationRouteFixture('Dental', 'DEN', 'dental');
        $service = ServiceCatalog::query()->create([
            'name' => 'Dental Billing Service',
            'code' => 'DED'.random_int(10000, 99999),
            'category' => ServiceType::CONSULTATION->value,
            'department_id' => $route->department_id,
            'price' => 100,
            'is_active' => true,
            'is_billable' => true,
        ]);
        ConsultationSpecialtyServiceMapping::query()->create([
            'consultation_specialty_profile_id' => $this->profile('dental')->id,
            'service_id' => $service->id,
            'mapping_context' => ConsultationSpecialtyServiceMapping::CONTEXT_CONSULTATION,
            'billing_trigger' => ConsultationSpecialtyServiceMapping::TRIGGER_MANUAL,
            'priority' => 0,
            'is_default' => true,
            'auto_bill' => false,
            'requires_confirmation' => true,
            'is_active' => true,
        ]);

        $response = $this->withoutMiddleware(ConvertBladeViewsToInertia::class)
            ->actingAs($this->doctor)
            ->get(route('admin.consultations.routes.show', [$visit, $route]));

        $response->assertOk();
        $this->assertArrayNotHasKey('specialtyBillingContext', $response->original->getData());
        $response->assertDontSee(__('consultation_specialties.billing.apply_charge'));
        $response->assertDontSee(__('consultation_specialties.billing.mapped_service'));
        $response->assertDontSee(route('admin.consultations.specialty-billing.preview', $visit));
    }

    public function test_specialist_reports_still_include_billing_mapping_health(): void
    {
        $reporter = User::factory()->create();
        $reporter->givePermissionTo(Permission::findOrCreate('reports.view', 'web'));

        $this->actingAs($reporter)
            ->getJson(route('admin.reports.consultation-specialties.data'))
            ->assertOk()
            ->assertJsonStructure(['summary' => ['billing_applications_count']]);
    }

    public function test_admin_config_can_inspect_hidden_deprecated_sections(): void
    {
        $admin = User::factory()->create();
        foreach (['consultation-specialties.view', 'consultation-specialties.configure'] as $permission) {
            $admin->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }

        $profile = $this->profile('dental');

        $sections = $this->withoutMiddleware(ConvertBladeViewsToInertia::class)
            ->actingAs($admin)
            ->get(route('admin.consultation-specialties.sections.index', $profile));

        $sections->assertOk()
            ->assertSee('dental_xray')
            ->assertSee(__('consultation_specialties.admin.deprecated_duplicate'))
            ->assertSee(__('consultation_specialties.admin.maps_to', ['section' => __('consultation_specialties.sections.investigations')]));

        $this->withoutMiddleware(ConvertBladeViewsToInertia::class)
            ->actingAs($admin)
            ->get(route('admin.consultation-specialties.show', $profile))
            ->assertOk()
            ->assertSee(__('consultation_specialties.admin.maps_to', ['section' => __('consultation_specialties.sections.diagnosis')]));
    }

    public function test_browser_fixture_metadata_uses_canonical_actions_and_sections(): void
    {
        $duplicates = array_merge(...array_map('array_keys', array_values(self::DUPLICATE_SECTIONS)));

        foreach (app(ConsultationSpecialtyBrowserFixtureService::class)->profileDefinitions() as $definition) {
            $this->assertSame([], array_intersect($definition['expected_sections'], $duplicates), "{$definition['code']} fixture expects a duplicate section");
            $this->assertSame([], array_intersect($definition['expected_actions'], $duplicates), "{$definition['code']} fixture expects a duplicate quick action");
        }
    }

    public function test_localisation_keys_exist_for_dedup_ui(): void
    {
        foreach (['en', 'fr'] as $locale) {
            foreach ([
                'consultation_specialties.favorites.specialty_suggestions',
                'consultation_specialties.summary_builder.sections.procedures',
                'consultation_specialties.admin.deprecated_duplicate',
                'consultation_specialties.admin.maps_to',
                'consultation_specialties.admin.hidden_from_doctor_workspace',
            ] as $key) {
                $this->assertTrue(Lang::has($key, $locale), "{$key} missing for {$locale}");
            }
        }
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
            'code' => 'DDP'.random_int(10000, 99999),
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
