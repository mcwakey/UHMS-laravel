<?php

namespace App\Services;

use App\Models\Patient;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PatientService
{
    public function __construct(
        private PatientIdGeneratorService $idGenerator
    ) {}

    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = Patient::with(['registeredBy', 'primaryInsurance.insuranceProvider'])
            ->select('patients.*')
            ->addSelect(['last_visit_date' => \App\Models\Visit::select('visit_date')
                ->whereColumn('patient_id', 'patients.id')
                ->latest('visit_date')
                ->limit(1)
            ]);

        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        // Gender and blood_group filters removed from patient list (kept in DB).

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['city'])) {
            $query->where('city', 'like', '%' . $filters['city'] . '%');
        }

        if (!empty($filters['insurance_provider_id'])) {
            $query->whereHas('insurances', function ($q) use ($filters) {
                $q->where('insurance_provider_id', $filters['insurance_provider_id'])
                  ->where('is_active', true);
            });
        }

        if (!empty($filters['visit_from'])) {
            $query->whereExists(function ($q) use ($filters) {
                $q->selectRaw('1')
                  ->from('visits')
                  ->whereColumn('visits.patient_id', 'patients.id')
                  ->whereNull('visits.deleted_at')
                  ->havingRaw('MAX(visit_date) >= ?', [$filters['visit_from']])
                  ->groupBy('visits.patient_id');
            });
        }

        if (!empty($filters['visit_to'])) {
            $query->whereExists(function ($q) use ($filters) {
                $q->selectRaw('1')
                  ->from('visits')
                  ->whereColumn('visits.patient_id', 'patients.id')
                  ->whereNull('visits.deleted_at')
                  ->havingRaw('MAX(visit_date) <= ?', [$filters['visit_to']])
                  ->groupBy('visits.patient_id');
            });
        }

        return $query->latest()->paginate($filters['per_page'] ?? 15);
    }

    public function create(array $data): Patient
    {
        $data['patient_number'] = $this->idGenerator->generate();
        $data['registered_by'] = Auth::id();

        if (isset($data['avatar']) && $data['avatar']) {
            $data['avatar'] = $data['avatar']->store('patients', 'public');
        }

        return Patient::create($data);
    }

    public function update(Patient $patient, array $data): Patient
    {
        if (isset($data['avatar']) && $data['avatar']) {
            if ($patient->avatar) {
                Storage::disk('public')->delete($patient->avatar);
            }
            $data['avatar'] = $data['avatar']->store('patients', 'public');
        } else {
            unset($data['avatar']);
        }

        $patient->update($data);
        return $patient->fresh();
    }

    public function toggleStatus(Patient $patient): Patient
    {
        // Do not allow toggling away from deceased via this method.
        if ($patient->status === 'deceased') {
            return $patient;
        }

        $patient->status = $patient->status === 'active' ? 'inactive' : 'active';
        $patient->save();
        return $patient;
    }

    public function markDeceased(Patient $patient, array $data): Patient
    {
        $patient->update([
            'status'              => 'deceased',
            'is_deceased'         => true,
            'deceased_at'         => $data['deceased_at'],
            'cause_of_death'      => $data['cause_of_death'] ?? null,
            'deceased_notes'      => $data['deceased_notes'] ?? null,
            'marked_deceased_by'  => Auth::id(),
        ]);

        return $patient->fresh();
    }
}
