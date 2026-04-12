<?php

namespace App\Services;

use App\Models\Bed;
use App\Models\Ward;
use Illuminate\Pagination\LengthAwarePaginator;

class WardService
{
    public function listWards(array $filters = []): LengthAwarePaginator
    {
        $query = Ward::with('department')
            ->withCount(['beds', 'beds as available_beds_count' => function ($q) {
                $q->where('status', 'available');
            }, 'beds as occupied_beds_count' => function ($q) {
                $q->where('status', 'occupied');
            }]);

        if (!empty($filters['search'])) {
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
        $ward->update(['is_active' => !$ward->is_active]);
        return $ward;
    }

    public function listBeds(array $filters = []): LengthAwarePaginator
    {
        $query = Bed::with(['ward', 'currentAdmission.patient']);

        if (!empty($filters['ward_id'])) {
            $query->where('ward_id', $filters['ward_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['bed_type'])) {
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

        if ($wardId) {
            $query->where('ward_id', $wardId);
        }

        return $query->orderBy('ward_id')->orderBy('bed_number')->get();
    }

    public function getBedMap()
    {
        return Ward::active()
            ->with(['beds' => function ($q) {
                $q->orderBy('bed_number');
            }, 'beds.currentAdmission.patient'])
            ->withCount(['beds', 'beds as available_beds_count' => function ($q) {
                $q->where('status', 'available');
            }, 'beds as occupied_beds_count' => function ($q) {
                $q->where('status', 'occupied');
            }])
            ->orderBy('name')
            ->get();
    }
}
