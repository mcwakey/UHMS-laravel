<?php

namespace App\Services;

use App\Models\ClinicalTask;
use App\Models\MedicationAdministrationSchedule;
use App\Models\MedicationOrder;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MedicationScheduleService
{
    public function __construct(
        private MedicationFrequencyService $frequencies,
        private ClinicalTaskService $tasks,
        private MedicationAdministrationLogService $logs,
    ) {}

    public function generateForOrder(MedicationOrder $order): Collection
    {
        return DB::transaction(function () use ($order) {
            $order->loadMissing('frequency');

            if ($order->schedules()->exists()) {
                return $order->schedules()->with('clinicalTask')->orderBy('sequence_number')->get();
            }

            $frequency = $order->frequency;

            if (! $frequency || ! $frequency->requires_schedule) {
                return collect();
            }

            $scheduledTimes = $this->buildScheduleTimes($order);
            $created = collect();

            foreach ($scheduledTimes as $index => $scheduledAt) {
                $schedule = MedicationAdministrationSchedule::create([
                    'medication_order_id' => $order->id,
                    'visit_id' => $order->visit_id,
                    'admission_id' => $order->admission_id,
                    'emergency_case_id' => $order->emergency_case_id,
                    'emergency_session_id' => $order->emergency_session_id,
                    'patient_id' => $order->patient_id,
                    'scheduled_at' => $scheduledAt,
                    'dose' => $order->dose,
                    'dose_unit' => $order->dose_unit,
                    'route' => $order->route,
                    'status' => $frequency->is_stat ? MedicationAdministrationSchedule::STATUS_DUE : MedicationAdministrationSchedule::STATUS_SCHEDULED,
                    'sequence_number' => $index + 1,
                ]);

                $task = $this->tasks->createMedicationTaskForSchedule($schedule);
                if ($frequency->is_stat) {
                    $task->update(['status' => ClinicalTask::STATUS_DUE]);
                }

                $this->logs->record('SCHEDULE_GENERATED', $order, $schedule, null, null, null, [
                    'scheduled_at' => $scheduledAt->toDateTimeString(),
                    'sequence_number' => $schedule->sequence_number,
                ]);

                $created->push($schedule->fresh('clinicalTask'));
            }

            if ($created->isNotEmpty() && ! in_array($order->status, [
                MedicationOrder::STATUS_HELD,
                MedicationOrder::STATUS_STOPPED,
                MedicationOrder::STATUS_CANCELLED,
            ], true)) {
                $order->update(['status' => MedicationOrder::STATUS_ACTIVE_ADMINISTRATION]);
            }

            return $created;
        });
    }

    public function regenerateFutureSchedules(MedicationOrder $order, User $user, ?string $reason = null): Collection
    {
        return DB::transaction(function () use ($order, $user, $reason) {
            $this->cancelFutureSchedules($order, $user, $reason ?? 'Regenerating future medication schedules.');

            return $this->generateForOrder($order->fresh());
        });
    }

    public function cancelFutureSchedules(MedicationOrder $order, User $user, ?string $reason = null): void
    {
        $order->schedules()
            ->where('scheduled_at', '>', now())
            ->whereNotIn('status', [
                MedicationAdministrationSchedule::STATUS_GIVEN,
                MedicationAdministrationSchedule::STATUS_PARTIALLY_GIVEN,
                MedicationAdministrationSchedule::STATUS_CANCELLED,
                MedicationAdministrationSchedule::STATUS_VOIDED,
            ])
            ->with('clinicalTask')
            ->get()
            ->each(function (MedicationAdministrationSchedule $schedule) use ($order, $user, $reason) {
                $old = $schedule->only(['status']);

                $schedule->update(['status' => MedicationAdministrationSchedule::STATUS_CANCELLED]);

                if ($schedule->clinicalTask) {
                    $this->tasks->cancelTask($schedule->clinicalTask, $user, $reason);
                }

                $this->logs->record('ORDER_STOPPED', $order, $schedule, null, $user, $old, $schedule->only(['status']), $reason);
            });
    }

    private function buildScheduleTimes(MedicationOrder $order): array
    {
        $frequency = $order->frequency;
        $startAt = $order->start_at ? $order->start_at->copy() : now();
        $total = max(1, (int) $order->total_doses);

        if ($frequency->is_stat) {
            return [$startAt];
        }

        if ($frequency->interval_hours) {
            $times = [];
            $cursor = $startAt->copy();
            for ($i = 0; $i < $total; $i++) {
                $times[] = $cursor->copy();
                $cursor->addHours((int) $frequency->interval_hours);
            }

            return $times;
        }

        $defaultTimes = collect($frequency->default_times ?: ['08:00'])->values();
        $times = [];
        $day = $startAt->copy()->startOfDay();

        while (count($times) < $total) {
            foreach ($defaultTimes as $time) {
                [$hour, $minute] = array_pad(explode(':', (string) $time), 2, 0);
                $scheduledAt = $day->copy()->setTime((int) $hour, (int) $minute);

                if ($scheduledAt->lessThan($startAt)) {
                    continue;
                }

                $times[] = $scheduledAt;
                if (count($times) >= $total) {
                    break;
                }
            }

            $day->addDay();
        }

        return array_map(fn (Carbon $time) => $time, $times);
    }
}
