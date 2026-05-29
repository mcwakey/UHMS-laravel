<?php

namespace App\Services;

use App\Enums\NotificationModule;
use App\Enums\NotificationPriority;
use App\Enums\ProcedureStatus;
use App\Enums\TheatreRoomStatus;
use App\Models\ProcedureRequest;
use App\Models\ProcedureSchedule;
use App\Models\TheatreRoom;
use App\Models\User;
use App\Services\TheatreRoomAvailabilityService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ProcedureScheduleService
{
    public function __construct(
        protected ProcedureWorkflowService $workflow,
        protected TheatreRoomAvailabilityService $availability,
        protected NotificationService $notifications,
    ) {}

    public function scheduleProcedure(ProcedureRequest $request, array $data, User $user): ProcedureSchedule
    {
        if (! $this->canScheduleFromCurrentStatus($request)) {
            throw new \RuntimeException("Cannot schedule: procedure status must be BILLED or RESCHEDULED (currently '{$request->status->value}').");
        }

        if (empty($data['scheduled_start'])) {
            throw new \InvalidArgumentException('Scheduled start datetime is required.');
        }

        $data = $this->normaliseScheduleData($data);
        $this->assertOverridePermission($data, $user);

        $this->availability->assertRoomCanBeScheduled(
            (int) $data['theatre_room_id'],
            $data['scheduled_start'],
            $data['scheduled_end'],
            null,
            (bool) ($data['override_room_conflict'] ?? false),
            $data['override_reason'] ?? null,
        );

        return DB::transaction(function () use ($request, $data, $user) {
            // Mark previous schedules as not current.
            $request->schedules()->where('is_current', true)->update(['is_current' => false]);

            $schedule = ProcedureSchedule::create([
                'procedure_request_id' => $request->id,
                'theatre_room_id'      => $data['theatre_room_id'] ?? null,
                'scheduled_start'      => $data['scheduled_start'],
                'scheduled_end'        => $data['scheduled_end'] ?? null,
                'expected_duration_minutes' => $data['expected_duration_minutes'] ?? null,
                'surgeon_id'           => $data['surgeon_id'] ?? null,
                'anaesthetist_id'      => $data['anaesthetist_id'] ?? null,
                'assistant_surgeon_id' => $data['assistant_surgeon_id'] ?? null,
                'theatre_nurse_ids'    => $data['theatre_nurse_ids'] ?? null,
                'required_equipment'   => $data['required_equipment'] ?? null,
                'status'               => 'scheduled',
                'notes'                => $data['notes'] ?? null,
                'override_reason'      => $data['override_reason'] ?? null,
                'scheduled_by'         => $user->id,
                'scheduled_at'         => now(),
                'is_current'           => true,
            ]);

            $this->markRoomScheduled((int) $data['theatre_room_id']);
            $this->markRequestScheduled($request, $user, 'Procedure scheduled.');

            $fresh = $schedule->fresh();
            $this->notifyScheduled($request, $fresh, $user);

            return $fresh;
        });
    }

    public function reschedule(ProcedureRequest $request, array $data, User $user, string $reason): ProcedureSchedule
    {
        if (trim($reason) === '') {
            throw new \InvalidArgumentException('Reschedule reason is required.');
        }

        // Allow reschedule from SCHEDULED only.
        if ($request->status !== ProcedureStatus::SCHEDULED) {
            throw new \RuntimeException("Cannot reschedule: status must be SCHEDULED (currently '{$request->status->value}').");
        }

        $data = $this->normaliseScheduleData($data);
        $this->assertOverridePermission($data, $user);

        $currentScheduleId = $request->schedule?->id;
        $this->availability->assertRoomCanBeScheduled(
            (int) $data['theatre_room_id'],
            $data['scheduled_start'],
            $data['scheduled_end'],
            $currentScheduleId,
            (bool) ($data['override_room_conflict'] ?? false),
            $data['override_reason'] ?? null,
        );

        return DB::transaction(function () use ($request, $data, $user, $reason) {
            // Mark current schedule as rescheduled.
            $request->schedules()->where('is_current', true)->update([
                'is_current' => false,
                'status'     => 'rescheduled',
                'notes'      => trim(($request->schedule?->notes ?? '') . "\nRescheduled: " . $reason),
            ]);

            $schedule = ProcedureSchedule::create([
                'procedure_request_id' => $request->id,
                'theatre_room_id'      => $data['theatre_room_id'] ?? null,
                'scheduled_start'      => $data['scheduled_start'],
                'scheduled_end'        => $data['scheduled_end'] ?? null,
                'expected_duration_minutes' => $data['expected_duration_minutes'] ?? null,
                'surgeon_id'           => $data['surgeon_id'] ?? null,
                'anaesthetist_id'      => $data['anaesthetist_id'] ?? null,
                'assistant_surgeon_id' => $data['assistant_surgeon_id'] ?? null,
                'theatre_nurse_ids'    => $data['theatre_nurse_ids'] ?? null,
                'required_equipment'   => $data['required_equipment'] ?? null,
                'status'               => 'scheduled',
                'notes'                => $data['notes'] ?? null,
                'override_reason'      => $data['override_reason'] ?? null,
                'scheduled_by'         => $user->id,
                'scheduled_at'         => now(),
                'is_current'           => true,
            ]);

            $this->markRoomScheduled((int) $data['theatre_room_id']);

            // Log on the request without changing status.
            $this->workflow->logStatusChange(
                $request,
                ProcedureStatus::SCHEDULED,
                ProcedureStatus::SCHEDULED,
                $user,
                'Rescheduled: ' . $reason
            );

            return $schedule->fresh();
        });
    }

    private function canScheduleFromCurrentStatus(ProcedureRequest $request): bool
    {
        if (in_array($request->status, [ProcedureStatus::BILLED, ProcedureStatus::RESCHEDULED], true)) {
            return true;
        }

        return $request->status === ProcedureStatus::ACCEPTED
            && ($request->is_emergency || $request->priority === 'emergency');
    }

    private function normaliseScheduleData(array $data): array
    {
        if (empty($data['theatre_room_id'])) {
            throw new \InvalidArgumentException('Theatre room is required.');
        }

        $start = Carbon::parse($data['scheduled_start']);
        $duration = max(1, (int) ($data['expected_duration_minutes'] ?? 60));
        $end = ! empty($data['scheduled_end'])
            ? Carbon::parse($data['scheduled_end'])
            : $start->copy()->addMinutes($duration);

        if ($end->lessThanOrEqualTo($start)) {
            throw new \InvalidArgumentException('Scheduled end time must be after the start time.');
        }

        $data['scheduled_start'] = $start;
        $data['scheduled_end'] = $end;
        $data['expected_duration_minutes'] = $start->diffInMinutes($end);
        $data['override_room_conflict'] = (bool) ($data['override_room_conflict'] ?? false);

        return $data;
    }

    private function assertOverridePermission(array $data, User $user): void
    {
        if (($data['override_room_conflict'] ?? false) && ! $user->can('theatre.schedule.override')) {
            throw new \RuntimeException('You are not allowed to override theatre room conflicts.');
        }
    }

    private function markRoomScheduled(int $roomId): void
    {
        TheatreRoom::whereKey($roomId)->update(['status' => TheatreRoomStatus::SCHEDULED->value]);
    }

    private function markRequestScheduled(ProcedureRequest $request, User $user, string $reason): void
    {
        if ($request->status === ProcedureStatus::ACCEPTED && ($request->is_emergency || $request->priority === 'emergency')) {
            $from = $request->status;
            $request->forceFill(['status' => ProcedureStatus::SCHEDULED])->save();
            $this->workflow->logStatusChange($request, $from, ProcedureStatus::SCHEDULED, $user, $reason . ' Emergency scheduling allowed before payment.');

            return;
        }

        $this->workflow->transition($request, ProcedureStatus::SCHEDULED, $user, $reason);
    }

    protected function notifyScheduled(ProcedureRequest $request, ProcedureSchedule $schedule, User $actor): void
    {
        $request->loadMissing(['patient', 'service']);
        $patient = $request->patient;
        $patientName = $patient ? trim($patient->first_name . ' ' . $patient->last_name) : 'patient';
        $serviceName = $request->service?->name ?? 'Procedure';
        $when = $schedule->scheduled_start
            ? Carbon::parse($schedule->scheduled_start)->format('D, d M Y H:i')
            : 'TBD';

        $base = [
            'title' => 'New theatre case scheduled',
            'module' => NotificationModule::THEATRE,
            'priority' => $request->is_emergency || $request->priority === 'emergency'
                ? NotificationPriority::URGENT
                : NotificationPriority::HIGH,
            'source_type' => 'procedure_schedule',
            'source_id' => $schedule->id,
            'patient_id' => $request->patient_id,
            'action_url' => $this->scheduleUrl(),
            'metadata' => [
                'procedure_request_id' => $request->id,
                'theatre_room_id' => $schedule->theatre_room_id,
                'scheduled_start' => (string) $schedule->scheduled_start,
            ],
        ];

        $recipientIds = array_filter([
            $schedule->surgeon_id,
            $schedule->anaesthetist_id,
            $schedule->assistant_surgeon_id,
        ]);
        $nurseIds = is_array($schedule->theatre_nurse_ids) ? $schedule->theatre_nurse_ids : [];
        $recipientIds = array_unique(array_merge($recipientIds, $nurseIds));
        $recipientIds = array_diff($recipientIds, [$actor->id]);

        if (! empty($recipientIds)) {
            $users = User::query()->whereIn('id', $recipientIds)->get();
            foreach ($users as $user) {
                $this->notifications->notifyUser($user, array_merge($base, [
                    'message' => sprintf('You are scheduled for %s (%s) at %s.', $serviceName, $patientName, $when),
                ]));
            }
        }
    }

    protected function scheduleUrl(): string
    {
        foreach (['admin.theatre.calendar', 'admin.theatre.board'] as $name) {
            try {
                return route($name);
            } catch (\Throwable $e) {
                continue;
            }
        }
        return '#';
    }
}
