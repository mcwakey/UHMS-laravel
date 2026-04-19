<?php

namespace App\Services;

use App\Models\Complaint;
use App\Models\Diagnosis;
use App\Models\Investigation;
use App\Models\MedicalRecord;
use App\Models\Patient;
use App\Models\Treatment;
use App\Models\Visit;

class ConsultationService
{
    public function __construct(
        protected PrescriptionService $prescriptionService,
    ) {}

    /**
     * Get or create a medical record for the visit.
     */
    public function getOrCreateRecord(Visit $visit): MedicalRecord
    {
        return MedicalRecord::firstOrCreate(
            ['visit_id' => $visit->id],
            [
                'patient_id' => $visit->patient_id,
                'doctor_id' => auth()->id(),
            ]
        );
    }

    /**
     * Get the full consultation data for a visit.
     */
    public function getConsultationData(Visit $visit): array
    {
        $record = $visit->medicalRecord;

        return [
            'visit' => $visit->load(['patient', 'assignedDoctor', 'latestVitals']),
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
        $query = MedicalRecord::with(['visit', 'complaints', 'diagnoses', 'prescriptions.items'])
            ->where('patient_id', $patientId)
            ->latest();

        if ($excludeVisitId) {
            $query->where('visit_id', '!=', $excludeVisitId);
        }

        return [
            'records' => $query->take(10)->get(),
            'total' => $query->count(),
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
     */
    public function addDiagnosis(MedicalRecord $record, array $data): Diagnosis
    {
        return $record->diagnoses()->create($data);
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
