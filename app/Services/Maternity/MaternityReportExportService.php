<?php

namespace App\Services\Maternity;

use App\Models\User;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MaternityReportExportService
{
    public function csv(string $filename, array $report, array $filters, User $user): StreamedResponse
    {
        return response()->streamDownload(function () use ($report, $filters, $user) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [config('app.name', 'UHMS')]);
            fputcsv($handle, [__('reports.generated_by'), $user->name ?? $user->email]);
            fputcsv($handle, [__('reports.generated_at'), now()->format('Y-m-d H:i:s')]);
            fputcsv($handle, []);
            fputcsv($handle, [__('maternity.report_filters')]);
            foreach ($filters as $key => $value) {
                if ($value !== null && $value !== '') {
                    fputcsv($handle, [$key, $value]);
                }
            }
            fputcsv($handle, []);
            fputcsv($handle, $report['columns'] ?? []);
            foreach (($report['rows'] ?? collect()) as $row) {
                fputcsv($handle, is_array($row) ? $row : (array) $row);
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
