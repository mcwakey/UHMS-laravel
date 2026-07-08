<?php

namespace App\Services\Consultation\Specialty;

use App\Data\Consultation\Specialty\ConsultationSpecialtyReadinessResult;
use App\Data\Consultation\Specialty\ResolvedConsultationSpecialty;
use App\Models\ConsultationSpecialtyEntry;
use App\Models\ConsultationSpecialtyProfile;
use App\Models\ProcedureRequest;
use App\Models\VisitConsultationRoute;
use App\Services\Consultation\ConsultationCompletionReadinessResult;
use Illuminate\Support\Collection;

class ConsultationSpecialtyReadinessService
{
    public function __construct(
        private readonly ConsultationSpecialtyReadinessRuleRegistry $registry,
        private readonly ConsultationSpecialtySectionComponentRegistry $sectionRegistry,
    ) {}

    public function evaluate(
        $consultation,
        ResolvedConsultationSpecialty|array|null $specialtyContext,
        array $workspacePayload = []
    ): ConsultationSpecialtyReadinessResult {
        try {
            $route = $this->route($consultation);
            $profile = $this->profile($specialtyContext);

            if (! $profile instanceof ConsultationSpecialtyProfile || $profile->code === 'general_medicine') {
                return $this->fallbackResult($profile, $workspacePayload);
            }

            $rules = $this->registry->rulesForProfile($profile);
            if ($rules === []) {
                return $this->fallbackResult($profile, $workspacePayload);
            }

            $route->loadMissing(['visit', 'medicalRecord']);
            $record = $route->medicalRecord;
            $record?->loadMissing(['complaints', 'physicalExaminations', 'diagnoses', 'investigations', 'treatments', 'prescriptions', 'tasks']);

            $entries = ConsultationSpecialtyEntry::query()
                ->where('consultation_id', $route->id)
                ->where('consultation_specialty_profile_id', $profile->id)
                ->get()
                ->keyBy('section_key')
                ->map(fn (ConsultationSpecialtyEntry $entry) => $entry->entry ?? []);

            $anchors = $this->anchors($specialtyContext);
            $items = collect($rules)
                ->map(fn (array $rule) => $this->evaluateRule($rule, $route, $record, $entries, $anchors))
                ->values()
                ->all();

            return $this->resultFromItems($profile, $items, false);
        } catch (\Throwable) {
            return $this->fallbackResult(null, $workspacePayload);
        }
    }

    public function blockingItemsForCompletion(
        VisitConsultationRoute $route,
        ResolvedConsultationSpecialty|array|null $specialtyContext,
        ConsultationCompletionReadinessResult $baseReadiness
    ): array {
        $result = $this->evaluate($route, $specialtyContext, ['completionReadiness' => $baseReadiness]);

        return $result->isFallback ? [] : $result->blockingItems;
    }

    private function evaluateRule(array $rule, VisitConsultationRoute $route, $record, Collection $entries, array $anchors): array
    {
        $complete = match ($rule['source'] ?? null) {
            'core_complaint' => ($record && ($record->complaints->isNotEmpty() || filled($route->visit?->chief_complaint)))
                || $this->entryHasFields($entries->get($rule['legacy_section_key'] ?? '') ?? [], [], 'any'),
            'core_hopc' => $record && $record->relationLoaded('historiesOfPresentingComplaint') && $record->historiesOfPresentingComplaint->isNotEmpty(),
            'core_examination' => $record && $record->physicalExaminations->isNotEmpty(),
            'core_diagnosis' => $record && $record->diagnoses->isNotEmpty(),
            'core_prescription' => $record && $record->prescriptions->isNotEmpty(),
            'core_investigation' => $record && $record->investigations->isNotEmpty(),
            'core_task' => $record && $record->tasks->isNotEmpty(),
            'core_summary' => $record && ($record->treatments->isNotEmpty() || $record->prescriptions->isNotEmpty() || $record->tasks->isNotEmpty() || filled($route->notes) || filled($record->final_note)),
            'specialty_entry', 'specialty_entry_any' => $this->entryHasFields($entries->get($rule['section_key']) ?? [], $rule['fields'] ?? [], $rule['mode'] ?? 'any'),
            'specialty_entry_field' => $this->entryHasFields($entries->get($rule['section_key']) ?? [], [$rule['field'] ?? $rule['target_field'] ?? null], 'any'),
            'custom' => $this->customRuleIsComplete($rule, $route, $record, $entries),
            default => false,
        };

        $severity = $rule['severity'] ?? 'blocking';
        $status = $complete ? 'complete' : match ($severity) {
            'warning' => 'warning',
            'optional' => 'optional',
            default => 'missing',
        };

        return [
            'key' => $rule['key'],
            'label' => $rule['label'] ?? $rule['key'],
            'section_key' => $rule['section_key'] ?? null,
            'severity' => $severity,
            'status' => $status,
            'message' => $complete ? null : ($rule['message'] ?? null),
            'anchor' => $this->anchorFor($rule['section_key'] ?? null, $anchors),
            'source' => $rule['source'] ?? 'custom',
            'metadata' => [
                'fields' => $rule['fields'] ?? [],
                'mode' => $rule['mode'] ?? null,
            ],
        ];
    }

