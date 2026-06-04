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
 * Assigning / changing a user's ROLE is a security event (it grants whatever the
 * role can do). It must surface in the global ROLES log with old/new role sets and
 * CRITICAL detection when the role carries critical permissions — and must NEVER
 * carry patient/visit context. End-to-end through the real user routes.
 */
class UserRoleAssignmentSecurityLogTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private const CRITICAL_PERM = 'billing.payment.reverse'; // CRITICAL via PermissionMeta
    private const NORMAL_PERM = 'patients.view';             // LOW

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['users.view', 'users.create', 'users.edit', self::CRITICAL_PERM, self::NORMAL_PERM] as $p) {
            Permission::findOrCreate($p, 'web');
        }

        // Cashier = normal role; Billing Manager = critical role (holds a critical perm).
        Role::findOrCreate('Cashier', 'web')->syncPermissions([self::NORMAL_PERM]);
        Role::findOrCreate('Billing Manager', 'web')->syncPermissions([self::CRITICAL_PERM]);

        // User routes are nested under can:users.view, so view + create/edit are both needed.
        $this->admin = User::factory()->create();
        $this->admin->givePermissionTo(['users.view', 'users.create', 'users.edit']);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->actingAs($this->admin);
    }

    private function event(string $event): ?ActivityLog
    {
        return ActivityLog::query()->where('event', $event)->latest('id')->first();
    }

    private function userPayload(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'newuser' . fake()->unique()->numberBetween(1, 99999) . '@example.test',
            'password' => 'Password123!',
            'role' => 'Cashier',
        ], $overrides);
    }

    public function test_creating_user_with_role_logs_user_roles_updated(): void
    {
        $this->post(route('admin.users.store'), $this->userPayload(['role' => 'Cashier']))->assertRedirect();

        $log = $this->event('USER_ROLES_UPDATED');
        $this->assertNotNull($log);
        $this->assertSame('ROLES', $log->log_name);
        $this->assertSame([], $log->properties['old']['roles']);
        $this->assertSame(['Cashier'], $log->properties['attributes']['roles']);
        $this->assertSame(['Cashier'], $log->properties['metadata']['added_roles']);
        $this->assertNotNull($log->properties['target_user_id']);
        $this->assertSame('WARNING', $log->properties['severity']); // Cashier is not critical
    }

    public function test_updating_user_role_logs_old_and_new_sets(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Cashier');

        $this->put(route('admin.users.update', $user), $this->userPayload([
            'email' => $user->email,
            'role' => 'Billing Manager',
        ]))->assertRedirect();

        $log = $this->event('USER_ROLES_UPDATED');
        $this->assertNotNull($log);
        $this->assertSame(['Cashier'], $log->properties['old']['roles']);
        $this->assertSame(['Billing Manager'], $log->properties['attributes']['roles']);
        $this->assertSame(['Billing Manager'], $log->properties['metadata']['added_roles']);
        $this->assertSame(['Cashier'], $log->properties['metadata']['removed_roles']);
        $this->assertSame((int) $user->id, (int) $log->properties['target_user_id']);
    }

    public function test_critical_role_assignment_logs_critical_event(): void
    {
        $this->post(route('admin.users.store'), $this->userPayload(['role' => 'Billing Manager']))->assertRedirect();

        $critical = $this->event('CRITICAL_ROLE_ASSIGNED');
        $this->assertNotNull($critical);
        $this->assertSame('CRITICAL', $critical->properties['severity']);
        $this->assertContains('Billing Manager', $critical->properties['metadata']['critical_roles']);
        $this->assertContains(self::CRITICAL_PERM, $critical->properties['metadata']['critical_permissions']);
        $this->assertStringContainsString('Billing Manager', $critical->description);

        // Umbrella event escalates to CRITICAL and records the critical-permission trail.
        $updated = $this->event('USER_ROLES_UPDATED');
        $this->assertSame('CRITICAL', $updated->properties['severity']);
        $this->assertContains('Billing Manager', $updated->properties['metadata']['critical_roles_added']);
        $this->assertContains(self::CRITICAL_PERM, $updated->properties['metadata']['critical_permissions_in_added_roles']);
    }

    public function test_critical_role_removal_logs_critical_event(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Billing Manager');

        $this->put(route('admin.users.update', $user), $this->userPayload([
            'email' => $user->email,
            'role' => 'Cashier',
        ]))->assertRedirect();

        $critical = $this->event('CRITICAL_ROLE_REMOVED');
        $this->assertNotNull($critical);
        $this->assertSame('CRITICAL', $critical->properties['severity']);
        $this->assertContains('Billing Manager', $critical->properties['metadata']['critical_roles']);
    }

    public function test_user_role_logs_carry_no_patient_context(): void
    {
        $this->post(route('admin.users.store'), $this->userPayload(['role' => 'Billing Manager']));

        $polluted = ActivityLog::query()
            ->whereIn('event', ['USER_ROLES_UPDATED', 'CRITICAL_ROLE_ASSIGNED', 'CRITICAL_ROLE_REMOVED'])
            ->where(fn ($q) => $q->whereNotNull('patient_id')->orWhereNotNull('visit_id'))
            ->count();

        $this->assertSame(0, $polluted, 'User role security logs must not appear on a patient timeline.');
    }

    public function test_unauthorized_user_cannot_assign_roles_and_nothing_is_logged(): void
    {
        $this->actingAs(User::factory()->create()); // no users.create

        $this->post(route('admin.users.store'), $this->userPayload(['email' => 'blocked@example.test']))
            ->assertForbidden();

        $this->assertNull($this->event('USER_ROLES_UPDATED'));
        $this->assertSame(0, User::where('email', 'blocked@example.test')->count());
    }

    public function test_logs_audit_still_reports_zero_missing_logs(): void
    {
        Artisan::call('logs:audit', ['--json' => true]);
        $report = json_decode(file_get_contents(storage_path('reports/logs-audit-report.json')), true);

        $this->assertSame(0, $report['summary']['MISSING_LOG']);
    }
}
