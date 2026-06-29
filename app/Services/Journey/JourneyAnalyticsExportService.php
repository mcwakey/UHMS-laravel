<?php

namespace App\Services\Journey;

use Illuminate\Support\Str;

/**
 * Phase 9.8 — aggregate-only CSV export of journey analytics. No patient-level data
 * is ever exported (the snapshots contain none). The controller enforces permission +
 * scope before calling these.
 */
class JourneyAnalyticsExportService
{
    public function __construct(
        private JourneyAnalyticsQueryService $query,
        private JourneyPredictionAccuracyService $accuracy,
    ) {}

    /** @return array{filename:string,content:string} */
    public function export(string $dataset, array $filters): array
    {
        return match ($dataset) {
            'matrix' => $this->csv('journey-handoff-matrix', ['from', 'to', 'handoff_count', 'breached_count', 'critical_count', 'avg_wait', 'avg_ack', 'avg_resolve', 'breach_rate'], $this->query->handoffPathRanking($filters)),
            'departments' => $this->csv('journey-department-ranking', ['type', 'handoff_count', 'breached_count', 'critical_count', 'avg_wait', 'breach_rate'], $this->query->departmentRanking($filters, 'to')),
            'risk_paths' => $this->csv('journey-risk-paths', ['from', 'to', 'evaluated', 'errors'], $this->accuracy->worstPaths($filters)),
            'prediction_accuracy', 'prediction_outcomes_summary' => $this->summaryCsv($filters),
            default => $this->csv('journey-cause-breakdown', ['cause', 'handoff_count', 'breached_count'], $this->query->causeBreakdown($filters)),
        };
    }

    /** Accuracy metrics as a key/value CSV (aggregate only). */
    private function summaryCsv(array $filters): array
    {
        $summary = $this->accuracy->accuracySummary($filters);
        $rows = [];
        foreach (['evaluated', 'precision', 'recall', 'false_alarm_rate', 'miss_rate', 'overall_accuracy', 'eta_error_avg', 'true_positive', 'false_positive', 'false_negative', 'true_negative'] as $key) {
            $rows[] = ['metric' => $key, 'value' => $summary[$key] ?? ''];
        }

        return $this->csv('journey-prediction-accuracy', ['metric', 'value'], $rows);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array{filename:string,content:string}
     */
    private function csv(string $name, array $headers, array $rows): array
    {
        $lines = [implode(',', $headers)];
        foreach ($rows as $row) {
            $lines[] = implode(',', array_map(
                fn ($header) => $this->escape($row[$header] ?? ''),
                $headers,
            ));
        }

        return [
            'filename' => $name.'-'.now()->format('Ymd_His').'.csv',
            'content' => implode("\n", $lines)."\n",
        ];
    }

    private function escape(mixed $value): string
    {
        $value = (string) $value;

        return Str::contains($value, [',', '"', "\n"]) ? '"'.str_replace('"', '""', $value).'"' : $value;
    }
}
