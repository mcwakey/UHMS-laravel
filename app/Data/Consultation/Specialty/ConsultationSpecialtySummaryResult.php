<?php

namespace App\Data\Consultation\Specialty;

use App\Models\ConsultationSpecialtyProfile;
use Illuminate\Support\HtmlString;

class ConsultationSpecialtySummaryResult
{
    public function __construct(
        public readonly ?ConsultationSpecialtyProfile $profile,
        public readonly string $title,
        public readonly string $status,
        public readonly array $sections,
        public readonly string $plainText,
        public readonly string $html,
        public readonly array $warnings,
        public readonly string $generatedAt,
        public readonly bool $isFallback,
        public readonly array $sourceCompleteness,
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
            'title' => $this->title,
            'status' => $this->status,
            'sections' => $this->sections,
            'plainText' => $this->plainText,
            'html' => $this->html,
            'warnings' => $this->warnings,
            'generatedAt' => $this->generatedAt,
            'isFallback' => $this->isFallback,
            'sourceCompleteness' => $this->sourceCompleteness,
        ];
    }

    public function plainText(): string
    {
        return $this->plainText;
    }

    public function html(): HtmlString
    {
        return new HtmlString($this->html);
    }
}
