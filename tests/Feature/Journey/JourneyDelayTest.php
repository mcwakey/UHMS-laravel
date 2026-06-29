<?php

namespace Tests\Feature\Journey;

use App\Enums\PatientJourneyStage;
use App\Enums\VisitStatus;
use App\Models\Department;
use App\Models\User;
use App\Models\Visit;
use App\Services\Journey\JourneyDelayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class JourneyDelayTest extends TestCase
{
    use RefreshDatabase;

    private function consultingVisit(int $minutesInStage): Visit
    {
        $user = User::factory()->create();
        $dept = Department::create(['name' => 'OPD', 'code' => 'OPD'.uniqid(), 'type' => 'consultation', 'status' => 'active']);
        $visit = Visit::factory()->create([
            'status' => VisitStatus::CONSULTING,
            'current_department_id' => $dept->id,
            'created_by' => $user->id,
            'created_at' => now()->subMinutes($minutesInStage + 30),
        ]);
        DB::table('visit_status_logs')->insert([
            'visit_id' => $visit->id, 'from_status' => 'checked_in', 'to_status' => 'consulting',
            'changed_by' => $user->id, 'timestamp' => now()->subMinutes($minutesInStage),
        ]);

        return $visit;
    }

    public function test_delay_is_classified_against_configurable_thresholds(): void
    {
        // config/journey.php → consultation: delayed 60, critical 120.
        $this->assertSame('normal', app(JourneyDelayService::class)->currentDelay($this->consultingVisit(10))['status']);
        $this->assertSame('delayed', app(JourneyDelayService::class)->currentDelay($this->consultingVisit(70))['status']);
        $this->assertSame('critical', app(JourneyDelayService::class)->currentDelay($this->consultingVisit(130))['status']);
    }

    public function test_blocked_stage_is_the_current_stage_when_delayed(): void
    {
        $this->assertSame(PatientJourneyStage::CONSULTATION, app(JourneyDelayService::class)->blockedStage($this->consultingVisit(90)));
        $this->assertNull(app(JourneyDelayService::class)->blockedStage($this->consultingVisit(5)));
    }

    public function test_thresholds_are_configurable(): void
    {
        config(['journey.thresholds.consultation' => ['delayed' => 5, 'critical' => 10]]);

        $this->assertSame('delayed', app(JourneyDelayService::class)->currentDelay($this->consultingVisit(7))['status']);
        $this->assertSame('critical', app(JourneyDelayService::class)->currentDelay($this->consultingVisit(12))['status']);
    }

    public function test_durations_are_computed_from_the_status_log_timeline(): void
    {
        $user = User::factory()->create();
        $dept = Department::create(['name' => 'Lab', 'code' => 'LAB'.uniqid(), 'type' => 'investigation', 'status' => 'active']);
        $visit = Visit::factory()->create([
            'status' => VisitStatus::LAB, 'current_department_id' => $dept->id,
            'created_by' => $user->id, 'created_at' => now()->subMinutes(120),
        ]);
        foreach ([['registered', 'checked_in', 100], ['checked_in', 'consulting', 80], ['consulting', 'lab', 50]] as [$from, $to, $ago]) {
            DB::table('visit_status_logs')->insert([
                'visit_id' => $visit->id, 'from_status' => $from, 'to_status' => $to,
                'changed_by' => $user->id, 'timestamp' => now()->subMinutes($ago),
            ]);
        }

        $durations = app(JourneyDelayService::class)->durations($visit);

        $this->assertArrayHasKey('consultation', $durations['per_stage']);
        $this->assertArrayHasKey('investigation', $durations['per_stage']);
        $this->assertGreaterThan(0, $durations['per_stage']['consultation']);
        $this->assertGreaterThan(100, $durations['total_minutes']);
    }
}
