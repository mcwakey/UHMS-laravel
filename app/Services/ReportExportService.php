<?php

namespace App\Services;

use App\Models\User;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExportService
{
    public function csv(string $filename, array $data, array $filters, User $user): StreamedResponse
    {
        return response()->streamDownload(function () use ($data, $filters, $user) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [config('app.name', 'UHMS')]);
            fputcsv($handle, [__('reports.generated_by'), $user->name ?? $user->email]);
            fputcsv($handle, [__('reports.generated_at'), now()->format('Y-m-d H:i:s')]);
            fputcsv($handle, []);
            fputcsv($handle, [__('reports.print.filter_summary')]);
            foreach ($filters as $key => $value) {
                if ($value !== null && $value !== '') {
                    fputcsv($handle, [__('reports.filters.' . $key), $value]);
                }
            }
            fputcsv($handle, []);

            fputcsv($handle, $data['columns']);
            foreach ($data['rows'] as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
