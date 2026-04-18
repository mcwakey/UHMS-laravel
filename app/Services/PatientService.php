<?php

namespace App\Services;

use App\Models\Patient;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;

class PatientService
{
    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = Patient::with('registeredBy')
            ->select('patients.*')
            ->addSelect(['last_visit_date' => \App\Models\Visit::select('visit_date')
                ->whereColumn('patient_id', 'patients.id')
                ->latest('visit_date')
                ->limit(1)
            ]);

        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        if (!empty($filters['gender'])) {
            $query->where('gender', $filters['gender']);
        }

        if (!empty($filters['blood_group'])) {
            $query->where('blood_group', $filters['blood_group']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['region'])) {
            $query->where('region', $filters['region']);
        }

        return $query->latest()->paginate($filters['per_page'] ?? 15);
    }

    public function create(array $data): Patient
    {
        $data['patient_number'] = Patient::generatePatientNumber();
        $data['registered_by'] = auth()->id();

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
        $patient->status = $patient->status === 'active' ? 'inactive' : 'active';
        $patient->save();
        return $patient;
    }
}
