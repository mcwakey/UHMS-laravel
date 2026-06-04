<?php

namespace App\Services\Insurance\Verification;

use App\Enums\LogModule;
use App\Enums\LogSeverity;
use App\Models\InsuranceVerification;
use App\Models\PatientInsurance;
use App\Models\Visit;
use App\Services\ActivityLogService;
use App\Support\Insurance\VerificationRequest;
use App\Support\Insurance\VerificationResult;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Application-level verification service. Coordinates the manager, persistence
 * of the verification record, and the link to PatientInsurance / Visit.
 *
 * No provider-specific logic.
 */
class InsuranceVerificationService
{
    public function __construct(protected VerificationManager $manager) {}

    /**
     * Run a verification (or no-op if the provider does not require one) and
     * persist an InsuranceVerification audit record.
     */
    public function verify(
        PatientInsurance $insurance,
        ?Visit $visit = null,
        ?string $referenceCode = null,
        array $context = [],
    ): InsuranceVerification {
        $insurance->loadMissing(['insuranceProvider', 'patient']);
        $provider = $insurance->insuranceProvider;

        // Provider with NO driver configured -> not required.
        if (! $provider || ! $provider->verification_driver) {
            return $this->store(
                $insurance,
                $visit,
                'manual',
                VerificationResult::notRequired(),
            );
        }

        $driver = $this->manager->for($provider);
        $request = new VerificationRequest(
            patientInsurance: $insurance,
            visit: $visit,
            referenceCode: $referenceCode,
            context: $context,
        );
        $result = $driver->verify($request);

        return $this->store($insurance, $visit, $driver->name(), $result);
    }

    /**
     * Persist the result, attach to visit, update insurance summary fields.
     */
    protected function store(
        PatientInsurance $insurance,
        ?Visit $visit,
        string $driver,
        VerificationResult $result,
    ): InsuranceVerification {
        return DB::transaction(function () use ($insurance, $visit, $driver, $result) {
            $verification = InsuranceVerification::create([
                'patient_insurance_id'  => $insurance->id,
                'insurance_provider_id' => $insurance->insurance_provider_id,
                'visit_id'              => $visit?->id,
                'driver'                => $driver,
                'status'                => $result->status,
                'reference_code'        => $result->referenceCode,
                'membership_number'     => $insurance->membership_number,
                'member_name'           => $result->memberName ?? $insurance->patient?->full_name,
                'expires_at'            => $result->expiresAt?->toDateString() ?? $insurance->expiry_date,
                'verified_by'           => Auth::id(),
                'verified_at'           => now(),
                'payload'               => $result->payload,
                'message'               => $result->message,
            ]);

            if ($visit && $result->status->isAcceptable()) {
                $visit->insurance_verification_id = $verification->id;
                $visit->save();
            }

            $this->logVerification($insurance, $visit, $verification);

            return $verification->fresh(['insuranceProvider', 'patientInsurance']);
        });
    }

    /**
     * Insurance verification IS a patient-related event — it surfaces on the
     * patient timeline (module INSURANCE) with patient/visit context.
     */
    private function logVerification(PatientInsurance $insurance, ?Visit $visit, InsuranceVerification $verification): void
    {
        try {
            $status = $verification->status instanceof \BackedEnum ? $verification->status->value : (string) $verification->status;
            app(ActivityLogService::class)->log(
                LogModule::INSURANCE,
                'INSURANCE_VERIFIED',
                [
                    'patient_id' => $insurance->patient_id,
                    'visit_id' => $visit?->id,
                    'insurance_provider_id' => $insurance->insurance_provider_id,
                    'severity' => LogSeverity::NOTICE,
                    'metadata' => [
                        'driver' => $verification->driver,
                        'status' => $status,
                        'reference_code' => $verification->reference_code,
                        'provider' => $insurance->insuranceProvider?->name,
                    ],
                    'source_type' => 'insurance_verification',
                    'source_id' => $verification->id,
                ],
                $verification,
                'Insurance verified: ' . ($insurance->insuranceProvider?->name ?? 'provider') . ' (' . $status . ')',
            );
        } catch (\Throwable $e) {
            // Logging must never break insurance verification.
        }
    }
}
