<?php

namespace Tests\Feature\Journey;

use App\Enums\VisitStatus;
use App\Models\Department;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class JourneyUnassignedSweepTest extends TestCase
{
    use RefreshDatabase;

    private Department $lab;

    /** A critical-breach unassigned Consultation→Investigation handoff + eligible lab staff. */
    private function seedUnassignedHandoff(): User
    {
        $consult = Department::create(['name' => 'OPD '.uniqid(), 'code' => 'US'.uniqid(), 'type' => 'consultation', 'status' => 'active']);
        $this->lab = Department::create(['name' => 'Lab '.uniqid(), 'code' => 'UL'.uniqid(), 'type' => 'investigation', 'status' => 'active']);
        $creator = User::factory()->create();
        $visit = Visit::factory()->create([
            'status' => VisitStatus::LAB, 'current_department_id' => $consult->id, 'created_by' => $creator->id,
            'created_at' => now()->subHours(5),
        ]);
        DB::table('visits')->where('id', $visit->id)->update(['updated_at' => now()->subHours(5)]);
        DB::table('lab_requests')->insert([
            'request_number' => 'LR-'.uniqid(), 'requested_by' => $creator->id,
            'visit_id' => $visit->id, 'patient_id' => $visit->patient_id,
            'target_department_id' => $this->lab->id, 'status' => 'pending',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return User::factory()->create(['department_id' => $this->lab->id]); // eligible recipient
    }

    public function test_sweep_notifies_eligible_staff_for_unassigned_critical(): void
    {
        $labStaff = $this->seedUnassignedHandoff();

        $this->artisan('journey:handoffs:escalate --include-unassigned')->assertSuccessful();

        $this->assertGreaterThan(0, $labStaff->fresh()->notifications()->count());
    }

    public function test_dry_run_sends_no_notifications(): void
    {
        $labStaff = $this->seedUnassignedHandoff();

        $this->artisan('journey:handoffs:escalate --include-unassigned --dry-run')->assertSuccessful();

        $this->assertSame(0, $labStaff->fresh()->notifications()->count());
    }

    public function test_repeated_runs_are_deduped(): void
    {
        $labStaff = $this->seedUnassignedHandoff();

        $this->artisan('journey:handoffs:escalate --include-unassigned');
        $this->artisan('journey:handoffs:escalate --include-unassigned');

        $this->assertSame(1, $labStaff->fresh()->notifications()->count());
    }

    public function test_cause_filter_excludes_unmatched_handoffs(): void
    {
        $labStaff = $this->seedUnassignedHandoff(); // cause = awaiting_lab_result

        $this->artisan('journey:handoffs:escalate --include-unassigned --cause=awaiting_dispensing')->assertSuccessful();

        $this->assertSame(0, $labStaff->fresh()->notifications()->count());
    }
}
