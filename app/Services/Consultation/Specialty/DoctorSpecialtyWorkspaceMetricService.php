<?php

namespace App\Services\Consultation\Specialty;

use App\Models\Appointment;
use App\Models\ConsultationSpecialtyProfile;
use App\Models\ConsultationTask;
use App\Models\LabRequest;
use App\Models\QueueEntry;
use App\Models\User;
use App\Models\VisitConsultationRoute;

class DoctorSpecialtyWorkspaceMetricService
{
    public function metricsFor(User $user, $consultation, ConsultationSpecialtyProfile $profile, mixed $department = null): array
    {
        $departmentId = $department?->id ?? $consultation?->department_id ?? $user->department_id;

        try {
            $metrics = [
                $this->metric('waiting_today', $this->waitingToday($departmentId)),
                $this->metric('reviewed_today', $this->reviewedToday($user, $departmentId)),
                $this->metric('pending_completion', $this->pendingCompletion($user, $departmentId)),
                $this->metric('pending_results', $this->pendingResults($user, $departmentId)),
                $this->metric('pending_tasks', $this->pendingTasks($user, $departmentId)),
                $this->metric('followups_due', $this->followupsDue($user, $departmentId)),
            ];
        } catch (\Throwable) {
            return [];
        }

        return collect($metrics)->filter(fn ($metric) => $metric['value'] !== null)->values()->all();
    }

    private function metric(string $key, ?int $value): array
    {
        return [
            'key' => $key,
            'label' => __('consultation_specialties.metrics.'.$key),
            'value' => $value,
        ];
    }

    private function waitingToday(?int $departmentId): ?int
    {
        if (! $departmentId) {
            return null;
        }

        return QueueEntry::query()->today()->waiting()->forDepartment($departmentId)->count();
    }

    private function reviewedToday(User $user, ?int $departmentId): int
    {
        return VisitConsultationRoute::query()
            ->whereDate('completed_at', today())
            ->where(function ($query) use ($user) {
                $query->where('doctor_id', $user->id)->orWhere('main_doctor_id', $user->id)->orWhere('completed_by', $user->id);
            })
            ->when($departmentId, fn ($query) => $query->where('department_id', $departmentId))
            ->count();
    }

    private function pendingCompletion(User $user, ?int $departmentId): int
    {
        return VisitConsultationRoute::query()
            ->whereIn('status', [VisitConsultationRoute::STATUS_ACTIVE, VisitConsultationRoute::STATUS_PAUSED])
            ->where(function ($query) use ($user) {
                $query->where('doctor_id', $user->id)->orWhere('main_doctor_id', $user->id);
            })
            ->when($departmentId, fn ($query) => $query->where('department_id', $departmentId))
            ->count();
    }

    private function pendingResults(User $user, ?int $departmentId): int
    {
        return LabRequest::query()
            ->pending()
            ->where('requested_by', $user->id)
            ->when($departmentId, fn ($query) => $query->where('department_id', $departmentId))
            ->whereDate('created_at', today())
            ->count();
    }

    private function pendingTasks(User $user, ?int $departmentId): int
    {
        return ConsultationTask::query()
            ->pending()
            ->where(function ($query) use ($user) {
                $query->where('assigned_to', $user->id)->orWhere('created_by', $user->id);
            })
            ->when($departmentId, fn ($query) => $query->where('department_id', $departmentId))
            ->count();
    }

    private function followupsDue(User $user, ?int $departmentId): int
    {
        return Appointment::query()
            ->whereDate('appointment_date', '<=', today())
            ->byDoctor($user->id)
            ->when($departmentId, fn ($query) => $query->where('department_id', $departmentId))
            ->whereNotIn('status', ['completed', 'cancelled', 'no_show'])
            ->count();
    }
}
