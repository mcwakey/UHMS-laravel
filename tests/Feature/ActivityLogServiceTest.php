<?php

namespace Tests\Feature;

use App\Enums\LogModule;
use App\Enums\LogSeverity;
use App\Models\Patient;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ActivityLogServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ActivityLogService $logger;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->logger = app(ActivityLogService::class);
    }

    public function test_log_writes_module_action_severity_to_properties(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->logger->log(LogModule::SETTINGS, 'UPDATED', [
            'description' => 'Changed invoice tax rate',
            'severity' => LogSeverity::NOTICE,
            'metadata' => ['field' => 'tax_rate'],
        ]);

        $row = Activity::where('log_name', LogModule::SETTINGS->value)->orderByDesc('id')->first();
        $this->assertNotNull($row);
        $this->assertSame(LogModule::SETTINGS->value, $row->log_name);
        $this->assertSame('UPDATED', $row->event);
        $this->assertSame(LogModule::SETTINGS->value, $row->properties['module']);
        $this->assertSame(LogSeverity::NOTICE->value, $row->properties['severity']);
        $this->assertSame($user->id, $row->causer_id);
        $this->assertArrayHasKey('metadata', $row->properties->toArray());
    }

    public function test_log_updated_captures_old_and_new_values_diff_only(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $patient = Patient::factory()->create();

        $this->logger->logUpdated(
            $patient,
            LogModule::PATIENTS,
            ['first_name' => 'Old', 'last_name' => 'Same'],
            ['first_name' => 'New', 'last_name' => 'Same'],
            reason: 'Spelling correction',
        );

        $row = Activity::where('log_name', LogModule::PATIENTS->value)->where('event', 'UPDATED')->orderByDesc('id')->first();
        $this->assertNotNull($row);
        $this->assertSame('UPDATED', $row->event);
        $this->assertSame('Spelling correction', $row->properties['reason']);
        $this->assertSame(['first_name' => 'Old'], $row->properties['old']);
        $this->assertSame(['first_name' => 'New'], $row->properties['attributes']);
    }

    public function test_log_updated_skips_when_no_change(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $patient = Patient::factory()->create();
        $before = Activity::count();

        $this->logger->logUpdated($patient, LogModule::PATIENTS, ['a' => 1], ['a' => 1]);

        $this->assertSame($before, Activity::count());
    }

    public function test_sanitise_masks_sensitive_fields(): void
    {
        $sanitised = $this->logger->sanitise([
            'password' => 'secret',
            'token' => 'abc',
            'first_name' => 'Jane',
            'nested' => ['api_key' => 'xx', 'safe' => 'ok'],
        ]);

        $this->assertSame('***MASKED***', $sanitised['password']);
        $this->assertSame('***MASKED***', $sanitised['token']);
        $this->assertSame('Jane', $sanitised['first_name']);
        $this->assertSame('***MASKED***', $sanitised['nested']['api_key']);
        $this->assertSame('ok', $sanitised['nested']['safe']);
    }

    public function test_log_security_writes_auth_module_with_security_severity(): void
    {
        $this->logger->logSecurity('FAILED_LOGIN', [
            'metadata' => ['email' => 'attacker@example.com'],
            'severity' => LogSeverity::WARNING,
        ]);

        $row = Activity::where('log_name', LogModule::AUTH->value)->orderByDesc('id')->first();
        $this->assertNotNull($row);
        $this->assertSame(LogModule::AUTH->value, $row->log_name);
        $this->assertSame('FAILED_LOGIN', $row->event);
        $this->assertSame(LogSeverity::WARNING->value, $row->properties['severity']);
    }

    public function test_log_index_requires_logs_view_permission(): void
    {
        Permission::findOrCreate('logs.view', 'web');
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/admin/logs')
            ->assertForbidden();

        $user->givePermissionTo('logs.view');
        $this->actingAs($user->fresh())
            ->get('/admin/logs')
            ->assertOk();
    }

    public function test_log_export_requires_logs_export_permission(): void
    {
        Permission::findOrCreate('logs.view', 'web');
        Permission::findOrCreate('logs.export', 'web');

        $user = User::factory()->create();
        $user->givePermissionTo('logs.view');

        $this->actingAs($user)
            ->get('/admin/logs/export')
            ->assertForbidden();

        $user->givePermissionTo('logs.export');
        $response = $this->actingAs($user->fresh())->get('/admin/logs/export');
        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('content-type'));
    }
}
