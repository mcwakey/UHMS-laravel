<?php

namespace App\Services\Consultation;

use App\Enums\LogModule;
use App\Enums\LogSeverity;
use App\Models\Prescription;
use App\Models\Visit;
use App\Services\ActivityLogService;
use App\Services\PrescriptionService;
use Illuminate\Http\Request;

class ConsultationPrescriptionWorkflowService
{
    public function __construct(
        private readonly PrescriptionService $prescriptions,
        private readonly ConsultationIdempotencyService $idempotency,
        private readonly PrescriptionSafetyService $safety,
        private readonly ActivityLogService $activityLog,
    ) {}

    public function create(Request $request, Visit $visit, ConsultationActionContext $context, array $data): Prescription
    {
        $overrideReason = trim((string) ($data['safety_override_reason'] ?? ''));
        $overrideCodes = collect($data['safety_override_codes'] ?? [])->filter()->values()->all();
        unset($data['safety_override_reason'], $data['safety_override_codes']);

        return $this->idempotency->run(
            $request,
            'prescription.create',
            $visit,
            $context->route,
            $data + ['consultation_route_id' => $context->route->id],
            function () use ($visit, $context, $data, $overrideReason, $overrideCodes) {
                $result = $this->safety->assess($context->medicalRecord, $data);
                $this->assertSafeToCreate($result, $visit, $context, $overrideReason, $overrideCodes);

                return $this->prescriptions->create($context->medicalRecord, $data);
            },
        );
    }

    private function assertSafeToCreate(
        PrescriptionSafetyResult $result,
        Visit $visit,
        ConsultationActionContext $context,
        string $overrideReason,
        array $overrideCodes,
    ): void {
        if ($result->hasBlockingErrors()) {
            throw new PrescriptionSafetyException(
                __('consultation.safety.missing_required_field'),
                $result,
                422,
                false,
                'PRESCRIPTION_SAFETY_BLOCKED',
            );
        }

        if (! $result->hasWarnings()) {
            return;
        }

        if ($overrideReason === '') {
            throw new PrescriptionSafetyException(
                __('consultation.safety.override_required'),
                $result,
                409,
                true,
                'PRESCRIPTION_SAFETY_OVERRIDE_REQUIRED',
            );
        }

        $this->logSafetyEvent('PRESCRIPTION_SAFETY_OVERRIDE_ACCEPTED', $visit, $context, [
            'warnings' => $result->warnings(),
            'override_codes' => $overrideCodes,
            'reason' => $overrideReason,
        ]);
    }

    private function logSafetyEvent(string $event, Visit $visit, ConsultationActionContext $context, array $data): void
    {
        $this->activityLog->log(LogModule::CONSULTATION, $event, array_merge($data, [
            'severity' => LogSeverity::WARNING,
            'patient_id' => $visit->patient_id,
            'visit_id' => $visit->id,
            'consultation_route_id' => $context->route->id,
            'medical_record_id' => $context->medicalRecord->id,
            'causer' => $context->user,
        ]), $context->route, __('consultation.safety.prescription_warning'));
    }
}
