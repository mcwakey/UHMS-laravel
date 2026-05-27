<?php

namespace App\Services;

use App\Models\EmergencyCase;

class EmergencyReportService
{
    public function attendance(array $filters = [])
    {
        return EmergencyCase::query()
            ->with(['patient', 'bay', 'assignedDoctor', 'assignedNurse', 'disposedBy'])
            ->when($filters['date_from'] ?? null, fn ($q, $date) => $q->whereDate('arrival_time', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($q, $date) => $q->whereDate('arrival_time', '<=', $date))
            ->when($filters['triage_category'] ?? null, fn ($q, $category) => $q->where('triage_category', $category))
            ->when($filters['disposition'] ?? null, fn ($q, $disposition) => $q->where('disposition', $disposition))
            ->latest('arrival_time')
            ->paginate(30)
            ->withQueryString();
    }

    public function dispositionCounts(array $filters = []): array
    {
        return EmergencyCase::query()
            ->when($filters['date_from'] ?? null, fn ($q, $date) => $q->whereDate('arrival_time', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($q, $date) => $q->whereDate('arrival_time', '<=', $date))
            ->selectRaw('COALESCE(disposition, "ACTIVE") as disposition_key, COUNT(*) as total')
            ->groupBy('disposition_key')
            ->pluck('total', 'disposition_key')
            ->toArray();
    }
}
