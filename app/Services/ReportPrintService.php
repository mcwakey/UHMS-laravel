<?php

namespace App\Services;

use App\Models\User;

class ReportPrintService
{
    public function metadata(array $data, array $filters, User $user): array
    {
        return [
            'title' => $data['meta']['title'] ?? __('reports.hub_title'),
            'subtitle' => __('reports.print.filter_summary'),
            'generatedAt' => now(),
            'generatedBy' => $user->name ?? $user->email,
            'filters' => $filters,
        ];
    }
}
