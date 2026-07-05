<?php

namespace App\Data\Consultation\Specialty;

use App\Models\ConsultationSpecialtyProfile;

class ConsultationSpecialtyReadinessResult
{
    public function __construct(
        public readonly ?ConsultationSpecialtyProfile $profile,
        public readonly string $status,
        public readonly int $score,
        public readonly array $blockingItems,
        public readonly array $warningItems,
        public readonly array $optionalItems,
        public readonly array $completedItems,
        public readonly array $items,
        public readonly bool $canComplete,
        public readonly bool $isFallback,
        public readonly string $summary,
    ) {}

    public function toArray(): array
    {
        return [
            'profile' => $this->profile ? [
                'id' => $this->profile->id,
                'code' => $this->profile->code,
                'name' => $this->profile->name,
                'translated_name' => $this->profile->translatedName(),
                'icon' => $this->profile->icon,
                'color' => $this->profile->color,
            ] : null,
            'status' => $this->status,
            'score' => $this->score,
            'blockingItems' => $this->blockingItems,
            'warningItems' => $this->warningItems,
            'optionalItems' => $this->optionalItems,
            'completedItems' => $this->completedItems,
            'items' => $this->items,
            'canComplete' => $this->canComplete,
            'isFallback' => $this->isFallback,
            'summary' => $this->summary,
        ];
    }
}
