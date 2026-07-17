<?php

namespace App\Services\Nursing;

use App\Enums\DepartmentType;
use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Models\ClinicalTask;
use App\Models\Department;
use App\Models\Treatment;
use App\Models\Visit;
use Illuminate\Database\Eloquent\Builder;

class NursingOpdService
{
    /** @return Builder<Visit> */
    public function query(Department $department): Builder
    {
        return $this->applyDepartmentScope(Visit::query(), $department);
    }

    /** @return Builder<Visit> */
    public function category(Department $department, string $category): Builder
    {
        $query = $this->query($department);

        return match ($category) {
            'waiting_for_triage' => $query->where('status', VisitStatus::QUEUED->value),
            'triage_in_progress' => $query->where('status', VisitStatus::TRIAGE->value),
            'vitals_incomplete' => $query->whereIn('status', [VisitStatus::QUEUED->value, VisitStatus::TRIAGE->value])
                ->whereDoesntHave('vitals'),
            'waiting_for_consultation' => $query->where('status', VisitStatus::WAITING->value),
            'consultation_in_progress' => $query->whereIn('status', [VisitStatus::ACTIVE->value, VisitStatus::CONSULTING->value]),
            'nursing_action_required' => $query->whereHas('clinicalTasks', fn (Builder $tasks) => $tasks
                ->whereIn('status', [ClinicalTask::STATUS_SCHEDULED, ClinicalTask::STATUS_DUE, ClinicalTask::STATUS_OVERDUE, ClinicalTask::STATUS_IN_PROGRESS])),
            'treatment_pending' => $query->whereHas('treatments'),
            'service_follow_up' => $query->whereIn('status', [VisitStatus::WAITING_INVESTIGATION->value, VisitStatus::LAB->value, VisitStatus::PHARMACY->value]),
            'ready_for_discharge' => $query->whereIn('status', [VisitStatus::BILLING->value, VisitStatus::DISCHARGING->value]),
            'completed_today' => $query->where('status', VisitStatus::COMPLETED->value)
                ->where(function (Builder $completed) {
                    $completed->whereDate('completed_at', today())->orWhereDate('visit_date', today());
                }),
            'active' => $query->whereNotIn('status', $this->terminalStatuses()),
            default => $query,
        };
    }

    /** @return array<string, int> */
    public function metrics(Department $department): array
    {
        $categories = [
            'waiting_for_triage', 'triage_in_progress', 'vitals_incomplete',
            'waiting_for_consultation', 'consultation_in_progress',
            'nursing_action_required', 'treatment_pending', 'service_follow_up',
            'ready_for_discharge', 'completed_today', 'active',
        ];

        $metrics = [];
        foreach ($categories as $category) {
            $metrics[$category] = $this->category($department, $category)->count();
        }

        $metrics['high_risk'] = $this->query($department)
            ->whereIn('triage_score', ['urgent', 'emergency'])
            ->whereNotIn('status', $this->terminalStatuses())
            ->count();

        return $metrics;
    }

    /** @return Builder<ClinicalTask> */
    public function tasks(Department $department): Builder
    {
        return ClinicalTask::query()
            ->whereNull('admission_id')
            ->whereHas('visit', fn (Builder $visit) => $this->applyDepartmentScope($visit, $department))
            ->where(function (Builder $query) use ($department) {
                $query->where('assigned_department_id', $department->id)
                    ->orWhereNull('assigned_department_id');
            });
    }

    /** @return Builder<Treatment> */
    public function treatments(Department $department): Builder
    {
        return Treatment::query()->whereHas('visit', fn (Builder $visit) => $this->applyDepartmentScope($visit, $department));
    }

    public function ensureAccessible(Visit $visit, Department $department): void
    {
        abort_unless($this->query($department)->whereKey($visit->id)->exists(), 404);
    }

    /** @return list<string> */
    private function terminalStatuses(): array
    {
        return [
            VisitStatus::COMPLETED->value, VisitStatus::CANCELLED->value,
            VisitStatus::NO_SHOW->value, VisitStatus::ABANDONED->value,
            VisitStatus::DISCHARGED->value, VisitStatus::DECEASED->value,
        ];
    }

    /** @return Builder<Visit> */
    private function applyDepartmentScope(Builder $query, Department $department): Builder
    {
        return $query
            ->where('visit_type', VisitType::OUTPATIENT->value)
            ->where(function (Builder $scope) use ($department) {
                $scope->where('current_department_id', $department->id)
                    ->orWhere(function (Builder $triageQueue) {
                        $triageQueue->whereIn('status', [VisitStatus::QUEUED->value, VisitStatus::TRIAGE->value])
                            ->where(function (Builder $queueScope) {
                                $queueScope->whereNull('current_department_id')
                                    ->orWhereHas('currentDepartment', fn (Builder $currentDepartment) => $currentDepartment
                                        ->where('type', '!=', DepartmentType::NURSING->value))
                                    ->orWhereHas('queueEntries', fn (Builder $queue) => $queue
                                        ->whereNull('department_id')
                                        ->whereIn('status', ['waiting', 'serving']));
                            });
                    })
                    ->orWhereHas('clinicalTasks', fn (Builder $tasks) => $tasks->where('assigned_department_id', $department->id))
                    ->orWhereHas('serviceRenderings', fn (Builder $renderings) => $renderings->where('department_id', $department->id))
                    ->orWhereHas('triage.triagedBy', fn (Builder $user) => $user
                        ->where('department_id', $department->id)
                        ->orWhereHas('departments', fn (Builder $assigned) => $assigned->whereKey($department->id)));
            });
    }
}
