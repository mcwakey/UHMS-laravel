<?php

namespace Tests\Feature\Journey;

use App\Enums\JourneyDelayCause;
use App\Models\Department;
use App\Models\User;
use App\Models\Visit;
use App\Services\Journey\JourneyBottleneckService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class JourneyBottleneckCauseTest extends TestCase
{
    use RefreshDatabase;

    /** A delayed active visit (old updated_at) in a department of the given type. */
    private function delayedVisit(Department $dept, string $status = 'waiting'): Visit
    {
        $user = User::factory()->create();
        $visit = Visit::factory()->create(['current_department_id' => $dept->id, 'created_by' => $user->id]);
        DB::table('visits')->where('id', $visit->id)->update([
            'status' => $status,
            'updated_at' => now()->subHours(3),
        ]);

        return $visit;
    }

    private function department(string $type, string $name): Department
    {
        return Department::create(['name' => $name, 'code' => 'BN'.uniqid(), 'type' => $type, 'status' => 'active']);
    }

    public function test_bottlenecks_aggregate_waiting_delayed_and_top_cause(): void
    {
        $opd = $this->department('consultation', 'General OPD');
        $this->delayedVisit($opd);
        $this->delayedVisit($opd);

        $bottleneck = collect(app(JourneyBottleneckService::class)->bottlenecks())->firstWhere('department_id', $opd->id);

        $this->assertNotNull($bottleneck);
        $this->assertSame(2, $bottleneck['waiting']);
        $this->assertSame(2, $bottleneck['delayed']); // 3h > consultation threshold (60 min)
        $this->assertSame(JourneyDelayCause::AWAITING_CONSULTATION, $bottleneck['top_cause']);
        $this->assertGreaterThan(60, $bottleneck['avg_wait_minutes']);
    }

    public function test_for_department_is_scoped(): void
    {
        $lab = $this->department('investigation', 'Laboratory');
        $this->delayedVisit($lab, 'lab');

        $insight = app(JourneyBottleneckService::class)->forDepartment($lab->id);

        $this->assertNotNull($insight);
        $this->assertSame(1, $insight['delayed']);
        $this->assertSame(JourneyDelayCause::AWAITING_LAB_RESULT, $insight['top_cause']);
    }

    public function test_summary_reports_most_common_cause(): void
    {
        $opd = $this->department('consultation', 'General OPD');
        $this->delayedVisit($opd);
        $this->delayedVisit($opd);
        $this->delayedVisit($this->department('pharmacy', 'Pharmacy'), 'pharmacy');

        $summary = app(JourneyBottleneckService::class)->summary();

        $this->assertSame(JourneyDelayCause::AWAITING_CONSULTATION, $summary['most_common_cause']);
        $this->assertNotNull($summary['worst']);
    }

    public function test_for_user_filters_by_capability(): void
    {
        $opd = $this->department('consultation', 'General OPD');
        $this->delayedVisit($opd);

        // A user working in the consultation department sees it (workflow capability).
        $clinician = User::factory()->create(['department_id' => $opd->id]);
        $this->assertTrue(collect(app(JourneyBottleneckService::class)->forUser($clinician))->contains('department_id', $opd->id));

        // A store keeper (stores department) has no consultation capability.
        $store = $this->department('stores', 'Main Stores');
        $storeKeeper = User::factory()->create(['department_id' => $store->id]);
        $this->assertFalse(collect(app(JourneyBottleneckService::class)->forUser($storeKeeper))->contains('department_id', $opd->id));
    }
}
