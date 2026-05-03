<?php

namespace Tests\Feature;

use App\Enums\DepartmentType;
use App\Enums\ResultType;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DepartmentManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_department_edit_permission_can_save_department_updates(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        /** @var User $user */
        $user = User::factory()->create();
        $role = Role::findOrCreate('Department Editor', 'web');
        foreach (['departments.view', 'departments.edit'] as $permission) {
            $role->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
        $user->assignRole($role);

        $department = Department::factory()->create([
            'name' => 'Old Department',
            'code' => 'OLD',
            'type' => DepartmentType::SUPPORT,
            'result_type' => ResultType::NONE,
        ]);

        $response = $this->actingAs($user)->put(route('admin.departments.update', $department), [
            'name' => 'Updated Department',
            'code' => 'UPD',
            'type' => DepartmentType::CONSULTATION->value,
            'result_type' => ResultType::NONE->value,
            'status' => 'active',
        ]);

        $response->assertRedirect(route('admin.departments.index'));

        $this->assertDatabaseHas('departments', [
            'id' => $department->id,
            'name' => 'Updated Department',
            'code' => 'UPD',
            'type' => DepartmentType::CONSULTATION->value,
        ]);
    }
}