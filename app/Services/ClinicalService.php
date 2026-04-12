<?php

namespace App\Services;

use App\Models\IcdCode;
use App\Models\PatientProcedure;
use App\Models\Procedure;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ClinicalService
{
    /*
    |--------------------------------------------------------------------------
    | ICD-10 Code Management
    |--------------------------------------------------------------------------
    */

    public function searchIcdCodes(string $term, int $limit = 20): Collection
    {
        return IcdCode::search($term)
            ->select('id', 'code', 'description', 'category')
            ->limit($limit)
            ->get();
    }

    public function listIcdCodes(array $filters = [], int $perPage = 50): LengthAwarePaginator
    {
        $query = IcdCode::query();

        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        if (!empty($filters['chapter'])) {
            $query->byChapter($filters['chapter']);
        }

        return $query->orderBy('code')->paginate($perPage)->withQueryString();
    }

    public function getIcdChapters(): Collection
    {
        return IcdCode::select('chapter')
            ->distinct()
            ->whereNotNull('chapter')
            ->orderBy('chapter')
            ->pluck('chapter');
    }

    /*
    |--------------------------------------------------------------------------
    | Procedure Catalog Management
    |--------------------------------------------------------------------------
    */

    public function listProcedures(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Procedure::with('department');

        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        if (!empty($filters['category'])) {
            $query->byCategory($filters['category']);
        }

        if (!empty($filters['department_id'])) {
            $query->byDepartment($filters['department_id']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        return $query->orderBy('name')->paginate($perPage)->withQueryString();
    }

    public function createProcedure(array $data): Procedure
    {
        return Procedure::create($data);
    }

    public function updateProcedure(Procedure $procedure, array $data): Procedure
    {
        $procedure->update($data);
        return $procedure;
    }

    public function toggleProcedure(Procedure $procedure): Procedure
    {
        $procedure->update(['is_active' => !$procedure->is_active]);
        return $procedure;
    }

    /*
    |--------------------------------------------------------------------------
    | Patient Procedures (Scheduling & Outcome)
    |--------------------------------------------------------------------------
    */

    public function listPatientProcedures(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = PatientProcedure::with(['visit', 'patient', 'procedure.department', 'performedByUser']);

        if (!empty($filters['status'])) {
            $query->byStatus($filters['status']);
        }

        if (!empty($filters['search'])) {
            $term = $filters['search'];
            $query->where(function ($q) use ($term) {
                $q->whereHas('patient', fn ($pq) => $pq->where('first_name', 'like', "%{$term}%")
                        ->orWhere('last_name', 'like', "%{$term}%")
                        ->orWhere('patient_number', 'like', "%{$term}%"))
                  ->orWhereHas('procedure', fn ($pq) => $pq->where('name', 'like', "%{$term}%"));
            });
        }

        if (!empty($filters['date_from'])) {
            $query->where('scheduled_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('scheduled_date', '<=', $filters['date_to'] . ' 23:59:59');
        }

        return $query->latest('scheduled_date')->paginate($perPage)->withQueryString();
    }

    public function scheduleProcedure(array $data): PatientProcedure
    {
        return PatientProcedure::create([
            'visit_id' => $data['visit_id'],
            'patient_id' => $data['patient_id'],
            'procedure_id' => $data['procedure_id'],
            'performed_by' => $data['performed_by'] ?? auth()->id(),
            'scheduled_date' => $data['scheduled_date'],
            'notes' => $data['notes'] ?? null,
            'consent_signed' => $data['consent_signed'] ?? false,
            'status' => 'scheduled',
        ]);
    }

    public function startProcedure(PatientProcedure $patientProcedure): PatientProcedure
    {
        $patientProcedure->update(['status' => 'in_progress']);
        return $patientProcedure;
    }

    public function completeProcedure(PatientProcedure $patientProcedure, array $data): PatientProcedure
    {
        $patientProcedure->update([
            'status' => 'completed',
            'performed_date' => now(),
            'performed_by' => auth()->id(),
            'outcome' => $data['outcome'] ?? null,
            'notes' => $data['notes'] ?? $patientProcedure->notes,
        ]);
        return $patientProcedure;
    }

    public function cancelProcedure(PatientProcedure $patientProcedure, ?string $reason = null): PatientProcedure
    {
        $patientProcedure->update([
            'status' => 'cancelled',
            'notes' => $reason ? ($patientProcedure->notes ? $patientProcedure->notes . "\nCancelled: " . $reason : "Cancelled: " . $reason) : $patientProcedure->notes,
        ]);
        return $patientProcedure;
    }

    public function getProcedureStats(): array
    {
        return [
            'scheduled_today' => PatientProcedure::scheduled()->whereDate('scheduled_date', today())->count(),
            'completed_today' => PatientProcedure::completed()->whereDate('performed_date', today())->count(),
            'total_procedures' => Procedure::active()->count(),
            'total_icd_codes' => IcdCode::count(),
        ];
    }
}
