<?php

namespace App\Services\FrontDesk;

use App\Enums\FrontDesk\CourierHandoffAction;
use App\Enums\FrontDesk\CourierHandoverStatus;
use App\Enums\FrontDesk\CourierStatus;
use App\Enums\LogModule;
use App\Models\FrontDeskCourierHandoff;
use App\Models\FrontDeskCourierLog;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Courier log workflow: log an item, edit it, and mark it delivered. All writes
 * are audit-logged through {@see ActivityLogService}.
 */
class CourierLogService
{
    public function __construct(private ActivityLogService $activityLog) {}

    public function create(array $data, User $actor): FrontDeskCourierLog
    {
        $data['received_or_sent_at'] = $data['received_or_sent_at'] ?? now();
        $data['handover_status'] = $data['handover_status'] ?? CourierHandoverStatus::AWAITING_HANDOVER->value;
        $data = $this->stampActor($data, $actor);

        $log = FrontDeskCourierLog::create($data);

        $this->recordHandoff($log, CourierHandoffAction::RECEIVED, $actor, ['from_user_id' => $actor->id]);

        $this->activityLog->log(LogModule::FRONT_DESK, 'FRONT_DESK_COURIER_CREATED', $this->context($log), $log);

        return $log;
    }

    public function update(FrontDeskCourierLog $log, array $data, User $actor): FrontDeskCourierLog
    {
        $previousStatus = $log->status;

        $log->fill($data)->save();
        $log->refresh();

        $this->activityLog->log(LogModule::FRONT_DESK, 'FRONT_DESK_COURIER_UPDATED', $this->context($log), $log);

        if ($previousStatus !== $log->status && $log->status === CourierStatus::CANCELLED) {
            $this->activityLog->log(LogModule::FRONT_DESK, 'FRONT_DESK_COURIER_CANCELLED', $this->context($log), $log);
        }

        return $log;
    }

    /**
     * Dispatch an item: mark it in transit. Cannot dispatch a delivered, lost or
     * cancelled record (an explicit update route may reopen those).
     *
     * @param  array{dispatch_department_id?: int|null, note?: string|null}  $data
     */
    public function dispatch(FrontDeskCourierLog $log, array $data, User $actor): FrontDeskCourierLog
    {
        if (in_array($log->status, [CourierStatus::DELIVERED, CourierStatus::CANCELLED, CourierStatus::LOST], true)) {
            throw ValidationException::withMessages([
                'status' => __('front_desk.errors.cannot_dispatch_closed'),
            ]);
        }

        $log->fill([
            'status' => CourierStatus::DISPATCHED->value,
            'handover_status' => CourierHandoverStatus::IN_TRANSIT->value,
            'dispatched_at' => now(),
            'dispatched_by' => $actor->id,
            'dispatch_department_id' => $data['dispatch_department_id'] ?? $log->dispatch_department_id,
        ])->save();

        $this->recordHandoff($log, CourierHandoffAction::DISPATCHED, $actor, [
            'from_user_id' => $actor->id,
            'to_department_id' => $data['dispatch_department_id'] ?? null,
            'note' => $data['note'] ?? null,
        ]);

        $this->activityLog->log(LogModule::FRONT_DESK, 'FRONT_DESK_COURIER_DISPATCHED', $this->context($log), $log);

        return $log->refresh();
    }

    /**
     * Record a custody handover to an internal recipient / department.
     *
     * @param  array{to_user_id?: int|null, to_department_id?: int|null, received_internally_by?: int|null, note?: string|null}  $data
     */
    public function handOver(FrontDeskCourierLog $log, array $data, User $actor): FrontDeskCourierLog
    {
        $log->fill([
            'handover_status' => CourierHandoverStatus::HANDED_OVER->value,
            'received_internally_by' => $data['received_internally_by'] ?? $data['to_user_id'] ?? $log->received_internally_by,
        ])->save();

        $this->recordHandoff($log, CourierHandoffAction::HANDED_OVER, $actor, [
            'from_user_id' => $actor->id,
            'to_user_id' => $data['to_user_id'] ?? null,
            'to_department_id' => $data['to_department_id'] ?? null,
            'note' => $data['note'] ?? null,
        ]);

        $this->activityLog->log(LogModule::FRONT_DESK, 'FRONT_DESK_COURIER_HANDED_OVER', $this->context($log), $log);

        return $log->refresh();
    }

