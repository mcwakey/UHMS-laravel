<?php

namespace App\Services\FrontDesk;

use App\Enums\FrontDesk\ShiftHandoverStatus;
use App\Enums\LogModule;
use App\Models\FrontDeskCallLog;
use App\Models\FrontDeskCourierLog;
use App\Models\FrontDeskIncidentLog;
use App\Models\FrontDeskLostFoundItem;
use App\Models\FrontDeskShiftHandover;
use App\Models\FrontDeskVisitorLog;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Validation\ValidationException;

/**
 * Shift handover workflow (Phase 18E). The snapshot carries only safe operational
 * counts — never clinical data or sensitive free text.
 */
class ShiftHandoverService
{
    public function __construct(private ActivityLogService $activityLog) {}

    public function createDraft(array $data, User $actor): FrontDeskShiftHandover
    {
        $snapshot = $this->buildOpenItemsSnapshot($actor);

        $handover = FrontDeskShiftHandover::create(array_merge([
            'shift_date' => $data['shift_date'] ?? now()->toDateString(),
            'handover_started_at' => now(),
            'outgoing_user_id' => $actor->id,
            'status' => ShiftHandoverStatus::DRAFT->value,
            'visitors_inside_count' => $snapshot['visitors_inside'],
            'pending_callbacks_count' => $snapshot['pending_callbacks'],
            'pending_couriers_count' => $snapshot['pending_couriers'],
            'open_incidents_count' => $snapshot['open_incidents'],
            'lost_found_unclaimed_count' => $snapshot['unclaimed_lost_found'],
            'open_items_snapshot' => $snapshot,
        ], array_intersect_key($data, array_flip(['shift_name', 'incoming_user_id', 'department_id', 'summary_notes']))));

        $this->activityLog->log(LogModule::FRONT_DESK, 'FRONT_DESK_HANDOVER_CREATED', $this->context($handover), $handover);

        return $handover;
    }

    public function update(FrontDeskShiftHandover $handover, array $data, User $actor): FrontDeskShiftHandover
    {
        if (! $handover->isDraft()) {
            throw ValidationException::withMessages(['status' => __('front_desk.errors.handover_locked')]);
        }

        $handover->fill(array_intersect_key($data, array_flip([
            'shift_name', 'shift_date', 'incoming_user_id', 'department_id', 'summary_notes',
        ])))->save();

        $this->activityLog->log(LogModule::FRONT_DESK, 'FRONT_DESK_HANDOVER_UPDATED', $this->context($handover), $handover);

        return $handover->refresh();
    }

    public function submit(FrontDeskShiftHandover $handover, User $actor, ?string $note = null): FrontDeskShiftHandover
    {
        if (! $handover->isDraft()) {
            throw ValidationException::withMessages(['status' => __('front_desk.errors.handover_not_draft')]);
        }

        $handover->update([
            'status' => ShiftHandoverStatus::SUBMITTED->value,
            'submitted_by' => $actor->id,
            'submitted_at' => now(),
            'summary_notes' => $note ?: $handover->summary_notes,
        ]);

        $this->activityLog->log(LogModule::FRONT_DESK, 'FRONT_DESK_HANDOVER_SUBMITTED', $this->context($handover), $handover);

        return $handover;
    }

    public function accept(FrontDeskShiftHandover $handover, User $actor, ?string $note = null): FrontDeskShiftHandover
    {
        if ($handover->status === ShiftHandoverStatus::CANCELLED) {
            throw ValidationException::withMessages(['status' => __('front_desk.errors.handover_cancelled')]);
        }
        if ($handover->status !== ShiftHandoverStatus::SUBMITTED) {
            throw ValidationException::withMessages(['status' => __('front_desk.errors.handover_not_submitted')]);
        }

        $metadata = $handover->metadata ?? [];
        if ($note !== null && trim($note) !== '') {
            $metadata['acceptance_note'] = trim($note);
        }

        $handover->update([
            'status' => ShiftHandoverStatus::ACCEPTED->value,
            'incoming_user_id' => $handover->incoming_user_id ?? $actor->id,
            'accepted_by' => $actor->id,
            'accepted_at' => now(),
            'handover_completed_at' => now(),
            'metadata' => $metadata ?: null,
        ]);

        $this->activityLog->log(LogModule::FRONT_DESK, 'FRONT_DESK_HANDOVER_ACCEPTED', $this->context($handover), $handover);

        return $handover;
    }

    public function cancel(FrontDeskShiftHandover $handover, User $actor, ?string $reason = null): FrontDeskShiftHandover
    {
        if ($handover->status === ShiftHandoverStatus::ACCEPTED) {
            throw ValidationException::withMessages(['status' => __('front_desk.errors.handover_accepted')]);
        }

        $metadata = $handover->metadata ?? [];
        if ($reason !== null && trim($reason) !== '') {
            $metadata['cancellation_reason'] = trim($reason);
        }

        $handover->update([
            'status' => ShiftHandoverStatus::CANCELLED->value,
            'cancelled_by' => $actor->id,
            'cancelled_at' => now(),
            'metadata' => $metadata ?: null,
        ]);

        $this->activityLog->log(LogModule::FRONT_DESK, 'FRONT_DESK_HANDOVER_CANCELLED', $this->context($handover), $handover);

        return $handover;
    }

    /**
     * Safe operational snapshot for the shift being handed over.
     *
     * @return array<string, int>
     */
    public function buildOpenItemsSnapshot(?User $actor = null): array
    {
        return [
            'visitors_inside' => FrontDeskVisitorLog::query()->currentlyInside()->count(),
            'overdue_visitors' => FrontDeskVisitorLog::query()->overdue()->count(),
            'pending_callbacks' => FrontDeskCallLog::query()->pendingCallback()->count(),
            'pending_couriers' => FrontDeskCourierLog::query()->pendingCourier()->count(),
            'in_transit_couriers' => FrontDeskCourierLog::query()->inTransit()->count(),
            'open_incidents' => FrontDeskIncidentLog::query()->open()->count(),
            'unclaimed_lost_found' => FrontDeskLostFoundItem::query()->unclaimed()->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function context(FrontDeskShiftHandover $handover): array
    {
        return [
            'department_id' => $handover->department_id,
            'metadata' => array_filter([
                'front_desk_shift_handover_id' => $handover->id,
                'status' => $handover->status?->value,
            ], fn ($v) => $v !== null),
        ];
    }
}
