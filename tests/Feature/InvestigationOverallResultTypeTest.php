<?php

namespace Tests\Feature;

use App\Enums\DepartmentType;
use App\Models\Department;
use App\Models\LabRequest;
use App\Models\LabRequestItem;
use App\Models\LabResult;
use App\Models\Patient;
use App\Models\ServiceCatalog;
use App\Models\User;
use App\Models\Visit;
use App\Services\InvestigationResultSummaryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class InvestigationOverallResultTypeTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;   // can configure catalogue + enter results
    private User $viewer;    // can only view results
    private Department $department;

    protected function setUp(): void
    {
        parent::setUp();
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->department = Department::factory()->create([
            'type' => DepartmentType::INVESTIGATION->value,
        ]);

        $managerRole = Role::create(['name' => 'LabManager']);
        foreach (['lab.tests.manage', 'lab.results.view', 'lab.results.create'] as $p) {
            $managerRole->givePermissionTo(Permission::findOrCreate($p, 'web'));
        }
        $this->manager = User::factory()->create(['department_id' => $this->department->id]);
        $this->manager->assignRole($managerRole);

        $viewerRole = Role::create(['name' => 'LabViewer']);
        $viewerRole->givePermissionTo(Permission::findOrCreate('lab.results.view', 'web'));
        $this->viewer = User::factory()->create(['department_id' => $this->department->id]);
        $this->viewer->assignRole($viewerRole);
    }

    /*
    |--------------------------------------------------------------------------
    | Catalogue configuration
    |--------------------------------------------------------------------------
    */

    public function test_catalogue_can_configure_each_overall_result_type(): void
    {
        $service = $this->makeService();

        $this->actingAs($this->manager)
            ->put(route('admin.investigation-catalogue.overall-result.update', $service), [
                'overall_result_type' => 'numeric',
                'overall_result_unit' => 'mmol/L',
                'overall_result_min_value' => 3.9,
                'overall_result_max_value' => 5.6,
            ])->assertRedirect();

        $this->assertDatabaseHas('service_catalog', [
            'id' => $service->id,
            'overall_result_type' => 'numeric',
            'overall_result_unit' => 'mmol/L',
        ]);

        // Positive/negative
        $this->actingAs($this->manager)
            ->put(route('admin.investigation-catalogue.overall-result.update', $service), [
                'overall_result_type' => 'positive_negative',
                'overall_result_positive_label' => 'Reactive',
            ])->assertRedirect();

        $service->refresh();
        $this->assertSame('positive_negative', $service->overall_result_type);
        $this->assertSame('Reactive', $service->overall_result_positive_label);
        // Switching type clears the previously stored numeric config.
        $this->assertNull($service->overall_result_unit);
        $this->assertNull($service->overall_result_min_value);

        // Boolean + free text round out the four supported types.
        $this->actingAs($this->manager)
            ->put(route('admin.investigation-catalogue.overall-result.update', $service), [
                'overall_result_type' => 'boolean',
            ])->assertRedirect();
        $this->assertSame('boolean', $service->fresh()->overall_result_type);

        $this->actingAs($this->manager)
            ->put(route('admin.investigation-catalogue.overall-result.update', $service), [
                'overall_result_type' => 'free_text',
            ])->assertRedirect();
        $this->assertSame('free_text', $service->fresh()->overall_result_type);
    }

    public function test_overall_result_config_rejects_invalid_type_and_bad_range(): void
    {
        $service = $this->makeService();

        $this->actingAs($this->manager)
            ->put(route('admin.investigation-catalogue.overall-result.update', $service), [
                'overall_result_type' => 'banana',
            ])->assertSessionHasErrors('overall_result_type');

        $this->actingAs($this->manager)
            ->put(route('admin.investigation-catalogue.overall-result.update', $service), [
                'overall_result_type' => 'numeric',
                'overall_result_min_value' => 10,
                'overall_result_max_value' => 2, // max < min
            ])->assertSessionHasErrors('overall_result_max_value');
    }

    public function test_unauthorized_users_cannot_configure_result_types(): void
    {
        $service = $this->makeService();

        $this->actingAs($this->viewer)
            ->put(route('admin.investigation-catalogue.overall-result.update', $service), [
                'overall_result_type' => 'numeric',
            ])->assertForbidden();
    }

    /*
    |--------------------------------------------------------------------------
    | Result entry — canonical storage + validation
    |--------------------------------------------------------------------------
    */

    public function test_numeric_result_is_stored_canonically_and_displayed_with_unit(): void
    {
        $service = $this->makeService(['overall_result_type' => 'numeric', 'overall_result_unit' => 'mmol/L']);
        $item = $this->makeAcceptedItem($service);

        $this->actingAs($this->manager)
            ->post(route('admin.lab.results.store', $item), [
                'result_type' => 'parameters',
                'overall_result_type' => 'numeric',
                'overall_result_value' => '5.6',
            ])->assertRedirect();

        $result = $item->fresh()->result;
        $this->assertNotNull($result);
        $this->assertSame('5.6000', (string) $result->overall_result_numeric);
        $this->assertSame('mmol/L', $result->overall_result_unit);
        $this->assertSame('5.6 mmol/L', $result->overallResultDisplay($service));
    }

    public function test_result_can_be_saved_when_the_optional_consumable_row_is_blank(): void
    {
        $service = $this->makeService(['overall_result_type' => 'free_text']);
        $item = $this->makeAcceptedItem($service);

        $this->actingAs($this->manager)
            ->post(route('admin.lab.results.store', $item), [
                'result_type' => 'parameters',
                'overall_result_type' => 'free_text',
                'result_value' => 'Normal',
                'consumables' => [[
                    'product_id' => '',
                    'quantity' => '',
                    'notes' => '',
                ]],
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertNotNull($item->fresh()->result);
        $this->assertSame('Normal', $item->fresh()->result->result_value);
    }

    public function test_partially_entered_consumable_still_requires_quantity(): void
    {
        $service = $this->makeService(['overall_result_type' => 'free_text']);
        $item = $this->makeAcceptedItem($service);

        $this->actingAs($this->manager)
            ->post(route('admin.lab.results.store', $item), [
                'result_type' => 'parameters',
                'overall_result_type' => 'free_text',
                'result_value' => 'Normal',
                'consumables' => [[
                    'product_id' => '1',
                    'quantity' => '',
                ]],
            ])
            ->assertSessionHasErrors('consumables.0.quantity');

        $this->assertNull($item->fresh()->result);
    }

    public function test_unverified_result_can_be_edited(): void
    {
        $service = $this->makeService(['overall_result_type' => 'free_text']);
        $item = $this->makeAcceptedItem($service);

        $this->actingAs($this->manager)->post(route('admin.lab.results.store', $item), [
            'result_type' => 'parameters',
            'overall_result_type' => 'free_text',
            'result_value' => 'Initial result',
            'remarks' => 'Initial remarks',
        ])->assertRedirect();

        $resultId = $item->fresh()->result->id;

        $this->actingAs($this->manager)->post(route('admin.lab.results.store', $item), [
            'result_type' => 'parameters',
            'overall_result_type' => 'free_text',
            'result_value' => 'Corrected result',
            'remarks' => 'Corrected before verification',
            'is_abnormal' => '1',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $result = $item->fresh()->result;
        $this->assertSame($resultId, $result->id);
        $this->assertSame('Corrected result', $result->result_value);
        $this->assertSame('Corrected before verification', $result->remarks);
        $this->assertTrue($result->is_abnormal);
    }

    public function test_verified_result_cannot_be_edited(): void
    {
        $service = $this->makeService(['overall_result_type' => 'free_text']);
        $item = $this->makeAcceptedItem($service);

        $this->actingAs($this->manager)->post(route('admin.lab.results.store', $item), [
            'result_type' => 'parameters',
            'overall_result_type' => 'free_text',
            'result_value' => 'Verified result',
        ])->assertRedirect();

        $item->fresh()->result->update([
            'verified_by' => $this->manager->id,
            'verified_at' => now(),
        ]);

        $this->actingAs($this->manager)->post(route('admin.lab.results.store', $item), [
            'result_type' => 'parameters',
            'overall_result_type' => 'free_text',
            'result_value' => 'Unauthorized correction',
        ])->assertRedirect()->assertSessionHas('error');

        $this->assertSame('Verified result', $item->fresh()->result->result_value);
    }

    public function test_boolean_result_stores_canonical_value_and_displays_translated_label(): void
    {
        $service = $this->makeService(['overall_result_type' => 'boolean', 'overall_result_true_label' => 'Consented']);
        $item = $this->makeAcceptedItem($service);

        $this->actingAs($this->manager)
            ->post(route('admin.lab.results.store', $item), [
                'result_type' => 'parameters',
                'overall_result_type' => 'boolean',
                'overall_result_value' => 'true',
            ])->assertRedirect();

        $result = $item->fresh()->result;
        $this->assertTrue((bool) $result->overall_result_boolean);
        $this->assertSame('true', $result->result_value); // canonical stored
        $this->assertSame('Consented', $result->overallResultDisplay($service)); // custom label displayed
    }

    public function test_positive_negative_result_stores_canonical_value(): void
    {
        $service = $this->makeService(['overall_result_type' => 'positive_negative']);
        $item = $this->makeAcceptedItem($service);

        $this->actingAs($this->manager)
            ->post(route('admin.lab.results.store', $item), [
                'result_type' => 'parameters',
                'overall_result_type' => 'positive_negative',
                'overall_result_value' => 'positive',
            ])->assertRedirect();

        $result = $item->fresh()->result;
        $this->assertSame('positive', $result->overall_result_outcome);
        $this->assertSame('Positive', $result->overallResultDisplay($service));
    }

    public function test_result_entry_validates_based_on_configured_type(): void
    {
        $service = $this->makeService(['overall_result_type' => 'numeric']);
        $item = $this->makeAcceptedItem($service);

        $this->actingAs($this->manager)
            ->post(route('admin.lab.results.store', $item), [
                'result_type' => 'parameters',
                'overall_result_type' => 'numeric',
                'overall_result_value' => 'not-a-number',
            ])->assertSessionHasErrors('overall_result_value');

        $this->actingAs($this->manager)
            ->post(route('admin.lab.results.store', $item), [
                'result_type' => 'parameters',
                'overall_result_type' => 'positive_negative',
                'overall_result_value' => 'maybe',
            ])->assertSessionHasErrors('overall_result_value');
    }

    public function test_old_free_text_results_still_display(): void
    {
        $service = $this->makeService(['overall_result_type' => 'free_text']);
        $item = $this->makeAcceptedItem($service);

        // Simulate a legacy row: only result_value, no canonical overall_* columns.
        $legacy = LabResult::create([
            'lab_request_item_id' => $item->id,
            'lab_request_id' => $item->lab_request_id,
            'result_type' => 'parameters',
            'result_value' => 'No acute abnormality detected',
            'performed_by' => $this->manager->id,
            'performed_at' => now(),
        ]);

        $this->assertFalse($legacy->hasTypedOverallResult());
        $this->assertSame('No acute abnormality detected', $legacy->overallResultDisplay($service));
    }

    /*
    |--------------------------------------------------------------------------
    | Reporting / tally logic
    |--------------------------------------------------------------------------
    */

    public function test_report_tally_counts_positive_negative_correctly(): void
    {
        $service = $this->makeService(['overall_result_type' => 'positive_negative']);
        $this->seedOutcome($service, 'positive', 3);
        $this->seedOutcome($service, 'negative', 1);

        $summary = app(InvestigationResultSummaryService::class)->summarise($service);

        $this->assertSame(3, $summary['positive_count']);
        $this->assertSame(1, $summary['negative_count']);
        $this->assertSame(4, $summary['total_tested']);
        $this->assertSame(75.0, $summary['positive_rate']);
    }

    public function test_report_tally_counts_true_false_correctly(): void
    {
        $service = $this->makeService(['overall_result_type' => 'boolean']);
        $this->seedBoolean($service, true, 2);
        $this->seedBoolean($service, false, 2);

        $summary = app(InvestigationResultSummaryService::class)->summarise($service);

        $this->assertSame(2, $summary['true_count']);
        $this->assertSame(2, $summary['false_count']);
        $this->assertSame(50.0, $summary['true_rate']);
    }

    public function test_report_summary_calculates_numeric_average_min_max_and_abnormal(): void
    {
        $service = $this->makeService([
            'overall_result_type' => 'numeric',
            'overall_result_min_value' => 4,
            'overall_result_max_value' => 6,
        ]);
        $this->seedNumeric($service, [5.0, 7.0, 3.0]); // 7 and 3 are out of [4,6]

        $summary = app(InvestigationResultSummaryService::class)->summarise($service);

        $this->assertSame(3, $summary['count']);
        $this->assertSame(5.0, $summary['average_value']);
        $this->assertSame(3.0, $summary['minimum_value']);
        $this->assertSame(7.0, $summary['maximum_value']);
        $this->assertSame(2, $summary['abnormal_count']);
        $this->assertSame(1, $summary['normal_count']);
    }

    public function test_free_text_summary_counts_completed(): void
    {
        $service = $this->makeService(['overall_result_type' => 'free_text']);
        $item = $this->makeAcceptedItem($service);
        LabResult::create([
            'lab_request_item_id' => $item->id,
            'lab_request_id' => $item->lab_request_id,
            'result_type' => 'parameters',
            'overall_result_type' => 'free_text',
            'overall_result_text' => 'Normal study',
            'result_value' => 'Normal study',
            'performed_by' => $this->manager->id,
            'performed_at' => now(),
        ]);

        $summary = app(InvestigationResultSummaryService::class)->summarise($service);

        $this->assertSame('free_text', $summary['type']);
        $this->assertSame(1, $summary['completed_count']);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    private function makeService(array $attributes = []): ServiceCatalog
    {
        return ServiceCatalog::create(array_merge([
            'name' => 'Test Service ' . uniqid(),
            'code' => 'SVC-' . strtoupper(uniqid()),
            'category' => 'lab',
            'price' => 50,
            'is_active' => true,
            'department_id' => $this->department->id,
            'department_type' => DepartmentType::INVESTIGATION->value,
        ], $attributes));
    }

    private function makeAcceptedItem(ServiceCatalog $service): LabRequestItem
    {
        $patient = Patient::factory()->create(['registered_by' => $this->manager->id]);
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'created_by' => $this->manager->id,
        ]);
        $request = LabRequest::create([
            'request_number' => 'INV-' . strtoupper(uniqid()),
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'requested_by' => $this->manager->id,
            'department_id' => $this->department->id,
            'target_department_id' => $this->department->id,
            'urgency' => 'routine',
            'status' => 'processing',
        ]);

        return $request->items()->create([
            'service_id' => $service->id,
            'name' => $service->name,
            'status' => 'accepted',
            'accepted_at' => now(),
            'accepted_by' => $this->manager->id,
            'billed_at' => now(),
        ]);
    }

    private function seedOutcome(ServiceCatalog $service, string $outcome, int $times): void
    {
        for ($i = 0; $i < $times; $i++) {
            $item = $this->makeAcceptedItem($service);
            LabResult::create([
                'lab_request_item_id' => $item->id,
                'lab_request_id' => $item->lab_request_id,
                'result_type' => 'parameters',
                'overall_result_type' => 'positive_negative',
                'overall_result_outcome' => $outcome,
                'result_value' => $outcome,
                'performed_by' => $this->manager->id,
                'performed_at' => now(),
            ]);
        }
    }

    private function seedBoolean(ServiceCatalog $service, bool $value, int $times): void
    {
        for ($i = 0; $i < $times; $i++) {
            $item = $this->makeAcceptedItem($service);
            LabResult::create([
                'lab_request_item_id' => $item->id,
                'lab_request_id' => $item->lab_request_id,
                'result_type' => 'parameters',
                'overall_result_type' => 'boolean',
                'overall_result_boolean' => $value,
                'result_value' => $value ? 'true' : 'false',
                'performed_by' => $this->manager->id,
                'performed_at' => now(),
            ]);
        }
    }

    private function seedNumeric(ServiceCatalog $service, array $values): void
    {
        foreach ($values as $value) {
            $item = $this->makeAcceptedItem($service);
            LabResult::create([
                'lab_request_item_id' => $item->id,
                'lab_request_id' => $item->lab_request_id,
                'result_type' => 'parameters',
                'overall_result_type' => 'numeric',
                'overall_result_numeric' => $value,
                'result_value' => (string) $value,
                'performed_by' => $this->manager->id,
                'performed_at' => now(),
            ]);
        }
    }
}
