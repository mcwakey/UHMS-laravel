<?php

namespace App\Services\Consultation\Specialty;

use Symfony\Component\HttpFoundation\StreamedResponse;

class ConsultationSpecialtyReportExportService
{
    public function __construct(private readonly ConsultationSpecialtyAnalyticsService $analytics) {}

    public function csv(array $filters = [], string $dataset = 'summary'): StreamedResponse
    {
        $payload = $this->analytics->dashboardPayload($filters);
        $filename = 'consultation-specialties-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($payload, $dataset): void {
            $handle = fopen('php://output', 'w');

            foreach ($this->sections($payload, $dataset) as $section => $rows) {
                fputcsv($handle, [strtoupper(str_replace('_', ' ', $section))]);

                if ($rows === []) {
                    fputcsv($handle, [__('reports.no_data')]);
                    fputcsv($handle, []);
                    continue;
                }

                fputcsv($handle, array_keys($rows[0]));
                foreach ($rows as $row) {
                    fputcsv($handle, $row);
                }
                fputcsv($handle, []);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    private function sections(array $payload, string $dataset): array
    {
        $sections = [
            'summary' => [collect($payload['summary'])->map(fn ($value) => is_numeric($value) ? $value : (string) $value)->all()],
            'volume_by_specialty' => $payload['volume']['by_specialty']->map(fn ($row) => [
                'specialty' => $row->name,
                'code' => $row->code,
                'consultations' => $row->consultations_count,
                'entries' => $row->entries_count,
            ])->all(),
            'section_completion' => $payload['sections']->map(fn ($row) => [
                'specialty' => $row->name,
                'section' => $row->section_label,
                'entries' => $row->entry_count,
                'consultations_with_section' => $row->consultations_with_section,
                'completion_rate' => $row->completion_rate,
            ])->all(),
            'doctor_workload' => $payload['workload']->map(fn ($row) => [
                'doctor' => $row->doctor_name,
                'consultations' => $row->consultations_count,
                'entries' => $row->entries_count,
                'specialties' => $row->specialty_count,
            ])->all(),
            'order_sets' => $payload['order_sets']['applications_by_order_set']->map(fn ($row) => [
                'specialty' => $row->profile_name,
                'order_set' => $row->order_set_name,
                'applications' => $row->applications_count,
            ])->all(),
            'billing' => $payload['billing']['applications_by_status']->map(fn ($row) => [
                'status' => $row->status,
                'count' => $row->count,
            ])->all(),
            'revenue' => $payload['revenue']['by_specialty']->map(fn ($row) => [
                'specialty' => $row->name,
                'billing_applications' => $row->billing_applications_count,
                'patient_payable' => $row->patient_payable,
                'paid_amount' => $row->paid_amount,
                'balance' => $row->balance,
            ])->all(),
        ];

        return $dataset === 'all' ? $sections : [$dataset => $sections[$dataset] ?? $sections['summary']];
    }
}
