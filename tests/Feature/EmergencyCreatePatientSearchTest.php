<?php

namespace Tests\Feature;

use App\Models\InsuranceProvider;
use App\Models\InsuranceType;
use App\Models\Patient;
use App\Models\PatientInsurance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * The Emergency Create "Find Existing Patient" must use the same shared AJAX
 * search (select2 → admin.visits.patient-search) as the Visit Create page, and
 * that endpoint must find patients by every documented criterion.
 */
class EmergencyCreatePatientSearchTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->user = User::factory()->create();
        $role = Role::findOrCreate('Emergency Intake', 'web');
        foreach (['emergency.case.create', 'visits.view'] as $p) {
            $role->givePermissionTo(Permission::findOrCreate($p, 'web'));
        }
        $this->user->assignRole($role);
    }

    public function test_emergency_create_page_uses_shared_patient_search(): void
    {
        $response = $this->actingAs($this->user)->get(route('admin.emergency.cases.create'));

        $response->assertOk();
        // Uses the shared partial (markup div + hidden field) + the shared
        // patient-search endpoint. NOTE: assertions avoid '/' and '"' because the
        // test harness returns JSON-escaped HTML (true for visits.create too).
        $response->assertSee('data-patient-search', false);
        $response->assertSee('emergencyPatientSearch', false);
        $response->assertSee('emergencyPatientSearchValue', false);
        $response->assertSee('patient-search', false); // shared endpoint URI segment
        // Still offers the unknown-patient path, and submits as patient_id.
        $response->assertSee('Create temporary emergency patient');
        $response->assertSee('patient_id', false);
        // The old full-page GET reload search input is gone.
        $response->assertDontSee('Search by patient name, number, or phone');
    }

    public function test_shared_search_endpoint_matches_every_criterion(): void
    {
        $type = InsuranceType::create(['name' => 'NHIS', 'code' => 'NHIS', 'is_active' => true]);
        $provider = InsuranceProvider::create([
            'name' => 'NHIA', 'short_name' => 'NHIA', 'code' => 'NHIA', 'type' => 'nhia',
            'insurance_type_id' => $type->id, 'is_active' => true,
        ]);

        $patient = Patient::factory()->create([
            'first_name' => 'Yaa', 'last_name' => 'Asantewaa', 'phone' => '0249876543',
            'ghana_card_number' => 'GHA-72727272-1', 'status' => 'active',
            'registered_by' => $this->user->id,
        ]);
        PatientInsurance::create([
            'patient_id' => $patient->id, 'insurance_provider_id' => $provider->id,
            'membership_number' => 'MEM-555111', 'is_active' => true,
        ]);

        foreach ([
            'Asantewaa',          // name
            '0249876543',         // phone
            'GHA-72727272-1',     // Ghana card
            'MEM-555111',         // insurance membership
            $patient->patient_number, // folder / patient number
        ] as $term) {
            $results = $this->actingAs($this->user)
                ->getJson(route('admin.visits.patient-search', ['q' => $term]))
                ->assertOk()
                ->json();

            $this->assertContains(
                $patient->id,
                collect($results)->pluck('id')->map(fn ($id) => (int) $id)->all(),
                "Patient should be findable by: {$term}"
            );
        }
    }
}
