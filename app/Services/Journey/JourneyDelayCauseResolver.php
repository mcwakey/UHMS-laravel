<?php

namespace App\Services\Journey;

use App\Enums\DepartmentType;
use App\Enums\JourneyDelayCause;
use App\Enums\PatientJourneyStage;
use App\Enums\VisitStatus;
use App\Models\Department;
use App\Models\Visit;
use Throwable;

/**
 * Explains WHY a patient is delayed and which department must act, from existing
 * records only. Two entry points:
 *
 *  - resolve()    full, single-patient (inspects related records) → cause + owner +
 *                 next action + severity (Phase 9.1 thresholds).
 *  - quickCause() query-free cause from (status, department type) — safe to call per
 *                 active visit during bottleneck aggregation.
 */
class JourneyDelayCauseResolver
{
    private const LAB_PENDING = ['pending', 'requested', 'processing', 'sample_collected', 'received', 'accepted'];

    private const RX_PENDING = ['pending', 'billed', 'partially_billed'];

    public function __construct(
        private PatientJourneyService $journey,
        private JourneyDelayService $delays,
    ) {}

    /**
     * @param  array<string,mixed>|null  $snapshot
     * @param  array<string,mixed>|null  $delay
     * @return array<string,mixed>
     */
    public function resolve(Visit $visit, ?array $snapshot = null, ?array $delay = null): array
    {
        $snapshot ??= $this->journey->snapshot($visit);
        $delay ??= $this->delays->currentDelay($visit, $snapshot);

        $stage = $snapshot['current_stage'];
        $status = $snapshot['current_status'];
        $deptType = $this->typeValue($snapshot['location']['department_type'] ?? $visit->currentDepartment?->type);

        $cause = $this->resolveCause($visit, $stage, $status, $deptType);
        $owner = $this->owner($visit, $cause);

        return [
            'cause' => $cause,
            'cause_label' => $cause->translatedLabel(),
            'owner_type' => $owner['type'],
            'owner_department_id' => $owner['id'],
            'owner_department_name' => $owner['name'],
            'action_label' => $cause->action(),
            'severity' => $delay['status'],
        ];
    }

    /** Query-free coarse cause for aggregation (status + department type). */
    public function quickCause(?VisitStatus $status, ?string $deptType): JourneyDelayCause
    {
        if ($status === VisitStatus::BILLING) {
            return JourneyDelayCause::AWAITING_PAYMENT;
        }

        return match ($status) {
            VisitStatus::QUEUED, VisitStatus::TRIAGE, VisitStatus::WAITING => JourneyDelayCause::AWAITING_CONSULTATION,
            VisitStatus::CONSULTING, VisitStatus::ACTIVE, VisitStatus::REFERRED_CONSULTATION, VisitStatus::EMERGENCY => JourneyDelayCause::AWAITING_CLINICAL_REVIEW,
            VisitStatus::WAITING_INVESTIGATION, VisitStatus::LAB => $deptType === DepartmentType::RADIOLOGY->value
                ? JourneyDelayCause::AWAITING_RADIOLOGY_RESULT
                : JourneyDelayCause::AWAITING_LAB_RESULT,
            VisitStatus::PHARMACY => JourneyDelayCause::AWAITING_DISPENSING,
            VisitStatus::ADMITTING => JourneyDelayCause::AWAITING_BED,
            VisitStatus::ADMITTED, VisitStatus::INPATIENT => JourneyDelayCause::AWAITING_CLINICAL_REVIEW,
            VisitStatus::DISCHARGING => JourneyDelayCause::AWAITING_DISCHARGE,
            default => JourneyDelayCause::UNKNOWN,
        };
    }

    private function resolveCause(Visit $visit, ?PatientJourneyStage $stage, ?VisitStatus $status, ?string $deptType): JourneyDelayCause
    {
        if ($stage === null) {
            return JourneyDelayCause::UNKNOWN;
        }
        if ($status === VisitStatus::BILLING) {
            return JourneyDelayCause::AWAITING_PAYMENT;
        }

        return match ($stage) {
            PatientJourneyStage::CONSULTATION => match ($status) {
                VisitStatus::CONSULTING, VisitStatus::ACTIVE, VisitStatus::REFERRED_CONSULTATION => JourneyDelayCause::AWAITING_CLINICAL_REVIEW,
                default => JourneyDelayCause::AWAITING_CONSULTATION,
            },
            PatientJourneyStage::INVESTIGATION => $this->investigationCause($visit, $deptType),
            PatientJourneyStage::PROCEDURE => JourneyDelayCause::AWAITING_PROCEDURE,
            PatientJourneyStage::PHARMACY => $this->pharmacyCause($visit),
            PatientJourneyStage::ADMISSION, PatientJourneyStage::DISCHARGE => $this->wardCause($visit, $status),
            default => $this->quickCause($status, $deptType),
        };
    }