    private function customRuleIsComplete(array $rule, VisitConsultationRoute $route, $record, Collection $entries): bool
    {
        return match ($rule['key']) {
            'session_schedule_missing' => ! $this->entryHasFields($entries->get('treatment_plan') ?? [], ['treatment_goals', 'modalities', 'expected_duration'], 'any')
                || $this->entryHasFields($entries->get('treatment_plan') ?? [], ['session_frequency', 'number_of_sessions'], 'any'),
            'home_exercise_plan_missing' => $this->entryHasFields($entries->get('home_exercise_plan') ?? [], ['exercises', 'frequency', 'instructions'], 'any'),
            'iop_missing' => $this->entryHasFields($entries->get('iop') ?? [], ['right_eye_iop', 'left_eye_iop'], 'any'),
            'follow_up_missing' => $this->entryHasFields($entries->get('follow_up') ?? [], ['follow_up_date', 'follow_up_reason', 'patient_instructions', 'warning_signs'], 'any')
                || ($record && $record->tasks->isNotEmpty()),
            'oral_or_tooth_exam_recorded' => $this->entryHasFields($entries->get('tooth_chart') ?? [], ['tooth_number', 'condition', 'mobility', 'percussion', 'notes'], 'any')
                || $this->entryHasFields($entries->get('oral_examination') ?? [], ['oral_hygiene', 'gingiva', 'mucosa', 'occlusion', 'swelling', 'bleeding', 'examination_notes'], 'any'),
            'dental_diagnosis_recorded' => $this->entryHasFields($entries->get('dental_diagnosis') ?? [], ['diagnosis_text'], 'any')
                || ($record && $record->diagnoses->isNotEmpty()),
            'procedure_or_plan_recorded' => $this->entryHasFields($entries->get('dental_procedures') ?? [], ['procedure_planned', 'procedure_performed'], 'any')
                || ($record && $record->treatments->isNotEmpty())
                || ProcedureRequest::query()->where('consultation_route_id', $route->id)->exists()
                || filled($route->notes),
            'procedure_plan_recorded' => $this->entryHasFields($entries->get('procedure_plan') ?? [], ['procedure_planned', 'procedure_done', 'anaesthesia_plan', 'notes'], 'any')
                || ($record && $record->treatments->isNotEmpty())
                || ProcedureRequest::query()->where('consultation_route_id', $route->id)->exists()
                || filled($route->notes),
            'consent_obtained_if_required' => ! (bool) data_get($entries->get('consent') ?? [], 'consent_required')
                || (bool) data_get($entries->get('consent') ?? [], 'consent_obtained'),
            'xray_missing_if_extraction_planned' => ! $this->extractionPlanned($entries, $record)
                || $this->entryHasFields($entries->get('dental_xray') ?? [], ['xray_requested', 'xray_findings'], 'any')
                || ($record && $record->investigations->isNotEmpty()),
            'handover_missing' => $this->entryHasFields($entries->get('handover') ?? [], ['handover_to', 'handover_notes'], 'any'),
            default => false,
        };
    }

    private function entryHasFields(array $entry, array $fields, string $mode): bool
    {
        $fields = array_values(array_filter($fields));
        if ($fields === []) {
            return collect($entry)->contains(fn ($value) => $this->meaningful($value));
        }

        $checks = collect($fields)->map(fn (string $field) => $this->meaningful(data_get($entry, $field)));

        return $mode === 'all' ? $checks->every(fn ($met) => $met) : $checks->contains(true);
    }

