<?php

namespace App\Services;

use App\Enums\AdmissionStatus;
use App\Enums\BedStatus;
use App\Enums\VisitStatus;
use App\Events\PatientAdmitted;
use App\Events\PatientDischarged;
use App\Models\Admission;
use App\Models\WardRound;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class AdmissionService
{
    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = Admission::with(['patient', 'bed.ward', 'admittedBy', 'visit']);

        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['ward_id'])) {
            $query->byWard($filters['ward_id']);
        }

        return $query->latest('admission_date')->paginate($filters['per_page'] ?? 15);
    }

    public function admit(array $data): Admission
    {
        return DB::transaction(function () use ($data) {
            $data['admission_number'] = Admission::generateAdmissionNumber();
            $data['admitted_by'] = auth()->id();
            $data['admission_date'] = $data['admission_date'] ?? now();

            $admission = Admission::create($data);

            // Mark bed as occupied
            $admission->bed->markOccupied();

            // Transition visit to admitted
            $visit = $admission->visit;
            if ($visit->canTransitionTo(VisitStatus::ADMITTED)) {
                $visit->transitionTo(VisitStatus::ADMITTED, 'Patient admitted to ' . $admission->bed->ward->name . ' - Bed ' . $admission->bed->bed_number);
            }

            return $admission->load(['patient', 'bed.ward', 'admittedBy']);
        });

        PatientAdmitted::dispatch($admission);

        return $admission;
    }

    public function discharge(Admission $admission, array $data): Admission
    {
        return DB::transaction(function () use ($admission, $data) {
            $admission->update([
                'actual_discharge_date' => now(),
                'discharged_by' => auth()->id(),
                'discharge_summary' => $data['discharge_summary'] ?? null,
                'discharge_instructions' => $data['discharge_instructions'] ?? null,
                'status' => AdmissionStatus::DISCHARGED,
            ]);

            // Free up the bed
            $admission->bed->markAvailable();

            // Transition visit to discharging (pending billing)
            $visit = $admission->visit;
            if ($visit->canTransitionTo(VisitStatus::DISCHARGING)) {
                $visit->transitionTo(VisitStatus::DISCHARGING, 'Patient discharge initiated');
            }

            return $admission->fresh(['patient', 'bed.ward', 'dischargedBy']);
        });

        PatientDischarged::dispatch($admission);

        return $admission;
    }

    public function addWardRound(Admission $admission, array $data): WardRound
    {
        return $admission->wardRounds()->create([
            'recorded_by' => auth()->id(),
            'round_date' => $data['round_date'] ?? now(),
            'notes' => $data['notes'],
            'instructions' => $data['instructions'] ?? null,
        ]);
    }

    public function getCurrentInpatients(array $filters = []): LengthAwarePaginator
    {
        $query = Admission::with(['patient', 'bed.ward', 'admittedBy', 'visit'])
            ->where('status', AdmissionStatus::ADMITTED);

        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        if (!empty($filters['ward_id'])) {
            $query->byWard($filters['ward_id']);
        }

        return $query->latest('admission_date')->paginate($filters['per_page'] ?? 15);
    }

    public function getStats(): array
    {
        return [
            'total_admitted' => Admission::where('status', AdmissionStatus::ADMITTED)->count(),
            'discharged_today' => Admission::where('status', AdmissionStatus::DISCHARGED)
                ->whereDate('actual_discharge_date', today())->count(),
            'admitted_today' => Admission::whereDate('admission_date', today())->count(),
        ];
    }
}
