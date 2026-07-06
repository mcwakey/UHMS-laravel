<?php

namespace App\Http\Controllers\Doctor\Consultations;

use App\Models\Visit;
use App\Services\Consultation\ConsultationActionException;
use App\Services\Consultation\Specialty\ConsultationSpecialtyBillingApplicationService;
use App\Services\Consultation\Specialty\ConsultationSpecialtyBillingMappingService;
use App\Services\Consultation\Specialty\ConsultationSpecialtyProfileResolver;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ConsultationSpecialtyBillingController extends ConsultationWorkflowController
{
    public function preview(
        Request $request,
        Visit $visit,
        ConsultationSpecialtyProfileResolver $resolver,
        ConsultationSpecialtyBillingMappingService $mappings,
        ConsultationSpecialtyBillingApplicationService $billing,
    ) {
        try {
            $context = $this->consultationMutationContext($request, $visit, 'specialty_billing.preview', 'consultations.create');
        } catch (ConsultationActionException $e) {
            return $this->consultationActionFailureResponse($request, $e);
        }

        $resolved = $resolver->resolve($request->user(), visit: $visit, consultationRoute: $context->route, department: $context->route?->department);
        $mapping = $mappings->resolveDefaultConsultationService($resolved->profile, $context->route?->department, $context->route);
        if (! $mapping) {
            throw ValidationException::withMessages(['billing' => __('consultation_specialties.billing.no_mapped_service')]);
        }

        return response()->json([
            'success' => true,
            'preview' => $billing->previewBilling($context->route, $mapping, $request->user(), ['record_preview' => $request->boolean('record_preview')]),
        ]);
    }

    public function apply(
        Request $request,
        Visit $visit,
        ConsultationSpecialtyProfileResolver $resolver,
        ConsultationSpecialtyBillingMappingService $mappings,
        ConsultationSpecialtyBillingApplicationService $billing,
    ) {
        try {
            $context = $this->consultationMutationContext($request, $visit, 'specialty_billing.apply', 'consultations.create');
        } catch (ConsultationActionException $e) {
            return $this->consultationActionFailureResponse($request, $e);
        }

        $resolved = $resolver->resolve($request->user(), visit: $visit, consultationRoute: $context->route, department: $context->route?->department);
        $mapping = $mappings->resolveDefaultConsultationService($resolved->profile, $context->route?->department, $context->route);
        if (! $mapping) {
            throw ValidationException::withMessages(['billing' => __('consultation_specialties.billing.no_mapped_service')]);
        }

        $result = $billing->applyBilling($context->route, $mapping, $request->user(), [
            'confirmed' => $request->boolean('confirmed'),
        ]);

        if ($this->shouldReturnJson($request)) {
            return response()->json([
                'success' => $result['status'] === 'applied',
                'message' => $result['message'],
                'result' => $result,
            ], $result['status'] === 'failed' ? 422 : 200);
        }

        return back()->with($result['status'] === 'applied' ? 'success' : 'error', $result['message']);
    }
}
