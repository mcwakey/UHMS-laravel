<?php

namespace App\Services\Consultation\Specialty;

class ConsultationSpecialtySummaryFormatter
{
    public function sentenceList(array $items): string
    {
        return collect($items)->map(fn ($item) => trim((string) $item))->filter()->unique()->implode('; ');
    }

    public function keyValue(array $entry, array $labels = []): string
    {
        return collect($entry)
            ->map(function ($value, string $key) use ($labels) {
                if (! $this->meaningful($value)) {
                    return null;
                }

                return ($labels[$key] ?? $this->label($key)).': '.$this->value($value);
            })
            ->filter()
            ->implode('; ');
    }

    public function complaints(array $items): string
    {
        return $this->sentenceList(collect($items)->map(fn ($item) => $item['description'] ?? null)->all());
    }

    public function diagnoses(array $items): string
    {
        return $this->sentenceList(collect($items)->map(function ($item) {
            return trim(collect([
                $item['description'] ?? null,
                ! empty($item['icd_code']) ? '('.$item['icd_code'].')' : null,
            ])->filter()->implode(' '));
        })->all());
    }

    public function investigations(array $items): string
    {
        return $this->sentenceList(collect($items)->map(fn ($item) => $item['description'] ?? $item['type'] ?? null)->all());
    }

    public function procedures(array $items): string
    {
        return $this->sentenceList(collect($items)->map(function ($item) {
            return trim(collect([
                $item['name'] ?? 'Procedure',
                ! empty($item['status']) ? '('.$item['status'].')' : null,
                $item['indication'] ?? null,
            ])->filter()->implode(' - '));
        })->all());
    }

    public function prescriptions(array $items): string
    {
        return $this->sentenceList(collect($items)->flatMap(fn ($prescription) => $prescription['items'] ?? [])->map(function ($item) {
            return trim(collect([
                $item['drug_name'] ?? null,
                $item['dosage'] ?? null,
                $item['frequency'] ?? null,
                $item['duration'] ?? null,
                $item['instructions'] ?? null,
            ])->filter()->implode(' '));
        })->all());
    }

    public function tasks(array $items): string
    {
        return $this->sentenceList(collect($items)->map(function ($item) {
            return trim(collect([
                $item['title'] ?? null,
                $item['description'] ?? null,
                ! empty($item['due_date']) ? 'Due '.$item['due_date'] : null,
            ])->filter()->implode(' - '));
        })->all());
    }

    public function readinessWarnings(array $items): string
    {
        return $this->sentenceList(collect($items)->map(fn ($item) => $item['message'] ?? $item['label'] ?? null)->all());
    }

    public function value($value): string
    {
        if (is_bool($value)) {
            return $value ? __('common.yes') : __('common.no');
        }

        if (is_array($value)) {
            return collect($value)->map(fn ($item) => $this->value($item))->filter()->implode(', ');
        }

        return trim((string) $value);
    }

    public function meaningful($value): bool
    {
        if (is_bool($value)) {
            return true;
        }

        if (is_array($value)) {
            return collect($value)->contains(fn ($item) => $this->meaningful($item));
        }

        return filled($value);
    }

    private function label(string $key): string
    {
        $translationKey = 'consultation_specialties.forms.fields.'.$key;

        return __($translationKey) !== $translationKey
            ? __($translationKey)
            : str($key)->replace('_', ' ')->title()->toString();
    }
}
