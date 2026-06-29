<?php

namespace App\Data\Journey;

use App\Enums\JourneyDelayCause;
use App\Enums\PatientJourneyStage;
use Illuminate\Support\Carbon;

/**
 * Immutable view model for a single "someone must act" item on a patient's journey.
 * Holds only primitives + enums — no Eloquent models leak into views.
 */
final class JourneyAction
{
    public function __construct(
        public readonly int $visitId,
        public readonly ?int $patientId,
        public readonly string $patientName,
        public readonly ?string $visitNumber,
        public readonly ?PatientJourneyStage $stage,
        public readonly JourneyDelayCause $cause,
        public readonly string $severity,        // normal | delayed | critical
        public readonly ?string $ownerType,
        public readonly ?int $ownerDepartmentId,
        public readonly ?string $ownerDepartmentName,
        public readonly string $actionLabel,
        public readonly string $actionStatus,    // open | actionable | blocked | resolved
        public readonly ?string $actionUrl,
        public readonly int $elapsedMinutes,
        public readonly ?Carbon $waitingSince,
    ) {}

    /** Sort weight: critical first, then delayed, then longest waiting. */
    public function rank(): int
    {
        $severity = match ($this->severity) {
            'critical' => 2_000_000,
            'delayed' => 1_000_000,
            default => 0,
        };

        return $severity + min($this->elapsedMinutes, 999_999);
    }

    public function isActionable(): bool
    {
        return $this->actionStatus === 'actionable' && $this->actionUrl !== null;
    }

    /** @return array<string, mixed> a flat, view-safe representation. */
    public function toArray(): array
    {
        return [
            'visit_id' => $this->visitId,
            'patient_id' => $this->patientId,
            'patient_name' => $this->patientName,
            'visit_number' => $this->visitNumber,
            'stage' => $this->stage?->value,
            'stage_label' => $this->stage?->translatedLabel(),
            'cause' => $this->cause->value,
            'cause_label' => $this->cause->translatedLabel(),
            'cause_icon' => $this->cause->icon(),
            'severity' => $this->severity,
            'owner_type' => $this->ownerType,
            'owner_department_id' => $this->ownerDepartmentId,
            'owner_department_name' => $this->ownerDepartmentName,
            'action_label' => $this->actionLabel,
            'action_status' => $this->actionStatus,
            'action_url' => $this->actionUrl,
            'elapsed_minutes' => $this->elapsedMinutes,
            'waiting_since' => $this->waitingSince,
        ];
    }
}
