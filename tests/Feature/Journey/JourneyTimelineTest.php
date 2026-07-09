<?php

namespace Tests\Feature\Journey;

use App\Enums\VisitStatus;
use App\Models\Department;
use App\Models\User;
use App\Models\Visit;
use App\Services\Journey\JourneyTimelineBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class JourneyTimelineTest extends TestCase
{
    use RefreshDatabase;

    private function visit(VisitStatus $status): Visit
    {
        $user = User::factory()->create();
        $dept = Department::create(['name' => 'OPD', 'code' => 'OPD'.uniqid(), 'type' => 'consultation', 'status' => 'active']);

        return Visit::factory()->create([
            'status' => $status, 'current_department_id' => $dept->id,
            'created_by' => $user->id, 'created_at' => now()->subHours(2),
        ]);
    }

    private function log(Visit $visit, string $from, string $to, $at): void
    {
        DB::table('visit_status_logs')->insert([
            'visit_id' => $visit->id, 'from_status' => $from, 'to_status' => $to,
            'changed_by' => $visit->created_by, 'timestamp' => $at,
        ]);
    }

    private function byStage(Visit $visit): \Illuminate\Support\Collection
    {
        return collect(app(JourneyTimelineBuilder::class)->build($visit))->keyBy(fn ($row) => $row['stage']->value);
    }

    public function test_timeline_marks_completed_active_and_waiting(): void
    {
        $visit = $this->visit(VisitStatus::CONSULTING);
        $this->log($visit, 'registered', 'checked_in', now()->subHour());
        $this->log($visit, 'checked_in', 'consulting', now()->subMinutes(30));

        $stages = $this->byStage($visit);

        $this->assertSame('completed', $stages['registered']['status']);
        $this->assertSame('completed', $stages['checked_in']['status']);
        $this->assertSame('skipped', $stages['triage']['status']);
        $this->assertSame('active', $stages['consultation']['status']);
        $this->assertSame('waiting', $stages['completed']['status']);
    }

    public function test_timeline_represents_triage_between_checkin_and_consultation(): void
    {
        $visit = $this->visit(VisitStatus::QUEUED);
        $this->log($visit, 'registered', 'checked_in', now()->subHour());

        $stages = $this->byStage($visit);

        $this->assertSame('active', $stages['checked_in']['status']);
        $this->assertSame('waiting', $stages['triage']['status']);
        $this->assertSame('waiting', $stages['consultation']['status']);
        $this->assertLessThan($stages['consultation']['stage']->order(), $stages['triage']['stage']->order());
    }

    public function test_a_stage_jumped_over_is_marked_skipped(): void
    {
        // Patient went registered → consulting without a check-in transition.
        $visit = $this->visit(VisitStatus::CONSULTING);
        $this->log($visit, 'registered', 'consulting', now()->subMinutes(20));

        $stages = $this->byStage($visit);

        $this->assertSame('completed', $stages['registered']['status']);
        $this->assertSame('skipped', $stages['checked_in']['status']);
        $this->assertSame('skipped', $stages['triage']['status']);
        $this->assertSame('active', $stages['consultation']['status']);
    }

    public function test_completed_visit_shows_all_stages_done(): void
    {
        $visit = $this->visit(VisitStatus::COMPLETED);
        $this->log($visit, 'registered', 'checked_in', now()->subHour());
        $this->log($visit, 'checked_in', 'consulting', now()->subMinutes(40));
        $this->log($visit, 'consulting', 'completed', now()->subMinutes(10));

        $stages = $this->byStage($visit);

        $this->assertSame('completed', $stages['completed']['status']);
        $this->assertSame('completed', $stages['consultation']['status']);
    }
}
