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
        $this->assertNotEmpty($patient->patient_number);
        // Patient number should follow the configured pattern (default: UHMS-YEAR-SEQUENCE)
        $this->assertMatchesRegularExpression('/^[A-Z]+-\d{4}-\d+/', $patient->patient_number);
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

    public function test_search_by_phone_works(): void
    {
        Patient::factory()->create([
            'first_name' => 'Yaw',
            'last_name'  => 'Mensah',
            'phone'      => '0201112222',
            'registered_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('admin.patients.index', ['search' => '0201112222']));

        $response->assertStatus(200);
        $response->assertSee('Yaw');
    }

    public function test_search_by_ghana_card_number_works(): void
    {
        Patient::factory()->create([
            'first_name'         => 'Abena',
            'last_name'          => 'Boateng',
            'ghana_card_number'  => 'GHA-123456789-0',
            'registered_by'      => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('admin.patients.index', ['search' => 'GHA-123456789-0']));

        $response->assertStatus(200);
        $response->assertSee('Abena');
    }

    public function test_search_by_insurance_membership_number_works(): void
    {
        $provider = InsuranceProvider::create([
            'name'       => 'NHIA Test',
            'short_name' => 'NHT',
            'type'       => InsuranceType::NHIA,
            'is_active'  => true,
            'is_default' => false,
        ]);
        $tier = InsuranceTier::create([
            'insurance_provider_id' => $provider->id,
            'name'                  => 'Standard',
            'code'                  => 'STD',
            'is_default'            => true,
            'is_active'             => true,
        ]);

        $patient = Patient::factory()->create(['registered_by' => $this->user->id]);
        $patient->insurances()->create([
            'insurance_provider_id' => $provider->id,
            'insurance_tier_id'     => $tier->id,
            'membership_number'     => 'MEM-987654',
            'is_primary'            => true,
            'is_active'             => true,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('admin.patients.index', ['search' => 'MEM-987654']));

        $response->assertStatus(200);
        $response->assertSee($patient->first_name);
    }

    public function test_search_by_emergency_contact_name_works(): void
    {
        $patient = Patient::factory()->create(['registered_by' => $this->user->id]);
        $patient->emergencyContacts()->create([
            'name'       => 'Kofi Asem',
            'phone'      => '0201234567',
            'is_primary' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('admin.patients.index', ['search' => 'Kofi Asem']));

        $response->assertStatus(200);
        $response->assertSee($patient->first_name);
    }

    public function test_search_by_emergency_contact_phone_works(): void
    {
        $patient = Patient::factory()->create(['registered_by' => $this->user->id]);
        $patient->emergencyContacts()->create([
            'name'       => 'Emergency Person',
            'phone'      => '0249998877',
            'is_primary' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('admin.patients.index', ['search' => '0249998877']));

        $response->assertStatus(200);
        $response->assertSee($patient->first_name);
    }

    public function test_gender_filter_not_present_on_patient_list(): void
    {
        $response = $this->actingAs($this->user)->get(route('admin.patients.index'));
        $response->assertStatus(200);
        $response->assertDontSee('name="gender"', false);
    }

    public function test_blood_group_filter_not_present_on_patient_list(): void
    {
        $response = $this->actingAs($this->user)->get(route('admin.patients.index'));
        $response->assertStatus(200);
        $response->assertDontSee('name="blood_group"', false);
    }

    public function test_insurance_provider_filter_works(): void
    {
        $provider = InsuranceProvider::create([
            'name'       => 'Priority Health',
            'short_name' => 'PH',
            'type'       => InsuranceType::PRIVATE,
            'is_active'  => true,
            'is_default' => false,
        ]);
        $tier = InsuranceTier::create([
            'insurance_provider_id' => $provider->id,
            'name'                  => 'Gold',
            'code'                  => 'GOLD',
            'is_default'            => true,
            'is_active'             => true,
        ]);

        $matchingPatient = Patient::factory()->create(['first_name' => 'Insured', 'registered_by' => $this->user->id]);
        $matchingPatient->insurances()->create([
            'insurance_provider_id' => $provider->id,
            'insurance_tier_id'     => $tier->id,
            'is_primary'            => true,
            'is_active'             => true,
        ]);

        $otherPatient = Patient::factory()->create(['first_name' => 'Uninsured', 'registered_by' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->get(route('admin.patients.index', ['insurance_provider_id' => $provider->id]));

        $response->assertStatus(200);
        $response->assertSee('Insured');
        $response->assertDontSee('Uninsured');
    }

    public function test_last_visit_date_range_filter_uses_latest_visit(): void
    {
        $patient = Patient::factory()->create(['registered_by' => $this->user->id]);

        // Two visits; latest is 2025-03-15
        \App\Models\Visit::factory()->create([
            'patient_id' => $patient->id,
            'visit_date' => '2024-11-01',
            'created_by' => $this->user->id,
        ]);
        \App\Models\Visit::factory()->create([
            'patient_id' => $patient->id,
            'visit_date' => '2025-03-15',
            'created_by' => $this->user->id,
        ]);

        // Should appear when filtering by a range covering 2025-03-15
        $response = $this->actingAs($this->user)
            ->get(route('admin.patients.index', [
                'visit_from' => '2025-01-01',
                'visit_to'   => '2025-12-31',
            ]));

        $response->assertStatus(200);
        $response->assertSee($patient->first_name);

        // Should NOT appear when filtering by range before the latest visit
        $response2 = $this->actingAs($this->user)
            ->get(route('admin.patients.index', [
                'visit_from' => '2026-01-01',
                'visit_to'   => '2026-12-31',
            ]));

        $response2->assertStatus(200);
        $response2->assertDontSee($patient->first_name);
    }

    // ── Deceased ─────────────────────────────────────

    public function test_authorized_user_can_mark_patient_deceased(): void
    {
        $perm = \Spatie\Permission\Models\Permission::create(['name' => 'patients.mark_deceased']);
        $this->user->givePermissionTo($perm);

        $patient = Patient::factory()->create([
            'status'       => 'active',
            'is_deceased'  => false,
            'registered_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->from(route('admin.patients.show', $patient))
            ->patch(route('admin.patients.mark-deceased', $patient), [
                'deceased_at'    => '2026-05-20',
                'cause_of_death' => 'Cardiac arrest',
            ]);

        $response->assertRedirect();

        $patient->refresh();
        $this->assertEquals('deceased', $patient->status);
        $this->assertTrue((bool) $patient->is_deceased);
        $this->assertEquals('2026-05-20', $patient->deceased_at->toDateString());
        $this->assertDatabaseHas('patients', [
            'id'     => $patient->id,
            'status' => 'deceased',
        ]);
    }

    public function test_unauthorized_user_cannot_mark_patient_deceased(): void
    {
        $patient = Patient::factory()->create(['registered_by' => $this->user->id]);

        // user does not have patients.mark_deceased permission
        $response = $this->actingAs($this->user)
            ->patch(route('admin.patients.mark-deceased', $patient), [
                'deceased_at' => '2026-05-20',
            ]);

        $response->assertStatus(403);
        $this->assertDatabaseHas('patients', ['id' => $patient->id, 'is_deceased' => false]);
    }

    public function test_deceased_patient_shows_deceased_badge(): void
    {
        $patient = Patient::factory()->create([
            'status'      => 'deceased',
            'is_deceased' => true,
            'deceased_at' => '2026-05-01',
            'registered_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->get(route('admin.patients.index'));
        $response->assertStatus(200);
        $response->assertSee('Deceased');
    }

    public function test_deceased_patient_cannot_start_new_visit(): void
    {
        $deceased = Patient::factory()->create([
            'status'      => 'deceased',
            'is_deceased' => true,
            'deceased_at' => '2026-05-01',
            'registered_by' => $this->user->id,
        ]);

        // Give visit permissions
        $viewPerm  = \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'visits.view']);
        $visitPerm = \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'visits.create']);
        $this->user->givePermissionTo([$viewPerm, $visitPerm]);
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        $this->user = $this->user->fresh();

        $dept = \App\Models\Department::factory()->create();

        $response = $this->actingAs($this->user)
            ->post(route('admin.visits.store'), [
                'patient_id'      => $deceased->id,
                'visit_type'      => 'outpatient',
                'visit_date'      => now()->toDateString(),
                'priority'        => 'normal',
                'chief_complaint' => 'Test',
                'department_id'   => $dept->id,
            ]);

        $response->assertStatus(302); // redirect back with error
        $this->assertDatabaseMissing('visits', ['patient_id' => $deceased->id]);
    }

    public function test_pagination_preserves_filters(): void
    {
        Patient::factory()->count(20)->create(['registered_by' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->get(route('admin.patients.index', ['search' => '', 'status' => 'active', 'page' => 2]));

        $response->assertStatus(200);
        $response->assertSee('status=active', false);
    }
}
