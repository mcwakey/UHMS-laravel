<?php

namespace Tests\Feature\Consultations;

use App\Enums\DepartmentType;
use App\Enums\ServiceType;
use App\Enums\VisitStatus;
use App\Http\Middleware\ConvertBladeViewsToInertia;
use App\Models\ConsultationSpecialtyEntry;
use App\Models\ConsultationSpecialtyFavorite;
use App\Models\ConsultationSpecialtyProfile;
use App\Models\ConsultationSpecialtyProfileMapping;
use App\Models\Department;
use App\Models\Patient;
use App\Models\ServiceCatalog;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitConsultationRoute;
use App\Services\Consultation\Specialty\ConsultationSpecialtyBillingMappingService;
use App\Services\Consultation\Specialty\ConsultationSpecialtyFavoriteService;
use App\Services\Consultation\Specialty\ConsultationSpecialtyLayoutService;
use App\Services\Consultation\Specialty\ConsultationSpecialtyOrderSetService;
use App\Services\Consultation\Specialty\ConsultationSpecialtyProfileResolver;
use App\Services\Consultation\Specialty\ConsultationSpecialtyReadinessService;
use App\Services\Consultation\Specialty\ConsultationSpecialtySectionComponentRegistry;
use App\Services\Consultation\Specialty\ConsultationSpecialtySummaryBuilder;
use App\Services\Consultation\Specialty\DoctorSpecialtyWorkspaceService;
use App\Services\ConsultationSessionService;
use Database\Seeders\ConsultationSpecialtyFavoriteSeeder;
use Database\Seeders\ConsultationSpecialtyOrderSetSeeder;
use Database\Seeders\ConsultationSpecialtySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ConsultationPersonalisedWorkspaceHardeningTest extends TestCase
{
    use RefreshDatabase;

    private User $doctor;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(ConsultationSpecialtySeeder::class);
        $this->seed(ConsultationSpecialtyFavoriteSeeder::class);
        $this->seed(ConsultationSpecialtyOrderSetSeeder::class);

        $this->doctor = User::factory()->create();
        $doctorRole = Role::findOrCreate('Doctor', 'web');
        foreach (['consultations.view', 'consultations.create'] as $permission) {
            $doctorRole->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
        $this->doctor->assignRole($doctorRole);

        $this->admin = User::factory()->create();
        $adminRole = Role::findOrCreate('Specialty Admin', 'web');
        foreach ([
            'consultation-specialties.view',
            'consultation-specialties.configure',
            'consultation-specialties.update',
            'consultations.view',
            'consultations.create',
        ] as $permission) {
            $adminRole->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
        $this->admin->assignRole($adminRole);
    }

    public function test_all_profiles_pass_workspace_inventory_audit(): void
    {
        $registry = app(ConsultationSpecialtySectionComponentRegistry::class);

        foreach ($this->profileCases() as $case) {
            [$visit, $route, $context] = $this->consultationRouteFixture($case);
            $profile = $this->profile($case['code']);

            $this->assertTrue($profile->is_active, "{$case['code']} profile is inactive.");
            $this->assertSame($case['code'], $context->profile->code);
            $this->assertNotEmpty($context->sections, "{$case['code']} has no resolved sections.");

            $layout = app(ConsultationSpecialtyLayoutService::class)->buildLayout($context);
            $sections = collect($layout['sections']);

            $this->assertSame($case['code'], data_get($layout, 'profile.code'));
            $this->assertTrue($sections->isNotEmpty(), "{$case['code']} layout has no visible sections.");
            $this->assertSame(
                $sections->pluck('display_order')->sort()->values()->all(),
                $sections->pluck('display_order')->values()->all(),
                "{$case['code']} section order is not stable."
            );

            $sections->each(function (array $section) use ($case): void {
                $this->assertNotEmpty($section['component'], "{$case['code']}: {$section['key']} has no component.");
                $this->assertNotEmpty($section['icon'], "{$case['code']}: {$section['key']} has no icon.");

                if (! empty($section['form_fields'])) {
                    $this->assertSame(
                        'consultations.partials.specialty.structured-section',
                        $section['component'],
                        "{$case['code']}: schema-backed {$section['key']} should use the structured partial."
                    );
                }
            });

            $this->assertSame(
                'consultations.partials.specialty.generic-section',
                $registry->resolveComponent('future_unknown_section'),
            );

            $this->assertWorkspacePayloadIsSafe($visit, $route, $case, $sections->pluck('key')->all());
        }
    }

    public function test_representative_structured_entries_save_reload_and_do_not_cross_profile_leak(): void
    {
        foreach ($this->profileCases() as $case) {
            if (! isset($case['representative_section'])) {
                continue;
            }

            [$visit, $route] = $this->consultationRouteFixture($case);
            $profile = $this->profile($case['code']);
            $section = $case['representative_section'];
            $payload = $case['representative_payload'];

            $this->actingAs($this->doctor)
                ->postJson(route('admin.consultations.specialty-entries.store', [$visit, $section]), [
                    'consultation_route_id' => $route->id,
                    'specialty_profile_id' => $profile->id,
                ] + $payload)
                ->assertOk()
                ->assertJsonPath('success', true)
                ->assertJsonPath('entry.section_key', $section);

            $this->assertDatabaseHas('consultation_specialty_entries', [
                'consultation_id' => $route->id,
                'consultation_specialty_profile_id' => $profile->id,
                'section_key' => $section,
            ]);

            $response = $this->withoutMiddleware(ConvertBladeViewsToInertia::class)
                ->actingAs($this->doctor)
                ->get(route('admin.consultations.routes.show', [$visit, $route]));

            $response->assertOk();
            foreach ($case['reload_needles'] as $needle) {
                $response->assertSee($needle);
            }

            $wrongProfile = $this->firstDifferentProfile($case['code']);
            ConsultationSpecialtyEntry::query()->create([
                'consultation_id' => $route->id,
                'consultation_specialty_profile_id' => $wrongProfile->id,
                'section_key' => $section,
                'entry' => ['notes' => 'Wrong profile hardening sentinel'],
                'created_by' => $this->doctor->id,
                'updated_by' => $this->doctor->id,
            ]);

            $response = $this->withoutMiddleware(ConvertBladeViewsToInertia::class)
                ->actingAs($this->doctor)
                ->get(route('admin.consultations.routes.show', [$visit, $route]));

            $response->assertOk();
            $response->assertDontSee('Wrong profile hardening sentinel');
        }
    }

    public function test_readiness_and_summary_preview_work_for_every_specialist_profile(): void
    {
        foreach ($this->profileCases() as $case) {
            if ($case['code'] === 'general_medicine') {
                continue;
            }

            [$visit, $route, $context] = $this->consultationRouteFixture($case);
            $profile = $this->profile($case['code']);
            $readiness = app(ConsultationSpecialtyReadinessService::class)->evaluate($route, $context);

            $this->assertFalse($readiness->isFallback, "{$case['code']} readiness fell back unexpectedly.");
            $this->assertNotEmpty($readiness->blockingItems, "{$case['code']} should initially have blockers.");

            $this->saveCompleteReadinessData($route, $profile, $case);
            $this->addCoreClinicalRecord($route, ['complaint', 'diagnosis', 'plan']);

            $readiness = app(ConsultationSpecialtyReadinessService::class)->evaluate($route->fresh(['department', 'medicalRecord']), $context);
            $this->assertTrue($readiness->canComplete, "{$case['code']} readiness blockers were not cleared.");
            $this->assertEmpty($readiness->blockingItems, "{$case['code']} still has blockers.");

            $summary = app(ConsultationSpecialtySummaryBuilder::class)->build($route->fresh(['department', 'medicalRecord']), $context);
            $this->assertFalse($summary->isFallback, "{$case['code']} summary unexpectedly fell back.");
            $this->assertStringContainsString($case['summary_needle'], $summary->plainText(), "{$case['code']} summary missed saved fields.");

            $route->medicalRecord->forceFill(['final_note' => 'Existing hardening note'])->save();
            $this->actingAs($this->doctor)
                ->getJson(route('admin.consultations.specialty-summary.preview', $visit).'?consultation_route_id='.$route->id)
                ->assertOk()
                ->assertJsonPath('success', true)
                ->assertJsonPath('summary.isFallback', false);

            $this->assertSame('Existing hardening note', $route->medicalRecord->fresh()->final_note);
        }
    }

    public function test_favorites_order_sets_billing_and_doctor_workspace_are_safe_for_every_profile(): void
    {
        foreach ($this->profileCases() as $case) {
            [$visit, $route, $context] = $this->consultationRouteFixture($case);
            $profile = $this->profile($case['code']);

            $favorites = app(ConsultationSpecialtyFavoriteService::class)->getWorkspaceDefaults($profile);
            $this->assertArrayHasKey(ConsultationSpecialtyFavorite::TYPE_DIAGNOSIS, $favorites);
            $this->assertArrayHasKey('frequency_defaults', $favorites);

            $inactiveFavorite = ConsultationSpecialtyFavorite::query()->create([
                'consultation_specialty_profile_id' => $profile->id,
                'favorite_type' => ConsultationSpecialtyFavorite::TYPE_CLINICAL_INSTRUCTION,
                'label' => 'Inactive hardening favorite',
                'is_active' => false,
            ]);

            $this->assertFalse(app(ConsultationSpecialtyFavoriteService::class)
                ->getFavoritesForProfile($profile, ConsultationSpecialtyFavorite::TYPE_CLINICAL_INSTRUCTION)
                ->contains('id', $inactiveFavorite->id));

            $orderSets = app(ConsultationSpecialtyOrderSetService::class)->getWorkspaceOrderSets($context);
            if ($case['code'] !== 'general_medicine') {
                $this->assertNotEmpty($orderSets, "{$case['code']} should expose starter order sets.");
                $orderSet = \App\Models\ConsultationSpecialtyOrderSet::query()->findOrFail($orderSets[0]['id']);
                $preview = app(ConsultationSpecialtyOrderSetService::class)->previewOrderSet($route, $orderSet, $this->doctor);
                $this->assertNotEmpty($preview['items'], "{$case['code']} order set preview has no items.");

                $application = app(ConsultationSpecialtyOrderSetService::class)->applyOrderSet($route, $orderSet, $this->doctor);
                $this->assertContains($application->status, ['applied', 'partially_applied', 'previewed']);
            }

            $billing = app(ConsultationSpecialtyBillingMappingService::class)->getWorkspaceBillingContext($route, $context);
            $this->assertIsArray($billing);
            $this->assertArrayHasKey('warnings', $billing);
            $billingPreview = $this->actingAs($this->doctor)
                ->getJson(route('admin.consultations.specialty-billing.preview', [$visit, 'consultation_route_id' => $route->id]))
                ->assertStatus(422);
            $billingPreview->assertJsonValidationErrors(['billing']);

            $workspace = app(DoctorSpecialtyWorkspaceService::class)->build($this->doctor, $route, $context, [
                'specialtyOrderSets' => $orderSets,
                'specialtySummaryBuilder' => ['available' => true],
                'specialtyReadiness' => app(ConsultationSpecialtyReadinessService::class)->evaluate($route, $context),
            ])->toArray();

            $this->assertFalse($workspace['is_fallback'], "{$case['code']} doctor workspace fell back.");
            $this->assertSame($case['code'], data_get($workspace, 'profile.code'));
            $actionKeys = collect($workspace['quick_actions'])->pluck('key');
            foreach ($workspace['quick_actions'] as $action) {
                if (! empty($action['requires_section'])) {
                    $this->assertTrue(
                        collect($context->sections)->pluck('section_key')->contains($action['requires_section']),
                        "{$case['code']} quick action {$action['key']} targets a missing section."
                    );
                }
            }

            foreach ($case['expected_actions'] as $key) {
                $this->assertTrue($actionKeys->contains($key), "{$case['code']} is missing quick action {$key}.");
            }
        }

        $generalFavorites = app(ConsultationSpecialtyFavoriteService::class)->getWorkspaceDefaults($this->profile('general_medicine'));
        $this->assertSame([], $generalFavorites[ConsultationSpecialtyFavorite::TYPE_PROCEDURE]);
    }

    public function test_admin_configuration_pages_open_for_all_profiles_and_localisation_is_present(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.consultation-specialties.index'))
            ->assertOk()
            ->assertSee('General Medicine');

        $this->assertSame(11, ConsultationSpecialtyProfile::query()->where('is_active', true)->count());

        foreach ($this->profileCases() as $case) {
            $profile = $this->profile($case['code']);

            $this->actingAs($this->admin)
                ->get(route('admin.consultation-specialties.show', $profile))
                ->assertOk();

            $this->actingAs($this->admin)
                ->get(route('admin.consultation-specialties.sections.index', $profile))
                ->assertOk();

            $this->actingAs($this->admin)
                ->get(route('admin.consultation-specialties.favorites.index', $profile))
                ->assertOk();

            $this->actingAs($this->admin)
                ->get(route('admin.consultation-specialties.order-sets.index', $profile))
                ->assertOk();

            foreach (['en', 'fr'] as $locale) {
                $this->assertTrue(Lang::has("consultation_specialties.profiles.{$case['code']}", $locale));
                foreach ($profile->activeSections()->get() as $section) {
                    $this->assertTrue(Lang::has("consultation_specialties.sections.{$section->section_key}", $locale), "{$locale} missing {$section->section_key}");
                }
            }
        }

        $this->actingAs($this->admin)
            ->get(route('admin.consultation-specialties.service-mappings.index'))
            ->assertOk();

        $this->actingAs($this->admin)
            ->get(route('admin.consultation-specialties.doctor-preferences.index'))
            ->assertOk();

        $general = $this->profile('general_medicine');
        $this->actingAs($this->admin)
            ->delete(route('admin.consultation-specialties.destroy', $general))
            ->assertForbidden();
    }

    public function test_workspace_query_count_has_no_obvious_profile_specific_explosion(): void
    {
        $cases = collect($this->profileCases())
            ->whereIn('code', ['general_medicine', 'ophthalmology', 'dental', 'obstetrics', 'emergency', 'surgery'])
            ->values();

        foreach ($cases as $case) {
            [$visit, $route] = $this->consultationRouteFixture($case);
            DB::flushQueryLog();
            DB::enableQueryLog();

            $this->withoutMiddleware(ConvertBladeViewsToInertia::class)
                ->actingAs($this->doctor)
                ->get(route('admin.consultations.routes.show', [$visit, $route]))
                ->assertOk();

            $count = count(DB::getQueryLog());
            DB::disableQueryLog();

            $this->assertLessThan(
                260,
                $count,
                "{$case['code']} workspace query count is unexpectedly high: {$count}."
            );
        }
    }

    private function assertWorkspacePayloadIsSafe(Visit $visit, VisitConsultationRoute $route, array $case, array $sectionKeys): void
    {
        $response = $this->withoutMiddleware(ConvertBladeViewsToInertia::class)
            ->actingAs($this->doctor)
            ->get(route('admin.consultations.routes.show', [$visit, $route]));

        $response->assertOk()
            ->assertViewHas('specialtyContext', fn (array $context) => data_get($context, 'profile.code') === $case['code'])
            ->assertViewHas('specialtyLayout', fn (array $layout) => collect($layout['sections'])->isNotEmpty())
            ->assertViewHas('specialtyFavorites')
            ->assertViewHas('specialtyOrderSets')
            ->assertViewHas('specialtyReadiness')
            ->assertViewHas('specialtySummaryBuilder', fn (array $summary) => $summary['available'] === true)
            ->assertViewHas('doctorSpecialtyWorkspace');

        // Phase 16.5: the doctor workspace must not receive billing context.
        $this->assertArrayNotHasKey('specialtyBillingContext', $response->original->getData());

        foreach ($case['expected_sections'] as $sectionKey) {
            $this->assertContains($sectionKey, $sectionKeys, "{$case['code']} is missing {$sectionKey}");
        }
    }

    private function consultationRouteFixture(array $case): array
    {
        $department = Department::factory()->create([
            'name' => $case['department_name'],
            'code' => $case['department_code'].random_int(100, 999),
            'type' => $case['department_type']->value,
            'status' => 'active',
        ]);
        $this->doctor->forceFill(['department_id' => $department->id])->save();

        $patient = Patient::factory()->create(['registered_by' => $this->doctor->id]);
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'created_by' => $this->doctor->id,
            'status' => VisitStatus::CONSULTING->value,
            'current_department_id' => $department->id,
            'chief_complaint' => $case['code'] === 'general_medicine' ? 'General review' : null,
        ]);
        $service = ServiceCatalog::query()->create([
            'name' => $case['department_name'].' Consultation',
            'code' => 'HARD'.random_int(10000, 99999),
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
            'consultation_specialty_profile_id' => $this->profile($case['code'])->id,
            'department_id' => $department->id,
            'source' => 'hardening_test',
            'priority' => 100,
            'is_active' => true,
        ]);

        app(ConsultationSessionService::class)->getOrCreateMedicalRecordForRoute($route, $this->doctor);

        $route = $route->fresh(['department', 'medicalRecord', 'patient', 'visit']);
        $context = app(ConsultationSpecialtyProfileResolver::class)
            ->resolve($this->doctor, visit: $visit, consultationRoute: $route, department: $department);

        return [$visit, $route, $context];
    }

    private function saveCompleteReadinessData(VisitConsultationRoute $route, ConsultationSpecialtyProfile $profile, array $case): void
    {
        foreach ($case['readiness_entries'] as $section => $entry) {
            ConsultationSpecialtyEntry::query()->create([
                'consultation_id' => $route->id,
                'consultation_specialty_profile_id' => $profile->id,
                'section_key' => $section,
                'entry' => $entry,
                'created_by' => $this->doctor->id,
                'updated_by' => $this->doctor->id,
            ]);
        }
    }

    private function addCoreClinicalRecord(VisitConsultationRoute $route, array $sections): void
    {
        $record = $route->medicalRecord;
        $base = [
            'consultation_route_id' => $route->id,
            'visit_id' => $route->visit_id,
            'patient_id' => $route->patient_id,
            'department_id' => $route->department_id,
            'doctor_id' => $this->doctor->id,
            'created_by' => $this->doctor->id,
        ];

        if (in_array('complaint', $sections, true)) {
            $record->complaints()->create($base + ['description' => 'Hardening complaint']);
        }

        if (in_array('diagnosis', $sections, true)) {
            $record->diagnoses()->create($base + [
                'description' => 'Hardening diagnosis',
                'type' => 'provisional',
                'is_primary' => true,
            ]);
        }

        if (in_array('plan', $sections, true)) {
            $record->treatments()->create($base + [
                'type' => 'advice',
                'description' => 'Hardening plan',
            ]);
        }
    }

    private function profile(string $code): ConsultationSpecialtyProfile
    {
        return ConsultationSpecialtyProfile::query()->byCode($code)->firstOrFail();
    }

    private function firstDifferentProfile(string $code): ConsultationSpecialtyProfile
    {
        return ConsultationSpecialtyProfile::query()->where('code', '!=', $code)->firstOrFail();
    }

    private function profileCases(): array
    {
        return [
            [
                'code' => 'general_medicine',
                'department_name' => 'General Medicine',
                'department_code' => 'GEN',
                'department_type' => DepartmentType::CONSULTATION,
                'expected_sections' => ['patient_summary', 'complaints', 'diagnosis', 'summary'],
                'expected_actions' => ['complaints', 'generate_summary'],
                'readiness_entries' => [],
                'summary_needle' => 'Hardening diagnosis',
            ],
            [
                'code' => 'physiotherapy',
                'department_name' => 'Physiotherapy',
                'department_code' => 'PHY',
                'department_type' => DepartmentType::TREATMENT,
                'expected_sections' => ['complaints', 'pain_assessment', 'physical_assessment', 'treatment_plan'],
                'expected_actions' => ['pain_assessment', 'treatment_plan', 'order_sets'],
                'representative_section' => 'pain_assessment',
                'representative_payload' => ['pain_score' => 6, 'pain_location' => 'Lower back'],
                'reload_needles' => ['Lower back'],
                'readiness_entries' => [
                    'presenting_problem' => ['problem_description' => 'Low back pain'],
                    'pain_assessment' => ['pain_location' => 'Lower back'],
                    'physical_assessment' => ['gait' => 'Stable'],
                    'treatment_plan' => ['treatment_goals' => 'Improve mobility', 'session_frequency' => 'Weekly'],
                    'home_exercise_plan' => ['instructions' => 'Daily stretches'],
                ],
                'summary_needle' => 'Improve mobility',
            ],
            [
                'code' => 'ophthalmology',
                'department_name' => 'Ophthalmology',
                'department_code' => 'EYE',
                'department_type' => DepartmentType::CONSULTATION,
                'expected_sections' => ['complaints', 'visual_acuity', 'eye_examination', 'diagnosis'],
                'expected_actions' => ['visual_acuity', 'iop', 'order_sets'],
                'representative_section' => 'visual_acuity',
                'representative_payload' => ['right_eye_unaided' => '6/9', 'left_eye_unaided' => '6/12'],
                'reload_needles' => ['6/9', '6/12'],
                'readiness_entries' => [
                    'visual_acuity' => ['right_eye_unaided' => '6/9'],
                    'eye_examination' => ['cornea' => 'Clear'],
                    'iop' => ['right_eye_iop' => 15],
                    'follow_up' => ['warning_signs' => 'Return if worse'],
                ],
                'summary_needle' => '6/9',
            ],
            [
                'code' => 'dental',
                'department_name' => 'Dental',
                'department_code' => 'DEN',
                'department_type' => DepartmentType::CONSULTATION,
                'expected_sections' => ['complaints', 'tooth_chart', 'oral_examination', 'consent'],
                'expected_actions' => ['tooth_chart', 'consent', 'order_sets'],
                'representative_section' => 'tooth_chart',
                'representative_payload' => ['tooth_number' => '36', 'condition' => 'Caries'],
                'reload_needles' => ['36', 'Caries'],
                'readiness_entries' => [
                    'tooth_chart' => ['tooth_number' => '36', 'condition' => 'Caries'],
                    'dental_diagnosis' => ['diagnosis_text' => 'Pulpitis'],
                    'dental_procedures' => ['procedure_planned' => 'Restoration'],
                    'consent' => ['consent_required' => false],
                    'follow_up' => ['patient_instructions' => 'Return if pain worsens'],
                ],
                'summary_needle' => 'Pulpitis',
            ],
            [
                'code' => 'obstetrics',
                'department_name' => 'Obstetrics',
                'department_code' => 'OBS',
                'department_type' => DepartmentType::CONSULTATION,
                'expected_sections' => ['complaints', 'obstetric_history', 'current_pregnancy', 'fetal_assessment', 'birth_plan'],
                'expected_actions' => ['obstetric_history', 'fetal_assessment', 'order_sets'],
                'representative_section' => 'antenatal_vitals',
                'representative_payload' => ['blood_pressure' => '120/80', 'weight' => 72],
                'reload_needles' => ['120/80'],
                'readiness_entries' => [
                    'obstetric_history' => ['gravida' => 2],
                    'current_pregnancy' => ['pregnancy_confirmed' => true, 'current_complaints' => 'None'],
                    'fetal_assessment' => ['fetal_heart_rate' => 144],
                    'risk_assessment' => ['risk_level' => 'Low'],
                    'birth_plan' => ['planned_place' => 'UHMS maternity'],
                    'follow_up' => ['follow_up_date' => now()->addWeeks(4)->toDateString()],
                ],
                'summary_needle' => '144',
            ],
            [
                'code' => 'gynecology',
                'department_name' => 'Gynecology',
                'department_code' => 'GYN',
                'department_type' => DepartmentType::CONSULTATION,
                'expected_sections' => ['complaints', 'menstrual_history', 'pelvic_examination', 'breast_examination'],
                'expected_actions' => ['gyne_complaint', 'pelvic_examination', 'order_sets'],
                'representative_section' => 'menstrual_history',
                'representative_payload' => ['cycle_length' => '28 days', 'bleeding_pattern' => 'Regular'],
                'reload_needles' => ['28 days', 'Regular'],
                'readiness_entries' => [
                    'gyne_complaint' => ['complaint_text' => 'Pelvic pain'],
                    'menstrual_history' => ['cycle_length' => '28 days'],
                    'pelvic_examination' => ['exam_notes' => 'No cervical motion tenderness'],
                    'follow_up' => ['patient_instructions' => 'Review results'],
                ],
                'summary_needle' => 'Pelvic pain',
            ],
            [
                'code' => 'ent',
                'department_name' => 'ENT',
                'department_code' => 'ENT',
                'department_type' => DepartmentType::CONSULTATION,
                'expected_sections' => ['complaints', 'ear_assessment', 'nose_assessment', 'throat_assessment'],
                'expected_actions' => ['ent_complaint', 'ear_assessment', 'order_sets'],
                'representative_section' => 'ear_assessment',
                'representative_payload' => ['ear_pain' => true, 'otoscopy_right' => 'Inflamed canal'],
                'reload_needles' => ['Inflamed canal'],
                'readiness_entries' => [
                    'ent_complaint' => ['complaint_text' => 'Ear pain'],
                    'ear_assessment' => ['otoscopy_right' => 'Inflamed canal'],
                    'follow_up' => ['warning_signs' => 'Return if fever'],
                ],
                'summary_needle' => 'Inflamed canal',
            ],
            [
                'code' => 'pediatrics',
                'department_name' => 'Pediatrics',
                'department_code' => 'PED',
                'department_type' => DepartmentType::CONSULTATION,
                'expected_sections' => ['complaints', 'growth_assessment', 'immunization_status', 'caregiver_instructions'],
                'expected_actions' => ['pediatric_complaint', 'growth_assessment', 'order_sets'],
                'representative_section' => 'growth_assessment',
                'representative_payload' => ['weight' => 18.5, 'growth_concern' => 'No concerns'],
                'reload_needles' => ['18.5', 'No concerns'],
                'readiness_entries' => [
                    'pediatric_complaint' => ['complaint_text' => 'Fever'],
                    'growth_assessment' => ['weight' => 18.5],
                    'pediatric_examination' => ['general_appearance' => 'Alert'],
                    'caregiver_instructions' => ['instructions' => 'Fluids and return precautions'],
                ],
                'summary_needle' => '18.5',
            ],
            [
                'code' => 'emergency',
                'department_name' => 'Emergency',
                'department_code' => 'EMR',
                'department_type' => DepartmentType::EMERGENCY,
                'expected_sections' => ['triage_summary', 'primary_survey', 'vitals_monitoring', 'handover'],
                'expected_actions' => ['triage_summary', 'primary_survey', 'handover'],
                'representative_section' => 'primary_survey',
                'representative_payload' => ['airway' => 'Patent', 'breathing' => 'Spontaneous', 'gcs' => 15],
                'reload_needles' => ['Patent', 'Spontaneous'],
                'readiness_entries' => [
                    'triage_summary' => ['triage_category' => 'Yellow'],
                    'primary_survey' => ['airway' => 'Patent'],
                    'vitals_monitoring' => ['blood_pressure' => '118/76'],
                    'disposition' => ['disposition' => 'Observe'],
                    'handover' => ['handover_to' => 'Emergency nurse'],
                ],
                'summary_needle' => 'Patent',
            ],
            [
                'code' => 'orthopedics',
                'department_name' => 'Orthopedics',
                'department_code' => 'ORT',
                'department_type' => DepartmentType::CONSULTATION,
                'expected_sections' => ['complaints', 'joint_limb_examination', 'neurovascular_status', 'cast_splint_plan'],
                'expected_actions' => ['ortho_complaint', 'neurovascular_status', 'order_sets'],
                'representative_section' => 'neurovascular_status',
                'representative_payload' => ['pulse_present' => true, 'capillary_refill' => 'Less than 2 seconds'],
                'reload_needles' => ['Less than 2 seconds'],
                'readiness_entries' => [
                    'ortho_complaint' => ['complaint_text' => 'Wrist injury'],
                    'joint_limb_examination' => ['tenderness' => 'Distal radius'],
                    'neurovascular_status' => ['capillary_refill' => 'Less than 2 seconds'],
                    'procedure_plan' => ['procedure_planned' => 'Splint'],
                    'follow_up' => ['follow_up_reason' => 'X-ray review'],
                ],
                'summary_needle' => 'Distal radius',
            ],
            [
                'code' => 'surgery',
                'department_name' => 'Surgery',
                'department_code' => 'SUR',
                'department_type' => DepartmentType::CONSULTATION,
                'expected_sections' => ['complaints', 'wound_assessment', 'local_or_abdominal_exam', 'consent'],
                'expected_actions' => ['surgical_complaint', 'procedures', 'order_sets'],
                'representative_section' => 'consent',
                'representative_payload' => ['consent_required' => true, 'consent_obtained' => true, 'consent_type' => 'Debridement'],
                'reload_needles' => ['Debridement'],
                'readiness_entries' => [
                    'surgical_complaint' => ['complaint_text' => 'Wound review'],
                    'local_or_abdominal_exam' => ['inspection' => 'Clean wound'],
                    'procedure_plan' => ['procedure_planned' => 'Dressing'],
                    'consent' => ['consent_required' => true, 'consent_obtained' => true],
                    'follow_up' => ['patient_instructions' => 'Dressing change'],
                ],
                'summary_needle' => 'Clean wound',
            ],
        ];
    }
}
