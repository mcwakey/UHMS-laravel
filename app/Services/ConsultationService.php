<?php

namespace App\Services;

use App\Models\Complaint;
use App\Models\Diagnosis;
use App\Models\Investigation;
use App\Models\MedicalRecord;
use App\Models\Treatment;
use App\Models\Visit;
use Illuminate\Support\Facades\Auth;

class ConsultationService
{
    public function __construct(
        protected PrescriptionService $prescriptionService,
        protected ConsultationSessionService $sessionService,
    ) {}

    /**
     * Get or create a medical record for the visit.
     *
     * Populates service / department / consultation_route linkage from the
     * currently active consultation route so the record is auditable back
     * to the exact consultation that produced it. Existing records are
     * back-filled with the linkage on the first read if it was previously
     * missing.
     */
    public function getOrCreateRecord(Visit $visit, ?int $consultationRouteId = null): MedicalRecord
    {
        $activeRoute = $this->sessionService->resolveRouteForVisit($visit, $consultationRouteId);
        if ($activeRoute) {
            return $this->sessionService->getOrCreateMedicalRecordForRoute($activeRoute, Auth::user());
        }

        $linkage = [
            'service_id' => null,
            'department_id' => $visit->current_department_id,
            'consultation_route_id' => null,
        ];

        $record = MedicalRecord::firstOrCreate(
            ['visit_id' => $visit->id],
            array_merge([
                'patient_id' => $visit->patient_id,
                'doctor_id' => Auth::id(),
            ], $linkage)
        );

        // Back-fill linkage on existing records if any field is still NULL
        // and we now have a value to put there.
        $updates = [];
        foreach ($linkage as $col => $val) {
            if ($val !== null && empty($record->{$col})) {
                $updates[$col] = $val;
            }
        }
        if ($updates) {
            $record->forceFill($updates)->save();
        }

        return $record;
    }

    /**
     * Get the full consultation data for a visit.
     */
    public function getConsultationData(Visit $visit, ?MedicalRecord $record = null, bool $useLegacyFallback = true): array
    {
        if (! $record && $useLegacyFallback) {
            $record = $visit->medicalRecord;
        }

        return [
            'visit' => $visit->load([
                'patient',
                'visitInsurance.insuranceProvider',
                'consultationRoutes.department',
                'consultationRoutes.service',
                'consultationRoutes.services',
                'consultationRoutes.routeServices.service',
                'consultationRoutes.doctor',
                'consultationRoutes.medicalRecord',
                'activeConsultationRoute.doctor',
                'activeConsultationRoute.routeServices.service',
                'pendingConsultationRoutes.doctor',
                'pendingConsultationRoutes.routeServices.service',
                'latestVitals',
            ]),
            'record' => $record?->load(['complaints', 'diagnoses', 'investigations', 'treatments', 'prescriptions.items']),
            'vitals' => $visit->vitals()->with('recordedBy')->latest()->get(),
            'history' => $this->getPatientHistory($visit->patient_id, $visit->id),
        ];
    }

    /**
     * Get patient medical history (from other visits).
     */
    public function getPatientHistory(int $patientId, ?int $excludeVisitId = null): array
    {
        $query = MedicalRecord::with([
            'visit.activeConsultationRoute.doctor',
            'visit.pendingConsultationRoutes.doctor',
            'complaints',
            'diagnoses',
            'investigations',
            'treatments',
            'prescriptions.items',
        ])
            ->where('patient_id', $patientId)
            ->latest();

        if ($excludeVisitId) {
            $query->where('visit_id', '!=', $excludeVisitId);
        }

        $records = $query->take(10)->get();
        $total = MedicalRecord::where('patient_id', $patientId)
            ->when($excludeVisitId, fn ($q) => $q->where('visit_id', '!=', $excludeVisitId))
            ->count();

        return [
            'records' => $records,
            'total' => $total,
        ];
    }

    /**
     * Add a complaint to the medical record.
     */
    public function addComplaint(MedicalRecord $record, array $data): Complaint
    {
        return $record->complaints()->create($data);
    }

    /**
     * Update a complaint.
     */
    public function updateComplaint(Complaint $complaint, array $data): Complaint
    {
        $complaint->update($data);

        return $complaint;
    }

    /**
     * Delete a complaint.
     */
    public function deleteComplaint(Complaint $complaint): void
    {
        $complaint->delete();
    }

    /**
     * Add a diagnosis to the medical record.
     * The first diagnosis added is automatically set as primary.
     */
    public function addDiagnosis(MedicalRecord $record, array $data): Diagnosis
    {
        if (! isset($data['is_primary']) && ! $record->diagnoses()->where('is_primary', true)->exists()) {
            $data['is_primary'] = true;
        }

        return $record->diagnoses()->create($data);
    }

    /**
     * Set a diagnosis as the primary one for its medical record.
     */
    public function setPrimaryDiagnosis(Diagnosis $diagnosis): Diagnosis
    {
        // Unset all primaries for this record first
        $diagnosis->medicalRecord->diagnoses()->update(['is_primary' => false]);
        $diagnosis->update(['is_primary' => true]);

        return $diagnosis->fresh();
    }

    /**
     * Update a diagnosis.
     */
    public function updateDiagnosis(Diagnosis $diagnosis, array $data): Diagnosis
    {
        $diagnosis->update($data);

        return $diagnosis;
    }

    /**
     * Delete a diagnosis.
     */
    public function deleteDiagnosis(Diagnosis $diagnosis): void
    {
        $diagnosis->delete();
    }

    /**
     * Add an investigation to the medical record.
     */
    public function addInvestigation(MedicalRecord $record, array $data): Investigation
    {
        return $record->investigations()->create($data);
    }

    /**
     * Update an investigation.
     */
    public function updateInvestigation(Investigation $investigation, array $data): Investigation
    {
        $investigation->update($data);

        return $investigation;
    }

    /**
     * Delete an investigation.
     */
    public function deleteInvestigation(Investigation $investigation): void
    {
        $investigation->delete();
    }

    /**
     * Add a treatment to the medical record.
     */
    public function addTreatment(MedicalRecord $record, array $data): Treatment
    {
        return $record->treatments()->create($data);
    }

    /**
     * Update a treatment.
     */
    public function updateTreatment(Treatment $treatment, array $data): Treatment
    {
        $treatment->update($data);

        return $treatment;
    }

    /**
     * Delete a treatment.
     */
    public function deleteTreatment(Treatment $treatment): void
    {
        $treatment->delete();
    }
}
