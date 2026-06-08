<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\LabRequest;
use App\Models\LabTest;
use App\Models\LabTestCategory;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LabWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Department $department;

    protected function setUp(): void
    {
        parent::setUp();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $dept = Department::factory()->create();
        $this->department = $dept;
        $this->user = User::factory()->create(['department_id' => $dept->id]);
        $role = Role::create(['name' => 'LabTech']);
        foreach ([
            'lab.requests.view', 'lab.requests.create', 'lab.results.view',
            'lab.results.create', 'lab.tests.manage', 'patients.view', 'visits.view',
        ] as $p) {
            $perm = Permission::create(['name' => $p]);
            $role->givePermissionTo($perm);
        }
        $this->user->assignRole($role);
    }

    public function test_lab_request_index_loads(): void
    {
        $response = $this->actingAs($this->user)->get(route('admin.lab.requests.index'));
        $response->assertStatus(200);
    }

    public function test_lab_test_management_loads(): void
    {
        $response = $this->actingAs($this->user)->get(route('admin.lab.tests.index'));
        $response->assertStatus(200);
    }

    public function test_lab_result_index_loads(): void
    {
        $response = $this->actingAs($this->user)->get(route('admin.lab.results.index'));
        $response->assertStatus(200);
    }

    public function test_result_index_lists_billed_requests_not_pending_requests(): void
    {
        $pending = $this->makeLabRequest('INV-PENDING-001', 'pending');
        $pending->items()->create(['name' => 'Pending malaria test', 'status' => 'pending']);

        $billed = $this->makeLabRequest('INV-BILLED-001', 'processing');
        $billed->items()->create([
            'name' => 'Billed full blood count',
            'status' => 'accepted',
            'accepted_at' => now(),
            'accepted_by' => $this->user->id,
            'billed_at' => now(),
        ]);

        $response = $this->actingAs($this->user)->get(route('admin.lab.results.index'));

        $response->assertStatus(200);
        $response->assertSee('INV-BILLED-001');
        $response->assertDontSee('INV-PENDING-001');
    }

    public function test_billed_request_opens_from_results_route_for_entry(): void
    {
        $request = $this->makeLabRequest('INV-RESULT-001', 'processing');
        $request->items()->create([
            'name' => 'Billed haemoglobin',
            'status' => 'accepted',
            'accepted_at' => now(),
            'accepted_by' => $this->user->id,
            'billed_at' => now(),
        ]);
        $request->items()->create(['name' => 'Unbilled urine R/E', 'status' => 'pending']);

        $response = $this->actingAs($this->user)->get(route('admin.lab.results.show', $request));

        $response->assertStatus(200);
        $response->assertSee('INV-RESULT-001');
        $response->assertSee('Billed haemoglobin');
        $response->assertDontSee('Bill Selected');
    }

    public function test_pending_only_request_redirects_back_to_billing_from_results_route(): void
    {
        $request = $this->makeLabRequest('INV-PENDING-ONLY-001', 'pending');
        $request->items()->create(['name' => 'Pending urine R/E', 'status' => 'pending']);

        $response = $this->actingAs($this->user)->get(route('admin.lab.results.show', $request));

        $response->assertRedirect(route('admin.lab.requests.show', $request));
    }

    public function test_lab_test_can_store_multiple_criteria(): void
    {
        $category = LabTestCategory::create([
            'name' => 'Haematology',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->post(route('admin.lab.tests.store'), [
            'category_id' => $category->id,
            'name' => 'Full Blood Count',
            'code' => 'FBC',
            'price' => 45,
            'criteria' => [
                ['name' => 'Haemoglobin', 'normal_range' => '12-16', 'unit' => 'g/dL'],
                ['name' => 'White Cell Count', 'normal_range' => '4-11', 'unit' => '10^9/L'],
            ],
        ]);

        $test = LabTest::where('code', 'FBC')->first();

        $response->assertRedirect();
        $this->assertNotNull($test);
        $this->assertSame(2, $test->criteria()->count());
        $this->assertDatabaseHas('lab_test_criteria', [
            'lab_test_id' => $test->id,
            'name' => 'Haemoglobin',
            'normal_range' => '12-16',
            'unit' => 'g/dL',
        ]);
    }

    private function makeLabRequest(string $requestNumber, string $status): LabRequest
    {
        $patient = Patient::factory()->create(['registered_by' => $this->user->id]);
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'created_by' => $this->user->id,
        ]);

        return LabRequest::create([
            'request_number' => $requestNumber,
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'requested_by' => $this->user->id,
            'department_id' => $this->department->id,
            'target_department_id' => $this->department->id,
            'clinical_info' => 'Regression fixture',
            'urgency' => 'routine',
            'status' => $status,
        ]);
    }
}
