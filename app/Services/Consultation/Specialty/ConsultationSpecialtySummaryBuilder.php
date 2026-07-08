<?php

namespace App\Services\Consultation\Specialty;

use App\Data\Consultation\Specialty\ConsultationSpecialtySummaryResult;
use App\Data\Consultation\Specialty\ResolvedConsultationSpecialty;
use App\Models\ConsultationSpecialtyProfile;
use App\Services\ConsultationSummaryService;
use Illuminate\Support\Str;

class ConsultationSpecialtySummaryBuilder
{
    public function __construct(
        private readonly ConsultationSpecialtySummarySourceCollector $collector,
        private readonly ConsultationSpecialtySummaryTemplateRegistry $templates,
        private readonly ConsultationSpecialtySummaryFormatter $formatter,
        private readonly ConsultationSummaryService $generalSummaries,
    ) {}

    public function build(
        $consultation,
        ResolvedConsultationSpecialty|array $specialtyContext,
        array $workspacePayload = [],
        array $options = []
    ): ConsultationSpecialtySummaryResult {
        try {
            $profile = $this->profile($specialtyContext);
            if (! $profile instanceof ConsultationSpecialtyProfile) {
                return $this->fallback($consultation, null);
            }

            $template = $this->templates->templateForProfile($profile);
            $sources = $this->collector->collect($consultation, $specialtyContext, $workspacePayload);

            if (($template['fallback'] ?? false) === true) {
                return $this->fallback($consultation, $profile);
            }

            $sections = collect($template['sections'] ?? [])
                ->map(fn (array $section) => $this->buildSection($section, $sources))
                ->filter(fn (array $section) => ! $section['is_empty'] || ($section['include_if_empty'] ?? false))
                ->values()
                ->all();

            if ($sections === []) {
                $sections[] = [
                    'key' => 'no_data',
                    'label' => __('consultation_specialties.summary_builder.title'),
                    'content' => __('consultation_specialties.summary_builder.no_data_available'),
                    'is_empty' => true,
                    'source' => 'none',
                    'warnings' => [],
                ];
            }

            $warnings = $this->warnings($sources);
            $plainText = $this->plainText((string) $template['title'], $sections, $warnings);

            return new ConsultationSpecialtySummaryResult(
                profile: $profile,
                title: (string) $template['title'],
                status: $warnings === [] ? 'complete' : 'warnings',
                sections: $sections,
                plainText: $plainText,
                html: $this->html((string) $template['title'], $sections, $warnings),
                warnings: $warnings,
                generatedAt: now()->toIso8601String(),
                isFallback: false,
                sourceCompleteness: [
                    'sections' => count($sections),
                    'warnings' => count($warnings),
                    'readiness_status' => data_get($sources, 'readiness.status'),
                ],
            );
        } catch (\Throwable) {
            return $this->fallback($consultation, null);
        }
    }

    private function buildSection(array $section, array $sources): array
    {
        $content = match ($section['formatter']) {
            'complaints' => $this->formatter->complaints((array) data_get($sources, $section['source'], [])),
            'complaints_plus_entry' => $this->formatter->sentenceList([
                $this->formatter->complaints((array) data_get($sources, $section['source'], [])),
                $this->formatter->keyValue((array) data_get($sources, $section['extra_source'] ?? '', [])),
            ]),
            'diagnoses' => $this->formatter->diagnoses((array) data_get($sources, $section['source'], [])),
            'investigations' => $this->formatter->investigations((array) data_get($sources, $section['source'], [])),
            'procedures' => $this->formatter->procedures((array) data_get($sources, $section['source'], [])),
            'prescriptions' => $this->formatter->prescriptions((array) data_get($sources, $section['source'], [])),
            'tasks' => $this->formatter->tasks((array) data_get($sources, $section['source'], [])),
            'readiness_warnings' => $this->formatter->readinessWarnings((array) data_get($sources, $section['source'], [])),
            'entry_plus_diagnoses' => $this->combined($sources, $section, fn ($extra) => $this->formatter->diagnoses((array) $extra)),
            'entry_plus_investigations' => $this->combined($sources, $section, fn ($extra) => $this->formatter->investigations((array) $extra)),
            'entry_plus_procedures' => $this->combined($sources, $section, fn ($extra) => $this->formatter->procedures((array) $extra)),
            'entry_plus_prescriptions' => $this->combined($sources, $section, fn ($extra) => $this->formatter->prescriptions((array) $extra)),
            'key_value_list' => $this->keyValueList((array) data_get($sources, $section['source'], [])),
            default => $this->formatter->keyValue((array) data_get($sources, $section['source'], [])),
        };

        return [
            'key' => $section['key'],
            'label' => $section['label'],
            'content' => $content,
            'is_empty' => ! filled($content),
            'source' => $section['source'],
            'warnings' => [],
            'include_if_empty' => (bool) ($section['include_if_empty'] ?? false),
        ];
    }

