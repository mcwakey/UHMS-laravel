<?php

namespace App\Services\Consultation\Specialty;

use App\Data\Consultation\Specialty\ResolvedConsultationSpecialty;
use App\Models\ConsultationSpecialtyEntry;
use App\Models\ConsultationSpecialtyOrderSetApplication;
use App\Models\ConsultationSpecialtyProfile;
use App\Models\ProcedureRequest;
use App\Models\VisitConsultationRoute;

class ConsultationSpecialtySummarySourceCollector
{
    public function __construct(
        private readonly ConsultationSpecialtyReadinessService $readiness,
        private readonly ConsultationSpecialtySectionAliasService $aliases,
    ) {}

    public function collect($consultation, ResolvedConsultationSpecialty|array $specialtyContext, array $workspacePayload = []): array
    {
        $route = $this->route($consultation);
        $profile = $this->profile($specialtyContext);

        $route->loadMissing(['visit', 'department', 'medicalRecord']);
        $record = $route->medicalRecord;
        $record?->loadMissing([
            'complaints',
            'historiesOfPresentingComplaint',
            'physicalExaminations',
            'diagnoses',
            'investigations',
            'treatments',
            'prescriptions.items',
            'tasks',
        ]);

        $entries = $profile
            ? ConsultationSpecialtyEntry::query()
                ->where('consultation_id', $route->id)
                ->where('consultation_specialty_profile_id', $profile->id)
                ->get()
                ->mapWithKeys(fn (ConsultationSpecialtyEntry $entry) => [$entry->section_key => $entry->entry ?? []])
                ->all()
            : [];

        if ($profile) {
            // Expose legacy duplicate-section entries under canonical
            // "*_review" buckets so summary templates can merge them into
            // the canonical shared headings without losing saved data.
            $entries = $this->aliases->withMergedLegacyEntries($profile->code, $entries);
        }

        $readiness = $workspacePayload['specialtyReadiness'] ?? null;
        if (! is_array($readiness) && $profile) {
            $readiness = $this->readiness->evaluate($route, $specialtyContext, $workspacePayload)->toArray();
        }

        // Phase 14R.6 — additional GENERATED source. Creates no specialty
        // entry, overwrites no existing field, and returns an empty array
        // (with zero queries) while the summary projection flag is off.
        $maternityContext = $this->maternityProjection($route);

        return [
            'maternity_context' => $maternityContext,
            'profile' => $profile ? [
                'id' => $profile->id,
                'code' => $profile->code,
                'name' => $profile->name,
                'translated_name' => $profile->translatedName(),
            ] : null,
            'core' => [
                'complaints' => $record ? $record->complaints->map(fn ($entry) => [
                    'description' => $entry->description,
                    'duration' => trim(($entry->duration ?? '').' '.($entry->duration_unit ?? '')),
                    'severity' => $entry->severity,
                    'notes' => $entry->notes,
                ])->values()->all() : [],
                'hopc' => $record ? $record->historiesOfPresentingComplaint->map(fn ($entry) => [
                    'content' => $entry->content,
                    'onset' => $entry->onset,
                    'duration' => $entry->duration,
                    'severity' => $entry->severity,
                    'notes' => $entry->notes,
                ])->values()->all() : [],
                'examination' => $record ? $record->physicalExaminations->map(fn ($entry) => [
                    'findings' => $entry->findings,
                    'general' => $entry->general_examination,
                    'systemic' => $entry->systemic_examination,
                    'specialty' => $entry->specialty_examination,
                    'notes' => $entry->notes,
                ])->values()->all() : [],
                'diagnoses' => $record ? $record->diagnoses->map(fn ($entry) => [
                    'description' => $entry->description,
                    'type' => $entry->type,
                    'icd_code' => $entry->icd_code,
                    'is_primary' => (bool) $entry->is_primary,
                    'notes' => $entry->notes,
                ])->values()->all() : [],
                'investigations' => $record ? $record->investigations->map(fn ($entry) => [
                    'description' => $entry->description,
                    'type' => $entry->investigation_type,
                    'urgency' => $entry->urgency,
                    'status' => $entry->status,
                    'notes' => $entry->notes,
                ])->values()->all() : [],
                'procedures' => ProcedureRequest::query()
                    ->with(['service', 'procedure'])
                    ->where('consultation_route_id', $route->id)
                    ->get()
                    ->map(fn (ProcedureRequest $entry) => [
                        'name' => $entry->service?->name ?? $entry->procedure?->name,
                        'status' => $entry->status?->label() ?? $entry->status,
                        'priority' => $entry->priority,
                        'indication' => $entry->indication,
                        'notes' => $entry->notes,
                    ])->values()->all(),
                'prescriptions' => $record ? $record->prescriptions->map(fn ($entry) => [
                    'number' => $entry->prescription_number,
                    'status' => $entry->status?->label() ?? $entry->status,
                    'notes' => $entry->notes,
                    'items' => $entry->items->map(fn ($item) => [
                        'drug_name' => $item->drug_name,
                        'dosage' => $item->dosage,
                        'frequency' => $item->frequency,
                        'duration' => $item->duration,
                        'route' => $item->route,
                        'instructions' => $item->instructions,
                    ])->values()->all(),
                ])->values()->all() : [],
                'tasks' => $record ? $record->tasks->map(fn ($entry) => [
                    'title' => $entry->title,
                    'description' => $entry->description,
                    'status' => $entry->status,
                    'priority' => $entry->priority,
                    'due_date' => $entry->due_date?->format('Y-m-d'),
                ])->values()->all() : [],
                'notes' => $route->notes,
                'summary' => $record?->final_note,
                'plan' => $record ? $record->treatments->map(fn ($entry) => [
                    'type' => $entry->type,
                    'description' => $entry->description,
                ])->values()->all() : [],
                'disposition' => $route->notes,
            ],
            'specialty_entries' => $entries,
            'readiness' => $readiness ?: [],
            'order_set_applications' => ConsultationSpecialtyOrderSetApplication::query()
                ->with(['orderSet', 'items'])
                ->where('consultation_id', $route->id)
                ->latest()
                ->get()
                ->map(fn (ConsultationSpecialtyOrderSetApplication $application) => [
                    'id' => $application->id,
                    'order_set' => $application->orderSet?->name,
                    'status' => $application->status,
                    'items' => $application->items->map(fn ($item) => [
                        'label' => $item->label,
                        'status' => $item->status,
                        'apply_mode' => $item->apply_mode,
                    ])->values()->all(),
                ])->values()->all(),
        ];
    }

    /**
     * Phase 14R.6 — the curated maternity projection, as an additional summary
     * source named `maternity_context`.
     *
     * Only an EXPLICIT link yields clinical values; a suggested, ambiguous or
     * invalid context contributes its advisory status and nothing else, so an
     * unconfirmed pregnancy can never be rendered as consultation truth.
     *
     * @return array<string, mixed>
     */
    private function maternityProjection(VisitConsultationRoute $route): array
    {
        $service = app(\App\Services\Consultation\Maternity\ConsultationMaternitySummaryService::class);

        if (! $service->enabled()) {
            return [];
        }

        return $service->project($route)->toCanonicalArray();
    }

    private function route($consultation): VisitConsultationRoute
    {
        if ($consultation instanceof VisitConsultationRoute) {
            return $consultation;
        }

        return VisitConsultationRoute::query()->findOrFail((int) $consultation);
    }

    private function profile(ResolvedConsultationSpecialty|array $context): ?ConsultationSpecialtyProfile
    {
        if ($context instanceof ResolvedConsultationSpecialty) {
            return $context->profile;
        }

        return ConsultationSpecialtyProfile::query()->find(data_get($context, 'profile.id'));
    }
}
