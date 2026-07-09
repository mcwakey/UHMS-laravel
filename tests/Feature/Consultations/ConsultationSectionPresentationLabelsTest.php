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
use App\Services\Consultation\Specialty\ConsultationSpecialtyLayoutService;
use App\Services\Consultation\Specialty\ConsultationSpecialtyProfileResolver;
use App\Services\Consultation\Specialty\ConsultationSpecialtySectionAliasService;
use App\Services\Consultation\Specialty\ConsultationSpecialtySummaryBuilder;
use App\Services\Consultation\Specialty\ConsultationSpecialtySummaryTemplateRegistry;
use App\Services\ConsultationSessionService;
use Database\Seeders\ConsultationSpecialtySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Lang;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ConsultationSectionPresentationLabelsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Every profile-aware label the phase requires, keyed by profile then
     * canonical section. Only keys that a profile actually resolves (via a
     * workspace section or a summary template row) are listed.
     */
    private const EXPECTED_LABELS = [
        'general_medicine' => [
            'complaints' => 'Complaints',
            'diagnosis' => 'Diagnosis',
            'investigations' => 'Investigations',
            'procedures' => 'Procedures',
            'prescription' => 'Prescription',
            'tasks' => 'Tasks',
        ],
        'physiotherapy' => [
            'complaints' => 'Presenting Problem',
            'tasks' => 'Therapy Tasks',
        ],
        'ophthalmology' => [
            'complaints' => 'Eye Complaint',
            'diagnosis' => 'Eye Diagnosis',
            'investigations' => 'Eye Investigations',
            'procedures' => 'Eye Procedures',
            'prescription' => 'Eye Prescription / Treatment',
            'follow_up' => 'Eye Follow-up',
        ],
        'dental' => [
            'complaints' => 'Dental Complaint',
            'diagnosis' => 'Dental Diagnosis',
            'investigations' => 'Dental Investigations / X-ray',
            'procedures' => 'Dental Procedures',
            'prescription' => 'Dental Prescription / Treatment',
        ],
        'obstetrics' => [
            'complaints' => 'Current Complaint',
            'investigations' => 'ANC Investigations / Screening',
            'diagnosis' => 'Obstetric Diagnosis',
            'prescription' => 'Obstetric Treatment / Prescription',
            'follow_up' => 'ANC Follow-up',
        ],
        'gynecology' => [
            'complaints' => 'Gyne Complaint',
            'diagnosis' => 'Gyne Diagnosis',
        ],
        'ent' => [
            'complaints' => 'ENT Complaint',
            'diagnosis' => 'ENT Diagnosis',
        ],
        'pediatrics' => [
            'complaints' => 'Pediatric Complaint',
        ],
        'emergency' => [
            'complaints' => 'Emergency Complaint',
            'diagnosis' => 'Emergency Diagnosis',
            'investigations' => 'Emergency Investigations',
            'procedures' => 'Emergency Procedures',
            'prescription' => 'Emergency Treatment / Prescription',
        ],
        'orthopedics' => [
            'complaints' => 'Ortho Complaint',
            'diagnosis' => 'Orthopedic Diagnosis',
            'investigations' => 'Orthopedic Imaging / Investigations',
            'procedures' => 'Orthopedic Procedures',
        ],
        'surgery' => [
            'complaints' => 'Surgical Complaint',
            'diagnosis' => 'Surgical Diagnosis',
            'investigations' => 'Surgical Investigations',
            'procedures' => 'Surgical Procedures',
        ],
    ];

    private const SUMMARY_TITLES = [
        'physiotherapy' => 'Physiotherapy Summary',
        'ophthalmology' => 'Eye Consultation Summary',
        'dental' => 'Dental Summary',
        'obstetrics' => 'Obstetric Summary',
        'gynecology' => 'Gyne Summary',
        'ent' => 'ENT Summary',
        'pediatrics' => 'Pediatric Summary',
        'emergency' => 'Emergency Summary',
        'orthopedics' => 'Orthopedic Summary',
        'surgery' => 'Surgical Summary',
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

    public function test_alias_service_resolves_every_required_profile_aware_label(): void
    {
        $aliases = app(ConsultationSpecialtySectionAliasService::class);

        foreach (self::EXPECTED_LABELS as $profileCode => $sections) {
            foreach ($sections as $canonicalKey => $expectedLabel) {
                $this->assertSame(
                    $expectedLabel,
                    $aliases->presentationLabelFor($profileCode, $canonicalKey),
                    "{$profileCode}.{$canonicalKey} label mismatch",
                );
            }
        }
    }

    public function test_summary_document_titles_use_the_same_presentation_service(): void
    {
        $aliases = app(ConsultationSpecialtySectionAliasService::class);
        $templates = app(ConsultationSpecialtySummaryTemplateRegistry::class);

        foreach (self::SUMMARY_TITLES as $profileCode => $expectedTitle) {
            $this->assertSame($expectedTitle, $aliases->presentationLabelFor($profileCode, 'summary'));

            $template = $templates->templateForProfile($this->profile($profileCode));
            $this->assertSame($expectedTitle, $template['title'], "{$profileCode} summary document title mismatch");
        }
    }

    public function test_preview_headings_use_profile_aware_labels_for_every_profile(): void
    {
        $templates = app(ConsultationSpecialtySummaryTemplateRegistry::class);

        foreach (self::EXPECTED_LABELS as $profileCode => $sections) {
            if ($profileCode === 'general_medicine') {
                // general_medicine's template always takes the `fallback`
                // path (ConsultationSpecialtySummaryBuilder short-circuits to
                // the general ConsultationSummaryService before ever reading
                // these rows), so its own template labels are unused dead
                // code — covered separately by the alias-service unit test.
                continue;
            }

            $template = $templates->templateForProfile($this->profile($profileCode));
            $labelsByCanonicalKey = collect($template['sections'])
                ->filter(fn (array $section) => in_array($section['canonical_key'], array_keys($sections), true))
                ->pluck('label', 'canonical_key');

            foreach ($sections as $canonicalKey => $expectedLabel) {
                $this->assertSame(
                    $expectedLabel,
                    $labelsByCanonicalKey[$canonicalKey] ?? null,
                    "{$profileCode} summary row for {$canonicalKey} has the wrong heading",
                );
            }
        }
    }

    public function test_workspace_sidebar_and_summary_preview_labels_match(): void
    {
        $layoutService = app(ConsultationSpecialtyLayoutService::class);
        $templates = app(ConsultationSpecialtySummaryTemplateRegistry::class);

        foreach (['ophthalmology', 'dental', 'obstetrics', 'orthopedics', 'surgery', 'emergency'] as $profileCode) {
            $profile = $this->profile($profileCode);
            $layout = $layoutService->buildLayout($this->rawContext($profile));
            $template = $templates->templateForProfile($profile);

            $workspaceLabels = collect($layout['sections'])->pluck('translated_label', 'canonical_key');
            $summaryLabels = collect($template['sections'])->pluck('label', 'canonical_key');

            foreach (self::EXPECTED_LABELS[$profileCode] as $canonicalKey => $expectedLabel) {
                if ($canonicalKey === 'follow_up' && ! $workspaceLabels->has('follow_up')) {
                    continue;
                }

                $this->assertSame($expectedLabel, $workspaceLabels[$canonicalKey] ?? null, "{$profileCode} workspace label for {$canonicalKey} mismatch");
                if ($summaryLabels->has($canonicalKey)) {
                    $this->assertSame(
                        $workspaceLabels[$canonicalKey],
                        $summaryLabels[$canonicalKey],
                        "{$profileCode} workspace and summary preview disagree on the {$canonicalKey} heading",
                    );
                }
            }
        }
    }

    public function test_dental_preview_shows_specialty_labeled_headings_with_merged_legacy_data(): void
    {
        [, $route, $context] = $this->consultationRouteFixture('Dental', 'DEN', 'dental');
        $profile = $this->profile('dental');

        $this->entry($route, $profile, 'dental_diagnosis', ['diagnosis_text' => 'E2E pulpitis diagnosis']);
        $this->entry($route, $profile, 'dental_xray', ['xray_findings' => 'E2E periapical radiolucency']);
        $this->entry($route, $profile, 'dental_procedures', ['procedure_planned' => 'E2E tooth extraction']);

        $summary = app(ConsultationSpecialtySummaryBuilder::class)->build($route->fresh(['department', 'medicalRecord']), $context);
        $labels = collect($summary->sections)->pluck('label');

        $this->assertTrue($labels->contains('Dental Diagnosis'));
        $this->assertTrue($labels->contains('Dental Investigations / X-ray'));
        $this->assertTrue($labels->contains('Dental Procedures'));
        $this->assertStringContainsString('E2E pulpitis diagnosis', $summary->plainText());
        $this->assertStringContainsString('E2E periapical radiolucency', $summary->plainText());
        $this->assertStringContainsString('E2E tooth extraction', $summary->plainText());
    }

    public function test_obstetrics_preview_merges_legacy_screening_under_anc_investigations_heading(): void
    {
        [, $route, $context] = $this->consultationRouteFixture('Obstetrics', 'OBS', 'obstetrics');
        $profile = $this->profile('obstetrics');

        $this->entry($route, $profile, 'lab_screening', ['hb' => 'E2E Hb 10.2']);
        $this->entry($route, $profile, 'ultrasound_findings', ['findings' => 'E2E single live fetus']);

        $summary = app(ConsultationSpecialtySummaryBuilder::class)->build($route->fresh(['department', 'medicalRecord']), $context);
        $labels = collect($summary->sections)->pluck('label');

        $this->assertTrue($labels->contains('ANC Investigations / Screening'));
        $this->assertSame(1, $labels->filter(fn ($label) => $label === 'ANC Investigations / Screening')->count());
        $this->assertStringContainsString('E2E Hb 10.2', $summary->plainText());
        $this->assertStringContainsString('E2E single live fetus', $summary->plainText());
    }

    public function test_emergency_preview_shows_emergency_treatment_prescription_heading(): void
    {
        [, $route, $context] = $this->consultationRouteFixture('Emergency', 'EMR', 'emergency', DepartmentType::EMERGENCY);
        $profile = $this->profile('emergency');

        $this->entry($route, $profile, 'medications_given', ['medications' => 'E2E IV paracetamol given']);

        $summary = app(ConsultationSpecialtySummaryBuilder::class)->build($route->fresh(['department', 'medicalRecord']), $context);
        $labels = collect($summary->sections)->pluck('label');

        $this->assertTrue($labels->contains('Emergency Treatment / Prescription'));
        $this->assertStringContainsString('E2E IV paracetamol given', $summary->plainText());
    }

    public function test_orthopedics_preview_shows_orthopedic_imaging_investigations_heading(): void
    {
        [, $route, $context] = $this->consultationRouteFixture('Orthopedics', 'ORT', 'orthopedics');
        $profile = $this->profile('orthopedics');

        $this->entry($route, $profile, 'imaging', ['xray_findings' => 'E2E distal radius fracture']);

        $summary = app(ConsultationSpecialtySummaryBuilder::class)->build($route->fresh(['department', 'medicalRecord']), $context);
        $labels = collect($summary->sections)->pluck('label');

        $this->assertTrue($labels->contains('Orthopedic Imaging / Investigations'));
        $this->assertStringContainsString('E2E distal radius fracture', $summary->plainText());
    }

    public function test_surgery_preview_shows_surgical_procedures_heading(): void
    {
        [, $route, $context] = $this->consultationRouteFixture('Surgery', 'SUR', 'surgery');
        $profile = $this->profile('surgery');

        $this->entry($route, $profile, 'procedure_plan', ['procedure_planned' => 'E2E appendectomy']);

        $summary = app(ConsultationSpecialtySummaryBuilder::class)->build($route->fresh(['department', 'medicalRecord']), $context);
        $labels = collect($summary->sections)->pluck('label');

        $this->assertTrue($labels->contains('Surgical Procedures'));
        $this->assertStringContainsString('E2E appendectomy', $summary->plainText());
    }

    public function test_no_duplicate_headings_appear_in_any_profile_summary(): void
    {
        $templates = app(ConsultationSpecialtySummaryTemplateRegistry::class);

        foreach (array_keys(self::EXPECTED_LABELS) as $profileCode) {
            $template = $templates->templateForProfile($this->profile($profileCode));
            $labels = collect($template['sections'])->pluck('label');

            $this->assertSame(
                $labels->count(),
                $labels->unique()->count(),
                "{$profileCode} summary template produces duplicate headings: ".$labels->duplicates()->implode(', '),
            );
        }
    }

    public function test_hidden_legacy_section_keys_never_appear_as_raw_headings(): void
    {
        [, $route, $context] = $this->consultationRouteFixture('Dental', 'DEN', 'dental');
        $profile = $this->profile('dental');

        $this->entry($route, $profile, 'dental_diagnosis', ['diagnosis_text' => 'E2E pulpitis']);
        $this->entry($route, $profile, 'dental_xray', ['xray_findings' => 'E2E radiolucency']);
        $this->entry($route, $profile, 'dental_procedures', ['procedure_planned' => 'E2E extraction']);
        $this->entry($route, $profile, 'dental_complaint', ['complaint_text' => 'E2E toothache']);

        $summary = app(ConsultationSpecialtySummaryBuilder::class)->build($route->fresh(['department', 'medicalRecord']), $context);
        $labels = collect($summary->sections)->pluck('label')->implode(' | ');

        foreach (['dental_diagnosis', 'dental_xray', 'dental_procedures', 'dental_complaint'] as $legacyKey) {
            $this->assertStringNotContainsString($legacyKey, $labels, "raw legacy key {$legacyKey} leaked into a summary heading");
        }
    }

    public function test_final_note_is_not_modified_by_preview(): void
    {
        [$visit, $route] = $this->consultationRouteFixture('Dental', 'DEN', 'dental');
        $route->medicalRecord->forceFill(['final_note' => 'Existing clinician note'])->save();
        $this->entry($route, $this->profile('dental'), 'dental_diagnosis', ['diagnosis_text' => 'E2E abscess']);

        $this->actingAs($this->doctor)
            ->getJson(route('admin.consultations.specialty-summary.preview', $visit).'?consultation_route_id='.$route->id)
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame('Existing clinician note', $route->medicalRecord->fresh()->final_note);
    }

    public function test_billing_context_is_not_exposed_by_preview_or_workspace(): void
    {
        [$visit, $route] = $this->consultationRouteFixture('Dental', 'DEN', 'dental');

        $previewResponse = $this->actingAs($this->doctor)
            ->getJson(route('admin.consultations.specialty-summary.preview', $visit).'?consultation_route_id='.$route->id)
            ->assertOk();

        $this->assertArrayNotHasKey('specialtyBillingContext', $previewResponse->json('summary') ?? []);
        $this->assertStringNotContainsString('billing', strtolower(json_encode($previewResponse->json())));

        $workspaceResponse = $this->withoutMiddleware(ConvertBladeViewsToInertia::class)
            ->actingAs($this->doctor)
            ->get(route('admin.consultations.routes.show', [$visit, $route]));

        $workspaceResponse->assertOk();
        $this->assertArrayNotHasKey('specialtyBillingContext', $workspaceResponse->original->getData());
    }

    public function test_localisation_keys_exist_for_section_presentation_labels(): void
    {
        foreach (['en', 'fr'] as $locale) {
            foreach (self::EXPECTED_LABELS as $profileCode => $sections) {
                if ($profileCode === 'general_medicine') {
                    continue;
                }

                foreach (array_keys($sections) as $canonicalKey) {
                    $key = "consultation_specialties.section_presentation_labels.{$profileCode}.{$canonicalKey}";
                    $this->assertTrue(Lang::has($key, $locale), "{$key} missing for {$locale}");
                }
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
            'code' => 'SPL'.random_int(10000, 99999),
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

        $context = app(ConsultationSpecialtyProfileResolver::class)
            ->resolve($this->doctor, visit: $visit, consultationRoute: $route->fresh('department'), department: $department);

        return [$visit, $route->fresh(['department', 'medicalRecord']), $context];
    }
}
