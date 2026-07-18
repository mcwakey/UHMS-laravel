<?php

namespace App\Services;

use App\Enums\AdmissionLocationEvent;
use App\Enums\BedStatus;
use App\Models\Admission;
use App\Models\AdmissionLocationHistory;
use App\Models\Bed;
use App\Models\BedReservation;
use App\Models\Ward;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class WardService
{
    public function __construct(private InpatientWorkspaceScope $inpatientScope) {}

    public function listWards(array $filters = []): LengthAwarePaginator
    {
        $query = Ward::with('department')
            ->withCount(['beds', 'beds as available_beds_count' => function ($q) {
                $q->where('status', 'available');
            }, 'beds as occupied_beds_count' => function ($q) {
                $q->where('status', 'occupied');
            }]);
        $this->inpatientScope->wards($query);

        if (! empty($filters['search'])) {
            $query->search($filters['search']);
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $query->where('is_active', $filters['is_active']);
        }

        return $query->orderBy('name')->paginate($filters['per_page'] ?? 15);
    }

    public function createWard(array $data): Ward
    {
        return Ward::create($data);
    }

    public function updateWard(Ward $ward, array $data): Ward
    {
        $ward->update($data);

        return $ward->fresh();
    }

    public function toggleWard(Ward $ward): Ward
    {
        $ward->update(['is_active' => ! $ward->is_active]);

        return $ward;
    }

    public function listBeds(array $filters = []): LengthAwarePaginator
    {
        $query = Bed::with(['ward', 'currentAdmission.patient', 'activeReservation.admissionRequest.patient', 'statusChangedBy']);
        $this->inpatientScope->beds($query);

        if (! empty($filters['ward_id'])) {
            $query->where('ward_id', $filters['ward_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['bed_type'])) {
            $query->where('bed_type', $filters['bed_type']);
        }

        return $query->orderBy('ward_id')->orderBy('bed_number')->paginate($filters['per_page'] ?? 25);
    }

    public function createBed(array $data): Bed
    {
        return Bed::create($data);
    }

    public function updateBed(Bed $bed, array $data): Bed
    {
        $bed->update($data);

        return $bed->fresh();
    }

    public function getAvailableBeds(?int $wardId = null)
    {
        $query = Bed::with('ward')->where('status', 'available');
        $this->inpatientScope->beds($query);

        if ($wardId) {
            $query->where('ward_id', $wardId);
        }

        return $query->orderBy('ward_id')->orderBy('bed_number')->get();
    }

    public function activeWards(): Collection
    {
        $query = Ward::active();
        $this->inpatientScope->wards($query);

        return $query->orderBy('name')->get();
    }

    public function getBedMap(array $filters = [])
    {
        $query = Ward::active();
        $this->inpatientScope->wards($query);

        return $query
            ->with(['beds' => function ($q) {
                $q->orderBy('bed_number');
            }, 'beds.currentAdmission.patient', 'beds.currentAdmission.nursingTasks', 'beds.currentAdmission.dischargeClearances', 'beds.currentAdmission.dischargeSummaryRecord', 'beds.currentAdmission.visit.vitals', 'beds.currentAdmission.visit.latestInvoice', 'beds.activeReservation.admissionRequest.patient', 'beds.statusChangedBy'])
            ->withCount(['beds', 'beds as available_beds_count' => function ($q) {
                $q->where('status', 'available');
            }, 'beds as occupied_beds_count' => function ($q) {
                $q->where('status', 'occupied');
            }])
            ->when(! empty($filters['ward_id']), fn ($query) => $query->where('id', $filters['ward_id']))
            ->orderBy('name')
            ->get()
            ->map(function (Ward $ward) use ($filters) {
                if (! empty($filters['status']) || ! empty($filters['bed_type']) || ! empty($filters['search'])) {
                    $ward->setRelation('beds', $ward->beds->filter(function (Bed $bed) use ($filters) {
                        if (! empty($filters['status']) && $bed->status->value !== $filters['status']) {
                            return false;
                        }
                        if (! empty($filters['bed_type']) && $bed->bed_type->value !== $filters['bed_type']) {
                            return false;
                        }
                        if (! empty($filters['search'])) {
                            $term = mb_strtolower($filters['search']);
                            $haystack = mb_strtolower(implode(' ', array_filter([
                                $bed->bed_number,
                                $bed->ward?->name,
                                $bed->currentAdmission?->patient?->full_name,
                                $bed->activeReservation?->admissionRequest?->patient?->full_name,
                            ])));

                            return str_contains($haystack, $term);
                        }

                        return true;
                    })->values());
                }

                return $ward;
            })
            ->filter(fn (Ward $ward) => $ward->beds->isNotEmpty() || empty($filters['search']))
            ->values();
    }

    public function getBedCapacityStats(array $filters = []): array
    {
        $bedQuery = Bed::query();
        $this->inpatientScope->beds($bedQuery);
        if (! empty($filters['ward_id'])) {
            $bedQuery->where('ward_id', $filters['ward_id']);
        }

        $counts = (clone $bedQuery)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $total = (int) $counts->sum();
        $occupied = (int) ($counts[BedStatus::OCCUPIED->value] ?? 0);

        $wardIds = ! empty($filters['ward_id']) ? [(int) $filters['ward_id']] : null;

        return [
            'total' => $total,
            'available' => (int) ($counts[BedStatus::AVAILABLE->value] ?? 0),
            'reserved' => (int) ($counts[BedStatus::RESERVED->value] ?? 0),
            'occupied' => $occupied,
            'cleaning' => (int) ($counts[BedStatus::CLEANING->value] ?? 0),
            'maintenance' => (int) ($counts[BedStatus::MAINTENANCE->value] ?? 0),
            'blocked' => (int) ($counts[BedStatus::BLOCKED->value] ?? 0),
            'isolation' => (int) ($counts[BedStatus::ISOLATION->value] ?? 0),
            'occupancy_percentage' => $total > 0 ? round(($occupied / $total) * 100, 1) : 0,
            'active_reservations' => BedReservation::active()
                ->when($wardIds, fn ($query) => $query->whereHas('bed', fn ($bed) => $bed->whereIn('ward_id', $wardIds)))
                ->count(),
            'expiring_reservations' => BedReservation::active()
                ->whereNotNull('expires_at')
                ->whereBetween('expires_at', [now(), now()->addHour()])
                ->when($wardIds, fn ($query) => $query->whereHas('bed', fn ($bed) => $bed->whereIn('ward_id', $wardIds)))
                ->count(),
            'admissions_in_ward' => Admission::active()
                ->when($wardIds, fn ($query) => $query->whereHas('bed', fn ($bed) => $bed->whereIn('ward_id', $wardIds)))
                ->count(),
            'discharges_today' => Admission::query()
                ->whereDate('actual_discharge_date', today())
                ->when($wardIds, fn ($query) => $query->whereHas('bed', fn ($bed) => $bed->whereIn('ward_id', $wardIds)))
                ->count(),
            'transfers_today' => AdmissionLocationHistory::query()
                ->whereIn('event_type', [
                    AdmissionLocationEvent::BED_TRANSFERRED->value,
                    AdmissionLocationEvent::WARD_TRANSFERRED->value,
                ])
                ->whereDate('moved_at', today())
                ->when($wardIds, function ($query) use ($wardIds) {
                    $query->where(function ($inner) use ($wardIds) {
                        $inner->whereIn('from_ward_id', $wardIds)->orWhereIn('to_ward_id', $wardIds);
                    });
                })
                ->count(),
        ];
    }
}
