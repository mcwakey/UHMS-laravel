<?php

namespace App\Services\Consultation;

use App\Models\ConsultationTask;
use App\Models\MedicalRecord;
use App\Models\User;
use App\Models\VisitConsultationRoute;
use App\Services\ClinicalFrequencyOptionService;
use App\Services\ConsultationContributorService;
use App\Services\MedicalRecordEntryLogService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ConsultationTaskFrequencyExpansionService
{
    public function __construct(
        private readonly ClinicalFrequencyOptionService $frequencies,
        private readonly ConsultationContributorService $contributors,
        private readonly MedicalRecordEntryLogService $entryLogs,
    ) {}

    /**
     * @return Collection<int, ConsultationTask>
     */
    public function createTasks(MedicalRecord $record, VisitConsultationRoute $route, array $data, User $user): Collection
    {
        $frequency = strtoupper((string) ($data['frequency'] ?? 'OD'));
        $count = $this->frequencies->taskInstanceCount($frequency);
        $isPrn = $this->frequencies->isPrn($frequency);
        $start = $this->startAt($data);
        $spacingHours = $this->spacingHours($frequency);

        $tasks = collect();
        for ($i = 0; $i < $count; $i++) {
            $scheduledAt = $isPrn ? null : $start->copy()->addHours($spacingHours * $i);
            $task = $record->tasks()->create([
                'consultation_route_id' => $route->id,
                'visit_id' => $record->visit_id,
                'patient_id' => $record->patient_id,
                'department_id' => $record->department_id,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'priority' => $data['priority'] ?? 'medium',
                'status' => 'pending',
                'assigned_to' => $data['assigned_to'] ?? null,
                'due_date' => $scheduledAt?->toDateString() ?? ($data['due_date'] ?? null),
                'scheduled_at' => $scheduledAt,
                'frequency' => $frequency,
                'is_prn' => $isPrn,
                'created_by' => $user->id,
            ]);

            $this->entryLogs->created($task, $user);
            $tasks->push($task);
        }

        $this->contributors->recordContribution($record, $user, 'Task');

        return $tasks;
    }

    private function startAt(array $data): Carbon
    {
        if (! empty($data['start_at'])) {
            return Carbon::parse($data['start_at']);
        }

        if (! empty($data['due_date'])) {
            return Carbon::parse($data['due_date'])->setTimeFrom(now());
        }

        return now();
    }

    private function spacingHours(string $frequency): int
    {
        return match ($frequency) {
            'Q4H' => 4,
            'Q6H', 'QID', 'QDS' => 6,
            'Q8H', 'TID', 'TDS', 'AC', 'PC', 'WITH_MEALS' => 8,
            'Q12H', 'BID', 'BD' => 12,
            default => 0,
        };
    }
}
