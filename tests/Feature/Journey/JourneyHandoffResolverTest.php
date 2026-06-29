<?php

namespace Tests\Feature\Journey;

use App\Data\Journey\JourneyHandoff;
use App\Enums\JourneyDelayCause;
use App\Enums\VisitStatus;
use App\Models\Department;
use App\Models\User;
use App\Models\Visit;
use App\Services\Journey\JourneyHandoffResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class JourneyHandoffResolverTest extends TestCase
{
    use RefreshDatabase;

    private function department(string $type): Department
    {
        return Department::create(['name' => ucfirst($type), 'code' => 'H'.uniqid(), 'type' => $type, 'status' => 'active']);
    }

    private function visit(VisitStatus $status, Department $from): Visit
    {
        $creator = User::factory()->create();

        return Visit::factory()->create([
            'status' => $status, 'current_department_id' => $from->id, 'created_by' => $creator->id,
            'created_at' => now()->subHours(3),
        ]);
    }

    private function handoff(Visit $visit): JourneyHandoff
    {
        return app(JourneyHandoffResolver::class)->resolve($visit->fresh(), User::factory()->create());
    }

    public function test_lab_handoff_consultation_to_investigation(): void
    {
        $consult = $this->department('consultation');
        $lab = $this->department('investigation');
        $visit = $this->visit(VisitStatus::LAB, $consult);
        DB::table('lab_requests')->insert([
            'request_number' => 'LR-'.uniqid(), 'requested_by' => $visit->created_by,
            'visit_id' => $visit->id, 'patient_id' => $visit->patient_id,
            'target_department_id' => $lab->id, 'status' => 'pending',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $handoff = $this->handoff($visit);

        $this->assertSame($consult->id, $handoff->fromDepartmentId);
        $this->assertSame('consultation', $handoff->fromDepartmentType);
        $this->assertSame($lab->id, $handoff->toDepartmentId);
        $this->assertSame('investigation', $handoff->toDepartmentType);
        $this->assertSame(JourneyDelayCause::AWAITING_LAB_RESULT, $handoff->cause);
        $this->assertTrue($handoff->isCrossDepartment());
    }

    public function test_pharmacy_handoff_ward_to_pharmacy(): void
    {
        $handoff = $this->handoff($this->visit(VisitStatus::PHARMACY, $this->department('inpatient')));

        $this->assertSame('inpatient', $handoff->fromDepartmentType);
        $this->assertSame('pharmacy', $handoff->toDepartmentType);
        $this->assertTrue($handoff->isCrossDepartment());
    }

    public function test_bed_handoff_emergency_to_ward(): void
    {
        $handoff = $this->handoff($this->visit(VisitStatus::ADMITTING, $this->department('emergency')));

        $this->assertSame('emergency', $handoff->fromDepartmentType);
        $this->assertSame('inpatient', $handoff->toDepartmentType);
        $this->assertSame(JourneyDelayCause::AWAITING_BED, $handoff->cause);
        $this->assertTrue($handoff->isCrossDepartment());
    }

    public function test_payment_handoff_clinical_to_finance(): void
    {
        $handoff = $this->handoff($this->visit(VisitStatus::BILLING, $this->department('consultation')));

        $this->assertSame('consultation', $handoff->fromDepartmentType);
        $this->assertSame('finance', $handoff->toDepartmentType);
        $this->assertSame(JourneyDelayCause::AWAITING_PAYMENT, $handoff->cause);
        $this->assertTrue($handoff->isCrossDepartment());
    }

    public function test_handoff_carries_sla_evaluation(): void
    {
        // Awaiting payment SLA = 30 min; the visit is 3h old → critical breach.
        $handoff = $this->handoff($this->visit(VisitStatus::BILLING, $this->department('consultation')));

        $this->assertSame(30, $handoff->slaMinutes);
        $this->assertSame('critical_breach', $handoff->slaStatus);
        $this->assertTrue($handoff->isBreached());
    }
}
