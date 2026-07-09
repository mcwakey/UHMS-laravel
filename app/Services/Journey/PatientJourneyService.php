<?php

namespace App\Services\Journey;

use App\Enums\DepartmentType;
use App\Enums\PatientJourneyStage;
use App\Enums\VisitStatus;
use App\Models\Visit;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Throwable;

/**
 * Derives a patient's journey snapshot from EXISTING records only — the visit's
 * status, its timestamped status logs, and the presence of related operational
 * records (lab requests, prescriptions, procedures, admission). No new storage.
 *
 * Answers: where is the patient now, where have they been, what's next, and since
 * when (current location). Delay/duration logic lives in JourneyDelayService.
 */
class PatientJourneyService
{
    /**
     * @return array<string, mixed>
     */
    public function snapshot(Visit $visit): array
    {
        $status = $this->visitStatus($visit);
        $terminal = $status !== null && PatientJourneyStage::isTerminal($status);
        $current = $status !== null ? PatientJourneyStage::fromVisitStatus($status) : null;

        $logs = $visit->relationLoaded('statusLogs') ? $visit->statusLogs : $visit->statusLogs()->get();
        $enteredAt = $this->enteredCurrentStatusAt($visit, $status, $logs);
        $relevant = $this->relevantStages($visit);
        $touched = $this->touchedStages($visit, $logs);

        $completed = [];
        $next = null;
        if ($current !== null && ! $terminal && $current !== PatientJourneyStage::COMPLETED) {
            foreach ($relevant as $stage) {
                if ($stage->order() < $current->order()) {
                    $completed[] = $stage;
                } elseif ($stage->order() > $current->order() && $next === null) {
                    $next = $stage;
                }
            }
        }

        $department = $visit->currentDepartment;

        return [
            'visit_id' => $visit->id,
            'current_status' => $status,
            'current_stage' => $current,
            'is_terminal' => $terminal,
            'is_completed' => $current === PatientJourneyStage::COMPLETED,
            'completed_stages' => $completed,
            'next_stage' => $next,
            'relevant_stages' => $relevant,
            'touched_stages' => $touched,
            'started_at' => $visit->created_at ? Carbon::parse($visit->created_at) : null,
            'entered_current_at' => $enteredAt,
            'location' => [
                'department' => $department?->name,
                'department_type' => $department?->type,
                'since' => $enteredAt,
            ],
        ];
    }

    public function currentStage(Visit $visit): ?PatientJourneyStage
    {
        $status = $this->visitStatus($visit);

        return $status !== null ? PatientJourneyStage::fromVisitStatus($status) : null;
    }

    /** @return list<PatientJourneyStage> */
    public function completedStages(Visit $visit): array
    {
        return $this->snapshot($visit)['completed_stages'];
    }

    public function nextStage(Visit $visit): ?PatientJourneyStage
    {
        return $this->snapshot($visit)['next_stage'];
    }

    /** Current location: department, the type that owns it, and "since" timestamp. */
    public function location(Visit $visit): array
    {
        return $this->snapshot($visit)['location'];
    }

    private function visitStatus(Visit $visit): ?VisitStatus
    {
        $status = $visit->status;
        if ($status instanceof VisitStatus) {
            return $status;
        }

        return is_string($status) ? VisitStatus::tryFrom($status) : null;
    }

    private function enteredCurrentStatusAt(Visit $visit, ?VisitStatus $status, Collection $logs): ?Carbon
    {
        if ($status !== null) {
            $match = $logs->last(fn ($log) => (string) $log->to_status === $status->value);
            if ($match && $match->timestamp) {
                return Carbon::parse($match->timestamp);
            }
        }

        $last = $logs->last();
        if ($last && $last->timestamp) {
            return Carbon::parse($last->timestamp);
        }

        return $visit->created_at ? Carbon::parse($visit->created_at) : null;
    }

    /** @return list<PatientJourneyStage> the stages this visit actually passed through. */
    private function touchedStages(Visit $visit, Collection $logs): array
    {
        $stages = [];
        // Both endpoints of a transition were occupied — capture from_status too so
        // the initial stage (never a transition target) still reads as visited.
        foreach ($logs as $log) {
            foreach ([$log->from_status, $log->to_status] as $value) {
                $status = $value !== null ? VisitStatus::tryFrom((string) $value) : null;
                $stage = $status ? PatientJourneyStage::fromVisitStatus($status) : null;
                if ($stage) {
                    $stages[$stage->value] = $stage;
                }
            }
        }

        if ($this->has($visit, 'labRequests')) {
            $stages['investigation'] = PatientJourneyStage::INVESTIGATION;
        }
        if ($this->has($visit, 'procedureRequests')) {
            $stages['procedure'] = PatientJourneyStage::PROCEDURE;
        }
        if ($this->has($visit, 'prescriptions')) {
            $stages['pharmacy'] = PatientJourneyStage::PHARMACY;
        }
        if ($this->has($visit, 'admission')) {
            $stages['admission'] = PatientJourneyStage::ADMISSION;
        }

        return array_values($stages);
    }

    /** @return list<PatientJourneyStage> stages relevant to this visit, in order. */
    private function relevantStages(Visit $visit): array
    {
        $relevant = [
            PatientJourneyStage::REGISTERED,
            PatientJourneyStage::CHECKED_IN,
            PatientJourneyStage::TRIAGE,
            PatientJourneyStage::CONSULTATION,
        ];

        if ($this->has($visit, 'labRequests')) {
            $relevant[] = PatientJourneyStage::INVESTIGATION;
        }
        if ($this->has($visit, 'procedureRequests')) {
            $relevant[] = PatientJourneyStage::PROCEDURE;
        }
        $type = $visit->currentDepartment?->type;
        if (($type instanceof DepartmentType ? $type->value : $type) === DepartmentType::TREATMENT->value) {
            $relevant[] = PatientJourneyStage::TREATMENT;
        }
        if ($this->has($visit, 'prescriptions')) {
            $relevant[] = PatientJourneyStage::PHARMACY;
        }
        if ($this->has($visit, 'admission')) {
            $relevant[] = PatientJourneyStage::ADMISSION;
            $relevant[] = PatientJourneyStage::DISCHARGE;
        }
        $relevant[] = PatientJourneyStage::COMPLETED;

        usort($relevant, fn (PatientJourneyStage $a, PatientJourneyStage $b) => $a->order() <=> $b->order());

        return $relevant;
    }

    private function has(Visit $visit, string $relation): bool
    {
        // Prefer an already-loaded relation (worklist eager-loads them) to avoid a
        // per-row query; fall back to a cheap exists() for single-visit use.
        if ($visit->relationLoaded($relation)) {
            $value = $visit->getRelation($relation);

            return $value instanceof Collection ? $value->isNotEmpty() : $value !== null;
        }

        try {
            return $visit->{$relation}()->exists();
        } catch (Throwable) {
            return false;
        }
    }
}
