<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->admin = User::factory()->create();
        $role = Role::findOrCreate('Super Admin', 'web');
        foreach (['users.view', 'users.create', 'users.edit', 'users.delete', 'roles.manage'] as $p) {
            $perm = Permission::findOrCreate($p, 'web');
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
        Role::findOrCreate('Nurse', 'web');

        $data = [
            'first_name' => 'Test',
            'last_name' => 'Nurse',
            'email' => 'nurse@test.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'department_id' => $dept->id,
            'role' => 'Nurse',
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
            'first_name' => 'Updated',
            'last_name' => 'Name',
            'email' => $user->email,
            'role' => 'Super Admin',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'first_name' => 'Updated',
            'last_name' => 'Name',
        ]);
    }

    public function test_role_index_loads(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.roles.index'));
        $response->assertStatus(200);
    }
}
