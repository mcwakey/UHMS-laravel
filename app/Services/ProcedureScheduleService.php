<?php

namespace App\Services;

use App\Enums\ProcedureStatus;
use App\Models\ProcedureRequest;
use App\Models\ProcedureSchedule;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ProcedureScheduleService
{
    public function __construct(
        protected ProcedureWorkflowService $workflow,
    ) {}

    public function scheduleProcedure(ProcedureRequest $request, array $data, User $user): ProcedureSchedule
    {
        if ($request->status !== ProcedureStatus::BILLED && $request->status !== ProcedureStatus::RESCHEDULED) {
            throw new \RuntimeException("Cannot schedule: procedure status must be BILLED (currently '{$request->status->value}').");
        }

        if (empty($data['scheduled_start'])) {
            throw new \InvalidArgumentException('Scheduled start datetime is required.');
        }

        return DB::transaction(function () use ($request, $data, $user) {
            // Mark previous schedules as not current.
            $request->schedules()->where('is_current', true)->update(['is_current' => false]);

            $schedule = ProcedureSchedule::create([
                'procedure_request_id' => $request->id,
                'theatre_room_id'      => $data['theatre_room_id'] ?? null,
                'scheduled_start'      => $data['scheduled_start'],
                'scheduled_end'        => $data['scheduled_end'] ?? null,
                'surgeon_id'           => $data['surgeon_id'] ?? null,
                'anaesthetist_id'      => $data['anaesthetist_id'] ?? null,
                'assistant_surgeon_id' => $data['assistant_surgeon_id'] ?? null,
                'theatre_nurse_ids'    => $data['theatre_nurse_ids'] ?? null,
                'required_equipment'   => $data['required_equipment'] ?? null,
                'status'               => 'scheduled',
                'notes'                => $data['notes'] ?? null,
                'scheduled_by'         => $user->id,
                'scheduled_at'         => now(),
                'is_current'           => true,
            ]);

            $this->workflow->transition($request, ProcedureStatus::SCHEDULED, $user, 'Procedure scheduled.');

            return $schedule->fresh();
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
                'surgeon_id'           => $data['surgeon_id'] ?? null,
                'anaesthetist_id'      => $data['anaesthetist_id'] ?? null,
                'assistant_surgeon_id' => $data['assistant_surgeon_id'] ?? null,
                'theatre_nurse_ids'    => $data['theatre_nurse_ids'] ?? null,
                'required_equipment'   => $data['required_equipment'] ?? null,
                'status'               => 'scheduled',
                'notes'                => $data['notes'] ?? null,
                'scheduled_by'         => $user->id,
                'scheduled_at'         => now(),
                'is_current'           => true,
            ]);

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
}
