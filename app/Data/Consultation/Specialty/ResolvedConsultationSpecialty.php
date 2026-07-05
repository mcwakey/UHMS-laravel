<?php

namespace App\Data\Consultation\Specialty;

use App\Models\ConsultationSpecialtyProfile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Lang;

class ResolvedConsultationSpecialty
{
    public function __construct(
        public readonly ConsultationSpecialtyProfile $profile,
        public readonly ?string $source,
        public readonly ?string $reason,
        public readonly ?object $department,
        public readonly ?object $consultationRoute,
        public readonly array $sections,
        public readonly bool $isFallback,
    ) {}

    public function toArray(): array
    {
        return [
            'profile' => [
                'id' => $this->profile->id,
                'code' => $this->profile->code,
                'name' => $this->profile->name,
                'translated_name' => $this->profile->translatedName(),
                'icon' => $this->profile->icon,
                'color' => $this->profile->color,
            ],
            'source' => $this->source,
            'reason' => $this->reason,
            'is_fallback' => $this->isFallback,
            'sections' => Collection::make($this->sections)
                ->map(function ($section): array {
                    $key = $section->section_key;
                    $translationKey = 'consultation_specialties.sections.' . $key;

                    return [
                        'key' => $key,
                        'label' => $section->label,
                        'translated_label' => Lang::has($translationKey) ? __($translationKey) : $section->label,
                        'component' => $section->component,
                        'display_order' => $section->display_order,
                        'is_required' => $section->is_required,
                        'is_visible' => $section->is_visible,
                        'config' => $section->config ?? [],
                    ];
                })
                ->values()
                ->all(),
        ];
    }
}
