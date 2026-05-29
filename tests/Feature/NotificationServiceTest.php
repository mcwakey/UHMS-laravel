<?php

namespace Tests\Feature;

use App\Enums\NotificationModule;
use App\Enums\NotificationPriority;
use App\Models\Department;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class NotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected NotificationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->service = app(NotificationService::class);
    }

    public function test_notify_user_creates_a_database_notification(): void
    {
        $user = User::factory()->create();

        $sent = $this->service->notifyUser($user, [
            'title' => 'Hello',
            'message' => 'Test notification',
            'module' => NotificationModule::SYSTEM,
            'priority' => NotificationPriority::HIGH,
            'source_type' => 'unit_test',
            'source_id' => 1,
        ]);

        $this->assertTrue($sent);
        $this->assertSame(1, $user->fresh()->unreadNotifications()->count());

        $row = $user->notifications()->first();
        $this->assertSame('SYSTEM', $row->data['module']);
        $this->assertSame('HIGH', $row->data['priority']);
        $this->assertSame('Hello', $row->data['title']);
    }

    public function test_notify_user_dedupes_within_window(): void
    {
        $user = User::factory()->create();

        $payload = [
            'message' => 'Duplicate me',
            'module' => 'SYSTEM',
            'source_type' => 'dedupe_test',
            'source_id' => 99,
        ];

        $this->assertTrue($this->service->notifyUser($user, $payload));
        $this->assertFalse($this->service->notifyUser($user, $payload));
        $this->assertSame(1, $user->notifications()->count());
    }

    public function test_notify_user_does_not_dedupe_when_window_is_zero(): void
    {
        $user = User::factory()->create();

        $payload = [
            'message' => 'Allow duplicate',
            'source_type' => 'no_dedupe',
            'source_id' => 1,
        ];

        $this->assertTrue($this->service->notifyUser($user, $payload, dedupeMinutes: 0));
        $this->assertTrue($this->service->notifyUser($user, $payload, dedupeMinutes: 0));
        $this->assertSame(2, $user->notifications()->count());
    }

    public function test_notify_role_targets_active_users_with_that_role(): void
    {
        Role::findOrCreate('Notify Role Test', 'web');

        $active = User::factory()->create(['status' => 'active']);
        $active->assignRole('Notify Role Test');

        $inactive = User::factory()->create(['status' => 'inactive']);
        $inactive->assignRole('Notify Role Test');

        $unrelated = User::factory()->create(['status' => 'active']);

        $count = $this->service->notifyRole('Notify Role Test', [
            'message' => 'Role broadcast',
            'source_type' => 'role_test',
            'source_id' => 1,
        ]);

        $this->assertSame(1, $count);
        $this->assertSame(1, $active->fresh()->notifications()->count());
        $this->assertSame(0, $inactive->fresh()->notifications()->count());
        $this->assertSame(0, $unrelated->fresh()->notifications()->count());
    }

    public function test_notify_department_targets_active_department_members(): void
    {
        $dept = Department::factory()->create(['code' => 'NTF', 'type' => 'treatment']);
        $member = User::factory()->create(['status' => 'active', 'department_id' => $dept->id]);
        $outsider = User::factory()->create(['status' => 'active']);

        $count = $this->service->notifyDepartment($dept, [
            'message' => 'Dept broadcast',
            'source_type' => 'dept_test',
            'source_id' => 1,
        ]);

        $this->assertSame(1, $count);
        $this->assertSame(1, $member->fresh()->notifications()->count());
        $this->assertSame(0, $outsider->fresh()->notifications()->count());
    }

    public function test_mark_as_read_and_mark_all_as_read(): void
    {
        $user = User::factory()->create();

        $this->service->notifyUser($user, ['message' => 'one', 'source_type' => 'm', 'source_id' => 1]);
        $this->service->notifyUser($user, ['message' => 'two', 'source_type' => 'm', 'source_id' => 2]);
        $this->service->notifyUser($user, ['message' => 'three', 'source_type' => 'm', 'source_id' => 3]);

        $this->assertSame(3, $this->service->unreadCount($user));

        $first = $user->notifications()->latest()->first();
        $this->assertTrue($this->service->markAsRead($user, $first->id));
        $this->assertSame(2, $this->service->unreadCount($user->fresh()));

        $updated = $this->service->markAllAsRead($user->fresh());
        $this->assertSame(2, $updated);
        $this->assertSame(0, $this->service->unreadCount($user->fresh()));
    }

    public function test_notify_user_handles_null_recipient(): void
    {
        $this->assertFalse($this->service->notifyUser(null, ['message' => 'ignored']));
    }
}
