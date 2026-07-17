<?php

namespace App\Services;

use App\Models\Visit;

class ConsultationPreviewDataService
{
    public function __construct(
        private readonly ConsultationSummaryService $summaryService,
        private readonly LabService $labService,
        private readonly ProcedureRequestService $procedureRequestService,
    ) {}

    public function build(Visit $visit): array
    {
        $visit->load([
            'patient',
            'department',
            'visitInsurance.insuranceProvider',
            'vitals' => fn ($q) => $q->with('recordedBy')->latest(),
        ]);

        $sessions = $visit->consultationRoutes()
            ->with([
                'department',
                'doctor',
                'mainDoctor',
                'primaryNurse',
                'emergencyCase',
                'emergencySession',
            ])
            ->orderByRaw("CASE status WHEN 'ACTIVE' THEN 0 WHEN 'PENDING' THEN 1 WHEN 'PAUSED' THEN 2 WHEN 'COMPLETED' THEN 3 ELSE 4 END")
            ->oldest()
            ->get();

        $sessionSummaries = collect();

        if ($sessions->isNotEmpty()) {
            foreach ($sessions as $session) {
                $record = $session->medicalRecord;
                $sessionSummaries->push([
                    'session' => $session,
                    'record' => $record,
                    'summary' => $this->summaryService->forRecord($record),
                ]);
            }
        } else {
            $sessionSummaries->push([
                'session' => null,
                'record' => $visit->medicalRecord,
                'summary' => $this->summaryService->forRecord($visit->medicalRecord),
            ]);
        }

        $labRequests = $this->labService->getVisitLabRequests($visit);
        $procedureRequests = $this->procedureRequestService->forVisit($visit->id);

        return [
            'visit' => $visit,
            'sessions' => $sessions,
            'sessionSummaries' => $sessionSummaries,
            'labRequests' => $labRequests,
            'procedureRequests' => $procedureRequests,
            'contributors' => $this->buildVisitContributors($sessionSummaries, $sessions, $labRequests, $procedureRequests),
            'generatedAt' => now(),
        ];
    }

    private function buildVisitContributors($sessionSummaries, $sessions, $labRequests, $procedureRequests): array
    {
        $mainDoctorIds = $sessions->pluck('doctor.id')->filter()->unique()->all();
        $tally = [];

        $bump = function (?int $id, ?string $name, bool $isMain) use (&$tally): void {
            if (! $name) {
                return;
            }

            $key = $id ? 'u-'.$id : 'n-'.$name;
            if (! isset($tally[$key])) {
                $tally[$key] = [
                    'user_id' => $id,
                    'name' => $name,
                    'role_label' => $isMain ? 'Main Doctor' : 'Contributor',
                    'entries' => 0,
                ];
            } elseif ($isMain) {
                $tally[$key]['role_label'] = 'Main Doctor';
            }

            $tally[$key]['entries']++;
        };

        foreach ($sessionSummaries as $bundle) {
            foreach ($bundle['summary']['sections'] ?? [] as $entries) {
                foreach ($entries as $entry) {
                    $id = $entry['owner_id'] ?? null;
                    $name = $entry['entered_by'] ?? null;
                    if ($name === 'Unknown user') {
                        continue;
                    }

                    $bump($id, $name, $id && in_array($id, $mainDoctorIds, true));
                }
            }
        }

        foreach ($labRequests as $request) {
            $owner = $request->requestedBy ?? null;
            $bump($owner?->id, $owner?->full_name, $owner && in_array($owner->id, $mainDoctorIds, true));
        }

        foreach ($procedureRequests as $procedureRequest) {
            $owner = $procedureRequest->requestingDoctor ?? $procedureRequest->requestedBy ?? null;
            $bump($owner?->id, $owner?->full_name, $owner && in_array($owner->id, $mainDoctorIds, true));
        }

        return array_values($tally);
    }
}
