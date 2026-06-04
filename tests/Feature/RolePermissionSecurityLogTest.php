<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Role/permission mutations are SECURITY events: they must surface in the global
 * ROLES/PERMISSIONS log (with old/new permission sets + CRITICAL detection) and
 * must NEVER carry patient/visit context. End-to-end through the real routes so
 * authorization is exercised too.
 */
class RolePermissionSecurityLogTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    // patients.view → LOW, billing.payment.record → NORMAL, billing.payment.reverse → CRITICAL
    private const NORMAL_A = 'patients.view';
    private const NORMAL_B = 'billing.payment.record';
    private const CRITICAL = 'billing.payment.reverse';

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['roles.manage', 'permissions.assign', 'permissions.assign_critical',
                  self::NORMAL_A, self::NORMAL_B, self::CRITICAL] as $p) {
            Permission::findOrCreate($p, 'web');
        }

        $this->admin = User::factory()->create();
        $this->admin->givePermissionTo(['roles.manage', 'permissions.assign', 'permissions.assign_critical']);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->actingAs($this->admin);
    }

    private function event(string $event): ?ActivityLog
    {
        return ActivityLog::query()->where('event', $event)->latest('id')->first();
    }

    private function securityLogs(): \Illuminate\Support\Collection
    {
        return ActivityLog::query()->whereIn('log_name', ['ROLES', 'PERMISSIONS'])->get();
    }

    public function test_role_create_logs_role_created(): void
    {
        $this->post(route('admin.roles.store'), ['name' => 'Cashier'])->assertRedirect();

        $log = $this->event('ROLE_CREATED');
        $this->assertNotNull($log);
        $this->assertSame('ROLES', $log->log_name);
        $this->assertSame('INFO', $log->properties['severity']);
        $this->assertSame('Cashier', $log->properties['metadata']['role_name']);
        $this->assertStringContainsString('Cashier', $log->description);
        $this->assertSame($this->admin->id, (int) $log->causer_id);
    }

    public function test_role_update_logs_old_and_new(): void
    {
        $role = Role::create(['name' => 'Billing Clerk']);

        $this->put(route('admin.roles.update', $role), ['name' => 'Billing Manager'])->assertRedirect();

        $log = $this->event('ROLE_UPDATED');
        $this->assertNotNull($log);
        $this->assertSame('WARNING', $log->properties['severity']);
        $this->assertSame('Billing Clerk', $log->properties['old']['name']);
        $this->assertSame('Billing Manager', $log->properties['attributes']['name']);
    }

    public function test_role_delete_logs_role_deleted(): void
    {
        $role = Role::create(['name' => 'Temporary Clerk']);

        $this->delete(route('admin.roles.destroy', $role))->assertRedirect();

        $log = $this->event('ROLE_DELETED');
        $this->assertNotNull($log);
        $this->assertSame('ROLES', $log->log_name);
        $this->assertSame('Temporary Clerk', $log->properties['metadata']['role_name']);
    }

    public function test_permission_sync_captures_added_and_removed(): void
    {
        $role = Role::create(['name' => 'Cashier']);
        $role->syncPermissions([self::NORMAL_A]);

        $this->put(route('admin.roles.permissions.update', $role), [
            'permissions' => [self::NORMAL_A, self::NORMAL_B], // add NORMAL_B, keep NORMAL_A
        ])->assertRedirect();

        $log = $this->event('ROLE_PERMISSIONS_UPDATED');
        $this->assertNotNull($log);
        $this->assertSame('PERMISSIONS', $log->log_name);
        $this->assertSame([self::NORMAL_B], $log->properties['metadata']['added_permissions']);
        $this->assertSame([], $log->properties['metadata']['removed_permissions']);
        $this->assertContains(self::NORMAL_A, $log->properties['attributes']['permissions']);
        $this->assertContains(self::NORMAL_B, $log->properties['attributes']['permissions']);
        // No critical involved → WARNING, not CRITICAL.
        $this->assertSame('WARNING', $log->properties['severity']);
    }

    public function test_critical_permission_assignment_logs_critical_event(): void
    {
        $role = Role::create(['name' => 'Billing Manager']);

        $this->put(route('admin.roles.permissions.update', $role), [
            'permissions' => [self::CRITICAL],
        ])->assertRedirect();

        $critical = $this->event('CRITICAL_PERMISSION_ASSIGNED');
        $this->assertNotNull($critical);
        $this->assertSame('CRITICAL', $critical->properties['severity']);
        $this->assertContains(self::CRITICAL, $critical->properties['metadata']['critical_permissions']);
        $this->assertStringContainsString(self::CRITICAL, $critical->description);

        // The umbrella event is escalated to CRITICAL too.
        $updated = $this->event('ROLE_PERMISSIONS_UPDATED');
        $this->assertSame('CRITICAL', $updated->properties['severity']);
        $this->assertContains(self::CRITICAL, $updated->properties['metadata']['critical_permissions_added']);
    }

    public function test_critical_permission_removal_logs_critical_event(): void
    {
        $role = Role::create(['name' => 'Cashier']);
        $role->syncPermissions([self::CRITICAL]);

        $this->put(route('admin.roles.permissions.update', $role), ['permissions' => []])->assertRedirect();

        $critical = $this->event('CRITICAL_PERMISSION_REMOVED');
        $this->assertNotNull($critical);
        $this->assertSame('CRITICAL', $critical->properties['severity']);
        $this->assertContains(self::CRITICAL, $critical->properties['metadata']['critical_permissions']);
    }

    public function test_user_direct_permissions_are_logged(): void
    {
        $target = User::factory()->create(['first_name' => 'Jane', 'last_name' => 'Doe']);

        $this->put(route('admin.users.permissions.update', $target), [
            'permissions' => [self::NORMAL_A],
            'reason' => 'Temporary cover for records desk',
        ])->assertRedirect();

        $log = $this->event('USER_PERMISSIONS_UPDATED');
        $this->assertNotNull($log);
        $this->assertSame('PERMISSIONS', $log->log_name);
        $this->assertSame($target->id, (int) $log->properties['target_user_id']);
        $this->assertSame('Temporary cover for records desk', $log->properties['reason']);
        $this->assertSame([self::NORMAL_A], $log->properties['metadata']['added_permissions']);
    }

    public function test_security_logs_never_carry_patient_context(): void
    {
        $role = Role::create(['name' => 'Cashier']);
        $this->post(route('admin.roles.store'), ['name' => 'Clerk']);
        $this->put(route('admin.roles.permissions.update', $role), ['permissions' => [self::CRITICAL]]);

        $polluted = $this->securityLogs()->filter(
            fn ($l) => $l->patient_id !== null || $l->visit_id !== null
        );
        $this->assertCount(0, $polluted, 'Role/permission security logs must not appear on a patient timeline.');
    }

    public function test_unauthorized_user_cannot_manage_roles_and_nothing_is_logged(): void
    {
        $this->actingAs(User::factory()->create()); // no roles.manage

        $this->post(route('admin.roles.store'), ['name' => 'Sneaky'])->assertForbidden();

        $this->assertNull($this->event('ROLE_CREATED'));
        $this->assertSame(0, Role::where('name', 'Sneaky')->count());
    }

    public function test_critical_change_requires_assign_critical_permission(): void
    {
        // Has roles.manage but NOT permissions.assign_critical.
        $weak = User::factory()->create();
        $weak->givePermissionTo('roles.manage');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->actingAs($weak);

        $role = Role::create(['name' => 'Cashier']);

        $this->put(route('admin.roles.permissions.update', $role), [
            'permissions' => [self::CRITICAL],
        ])->assertForbidden();

        $this->assertNull($this->event('CRITICAL_PERMISSION_ASSIGNED'));
        $this->assertSame(0, $role->fresh()->permissions->count());
    }

    public function test_logs_audit_no_longer_flags_role_controller(): void
    {
        Artisan::call('logs:audit', ['--json' => true]);
        $report = json_decode(file_get_contents(storage_path('reports/logs-audit-report.json')), true);

        $role = collect($report['findings'])->firstWhere('controller', 'app/Http/Controllers/Admin/RoleController.php');
        $this->assertNotNull($role);
        $this->assertSame('SERVICE_FUNNEL_COVERED', $role['classification']);
        $this->assertSame(0, $report['summary']['MISSING_LOG']);
    }
}
