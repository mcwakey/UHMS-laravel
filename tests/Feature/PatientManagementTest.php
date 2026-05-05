<?php

namespace Tests\Feature;

use App\Enums\InsuranceType;
use App\Models\InsuranceProvider;
use App\Models\InsuranceTier;
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
        $response->assertSee('Register New Patient');
        $response->assertSee('Insurance Type');
        $response->assertSee('Provider');
        $response->assertSee('Tier');
        $response->assertSee('insurances[0][type]', false);
        $response->assertSee('insurances[0][provider_id]', false);
        $response->assertSee('insurances[0][insurance_tier_id]', false);
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

    public function test_patient_registration_can_add_public_insurance_with_selected_tier(): void
    {
        $provider = InsuranceProvider::create([
            'name' => 'Public Health Plan',
            'short_name' => 'PHP',
            'type' => InsuranceType::NHIA,
            'is_active' => true,
            'is_default' => false,
        ]);

        $tier = InsuranceTier::create([
            'insurance_provider_id' => $provider->id,
            'name' => 'Standard',
            'code' => 'STD',
            'is_default' => true,
            'is_active' => true,
            'coverage_percentage' => 100,
        ]);

        $response = $this->actingAs($this->user)->post(route('admin.patients.store'), [
            'first_name' => 'Akua',
            'last_name' => 'Boateng',
            'date_of_birth' => '1992-08-10',
            'gender' => 'female',
            'phone' => '0244000000',
            'insurances' => [[
                'type' => InsuranceType::NHIA->value,
                'provider_id' => $provider->id,
                'insurance_tier_id' => $tier->id,
                'membership_number' => 'INS-998877',
                'expiry_date' => now()->addYear()->toDateString(),
            ]],
        ]);

        $response->assertRedirect();

        $patient = Patient::where('first_name', 'Akua')->firstOrFail();
        $this->assertDatabaseHas('patient_insurances', [
            'patient_id' => $patient->id,
            'insurance_provider_id' => $provider->id,
            'insurance_tier_id' => $tier->id,
            'member_type' => 'holder',
            'membership_number' => 'INS-998877',
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

    public function test_patient_can_add_insurance_without_explicit_tier_selection(): void
    {
        $patient = Patient::factory()->create(['registered_by' => $this->user->id]);

        $provider = InsuranceProvider::create([
            'name' => 'Community Health Plan',
            'short_name' => 'CHP',
            'type' => InsuranceType::NHIA,
            'is_active' => true,
            'is_default' => false,
        ]);

        $tier = InsuranceTier::create([
            'insurance_provider_id' => $provider->id,
            'name' => 'Standard',
            'code' => 'STD',
            'is_default' => true,
            'is_active' => true,
            'coverage_percentage' => 100,
        ]);

        $response = $this->actingAs($this->user)
            ->from(route('admin.patients.show', $patient))
            ->post(route('admin.patients.insurances.store', $patient), [
                'insurance_provider_id' => $provider->id,
                'member_type' => 'holder',
                'membership_number' => 'INS-12345',
                // Simulates the real UI case where the tier select is not submitted.
                'insurance_tier_id' => null,
            ]);

        $response->assertRedirect(route('admin.patients.show', $patient));
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('patient_insurances', [
            'patient_id' => $patient->id,
            'insurance_provider_id' => $provider->id,
            'insurance_tier_id' => $tier->id,
            'membership_number' => 'INS-12345',
            'member_type' => 'holder',
        ]);
    }

    public function test_insurance_provider_dropdown_filters_by_type_and_excludes_cash_default(): void
    {
        InsuranceProvider::create([
            'name' => 'Cash & Carry',
            'short_name' => 'CASH',
            'type' => InsuranceType::PRIVATE,
            'is_active' => true,
            'is_default' => true,
        ]);

        $publicProvider = InsuranceProvider::create([
            'name' => 'Public Health Plan',
            'short_name' => 'PHP',
            'type' => InsuranceType::NHIA,
            'is_active' => true,
            'is_default' => false,
        ]);

        InsuranceProvider::create([
            'name' => 'Priority Insurance',
            'short_name' => 'PRIORITY',
            'type' => InsuranceType::PRIVATE,
            'is_active' => true,
            'is_default' => false,
        ]);

        $response = $this->actingAs($this->user)->getJson(route('admin.insurance-providers.by-type', [
            'type' => InsuranceType::NHIA->value,
        ]));

        $response->assertOk()
            ->assertJsonCount(1)
            ->assertJsonFragment(['id' => $publicProvider->id, 'name' => 'Public Health Plan'])
            ->assertJsonMissing(['name' => 'Priority Insurance'])
            ->assertJsonMissing(['name' => 'Cash & Carry']);
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
