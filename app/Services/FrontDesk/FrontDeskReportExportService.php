<?php

namespace App\Services\FrontDesk;

use App\Models\User;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams Front Desk report CSVs (Phase 18D). The dataset ({columns, rows}) is
 * built privacy-safe by {@see FrontDeskReportService} — this service only writes
 * it out, following the existing maternity export convention.
 */
class FrontDeskReportExportService
{
    /**
     * @param  array{columns: array<int, string>, rows: iterable}  $dataset
     * @param  array<string, mixed>  $filters
     */
    public function csv(string $filename, array $dataset, array $filters, User $user): StreamedResponse
    {
        return response()->streamDownload(function () use ($dataset, $filters, $user) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [config('app.name', 'UHMS')]);
            fputcsv($handle, [__('reports.generated_by'), $user->full_name ?? $user->email]);
            fputcsv($handle, [__('reports.generated_at'), now()->format('Y-m-d H:i:s')]);
            fputcsv($handle, []);

            fputcsv($handle, [__('front_desk.reports.filters')]);
            foreach ($filters as $key => $value) {
                if ($value !== null && $value !== '') {
                    fputcsv($handle, [$key, is_scalar($value) ? $value : json_encode($value)]);
                }
            }
            fputcsv($handle, []);

            fputcsv($handle, $dataset['columns'] ?? []);
            foreach (($dataset['rows'] ?? []) as $row) {
                fputcsv($handle, is_array($row) ? $row : (array) $row);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
