<?php

namespace App\Services;

use App\Models\BloodCrossmatch;
use App\Models\BloodDonationTest;
use App\Models\BloodDonorScreening;
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

    /** Donor Screening Report — eligibility decisions and deferrals. */
    public function donorScreenings(array $filters = [])
    {
        return BloodDonorScreening::query()
            ->with(['donor', 'assessedBy', 'reviewedBy'])
            ->whereNotNull('eligibility_decision')
            ->when($filters['date_from'] ?? null, fn ($q, $v) => $q->whereDate('reviewed_at', '>=', $v))
            ->when($filters['date_to'] ?? null, fn ($q, $v) => $q->whereDate('reviewed_at', '<=', $v))
            ->when($filters['decision'] ?? null, fn ($q, $v) => $q->where('eligibility_decision', strtoupper($v)))
            ->latest('reviewed_at')
            ->paginate(20, ['*'], 'screening_page')
            ->withQueryString();
    }

    /** Infectious-disease screening grouped per donation. */
    public function infectiousDiseaseScreening(array $filters = [])
    {
        return BloodDonationTest::query()
            ->with(['donation.donor', 'donation.unit', 'performedBy', 'verifiedBy'])
            ->when($filters['date_from'] ?? null, fn ($q, $v) => $q->whereDate('performed_at', '>=', $v))
            ->when($filters['date_to'] ?? null, fn ($q, $v) => $q->whereDate('performed_at', '<=', $v))
            ->when($filters['result'] ?? null, fn ($q, $v) => $q->where('result', strtoupper($v)))
            ->latest('performed_at')
            ->paginate(40, ['*'], 'ids_page')
            ->withQueryString();
    }

    /** Compatibility / Crossmatch Report. */
    public function crossmatches(array $filters = [])
    {
        return BloodCrossmatch::query()
            ->with(['request', 'unit', 'patient', 'performedBy', 'verifiedBy'])
            ->when($filters['date_from'] ?? null, fn ($q, $v) => $q->whereDate('performed_at', '>=', $v))
            ->when($filters['date_to'] ?? null, fn ($q, $v) => $q->whereDate('performed_at', '<=', $v))
            ->when($filters['result'] ?? null, fn ($q, $v) => $q->where('result', strtoupper($v)))
            ->latest('performed_at')
            ->paginate(20, ['*'], 'crossmatch_page')
            ->withQueryString();
    }

    /** Transfusion Reaction Report. */
    public function transfusionReactions(array $filters = [])
    {
        return BloodIssue::query()
            ->with(['unit', 'patient', 'transfusedBy'])
            ->where('reaction_occurred', true)
            ->when($filters['date_from'] ?? null, fn ($q, $v) => $q->whereDate('transfused_at', '>=', $v))
            ->when($filters['date_to'] ?? null, fn ($q, $v) => $q->whereDate('transfused_at', '<=', $v))
            ->latest('transfused_at')
            ->paginate(20, ['*'], 'reaction_page')
            ->withQueryString();
    }
}
