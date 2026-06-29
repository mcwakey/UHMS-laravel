<?php

namespace Tests\Feature\Journey;

use App\Enums\VisitStatus;
use App\Models\Department;
use App\Models\User;
use App\Models\Visit;
use App\Services\Journey\JourneyHandoffWorklistService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class JourneyHandoffWorklistTest extends TestCase
{
    use RefreshDatabase;

    private function department(string $type): Department
    {
        return Department::create(['name' => ucfirst($type).' '.uniqid(), 'code' => 'HW'.uniqid(), 'type' => $type, 'status' => 'active']);
    }

    private function userIn(string $type): User
    {
        return User::factory()->create(['department_id' => $this->department($type)->id]);
    }

    /** A delayed visit in $from awaiting a lab result owned by $lab (a real handoff). */
    private function labHandoff(Department $from, Department $lab, int $minutesAgo = 200): Visit
    {
        $creator = User::factory()->create();
        $visit = Visit::factory()->create([
            'status' => VisitStatus::LAB, 'current_department_id' => $from->id, 'created_by' => $creator->id,
            'created_at' => now()->subMinutes($minutesAgo),
        ]);
        DB::table('visits')->where('id', $visit->id)->update(['updated_at' => now()->subMinutes($minutesAgo)]);
        DB::table('lab_requests')->insert([
            'request_number' => 'LR-'.uniqid(), 'requested_by' => $creator->id,
            'visit_id' => $visit->id, 'patient_id' => $visit->patient_id,
            'target_department_id' => $lab->id, 'status' => 'pending',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return $visit->fresh();
    }

    private function service(): JourneyHandoffWorklistService
    {
        return app(JourneyHandoffWorklistService::class);
    }

    public function test_owed_to_department_lists_outgoing_waits(): void
    {
        $consult = $this->department('consultation');
        $visit = $this->labHandoff($consult, $this->department('investigation'));

        $rows = $this->service()->owedToDepartment($consult, $this->userIn('consultation'));

        $this->assertTrue(collect($rows)->contains('visitId', $visit->id));
        foreach ($rows as $row) {
            $this->assertSame($consult->id, $row->fromDepartmentId);
            $this->assertTrue($row->isCrossDepartment());
        }
    }

    public function test_owed_by_department_lists_incoming_obligations(): void
    {
        $lab = $this->department('investigation');
        $visit = $this->labHandoff($this->department('consultation'), $lab);

        $rows = $this->service()->owedByDepartment($lab, $this->userIn('investigation'));

        $this->assertTrue(collect($rows)->contains('visitId', $visit->id));
        foreach ($rows as $row) {
            $this->assertSame('investigation', $row->toDepartmentType);
            $this->assertNotSame($lab->id, $row->fromDepartmentId);
        }
    }

    public function test_for_user_is_capability_filtered(): void
    {
        $this->labHandoff($this->department('consultation'), $this->department('investigation'));

        // A store keeper sees neither the FROM (consultation) nor TO (investigation) side.
        $this->assertEmpty($this->service()->forUser($this->userIn('stores')));
        // A clinician sees it (from-side capability).
        $this->assertNotEmpty($this->service()->forUser($this->userIn('consultation')));
    }

    public function test_matrix_hides_unauthorized_departments(): void
    {
        $this->labHandoff($this->department('consultation'), $this->department('investigation'));

        $this->assertEmpty($this->service()->matrixForUser($this->userIn('stores')));

        $matrix = $this->service()->matrixForUser($this->userIn('consultation'));
        $this->assertNotEmpty($matrix);
        $this->assertSame('consultation', $matrix[0]['from']);
        $this->assertSame('investigation', $matrix[0]['to']);
        $this->assertSame(1, $matrix[0]['count']);
        $this->assertSame(1, $matrix[0]['breached']);
    }

    public function test_summary_counts_owed_to_for_the_users_department(): void
    {
        $this->labHandoff($this->department('consultation'), $this->department('investigation'));

        $summary = $this->service()->summaryForUser($this->userIn('consultation'));

        $this->assertSame(1, $summary['owed_to']);
        $this->assertSame(0, $summary['owed_by']);
        $this->assertSame('investigation', $summary['top_counterpart']);
    }
}
