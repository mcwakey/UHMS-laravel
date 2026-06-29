<?php

namespace Tests\Feature\Journey;

use App\Models\Department;
use App\Models\JourneyFlowSnapshot;
use App\Models\JourneyHandoffAssignment;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class JourneyAnalyticsSnapshotTest extends TestCase
{
    use RefreshDatabase;

    /** A resolved assignment today (Consultation→Investigation) → a 'resolved' lifecycle row. */
    private function seedResolvedAssignment(): void
    {
        $consult = Department::create(['name' => 'OPD', 'code' => 'AS'.substr(uniqid(), -6), 'type' => 'consultation', 'status' => 'active']);
        $visit = Visit::factory()->create(['current_department_id' => $consult->id, 'created_by' => User::factory()->create()->id]);
        JourneyHandoffAssignment::create([
            'visit_id' => $visit->id, 'cause' => 'awaiting_lab_result',
            'from_department_id' => $consult->id, 'to_department_id' => null, 'to_department_type' => 'investigation',
            'status' => JourneyHandoffAssignment::STATUS_RESOLVED,
            'assigned_at' => now()->subHours(3), 'acknowledged_at' => now()->subHours(2), 'resolved_at' => now()->subHour(),
        ]);
    }

    public function test_command_dry_run_creates_no_rows(): void
    {
        $this->seedResolvedAssignment();

        $this->artisan('journey:analytics:snapshot --dry-run')->assertSuccessful();

        $this->assertSame(0, JourneyFlowSnapshot::count());
    }

    public function test_command_is_idempotent(): void
    {
        $this->seedResolvedAssignment();

        $this->artisan('journey:analytics:snapshot');
        $count = JourneyFlowSnapshot::count();
        $this->assertGreaterThan(0, $count);

        $this->artisan('journey:analytics:snapshot');
        $this->assertSame($count, JourneyFlowSnapshot::count());
    }

    public function test_resolved_lifecycle_is_captured(): void
    {
        $this->seedResolvedAssignment();

        $this->artisan('journey:analytics:snapshot');

        $row = JourneyFlowSnapshot::where('sla_status', 'resolved')->first();
        $this->assertNotNull($row);
        $this->assertSame(1, $row->resolved_count);
        $this->assertGreaterThan(0, $row->total_time_to_resolve_minutes);
        $this->assertGreaterThan(0, $row->total_time_to_acknowledge_minutes);
    }

    public function test_run_is_audited(): void
    {
        $this->seedResolvedAssignment();

        $this->artisan('journey:analytics:snapshot');

        $this->assertTrue(DB::table('activity_log')->where('event', 'JOURNEY_ANALYTICS_SNAPSHOT_RUN')->exists());
    }
}
