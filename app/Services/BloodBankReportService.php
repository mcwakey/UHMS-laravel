<?php

namespace App\Services;

use App\Models\BloodIssue;
use App\Models\BloodRequest;
use App\Models\BloodUnit;

class BloodBankReportService
{
    public function inventory(array $filters = [])
    {
        return BloodUnit::query()
            ->with(['donor', 'donation', 'storageLocation', 'reservedForRequest.patient'])
            ->when($filters['blood_group'] ?? null, fn ($q, $v) => $q->where('blood_group', $v))
            ->when($filters['component_type'] ?? null, fn ($q, $v) => $q->where('component_type', $v))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', strtoupper($v)))
            ->orderBy('expiry_date')
            ->paginate(30)
            ->withQueryString();
    }

    public function requests(array $filters = [])
    {
        return BloodRequest::query()
            ->with(['patient', 'visit', 'admission', 'emergencyCase', 'requestedBy', 'approvedBy'])
            ->when($filters['date_from'] ?? null, fn ($q, $v) => $q->whereDate('requested_at', '>=', $v))
            ->when($filters['date_to'] ?? null, fn ($q, $v) => $q->whereDate('requested_at', '<=', $v))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', strtoupper($v)))
            ->when($filters['blood_group'] ?? null, fn ($q, $v) => $q->where('blood_group', $v))
            ->latest('requested_at')
            ->paginate(30)
            ->withQueryString();
    }

    public function issues(array $filters = [])
    {
        return BloodIssue::query()
            ->with(['request', 'unit', 'patient', 'issuedBy', 'transfusedBy'])
            ->when($filters['date_from'] ?? null, fn ($q, $v) => $q->whereDate('issued_at', '>=', $v))
            ->when($filters['date_to'] ?? null, fn ($q, $v) => $q->whereDate('issued_at', '<=', $v))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('transfusion_status', strtoupper($v)))
            ->latest('issued_at')
            ->paginate(30)
            ->withQueryString();
    }
}
