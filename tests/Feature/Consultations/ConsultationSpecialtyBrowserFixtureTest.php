<?php

namespace Tests\Feature\Consultations;

use App\Models\ConsultationSpecialtyEntry;
use App\Models\ConsultationSpecialtyProfile;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitConsultationRoute;
use App\Services\Consultation\Specialty\ConsultationSpecialtyBrowserFixtureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ConsultationSpecialtyBrowserFixtureTest extends TestCase
{
    use RefreshDatabase;

    public function test_specialty_fixture_command_returns_metadata_for_all_profiles(): void
    {
        $this->assertSame(0, Artisan::call('consultation:specialty-e2e-fixture', ['--json' => true]));
        $metadata = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame('/login', $metadata['login_url']);
        $this->assertSame(ConsultationSpecialtyBrowserFixtureService::ADMIN_EMAIL, $metadata['admin']['email']);
        $this->assertCount(11, $metadata['profiles']);
        $this->assertFileExists($metadata['metadata_path']);

        $covered = collect($metadata['profiles'])->pluck('profile_code')->all();
        $this->assertEqualsCanonicalizing([
            'general_medicine',
            'physiotherapy',
            'ophthalmology',
            'dental',
            'obstetrics',
            'gynecology',
            'ent',
            'pediatrics',
            'emergency',
            'orthopedics',
            'surgery',
        ], $covered);

        foreach ($metadata['profiles'] as $fixture) {
            $this->assertStringStartsWith('/admin/consultations/', $fixture['workspace_url']);
            $this->assertNotEmpty($fixture['doctor_email']);
            $this->assertSame(ConsultationSpecialtyBrowserFixtureService::PASSWORD, $fixture['doctor_password']);
            $this->assertNotEmpty($fixture['expected_sections']);
            $this->assertNotEmpty($fixture['expected_quick_actions']);
            $this->assertArrayHasKey('expected_structured_section', $fixture);
        }

        $stored = json_decode(File::get($metadata['metadata_path']), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame($metadata['profiles'][0]['route_id'], $stored['profiles'][0]['route_id']);
    }

    public function test_specialty_fixture_is_idempotent_and_creates_structured_entries(): void
    {
        $first = app(ConsultationSpecialtyBrowserFixtureService::class)->create();
        $second = app(ConsultationSpecialtyBrowserFixtureService::class)->create();

        $this->assertSame(
            collect($first['profiles'])->pluck('route_id')->all(),
            collect($second['profiles'])->pluck('route_id')->all()
        );

        $this->assertSame(11, User::query()
            ->where('email', 'like', 'specialist.%.e2e@uhms.test')
            ->where('email', '!=', ConsultationSpecialtyBrowserFixtureService::ADMIN_EMAIL)
            ->count());
        $this->assertSame(1, User::query()->where('email', ConsultationSpecialtyBrowserFixtureService::ADMIN_EMAIL)->count());
        $this->assertSame(11, Patient::query()->where('patient_number', 'like', 'E2E-SPECIALIST-%')->count());
        $this->assertSame(11, Visit::query()->where('visit_number', 'like', 'E2E-SPECIALIST-VISIT-%')->count());
        $this->assertSame(11, VisitConsultationRoute::query()->where('notes', 'Opt-in specialist workspace E2E route.')->count());

        $physio = collect($first['profiles'])->firstWhere('profile_code', 'physiotherapy');
        $this->assertDatabaseHas('consultation_specialty_entries', [
            'consultation_id' => $physio['route_id'],
            'section_key' => 'pain_assessment',
        ]);
        $this->assertSame(1, ConsultationSpecialtyEntry::query()
            ->where('consultation_id', $physio['route_id'])
            ->where('section_key', 'pain_assessment')
            ->count());
    }

    public function test_fixture_doctors_and_admin_have_required_permissions(): void
    {
        $metadata = app(ConsultationSpecialtyBrowserFixtureService::class)->create();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $service = app(ConsultationSpecialtyBrowserFixtureService::class);
        $doctor = User::query()->where('email', $metadata['profiles'][0]['doctor_email'])->firstOrFail();
        foreach ($service->doctorPermissions() as $permission) {
            $this->assertTrue($doctor->can($permission), "Fixture doctor is missing {$permission}.");
        }

        $admin = User::query()->where('email', ConsultationSpecialtyBrowserFixtureService::ADMIN_EMAIL)->firstOrFail();
        foreach ($service->adminPermissions() as $permission) {
            $this->assertTrue($admin->can($permission), "Fixture admin is missing {$permission}.");
        }
    }

    public function test_admin_setup_pages_open_for_fixture_admin(): void
    {
        $metadata = app(ConsultationSpecialtyBrowserFixtureService::class)->create();
        $admin = User::query()->where('email', $metadata['admin']['email'])->firstOrFail();

        $this->actingAs($admin)->get(route('admin.consultation-specialties.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.consultation-specialties.service-mappings.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.reports.consultation-specialties.index'))->assertOk();

        foreach (ConsultationSpecialtyProfile::query()->ordered()->get() as $profile) {
            $this->actingAs($admin)->get(route('admin.consultation-specialties.sections.index', $profile))->assertOk();
            $this->actingAs($admin)->get(route('admin.consultation-specialties.favorites.index', $profile))->assertOk();
            $this->actingAs($admin)->get(route('admin.consultation-specialties.order-sets.index', $profile))->assertOk();
        }
    }

    public function test_fixture_command_refuses_non_local_environments(): void
    {
        app()->detectEnvironment(fn () => 'production');

        try {
            $this->assertSame(1, Artisan::call('consultation:specialty-e2e-fixture', ['--json' => true]));
            $this->assertStringContainsString('only available locally or in testing', Artisan::output());
        } finally {
            app()->detectEnvironment(fn () => 'testing');
        }
    }

    public function test_playwright_specialty_smoke_is_wired_to_fixture_command(): void
    {
        $spec = file_get_contents(base_path('tests-e2e/tests/consultation-specialty-workspaces.spec.ts'));
        $helper = file_get_contents(base_path('tests-e2e/tests/support/consultation-fixture.ts'));

        $this->assertStringContainsString('specialtyWorkspaceFixtures', $helper);
        $this->assertStringContainsString("consultation:specialty-e2e-fixture', '--json'", $helper);
        $this->assertStringContainsString('SPECIALTY_PROFILES', $spec);
        $this->assertStringContainsString('VIEWPORT_PROFILES', $spec);
        $this->assertStringContainsString('generateSpecialtySummaryBtn', $spec);
        $this->assertStringContainsString('data-order-set-preview', $spec);
        $this->assertStringContainsString('fixtures.admin.report_url', $spec);
    }
}
