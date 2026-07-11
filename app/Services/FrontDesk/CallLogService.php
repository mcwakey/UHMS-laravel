<?php

namespace App\Services\FrontDesk;

use App\Enums\LogModule;
use App\Models\FrontDeskCallLog;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Validation\ValidationException;

/**
 * Call log workflow: log a call, edit it, and close out a required follow-up.
 * All writes are audit-logged through {@see ActivityLogService}.
 */
class CallLogService
{
    public function __construct(private ActivityLogService $activityLog) {}

    public function create(array $data, User $actor): FrontDeskCallLog
    {
        $data['handled_by'] = $actor->id;
        $data['started_at'] = $data['started_at'] ?? now();

        // A call flagged for follow-up defaults to a pending follow-up status.
        if (! empty($data['follow_up_required']) && empty($data['follow_up_status'])) {
            $data['follow_up_status'] = FrontDeskCallLog::FOLLOW_UP_PENDING;
        }

        $log = FrontDeskCallLog::create($data);

        $this->activityLog->log(LogModule::FRONT_DESK, 'FRONT_DESK_CALL_CREATED', $this->context($log), $log);

        return $log;
    }

    public function update(FrontDeskCallLog $log, array $data, User $actor): FrontDeskCallLog
    {
        if (array_key_exists('follow_up_required', $data) && ! $data['follow_up_required']) {
            $data['follow_up_status'] = null;
            $data['assigned_follow_up_user_id'] = null;
        } elseif (! empty($data['follow_up_required']) && empty($data['follow_up_status'])) {
            $data['follow_up_status'] = $log->follow_up_status ?? FrontDeskCallLog::FOLLOW_UP_PENDING;
        }

        $log->fill($data)->save();
        $log->refresh();

        $this->activityLog->log(LogModule::FRONT_DESK, 'FRONT_DESK_CALL_UPDATED', $this->context($log), $log);

        return $log;
    }

    /** Backward-compatible alias (Phase 18A route). */
    public function markFollowUpCompleted(FrontDeskCallLog $log, User $actor): FrontDeskCallLog
    {
        return $this->completeFollowUp($log, $actor);
    }

    /**
     * Flag a call for follow-up and put it on the callback queue.
     *
     * @param  array{assigned_follow_up_user_id?: int|null, follow_up_due_at?: mixed, follow_up_note?: string|null}  $data
     */
    public function assignFollowUp(FrontDeskCallLog $log, array $data, User $actor): FrontDeskCallLog
    {
        $log->fill([
            'follow_up_required' => true,
            'follow_up_status' => FrontDeskCallLog::FOLLOW_UP_PENDING,
            'assigned_follow_up_user_id' => $data['assigned_follow_up_user_id'] ?? $log->assigned_follow_up_user_id,
            'follow_up_due_at' => $data['follow_up_due_at'] ?? $log->follow_up_due_at,
            // Assigning re-opens a previously closed follow-up.
            'follow_up_completed_at' => null,
            'follow_up_completed_by' => null,
            'follow_up_cancelled_at' => null,
            'follow_up_cancelled_by' => null,
            'metadata' => $this->withMeta($log, ['follow_up_note' => $data['follow_up_note'] ?? null]),
        ])->save();

        $this->activityLog->log(LogModule::FRONT_DESK, 'FRONT_DESK_CALL_FOLLOWUP_ASSIGNED', $this->context($log), $log);

        return $log->refresh();
    }

    public function completeFollowUp(FrontDeskCallLog $log, User $actor, ?string $note = null): FrontDeskCallLog
    {
        if (! $log->follow_up_required) {
            throw ValidationException::withMessages([
                'follow_up' => __('front_desk.errors.follow_up_not_required'),
            ]);
        }

        $log->fill([
            'follow_up_status' => FrontDeskCallLog::FOLLOW_UP_COMPLETED,
            'follow_up_completed_at' => now(),
            'follow_up_completed_by' => $actor->id,
            'metadata' => $this->withMeta($log, ['completion_note' => $note]),
        ])->save();

        $this->activityLog->log(LogModule::FRONT_DESK, 'FRONT_DESK_CALL_FOLLOWUP_COMPLETED', $this->context($log), $log);

        return $log->refresh();
    }

    public function cancelFollowUp(FrontDeskCallLog $log, User $actor, ?string $reason = null): FrontDeskCallLog
    {
        $log->fill([
            'follow_up_status' => FrontDeskCallLog::FOLLOW_UP_CANCELLED,
            'follow_up_cancelled_at' => now(),
            'follow_up_cancelled_by' => $actor->id,
            'metadata' => $this->withMeta($log, ['cancellation_reason' => $reason]),
        ])->save();

        $this->activityLog->log(LogModule::FRONT_DESK, 'FRONT_DESK_CALL_FOLLOWUP_CANCELLED', $this->context($log), $log);

        return $log->refresh();
    }

    /**
     * Record that a call was transferred/assigned to a department and/or user.
     *
     * @param  array{transfer_department_id?: int|null, transferred_to_user_id?: int|null}  $data
     */
    public function transferCall(FrontDeskCallLog $log, array $data, User $actor): FrontDeskCallLog
    {
        $log->fill([
            'transfer_department_id' => $data['transfer_department_id'] ?? $log->transfer_department_id,
            'transferred_to_user_id' => $data['transferred_to_user_id'] ?? $log->transferred_to_user_id,
        ])->save();

        $this->activityLog->log(LogModule::FRONT_DESK, 'FRONT_DESK_CALL_TRANSFERRED', $this->context($log), $log);

        return $log->refresh();
    }

    /**
     * Merge non-null note keys into the metadata array without clobbering the
     * original call notes or other metadata. Never stores clinical data.
     *
     * @param  array<string, mixed>  $add
     * @return array<string, mixed>|null
     */
    private function withMeta(FrontDeskCallLog $log, array $add): ?array
    {
        $metadata = $log->metadata ?? [];
        foreach ($add as $key => $value) {
            if ($value !== null && trim((string) $value) !== '') {
                $metadata[$key] = trim((string) $value);
            }
        }

        return $metadata ?: null;
    }

    /**
     * @return array<string, mixed>
     */
    private function context(FrontDeskCallLog $log): array
    {
        return [
            'patient_id' => $log->related_patient_id,
            'department_id' => $log->department_id,
            'metadata' => array_filter([
                'front_desk_call_log_id' => $log->id,
                'direction' => $log->direction?->value,
                'category' => $log->category?->value,
                'outcome' => $log->outcome?->value,
                'follow_up_required' => $log->follow_up_required,
                'follow_up_status' => $log->follow_up_status,
                'assigned_user_id' => $log->assigned_follow_up_user_id,
            ], fn ($value) => $value !== null),
        ];
    }
}
