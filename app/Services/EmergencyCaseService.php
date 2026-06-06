<?php

namespace App\Services;

use App\Enums\LogModule;
use App\Enums\Priority;
use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Models\EmergencyCase;
use App\Models\InvoiceItem;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitConsultationRoute;
use App\Models\VisitConsultationRouteService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class EmergencyCaseService
{
    public function __construct(
        private EmergencyNumberService $numbers,
        private EmergencyTimelineService $timeline,
        private EmergencyBayService $bays,
        private PatientMergeGuard $patientMergeGuard,
        private EmergencySessionService $sessions,
        private PatientComplaintService $patientComplaints,
        private VisitPathwayService $pathway,
        private BillingService $billing,
        private ?\App\Services\ActivityLogService $logger = null,
    ) {
        $this->logger = $this->logger ?: app(\App\Services\ActivityLogService::class);
    }

    public function create(array $data, User $user): EmergencyCase
    {
        return DB::transaction(function () use ($data, $user) {
            $existingVisit = ! empty($data['visit_id'])
                ? Visit::query()->lockForUpdate()->with('patient')->findOrFail($data['visit_id'])
                : null;

            $patient = $existingVisit?->patient ?? (empty($data['patient_id'])
                ? $this->createTemporaryPatient($data, $user)
                : Patient::findOrFail($data['patient_id']));

            if ($existingVisit && ! empty($data['patient_id']) && (int) $data['patient_id'] !== (int) $existingVisit->patient_id) {
                throw new \InvalidArgumentException('The selected emergency patient does not match the existing visit patient.');
            }

            if ($existingVisit && EmergencyCase::query()
                ->where('visit_id', $existingVisit->id)
                ->whereNotIn('emergency_status', [EmergencyCase::STATUS_DISPOSED, EmergencyCase::STATUS_CANCELLED])
                ->exists()) {
                throw new \InvalidArgumentException('This visit already has an active emergency case.');
            }

            $this->patientMergeGuard->assertCanReceiveNewRecords($patient, 'emergency case');

            $arrival = Carbon::parse($data['arrival_time'] ?? now());

            $visit = $existingVisit ?: Visit::create([
                'visit_number' => Visit::generateVisitNumber(),
                'patient_id' => $patient->id,
                'patient_age' => $patient->date_of_birth?->age,
                'visit_type' => VisitType::EMERGENCY->value,
                'visit_date' => $arrival->toDateString(),
                'status' => VisitStatus::EMERGENCY->value,
                'priority' => Priority::EMERGENCY->value,
                'chief_complaint' => $data['chief_complaint'] ?? null,
                'notes' => $data['initial_condition'] ?? null,
                'checked_in_at' => $arrival,
                'created_by' => $user->id,
            ]);

            if ($existingVisit) {
                if ($visit->canTransitionTo(VisitStatus::EMERGENCY)) {
                    $visit->transitionTo(VisitStatus::EMERGENCY, 'Emergency case opened from existing visit');
                } else {
                    $visit->update(['status' => VisitStatus::EMERGENCY->value]);
                }

                $visit->update([
                    'visit_type' => VisitType::EMERGENCY->value,
                    'priority' => Priority::EMERGENCY->value,
                    'chief_complaint' => $data['chief_complaint'] ?? $visit->chief_complaint,
                    'notes' => $data['initial_condition'] ?? $visit->notes,
                    'checked_in_at' => $visit->checked_in_at ?? $arrival,
                ]);
            } else {
                $this->pathway->record($visit, 'VISIT_REGISTERED', [
                    'title' => 'Emergency visit registered',
                    'description' => $data['chief_complaint'] ?? null,
                ]);
            }

            $case = EmergencyCase::create([
                'emergency_number' => $this->numbers->generateCaseNumber(),
                'visit_id' => $visit->id,
                'patient_id' => $patient->id,
                'arrival_mode' => $data['arrival_mode'],
                'arrival_time' => $arrival,
                'brought_by' => $data['brought_by'] ?? null,
                'source' => $data['source'] ?? null,
                'referral_facility' => $data['referral_facility'] ?? null,
                'chief_complaint' => $data['chief_complaint'] ?? null,
                'initial_condition' => $data['initial_condition'] ?? null,
                'emergency_status' => EmergencyCase::STATUS_WAITING_TRIAGE,
                'assigned_doctor_id' => $data['assigned_doctor_id'] ?? null,
                'assigned_nurse_id' => $data['assigned_nurse_id'] ?? null,
                'created_by' => $user->id,
            ]);

            $this->timeline->record($case, 'ARRIVAL', 'Emergency case created', $case->chief_complaint, $case, $user);
            $session = $this->sessions->getOrCreateForCase($case, $user);
            if ($session->medicalRecord) {
                $this->patientComplaints->syncEmergencyChiefComplaint($case, $session->medicalRecord, $user);
            }

            // Bill the Emergency / Casualty consultation under the running-bill
            // policy (idempotent — never blocks care for non-payment).
            $this->ensureEmergencyConsultationBilling($case);

            $this->pathway->record($visit->fresh(), 'EMERGENCY_SESSION_CREATED', [
                'source' => $case,
                'title' => 'Emergency Session created',
                'description' => $existingVisit ? 'Emergency Session was attached to the existing visit.' : 'Emergency Session was created for the emergency visit.',
                'created_by' => $user->id,
            ]);

            if (! empty($data['emergency_bay_id'])) {
                $this->bays->assign($case, (int) $data['emergency_bay_id'], $user, true);
            }

            $this->logger?->log(LogModule::EMERGENCY, 'CASE_CREATED', [
                'emergency_case_id' => $case->id,
                'patient_id' => $patient->id,
                'visit_id' => $visit->id,
                'metadata' => [
                    'arrival_mode' => $data['arrival_mode'],
                    'temporary_patient' => $patient->is_temporary ?? false,
                ],
            ], $case, 'Emergency case created');

            return $case->fresh(['patient', 'visit', 'bay', 'assignedDoctor', 'assignedNurse', 'activeEmergencySession']);
        });
    }

    /**
     * Attach + bill the default Emergency / Casualty consultation service for the
     * case's visit and link it to the emergency consultation route. Idempotent:
     * the billing funnel prevents duplicate invoice items, and the route↔service
     * link is an updateOrCreate. Running-bill policy — does NOT require payment.
     */
    private function ensureEmergencyConsultationBilling(EmergencyCase $case): void
    {
        if ($case->emergency_status === EmergencyCase::STATUS_CANCELLED) {
            return;
        }

        $service = $this->sessions->defaultConsultationService($case);
        $visit = $case->visit;
        if (! $service || ! $visit) {
            return;
        }

        $invoiceItem = null;
        try {
            $invoiceItem = $this->billing->addItemToVisitInvoice(
                visit: $visit,
                service: $service,
                sourceType: 'emergency_service',
                sourceId: $service->id,
                quantity: 1,
                departmentId: $service->department_id,
                description: $service->name,
            );
        } catch (\RuntimeException $e) {
            // Already billed (duplicate guard) → reuse the existing item; any other
            // billing error must NOT break emergency case creation.
            if (! str_contains($e->getMessage(), 'Duplicate billing prevented')) {
                return;
            }
            $invoiceItem = InvoiceItem::where('visit_id', $visit->id)
                ->where('service_catalog_id', $service->id)
                ->whereIn('source_type', ['emergency_service', 'service_catalog'])
                ->orderBy('id')
                ->first();
        } catch (\Throwable $e) {
            return;
        }

        $route = VisitConsultationRoute::where('emergency_case_id', $case->id)
            ->oldest('id')
            ->first();

        if ($route) {
            VisitConsultationRouteService::updateOrCreate(
                ['visit_consultation_route_id' => $route->id, 'service_id' => $service->id],
                ['visit_id' => $visit->id, 'invoice_item_id' => $invoiceItem?->id],
            );
        }
    }

    private function createTemporaryPatient(array $data, User $user): Patient
    {
        $displayName = trim((string) ($data['temporary_display_name'] ?? 'Unknown Emergency Patient'));
        $parts = preg_split('/\s+/', $displayName, 2);
        $estimatedAge = max(0, min(120, (int) ($data['estimated_age'] ?? 30)));

        return Patient::create([
            'patient_number' => $this->numbers->generateTemporaryPatientNumber(),
            'first_name' => $parts[0] ?: 'Unknown',
            'last_name' => $parts[1] ?? 'Emergency',
            'date_of_birth' => now()->subYears($estimatedAge)->toDateString(),
            'gender' => $data['temporary_gender'] ?? 'male',
            'phone' => '0000000000',
            'status' => 'active',
            'is_temporary' => true,
            'temporary_reason' => $data['temporary_reason'] ?? 'Emergency identity not confirmed',
            'registered_by' => $user->id,
        ]);
    }
}
