<?php

namespace Tests\Feature\Consultations;

use App\Models\MedicalRecord;
use App\Models\Patient;
use App\Models\ServiceCatalog;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitConsultationRoute;
use App\Services\Consultation\ConsultationBrowserFixtureService;
use Database\Seeders\Testing\ConsultationWorkspaceE2ESeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ConsultationBrowserFixturePhase6Test extends TestCase
{
    use RefreshDatabase;

    public function test_e2e_fixture_seeder_is_guarded_to_local_and_testing(): void
    {
        $source = file_get_contents(database_path('seeders/Testing/ConsultationWorkspaceE2ESeeder.php'));

        $this->assertStringContainsString("App::environment(['local', 'testing'])", $source);
        $this->assertStringContainsString('ConsultationBrowserFixtureService', $source);
        $this->assertStringNotContainsString('Patient::factory()->create', $source);
    }

    public function test_fixture_service_creates_discoverable_consultation_workspace_metadata(): void
    {
        $metadata = app(ConsultationBrowserFixtureService::class)->create();

        $this->assertSame(ConsultationBrowserFixtureService::USER_EMAIL, $metadata['email']);
        $this->assertSame(ConsultationBrowserFixtureService::USER_PASSWORD, $metadata['password']);
        $this->assertStringStartsWith('/admin/consultations/', $metadata['consultation_url']);
        $this->assertSame(route('admin.consultations.routes.show', [$metadata['visit_id'], $metadata['consultation_route_id']], false), $metadata['consultation_url']);
        $this->assertFileExists($metadata['metadata_path']);

        $stored = json_decode(File::get($metadata['metadata_path']), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame($metadata['consultation_route_id'], $stored['consultation_route_id']);
        $this->assertSame($metadata['consultation_url'], $stored['consultation_url']);
    }

    public function test_fixture_creates_doctor_patient_visit_active_route_and_medical_record(): void
    {
        $metadata = app(ConsultationBrowserFixtureService::class)->create();

        $doctor = User::where('email', ConsultationBrowserFixtureService::USER_EMAIL)->firstOrFail();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (app(ConsultationBrowserFixtureService::class)->permissions() as $permission) {
            $this->assertTrue($doctor->can($permission), "Fixture doctor is missing {$permission}.");
        }

        $this->assertDatabaseHas('patients', [
            'id' => $metadata['patient_id'],
            'patient_number' => ConsultationBrowserFixtureService::PATIENT_NUMBER,
        ]);
        $this->assertDatabaseHas('visits', [
            'id' => $metadata['visit_id'],
            'visit_number' => ConsultationBrowserFixtureService::VISIT_NUMBER,
        ]);
        $this->assertDatabaseHas('visit_consultation_routes', [
            'id' => $metadata['consultation_route_id'],
            'visit_id' => $metadata['visit_id'],
            'status' => VisitConsultationRoute::STATUS_ACTIVE,
        ]);
        $this->assertDatabaseHas('medical_records', [
            'id' => $metadata['medical_record_id'],
            'visit_id' => $metadata['visit_id'],
            'consultation_route_id' => $metadata['consultation_route_id'],
        ]);
        $this->assertDatabaseHas('service_catalog', [
            'id' => $metadata['procedure_service_id'],
            'department_id' => $metadata['procedure_department_id'],
        ]);
    }

    public function test_fixture_can_be_re_run_without_duplicating_core_fixture_records(): void
    {
        $first = app(ConsultationBrowserFixtureService::class)->create();
        $second = app(ConsultationBrowserFixtureService::class)->create();

        $this->assertSame($first['patient_id'], $second['patient_id']);
        $this->assertSame($first['visit_id'], $second['visit_id']);
        $this->assertSame($first['consultation_route_id'], $second['consultation_route_id']);
        $this->assertSame($first['medical_record_id'], $second['medical_record_id']);

        $this->assertSame(1, User::where('email', ConsultationBrowserFixtureService::USER_EMAIL)->count());
        $this->assertSame(1, Patient::where('patient_number', ConsultationBrowserFixtureService::PATIENT_NUMBER)->count());
        $this->assertSame(1, Visit::where('visit_number', ConsultationBrowserFixtureService::VISIT_NUMBER)->count());
        $this->assertSame(1, VisitConsultationRoute::where('visit_id', $first['visit_id'])->count());
        $this->assertSame(1, MedicalRecord::where('visit_id', $first['visit_id'])->count());
        $this->assertSame(1, ServiceCatalog::where('code', 'E2E-CONSULT')->count());
        $this->assertSame(1, ServiceCatalog::where('code', 'E2E-FBC')->count());
        $this->assertSame(1, ServiceCatalog::where('code', 'E2E-DRESSING')->count());
    }

    public function test_consultation_fixture_command_returns_json_and_refuses_non_local_environments(): void
    {
        $this->assertSame(0, Artisan::call('consultation:e2e-fixture', ['--json' => true]));
        $metadata = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame(ConsultationBrowserFixtureService::USER_EMAIL, $metadata['email']);
        $this->assertSame(ConsultationBrowserFixtureService::USER_PASSWORD, $metadata['password']);
        $this->assertArrayHasKey('consultation_url', $metadata);
        $this->assertArrayHasKey('consultation_route_id', $metadata);

        app()->detectEnvironment(fn () => 'production');
        try {
            $this->assertSame(1, Artisan::call('consultation:e2e-fixture', ['--json' => true]));
            $this->assertStringContainsString('only available locally or in testing', Artisan::output());
        } finally {
            app()->detectEnvironment(fn () => 'testing');
        }
    }

    public function test_playwright_smoke_is_enabled_with_fixture_auth_and_core_js_paths(): void
    {
        $spec = file_get_contents(base_path('tests-e2e/tests/consultation-workspace.spec.ts'));
        $fixtureHelper = file_get_contents(base_path('tests-e2e/tests/support/consultation-fixture.ts'));

        $this->assertStringNotContainsString('describe.skip', $spec);
        $this->assertStringContainsString('consultationWorkspaceFixture', $spec);
        $this->assertStringContainsString('loginWithCredentials', $spec);
        $this->assertStringContainsString('consoleErrors', $spec);
        $this->assertStringContainsString('procedureDeptSelect', $spec);
        $this->assertStringContainsString('labRequestForm', $spec);
        $this->assertStringContainsString('add-prescription-item', $spec);
        $this->assertStringContainsString('sectionRefresh.refresh', $spec);
        $this->assertStringContainsString("consultation:e2e-fixture', '--json'", $fixtureHelper);
    }
}
