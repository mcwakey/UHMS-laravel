<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ReportFilterService
{
    public function operational(Request $request): array
    {
        $validated = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'status' => ['nullable', 'string', 'max:80'],
            'department_id' => ['nullable', 'integer'],
            'user_id' => ['nullable', 'integer'],
            'patient_id' => ['nullable', 'integer'],
            'visit_type' => ['nullable', 'string', 'max:80'],
            'blood_group' => ['nullable', 'string', 'max:5'],
        ]);

        $defaults = $this->defaultDateRange();

        return array_merge($validated, [
            'date_from' => $validated['date_from'] ?? $defaults['date_from'],
            'date_to' => $validated['date_to'] ?? $defaults['date_to'],
        ]);
    }

    public function summarize(array $filters): array
    {
        return collect($filters)
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->map(fn ($value, $key) => [
                'label' => __('reports.filters.' . $key),
                'value' => $value,
            ])
            ->values()
            ->all();
    }

    protected function defaultDateRange(): array
    {
        return [
            'date_from' => Carbon::now()->startOfMonth()->toDateString(),
            'date_to' => Carbon::now()->toDateString(),
        ];
    }
}
