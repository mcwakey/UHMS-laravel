<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    // ── Login Page ──────────────────────────────

    public function test_login_page_is_accessible(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertViewIs('auth.login');
    }

    public function test_authenticated_user_is_redirected_from_login(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $role = Role::create(['name' => 'Admin']);
        $user->assignRole($role);

        $response = $this->actingAs($user)->get('/login');
        $response->assertRedirect();
    }

    // ── Login Attempt ───────────────────────────

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create(['password' => bcrypt('Password123!')]);
        $role = Role::create(['name' => 'Admin']);
        $user->assignRole($role);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'Password123!',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('admin.dashboard'));
    }

    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        $user = User::factory()->create(['password' => bcrypt('Password123!')]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    public function test_login_validates_required_fields(): void
    {
        $response = $this->post('/login', []);
        $response->assertSessionHasErrors(['email', 'password']);
    }

    // ── Role-Based Redirect ─────────────────────

    public function test_doctor_redirected_to_doctor_dashboard(): void
    {
        $user = User::factory()->create(['password' => bcrypt('Password123!')]);
        $role = Role::create(['name' => 'Doctor']);
        $user->assignRole($role);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'Password123!',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('doctor.dashboard'));
    }

    public function test_admin_redirected_to_admin_dashboard(): void
    {
        $user = User::factory()->create(['password' => bcrypt('Password123!')]);
        $role = Role::create(['name' => 'Super Admin']);
        $user->assignRole($role);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'Password123!',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('admin.dashboard'));
    }

    // ── Logout ──────────────────────────────────

    public function test_user_can_logout(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/login');
    }

    // ── Authorization ───────────────────────────

    public function test_guest_cannot_access_admin_routes(): void
    {
        $response = $this->get(route('admin.dashboard'));
        $response->assertRedirect('/login');
    }

    public function test_user_without_permission_gets_forbidden(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $role = Role::create(['name' => 'Receptionist']);
        Permission::create(['name' => 'users.view']);
        $user->assignRole($role);

        $response = $this->actingAs($user)->get(route('admin.users.index'));
        $response->assertStatus(403);
    }

    public function test_user_with_permission_can_access_route(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $role = Role::create(['name' => 'Admin']);
        $perm = Permission::create(['name' => 'patients.view']);
        $role->givePermissionTo($perm);
        $user->assignRole($role);

        $response = $this->actingAs($user)->get(route('admin.patients.index'));
        $response->assertStatus(200);
    }

    // ── Session Security ────────────────────────

    public function test_session_regenerated_on_login(): void
    {
        $user = User::factory()->create(['password' => bcrypt('secret123')]);
        $role = Role::create(['name' => 'Admin']);
        $user->assignRole($role);

        $oldSession = session()->getId();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'secret123',
        ]);

        $this->assertNotEquals($oldSession, session()->getId());
    }
}
