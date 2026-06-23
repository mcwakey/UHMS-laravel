<?php

namespace App\Http\Controllers\Admin\Insurance;

use App\Http\Controllers\Controller;
use App\Models\PatientInsurance;
use App\Models\Visit;
use App\Services\Insurance\Verification\InsuranceVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Provider-agnostic AJAX endpoint to run an insurance verification through
 * whichever driver the selected provider is configured to use.
 */
class InsuranceVerificationController extends Controller
{
    public function __construct(protected InsuranceVerificationService $service) {}

    public function verify(Request $request): JsonResponse
    {
        $data = $request->validate([
            'patient_insurance_id' => ['required', 'exists:patient_insurances,id'],
            'visit_id'             => ['nullable', 'exists:visits,id'],
            'reference_code'       => ['nullable', 'string', 'max:80'],
        ]);

        $insurance = PatientInsurance::with('insuranceProvider', 'patient')->findOrFail($data['patient_insurance_id']);
        $visit = isset($data['visit_id']) ? Visit::find($data['visit_id']) : null;

        $verification = $this->service->verify(
            insurance: $insurance,
            visit: $visit,
            referenceCode: $data['reference_code'] ?? null,
        );

        $provider = $insurance->insuranceProvider;

        return response()->json([
            'verification_id' => $verification->id,
            'driver'          => $verification->driver,
            'status'          => $verification->status->value,
            'status_label'    => $verification->status->label(),
            'status_color'    => $verification->status->color(),
            'reference_code'  => $verification->reference_code,
            'member_name'     => $verification->member_name,
            'expires_at'      => $verification->expires_at?->toDateString(),
            'message'         => $verification->message,
            'acceptable'      => $verification->isAcceptable(),
            'requires_reference_code' => $provider?->requiresVerification()
                ? app(\App\Services\Insurance\Verification\VerificationManager::class)->for($provider)->requiresReferenceCode()
                : false,
            'provider' => [
                'id'      => $provider?->id,
                'name'    => $provider?->name,
                'driver'  => $provider?->verification_driver,
                'method'  => $provider?->verification_method,
                'channel' => $provider?->verification_channel,
            ],
        ]);
    }
}
