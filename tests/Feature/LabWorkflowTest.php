<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\LabRequest;
use App\Models\LabResult;
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
        // The results-entry route opens with billing acceptance suppressed
        // ($showBillingAcceptance = false), so the "Accept & Bill" card must not
        // render. We assert on the actual control (the accept-selected form/button),
        // not the bare "Bill Selected" label — that label now also appears inside the
        // serialised i18n translation bundle embedded on every page, which is not a
        // visible billing action.
        $response->assertDontSee('id="acceptSelectedForm"', false);
        $response->assertDontSee('name="item_ids[]"', false);
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

    public function test_verified_results_can_be_combined_on_one_printout(): void
    {
        $request = $this->makeLabRequest('INV-PRINT-001', 'completed');
        $first = $request->items()->create(['name' => 'Full Blood Count', 'status' => 'completed']);
        $second = $request->items()->create(['name' => 'Liver Function Test', 'status' => 'completed']);

        foreach ([$first, $second] as $item) {
            LabResult::create([
                'lab_request_item_id' => $item->id,
                'lab_request_id' => $request->id,
                'result_type' => 'parameters',
                'result_value' => $item->name . ' result',
                'is_abnormal' => false,
                'performed_by' => $this->user->id,
                'verified_by' => $this->user->id,
                'performed_at' => now(),
                'verified_at' => now(),
            ]);
        }

        $response = $this->actingAs($this->user)->get(route('admin.lab.results.print-request', [
            'labRequest' => $request,
            'items' => [$first->id, $second->id],
            'layout' => 'compact',
        ]));

        $response->assertOk()
            ->assertSee('Full Blood Count')
            ->assertSee('Liver Function Test')
            ->assertSee('2 results')
            ->assertDontSee('<div class="result-page-break">', false);
    }

    public function test_multi_result_print_can_start_each_result_on_a_new_page(): void
    {
        $request = $this->makeLabRequest('INV-PRINT-002', 'completed');
        $items = collect(['Urinalysis', 'Malaria Test'])->map(function (string $name) use ($request) {
            $item = $request->items()->create(['name' => $name, 'status' => 'completed']);
            LabResult::create([
                'lab_request_item_id' => $item->id,
                'lab_request_id' => $request->id,
                'result_type' => 'parameters',
                'result_value' => 'Normal',
                'is_abnormal' => false,
                'performed_by' => $this->user->id,
                'verified_by' => $this->user->id,
                'performed_at' => now(),
                'verified_at' => now(),
            ]);
            return $item;
        });

        $this->actingAs($this->user)->get(route('admin.lab.results.print-request', [
            'labRequest' => $request,
            'items' => $items->pluck('id')->all(),
            'layout' => 'separate',
        ]))
            ->assertOk()
            ->assertSee('<div class="result-page-break">', false);
    }

    public function test_multi_result_print_rejects_items_from_another_request(): void
    {
        $request = $this->makeLabRequest('INV-PRINT-003', 'completed');
        $otherRequest = $this->makeLabRequest('INV-PRINT-004', 'completed');
        $otherItem = $otherRequest->items()->create(['name' => 'Foreign Result', 'status' => 'completed']);
        LabResult::create([
            'lab_request_item_id' => $otherItem->id,
            'lab_request_id' => $otherRequest->id,
            'result_type' => 'parameters',
            'result_value' => 'Normal',
            'is_abnormal' => false,
            'performed_by' => $this->user->id,
            'verified_by' => $this->user->id,
            'performed_at' => now(),
            'verified_at' => now(),
        ]);

        $this->actingAs($this->user)->get(route('admin.lab.results.print-request', [
            'labRequest' => $request,
            'items' => [$otherItem->id],
        ]))->assertStatus(422);
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
