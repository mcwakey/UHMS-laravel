<?php

namespace Tests\Feature\Permissions;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PermissionsExportCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_writes_csv_with_user_role_and_permissions(): void
    {
        Permission::firstOrCreate(['name' => 'patients.view']);
        Permission::firstOrCreate(['name' => 'pharmacy.dispense']);

        $role = Role::firstOrCreate(['name' => 'Export Test Role']);
        $role->givePermissionTo('patients.view');

        $u1 = User::factory()->create(['first_name' => 'Alice', 'last_name' => 'Export', 'email' => 'alice.export@test.com']);
        $u1->assignRole($role);
        $u1->givePermissionTo('pharmacy.dispense');

        $output = storage_path('reports/test_export_' . uniqid() . '.csv');
        $this->artisan('permissions:export', ['--output' => $output])
            ->assertExitCode(0);

        $this->assertFileExists($output);
        $contents = file_get_contents($output);
        $this->assertStringContainsString('alice.export@test.com', $contents);
        $this->assertStringContainsString('Export Test Role', $contents);
        $this->assertStringContainsString('pharmacy.dispense', $contents);
        $this->assertStringContainsString('patients.view', $contents);

        @unlink($output);
    }
}
