<?php

namespace Tests\Feature;

use App\Enums\VisitPaymentArrangementStatus;
use App\Enums\VisitType;
use App\Models\ActivityLog;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitPaymentArrangement;
use Database\Seeders\PaymentTimingSettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class VisitPaymentArrangementCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['audit_streaming.async_writes' => false]);
        User::factory()->create(); // id 1
        (new PaymentTimingSettingsSeeder)->run();
    }

    private function approvedArrangement(?string $expiresAt = null): VisitPaymentArrangement
    {
        $visit = Visit::factory()->create(['patient_id' => Patient::factory()->create()->id, 'visit_type' => VisitType::OUTPATIENT->value]);

        return VisitPaymentArrangement::factory()->approved()->create([
            'visit_id' => $visit->id,
            'visit_payment_policy_id' => $visit->paymentPolicy?->id,
            'expires_at' => $expiresAt,
        ]);
    }

    public function test_expire_command_dry_run_writes_nothing_then_commit_expires(): void
    {
        $a = $this->approvedArrangement(now()->subDay()->toDateString());

        Artisan::call('billing:visit-payment-arrangement-expire', ['--json' => true]);
        $dry = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame('dry-run', $dry['mode']);
        $this->assertSame(1, $dry['due']);
        $this->assertSame(VisitPaymentArrangementStatus::APPROVED, $a->fresh()->status);

        Artisan::call('billing:visit-payment-arrangement-expire', ['--commit' => true]);
        $this->assertSame(VisitPaymentArrangementStatus::EXPIRED, $a->fresh()->status);
        $this->assertTrue(ActivityLog::where('event', 'VISIT_PAYMENT_ARRANGEMENT_EXPIRED')->exists());

        // Idempotent.
        Artisan::call('billing:visit-payment-arrangement-expire', ['--commit' => true, '--json' => true]);
        $second = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame(0, $second['due']);
    }

    public function test_audit_command_detects_anomalies_read_only(): void
    {
        // Approved but past-expiry still current + requester==approver anomaly.
        $u = User::factory()->create();
        $this->approvedArrangement(now()->subDays(2)->toDateString())->update(['requested_by' => $u->id, 'approved_by' => $u->id]);

        $before = ActivityLog::count();
        $exit = Artisan::call('billing:visit-payment-arrangement-audit', ['--json' => true]);
        $payload = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame(0, $exit);
        $flat = collect($payload['findings'])->pluck('findings')->flatten();
        $this->assertTrue($flat->contains('expired_still_current'));
        $this->assertTrue($flat->contains('requester_equals_approver'));
        $this->assertSame($before, ActivityLog::count());
    }
}
