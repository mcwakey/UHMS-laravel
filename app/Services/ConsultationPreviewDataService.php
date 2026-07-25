<?php

namespace App\Services;

use App\Services\Consultation\Specialty\ConsultationSpecialtyProfileResolver;
use App\Services\Consultation\Specialty\ConsultationSpecialtySummaryBuilder;
use App\Models\Visit;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class ConsultationPreviewDataService
{
    public function __construct(
        private readonly ConsultationSummaryService $summaryService,
        private readonly LabService $labService,
        private readonly ProcedureRequestService $procedureRequestService,
        private readonly ConsultationSpecialtyProfileResolver $specialtyResolver,
        private readonly ConsultationSpecialtySummaryBuilder $specialtySummaryBuilder,
    ) {}

    public function build(
        Visit $visit,
        ?Collection $sessions = null,
        ?Collection $labRequests = null,
        ?Collection $procedureRequests = null,
        array $summariesBySessionId = [],
    ): array {
        $visit->loadMissing([
            'patient',
            'department',
            'visitInsurance.insuranceProvider',
            'vitals' => fn ($q) => $q->with('recordedBy')->latest(),
        ]);

        $sessions ??= $visit->consultationRoutes()
            ->with([
                'department',
                'doctor',
                'mainDoctor',
                'primaryNurse',
                'medicalRecord',
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
                    'summary' => $summariesBySessionId[$session->id]
                        ?? $this->summaryService->forRecord($record),
                    // Specialty-specific findings for this session's consultation
                    // department (ophthalmology, ENT, …). Null for general
                    // medicine so the generic sections aren't duplicated.
                    'specialtySummary' => $this->buildSpecialtySummary($visit, $session),
                ]);
            }
        } else {
            $sessionSummaries->push([
                'session' => null,
                'record' => $visit->medicalRecord,
                'summary' => $this->summaryService->forRecord($visit->medicalRecord),
                'specialtySummary' => null,
            ]);
        }

        $labRequests ??= $this->labService->getVisitLabRequests($visit);
        $procedureRequests ??= $this->procedureRequestService->forVisit($visit->id);

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

    /**
     * Resolve and render the specialty-specific summary for a single
     * consultation session, so the preview adapts to the department the
     * session was run under (e.g. ophthalmology, ENT). Returns null for
     * general-medicine / fallback profiles or when there is nothing to show,
     * so those sessions fall back to the generic section rendering.
     *
     * @return array<string, mixed>|null
     */
    private function buildSpecialtySummary(Visit $visit, $session): ?array
    {
        $record = $session->medicalRecord;
        if (! $record) {
            return null;
        }

        // Resolve the specialty from the session (its route mapping / existing
        // specialty entries), using the session's own clinician as the user
        // signal so the preview reflects the consultation, not the viewer.
        $user = $session->doctor ?? $session->mainDoctor ?? $record->doctor ?? Auth::user();
        if (! $user) {
            return null;
        }

        try {
            $resolved = $this->specialtyResolver->resolve(
                $user,
                visit: $visit,
                consultationRoute: $session,
                department: $session->department ?? $visit->currentDepartment,
            );

            $summary = $this->specialtySummaryBuilder->build($session, $resolved);
        } catch (\Throwable $e) {
            return null;
        }

        // Skip general medicine and fallbacks — their content already appears
        // in the generic session sections, so rendering it again is noise.
        if ($summary->isFallback || ! $summary->profile || $summary->profile->code === 'general_medicine') {
            return null;
        }

        $data = $summary->toArray();

        $hasContent = collect($data['sections'] ?? [])
            ->contains(fn ($section) => filled($section['content'] ?? null));

        if (! $hasContent) {
            return null;
        }

        return $data;
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