    public function markDelivered(
        FrontDeskCourierLog $log,
        User $actor,
        ?Carbon $deliveredAt = null,
        ?string $deliveryNote = null,
        ?string $proofReference = null
    ): FrontDeskCourierLog {
        if ($log->status === CourierStatus::DELIVERED) {
            throw ValidationException::withMessages([
                'status' => __('front_desk.errors.already_delivered'),
            ]);
        }

        if (in_array($log->status, [CourierStatus::RETURNED, CourierStatus::LOST, CourierStatus::CANCELLED], true)) {
            throw ValidationException::withMessages([
                'status' => __('front_desk.errors.cannot_deliver_closed'),
            ]);
        }

        $metadata = $log->metadata ?? [];
        if ($deliveryNote !== null && trim($deliveryNote) !== '') {
            $metadata['delivery_note'] = trim($deliveryNote);
        }

        $log->fill([
            'status' => CourierStatus::DELIVERED->value,
            'handover_status' => CourierHandoverStatus::DELIVERED->value,
            'delivered_at' => $deliveredAt ?? now(),
            'proof_reference' => $proofReference ?: $log->proof_reference,
            'metadata' => $metadata ?: null,
        ])->save();

        $this->recordHandoff($log, CourierHandoffAction::DELIVERED, $actor, ['note' => $deliveryNote]);

        $this->activityLog->log(LogModule::FRONT_DESK, 'FRONT_DESK_COURIER_DELIVERED', $this->context($log), $log);

        if (($proofReference && trim($proofReference) !== '') || ($deliveryNote && trim($deliveryNote) !== '')) {
            $this->activityLog->log(LogModule::FRONT_DESK, 'FRONT_DESK_COURIER_DELIVERY_PROOF_RECORDED', $this->context($log), $log);
        }

        return $log->refresh();
    }

    public function markReturned(FrontDeskCourierLog $log, User $actor, ?string $reason = null): FrontDeskCourierLog
    {
        if ($log->status === CourierStatus::DELIVERED) {
            throw ValidationException::withMessages([
                'status' => __('front_desk.errors.cannot_return_delivered'),
            ]);
        }

        $metadata = $log->metadata ?? [];
        if ($reason !== null && trim($reason) !== '') {
            $metadata['return_reason'] = trim($reason);
        }

        $log->fill([
            'status' => CourierStatus::RETURNED->value,
            'handover_status' => CourierHandoverStatus::RETURNED->value,
            'metadata' => $metadata ?: null,
        ])->save();

        $this->recordHandoff($log, CourierHandoffAction::RETURNED, $actor, ['note' => $reason]);

        $this->activityLog->log(LogModule::FRONT_DESK, 'FRONT_DESK_COURIER_RETURNED', $this->context($log), $log);

        return $log->refresh();
    }

    /**
     * Append a custody event to the handover trail.
     *
     * @param  array<string, mixed>  $extra
     */
    private function recordHandoff(FrontDeskCourierLog $log, CourierHandoffAction $action, User $actor, array $extra = []): FrontDeskCourierHandoff
    {
        return FrontDeskCourierHandoff::create([
            'courier_log_id' => $log->id,
            'from_user_id' => $extra['from_user_id'] ?? null,
            'to_user_id' => $extra['to_user_id'] ?? null,
            'from_department_id' => $extra['from_department_id'] ?? null,
            'to_department_id' => $extra['to_department_id'] ?? null,
            'action' => $action->value,
            'action_at' => now(),
            'note' => (isset($extra['note']) && trim((string) $extra['note']) !== '') ? trim((string) $extra['note']) : null,
            'created_by' => $actor->id,
        ]);
    }

    /**
     * Stamp the acting user onto the direction-appropriate handler column when
     * the caller didn't supply one.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function stampActor(array $data, User $actor): array
    {
        if (($data['direction'] ?? 'incoming') === 'outgoing') {
            $data['sent_by'] = $data['sent_by'] ?? $actor->id;
        } else {
            $data['received_by'] = $data['received_by'] ?? $actor->id;
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function context(FrontDeskCourierLog $log): array
    {
        return [
            'patient_id' => $log->related_patient_id,
            'department_id' => $log->recipient_department_id,
            'metadata' => array_filter([
                'front_desk_courier_log_id' => $log->id,
                'direction' => $log->direction?->value,
                'courier_type' => $log->courier_type?->value,
                'status' => $log->status?->value,
                'handover_status' => $log->handover_status?->value,
            ], fn ($value) => $value !== null),
        ];
    }
}
