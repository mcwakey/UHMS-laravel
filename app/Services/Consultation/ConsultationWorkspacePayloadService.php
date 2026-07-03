<?php

namespace App\Services\Consultation;

use App\Models\Visit;
use App\Services\ConsultationSessionService;
use App\Services\ConsultationSummaryService;

class ConsultationWorkspacePayloadService
{
    public function __construct(
        private readonly ConsultationSessionService $sessions,
        private readonly ConsultationSummaryService $summaries,
    ) {}

    public function summaryPayload(Visit $visit, ?int $routeId = null): array
    {
        $route = $this->sessions->resolveRouteForVisit($visit, $routeId);
        $record = $route ? $route->medicalRecord : $visit->medicalRecord;

        return [
            'route' => $route,
            'record' => $record,
            'consultationSummary' => $this->summaries->forRecord($record),
        ];
    }
}

