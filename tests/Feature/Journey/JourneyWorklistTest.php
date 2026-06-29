<?php

namespace Tests\Feature\Journey;

use App\Enums\VisitStatus;
use App\Models\Department;
use App\Models\User;
use App\Models\Visit;
use App\Services\Journey\JourneyWorklistService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class JourneyWorklistTest extends TestCase
{
    use RefreshDatabase;

    private function department(string $type): Department
    {
        return Department::create(['name' => $type, 'code' => 'W'.uniqid(), 'type' => $type, 'status' => 'active']);
    }

    private function userIn(string $type): User
    {
        return User::factory()->create(['department_id' => $this->department($type)->id]);
    }

    /** A delayed active visit in a department of the given type, $minutesAgo old. */
    private function delayedVisit(string $type, VisitStatus $status, int $minutesAgo, ?Department $dept = null): Visit
    {
        $creator = User::factory()->create();
        $dept ??= $this->department($type);
        $visit = Visit::factory()->create([
            'status' => $status, 'current_department_id' => $dept->id, 'created_by' => $creator->id,
            'created_at' => now()->subMinutes($minutesAgo),
        ]);
        DB::table('visits')->where('id', $visit->id)->update(['updated_at' => now()->subMinutes($minutesAgo)]);

        return $visit->fresh();
    }

    public function test_worklist_is_capability_filtered(): void
    {
        $opdVisit = $this->delayedVisit('consultation', VisitStatus::WAITING, 200);
        $storeVisit = $this->delayedVisit('stores', VisitStatus::WAITING, 200);

        $actions = app(JourneyWorklistService::class)->forUser($this->userIn('consultation'));
        $visitIds = collect($actions)->pluck('visitId');

        $this->assertTrue($visitIds->contains($opdVisit->id));
        $this->assertFalse($visitIds->contains($storeVisit->id));
    }

    public function test_critical_actions_sort_before_delayed(): void
    {
        $this->delayedVisit('consultation', VisitStatus::WAITING, 90);   // delayed (60 ≤ 90 < 120)
        $critical = $this->delayedVisit('consultation', VisitStatus::WAITING, 240); // critical (≥ 120)

        $actions = app(JourneyWorklistService::class)->forUser($this->userIn('consultation'));

        $this->assertNotEmpty($actions);
        $this->assertSame('critical', $actions[0]->severity);
        $this->assertSame($critical->id, $actions[0]->visitId);
    }

    public function test_for_department_is_scoped(): void
    {
        $opd = $this->department('consultation');
        $visit = $this->delayedVisit('consultation', VisitStatus::WAITING, 200, $opd);
        // Another consultation department's delayed visit must not leak in.
        $this->delayedVisit('consultation', VisitStatus::WAITING, 200);

        $actions = app(JourneyWorklistService::class)->forDepartment($opd, $this->userIn('consultation'));

        $this->assertCount(1, $actions);
        $this->assertSame($visit->id, $actions[0]->visitId);
    }

    public function test_summary_counts_and_top_cause(): void
    {
        $this->delayedVisit('consultation', VisitStatus::WAITING, 90);   // delayed
        $this->delayedVisit('consultation', VisitStatus::WAITING, 240);  // critical

        $summary = app(JourneyWorklistService::class)->summaryForUser($this->userIn('consultation'));

        $this->assertSame(2, $summary['total']);
        $this->assertSame(1, $summary['critical']);
        $this->assertSame(1, $summary['delayed']);
        $this->assertNotNull($summary['top_cause']);
    }
}
