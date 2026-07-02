<?php

namespace Tests\Feature;

use App\Models\EmergencyContact;
use App\Models\InsuranceProvider;
use App\Models\InsuranceTier;
use App\Models\Patient;
use App\Models\PatientInsurance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PatientPrivacyPhase2RuntimeMaskingTest extends TestCase
{
    use RefreshDatabase;

    protected Patient $patient;
    protected InsuranceProvider $provider;
    protected InsuranceTier $tier;
    protected PatientInsurance $insurance;
    protected EmergencyContact $emergencyContact;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach (array_merge(config('patient_privacy.permissions'), [
            'patients.view',
            'patients.merge.view',
            'visits.view',
        ]) as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $registrar = User::factory()->create();

        $this->patient = Patient::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Mensah',
            'phone' => '0241234567',
            'phone_secondary' => '0271234567',
            'email' => 'john.doe@gmail.com',
            'address' => 'House 12 Airport Residential',
            'digital_address' => 'GA-123-4567',
            'ghana_card_number' => 'GHA123456789',
            'allergies' => 'Peanuts',
            'chronic_conditions' => 'Hypertension',
            'status' => 'active',
            'registered_by' => $registrar->id,
        ]);

        $this->provider = InsuranceProvider::create([
            'name' => 'Acme Health',
            'short_name' => 'ACME',
            'type' => 'private',
            'is_active' => true,
            'is_default' => false,
            'coverage_percentage' => 50,
        ]);

        $this->tier = InsuranceTier::create([
            'insurance_provider_id' => $this->provider->id,
            'name' => 'Standard',
            'code' => 'STD',
            'is_default' => true,
            'is_active' => true,
            'coverage_percentage' => 50,
        ]);

        $this->insurance = PatientInsurance::create([
            'patient_id' => $this->patient->id,
            'insurance_provider_id' => $this->provider->id,
            'insurance_tier_id' => $this->tier->id,
            'member_type' => 'holder',
            'membership_number' => 'ABC123456789',
            'policy_number' => 'POL123456789',
            'ccc_code' => 'CCC123456789',
            'is_primary' => true,
            'is_active' => true,
        ]);

        $this->emergencyContact = EmergencyContact::create([
            'patient_id' => $this->patient->id,
            'name' => 'Emergency Person',
            'phone' => '0551234567',
            'phone_secondary' => '0591234567',
            'relationship' => 'Sibling',
            'is_primary' => true,
        ]);
    }

    public function test_patient_search_json_masks_phone_and_email_without_contact_permission(): void
    {
        $response = $this->actingAs($this->userWith(['visits.view']))
            ->getJson(route('admin.visits.patient-search', ['q' => 'John']));

        $response->assertOk()
            ->assertJsonPath('0.phone', '024****567')
            ->assertJsonPath('0.email', 'jo****@gmail.com');

        $response->assertDontSee('0241234567', false);
        $response->assertDontSee('john.doe@gmail.com', false);
    }

    public function test_patient_search_json_returns_full_contact_with_contact_permission(): void
    {
        $response = $this->actingAs($this->userWith(['visits.view', 'patients.contact.view']))
            ->getJson(route('admin.visits.patient-search', ['q' => 'John']));

        $response->assertOk()
            ->assertJsonPath('0.phone', '0241234567')
            ->assertJsonPath('0.email', 'john.doe@gmail.com');
    }

    public function test_patient_merge_search_json_does_not_leak_raw_phone(): void
    {
        $response = $this->actingAs($this->userWith(['patients.view', 'patients.merge.view']))
            ->getJson(route('admin.patients.merge.search', ['q' => 'John']));

        $response->assertOk()
            ->assertJsonPath('0.phone', '024****567')
            ->assertDontSee('0241234567', false);
    }

    public function test_patient_index_masks_and_reveals_contact_by_permission(): void
    {
        $masked = $this->actingAs($this->userWith(['patients.view']))
            ->get(route('admin.patients.index'));

        $masked->assertOk()
            ->assertSee('024****567')
            ->assertSee('jo****@gmail.com')
            ->assertDontSee('0241234567', false)
            ->assertDontSee('john.doe@gmail.com', false);

        $full = $this->actingAs($this->userWith(['patients.view', 'patients.contact.view']))
            ->get(route('admin.patients.index'));

        $full->assertOk()
            ->assertSee('0241234567')
            ->assertSee('john.doe@gmail.com');
    }

    public function test_patient_profile_masks_sensitive_fields_without_granular_permissions(): void
    {
        $response = $this->actingAs($this->userWith(['patients.view']))
            ->get(route('admin.patients.show', $this->patient));

        $response->assertOk()
            ->assertSee('Hidden')
            ->assertSee('GHA******789')
            ->assertSee('ABC******789')
            ->assertSee('055****567')
            ->assertDontSee('House 12 Airport Residential', false)
            ->assertDontSee('GA-123-4567', false)
            ->assertDontSee('GHA123456789', false)
            ->assertDontSee('ABC123456789', false)
            ->assertDontSee('0551234567', false)
            ->assertDontSee('Peanuts', false)
            ->assertDontSee('Hypertension', false);
    }

    public function test_pii_permission_reveals_level_two_but_not_level_three_clinical_sensitive_fields(): void
    {
        $response = $this->actingAs($this->userWith(['patients.view', 'patients.pii.view']))
            ->get(route('admin.patients.show', $this->patient));

        $response->assertOk()
            ->assertSee('House 12 Airport Residential')
            ->assertSee('GHA123456789')
            ->assertSee('ABC123456789')
            ->assertDontSee('Peanuts', false)
            ->assertDontSee('Hypertension', false);
    }

    public function test_clinical_sensitive_permission_reveals_level_three_fields(): void
    {
        $response = $this->actingAs($this->userWith(['patients.view', 'patients.clinical_sensitive.view']))
            ->get(route('admin.patients.show', $this->patient));

        $response->assertOk()
            ->assertSee('Peanuts')
            ->assertSee('Hypertension');
    }

    public function test_shared_patient_card_masks_contact_fields(): void
    {
        $this->actingAs($this->userWith(['patients.view']));

        $html = Blade::render('<x-patient-card :patient="$patient" />', [
            'patient' => $this->patient,
        ]);

        $this->assertStringContainsString('024****567', $html);
        $this->assertStringNotContainsString('0241234567', $html);
        $this->assertStringNotContainsString('Peanuts', $html);
    }

    private function userWith(array $permissions): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo($permissions);

        return $user->fresh();
    }
}
