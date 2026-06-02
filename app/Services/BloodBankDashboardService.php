<?php

namespace App\Services;

use App\Models\BloodIssue;
use App\Models\BloodRequest;
use App\Models\BloodUnit;

class BloodBankDashboardService
{
    public function dashboard(): array
    {
        return [
            'summary' => [
                'available_units' => BloodUnit::where('status', BloodUnit::STATUS_AVAILABLE)->count(),
                'quarantined_units' => BloodUnit::where('status', BloodUnit::STATUS_QUARANTINED)->count(),
                'pending_requests' => BloodRequest::where('status', BloodRequest::STATUS_PENDING)->count(),
                'issued_today' => BloodIssue::whereDate('issued_at', today())->count(),
                'expiring_soon' => BloodUnit::whereIn('status', [BloodUnit::STATUS_AVAILABLE, BloodUnit::STATUS_RESERVED, BloodUnit::STATUS_CROSSMATCHED])
                    ->whereBetween('expiry_date', [today(), today()->addDays(7)])
                    ->count(),
            ],
            'inventoryByGroup' => BloodUnit::selectRaw('blood_group, component_type, status, COUNT(*) as total')
                ->groupBy('blood_group', 'component_type', 'status')
                ->orderBy('blood_group')
                ->get(),
            'pendingRequests' => BloodRequest::with(['patient', 'visit', 'requestedBy'])
                ->whereIn('status', [BloodRequest::STATUS_PENDING, BloodRequest::STATUS_APPROVED, BloodRequest::STATUS_PARTIALLY_ISSUED])
                ->latest('requested_at')
                ->limit(10)
                ->get(),
            'expiringUnits' => BloodUnit::with('storageLocation')
                ->whereIn('status', [BloodUnit::STATUS_AVAILABLE, BloodUnit::STATUS_RESERVED, BloodUnit::STATUS_CROSSMATCHED])
                ->whereBetween('expiry_date', [today(), today()->addDays(7)])
                ->orderBy('expiry_date')
                ->limit(10)
                ->get(),
        ];
    }
}
