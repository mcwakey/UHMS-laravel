<?php

namespace App\Services;

use App\Services\Consultation\Specialty\ConsultationSpecialtyEntryService;
use App\Services\Consultation\Specialty\ConsultationSpecialtyLayoutService;
use App\Services\Consultation\Specialty\ConsultationSpecialtyProfileResolver;
use App\Models\Visit;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Lang;

class ConsultationPreviewDataService
{
    public function __construct(
        private readonly ConsultationSummaryService $summaryService,
        private readonly LabService $labService,
        private readonly ProcedureRequestService $procedureRequestService,
        private readonly ConsultationSpecialtyProfileResolver $specialtyResolver,
        private readonly ConsultationSpecialtyEntryService $specialtyEntries,
        private readonly ConsultationSpecialtyLayoutService $specialtyLayout,
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
                    // Specialty personalisation for this session's consultation
                    // department (ophthalmology, ENT, …): the workspace's section
                    // order, profile-aware labels ("Eye Diagnosis"), and the
                    // structured entries. Null for general medicine.
                    'specialty' => $this->buildSpecialtyContext($visit, $session),
                ]);
            }
        } else {
            $sessionSummaries->push([
                'session' => null,
                'record' => $visit->medicalRecord,
                'summary' => $this->summaryService->forRecord($visit->medicalRecord),
                'specialty' => null,
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
     * Personalise a session's preview to its consultation specialty profile
     * (ophthalmology, ENT, …) the same way the doctor workspace adapts:
     *
     *   - sections follow the profile layout's order,
     *   - the shared/core sections keep their generic entries but take the
     *     profile's presentation label ("Eye Diagnosis" instead of "Diagnoses"),
     *   - the structured specialty sections (visual acuity, refraction, …) are
     *     interleaved at their layout position, shaped for the preview's
     *     native owner-block/detail rendering,
     *   - visit-level headings (investigations / prescriptions / procedures)
     *     get the profile labels for single-specialty visits.
     *
     * Returns null for general medicine so those sessions keep the default
     * rendering untouched.
     *
     * @return array{profile: array<string,mixed>, orderedSections: list<array<string,mixed>>, visitSectionLabels: array<string,string>}|null
     */
    private function buildSpecialtyContext(Visit $visit, $session): ?array
    {
        $record = $session->medicalRecord;
        if (! $record) {
            return null;
        }

        // Resolve the specialty from the session (route mapping / existing
        // entries), using the session's own clinician as the user signal so the
        // preview reflects the consultation, not the viewer.
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

            $profile = $resolved->profile ?? null;
            if (! $profile || $profile->code === 'general_medicine') {
                return null;
            }

            $layoutSections = collect($this->specialtyLayout->buildLayout($resolved)['sections'] ?? []);
            $entriesBySection = $this->specialtyEntries
                ->getEntriesForConsultation($session, $profile)
                ->groupBy('section_key');

            // Layout keys that mirror the generic summary sections rendered
            // inside a session card, mapped to the summary's section keys.
            $coreKeyMap = [
                'complaints' => 'complaints',
                'hopc' => 'history_of_presenting_complaint',
                'history_of_presenting_complaint' => 'history_of_presenting_complaint',
                'examination' => 'examination',
                'diagnosis' => 'diagnoses',
                'diagnoses' => 'diagnoses',
                'treatments' => 'treatments',
                'procedures' => 'procedures',
                'tasks' => 'tasks',
                'notes' => 'notes',
            ];

            // Workspace-only panels, plus the sections the preview renders at
            // visit level (outside the session card).
            $skipKeys = ['patient_summary', 'summary', 'completion_readiness', 'investigations', 'prescription', 'prescriptions'];

            $ordered = [];
            $placedGenericKeys = [];

            foreach ($layoutSections as $meta) {
                $key = (string) ($meta['key'] ?? '');
                if ($key === '' || in_array($key, $skipKeys, true)) {
                    continue;
                }

                $fields = collect($meta['form_fields'] ?? []);
                $isStructured = $fields->isNotEmpty()
                    || str_contains((string) ($meta['component'] ?? ''), 'structured-section');

                if (! $isStructured) {
                    $genericKey = $coreKeyMap[$key] ?? null;
                    if ($genericKey && ! in_array($genericKey, $placedGenericKeys, true)) {
                        $ordered[] = [
                            'type' => 'generic',
                            'key' => $genericKey,
                            'label' => $meta['translated_label'] ?? $meta['label'] ?? null,
                        ];
                        $placedGenericKeys[] = $genericKey;
                    }

                    continue;
                }

                $rendered = ($entriesBySection[$key] ?? collect())
                    ->map(function ($entry) use ($fields) {
                        $data = is_array($entry->entry) ? $entry->entry : [];

                        // Prefer the schema's field order/labels; fall back to
                        // whatever keys are present so nothing is silently lost.
                        $names = $fields->pluck('name')->filter()->values();
                        if ($names->isEmpty()) {
                            $names = collect(array_keys($data));
                        }

                        $pairs = $names
                            ->map(fn ($name) => [
                                'label' => $this->specialtyFieldLabel($name),
                                'value' => $this->specialtyDisplayValue($data[$name] ?? null),
                            ])
                            ->filter(fn ($pair) => $pair['value'] !== '')
                            ->values()
                            ->all();

                        return [
                            'owner_name' => $entry->createdBy?->full_name,
                            'recorded_at' => $entry->created_at,
                            'fields' => $pairs,
                        ];
                    })
                    ->filter(fn ($item) => ! empty($item['fields']))
                    ->values()
                    ->all();

                if ($rendered === []) {
                    continue;
                }

                $ordered[] = [
                    'type' => 'structured',
                    'key' => $key,
                    'label' => $meta['translated_label']
                        ?? $meta['label']
                        ?? $this->specialtyFieldLabel($key),
                    'entries' => $rendered,
                ];
            }

            // Generic sections the profile layout doesn't mention keep their
            // default place at the end, in the preview's default order.
            foreach ([
                'complaints', 'history_of_presenting_complaint', 'examination',
                'diagnoses', 'treatments', 'procedures', 'tasks', 'notes',
            ] as $genericKey) {
                if (! in_array($genericKey, $placedGenericKeys, true)) {
                    $ordered[] = ['type' => 'generic', 'key' => $genericKey, 'label' => null];
                }
            }

            $visitSectionLabels = collect([
                'investigations' => 'investigations',
                'prescriptions' => 'prescription',
                'procedures' => 'procedures',
            ])
                ->map(function (string $layoutKey) use ($layoutSections) {
                    $meta = $layoutSections->firstWhere('key', $layoutKey);

                    return $meta['translated_label'] ?? ($meta['label'] ?? null);
                })
                ->filter()
                ->all();

            return [
                'profile' => [
                    'code' => $profile->code,
                    'name' => $profile->translatedName(),
                    'icon' => $profile->icon,
                ],
                'orderedSections' => $ordered,
                'visitSectionLabels' => $visitSectionLabels,
            ];
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function specialtyFieldLabel(string $name): string
    {
        return Lang::has('consultation_specialties.forms.fields.'.$name)
            ? __('consultation_specialties.forms.fields.'.$name)
            : str($name)->replace('_', ' ')->title()->toString();
    }

    private function specialtyDisplayValue(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? __('common.yes') : __('common.no');
        }

        if (is_array($value)) {
            return collect($value)->filter(fn ($item) => filled($item))->implode(', ');
        }

        return trim((string) ($value ?? ''));
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
