<?php

namespace App\Services\Consultation;

use App\Enums\LogModule;
use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitConsultationRoute;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\DB;

class ConsultationAutoCompletionService
{
    public function __construct(
        private readonly ConsultationSessionEligibilityService $eligibility,
        private readonly ActivityLogService $activityLog,
    ) {}

    public function evaluate(Visit $visit, ?User $user = null, ?VisitConsultationRoute $sourceRoute = null, ?string $notes = null): Visit
    {
        return DB::transaction(function () use ($visit, $user, $sourceRoute, $notes) {
            $visit = $visit->fresh(['admission', 'consultationRoutes']);

            if (! $visit || $visit->completed_at || ! $this->eligibility->shouldAutoCompleteConsultation($visit)) {
                return $visit;
            }

            $fromStatus = $visit->status;
            $status = $visit->status;
            if ($visit->visit_type === VisitType::OUTPATIENT && $visit->canTransitionTo(VisitStatus::COMPLETED)) {
                $status = VisitStatus::COMPLETED;
            }

            $visit->forceFill([
                'status' => $status,
                'checked_out_at' => $visit->checked_out_at ?: now(),
                'completed_at' => now(),
                'completed_by' => $user?->id,
            ])->save();

            if ($status === VisitStatus::COMPLETED) {
                $visit->statusLogs()->create([
                    'from_status' => $fromStatus->value,
                    'to_status' => VisitStatus::COMPLETED->value,
                    'changed_by' => $user?->id,
                    'notes' => $notes ?: __('consultations.routes.all_sessions_completed'),
                ]);
            }

            $this->activityLog->log(
                LogModule::CONSULTATION,
                'CONSULTATION_SYSTEM_COMPLETED',
                [
                    'patient_id' => $visit->patient_id,
                    'visit_id' => $visit->id,
                    'consultation_route_id' => $sourceRoute?->id,
                    'department_id' => $sourceRoute?->department_id,
                    'causer' => $user,
                    'metadata' => [
                        'visit_status' => $status->value,
                        'visit_type' => $visit->visit_type?->value,
                    ],
                ],
                $sourceRoute,
                'Consultation system-completed after all required sessions were completed.',
            );

            $this->activityLog->log(
                LogModule::CONSULTATION,
                'CONSULTATION_COMPLETED_AFTER_LAST_SESSION',
                [
                    'patient_id' => $visit->patient_id,
                    'visit_id' => $visit->id,
                    'consultation_route_id' => $sourceRoute?->id,
                    'department_id' => $sourceRoute?->department_id,
                    'causer' => $user,
                ],
                $sourceRoute,
                'Consultation completed after the final session was completed.',
            );

            return $visit->fresh(['admission', 'consultationRoutes']);
        });
    }
}
