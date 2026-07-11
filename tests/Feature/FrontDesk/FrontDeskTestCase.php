<?php

namespace Tests\Feature\FrontDesk;

use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Shared scaffolding for Front Desk Operations (Phase 18A) feature tests.
 * Permissions are granted directly to users (no shared role) so each test can
 * assert permission separation independently.
 */
abstract class FrontDeskTestCase extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Department $department;

    /** Every front desk permission — the fully-authorised desk operator. */
    protected const ALL_PERMISSIONS = [
        'front_desk.view',
        'front_desk.dashboard.view',
        'front_desk.visitors.view',
        'front_desk.visitors.create',
        'front_desk.visitors.update',
        'front_desk.visitors.checkout',
        'front_desk.visitors.print_pass',
        'front_desk.calls.view',
        'front_desk.calls.create',
        'front_desk.calls.update',
        'front_desk.couriers.view',
        'front_desk.couriers.create',
        'front_desk.couriers.update',
        'front_desk.couriers.deliver',
        'front_desk.reports.view',
        'front_desk.reports.export',
        // Phase 18C
        'front_desk.calls.followups.view',
        'front_desk.calls.followups.assign',
        'front_desk.calls.followups.complete',
        'front_desk.calls.transfer',
        'front_desk.couriers.workflow.view',
        'front_desk.couriers.dispatch',
        'front_desk.couriers.handover',
        'front_desk.couriers.return',
        // Phase 18E
        'front_desk.handovers.view', 'front_desk.handovers.create', 'front_desk.handovers.update',
        'front_desk.handovers.submit', 'front_desk.handovers.accept', 'front_desk.handovers.cancel',
        'front_desk.lost_found.view', 'front_desk.lost_found.create', 'front_desk.lost_found.update',
        'front_desk.lost_found.claim', 'front_desk.lost_found.release', 'front_desk.lost_found.cancel',
        'front_desk.incidents.view', 'front_desk.incidents.create', 'front_desk.incidents.update',
        'front_desk.incidents.assign', 'front_desk.incidents.escalate', 'front_desk.incidents.resolve',
        'front_desk.incidents.cancel',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->department = Department::factory()->create();
        $this->user = $this->userWith(self::ALL_PERMISSIONS);
    }

    /** Create a user granted exactly the given permissions. */
    protected function userWith(array $permissions): User
    {
        $user = User::factory()->create(['department_id' => $this->department->id]);

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        $user->givePermissionTo($permissions);
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        return $user;
    }
}
