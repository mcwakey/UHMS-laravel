<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PatientManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->user = User::factory()->create();
        $role = Role::create(['name' => 'Admin']);
        foreach (['patients.view', 'patients.create', 'patients.edit', 'patients.delete'] as $p) {
            $perm = Permission::create(['name' => $p]);
            $role->givePermissionTo($perm);
        }
        $this->user->assignRole($role);
    }

    // ── Index ───────────────────────────────────

    public function test_patient_index_page_loads(): void
    {
        $response = $this->actingAs($this->user)->get(route('admin.patients.index'));
        $response->assertStatus(200);
    }

    public function test_patient_index_shows_patients(): void
    {
        $patient = Patient::factory()->create(['registered_by' => $this->user->id]);

        $response = $this->actingAs($this->user)->get(route('admin.patients.index'));
        $response->assertStatus(200);
        $response->assertSee($patient->first_name);
    }

    // ── Create ──────────────────────────────────

    public function test_patient_create_form_loads(): void
    {
        $response = $this->actingAs($this->user)->get(route('admin.patients.create'));
        $response->assertStatus(200);
    }

    public function test_patient_can_be_created(): void
    {
        $data = [
            'first_name' => 'Kofi',
            'last_name' => 'Mensah',
            'date_of_birth' => '1990-05-15',
            'gender' => 'male',
            'phone' => '0244123456',
            'address' => '123 Main Street, Accra',
        ];

        $response = $this->actingAs($this->user)->post(route('admin.patients.store'), $data);

        $response->assertRedirect();
        $this->assertDatabaseHas('patients', [
            'first_name' => 'Kofi',
            'last_name' => 'Mensah',
        ]);
    }

    public function test_patient_creation_validates_required_fields(): void
    {
        $response = $this->actingAs($this->user)->post(route('admin.patients.store'), []);

        $response->assertSessionHasErrors(['first_name', 'last_name', 'date_of_birth', 'gender']);
    }

    public function test_patient_number_is_auto_generated(): void
    {
        $data = [
            'first_name' => 'Ama',
            'last_name' => 'Darko',
            'date_of_birth' => '1985-01-20',
            'gender' => 'female',
            'phone' => '0201234567',
        ];

        $this->actingAs($this->user)->post(route('admin.patients.store'), $data);

        $patient = Patient::where('first_name', 'Ama')->first();
        $this->assertNotNull($patient);
        $this->assertStringStartsWith('PT', $patient->patient_number);
    }

    // ── Show ────────────────────────────────────

    public function test_patient_detail_page_loads(): void
    {
        $patient = Patient::factory()->create(['registered_by' => $this->user->id]);

        $response = $this->actingAs($this->user)->get(route('admin.patients.show', $patient));
        $response->assertStatus(200);
        $response->assertSee($patient->first_name);
    }

    // ── Update ──────────────────────────────────

    public function test_patient_can_be_updated(): void
    {
        $patient = Patient::factory()->create(['registered_by' => $this->user->id]);

        $response = $this->actingAs($this->user)->put(route('admin.patients.update', $patient), [
            'first_name' => 'Updated',
            'last_name' => $patient->last_name,
            'date_of_birth' => $patient->date_of_birth->format('Y-m-d'),
            'gender' => $patient->gender->value,
            'phone' => $patient->phone,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('patients', [
            'id' => $patient->id,
            'first_name' => 'Updated',
        ]);
    }

    // ── Search ──────────────────────────────────

    public function test_patient_search_works(): void
    {
        Patient::factory()->create([
            'first_name' => 'Kwame',
            'last_name' => 'Asante',
            'registered_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('admin.patients.index', ['search' => 'Kwame']));

        $response->assertStatus(200);
        $response->assertSee('Kwame');
    }
}
