<?php

namespace Tests\Feature\Permissions;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PermissionsAuditCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_command_runs_and_writes_json_report(): void
    {
        // Seed a couple of permissions so the audit has data to chew on.
        Permission::firstOrCreate(['name' => 'patients.view']);
        Permission::firstOrCreate(['name' => 'patients.delete']);

        $reportPath = storage_path('reports/permissions_audit.json');
        if (file_exists($reportPath)) {
            @unlink($reportPath);
        }

        $this->artisan('permissions:audit', ['--json' => true])
            ->assertExitCode(0);

        $this->assertFileExists($reportPath);

        $payload = json_decode(file_get_contents($reportPath), true);
        $this->assertIsArray($payload);
        $this->assertArrayHasKey('totals', $payload);
        $this->assertArrayHasKey('risk_distribution', $payload);
        $this->assertArrayHasKey('referenced_not_in_db', $payload);
        $this->assertArrayHasKey('unprotected_routes', $payload);
        $this->assertArrayHasKey('possible_duplicates', $payload);
        $this->assertGreaterThanOrEqual(2, $payload['totals']['permissions_in_db']);
    }

    public function test_permission_meta_classifies_risk(): void
    {
        $this->assertSame('CRITICAL', \App\Support\PermissionMeta::risk('patients.delete'));
        $this->assertSame('CRITICAL', \App\Support\PermissionMeta::risk('modules.override_disabled'));
        $this->assertSame('LOW', \App\Support\PermissionMeta::risk('patients.view'));
        $this->assertSame('NORMAL', \App\Support\PermissionMeta::risk('patients.create'));
        $this->assertSame('HIGH', \App\Support\PermissionMeta::risk('claims.submit'));
    }

    public function test_patient_insurance_permissions_are_grouped_with_patient_permissions(): void
    {
        $this->assertSame('patients', \App\Support\PermissionMeta::module('patient.insurance.create'));
        $this->assertSame('patients', \App\Support\PermissionMeta::module('patient.insurance.verify'));
    }

    public function test_permission_meta_explains_permissions(): void
    {
        $this->assertSame(
            'Allows the user to mark patient records as deceased.',
            \App\Support\PermissionMeta::description('patients.mark_deceased')
        );
        $this->assertSame(
            'Allows the user to assign theatre teams.',
            \App\Support\PermissionMeta::description('theatre.team.assign')
        );
    }
}
