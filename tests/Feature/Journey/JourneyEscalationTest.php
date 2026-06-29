<?php

namespace Tests\Feature\Journey;

use App\Data\Journey\JourneyHandoff;
use App\Enums\JourneyDelayCause;
use App\Models\JourneyHandoffAssignment;
use App\Services\Journey\JourneyEscalationService;
use App\Services\Journey\JourneySlaService;
use Tests\TestCase;

class JourneyEscalationTest extends TestCase
{
    private function handoff(string $slaStatus): JourneyHandoff
    {
        return new JourneyHandoff(
            visitId: 1, patientId: 1, patientName: 'Patient', visitNumber: 'V1', stage: null,
            cause: JourneyDelayCause::AWAITING_LAB_RESULT, severity: 'critical',
            fromDepartmentId: 1, fromDepartmentName: 'OPD', fromDepartmentType: 'consultation',
            toDepartmentId: 2, toDepartmentName: 'Lab', toDepartmentType: 'investigation',
            actionLabel: 'Validate', actionStatus: 'actionable', actionUrl: null,
            elapsedMinutes: 100, slaMinutes: 120, slaStatus: $slaStatus, minutesToBreach: 20, waitingSince: null,
        );
    }

    private function assignment(string $status): JourneyHandoffAssignment
    {
        return new JourneyHandoffAssignment(['status' => $status, 'assigned_to_user_id' => 7]);
    }

    private function level(string $slaStatus, ?JourneyHandoffAssignment $assignment = null): string
    {
        return app(JourneyEscalationService::class)->levelFor($this->handoff($slaStatus), $assignment);
    }

    public function test_within_sla_has_no_escalation(): void
    {
        $this->assertSame(JourneyHandoffAssignment::ESCALATION_NONE, $this->level(JourneySlaService::WITHIN));
    }

    public function test_near_breach_unassigned_is_warning(): void
    {
        $this->assertSame(JourneyHandoffAssignment::ESCALATION_WARNING, $this->level(JourneySlaService::NEAR_BREACH));
    }

    public function test_breached_unassigned_is_supervisor(): void
    {
        $this->assertSame(JourneyHandoffAssignment::ESCALATION_SUPERVISOR, $this->level(JourneySlaService::BREACHED));
    }

    public function test_critical_breach_is_critical(): void
    {
        $this->assertSame(JourneyHandoffAssignment::ESCALATION_CRITICAL, $this->level(JourneySlaService::CRITICAL_BREACH));
    }

    public function test_breached_but_acknowledged_is_not_escalated(): void
    {
        $this->assertSame(
            JourneyHandoffAssignment::ESCALATION_NONE,
            $this->level(JourneySlaService::BREACHED, $this->assignment(JourneyHandoffAssignment::STATUS_ACKNOWLEDGED)),
        );
    }

    public function test_near_breach_when_assigned_is_not_escalated(): void
    {
        $this->assertSame(
            JourneyHandoffAssignment::ESCALATION_NONE,
            $this->level(JourneySlaService::NEAR_BREACH, $this->assignment(JourneyHandoffAssignment::STATUS_ASSIGNED)),
        );
    }
}
