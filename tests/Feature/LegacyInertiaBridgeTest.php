<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class LegacyInertiaBridgeTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $department = Department::factory()->create();
        $this->user = User::factory()->create(['department_id' => $department->id]);

        Role::findOrCreate('Doctor', 'web');
        $role = Role::findOrCreate('Inertia Bridge Tester', 'web');

        foreach ([
            'appointments.view',
            'consultations.view',
            'consultations.create',
        ] as $permission) {
            $role->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }

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

        preg_match('/<script data-page="app" type="application\/json">(.*?)<\/script>/s', $response->getContent(), $matches);

        $this->assertNotEmpty($matches[1] ?? null);
        $page = json_decode($matches[1], true);

        $this->assertIsArray($page);
        $this->assertSame('', $page['props']['scripts']);
        $this->assertNotEmpty($page['props']['scriptsEncoded']);
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

    public function test_inertia_complaint_submission_redirects_instead_of_returning_plain_json(): void
    {
        $version = $this->inertiaVersion();
        $visit = $this->createVisit();

        $response = $this->actingAs($this->user)
            ->from(route('admin.consultations.show', $visit))
            ->withHeaders([
                'X-Inertia' => 'true',
                'X-Inertia-Version' => $version,
                'X-Requested-With' => 'XMLHttpRequest',
            ])
            ->post(route('admin.consultations.complaints.store', $visit), [
                'description' => 'headache',
            ]);

        $response->assertRedirect(route('admin.consultations.show', $visit));
        $response->assertSessionHas('success', 'Complaint added.');

        $this->assertDatabaseHas('complaints', [
            'description' => 'headache',
        ]);
    }

    public function test_non_inertia_ajax_complaint_submission_still_returns_json(): void
    {
        $visit = $this->createVisit();

        $response = $this->actingAs($this->user)
            ->withHeaders([
                'X-Requested-With' => 'XMLHttpRequest',
            ])
            ->post(route('admin.consultations.complaints.store', $visit), [
                'description' => 'headache',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('complaint.description', 'headache');
    }

    public function test_inertia_diagnosis_submission_redirects_instead_of_returning_plain_json(): void
    {
        $version = $this->inertiaVersion();
        $visit = $this->createVisit();

        $response = $this->actingAs($this->user)
            ->from(route('admin.consultations.show', $visit))
            ->withHeaders([
                'X-Inertia' => 'true',
                'X-Inertia-Version' => $version,
                'X-Requested-With' => 'XMLHttpRequest',
            ])
            ->post(route('admin.consultations.diagnoses.store', $visit), [
                'description' => 'Migraine',
                'type' => 'provisional',
            ]);

        $response->assertRedirect(route('admin.consultations.show', $visit));
        $response->assertSessionHas('success', 'Diagnosis added.');

        $this->assertDatabaseHas('diagnoses', [
            'description' => 'Migraine',
        ]);
    }

    public function test_non_inertia_ajax_diagnosis_submission_still_returns_json(): void
    {
        $visit = $this->createVisit();

        $response = $this->actingAs($this->user)
            ->withHeaders([
                'X-Requested-With' => 'XMLHttpRequest',
            ])
            ->post(route('admin.consultations.diagnoses.store', $visit), [
                'description' => 'Migraine',
                'type' => 'provisional',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('diagnosis.description', 'Migraine');
    }

    public function test_inertia_treatment_submission_redirects_instead_of_returning_plain_json(): void
    {
        $version = $this->inertiaVersion();
        $visit = $this->createVisit();

        $response = $this->actingAs($this->user)
            ->from(route('admin.consultations.show', $visit))
            ->withHeaders([
                'X-Inertia' => 'true',
                'X-Inertia-Version' => $version,
                'X-Requested-With' => 'XMLHttpRequest',
            ])
            ->post(route('admin.consultations.treatments.store', $visit), [
                'type' => 'medication',
                'description' => 'Paracetamol and rest',
            ]);

        $response->assertRedirect(route('admin.consultations.show', $visit));
        $response->assertSessionHas('success', 'Treatment added.');

        $this->assertDatabaseHas('treatments', [
            'description' => 'Paracetamol and rest',
        ]);
    }

    public function test_non_inertia_ajax_treatment_submission_still_returns_json(): void
    {
        $visit = $this->createVisit();

        $response = $this->actingAs($this->user)
            ->withHeaders([
                'X-Requested-With' => 'XMLHttpRequest',
            ])
            ->post(route('admin.consultations.treatments.store', $visit), [
                'type' => 'medication',
                'description' => 'Paracetamol and rest',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('treatment.description', 'Paracetamol and rest');
    }

    private function inertiaVersion(): string
    {
        $response = $this->actingAs($this->user)->get(route('admin.appointments.index'));

        preg_match('/"version":"([^"]+)"/', $response->getContent(), $matches);

        return $matches[1] ?? '';
    }

    private function createVisit(): Visit
    {
        $patient = Patient::factory()->create(['registered_by' => $this->user->id]);

        return Visit::factory()->create([
            'patient_id' => $patient->id,
            'created_by' => $this->user->id,
        ]);
    }
}
