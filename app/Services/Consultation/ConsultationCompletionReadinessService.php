<?php

namespace App\Services\Consultation;

use App\Enums\LogModule;
use App\Enums\LogSeverity;
use App\Models\User;
use App\Models\VisitConsultationRoute;
use App\Services\ActivityLogService;
use App\Services\Consultation\Specialty\ConsultationSpecialtyProfileResolver;
use App\Services\Consultation\Specialty\ConsultationSpecialtyReadinessService;

class ConsultationCompletionReadinessService
{
    public function __construct(
        private readonly ActivityLogService $activityLog,
        private readonly ConsultationSpecialtyProfileResolver $specialtyResolver,
        private readonly ConsultationSpecialtyReadinessService $specialtyReadiness,
    ) {}

    public function forRoute(VisitConsultationRoute $route): ConsultationCompletionReadinessResult
    {
        $route = $route->fresh(['visit', 'medicalRecord']) ?? $route;
        $record = $route->medicalRecord;

        if (! $record) {
            return new ConsultationCompletionReadinessResult($this->requirements([
                'complaint' => false,
                'examination' => false,
                'diagnosis' => false,
                'plan_or_disposition' => false,
            ]));
        }

        $record = $record->fresh(['complaints', 'physicalExaminations', 'diagnoses', 'treatments', 'prescriptions', 'tasks']) ?? $record;

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
        $result = $this->withSpecialtyRequirements($route, $user, $result);

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

    private function withSpecialtyRequirements(VisitConsultationRoute $route, User $user, ConsultationCompletionReadinessResult $base): ConsultationCompletionReadinessResult
    {
        try {
            $route->loadMissing(['visit', 'department']);
            $context = $this->specialtyResolver->resolve(
                user: $user,
                visit: $route->visit,
                consultationRoute: $route,
                department: $route->department,
            );
            $blockingItems = $this->specialtyReadiness->blockingItemsForCompletion($route, $context, $base);
        } catch (\Throwable) {
            return $base;
        }

        if ($blockingItems === []) {
            return $base;
        }

        $requirements = collect($base->requirements())
            ->concat(collect($blockingItems)->map(fn (array $item) => [
                'code' => 'specialty_'.$item['key'],
                'message' => $item['message'] ?: $item['label'],
                'met' => false,
            ]))
            ->unique('code')
            ->values()
            ->all();

        return new ConsultationCompletionReadinessResult($requirements);
    }
}
