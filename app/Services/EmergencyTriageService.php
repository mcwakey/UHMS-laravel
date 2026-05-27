<?php

namespace App\Services;

use App\Models\ClinicalTask;
use App\Models\EmergencyCase;
use App\Models\User;
use App\Models\Vital;
use Illuminate\Support\Facades\DB;

class EmergencyTriageService
{
    public function __construct(
        private EmergencyTimelineService $timeline,
        private ClinicalTaskService $tasks,
    ) {}

    public function record(EmergencyCase $case, array $data, User $user): EmergencyCase
    {
        return DB::transaction(function () use ($case, $data, $user) {
            $case->update([
                'chief_complaint' => $data['chief_complaint'] ?? $case->chief_complaint,
                'triage_category' => $data['triage_category'],
                'triage_score' => $data['triage_score'] ?? null,
                'triage_notes' => $data['triage_notes'] ?? null,
                'emergency_status' => $data['triage_category'] === EmergencyCase::TRIAGE_BLACK
                    ? EmergencyCase::STATUS_READY_FOR_DISPOSITION
                    : EmergencyCase::STATUS_TRIAGED,
                'triaged_by' => $user->id,
                'triaged_at' => now(),
            ]);

            if ($this->hasVitals($data)) {
                $vital = Vital::create(array_merge($this->vitalPayload($data), [
                    'visit_id' => $case->visit_id,
                    'emergency_case_id' => $case->id,
                    'patient_id' => $case->patient_id,
                    'recorded_by' => $user->id,
                    'monitoring_context' => 'EMERGENCY_TRIAGE',
                    'recorded_at' => now(),
                ]));

                $this->timeline->record($case, 'VITALS_RECORDED', 'Emergency triage vitals recorded', $vital->blood_pressure ?: null, $vital, $user);
            }

            $this->createMonitoringTask($case->fresh(), $user);
            $this->timeline->record($case->fresh(), 'TRIAGE', 'Emergency triage completed', $case->triage_category.' category', $case, $user);

            return $case->fresh(['patient', 'visit', 'triagedBy', 'latestVitals']);
        });
    }

    private function createMonitoringTask(EmergencyCase $case, User $user): void
    {
        $minutes = match ($case->triage_category) {
            EmergencyCase::TRIAGE_RED => 15,
            EmergencyCase::TRIAGE_ORANGE => 30,
            EmergencyCase::TRIAGE_YELLOW => 60,
            default => null,
        };

        if (! $minutes) {
            return;
        }

        $this->tasks->createTask([
            'visit_id' => $case->visit_id,
            'emergency_case_id' => $case->id,
            'patient_id' => $case->patient_id,
            'task_type' => ClinicalTask::TYPE_VITALS_MONITORING,
            'title' => 'Repeat emergency vitals',
            'description' => "{$case->triage_category} emergency case monitoring.",
            'scheduled_at' => now()->addMinutes($minutes),
            'due_at' => now()->addMinutes($minutes),
            'status' => ClinicalTask::STATUS_SCHEDULED,
            'priority' => $case->triage_category === EmergencyCase::TRIAGE_RED ? 'critical' : 'high',
            'assigned_role' => 'Emergency Nurse',
            'source_type' => EmergencyCase::class,
            'source_id' => $case->id,
        ]);
    }

    private function hasVitals(array $data): bool
    {
        return collect($this->vitalPayload($data))->filter(fn ($value) => $value !== null && $value !== '')->isNotEmpty();
    }

    private function vitalPayload(array $data): array
    {
        return [
            'blood_pressure_systolic' => $data['blood_pressure_systolic'] ?? null,
            'blood_pressure_diastolic' => $data['blood_pressure_diastolic'] ?? null,
            'heart_rate' => $data['heart_rate'] ?? null,
            'temperature' => $data['temperature'] ?? null,
            'respiratory_rate' => $data['respiratory_rate'] ?? null,
            'spo2' => $data['spo2'] ?? null,
            'weight' => $data['weight'] ?? null,
            'height' => $data['height'] ?? null,
            'blood_sugar' => $data['blood_sugar'] ?? null,
            'notes' => $data['triage_notes'] ?? null,
        ];
    }
}
