<?php

namespace Tests\Feature;

use App\Enums\PatientFinancialRiskStatus;
use App\Models\ActivityLog;
use App\Models\Patient;
use App\Models\PatientFinancialRiskProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class PatientFinancialRiskCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['audit_streaming.async_writes' => false]);
        User::factory()->create(); // id 1 for PatientFactory registered_by
    }

    public function test_expire_command_expires_due_profiles_and_is_idempotent(): void
    {
        $profile = PatientFinancialRiskProfile::factory()->for(Patient::factory())
            ->status(PatientFinancialRiskStatus::ACTIVE)
            ->expiringOn(now()->subDay()->toDateString())
            ->create(['set_by' => User::factory()->create()->id]);

        $this->assertSame(0, Artisan::call('billing:financial-risk-expire', ['--json' => true]));
        $this->assertSame(PatientFinancialRiskStatus::EXPIRED, $profile->fresh()->status);
        $this->assertSame(1, $profile->history()->count());
        $this->assertTrue(ActivityLog::where('event', 'PATIENT_FINANCIAL_RISK_EXPIRED')->exists());

        // Second run does nothing further.
        Artisan::call('billing:financial-risk-expire');
        $this->assertSame(1, $profile->fresh()->history()->count());
    }

    public function test_audit_command_reports_anomalies_as_valid_json_without_writes(): void
    {
        // Active profile already beyond expiry + missing "other" details → anomalies.
        PatientFinancialRiskProfile::factory()->for(Patient::factory())
            ->status(PatientFinancialRiskStatus::ACTIVE)
            ->create([
                'primary_reason' => 'other',
                'reason_details' => null,
                'expires_at' => now()->subDays(3)->toDateString(),
                'set_by' => null,
            ]);

        $before = ActivityLog::query()->count();
        $exit = Artisan::call('billing:financial-risk-audit', ['--json' => true]);
        $payload = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame(0, $exit);
        $this->assertGreaterThanOrEqual(1, $payload['profiles_with_findings']);
        $flat = collect($payload['findings'])->pluck('findings')->flatten();
        $this->assertTrue($flat->contains('active_beyond_expiry'));
        $this->assertTrue($flat->contains('missing_reason_details'));
        $this->assertTrue($flat->contains('missing_setter'));
        // Read-only: no activity written.
        $this->assertSame($before, ActivityLog::query()->count());
    }

    public function test_audit_command_reports_no_findings_cleanly(): void
    {
        $exit = Artisan::call('billing:financial-risk-audit', ['--json' => true]);
        $payload = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame(0, $exit);
        $this->assertSame(0, $payload['profiles_with_findings']);
    }
}
