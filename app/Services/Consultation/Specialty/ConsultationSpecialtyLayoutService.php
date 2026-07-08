<?php

namespace App\Services\Consultation\Specialty;

use App\Data\Consultation\Specialty\ResolvedConsultationSpecialty;
use App\Models\ConsultationSpecialtyProfile;
use Illuminate\Support\Collection;

class ConsultationSpecialtyLayoutService
{
    public function __construct(
        private readonly ConsultationSpecialtyProfileService $profiles,
        private readonly ConsultationSpecialtySectionComponentRegistry $registry,
        private readonly ConsultationSpecialtySectionSchema $schemas,
        private readonly ConsultationSpecialtySectionAliasService $aliases,
    ) {}

    public function buildLayout(array|ResolvedConsultationSpecialty $specialtyContext, array $existingWorkspacePayload = []): array
    {
        $context = $specialtyContext instanceof ResolvedConsultationSpecialty
            ? $specialtyContext->toArray()
            : $specialtyContext;

        $profileCode = $context['profile']['code'] ?? null;

        $sections = Collection::make($context['sections'] ?? [])
            ->filter(fn ($section) => is_array($section) && ($section['is_visible'] ?? true))
            ->sortBy(fn ($section) => (int) ($section['display_order'] ?? 0))
            ->unique(fn ($section) => (string) ($section['key'] ?? ''))
            ->map(fn ($section) => $this->normalizeSection($section, $profileCode))
            ->values();

        if ($sections->isEmpty()) {
            return $this->generalLayout();
        }

        return [
            'profile' => $context['profile'] ?? $this->generalLayout()['profile'],
            'is_fallback' => (bool) ($context['is_fallback'] ?? false),
            'source' => $context['source'] ?? null,
            'sections' => $sections->all(),
        ];
    }

    public function generalLayout(): array
    {
        $profile = $this->profiles->getDefaultProfile();
        $sections = $this->profiles->getVisibleOrderedSections($profile)
            ->map(fn ($section) => $this->normalizeSection([
                'key' => $section->section_key,
                'label' => $section->label,
                'translated_label' => __('consultation_specialties.sections.'.$section->section_key),
                'component' => $section->component,
                'display_order' => $section->display_order,
                'is_required' => $section->is_required,
                'is_visible' => $section->is_visible,
                'config' => $section->config ?? [],
            ], $profile->code))
            ->values()
            ->all();

        return [
            'profile' => [
                'id' => $profile->id,
                'code' => $profile->code,
                'name' => $profile->name,
                'translated_name' => $profile->translatedName(),
                'icon' => $profile->icon,
                'color' => $profile->color,
            ],
            'is_fallback' => true,
            'source' => 'fallback',
            'sections' => $sections,
        ];
    }

    private function normalizeSection(array $section, ?string $profileCode = null): array
    {
        $key = (string) ($section['key'] ?? $section['section_key'] ?? '');
        $canonicalKey = $this->registry->canonicalSectionKey($key);
        $displayLabel = $profileCode ? $this->aliases->displayLabelFor($profileCode, $canonicalKey) : null;

        return [
            'key' => $key,
            'canonical_key' => $canonicalKey,
            'label' => $displayLabel ?? ($section['label'] ?? str($key)->replace('_', ' ')->title()->toString()),
            'translated_label' => $displayLabel ?? ($section['translated_label'] ?? __(
                'consultation_specialties.sections.'.$key,
            )),
            'component' => $this->registry->resolveComponent($key, $section['component'] ?? null),
            'display_order' => (int) ($section['display_order'] ?? 0),
            'is_required' => (bool) ($section['is_required'] ?? false),
            'is_visible' => (bool) ($section['is_visible'] ?? true),
            'is_core' => $this->registry->isCoreSection($key),
            'tab_target' => $this->registry->tabTargetFor($key),
            'icon' => $this->registry->iconFor($key),
            'config' => $section['config'] ?? [],
            'form_fields' => $this->schemas->fieldsFor($key),
        ];
    }
}
