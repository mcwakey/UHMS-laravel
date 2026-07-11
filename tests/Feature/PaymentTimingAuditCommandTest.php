<?php

namespace Tests\Feature;

use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Models\Patient;
use App\Models\Setting;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PaymentTimingAuditCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_is_read_only_emits_valid_json_and_respects_limit(): void
    {
        $patient = $this->patient();
        Visit::factory()->count(3)->create([
            'patient_id' => $patient->id,
            'created_by' => $patient->registered_by,
            'visit_type' => VisitType::OUTPATIENT,
            'status' => VisitStatus::ACTIVE,
        ]);
        $before = $this->writeCounts();

        $exit = Artisan::call('billing:payment-timing-audit', ['--limit' => 2, '--json' => true]);
        $payload = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame(0, $exit);
        $this->assertSame(2, $payload['visits_inspected']);
        $this->assertSame($before, $this->writeCounts());
    }

    public function test_visit_filter_and_mismatches_only_are_applied_without_failure_exit(): void
    {
        $patient = $this->patient();
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'created_by' => $patient->registered_by,
            'visit_type' => VisitType::OUTPATIENT,
        ]);
        Visit::factory()->create([
            'patient_id' => $patient->id,
            'created_by' => $patient->registered_by,
            'visit_type' => VisitType::INPATIENT,
        ]);
        Setting::setValue('payment_timing', 'outpatient_policy', 'running_bill');

        $exit = Artisan::call('billing:payment-timing-audit', [
            '--visit' => $visit->id,
            '--mismatches-only' => true,
            '--json' => true,
        ]);
        $payload = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame(0, $exit);
        $this->assertSame(1, $payload['visits_inspected']);
        $this->assertCount(1, $payload['examples']);
        $this->assertSame('legacy_more_restrictive', $payload['examples'][0]['outcome']);
    }

    private function writeCounts(): array
    {
        return [
            'visits' => DB::table('visits')->count(),
            'invoices' => DB::table('invoices')->count(),
            'payments' => DB::table('payments')->count(),
            'overrides' => DB::table('visit_billing_overrides')->count(),
            'activity_log' => DB::table('activity_log')->count(),
        ];
    }

    private function patient(): Patient
    {
        $user = User::factory()->create();

        return Patient::factory()->create(['registered_by' => $user->id]);
    }
}
