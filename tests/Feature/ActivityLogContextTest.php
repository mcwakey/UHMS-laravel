<?php

namespace Tests\Feature;

use App\Enums\BillingType;
use App\Enums\InvoiceStatus;
use App\Enums\LogModule;
use App\Models\ActivityLog;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use App\Services\ActivityLogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Audit-trail context: module actions must attach patient/visit and surface on
 * the patient timeline (the bug was the timeline only queried subject=Patient).
 */
class ActivityLogContextTest extends TestCase
{
    use RefreshDatabase;

    private ActivityLogService $log;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
        $this->log = app(ActivityLogService::class);
    }

    private function makePatientVisit(): array
    {
        $patient = Patient::factory()->create();
        $visit = Visit::factory()->create(['patient_id' => $patient->id]);

        return [$patient, $visit];
    }

    private function makeInvoice(Visit $visit): Invoice
    {
        return Invoice::create([
            'invoice_number' => 'INV' . fake()->unique()->numberBetween(10000, 99999),
            'visit_id' => $visit->id,
            'patient_id' => $visit->patient_id,
            'billing_type' => BillingType::CASH,
            'subtotal' => 100, 'tax_amount' => 0, 'discount_amount' => 0, 'nhis_amount' => 0,
            'total_amount' => 100, 'amount_paid' => 0, 'balance' => 100,
            'status' => InvoiceStatus::PENDING, 'created_by' => $this->user->id,
        ]);
    }

    public function test_log_attaches_patient_and_visit_from_visit_subject(): void
    {
        [$patient, $visit] = $this->makePatientVisit();

        $this->log->log(LogModule::CONSULTATION, 'DIAGNOSIS_ADDED', ['description' => 'Added malaria'], $visit);

        $this->assertDatabaseHas('activity_log', [
            'event' => 'DIAGNOSIS_ADDED',
            'patient_id' => $patient->id,
            'visit_id' => $visit->id,
        ]);
    }

    public function test_log_attaches_patient_from_invoice_subject(): void
    {
        [$patient, $visit] = $this->makePatientVisit();
        $invoice = $this->makeInvoice($visit);

        $this->log->log(LogModule::BILLING, 'PAYMENT_RECORDED', ['description' => 'GHS 100'], $invoice);

        $this->assertDatabaseHas('activity_log', [
            'event' => 'PAYMENT_RECORDED',
            'patient_id' => $patient->id,
            'visit_id' => $visit->id,
        ]);
    }

    public function test_log_stores_user_module_action_and_reason(): void
    {
        [, $visit] = $this->makePatientVisit();

        $this->log->log(LogModule::CONSULTATION, 'CORRECTED', [
            'reason' => 'Corrected after lab',
            'old_values' => ['diagnosis' => 'Malaria'],
            'new_values' => ['diagnosis' => 'Typhoid'],
        ], $visit);

        $row = ActivityLog::where('event', 'CORRECTED')->first();
        $this->assertNotNull($row);
        $this->assertSame('CONSULTATION', $row->log_name);
        $this->assertSame($this->user->id, $row->causer_id);
        $this->assertSame('Corrected after lab', $row->properties['reason']);
        // buildProperties stores old/new under Spatie's keys (old / attributes).
        $this->assertSame('Malaria', $row->properties['old']['diagnosis']);
        $this->assertSame('Typhoid', $row->properties['attributes']['diagnosis']);
    }

    public function test_sensitive_fields_are_masked(): void
    {
        $out = $this->log->sanitise(['password' => 'secret', 'phone' => '0550000000']);
        $this->assertSame('***MASKED***', $out['password']);
        $this->assertSame('***MASKED***', $out['phone']);
    }

    public function test_patient_timeline_includes_actions_from_all_modules(): void
    {
        [$patient, $visit] = $this->makePatientVisit();
        $invoice = $this->makeInvoice($visit);

        $this->log->log(LogModule::CONSULTATION, 'DIAGNOSIS_ADDED', [], $visit);
        $this->log->log(LogModule::BILLING, 'PAYMENT_RECORDED', [], $invoice);
        $this->log->log(LogModule::PHARMACY, 'DRUG_DISPENSED', ['visit_id' => $visit->id], null);

        $events = $this->log->getPatientTimeline($patient)->pluck('event')->all();

        $this->assertContains('DIAGNOSIS_ADDED', $events);
        $this->assertContains('PAYMENT_RECORDED', $events);
        $this->assertContains('DRUG_DISPENSED', $events);
    }

    public function test_patient_timeline_includes_merged_duplicate_history(): void
    {
        [$patient] = $this->makePatientVisit();
        $duplicate = Patient::factory()->create(['merged_to_patient_id' => $patient->id]);
        $dupVisit = Visit::factory()->create(['patient_id' => $duplicate->id]);

        $this->log->log(LogModule::CONSULTATION, 'DUP_VISIT_NOTE', [], $dupVisit);

        $events = $this->log->getPatientTimeline($patient)->pluck('event')->all();
        $this->assertContains('DUP_VISIT_NOTE', $events);
    }

    public function test_patient_timeline_still_finds_legacy_subject_rows(): void
    {
        [$patient] = $this->makePatientVisit();

        // Simulate a pre-backfill row: subject is the Patient, patient_id column null.
        DB::table('activity_log')->insert([
            'log_name' => 'PATIENTS',
            'description' => 'Legacy patient update',
            'event' => 'LEGACY_UPDATE',
            'subject_type' => Patient::class,
            'subject_id' => $patient->id,
            'patient_id' => null,
            'properties' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $events = $this->log->getPatientTimeline($patient)->pluck('event')->all();
        $this->assertContains('LEGACY_UPDATE', $events);
    }

    public function test_backfill_command_populates_patient_id_from_subject(): void
    {
        [$patient, $visit] = $this->makePatientVisit();

        // Pre-backfill style row: subject Visit, no patient_id column.
        DB::table('activity_log')->insert([
            'log_name' => 'CONSULTATION',
            'description' => 'Old consult',
            'event' => 'OLD_EVENT',
            'subject_type' => Visit::class,
            'subject_id' => $visit->id,
            'patient_id' => null,
            'visit_id' => null,
            'properties' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Artisan::call('logs:backfill-context');

        $this->assertDatabaseHas('activity_log', [
            'event' => 'OLD_EVENT',
            'patient_id' => $patient->id,
            'visit_id' => $visit->id,
        ]);
    }

    public function test_logs_audit_command_runs_and_writes_json(): void
    {
        $code = Artisan::call('logs:audit', ['--json' => true]);
        $this->assertSame(0, $code);
        $this->assertFileExists(storage_path('reports/logs-audit-report.json'));
        $this->assertStringContainsString('UHMS Logging Coverage Audit', Artisan::output());
    }
}
