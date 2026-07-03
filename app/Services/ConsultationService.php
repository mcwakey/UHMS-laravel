<?php

namespace App\Services;

use App\Models\Complaint;
use App\Models\Diagnosis;
use App\Models\Investigation;
use App\Models\MedicalRecord;
use App\Models\Treatment;
use App\Models\Visit;
use App\Models\VisitConsultationRoute;
use Illuminate\Support\Facades\Auth;

class ConsultationService
{
    public function __construct(
        protected PrescriptionService $prescriptionService,
        protected ConsultationSessionService $sessionService,
        protected ConsultationContributorService $contributors,
        protected MedicalRecordEntryLogService $entryLogs,
        protected PatientComplaintService $patientComplaints,
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
                'consultationRoutes.mainDoctor',
                'consultationRoutes.primaryNurse',
                'consultationRoutes.medicalRecord',
                'consultationRoutes.emergencyCase',
                'consultationRoutes.emergencySession',
                'activeConsultationRoute.doctor',
                'activeConsultationRoute.routeServices.service',
                'pendingConsultationRoutes.doctor',
                'pendingConsultationRoutes.routeServices.service',
                'latestVitals',
            ]),
            'record' => $record?->load([
                'consultationRoute.contributors.user',
                'complaints.creator', 'complaints.updater', 'complaints.sourcePattern', 'complaints.complaintCatalogue',
                'historiesOfPresentingComplaint.creator', 'historiesOfPresentingComplaint.updater', 'historiesOfPresentingComplaint.complaint', 'historiesOfPresentingComplaint.sourcePattern',
                'physicalExaminations.creator', 'physicalExaminations.updater', 'physicalExaminations.sourcePattern',
                'diagnoses.creator', 'diagnoses.updater', 'diagnoses.icdCodeEntry', 'diagnoses.sourcePattern',
                'investigations.creator', 'investigations.updater', 'investigations.sourcePattern',
                'treatments.creator', 'treatments.updater', 'treatments.sourcePattern',
                'prescriptions.creator', 'prescriptions.updater', 'prescriptions.doctor', 'prescriptions.items', 'prescriptions.sourcePattern',
                'tasks.creator', 'tasks.assignedUser', 'tasks.completedBy', 'tasks.sourcePattern',
            ]),
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
            'consultationRoute.department',
            'consultationRoute.emergencyCase',
            'complaints.creator', 'complaints.complaintCatalogue',
            'historiesOfPresentingComplaint.creator',
            'physicalExaminations.creator',
            'diagnoses.creator',
            'investigations.creator',
            'treatments.creator',
            'prescriptions.creator',
            'prescriptions.items',
            'tasks.creator',
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
        return $this->patientComplaints->createForRecord($record, $data, Auth::user());
    }

    /**
     * Update a complaint.
     */
    public function updateComplaint(Complaint $complaint, array $data): Complaint
    {
        return $this->patientComplaints->update($complaint, $data, Auth::user());
    }

    /**
     * Delete a complaint.
     */
    public function deleteComplaint(Complaint $complaint): void
    {
        $this->patientComplaints->delete($complaint, Auth::user());
    }

    /**
     * Add a diagnosis to the medical record.
     * The first diagnosis added is automatically set as primary.
     */
    public function addDiagnosis(MedicalRecord $record, array $data): Diagnosis
    {
        $this->assertRecordEditable($record);

        if (! isset($data['is_primary']) && ! $record->diagnoses()->where('is_primary', true)->exists()) {
            $data['is_primary'] = true;
        }

        $diagnosis = $record->diagnoses()->create(array_merge($this->entryContext($record), $data));
        $this->afterEntryCreated($record, $diagnosis, 'Diagnosis');

        return $diagnosis->load(['creator', 'icdCodeEntry', 'sourcePattern']);
    }

    /**
     * Set a diagnosis as the primary one for its medical record.
     */
    public function setPrimaryDiagnosis(Diagnosis $diagnosis): Diagnosis
    {
        $this->assertEntryEditable($diagnosis);

        // Unset all primaries for this record first
        $diagnosis->medicalRecord->diagnoses()->update(['is_primary' => false]);
        $diagnosis->update(['is_primary' => true, 'updated_by' => Auth::id()]);

        return $diagnosis->fresh();
    }

    /**
     * Update a diagnosis.
     */
    public function updateDiagnosis(Diagnosis $diagnosis, array $data): Diagnosis
    {
        $this->assertEntryEditable($diagnosis);

        $old = $diagnosis->getOriginal();
        $diagnosis->update(array_merge($data, ['updated_by' => Auth::id()]));
        $this->entryLogs->updated($diagnosis, $old, Auth::user());

        return $diagnosis;
    }

    /**
     * Delete a diagnosis.
     */
    public function deleteDiagnosis(Diagnosis $diagnosis): void
    {
        $this->assertEntryEditable($diagnosis);

        $this->entryLogs->deleted($diagnosis, Auth::user());
        $diagnosis->delete();
    }

    /**
     * Add an investigation to the medical record.
     */
    public function addInvestigation(MedicalRecord $record, array $data): Investigation
    {
        $this->assertRecordEditable($record);

        $investigation = $record->investigations()->create(array_merge($this->entryContext($record), $data));
        $this->afterEntryCreated($record, $investigation, 'Investigation');

        return $investigation->load(['creator', 'sourcePattern']);
    }

    /**
     * Update an investigation.
     */
    public function updateInvestigation(Investigation $investigation, array $data): Investigation
    {
        $this->assertEntryEditable($investigation);

        $old = $investigation->getOriginal();
        $investigation->update(array_merge($data, ['updated_by' => Auth::id()]));
        $this->entryLogs->updated($investigation, $old, Auth::user());

        return $investigation;
    }

    /**
     * Delete an investigation.
     */
    public function deleteInvestigation(Investigation $investigation): void
    {
        $this->assertEntryEditable($investigation);

        $this->entryLogs->deleted($investigation, Auth::user());
        $investigation->delete();
    }

    /**
     * Add a treatment to the medical record.
     */
    public function addTreatment(MedicalRecord $record, array $data): Treatment
    {
        $this->assertRecordEditable($record);

        $treatment = $record->treatments()->create(array_merge($this->entryContext($record), $data));
        $this->afterEntryCreated($record, $treatment, 'Treatment');

        return $treatment->load(['creator', 'sourcePattern']);
    }

    /**
     * Update a treatment.
     */
    public function updateTreatment(Treatment $treatment, array $data): Treatment
    {
        $this->assertEntryEditable($treatment);

        $old = $treatment->getOriginal();
        $treatment->update(array_merge($data, ['updated_by' => Auth::id()]));
        $this->entryLogs->updated($treatment, $old, Auth::user());

        return $treatment;
    }

    /**
     * Delete a treatment.
     */
    public function deleteTreatment(Treatment $treatment): void
    {
        $this->assertEntryEditable($treatment);

        $this->entryLogs->deleted($treatment, Auth::user());
        $treatment->delete();
    }

    public function entryContext(MedicalRecord $record): array
    {
        return [
            'consultation_route_id' => $record->consultation_route_id,
            'visit_id' => $record->visit_id,
            'patient_id' => $record->patient_id,
            'department_id' => $record->department_id,
            'doctor_id' => Auth::id(),
            'created_by' => Auth::id(),
        ];
    }

    private function afterEntryCreated(MedicalRecord $record, object $entry, string $role): void
    {
        if ($user = Auth::user()) {
            $this->contributors->recordContribution($record, $user, $role);
            $this->entryLogs->created($entry, $user);
        }
    }

    private function assertRecordEditable(MedicalRecord $record): void
    {
        $route = $record->consultationRoute;
        if (! $route || (! $route->locked_at && ! in_array($route->status, [
            VisitConsultationRoute::STATUS_COMPLETED,
            VisitConsultationRoute::STATUS_CANCELLED,
        ], true))) {
            return;
        }

        $user = Auth::user();
        if ($user && method_exists($user, 'can') && ($user->can('consultation.entries.correct_completed') || $user->can('visits.reopen_locked_session'))) {
            return;
        }

        throw new \RuntimeException('This outpatient consultation session is locked or completed. Use correction permission to amend it.');
    }

    private function assertEntryEditable(object $entry): void
    {
        $record = method_exists($entry, 'medicalRecord') ? $entry->medicalRecord : null;
        if ($record instanceof MedicalRecord) {
            $this->assertRecordEditable($record);
        }
    }
}
