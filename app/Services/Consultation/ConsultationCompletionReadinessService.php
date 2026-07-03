<?php

namespace App\Services\Consultation;

use App\Enums\LogModule;
use App\Enums\LogSeverity;
use App\Models\User;
use App\Models\VisitConsultationRoute;
use App\Services\ActivityLogService;

class ConsultationCompletionReadinessService
{
    public function __construct(private readonly ActivityLogService $activityLog) {}

    public function forRoute(VisitConsultationRoute $route): ConsultationCompletionReadinessResult
    {
        $route->loadMissing(['visit', 'medicalRecord']);
        $record = $route->medicalRecord;

        if (! $record) {
            return new ConsultationCompletionReadinessResult($this->requirements([
                'complaint' => false,
                'examination' => false,
                'diagnosis' => false,
                'plan_or_disposition' => false,
            ]));
        }

        $record->loadMissing(['complaints', 'physicalExaminations', 'diagnoses', 'treatments', 'prescriptions', 'tasks']);

        return new ConsultationCompletionReadinessResult($this->requirements([
            'complaint' => $record->complaints->isNotEmpty() || filled($route->visit?->chief_complaint),
            'examination' => $record->physicalExaminations->isNotEmpty(),
            'diagnosis' => $record->diagnoses->isNotEmpty(),
            'plan_or_disposition' => $record->treatments->isNotEmpty()
                || $record->prescriptions->isNotEmpty()
                || $record->tasks->isNotEmpty()
                || filled($route->notes),
        ]));
    }

    public function assertReady(VisitConsultationRoute $route, User $user): ConsultationCompletionReadinessResult
    {
        $result = $this->forRoute($route);

        if ($result->ready()) {
            return $result;
        }

        $this->activityLog->log(LogModule::CONSULTATION, 'CONSULTATION_COMPLETION_BLOCKED', [
            'severity' => LogSeverity::WARNING,
            'patient_id' => $route->patient_id,
            'visit_id' => $route->visit_id,
            'consultation_route_id' => $route->id,
            'causer' => $user,
            'missing_requirements' => collect($result->missing())->pluck('code')->values()->all(),
        ], $route, __('consultation.completion.blocked'));

        throw new ConsultationCompletionException(
            __('consultation.completion.missing_requirements'),
            $result,
        );
    }

    private function requirements(array $statusByCode): array
    {
        $configured = config('consultation.completion_checklist.requirements', array_keys($statusByCode));

        return collect($configured)
            ->map(fn (string $code) => [
                'code' => $code,
                'message' => __('consultation.completion.requirement.'.$code),
                'met' => (bool) ($statusByCode[$code] ?? false),
            ])
            ->values()
            ->all();
    }
}