    private function investigationCause(Visit $visit, ?string $deptType): JourneyDelayCause
    {
        $isRadiology = $deptType === DepartmentType::RADIOLOGY->value;
        $hasPending = $this->exists($visit, 'labRequests', self::LAB_PENDING);
        $hasAny = $this->exists($visit, 'labRequests');

        if ($isRadiology) {
            return $hasPending || $hasAny ? JourneyDelayCause::AWAITING_RADIOLOGY_RESULT : JourneyDelayCause::AWAITING_RADIOLOGY_REQUEST;
        }

        return $hasPending || $hasAny ? JourneyDelayCause::AWAITING_LAB_RESULT : JourneyDelayCause::AWAITING_LAB_REQUEST;
    }

    private function pharmacyCause(Visit $visit): JourneyDelayCause
    {
        if ($this->exists($visit, 'prescriptions', self::RX_PENDING)) {
            return JourneyDelayCause::AWAITING_DISPENSING;
        }
        if ($this->exists($visit, 'prescriptions')) {
            return JourneyDelayCause::AWAITING_DISPENSING;
        }

        return JourneyDelayCause::AWAITING_PRESCRIPTION;
    }

    private function wardCause(Visit $visit, ?VisitStatus $status): JourneyDelayCause
    {
        $admission = $this->admission($visit);

        if ($status === VisitStatus::DISCHARGING) {
            return JourneyDelayCause::AWAITING_DISCHARGE;
        }
        if ($status === VisitStatus::ADMITTING || ($admission && empty($admission->bed_id))) {
            return JourneyDelayCause::AWAITING_BED;
        }
        if ($admission === null) {
            return JourneyDelayCause::AWAITING_ADMISSION;
        }
        if ($admission->expected_discharge_date !== null && \Illuminate\Support\Carbon::parse($admission->expected_discharge_date)->isPast()) {
            return JourneyDelayCause::AWAITING_DISCHARGE;
        }

        return JourneyDelayCause::AWAITING_CLINICAL_REVIEW;
    }

    /** @return array{type:?string,id:?int,name:?string} */
    private function owner(Visit $visit, JourneyDelayCause $cause): array
    {
        $type = $cause->ownerType();

        if (in_array($cause, [
            JourneyDelayCause::AWAITING_LAB_REQUEST, JourneyDelayCause::AWAITING_LAB_RESULT,
            JourneyDelayCause::AWAITING_RADIOLOGY_REQUEST, JourneyDelayCause::AWAITING_RADIOLOGY_RESULT,
        ], true)) {
            $targetId = $visit->relationLoaded('labRequests')
                ? $visit->getRelation('labRequests')->sortByDesc('id')->first()?->target_department_id
                : $this->safe(fn () => $visit->labRequests()->latest('id')->value('target_department_id'));
            if ($targetId) {
                return ['type' => $type, 'id' => (int) $targetId, 'name' => Department::find($targetId)?->name];
            }
        }

        $current = $visit->currentDepartment;

        return ['type' => $type, 'id' => $current?->id, 'name' => $current?->name];
    }

    private function exists(Visit $visit, string $relation, ?array $statuses = null): bool
    {
        if ($visit->relationLoaded($relation)) {
            $value = $visit->getRelation($relation);
            $items = $value instanceof \Illuminate\Support\Collection ? $value : collect($value ? [$value] : []);
            if ($statuses !== null) {
                $items = $items->filter(fn ($record) => in_array((string) ($record->status ?? ''), $statuses, true));
            }

            return $items->isNotEmpty();
        }

        return (bool) $this->safe(function () use ($visit, $relation, $statuses) {
            $query = $visit->{$relation}();
            if ($statuses !== null) {
                $query->whereIn('status', $statuses);
            }

            return $query->exists();
        });
    }

    private function admission(Visit $visit): mixed
    {
        if ($visit->relationLoaded('admission')) {
            return $visit->getRelation('admission');
        }

        return $this->safe(fn () => $visit->admission()->first());
    }

    private function typeValue(mixed $type): ?string
    {
        return $type instanceof DepartmentType ? $type->value : (is_string($type) ? $type : null);
    }

    private function safe(callable $callback): mixed
    {
        try {
            return $callback();
        } catch (Throwable) {
            return null;
        }
    }
}