    private function meaningful($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_array($value)) {
            return collect($value)->contains(fn ($item) => $this->meaningful($item));
        }

        return filled($value);
    }

    private function containsExtraction(array $entry): bool
    {
        $text = strtolower(implode(' ', array_filter([
            $entry['procedure_planned'] ?? null,
            $entry['procedure_performed'] ?? null,
        ])));

        return str_contains($text, 'extract') || str_contains($text, 'extraction');
    }

    private function extractionPlanned(Collection $entries, $record): bool
    {
        if ($this->containsExtraction($entries->get('dental_procedures') ?? [])) {
            return true;
        }

        return (bool) $record?->treatments
            ->contains(fn ($treatment) => str_contains(strtolower((string) $treatment->description), 'extract'));
    }

    private function resultFromItems(?ConsultationSpecialtyProfile $profile, array $items, bool $fallback): ConsultationSpecialtyReadinessResult
    {
        $blocking = collect($items)->where('severity', 'blocking')->where('status', 'missing')->values()->all();
        $warnings = collect($items)->where('severity', 'warning')->where('status', 'warning')->values()->all();
        $optional = collect($items)->where('severity', 'optional')->where('status', 'optional')->values()->all();
        $completed = collect($items)->where('status', 'complete')->values()->all();
        $total = max(1, count($items));
        $score = (int) round((count($completed) / $total) * 100);
        $canComplete = $blocking === [];
        $status = $fallback ? 'fallback' : (! $canComplete ? 'blocked' : ($warnings === [] ? 'ready' : 'needs_attention'));

        return new ConsultationSpecialtyReadinessResult(
            profile: $profile,
            status: $status,
            score: $score,
            blockingItems: $blocking,
            warningItems: $warnings,
            optionalItems: $optional,
            completedItems: $completed,
            items: $items,
            canComplete: $canComplete,
            isFallback: $fallback,
            summary: __('consultation_specialties.readiness.'.$status),
        );
    }

    private function fallbackResult(?ConsultationSpecialtyProfile $profile, array $workspacePayload): ConsultationSpecialtyReadinessResult
    {
        $base = $workspacePayload['completionReadiness'] ?? null;
        $items = [];

        if ($base instanceof ConsultationCompletionReadinessResult) {
            $items = collect($base->requirements())->map(fn (array $requirement) => [
                'key' => $requirement['code'],
                'label' => $requirement['message'],
                'section_key' => null,
                'severity' => 'blocking',
                'status' => $requirement['met'] ? 'complete' : 'missing',
                'message' => $requirement['met'] ? null : $requirement['message'],
                'anchor' => null,
                'source' => 'general_completion',
                'metadata' => [],
            ])->values()->all();
        }

        return $this->resultFromItems($profile, $items, true);
    }

    private function route($consultation): VisitConsultationRoute
    {
        if ($consultation instanceof VisitConsultationRoute) {
            return $consultation;
        }

        return VisitConsultationRoute::query()->findOrFail((int) $consultation);
    }

    private function profile(ResolvedConsultationSpecialty|array|null $context): ?ConsultationSpecialtyProfile
    {
        if ($context instanceof ResolvedConsultationSpecialty) {
            return $context->profile;
        }

        if (is_array($context)) {
            return ConsultationSpecialtyProfile::query()->find(data_get($context, 'profile.id'));
        }

        return null;
    }

    private function anchors(ResolvedConsultationSpecialty|array|null $context): array
    {
        $sections = $context instanceof ResolvedConsultationSpecialty ? $context->sections : data_get($context, 'sections', []);

        return collect($sections)
            ->mapWithKeys(function ($section) {
                $key = data_get($section, 'section_key') ?? data_get($section, 'key');
                if (! $key) {
                    return [];
                }

                $tabTarget = data_get($section, 'tab_target') ?? $this->sectionRegistry->tabTargetFor($key);

                return [$key => $tabTarget ? '#'.$tabTarget : null];
            })
            ->filter(fn ($anchor, $key) => filled($key) && filled($anchor))
            ->all();
    }

    private function anchorFor(?string $sectionKey, array $anchors): ?string
    {
        return $sectionKey ? ($anchors[$sectionKey] ?? '#'.$sectionKey) : null;
    }
}
