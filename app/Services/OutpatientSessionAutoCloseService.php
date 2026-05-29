<?php

namespace App\Services;

use App\Enums\LogModule;
use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Models\EmergencyCase;
use App\Models\Visit;
use App\Models\VisitConsultationRoute;
use Illuminate\Support\Facades\DB;

class OutpatientSessionAutoCloseService
{
    public const LOCK_REASON = 'Automatically completed after end of outpatient day.';

    public function __construct(
        private VisitStatusService $statuses,
        private VisitPathwayService $pathway,
        private ?ActivityLogService $logger = null,
    ) {
        $this->logger = $this->logger ?: app(ActivityLogService::class);
    }

    public function closeStaleSessions(): int
    {
        $count = 0;

        VisitConsultationRoute::query()
            ->with('visit')
            ->where('session_type', '!=', VisitConsultationRoute::SESSION_TYPE_EMERGENCY)
            ->whereIn('status', [
                VisitConsultationRoute::STATUS_PENDING,
                VisitConsultationRoute::STATUS_ACTIVE,
                VisitConsultationRoute::STATUS_PAUSED,
            ])
            ->whereHas('visit', function ($query) {
                $query->whereDate('visit_date', '<', today())
                    ->where('visit_type', VisitType::OUTPATIENT->value)
                    ->whereNotIn('status', [
                        VisitStatus::ADMITTED->value,
                        VisitStatus::EMERGENCY->value,
                        VisitStatus::COMPLETED->value,
                        VisitStatus::CANCELLED->value,
                        VisitStatus::NO_SHOW->value,
                    ])
                    ->whereDoesntHave('admission', fn ($admission) => $admission->whereNotIn('status', ['discharged', 'transferred', 'deceased']))
                    ->whereDoesntHave('emergencyCase', fn ($case) => $case->whereNotIn('emergency_status', [EmergencyCase::STATUS_DISPOSED, EmergencyCase::STATUS_CANCELLED]));
            })
            ->chunkById(100, function ($routes) use (&$count) {
                foreach ($routes as $route) {
                    DB::transaction(function () use ($route, &$count) {
                        $route = VisitConsultationRoute::query()->lockForUpdate()->find($route->id);
                        if (! $route || ! in_array($route->status, [VisitConsultationRoute::STATUS_PENDING, VisitConsultationRoute::STATUS_ACTIVE, VisitConsultationRoute::STATUS_PAUSED], true)) {
                            return;
                        }

                        $route->forceFill([
                            'status' => VisitConsultationRoute::STATUS_COMPLETED,
                            'completed_at' => $route->completed_at ?: now(),
                            'locked_at' => now(),
                            'lock_reason' => self::LOCK_REASON,
                        ])->save();

                        $visit = $route->visit()->lockForUpdate()->first();
                        if ($visit && ! $this->visitHasOpenOutpatientWork($visit)) {
                            $visit->forceFill([
                                'locked_at' => now(),
                                'lock_reason' => self::LOCK_REASON,
                            ])->save();

                            if (! in_array($visit->status, [VisitStatus::COMPLETED, VisitStatus::CANCELLED, VisitStatus::NO_SHOW], true)) {
                                $this->statuses->setCompleted($visit, self::LOCK_REASON);
                            }
                        }

                        if ($visit) {
                            $this->pathway->record($visit, 'OUTPATIENT_SESSION_AUTO_LOCKED', [
                                'source' => $route,
                                'department_id' => $route->department_id,
                                'title' => 'Outpatient session auto-completed',
                                'description' => self::LOCK_REASON,
                            ]);
                        }

                        $this->logger?->log(LogModule::CONSULTATION, 'OUTPATIENT_SESSION_AUTO_LOCKED', [
                            'visit_id' => $route->visit_id,
                            'consultation_route_id' => $route->id,
                        ], $route, self::LOCK_REASON);

                        $count++;
                    });
                }
            });

        return $count;
    }

    private function visitHasOpenOutpatientWork(Visit $visit): bool
    {
        return $visit->consultationRoutes()
            ->where('session_type', '!=', VisitConsultationRoute::SESSION_TYPE_EMERGENCY)
            ->whereIn('status', [VisitConsultationRoute::STATUS_PENDING, VisitConsultationRoute::STATUS_ACTIVE, VisitConsultationRoute::STATUS_PAUSED])
            ->exists();
    }
}
