<?php

namespace App\Services\Journey;

use App\Data\Journey\JourneyAction;
use App\Enums\JourneyDelayCause;
use App\Models\User;
use App\Models\Visit;
use Throwable;

/**
 * Turns a Visit into a single actionable JourneyAction — reusing the Phase 9.1/9.2
 * services for stage, delay and cause, then deciding the action status
 * (open/actionable/blocked/resolved) and a safe deep link. Use only for displayed
 * rows; aggregation should use JourneyDelayCauseResolver::quickCause().
 */
class JourneyActionResolver
{
    private const LAB_PENDING = ['pending', 'requested', 'processing', 'sample_collected', 'received', 'accepted'];

    private const RX_PENDING = ['pending', 'billed', 'partially_billed'];

    public function __construct(
        private PatientJourneyService $journey,
        private JourneyDelayService $delays,
        private JourneyDelayCauseResolver $causes,
        private JourneyActionLinkResolver $links,
    ) {}

    public function resolve(Visit $visit, ?User $user = null): JourneyAction
    {
        $user ??= auth()->user();
        $snapshot = $this->journey->snapshot($visit);
        $delay = $this->delays->currentDelay($visit, $snapshot);
        $resolved = $this->causes->resolve($visit, $snapshot, $delay);
        $cause = $resolved['cause'];

        $status = $this->actionStatus($visit, $cause, $snapshot);
        $url = ($user !== null && in_array($status, ['actionable', 'open'], true))
            ? $this->links->resolve($cause, $visit, $user)
            : null;

        $patient = $visit->patient;

        return new JourneyAction(
            visitId: $visit->id,
            patientId: $visit->patient_id,
            patientName: $patient?->full_name ?? trim(($patient?->first_name ?? '').' '.($patient?->last_name ?? '')) ?: ('#'.$visit->patient_id),
            visitNumber: $visit->visit_number,
            stage: $snapshot['current_stage'],
            cause: $cause,
            severity: $delay['status'],
            ownerType: $resolved['owner_type'],
            ownerDepartmentId: $resolved['owner_department_id'],
            ownerDepartmentName: $resolved['owner_department_name'],
            actionLabel: $resolved['action_label'],
            actionStatus: $status,
            actionUrl: $url,
            elapsedMinutes: $delay['minutes'],
            waitingSince: $snapshot['entered_current_at'],
        );
    }

    private function actionStatus(Visit $visit, JourneyDelayCause $cause, array $snapshot): string
    {
        if ($snapshot['is_terminal'] || $snapshot['is_completed'] || $snapshot['current_stage'] === null) {
            return 'resolved';
        }

        return match ($cause) {
            JourneyDelayCause::AWAITING_LAB_RESULT,
            JourneyDelayCause::AWAITING_RADIOLOGY_RESULT => $this->exists($visit, 'labRequests', self::LAB_PENDING) ? 'actionable' : 'blocked',
            JourneyDelayCause::AWAITING_DISPENSING => $this->exists($visit, 'prescriptions', self::RX_PENDING) ? 'actionable' : 'blocked',
            JourneyDelayCause::AWAITING_BED => $this->exists($visit, 'admission') ? 'actionable' : 'blocked',
            JourneyDelayCause::AWAITING_PAYMENT => 'actionable',
            default => 'open',
        };
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

        try {
            $query = $visit->{$relation}();
            if ($statuses !== null) {
                $query->whereIn('status', $statuses);
            }

            return $query->exists();
        } catch (Throwable) {
            return false;
        }
    }
}
