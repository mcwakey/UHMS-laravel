<?php

namespace Tests\Feature\Lab;

use App\Enums\SampleStatus;
use App\Models\Department;
use App\Models\LabRequest;
use App\Models\LabRequestItem;
use App\Models\LabTest;
use App\Models\LabTestCategory;
use App\Models\Patient;
use App\Models\Sample;
use App\Models\User;
use App\Models\Visit;
use App\Services\LabService;
use App\Services\SampleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SampleManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Patient $patient;
    private Visit $visit;

    protected function setUp(): void
    {
        parent::setUp();
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $dept = Department::factory()->create();
        $this->user = User::factory()->create(['department_id' => $dept->id]);

        $role = Role::create(['name' => 'Lab Technician']);
        foreach ([
            'lab.samples.view', 'lab.samples.collect', 'lab.samples.receive', 'lab.samples.manage',
            'lab.results.view', 'lab.results.create',
        ] as $p) {
            $role->givePermissionTo(Permission::create(['name' => $p]));
        }
        $this->user->assignRole($role);

        $this->patient = Patient::factory()->create(['registered_by' => $this->user->id]);
        $this->visit = Visit::factory()->create([
            'patient_id' => $this->patient->id,
            'current_department_id' => $dept->id,
            'created_by' => $this->user->id,
        ]);
    }

    private function makeRequestWithItems(): LabRequest
    {
        $category = LabTestCategory::create(['name' => 'Haematology', 'is_active' => true]);
        $fbc = LabTest::create(['category_id' => $category->id, 'name' => 'Full Blood Count', 'code' => 'FBC', 'default_specimen_type' => 'whole_blood', 'is_active' => true]);
        $ua  = LabTest::create(['category_id' => $category->id, 'name' => 'Urinalysis', 'code' => 'UA', 'default_specimen_type' => 'urine', 'is_active' => true]);
        $esr = LabTest::create(['category_id' => $category->id, 'name' => 'ESR', 'code' => 'ESR', 'default_specimen_type' => 'whole_blood', 'is_active' => true]);

        $request = LabRequest::create([
            'request_number' => LabRequest::generateRequestNumber(),
            'visit_id' => $this->visit->id,
            'patient_id' => $this->patient->id,
            'requested_by' => $this->user->id,
            'status' => 'processing',
        ]);

        foreach ([$fbc, $ua, $esr] as $test) {
            $request->items()->create(['lab_test_id' => $test->id, 'status' => 'accepted']);
        }

        return $request->load('items.labTest');
    }

    public function test_generate_creates_one_sample_per_specimen_type_and_links_items(): void
    {
        $this->actingAs($this->user);
        $request = $this->makeRequestWithItems();

        $samples = app(SampleService::class)->generateForRequest($request);

        // whole_blood (FBC + ESR) and urine (UA) → two samples.
        $this->assertCount(2, $samples);
        $this->assertEqualsCanonicalizing(
            ['whole_blood', 'urine'],
            $samples->pluck('specimen_type')->all()
        );

        $blood = $samples->firstWhere('specimen_type', 'whole_blood');
        $this->assertEquals(2, $blood->items()->count());
        $this->assertEquals(SampleStatus::PENDING, $blood->fresh()->status_enum);
        // Barcode defaults to the sample number for analyzer matching.
        $this->assertEquals($blood->sample_number, $blood->barcode);

        // Every item is now linked to a sample.
        $this->assertSame(0, $request->items()->whereNull('sample_id')->count());
    }

    public function test_generate_is_idempotent_and_only_fills_gaps(): void
    {
        $this->actingAs($this->user);
        $request = $this->makeRequestWithItems();
        $service = app(SampleService::class);

        $service->generateForRequest($request);
        $service->generateForRequest($request->fresh('items'));

        $this->assertEquals(2, Sample::where('lab_request_id', $request->id)->count());
    }

    public function test_collect_then_receive_lifecycle(): void
    {
        $this->actingAs($this->user);
        $request = $this->makeRequestWithItems();
        $sample = app(SampleService::class)->generateForRequest($request)->first();

        $this->patch(route('admin.lab.samples.collect', $sample), [
            'barcode' => 'BC-123', 'container' => 'EDTA',
        ])->assertRedirect();

        $sample->refresh();
        $this->assertEquals(SampleStatus::COLLECTED, $sample->status_enum);
        $this->assertEquals('BC-123', $sample->barcode);
        $this->assertEquals($this->user->id, $sample->collected_by);

        $this->patch(route('admin.lab.samples.receive', $sample))->assertRedirect();

        $sample->refresh();
        $this->assertEquals(SampleStatus::RECEIVED, $sample->status_enum);
        $this->assertTrue($sample->isReceived());
    }

    public function test_receive_requires_collected_status(): void
    {
        $this->actingAs($this->user);
        $request = $this->makeRequestWithItems();
        $sample = app(SampleService::class)->generateForRequest($request)->first();

        // Still pending — cannot receive directly.
        $this->expectException(\RuntimeException::class);
        app(SampleService::class)->receive($sample);
    }

    public function test_result_entry_is_blocked_until_sample_received(): void
    {
        $this->actingAs($this->user);
        $request = $this->makeRequestWithItems();
        $service = app(SampleService::class);
        $service->generateForRequest($request);

        $item = $request->items()->whereNotNull('sample_id')->first();
        $lab = app(LabService::class);

        try {
            $lab->enterResult($item->fresh('sample'), ['result_type' => 'parameters', 'result_value' => '5.0']);
            $this->fail('Result entry should be blocked while the sample is unreceived.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('received', strtolower($e->getMessage()));
        }

        // Collect + receive the item's sample, then result entry succeeds.
        $sample = $item->fresh('sample')->sample;
        $service->collect($sample);
        $service->receive($sample->fresh());

        $result = $lab->enterResult($item->fresh('sample'), ['result_type' => 'parameters', 'result_value' => '5.0']);
        $this->assertEquals('5.0', $result->result_value);
    }

    public function test_reject_records_reason_and_blocks_results(): void
    {
        $this->actingAs($this->user);
        $request = $this->makeRequestWithItems();
        $sample = app(SampleService::class)->generateForRequest($request)->first();

        $this->patch(route('admin.lab.samples.reject', $sample), [
            'rejection_reason' => 'Haemolysed',
        ])->assertRedirect();

        $sample->refresh();
        $this->assertEquals(SampleStatus::REJECTED, $sample->status_enum);
        $this->assertEquals('Haemolysed', $sample->rejection_reason);

        $item = $sample->items()->first();
        $this->assertTrue($item->isBlockedBySample());
    }

    public function test_samples_queue_is_visible_with_permission(): void
    {
        $this->actingAs($this->user);
        $request = $this->makeRequestWithItems();
        app(SampleService::class)->generateForRequest($request);

        $this->get(route('admin.lab.samples.index'))
            ->assertOk()
            ->assertSee($request->request_number);
    }

    public function test_request_page_renders_samples_panel(): void
    {
        $this->user->givePermissionTo(Permission::create(['name' => 'lab.requests.view']));
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        $this->actingAs($this->user);

        $request = $this->makeRequestWithItems();
        app(SampleService::class)->generateForRequest($request);

        $sampleNumber = $request->samples()->first()->sample_number;

        $this->get(route('admin.lab.requests.show', $request))
            ->assertOk()
            // Panel column header + a generated sample confirm the specimen panel rendered.
            ->assertSee(__('samples.chain_col'))
            ->assertSee($sampleNumber);
    }
}
