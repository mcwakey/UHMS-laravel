<?php

namespace Tests\Feature\Permissions;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PermissionsAuditStrictTest extends TestCase
{
    use RefreshDatabase;

    public function test_strict_mode_returns_failure_when_drift_detected(): void
    {
        // Trigger drift: a route in admin/* that does not have can: middleware.
        // We rely on the bootstrap auth/profile route that exists in routes/web.php.
        Permission::firstOrCreate(['name' => 'patients.view']);

        // Without --strict the command exits 0 even with drift.
        $this->artisan('permissions:audit')->assertExitCode(0);

        // The fixture project has notifications/profile mutation routes without
        // can: — strict mode must surface them as failures.
        $this->artisan('permissions:audit', ['--strict' => true])
            ->assertExitCode(1);
    }
}