    private function combined(array $sources, array $section, callable $extraFormatter): string
    {
        return $this->formatter->sentenceList([
            $this->formatter->keyValue((array) data_get($sources, $section['source'], [])),
            $extraFormatter(data_get($sources, $section['extra_source'], [])),
        ]);
    }

    private function keyValueList(array $items): string
    {
        return $this->formatter->sentenceList(collect($items)
            ->map(fn ($item) => $this->formatter->keyValue((array) $item))
            ->all());
    }

    private function warnings(array $sources): array
    {
        $blocking = collect(data_get($sources, 'readiness.blockingItems', []));
        $warnings = collect(data_get($sources, 'readiness.warningItems', []));

        return $blocking
            ->concat($warnings)
            ->map(fn (array $item) => __('consultation_specialties.summary_builder.may_be_incomplete', [
                'item' => $item['message'] ?? $item['label'] ?? $item['key'],
            ]))
            ->unique()
            ->values()
            ->all();
    }

    private function plainText(string $title, array $sections, array $warnings): string
    {
        $lines = [$title, str_repeat('=', Str::length($title))];

        foreach ($warnings as $warning) {
            $lines[] = $warning;
        }

        foreach ($sections as $section) {
            if (! filled($section['content'])) {
                continue;
            }

            $lines[] = '';
            $lines[] = $section['label'].': '.$section['content'];
        }

        return trim(implode("\n", $lines));
    }

    private function html(string $title, array $sections, array $warnings): string
    {
        return view('consultations.partials.specialty-summary-preview', [
            'title' => $title,
            'sections' => $sections,
            'warnings' => $warnings,
        ])->render();
    }

    private function fallback($consultation, ?ConsultationSpecialtyProfile $profile): ConsultationSpecialtySummaryResult
    {
        $route = is_object($consultation) ? $consultation : \App\Models\VisitConsultationRoute::query()->find($consultation);
        $summary = $this->generalSummaries->forRecord($route?->medicalRecord);
        $sections = collect($summary['sections'] ?? [])
            ->flatMap(fn ($items, $key) => collect($items)->map(fn ($item) => [
                'key' => (string) $key,
                'label' => str((string) $key)->replace('_', ' ')->title()->toString(),
                'content' => $item['content'] ?? null,
                'is_empty' => ! filled($item['content'] ?? null),
                'source' => 'general_summary',
                'warnings' => [],
            ]))
            ->filter(fn ($section) => ! $section['is_empty'])
            ->values()
            ->all();
        $title = __('consultation_specialties.summary_builder.general_title');
        $plain = $this->plainText($title, $sections, []);

        return new ConsultationSpecialtySummaryResult(
            profile: $profile,
            title: $title,
            status: $sections === [] ? 'empty' : 'complete',
            sections: $sections,
            plainText: $plain ?: __('consultation_specialties.summary_builder.no_data_available'),
            html: $this->html($title, $sections, []),
            warnings: [],
            generatedAt: now()->toIso8601String(),
            isFallback: true,
            sourceCompleteness: ['sections' => count($sections), 'warnings' => 0],
        );
    }

    private function profile(ResolvedConsultationSpecialty|array $context): ?ConsultationSpecialtyProfile
    {
        if ($context instanceof ResolvedConsultationSpecialty) {
            return $context->profile;
        }

        return ConsultationSpecialtyProfile::query()->find(data_get($context, 'profile.id'));
    }
}
