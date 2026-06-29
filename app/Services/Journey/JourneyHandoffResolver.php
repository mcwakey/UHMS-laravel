<?php

namespace App\Services\Journey;

use App\Data\Journey\JourneyAction;
use App\Data\Journey\JourneyHandoff;
use App\Models\User;
use App\Models\Visit;

/**
 * Turns a Visit into a cross-department handoff: FROM = the department the patient
 * is physically in (waiting), TO = the department responsible for the next action
 * (the delay cause owner), plus SLA status. Reuses the Phase 9.3 action resolver
 * (so cause/owner/url/severity stay single-sourced) and the SLA service.
 */
class JourneyHandoffResolver
{
    public function __construct(
        private JourneyActionResolver $actions,
        private JourneySlaService $sla,
    ) {}

    public function resolve(Visit $visit, ?User $user = null): JourneyHandoff
    {
        return $this->fromAction($visit, $this->actions->resolve($visit, $user));
    }

    /** Build a handoff from an already-resolved action (avoids resolving twice). */
    public function fromAction(Visit $visit, JourneyAction $action): JourneyHandoff
    {
        $from = $visit->relationLoaded('currentDepartment')
            ? $visit->getRelation('currentDepartment')
            : $visit->currentDepartment;

        $sla = $this->sla->evaluate($action->cause, $action->elapsedMinutes);

        $fromType = $this->typeValue($from?->type);
        $toType = $this->ownerTypeToDepartmentType($action->ownerType);
        $toId = $action->ownerDepartmentId;
        $toName = $action->ownerDepartmentName;

        // For non-diagnostic causes the owner falls back to the current (FROM)
        // department. When the resolving domain differs, the specific target is
        // unknown — surface the domain, not the FROM department, as the destination.
        if ($toId !== null && $from !== null && $toId === $from->id && $toType !== $fromType) {
            $toId = null;
            $toName = null;
        }

        return new JourneyHandoff(
            visitId: $action->visitId,
            patientId: $action->patientId,
            patientName: $action->patientName,
            visitNumber: $action->visitNumber,
            stage: $action->stage,
            cause: $action->cause,
            severity: $action->severity,
            fromDepartmentId: $from?->id,
            fromDepartmentName: $from?->name,
            fromDepartmentType: $fromType,
            toDepartmentId: $toId,
            toDepartmentName: $toName,
            toDepartmentType: $toType,
            actionLabel: $action->actionLabel,
            actionStatus: $action->actionStatus,
            actionUrl: $action->actionUrl,
            elapsedMinutes: $action->elapsedMinutes,
            slaMinutes: $sla['sla_minutes'],
            slaStatus: $sla['sla_status'],
            minutesToBreach: $sla['minutes_to_breach'],
            waitingSince: $action->waitingSince,
        );
    }

    /** Representative department type for a cause owner domain. */
    private function ownerTypeToDepartmentType(?string $ownerType): ?string
    {
        return match ($ownerType) {
            'consultation' => 'consultation',
            'investigation' => 'investigation',
            'radiology' => 'radiology',
            'pharmacy' => 'pharmacy',
            'ward' => 'inpatient',
            'finance' => 'finance',
            'theatre' => 'theatre',
            default => $ownerType,
        };
    }

    private function typeValue(mixed $type): ?string
    {
        return $type instanceof \App\Enums\DepartmentType ? $type->value : (is_string($type) ? $type : null);
    }
}
