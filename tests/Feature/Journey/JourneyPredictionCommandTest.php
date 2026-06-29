<?php

namespace Tests\Feature\Journey;

use App\Enums\VisitStatus;
use App\Models\Department;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class JourneyPredictionCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    /** A critical-breach Consultation→Investigation handoff + eligible lab staff. */
    private function seedHandoff(): User
    {
        $consult = Department::create(['name' => 'OPD', 'code' => 'PC'.substr(uniqid(), -6), 'type' => 'consultation', 'status' => 'active']);
        $lab = Department::create(['name' => 'Lab', 'code' => 'PCL'.substr(uniqid(), -5), 'type' => 'investigation', 'status' => 'active']);
        $visit = Visit::factory()->create(['status' => VisitStatus::LAB, 'current_department_id' => $consult->id, 'created_by' => User::factory()->create()->id, 'created_at' => now()->subHours(5)]);
        DB::table('visits')->where('id', $visit->id)->update(['updated_at' => now()->subHours(5)]);
        DB::table('lab_requests')->insert([
            'request_number' => 'LR-'.uniqid(), 'requested_by' => $visit->created_by, 'visit_id' => $visit->id,
            'patient_id' => $visit->patient_id, 'target_department_id' => $lab->id, 'status' => 'pending',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return User::factory()->create(['department_id' => $lab->id]);
    }

    public function test_dry_run_writes_no_audit(): void
    {
        $this->seedHandoff();

        $this->artisan('journey:predictions:check --dry-run')->assertSuccessful();

        $this->assertFalse(DB::table('activity_log')->where('event', 'JOURNEY_PREDICTION_CHECK_RUN')->exists());
    }

    public function test_real_run_is_audited(): void
    {
        $this->seedHandoff();

        $this->artisan('journey:predictions:check')->assertSuccessful();

        $this->assertTrue(DB::table('activity_log')->where('event', 'JOURNEY_PREDICTION_CHECK_RUN')->exists());
    }

    public function test_alerts_are_disabled_by_default(): void
    {
        $labStaff = $this->seedHandoff();
        // prediction_alerts.enabled defaults to false → --notify is a no-op.

        $this->artisan('journey:predictions:check --notify')->assertSuccessful();

        $this->assertSame(0, $labStaff->fresh()->notifications()->count());
    }

    public function test_limit_is_respected(): void
    {
        $this->seedHandoff();
        $this->seedHandoff();

        $this->artisan('journey:predictions:check --limit=1')
            ->expectsOutputToContain('Checked: 1')
            ->assertSuccessful();
    }
}
