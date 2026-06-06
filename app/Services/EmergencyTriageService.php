<?php

namespace App\Services;

use App\Enums\TriageScore;
use App\Models\ClinicalTask;
use App\Models\EmergencyCase;
use App\Models\Triage;
use App\Models\User;
use App\Models\Vital;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EmergencyTriageService
{
    public function __construct(
        private EmergencyTimelineService $timeline,
        private EmergencyTriageScoringService $scoring,
        private EmergencySessionService $sessions,
    ) {}

    public function record(EmergencyCase $case, array $data, User $user): EmergencyCase
    {
        return DB::transaction(function () use ($case, $data, $user) {
            $session = $this->sessions->getOrCreateForCase($case, $user);
            $flags = $this->triageFlags($data);
            $result = $this->scoring->calculateFromVitals($this->vitalPayload($data), $flags);
            $finalCategory = $data['final_triage_category'] ?? $data['triage_category'] ?? $result->category;
            $overrideReason = trim((string) ($data['triage_override_reason'] ?? ''));

            if ($finalCategory !== $result->category && $overrideReason === '') {
                throw ValidationException::withMessages([
                    'triage_override_reason' => 'An override reason is required when final triage differs from the automated category.',
                ]);
            }

            $case->update([
                'chief_complaint' => $data['chief_complaint'] ?? $case->chief_complaint,
                'triage_category' => $finalCategory,
                'auto_triage_category' => $result->category,
                'final_triage_category' => $finalCategory,
                'triage_score' => $data['triage_score'] ?? $result->score,
                'triage_notes' => $data['triage_notes'] ?? null,
                'triage_override_reason' => $overrideReason ?: null,
                'triage_reasons' => $result->reasons,
                'triage_warnings' => $result->warnings,
                'avpu' => $flags['avpu'] ?? null,
                'pain_score' => $flags['pain_score'] ?? null,
                'danger_signs' => $flags['danger_signs'] ?? [],
                'emergency_status' => $finalCategory === EmergencyCase::TRIAGE_BLACK
                    ? EmergencyCase::STATUS_READY_FOR_DISPOSITION
                    : EmergencyCase::STATUS_TRIAGED,
                'triaged_by' => $user->id,
                'triaged_at' => now(),
            ]);

            if ($this->hasVitals($data)) {
                $vital = Vital::create(array_merge($this->vitalPayload($data), [
                    'visit_id' => $case->visit_id,
                    'emergency_case_id' => $case->id,
                    'emergency_session_id' => $session->id,
                    'medical_record_id' => $session->medical_record_id,
                    'consultation_route_id' => $session->consultation_route_id,
                    'patient_id' => $case->patient_id,
                    'recorded_by' => $user->id,
                    'monitoring_context' => 'EMERGENCY_TRIAGE',
                    'recorded_at' => now(),
                ]));

                $this->timeline->record($case, 'VITALS_RECORDED', 'Emergency triage vitals recorded', $vital->blood_pressure ?: null, $vital, $user);
            }

            // Mirror the triage onto the visit's Triage record so it surfaces in the
            // "Triage Assessment" card on the visit/consultation page, exactly like a
            // normal OPD triage (VisitService::processTriage).
            $this->syncVisitTriage($case, $data, $user, $finalCategory);

            $this->sessions->recordContribution($case, $user, 'Triage');
            $this->createMonitoringTask($case->fresh(), $user, $session->id);
            $this->timeline->record($case->fresh(), 'TRIAGE', 'Emergency triage completed', $finalCategory.' category', $case, $user);

            // Activity log → patient timeline. An override (final differs from the
            // automated category) is a clinically significant, reason-bearing event.
            $isOverride = $finalCategory !== $result->category;
            app(\App\Services\ActivityLogService::class)->log(
                \App\Enums\LogModule::EMERGENCY,
                $isOverride ? 'TRIAGE_OVERRIDDEN' : 'TRIAGE_RECORDED',
                $case->toActivityContext() + array_filter([
                    'reason' => $isOverride ? ($overrideReason ?: null) : null,
                    'severity' => $isOverride ? \App\Enums\LogSeverity::WARNING : \App\Enums\LogSeverity::INFO,
                    'old_values' => $isOverride ? ['triage_category' => $result->category] : null,
                    'new_values' => ['triage_category' => $finalCategory],
                    'metadata' => ['triage_score' => $data['triage_score'] ?? $result->score],
                    'causer' => $user,
                ], fn ($v) => $v !== null),
                $case,
                $isOverride
                    ? "Triage overridden: {$result->category} → {$finalCategory}"
                    : "Triage recorded: {$finalCategory}",
            );

            return $case->fresh(['patient', 'visit', 'triagedBy', 'latestVitals']);
        });
    }

    /**
     * Create/update the visit-level Triage record (and visit triage_score) so the
     * emergency triage shows in the visit page's "Triage Assessment" card, just
     * like a consultation-flow triage.
     */
    private function syncVisitTriage(EmergencyCase $case, array $data, User $user, string $finalCategory): void
    {
        $visit = $case->visit;
        if (! $visit) {
            return;
        }

        $vitals = $this->vitalPayload($data);
        $weight = $vitals['weight'] ?: null;
        $height = $vitals['height'] ?: null;
        $bmi = ($weight && $height) ? round((float) $weight / (((float) $height / 100) ** 2), 1) : null;
        $mappedScore = $this->mapCategoryToTriageScore($finalCategory);
        $departmentId = $case->activeEmergencySession?->department_id ?: $visit->current_department_id;

        Triage::updateOrCreate(
            ['visit_id' => $visit->id],
            [
                'patient_id' => $case->patient_id,
                'blood_pressure_systolic' => $vitals['blood_pressure_systolic'] ?: null,
                'blood_pressure_diastolic' => $vitals['blood_pressure_diastolic'] ?: null,
                'heart_rate' => $vitals['heart_rate'] ?: null,
                'temperature' => $vitals['temperature'] ?: null,
                'respiratory_rate' => $vitals['respiratory_rate'] ?: null,
                'spo2' => $vitals['spo2'] ?: null,
                'weight' => $weight,
                'height' => $height,
                'bmi' => $bmi,
                'triage_score' => $mappedScore,
                'department_id' => $departmentId,
                'notes' => $data['triage_notes'] ?? null,
                'triaged_by' => $user->id,
                'triaged_at' => now(),
            ],
        );

        $visit->update(['triage_score' => $mappedScore]);
    }

    private function mapCategoryToTriageScore(string $category): string
    {
        return match ($category) {
            EmergencyCase::TRIAGE_GREEN => TriageScore::ROUTINE->value,
            EmergencyCase::TRIAGE_YELLOW => TriageScore::URGENT->value,
            default => TriageScore::EMERGENCY->value, // RED / ORANGE / BLACK
        };
    }

    private function createMonitoringTask(EmergencyCase $case, User $user, ?int $sessionId = null): void
    {
        $session = $sessionId ? $case->emergencySessions()->find($sessionId) : null;
        $minutes = match ($case->triage_category) {
            EmergencyCase::TRIAGE_RED => 15,
            EmergencyCase::TRIAGE_ORANGE => 30,
            EmergencyCase::TRIAGE_YELLOW => 60,
            default => null,
        };

        $lookup = [
            'task_type' => ClinicalTask::TYPE_VITALS_MONITORING,
            'source_type' => EmergencyCase::class,
            'source_id' => $case->id,
        ];

        if (! $minutes) {
            ClinicalTask::query()
                ->where($lookup)
                ->whereNotIn('status', [ClinicalTask::STATUS_COMPLETED, ClinicalTask::STATUS_CANCELLED])
                ->update([
                    'status' => ClinicalTask::STATUS_CANCELLED,
                    'notes' => 'Monitoring task cancelled after triage category was lowered.',
                ]);

            return;
        }

        $dueAt = now()->addMinutes($minutes);

        ClinicalTask::updateOrCreate($lookup, [
            'visit_id' => $case->visit_id,
            'emergency_case_id' => $case->id,
            'emergency_session_id' => $sessionId,
            'medical_record_id' => $session?->medical_record_id,
            'consultation_route_id' => $session?->consultation_route_id,
            'patient_id' => $case->patient_id,
            'title' => 'Repeat emergency vitals',
            'description' => "{$case->triage_category} emergency case monitoring.",
            'scheduled_at' => $dueAt,
            'due_at' => $dueAt,
            'status' => ClinicalTask::STATUS_SCHEDULED,
            'priority' => $case->current_triage_category === EmergencyCase::TRIAGE_RED ? 'critical' : 'high',
            'assigned_role' => 'Emergency Nurse',
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

    private function triageFlags(array $data): array
    {
        $dangerSigns = collect($data['danger_signs'] ?? [])
            ->filter()
            ->map(fn ($sign) => (string) $sign)
            ->values()
            ->all();

        foreach (['trauma', 'bleeding', 'seizure', 'respiratory_distress', 'pregnancy', 'shock', 'uncontrolled_bleeding', 'dead_on_arrival'] as $flag) {
            if (! empty($data[$flag])) {
                $dangerSigns[] = $flag;
            }
        }

        return [
            'avpu' => $data['avpu'] ?? null,
            'pain_score' => $data['pain_score'] ?? null,
            'danger_signs' => array_values(array_unique($dangerSigns)),
            'trauma' => $data['trauma'] ?? in_array('trauma', $dangerSigns, true),
            'bleeding' => $data['bleeding'] ?? in_array('bleeding', $dangerSigns, true),
            'seizure' => $data['seizure'] ?? in_array('seizure', $dangerSigns, true),
            'respiratory_distress' => $data['respiratory_distress'] ?? in_array('respiratory_distress', $dangerSigns, true),
            'pregnancy' => $data['pregnancy'] ?? in_array('pregnancy', $dangerSigns, true),
            'shock' => $data['shock'] ?? in_array('shock', $dangerSigns, true),
            'uncontrolled_bleeding' => $data['uncontrolled_bleeding'] ?? in_array('uncontrolled_bleeding', $dangerSigns, true),
            'dead_on_arrival' => $data['dead_on_arrival'] ?? in_array('dead_on_arrival', $dangerSigns, true),
        ];
    }
}
