<?php

namespace App\Services;

use App\Enums\AdmissionStatus;
use App\Enums\LogModule;
use App\Enums\LogSeverity;
use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Events\PatientAdmitted;
use App\Events\PatientDischarged;
use App\Models\Admission;
use App\Models\EmergencyCase;
use App\Models\WardRound;
use App\Services\Admissions\BedWorkflowService;
use App\Services\Consultation\ConsultationAutoCompletionService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdmissionService
{
    public function __construct(
        protected InsuranceService $insuranceService,
        protected AdmissionBedBillingService $admissionBilling,
        protected EmergencyBayService $emergencyBays,
        protected VisitStatusService $statuses,
        protected VisitPathwayService $pathway,
        protected BedWorkflowService $bedWorkflow,
        protected InpatientWorkspaceScope $inpatientScope,
        protected ?ActivityLogService $logger = null,
    ) {
        $this->logger = $this->logger ?: app(ActivityLogService::class);
    }

    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = Admission::with(['patient', 'bed.ward', 'admittedBy', 'visit']);
        $this->inpatientScope->admissions($query);

        if (! empty($filters['search'])) {
            $query->search($filters['search']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['ward_id'])) {
            $query->byWard($filters['ward_id']);
        }

        return $query->latest('admission_date')->paginate($filters['per_page'] ?? 15);
    }

    public function admit(array $data): Admission
    {
        $admission = DB::transaction(function () use ($data) {
            $admissionFields = array_intersect_key($data, array_flip([
                'admission_number', 'admission_request_id', 'visit_id', 'patient_id', 'bed_id', 'admitted_by',
                'admitting_diagnosis', 'admission_date', 'expected_discharge_date',
                'admission_type', 'admission_fee_service_id', 'consumable_fee_service_id',
            ]));
            $admissionFields['admission_number'] = Admission::generateAdmissionNumber();
            $admissionFields['admitted_by'] = Auth::id();
            $admissionFields['admission_date'] = $data['admission_date'] ?? now();

            $admission = Admission::create($admissionFields);

            // Mark bed as occupied
            $bed = $admission->bed;
            $bed->markOccupied(Auth::id(), 'Patient admitted');

            // Transition visit status to ADMITTED and update type to INPATIENT
            $visit = $admission->visit;
            $this->statuses->setAdmitted($visit,
                'Patient admitted ('.($data['admission_type'] ?? 'admission').') to '
                .$bed->ward->name.' - Bed '.$bed->bed_number);
            $visit->update(['visit_type' => VisitType::INPATIENT->value]);

            $this->admissionBilling->createInitialCharges($admission, $data);
            $this->releaseEmergencyBedIfPresent($admission);
            $this->bedWorkflow->recordAdmissionStart($admission, Auth::user());
            $this->bedWorkflow->fulfillReservationForAdmission($admission, Auth::user());

            $this->pathway->record($visit->fresh(), 'ADMISSION_STARTED', [
                'source' => $admission,
                'title' => 'Admission started',
                'description' => $bed->ward->name.' / Bed '.$bed->bed_number,
            ]);

            return $admission->load(['patient', 'bed.ward', 'admittedBy']);
        });

        PatientAdmitted::dispatch($admission);

        $this->logger?->log(LogModule::ADMISSION, 'ADMITTED', [
            'admission_id' => $admission->id,
            'patient_id' => $admission->patient_id,
            'visit_id' => $admission->visit_id,
            'metadata' => [
                'bed_id' => $admission->bed_id,
                'admission_type' => $data['admission_type'] ?? null,
            ],
        ], $admission, 'Patient admitted');

        return $admission;
    }

    private function releaseEmergencyBedIfPresent(Admission $admission): void
    {
        $case = EmergencyCase::query()
            ->where('visit_id', $admission->visit_id)
            ->where('disposition', EmergencyCase::DISPOSITION_ADMITTED)
            ->latest('id')
            ->first();

        if (! $case) {
            return;
        }

        $case->update(['admission_id' => $admission->id]);

        if ($case->emergency_bay_id) {
            $this->emergencyBays->release($case->fresh(['bay', 'activeBayAssignment']), user: Auth::user());
        }
    }

    public function discharge(Admission $admission, array $data): Admission
    {
        $admission = DB::transaction(function () use ($admission, $data) {
            $admission->update([
                'actual_discharge_date' => now(),
                'discharged_by' => Auth::id(),
                'discharge_summary' => $data['discharge_summary'] ?? null,
                'discharge_instructions' => $data['discharge_instructions'] ?? null,
                'status' => AdmissionStatus::DISCHARGED,
            ]);

            // Free up the bed and retain an auditable location-history record.
            $this->bedWorkflow->releaseBedForDischarge($admission, Auth::user());

            // Transition visit to discharging (pending billing)
            $visit = $admission->visit;
            if ($visit->canTransitionTo(VisitStatus::DISCHARGING)) {
                $visit->transitionTo(VisitStatus::DISCHARGING, 'Patient discharge initiated');
            }
            app(ConsultationAutoCompletionService::class)
                ->evaluate($visit, Auth::user(), $visit->consultationRoutes()->latest('id')->first(), 'Patient discharged');

            return $admission->fresh(['patient', 'bed.ward', 'dischargedBy']);
        });

        PatientDischarged::dispatch($admission);

        $this->logger?->log(LogModule::ADMISSION, 'DISCHARGED', [
            'severity' => LogSeverity::NOTICE,
            'admission_id' => $admission->id,
            'patient_id' => $admission->patient_id,
            'visit_id' => $admission->visit_id,
            'metadata' => [
                'discharge_type' => $data['discharge_type'] ?? null,
                'discharged_by' => Auth::id(),
            ],
        ], $admission, 'Patient discharged');

        return $admission;
    }

    public function addWardRound(Admission $admission, array $data): WardRound
    {
        return $admission->wardRounds()->create([
            'recorded_by' => Auth::id(),
            'round_date' => $data['round_date'] ?? now(),
            'notes' => $data['notes'],
            'instructions' => $data['instructions'] ?? null,
        ]);
    }

    public function getCurrentInpatients(array $filters = []): LengthAwarePaginator
    {
        $query = Admission::with(['patient', 'bed.ward', 'admittedBy', 'visit'])
            ->where('status', AdmissionStatus::ADMITTED);
        $this->inpatientScope->admissions($query);

        if (! empty($filters['search'])) {
            $query->search($filters['search']);
        }

        if (! empty($filters['ward_id'])) {
            $query->byWard($filters['ward_id']);
        }

        return $query->latest('admission_date')->paginate($filters['per_page'] ?? 15);
    }

    public function getStats(): array
    {
        $admissions = Admission::query();
        $this->inpatientScope->admissions($admissions);

        return [
            'total_admitted' => (clone $admissions)->where('status', AdmissionStatus::ADMITTED)->count(),
            'discharged_today' => (clone $admissions)->where('status', AdmissionStatus::DISCHARGED)
                ->whereDate('actual_discharge_date', today())->count(),
            'admitted_today' => (clone $admissions)->whereDate('admission_date', today())->count(),
        ];
    }
}
