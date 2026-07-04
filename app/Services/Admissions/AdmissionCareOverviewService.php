<?php

namespace App\Services\Admissions;

use App\Enums\AdmissionCareFlag;
use App\Enums\NursingTaskStatus;
use App\Models\Admission;
use Illuminate\Support\Collection;

class AdmissionCareOverviewService
{
    public function forAdmission(Admission $admission, array $medicationBoard = []): array
    {
        $admission->loadMissing([
            'patient',
            'bed.ward',
            'admissionRequest.requestedBy',
            'nursingNotes.nurse',
            'nursingTasks.assignedTo',
            'visit.vitals.recordedBy',
            'wardRounds.recordedBy',
        ]);

        $latestVitals = $admission->visit?->vitals?->sortByDesc('recorded_at')->first();
        $latestRound = $admission->wardRounds->sortByDesc('round_date')->first();
        $openTasks = $admission->nursingTasks
            ->filter(fn ($task) => $task->status?->isOpen())
            ->values();
        $overdueTasks = $openTasks
            ->filter(fn ($task) => $task->due_at && $task->due_at->isPast())
            ->values();
        $careFlags = $this->careFlags($admission->care_flags ?? []);

        $vitalsOverdueHours = (int) config('admissions.vitals_overdue_hours', 8);
        $wardRoundOverdueHours = (int) config('admissions.ward_round_overdue_hours', 24);
        $vitalsOverdue = ! $latestVitals || $latestVitals->recorded_at?->lt(now()->subHours($vitalsOverdueHours));
        $wardRoundOverdue = ! $latestRound || $latestRound->round_date?->lt(now()->subHours($wardRoundOverdueHours));

        return [
            'latest_vitals' => $latestVitals,
            'latest_ward_round' => $latestRound,
            'vitals_overdue' => $vitalsOverdue,
            'vitals_overdue_hours' => $vitalsOverdueHours,
            'ward_round_overdue' => $wardRoundOverdue,
            'ward_round_overdue_hours' => $wardRoundOverdueHours,
            'care_flags' => $careFlags,
            'allowed_care_flags' => collect(AdmissionCareFlag::cases()),
            'open_tasks' => $openTasks,
            'overdue_tasks' => $overdueTasks,
            'recent_notes' => $admission->nursingNotes->take(6)->values(),
            'handover' => $this->handover($admission, $latestVitals, $latestRound, $openTasks, $careFlags, $medicationBoard),
            'checklist' => $this->checklist($admission, $latestVitals, $latestRound, $openTasks, $overdueTasks, $medicationBoard, $vitalsOverdue, $wardRoundOverdue),
        ];
    }

    public function compactIndicators(Admission $admission): array
    {
        $admission->loadMissing(['nursingTasks', 'visit.vitals']);

        $latestVitals = $admission->visit?->vitals?->sortByDesc('recorded_at')->first();
        $vitalsOverdue = ! $latestVitals || $latestVitals->recorded_at?->lt(now()->subHours((int) config('admissions.vitals_overdue_hours', 8)));
        $openTasks = $admission->nursingTasks->filter(fn ($task) => $task->status?->isOpen());

        return [
            'open_tasks_count' => $openTasks->count(),
            'overdue_tasks_count' => $openTasks->filter(fn ($task) => $task->due_at && $task->due_at->isPast())->count(),
            'vitals_overdue' => $vitalsOverdue,
            'latest_vitals_at' => $latestVitals?->recorded_at,
        ];
    }

    private function handover(Admission $admission, $latestVitals, $latestRound, Collection $openTasks, Collection $careFlags, array $medicationBoard): array
    {
        $counts = $medicationBoard['counts'] ?? [];

        return [
            'location' => trim(($admission->bed?->ward?->name ?? __('admissions.no_current_bed')) . ' / ' . ($admission->bed?->bed_number ?? '')),
            'admitting_diagnosis' => $admission->admitting_diagnosis,
            'requested_by' => $admission->admissionRequest?->requestedBy?->name,
            'last_vitals_at' => $latestVitals?->recorded_at,
            'last_round_at' => $latestRound?->round_date,
            'open_tasks_count' => $openTasks->count(),
            'medications_due' => (int) ($counts['due_now'] ?? 0),
            'medications_overdue' => (int) ($counts['overdue'] ?? 0),
            'flags_count' => $careFlags->count(),
        ];
    }

    private function checklist(
        Admission $admission,
        $latestVitals,
        $latestRound,
        Collection $openTasks,
        Collection $overdueTasks,
        array $medicationBoard,
        bool $vitalsOverdue,
        bool $wardRoundOverdue
    ): array {
        $counts = $medicationBoard['counts'] ?? [];

        return [
            ['label' => __('admissions.check_bed_assigned'), 'status' => $admission->bed_id ? 'complete' : 'warning'],
            ['label' => __('admissions.check_latest_vitals'), 'status' => $latestVitals && ! $vitalsOverdue ? 'complete' : 'warning'],
            ['label' => __('admissions.check_ward_round'), 'status' => $latestRound && ! $wardRoundOverdue ? 'complete' : 'warning'],
            ['label' => __('admissions.check_nursing_note'), 'status' => $admission->nursingNotes->isNotEmpty() ? 'complete' : 'pending'],
            ['label' => __('admissions.check_tasks_reviewed'), 'status' => $overdueTasks->isEmpty() ? ($openTasks->isEmpty() ? 'complete' : 'pending') : 'warning'],
            ['label' => __('admissions.check_medication_review'), 'status' => ((int) ($counts['overdue'] ?? 0)) > 0 ? 'warning' : 'complete'],
            ['label' => __('admissions.check_discharge_planning'), 'status' => in_array('discharge_planning', $admission->care_flags ?? [], true) ? 'pending' : 'complete'],
        ];
    }

    private function careFlags(array $flags): Collection
    {
        return collect($flags)
            ->map(fn ($flag) => AdmissionCareFlag::tryFrom($flag))
            ->filter()
            ->values();
    }
}
