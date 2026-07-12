<?php

namespace Tests\Feature;

use App\Enums\VisitType;
use App\Models\ActivityLog;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitPaymentPolicy;
use Database\Seeders\PaymentTimingSettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class VisitPaymentPolicyBackfillTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['audit_streaming.async_writes' => false]);
        User::factory()->create(); // id 1
        (new PaymentTimingSettingsSeeder)->run();
    }

    /** Create visits WITHOUT auto-materialisation so backfill has work to do. */
    private function makeVisits(int $n): void
    {
        config(['visit_payment_policy.auto_materialize' => false]);
        for ($i = 0; $i < $n; $i++) {
            Visit::factory()->create(['patient_id' => Patient::factory()->create()->id, 'visit_type' => VisitType::OUTPATIENT->value]);
        }
        config(['visit_payment_policy.auto_materialize' => true]);
    }

    public function test_dry_run_writes_nothing(): void
    {
        $this->makeVisits(3);
        $exit = Artisan::call('billing:visit-payment-policy-backfill', ['--json' => true]);
        $payload = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame(0, $exit);
        $this->assertSame('dry-run', $payload['mode']);
        $this->assertSame(3, $payload['candidates']);
        $this->assertSame(0, $payload['created']);
        $this->assertSame(0, VisitPaymentPolicy::count());
    }

    public function test_commit_creates_missing_records_and_is_idempotent(): void
    {
        $this->makeVisits(3);

        Artisan::call('billing:visit-payment-policy-backfill', ['--commit' => true]);
        $this->assertSame(3, VisitPaymentPolicy::count());

        // Repeat is idempotent — no duplicates, no new candidates.
        Artisan::call('billing:visit-payment-policy-backfill', ['--commit' => true, '--json' => true]);
        $payload = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame(0, $payload['candidates']);
        $this->assertSame(3, VisitPaymentPolicy::count());

        // No invoices/payments created by backfill.
        $this->assertDatabaseCount('invoices', 0);
    }

    public function test_audit_command_is_read_only_and_valid_json(): void
    {
        $this->makeVisits(2);
        $before = ActivityLog::count();

        $exit = Artisan::call('billing:visit-payment-policy-audit', ['--json' => true]);
        $payload = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame(0, $exit);
        $this->assertSame(2, $payload['missing_records']);
        $this->assertSame($before, ActivityLog::count());
    }

    public function test_refresh_command_dry_run_writes_nothing(): void
    {
        // One auto-materialised visit exists.
        Visit::factory()->create(['patient_id' => Patient::factory()->create()->id, 'visit_type' => VisitType::OUTPATIENT->value]);

        $exit = Artisan::call('billing:visit-payment-policy-refresh', ['--json' => true]);
        $payload = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame(0, $exit);
        $this->assertSame('dry-run', $payload['mode']);
        $this->assertSame(0, VisitPaymentPolicy::whereNotNull('last_refreshed_at')->count());
    }
}
