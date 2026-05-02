<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LegacyInertiaBridgeTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $department = Department::factory()->create();
        $this->user = User::factory()->create(['department_id' => $department->id]);

        Role::findOrCreate('Doctor', 'web');
        $role = Role::findOrCreate('Inertia Bridge Tester', 'web');
        $role->givePermissionTo(Permission::findOrCreate('appointments.view', 'web'));

        $this->user->assignRole($role);
    }

    public function test_inertia_request_to_existing_blade_page_returns_legacy_component(): void
    {
        $version = $this->inertiaVersion();

        $response = $this->actingAs($this->user)
            ->withHeaders([
                'X-Inertia' => 'true',
                'X-Inertia-Version' => $version,
            ])
            ->get(route('admin.appointments.index'));

        $response->assertOk()
            ->assertJsonPath('component', 'Legacy/BladePage')
            ->assertJsonPath('props.title', 'Appointments - '.config('app.name'))
            ->assertJsonPath('url', '/admin/appointments');

        $this->assertStringContainsString('Appointments', $response->json('props.html'));
        $this->assertStringContainsString('notificationBadge', $response->json('props.html'));
        $this->assertStringContainsString('uhmsNotificationInterval', $response->json('props.scripts'));
    }

    public function test_plain_request_to_existing_blade_page_boots_inertia_root(): void
    {
        $response = $this->actingAs($this->user)->get(route('admin.appointments.index'));

        $response->assertOk()
            ->assertSee('id="app"', false)
            ->assertSee('data-page', false)
            ->assertSee('Legacy\/BladePage', false);
    }

    public function test_inertia_request_to_calendar_page_returns_legacy_component(): void
    {
        $version = $this->inertiaVersion();

        $response = $this->actingAs($this->user)
            ->withHeaders([
                'X-Inertia' => 'true',
                'X-Inertia-Version' => $version,
                'X-Requested-With' => 'XMLHttpRequest',
            ])
            ->get(route('admin.appointments.calendar'));

        $response->assertOk()
            ->assertJsonPath('component', 'Legacy/BladePage')
            ->assertJsonPath('props.title', 'Appointment Calendar - '.config('app.name'))
            ->assertJsonPath('url', '/admin/appointments/calendar');

        $this->assertStringContainsString('Appointment Calendar', $response->json('props.html'));
    }

    public function test_non_inertia_ajax_calendar_request_still_returns_json(): void
    {
        $response = $this->actingAs($this->user)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get(route('admin.appointments.calendar'));

        $response->assertOk()->assertJson([]);
    }

    private function inertiaVersion(): string
    {
        $response = $this->actingAs($this->user)->get(route('admin.appointments.index'));

        preg_match('/"version":"([^"]+)"/', $response->getContent(), $matches);

        return $matches[1] ?? '';
    }
}
