<?php

namespace App\Services\Consultation;

use App\Models\Complaint;
use App\Models\Diagnosis;
use App\Models\HistoryOfPresentingComplaint;
use App\Models\PhysicalExamination;
use App\Models\Treatment;
use App\Models\User;
use App\Models\Visit;
use App\Services\ConsultationService;
use App\Services\HistoryOfPresentingComplaintService;
use App\Services\MedicalRecordEntryLogService;
use App\Services\PhysicalExaminationService;
use Illuminate\Http\Request;

class ConsultationClinicalEntryWorkflowService
{
    public function __construct(
        private readonly ConsultationIdempotencyService $idempotency,
        private readonly ConsultationService $consultations,
        private readonly HistoryOfPresentingComplaintService $hopc,
        private readonly PhysicalExaminationService $examinations,
        private readonly MedicalRecordEntryLogService $entryLogs,
    ) {}

    public function createComplaint(Request $request, Visit $visit, ConsultationActionContext $context, array $data): Complaint
    {
        return $this->idempotent($request, 'complaint.create', $visit, $context, $data, fn () => $this->consultations->addComplaint($context->medicalRecord, $data));
    }

    public function updateComplaint(Complaint $complaint, array $data): Complaint
    {
        return $this->consultations->updateComplaint($complaint, $data);
    }

    public function deleteComplaint(Complaint $complaint): void
    {
        $this->consultations->deleteComplaint($complaint);
    }

    public function createHistoryOfPresentingComplaint(Request $request, Visit $visit, ConsultationActionContext $context, array $data, User $user): HistoryOfPresentingComplaint
    {
        return $this->idempotent($request, 'hopc.create', $visit, $context, $data, fn () => $this->hopc->create($context->medicalRecord, $data, $user));
    }

    public function updateHistoryOfPresentingComplaint(HistoryOfPresentingComplaint $hopc, array $data, User $user): HistoryOfPresentingComplaint
    {
        $old = $hopc->getOriginal();
        $hopc->update(array_merge($data, ['updated_by' => $user->id]));
        $this->entryLogs->updated($hopc, $old, $user);

        return $hopc;
    }

    public function deleteHistoryOfPresentingComplaint(HistoryOfPresentingComplaint $hopc, User $user): void
    {
        $this->entryLogs->deleted($hopc, $user);
        $hopc->delete();
    }

    public function createExamination(Request $request, Visit $visit, ConsultationActionContext $context, array $data, User $user): PhysicalExamination
    {
        return $this->idempotent($request, 'examination.create', $visit, $context, $data, fn () => $this->examinations->create($context->medicalRecord, $data, $user));
    }

    public function updateExamination(PhysicalExamination $examination, array $data, User $user): PhysicalExamination
    {
        $old = $examination->getOriginal();
        $examination->update(array_merge($data, ['updated_by' => $user->id]));
        $this->entryLogs->updated($examination, $old, $user);

        return $examination;
    }

    public function deleteExamination(PhysicalExamination $examination, User $user): void
    {
        $this->entryLogs->deleted($examination, $user);
        $examination->delete();
    }

    public function createDiagnosis(Request $request, Visit $visit, ConsultationActionContext $context, array $data): Diagnosis
    {
        return $this->idempotent($request, 'diagnosis.create', $visit, $context, $data, fn () => $this->consultations->addDiagnosis($context->medicalRecord, $data));
    }

    public function updateDiagnosis(Diagnosis $diagnosis, array $data): Diagnosis
    {
        return $this->consultations->updateDiagnosis($diagnosis, $data);
    }

    public function setPrimaryDiagnosis(Diagnosis $diagnosis): Diagnosis
    {
        return $this->consultations->setPrimaryDiagnosis($diagnosis);
    }

    public function deleteDiagnosis(Diagnosis $diagnosis): void
    {
        $this->consultations->deleteDiagnosis($diagnosis);
    }

    public function createTreatment(Request $request, Visit $visit, ConsultationActionContext $context, array $data): Treatment
    {
        return $this->idempotent($request, 'treatment.create', $visit, $context, $data, fn () => $this->consultations->addTreatment($context->medicalRecord, $data));
    }

    public function updateTreatment(Treatment $treatment, array $data): Treatment
    {
        return $this->consultations->updateTreatment($treatment, $data);
    }

    public function deleteTreatment(Treatment $treatment): void
    {
        $this->consultations->deleteTreatment($treatment);
    }

    private function idempotent(Request $request, string $action, Visit $visit, ConsultationActionContext $context, array $payload, callable $callback): mixed
    {
        return $this->idempotency->run($request, $action, $visit, $context->route, $payload, $callback);
    }
}
