<?php

namespace Tests\Feature\Permissions;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserPermissionOverrideTest extends TestCase
{
    use RefreshDatabase;

    public function test_assign_admin_can_grant_direct_permissions(): void
    {
        Permission::firstOrCreate(['name' => 'permissions.assign']);
        Permission::firstOrCreate(['name' => 'pharmacy.dispense']);

        $admin = User::factory()->create();
        $admin->givePermissionTo('permissions.assign');

        $target = User::factory()->create();

        $response = $this->actingAs($admin)
            ->put(route('admin.users.permissions.update', $target), [
                'permissions' => ['pharmacy.dispense'],
                'reason'      => 'Covering for absent pharmacist 2025-11-15',
            ]);

        $response->assertRedirect(route('admin.users.permissions.edit', $target));
        $target->refresh();
        $this->assertTrue($target->hasDirectPermission('pharmacy.dispense'));
    }

    public function test_user_without_assign_permission_is_forbidden(): void
    {
        Permission::firstOrCreate(['name' => 'permissions.assign']);
        Permission::firstOrCreate(['name' => 'pharmacy.dispense']);

        $caller = User::factory()->create();   // no permissions.assign
        $target = User::factory()->create();

        $response = $this->actingAs($caller)
            ->put(route('admin.users.permissions.update', $target), [
                'permissions' => ['pharmacy.dispense'],
                'reason'      => 'unauthorised',
            ]);

        $response->assertForbidden();
        $this->assertFalse($target->fresh()->hasDirectPermission('pharmacy.dispense'));
    }

    public function test_role_inherited_permissions_are_not_altered_by_direct_sync(): void
    {
        Permission::firstOrCreate(['name' => 'permissions.assign']);
        Permission::firstOrCreate(['name' => 'patients.view']);
        Permission::firstOrCreate(['name' => 'pharmacy.dispense']);

        $role = Role::firstOrCreate(['name' => 'Test Inherit Role']);
        $role->givePermissionTo('patients.view');

        $admin = User::factory()->create();
        $admin->givePermissionTo('permissions.assign');

        $target = User::factory()->create();
        $target->assignRole($role);

        $this->actingAs($admin)
            ->put(route('admin.users.permissions.update', $target), [
                'permissions' => ['pharmacy.dispense'],
                'reason'      => 'temporary',
            ])->assertRedirect();

        $target->refresh();
        $this->assertTrue($target->can('patients.view'),     'Role-inherited perm must remain.');
        $this->assertTrue($target->can('pharmacy.dispense'), 'Direct perm must be granted.');
        $this->assertFalse($target->hasDirectPermission('patients.view'), 'patients.view must remain inherited only.');
    }
}
