<?php

namespace Tests\Feature;

use App\Enums\AdmissionStatus;
use App\Enums\ProductType;
use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Models\Admission;
use App\Models\Bed;
use App\Models\ClinicalTask;
use App\Models\Department;
use App\Models\Drug;
use App\Models\DrugCategory;
use App\Models\MedicalRecord;
use App\Models\MedicationAdministration;
use App\Models\MedicationAdministrationSchedule;
use App\Models\MedicationFrequency;
use App\Models\MedicationOrder;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\Product;
use App\Models\StockBalance;
use App\Models\StockLocation;
use App\Models\User;
use App\Models\Visit;
use App\Models\Ward;
use App\Services\MedicationAdministrationService;
use App\Services\MedicationOrderService;
use App\Services\MedicationScheduleService;
use App\Services\MarChartService;
use Database\Seeders\MedicationFrequencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class MedicationAdministrationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Department $department;

    private Patient $patient;

    private Visit $visit;

    private Admission $admission;

    private Product $product;

    private Drug $drug;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(MedicationFrequencySeeder::class);

        $this->department = Department::factory()->create(['type' => 'treatment']);
        $this->user = User::factory()->create(['department_id' => $this->department->id]);

        $role = Role::findOrCreate('Ward Nurse', 'web');
        foreach ([
            'ward.view',
            'admission.medication_board.view',
            'emergency.medication_board.view',
            'medication_administration.view',
            'medication_administration.administer',
            'medication_administration.correct',
            'medication_administration.view_reports',
            'mar_chart.view',
            'mar_chart.print',
            'admission.mar_chart.view',
            'emergency.mar_chart.view',
            'medication_orders.hold',
            'medication_orders.stop',
        ] as $permission) {
            $role->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
        $this->user->assignRole($role);

        $this->patient = Patient::factory()->create([
            'registered_by' => $this->user->id,
            'first_name' => 'Ama',
            'other_names' => null,
            'last_name' => 'Mensah',
        ]);
        $this->visit = Visit::factory()->create([
            'patient_id' => $this->patient->id,
            'visit_type' => VisitType::INPATIENT,
            'status' => VisitStatus::ADMITTED,
            'current_department_id' => $this->department->id,
            'created_by' => $this->user->id,
        ]);

        $ward = Ward::create([
            'name' => 'Male Ward',
            'code' => 'MW-MAR',
            'department_id' => $this->department->id,
            'capacity' => 10,
            'is_active' => true,
        ]);

        $bed = Bed::create([
            'ward_id' => $ward->id,
            'bed_number' => 'B1',
            'bed_type' => 'standard',
            'status' => 'occupied',
            'daily_rate' => 100,
        ]);

        $this->admission = Admission::create([
            'admission_number' => 'ADM-MAR-001',
            'visit_id' => $this->visit->id,
            'patient_id' => $this->patient->id,
            'bed_id' => $bed->id,
            'admitted_by' => $this->user->id,
            'admission_date' => now()->subDay(),
            'status' => AdmissionStatus::ADMITTED,
        ]);

        $this->product = Product::create([
            'name' => 'Ceftriaxone',
            'code' => 'CEF-1G',
            'product_type' => ProductType::DRUG,
            'unit' => 'vial',
            'is_active' => true,
            'created_by' => $this->user->id,
        ]);

        $category = DrugCategory::create(['name' => 'Antibiotics', 'is_active' => true]);
        $this->drug = Drug::create([
            'category_id' => $category->id,
            'product_id' => $this->product->id,
            'name' => 'Ceftriaxone',
            'dosage_form' => 'Injection',
            'strength' => '1g',
            'unit' => 'vial',
            'price' => 20,
            'is_active' => true,
        ]);
    }

    public function test_common_medication_frequencies_are_seeded(): void
    {
        foreach (['OD', 'BD', 'TDS', 'QID', 'STAT', 'PRN'] as $code) {
            $this->assertDatabaseHas('medication_frequencies', ['code' => $code]);
        }
    }

    public function test_bd_for_five_days_generates_ten_schedules_and_tasks_without_duplicates(): void
    {
        $order = $this->makeOrder('BD', 5, 10);

        $first = app(MedicationScheduleService::class)->generateForOrder($order);
        $second = app(MedicationScheduleService::class)->generateForOrder($order);

        $this->assertCount(10, $first);
        $this->assertCount(10, $second);
        $this->assertDatabaseCount('medication_administration_schedules', 10);
        $this->assertDatabaseCount('clinical_tasks', 10);
    }

    public function test_prescription_item_for_admitted_patient_maps_to_medication_order(): void
    {
        $record = MedicalRecord::create([
            'visit_id' => $this->visit->id,
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->user->id,
        ]);

        $prescription = Prescription::create([
            'medical_record_id' => $record->id,
            'visit_id' => $this->visit->id,
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->user->id,
            'prescription_number' => 'RX-MAR-001',
            'status' => 'pending',
        ]);

        $item = PrescriptionItem::create([
            'prescription_id' => $prescription->id,
            'drug_name' => 'Ceftriaxone',
            'drug_id' => $this->drug->id,
            'dosage' => '1g',
            'frequency' => 'BD',
            'duration' => '5 days',
            'quantity' => 10,
            'route' => 'IV',
        ]);

        $order = app(MedicationOrderService::class)->ensureOrderForPrescriptionItem($item);

        $this->assertNotNull($order);
        $this->assertSame($this->admission->id, $order->admission_id);
        $this->assertSame('BD', $order->frequency_code);
        $this->assertSame(10, (int) $order->total_doses);
    }

    public function test_nurse_can_mark_scheduled_dose_given_from_patient_stock_without_stock_movement(): void
    {
        $order = $this->makeOrder('BD', 5, 10, ['quantity_dispensed' => 10]);
        $schedule = app(MedicationScheduleService::class)->generateForOrder($order)->first();

        app(MedicationAdministrationService::class)->administerSchedule($schedule, [
            'status' => 'GIVEN',
            'dose_given' => '1g',
            'source_stock_type' => MedicationAdministration::SOURCE_PATIENT_STOCK,
        ], $this->user);

        $this->assertDatabaseHas('medication_administrations', [
            'schedule_id' => $schedule->id,
            'status' => 'GIVEN',
            'administered_by' => $this->user->id,
        ]);
        $this->assertDatabaseHas('medication_administration_schedules', [
            'id' => $schedule->id,
            'status' => 'GIVEN',
        ]);
        $this->assertDatabaseMissing('stock_movements', [
            'source_type' => MedicationOrder::class,
            'source_id' => $order->id,
        ]);
    }

    public function test_non_given_status_requires_reason(): void
    {
        $this->expectException(ValidationException::class);

        $order = $this->makeOrder('BD', 5, 10, ['quantity_dispensed' => 10]);
        $schedule = app(MedicationScheduleService::class)->generateForOrder($order)->first();

        app(MedicationAdministrationService::class)->administerSchedule($schedule, [
            'status' => 'HELD',
            'source_stock_type' => MedicationAdministration::SOURCE_PATIENT_STOCK,
        ], $this->user);
    }

    public function test_ward_stock_administration_creates_single_stock_out_movement(): void
    {
        $location = StockLocation::create([
            'name' => 'Male Ward Stock',
            'type' => 'ward',
            'department_id' => $this->department->id,
            'is_active' => true,
        ]);
        StockBalance::create([
            'product_id' => $this->product->id,
            'stock_location_id' => $location->id,
            'quantity_on_hand' => 5,
        ]);

        $order = $this->makeOrder('BD', 5, 10, ['quantity_dispensed' => 0]);
        $schedule = app(MedicationScheduleService::class)->generateForOrder($order)->first();

        app(MedicationAdministrationService::class)->administerSchedule($schedule, [
            'status' => 'GIVEN',
            'dose_given' => '1g',
            'source_stock_type' => MedicationAdministration::SOURCE_WARD_STOCK,
            'stock_location_id' => $location->id,
        ], $this->user);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $this->product->id,
            'stock_location_id' => $location->id,
            'source_type' => MedicationOrder::class,
            'source_id' => $order->id,
        ]);
        $this->assertSame('4.0000', StockBalance::where('product_id', $this->product->id)->where('stock_location_id', $location->id)->value('quantity_on_hand'));
    }

    public function test_stopping_medication_cancels_future_schedules_without_changing_given_doses(): void
    {
        $order = $this->makeOrder('BD', 5, 10, ['quantity_dispensed' => 10]);
        $schedules = app(MedicationScheduleService::class)->generateForOrder($order);

        app(MedicationAdministrationService::class)->administerSchedule($schedules->first(), [
            'status' => 'GIVEN',
            'dose_given' => '1g',
            'source_stock_type' => MedicationAdministration::SOURCE_PATIENT_STOCK,
        ], $this->user);

        app(MedicationOrderService::class)->stop($order->fresh(), $this->user, 'Changed by doctor.');

        $this->assertDatabaseHas('medication_administration_schedules', [
            'id' => $schedules->first()->id,
            'status' => 'GIVEN',
        ]);
        $this->assertGreaterThan(0, MedicationAdministrationSchedule::where('medication_order_id', $order->id)->where('status', 'CANCELLED')->count());
    }

    public function test_admission_medication_board_shows_overdue_task(): void
    {
        $order = $this->makeOrder('STAT', null, 1, ['quantity_dispensed' => 1, 'start_at' => now()->subHour()]);
        $schedule = app(MedicationScheduleService::class)->generateForOrder($order)->first();
        $schedule->update(['scheduled_at' => now()->subHour()]);
        $schedule->clinicalTask->update(['due_at' => now()->subHour()]);

        $response = $this->actingAs($this->user)->get(route('admin.admissions.medication-board'));

        $response->assertOk();
        $response->assertSee('Mensah', false);
        $response->assertSee('Overdue', false);
    }

    public function test_stat_emergency_medication_generates_one_due_task_on_emergency_board(): void
    {
        $emergencyVisit = Visit::factory()->create([
            'patient_id' => $this->patient->id,
            'visit_type' => VisitType::EMERGENCY,
            'status' => VisitStatus::EMERGENCY,
            'current_department_id' => $this->department->id,
            'created_by' => $this->user->id,
        ]);

        $frequency = MedicationFrequency::where('code', 'STAT')->firstOrFail();
        $order = MedicationOrder::create([
            'visit_id' => $emergencyVisit->id,
            'patient_id' => $this->patient->id,
            'prescribed_by' => $this->user->id,
            'product_id' => $this->product->id,
            'drug_id' => $this->drug->id,
            'drug_name' => 'Ceftriaxone',
            'dose' => '1g',
            'route' => 'IV',
            'frequency_id' => $frequency->id,
            'frequency_code' => 'STAT',
            'total_doses' => 1,
            'quantity_ordered' => 1,
            'quantity_dispensed' => 1,
            'start_at' => now(),
            'status' => MedicationOrder::STATUS_DISPENSED,
        ]);

        app(MedicationScheduleService::class)->generateForOrder($order);

        $this->assertDatabaseHas('clinical_tasks', [
            'visit_id' => $emergencyVisit->id,
            'task_type' => ClinicalTask::TYPE_MEDICATION_ADMINISTRATION,
            'status' => ClinicalTask::STATUS_DUE,
        ]);

        $response = $this->actingAs($this->user)->get(route('admin.emergency.medication-board'));
        $response->assertOk();
        $response->assertSee('STAT', false);
        $response->assertSee('Ceftriaxone', false);
    }

    public function test_prn_medication_does_not_generate_fixed_schedule(): void
    {
        $order = $this->makeOrder('PRN', null, 0, ['quantity_dispensed' => 5]);

        $schedules = app(MedicationScheduleService::class)->generateForOrder($order);

        $this->assertCount(0, $schedules);
        $this->assertDatabaseMissing('medication_administration_schedules', [
            'medication_order_id' => $order->id,
        ]);
    }

    public function test_admission_mar_chart_renders_daily_grid_with_due_cell_and_modal(): void
    {
        $order = $this->makeOrder('BD', 1, 2, ['quantity_dispensed' => 2]);
        $schedule = app(MedicationScheduleService::class)->generateForOrder($order)->first();
        $scheduledAt = now()->subMinute();
        $schedule->update(['scheduled_at' => $scheduledAt]);
        $schedule->clinicalTask->update([
            'due_at' => $scheduledAt,
            'scheduled_at' => $scheduledAt,
            'status' => ClinicalTask::STATUS_DUE,
        ]);

        $response = $this->actingAs($this->user)->get(route('admin.admissions.mar-chart', [
            'admission' => $this->admission,
            'date' => today()->toDateString(),
        ]));

        $response->assertOk();
        $response->assertSee('Medication Administration Record', false);
        $response->assertSee('Ama Mensah', false);
        $response->assertSee('Ceftriaxone', false);
        $response->assertSee($scheduledAt->format('H:i'), false);
        $response->assertSee('DUE', false);
        $response->assertSee('Print MAR', false);

        // The actionable dose-cell modal trigger (data-bs-target="#mar-dose-{id}")
        // lives inside #mar-chart-content. On a full-page load that markup is
        // delivered inside the Inertia data-page JSON envelope, where the literal
        // quotes are escaped (data-bs-target=\"#mar-dose-1\"). We assert it against
        // the app's real raw-HTML chart render (the X-Mar-Partial path the chart
        // refresh actually uses), where the markup is unescaped.
        $partial = $this->actingAs($this->user)
            ->withHeaders(['X-Mar-Partial' => 'chart'])
            ->get(route('admin.admissions.mar-chart', [
                'admission' => $this->admission,
                'date' => today()->toDateString(),
            ]));

        $partial->assertOk();
        $partial->assertSee('data-bs-target="#mar-dose-'.$schedule->id.'"', false);
    }

    public function test_admission_mar_chart_shows_given_details_with_nurse_name(): void
    {
        $this->user->update(['first_name' => 'Nurse', 'last_name' => 'Ama']);
        $order = $this->makeOrder('BD', 1, 2, ['quantity_dispensed' => 2]);
        $schedule = app(MedicationScheduleService::class)->generateForOrder($order)->first();

        app(MedicationAdministrationService::class)->administerSchedule($schedule, [
            'status' => 'GIVEN',
            'dose_given' => '1g',
            'source_stock_type' => MedicationAdministration::SOURCE_PATIENT_STOCK,
            'notes' => 'Tolerated well',
        ], $this->user);

        $response = $this->actingAs($this->user)->get(route('admin.admissions.mar-chart', [
            'admission' => $this->admission,
            'date' => today()->toDateString(),
        ]));

        $response->assertOk();
        $response->assertSee('GIVEN', false);
        $response->assertSee('Nurse Ama', false);
        $response->assertSee('Tolerated well', false);
        $response->assertSee('Medication Administration Details', false);
    }

    public function test_prn_medications_appear_in_separate_mar_section(): void
    {
        $this->makeOrder('PRN', null, 0, ['quantity_dispensed' => 5, 'instructions' => 'For severe pain.']);

        // The PRN/SOS section is part of #mar-chart-content. On a full-page load it is
        // serialised inside the Inertia data-page JSON, where the heading's slash is
        // escaped ("PRN \/ SOS Medications"); assert against the app's raw-HTML chart
        // render (X-Mar-Partial) where the section is emitted verbatim.
        $response = $this->actingAs($this->user)
            ->withHeaders(['X-Mar-Partial' => 'chart'])
            ->get(route('admin.admissions.mar-chart', [
                'admission' => $this->admission,
                'date' => today()->toDateString(),
            ]));

        $response->assertOk();
        $response->assertSee('PRN / SOS Medications', false);
        $response->assertSee('For severe pain.', false);
        $response->assertSee('Administer PRN', false);
    }

    public function test_emergency_mar_chart_loads_for_emergency_visit(): void
    {
        $emergencyVisit = Visit::factory()->create([
            'patient_id' => $this->patient->id,
            'visit_type' => VisitType::EMERGENCY,
            'status' => VisitStatus::EMERGENCY,
            'current_department_id' => $this->department->id,
            'created_by' => $this->user->id,
        ]);

        $frequency = MedicationFrequency::where('code', 'STAT')->firstOrFail();
        $order = MedicationOrder::create([
            'visit_id' => $emergencyVisit->id,
            'patient_id' => $this->patient->id,
            'prescribed_by' => $this->user->id,
            'product_id' => $this->product->id,
            'drug_id' => $this->drug->id,
            'drug_name' => 'Ceftriaxone',
            'dose' => '1g',
            'route' => 'IV',
            'frequency_id' => $frequency->id,
            'frequency_code' => 'STAT',
            'total_doses' => 1,
            'quantity_ordered' => 1,
            'quantity_dispensed' => 1,
            'start_at' => now(),
            'status' => MedicationOrder::STATUS_DISPENSED,
        ]);
        app(MedicationScheduleService::class)->generateForOrder($order);

        $response = $this->actingAs($this->user)->get(route('admin.emergency.mar-chart', [
            'visit' => $emergencyVisit,
            'date' => today()->toDateString(),
        ]));

        $response->assertOk();
        $response->assertSee('MAR Chart', false);
        $response->assertSee('Ceftriaxone', false);
        $response->assertSee('STAT', false);
    }

    public function test_mar_chart_partial_refresh_returns_chart_content_only(): void
    {
        $this->makeOrder('BD', 1, 2, ['quantity_dispensed' => 2]);

        $response = $this->actingAs($this->user)
            ->withHeaders(['X-Mar-Partial' => 'chart'])
            ->get(route('admin.admissions.mar-chart', [
                'admission' => $this->admission,
                'date' => today()->toDateString(),
            ]));

        $response->assertOk();
        $response->assertSee('id="mar-chart-content"', false);
        $response->assertDontSee('<html', false);
    }

    public function test_viewing_mar_chart_does_not_create_stock_movements(): void
    {
        $this->makeOrder('BD', 1, 2, ['quantity_dispensed' => 2]);
        $before = DB::table('stock_movements')->count();

        $this->actingAs($this->user)->get(route('admin.admissions.mar-chart', [
            'admission' => $this->admission,
            'date' => today()->toDateString(),
        ]))->assertOk();

        $this->assertSame($before, DB::table('stock_movements')->count());
    }

    public function test_mar_chart_service_returns_normalized_time_columns_and_rows(): void
    {
        $this->makeOrder('BD', 1, 2, ['quantity_dispensed' => 2]);

        $payload = app(MarChartService::class)->buildForAdmission($this->admission, today());

        $this->assertSame('Ama Mensah', $payload['header']['patient_name']);
        $this->assertNotEmpty($payload['time_columns']);
        $this->assertCount(1, $payload['medication_rows']);
        $this->assertArrayHasKey('cells', $payload['medication_rows']->first());
    }

    /*
    |--------------------------------------------------------------------------
    | MAR audit-trail logging (patient timeline)
    |--------------------------------------------------------------------------
    */

    private function marTimeline(): \Illuminate\Support\Collection
    {
        return app(\App\Services\ActivityLogService::class)->getPatientTimeline($this->patient)->get();
    }

    public function test_dose_administration_logs_to_patient_timeline_with_context(): void
    {
        $order = $this->makeOrder('BD', 5, 10, ['quantity_dispensed' => 10]);
        $schedule = app(MedicationScheduleService::class)->generateForOrder($order)->first();

        app(MedicationAdministrationService::class)->administerSchedule($schedule, [
            'status' => 'GIVEN', 'dose_given' => '1g',
            'source_stock_type' => MedicationAdministration::SOURCE_PATIENT_STOCK,
        ], $this->user);

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'MAR', 'event' => 'DOSE_ADMINISTERED',
            'patient_id' => $this->patient->id, 'visit_id' => $this->visit->id,
        ]);

        $log = $this->marTimeline()->firstWhere('event', 'DOSE_ADMINISTERED');
        $this->assertNotNull($log);
        $this->assertSame($this->admission->id, (int) $log->properties['admission_id']);
        $this->assertSame($order->id, (int) $log->properties['medication_order_id']);
        $this->assertStringContainsString('Ceftriaxone', $log->description);

        // Exactly one timeline item for one dose action.
        $this->assertSame(1, $this->marTimeline()->where('event', 'DOSE_ADMINISTERED')->count());
    }

    public function test_held_dose_logs_reason_on_patient_timeline(): void
    {
        $order = $this->makeOrder('BD', 5, 10, ['quantity_dispensed' => 10]);
        $schedule = app(MedicationScheduleService::class)->generateForOrder($order)->first();

        app(MedicationAdministrationService::class)->administerSchedule($schedule, [
            'status' => 'HELD', 'reason_not_given' => 'Patient vomiting',
            'source_stock_type' => MedicationAdministration::SOURCE_PATIENT_STOCK,
        ], $this->user);

        $log = $this->marTimeline()->firstWhere('event', 'DOSE_HELD');
        $this->assertNotNull($log);
        $this->assertSame('Patient vomiting', $log->properties['reason']);
        $this->assertStringContainsString('vomiting', $log->description);
    }

    public function test_adverse_reaction_logs_distinct_timeline_event(): void
    {
        $order = $this->makeOrder('BD', 5, 10, ['quantity_dispensed' => 10]);
        $schedule = app(MedicationScheduleService::class)->generateForOrder($order)->first();

        app(MedicationAdministrationService::class)->administerSchedule($schedule, [
            'status' => 'GIVEN', 'dose_given' => '1g', 'reaction' => 'Rash after dose',
            'source_stock_type' => MedicationAdministration::SOURCE_PATIENT_STOCK,
        ], $this->user);

        $events = $this->marTimeline()->pluck('event')->all();
        $this->assertContains('DOSE_ADMINISTERED', $events);
        $this->assertContains('ADVERSE_REACTION_RECORDED', $events);
    }

    public function test_stopping_order_logs_on_patient_timeline_with_reason(): void
    {
        $order = $this->makeOrder('BD', 5, 10);

        app(MedicationOrderService::class)->stop($order->fresh(), $this->user, 'Adverse reaction');

        $log = $this->marTimeline()->firstWhere('event', 'MEDICATION_ORDER_STOPPED');
        $this->assertNotNull($log);
        $this->assertSame('Adverse reaction', $log->properties['reason']);
        $this->assertSame($this->patient->id, (int) $log->patient_id);
    }

    private function makeOrder(string $frequencyCode, ?int $durationDays, int $totalDoses, array $overrides = []): MedicationOrder
    {
        $frequency = MedicationFrequency::where('code', $frequencyCode)->firstOrFail();

        return MedicationOrder::create(array_merge([
            'visit_id' => $this->visit->id,
            'admission_id' => $this->admission->id,
            'patient_id' => $this->patient->id,
            'prescribed_by' => $this->user->id,
            'product_id' => $this->product->id,
            'drug_id' => $this->drug->id,
            'drug_name' => 'Ceftriaxone',
            'dose' => '1g',
            'route' => 'IV',
            'frequency_id' => $frequency->id,
            'frequency_code' => $frequency->code,
            'duration_value' => $durationDays,
            'duration_unit' => $durationDays ? 'days' : null,
            'total_doses' => $totalDoses,
            'quantity_ordered' => $totalDoses,
            'quantity_dispensed' => 0,
            'start_at' => now()->startOfDay(),
            'status' => MedicationOrder::STATUS_DISPENSED,
        ], $overrides));
    }
}
