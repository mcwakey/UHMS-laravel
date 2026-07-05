<?php

namespace App\Services;

class ClinicalFrequencyOptionService
{
    /**
     * Standard clinical frequencies used by consultation prescriptions and tasks.
     *
     * @return array<int, array{value:string,label:string,doses_per_day:int,task_instances:int,is_prn:bool}>
     */
    public function options(): array
    {
        return [
            ['value' => 'STAT', 'label' => 'STAT - immediately', 'doses_per_day' => 1, 'task_instances' => 1, 'is_prn' => false],
            ['value' => 'OD', 'label' => 'OD - once daily', 'doses_per_day' => 1, 'task_instances' => 1, 'is_prn' => false],
            ['value' => 'QD', 'label' => 'QD - once daily', 'doses_per_day' => 1, 'task_instances' => 1, 'is_prn' => false],
            ['value' => 'BD', 'label' => 'BD - twice daily', 'doses_per_day' => 2, 'task_instances' => 2, 'is_prn' => false],
            ['value' => 'BID', 'label' => 'BID - twice daily', 'doses_per_day' => 2, 'task_instances' => 2, 'is_prn' => false],
            ['value' => 'TDS', 'label' => 'TDS - three times daily', 'doses_per_day' => 3, 'task_instances' => 3, 'is_prn' => false],
            ['value' => 'TID', 'label' => 'TID - three times daily', 'doses_per_day' => 3, 'task_instances' => 3, 'is_prn' => false],
            ['value' => 'QDS', 'label' => 'QDS - four times daily', 'doses_per_day' => 4, 'task_instances' => 4, 'is_prn' => false],
            ['value' => 'QID', 'label' => 'QID - four times daily', 'doses_per_day' => 4, 'task_instances' => 4, 'is_prn' => false],
            ['value' => 'Q4H', 'label' => 'Every 4 hours', 'doses_per_day' => 6, 'task_instances' => 6, 'is_prn' => false],
            ['value' => 'Q6H', 'label' => 'Every 6 hours', 'doses_per_day' => 4, 'task_instances' => 4, 'is_prn' => false],
            ['value' => 'Q8H', 'label' => 'Every 8 hours', 'doses_per_day' => 3, 'task_instances' => 3, 'is_prn' => false],
            ['value' => 'Q12H', 'label' => 'Every 12 hours', 'doses_per_day' => 2, 'task_instances' => 2, 'is_prn' => false],
            ['value' => 'Q24H', 'label' => 'Every 24 hours', 'doses_per_day' => 1, 'task_instances' => 1, 'is_prn' => false],
            ['value' => 'QAM', 'label' => 'Every morning', 'doses_per_day' => 1, 'task_instances' => 1, 'is_prn' => false],
            ['value' => 'QPM', 'label' => 'Every evening', 'doses_per_day' => 1, 'task_instances' => 1, 'is_prn' => false],
            ['value' => 'QHS', 'label' => 'At night', 'doses_per_day' => 1, 'task_instances' => 1, 'is_prn' => false],
            ['value' => 'AC', 'label' => 'Before meals', 'doses_per_day' => 3, 'task_instances' => 3, 'is_prn' => false],
            ['value' => 'PC', 'label' => 'After meals', 'doses_per_day' => 3, 'task_instances' => 3, 'is_prn' => false],
            ['value' => 'WITH_MEALS', 'label' => 'With meals', 'doses_per_day' => 3, 'task_instances' => 3, 'is_prn' => false],
            ['value' => 'ALTERNATE_DAYS', 'label' => 'Alternate days', 'doses_per_day' => 1, 'task_instances' => 1, 'is_prn' => false],
            ['value' => 'WEEKLY', 'label' => 'Once weekly', 'doses_per_day' => 1, 'task_instances' => 1, 'is_prn' => false],
            ['value' => 'TWICE_WEEKLY', 'label' => 'Twice weekly', 'doses_per_day' => 1, 'task_instances' => 2, 'is_prn' => false],
            ['value' => 'THREE_TIMES_WEEKLY', 'label' => 'Three times weekly', 'doses_per_day' => 1, 'task_instances' => 3, 'is_prn' => false],
            ['value' => 'MONTHLY', 'label' => 'Monthly', 'doses_per_day' => 1, 'task_instances' => 1, 'is_prn' => false],
            ['value' => 'REVIEW_3D', 'label' => 'Review in 3 days', 'doses_per_day' => 1, 'task_instances' => 1, 'is_prn' => false],
            ['value' => 'REVIEW_1W', 'label' => 'Review in 1 week', 'doses_per_day' => 1, 'task_instances' => 1, 'is_prn' => false],
            ['value' => 'REVIEW_2W', 'label' => 'Review in 2 weeks', 'doses_per_day' => 1, 'task_instances' => 1, 'is_prn' => false],
            ['value' => 'PRN', 'label' => 'As needed', 'doses_per_day' => 1, 'task_instances' => 1, 'is_prn' => true],
            ['value' => 'SOS', 'label' => 'SOS - if needed', 'doses_per_day' => 1, 'task_instances' => 1, 'is_prn' => true],
            ['value' => 'CONTINUOUS', 'label' => 'Continuous', 'doses_per_day' => 1, 'task_instances' => 1, 'is_prn' => false],
            ['value' => 'OTHER', 'label' => 'Other', 'doses_per_day' => 1, 'task_instances' => 1, 'is_prn' => false],
        ];
    }

    public function values(): array
    {
        return array_column($this->options(), 'value');
    }

    public function doseMap(): array
    {
        return collect($this->options())->mapWithKeys(fn (array $option) => [
            $option['value'] => $option['doses_per_day'],
        ])->all();
    }

    public function labelFor(?string $value): string
    {
        $option = collect($this->options())->firstWhere('value', strtoupper((string) $value));

        return $option['label'] ?? (string) $value;
    }

    public function taskInstanceCount(?string $value): int
    {
        $option = collect($this->options())->firstWhere('value', strtoupper((string) $value));

        return max(1, (int) ($option['task_instances'] ?? 1));
    }

    public function isPrn(?string $value): bool
    {
        $option = collect($this->options())->firstWhere('value', strtoupper((string) $value));

        return (bool) ($option['is_prn'] ?? false);
    }
}
