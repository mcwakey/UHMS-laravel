<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->admin = User::factory()->create();
        $role = Role::create(['name' => 'Super Admin']);
        foreach (['users.view', 'users.create', 'users.edit', 'users.delete', 'roles.manage'] as $p) {
            $perm = Permission::create(['name' => $p]);
            $role->givePermissionTo($perm);
        }
        $this->admin->assignRole($role);
    }

    public function test_user_index_loads(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.users.index'));
        $response->assertStatus(200);
    }

    public function test_user_create_form_loads(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.users.create'));
        $response->assertStatus(200);
    }

    public function test_user_can_be_created(): void
    {
        $dept = Department::factory()->create();
        Role::create(['name' => 'Nurse']);

        $data = [
            'name' => 'Test Nurse',
            'email' => 'nurse@test.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'department_id' => $dept->id,
            'roles' => ['Nurse'],
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.users.store'), $data);
        $response->assertRedirect();
        $this->assertDatabaseHas('users', ['email' => 'nurse@test.com']);
    }

    public function test_user_email_must_be_unique(): void
    {
        User::factory()->create(['email' => 'taken@test.com']);

        $data = [
            'name' => 'Duplicate',
            'email' => 'taken@test.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.users.store'), $data);
        $response->assertSessionHasErrors('email');
    }

    public function test_user_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($this->admin)->put(route('admin.users.update', $user), [
            'name' => 'Updated Name',
            'email' => $user->email,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_role_index_loads(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.roles.index'));
        $response->assertStatus(200);
    }
}
